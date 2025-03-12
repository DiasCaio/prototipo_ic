<?php
namespace local_imagedesc;

defined('MOODLE_INTERNAL') || die();

class observers {

    /**
     * Callback para evento de CRIAÇÃO de post no fórum.
     *
     * @param \mod_forum\event\post_created $event
     */
    public static function forum_post_created($event) {
        sleep(2); // Aguarda 2 segundos para garantir que o post foi salvo no banco
        self::update_post_description($event);
    }

    /**
     * Callback para evento de ATUALIZAÇÃO de post no fórum.
     *
     * @param \mod_forum\event\post_updated $event
     */
    public static function forum_post_updated($event) {
        self::update_post_description($event);
    }


    public static function file_uploaded($event) {
        global $DB;
    
        // Captura informações do arquivo enviado
        $fileinfo = $DB->get_record('files', ['id' => $event->objectid]);
    
        if (!$fileinfo) {
            error_log("[local_imagedesc] Arquivo não encontrado para o evento file_uploaded.");
            return;
        }
    
        // Verifica se é uma imagem
        $allowedtypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileinfo->mimetype, $allowedtypes)) {
            error_log("[local_imagedesc] O arquivo {$fileinfo->filename} não é uma imagem.");
            return;
        }
    
        // Adiciona um log para depuração
        error_log("[local_imagedesc] Upload detectado: {$fileinfo->filename}.");
    
        // Agora, tentamos modificar a descrição diretamente na imagem
        $fileinfo->author = "Imagem enviada por [Nome do Usuário] em " . date('d/m/Y H:i');
        $DB->update_record('files', $fileinfo);
    
        error_log("[local_imagedesc] Descrição alterada para {$fileinfo->filename}.");
    }
    

    /**
     * Função central para modificar a descrição da imagem no post.
     *
     * @param \mod_forum\event\post_created|\mod_forum\event\post_updated $event
     */
    private static function update_post_description($event) {
        global $DB;

        $postid = $event->objectid;
        $post = $DB->get_record('forum_posts', ['id' => $postid], '*', MUST_EXIST);

        if (empty($post->message)) {
            error_log("[local_imagedesc] Post $postid sem mensagem. Nada a alterar.");
            return;
        }

        $originalhtml = $post->message;
        $newhtml = self::process_images_in_html($originalhtml);

        if ($newhtml !== $originalhtml) {
            $post->message = $newhtml;
            $DB->update_record('forum_posts', $post);
            error_log("[local_imagedesc] Descrição alterada no postid $postid.");
        } else {
            error_log("[local_imagedesc] Nenhuma alteração necessária no postid $postid.");
        }
    }

    /**
     * Processa o HTML para modificar/inserir 'alt' nas imagens.
     *
     * @param string $html Conteúdo original.
     * @return string HTML modificado.
     */
    private static function process_images_in_html($html) {
        $pattern = '/<img\s+([^>]*?)>/i';
    
        return preg_replace_callback($pattern, function ($matches) {
            $imgtag = $matches[0];
    
            if (preg_match('/alt\s*=\s*"[^\"]*"/i', $imgtag)) {
                $imgtag = preg_replace(
                    '/alt\s*=\s*"[^"]*"/i',
                    'alt="Imagem enviada por [Nome do Usuário] em ' . date('d/m/Y H:i') . '"',
                    $imgtag
                );
            } else {
                $imgtag = rtrim($imgtag, '>') . ' alt="Imagem enviada por [Nome do Usuário] em ' . date('d/m/Y H:i') . '">';
            }
    
            return $imgtag;
        }, $html);
    }
    
}
