<?php
namespace local_imagedesc;

defined('MOODLE_INTERNAL') || die();

class observers {
    /** Armazena IDs de posts já processados nesta execução */
    private static $processed_posts = [];

    /** Não faz nada na criação do post */
    public static function forum_post_created($event) {
        // Uso mtrace para logs em CLI
        mtrace("[local_imagedesc] Post criado (ID: {$event->objectid}). Nenhuma ação necessária.");
    }

    /** Gera ou atualiza alt text para todas as imagens em um post */
    public static function update_post_description($event) {
        global $DB, $CFG;
        $postid = $event->objectid;

        // Log inicial de entrada
        mtrace("[local_imagedesc] *** Entrou em update_post_description para post ID $postid");

        // Evita duplicação na mesma execução
        if (in_array($postid, self::$processed_posts)) {
            mtrace("[local_imagedesc] Aviso: post ID $postid já processado nesta execução. Pulando.");
            return;
        }
        self::$processed_posts[] = $postid;

        // Carrega o post
        $post = $DB->get_record('forum_posts', ['id' => $postid], '*', MUST_EXIST);
        $html = $post->message;
        if (empty($html)) {
            mtrace("[local_imagedesc] Post ID $postid sem conteúdo HTML. Pulando.");
            return;
        }

        // Encontra imagens no HTML
        if (!preg_match_all('/<img[^>]+src="@@PLUGINFILE@@\/([^"\s>]+)"/i', $html, $matches)) {
            mtrace("[local_imagedesc] Nenhuma imagem encontrada no post ID $postid. Pulando.");
            return;
        }
        $filenames = array_unique($matches[1]);
        mtrace("[local_imagedesc] Encontradas " . count($filenames) . " imagem(ns) no post ID $postid.");

        // Prompt de acessibilidade
        $prompt = <<<'PROMPT'
Você é um assistente de acessibilidade visual especializado em descrever imagens para pessoas com deficiência visual. Ao receber uma imagem:
1. Responda **somente** com a descrição da imagem, sem comentários, explicações sobre o processo ou informações irrelevantes.
2. Siga as diretrizes de áudio-transcrição:
   - Use linguagem clara e objetiva.
   - Forneça os detalhes essenciais: cenário, objetos, pessoas, cores relevantes e ações.
   - Caso haja texto na imagem, transcreva-o fielmente, indicando quem “fala” ou onde está posicionado (ex.: “no canto superior esquerdo, o texto ‘Boas-vindas’”).
   - Se a imagem contiver uma equação, leia-a como se fosse para reprodução em voz: descreva operadores, expoentes e igualdade (ex.: “equis ao quadrado mais dois vezes equis menos um igual zero”).
   - Se for um print de conversa, comece informando “print de conversa” e depois identifique cada falante seguido do que é dito (ex.: “Alice: ‘Oi, como vai?’; Bruno: ‘Tudo bem, e você?’”).
3. Não faça suposições além do que está visível.
4. Não mencione palavras como “imagem”, “foto” ou “arquivo”; inicie direto na descrição do conteúdo.

Exemplo:
- Você envia: [imagem de uma equação quadratic.png]
- A IA retorna: "x ao quadrado mais dez x mais vinte e quatro igual zero".

- Você envia: [print de chat.png]
- A IA retorna: "print de conversa. Usuário A: 'Pode enviar o relatório hoje?'; Usuário B: 'Sim, até as 18h.'".

Agora, aguarde a imagem e gere apenas a descrição conforme acima.
PROMPT;

        // Processa cada arquivo
        foreach ($filenames as $filename) {
            mtrace("[local_imagedesc] ----- Processando imagem '$filename' -----");

            // 1) Checa alt existente e pula antes de chamar API
            $patternAlt = '/<img[^>]+src="@@PLUGINFILE@@\/' . preg_quote($filename, '/') . '"[^>]*alt="([^"]+)"/i';
            if (preg_match($patternAlt, $html, $am)) {
                $existing = trim($am[1]);
                if (strlen($existing) > 5 && strpos($existing, ' ') !== false) {
                    mtrace("[local_imagedesc] Pulando API para '$filename' pois alt válido=\"$existing\".");
                    continue;
                }
            }

            // 2) Localiza registro na tabela files
            $file = $DB->get_record_sql(
                "SELECT id, contextid, component, filearea, itemid
                   FROM {files}
                  WHERE component='mod_forum'
                    AND filearea='post'
                    AND itemid=:pid
                    AND filename=:fname
                  ORDER BY timemodified DESC
                  LIMIT 1",
                ['pid' => $postid, 'fname' => $filename]
            );
            if (!$file) {
                mtrace("[local_imagedesc] Arquivo '$filename' não encontrado no BD. Pulando.");
                continue;
            }

            // 3) Caminho físico da imagem
            $hash = $DB->get_field('files', 'contenthash', ['id' => $file->id]);
            $path = $CFG->dataroot . "/filedir/" . substr($hash, 0, 2) . "/" . substr($hash, 2, 2) . "/" . $hash;
            if (!file_exists($path)) {
                mtrace("[local_imagedesc] Caminho físico não existe: $path. Pulando.");
                continue;
            }

            // 4) Chamada API Gemini via cURL
            try {
                mtrace("[local_imagedesc] Chamando Gemini API para '$filename'...");
                $img64    = base64_encode(file_get_contents($path));
                $mimetype = mime_content_type($path) ?: 'image/png';
                $apiKey   = get_config('local_imagedesc', 'apikey');
                $url      = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

                $payload = [
                    'contents' => [
                        [
                            'parts' => [
                                ['inline_data' => ['mime_type' => $mimetype, 'data' => $img64]],
                                ['text'        => $prompt]
                            ]
                        ]
                    ]
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

                $resp = curl_exec($ch);
                if ($resp === false) {
                    throw new \Exception('cURL error: ' . curl_error($ch));
                }
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                mtrace("[local_imagedesc] HTTP status code: $code");
                mtrace("[local_imagedesc] Raw API response: $resp");

                if ($code !== 200) {
                    throw new \Exception('API retornou HTTP ' . $code);
                }

                $data = json_decode($resp, true);
                $desc = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                mtrace("[local_imagedesc] Descrição gerada: '$desc'");

            } catch (\Throwable $e) {
                mtrace("[local_imagedesc] ERRO na API Gemini: " . $e->getMessage());
                continue;
            }

            // 5) Substitui alt
            $html = preg_replace_callback(
                '/(<img[^>]+src="@@PLUGINFILE@@\/' . preg_quote($filename, '/') . '"[^>]*>)/i',
                function ($m) use ($desc) {
                    $tag = $m[1];
                    if (strpos($tag, 'alt="') !== false) {
                        return preg_replace(
                            '/(alt=")[^"]*(")/i',
                            '$1' . htmlspecialchars($desc) . '$2',
                            $tag
                        );
                    }
                    return preg_replace(
                        '/(<img)([^>]*>)/i',
                        '$1 alt="' . htmlspecialchars($desc) . '"$2',
                        $tag
                    );
                },
                $html
            );
            mtrace("[local_imagedesc] ALT atualizado para '$filename'.");
        }

        // 6) Grava o HTML atualizado
        $post->message = $html;
        $DB->update_record('forum_posts', $post);
        mtrace("[local_imagedesc] Post ID $postid atualizado com novos alts.");
    }
}
