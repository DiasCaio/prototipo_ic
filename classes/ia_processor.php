<?php
namespace block_descricao_imagens;

class ia_processor {
    public static function processar_imagem($caminho_imagem) {
        global $CFG;

        $log_file = $CFG->dirroot . "/blocks/descricao_imagens/debug_log.txt";
        $responses_dir = realpath($CFG->dirroot . "/blocks/descricao_imagens/respostas/");
        $image_path = realpath($caminho_imagem);

        if (!file_exists($image_path)) {
            file_put_contents($log_file, "Erro: Arquivo de imagem não encontrado: $image_path\n", FILE_APPEND);
            return "Erro: O arquivo de imagem não foi encontrado.";
        }

        if (!is_dir($responses_dir)) {
            mkdir($responses_dir, 0777, true);
        }

        $config = get_config('block_descricao_imagens');
        $api_url = trim($config->api_url ?? '');
        $api_key = trim($config->api_key ?? '');
        $url = $api_url;
        if ($api_key) {
            $delimiter = strpos($api_url, '?') === false ? '?' : '&';
            $url .= $delimiter . 'key=' . $api_key;
        }

        $mime = mime_content_type($image_path);
        $image_data = base64_encode(file_get_contents($image_path));

        $payload = json_encode([
            'contents' => [[
                'parts' => [
                    ['text' => 'Descreva a imagem de forma breve e em português.'],
                    ['inline_data' => ['mime_type' => $mime, 'data' => $image_data]]
                ]
            ]]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            file_put_contents($log_file, "Erro cURL: $error\n", FILE_APPEND);
            return 'Erro ao conectar com a API.';
        }

        if ($httpcode !== 200) {
            file_put_contents($log_file, "Resposta HTTP $httpcode: $response\n", FILE_APPEND);
            return 'Erro na resposta da API.';
        }

        $data = json_decode($response, true);
        $descricao = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (!$descricao) {
            file_put_contents($log_file, "Resposta inesperada: $response\n", FILE_APPEND);
            return 'Erro ao interpretar a resposta da IA.';
        }

        $json_path = $responses_dir . DIRECTORY_SEPARATOR . basename($image_path) . '.json';
        file_put_contents($json_path, json_encode(['descricao' => $descricao], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $json_path;
    }
}
