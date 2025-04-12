<?php
namespace local_imagedesc;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

class observers {

    public static function forum_post_created($event) {
        error_log("[local_imagedesc] Post criado (ID: {$event->objectid}). Nenhuma acao necessaria.");
    }

    public static function forum_post_updated($event) {
        error_log("[local_imagedesc] Evento de atualizacao de post detectado (ID: {$event->objectid}). Processando...");
        self::update_post_description($event);
    }

    public static function update_post_description($event) {
        global $DB, $CFG;

        $postid = $event->objectid;
        $post = $DB->get_record('forum_posts', ['id' => $postid], '*', MUST_EXIST);

        if (empty($post->message)) {
            error_log("[local_imagedesc] Post ID $postid sem mensagem. Nada a alterar.");
            return;
        }

        error_log("[local_imagedesc] Processando conteudo do post ID $postid...");

        $imagePath = urldecode(self::extract_image_path($post->message));
        if (!$imagePath) {
            error_log("[local_imagedesc] Nenhuma imagem encontrada no post ID $postid.");
            return;
        }

        error_log("[local_imagedesc] Imagem encontrada no post ID $postid: $imagePath");

        $realImagePath = self::resolve_moodle_image_path($postid, $imagePath);
        if (!$realImagePath) {
            error_log("[local_imagedesc] ERRO: Arquivo de imagem nao encontrado.");
            return;
        }

        error_log("[local_imagedesc] Caminho real do arquivo: $realImagePath");

        $description = self::fetch_image_description($realImagePath);

        if (!$description) {
            $description = "Descricao indisponivel";
        }

        error_log("[local_imagedesc] Descricao retornada pela API: $description");

        $newhtml = self::update_image_alt_text($post->message, $description);
        if ($newhtml !== $post->message) {
            $post->message = $newhtml;
            $DB->update_record('forum_posts', $post);
            error_log("[local_imagedesc] Descricao alterada no post ID $postid.");
        } else {
            error_log("[local_imagedesc] Nenhuma alteracao necessaria no post ID $postid.");
        }
    }

    private static function extract_image_path($html) {
        if (preg_match('/<img.*?src="@@PLUGINFILE@@\/(.*?)"/i', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function resolve_moodle_image_path($postid, $filename) {
        global $DB, $CFG;

        $sql = "SELECT contenthash FROM {files}
                WHERE component = 'mod_forum'
                AND filearea = 'post'
                AND itemid = :postid
                AND filename = :filename
                ORDER BY timemodified DESC LIMIT 1";

        $file = $DB->get_record_sql($sql, ['postid' => $postid, 'filename' => $filename]);

        if (!$file) {
            return null;
        }

        $hash = $file->contenthash;
        $filedir = $CFG->dataroot . "/filedir/" . substr($hash, 0, 2) . "/" . substr($hash, 2, 2) . "/" . $hash;

        return file_exists($filedir) ? $filedir : null;
    }

    private static function update_image_alt_text($html, $description) {
        error_log("[local_imagedesc] Atualizando ALT da imagem...");

        if (preg_match('/<img\s+[^>]*alt="[^"]*"/i', $html)) {
            $html = preg_replace('/(<img\s+[^>]*alt=")[^"]*(")/i', '$1' . htmlspecialchars($description) . '$2', $html);
        } else {
            $html = preg_replace('/(<img\s+[^>]*)(>)/i', '$1 alt="' . htmlspecialchars($description) . '"$2', $html);
        }

        return $html;
    }

    private static function fetch_image_description($imagePath) {
        $apiUrl = "http://localhost:8000/describe-image/";
        error_log("[local_imagedesc] Enviando imagem para API intermediaria: $imagePath");

        if (!file_exists($imagePath)) {
            error_log("[local_imagedesc] ERRO: Arquivo de imagem nao encontrado: $imagePath");
            return "Descricao indisponivel";
        }

        $tempImagePath = $imagePath . ".png";
        copy($imagePath, $tempImagePath);

        $postFields = [
            'image' => new \CURLFile($tempImagePath, "image/png", basename($tempImagePath))
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        unlink($tempImagePath);

        if ($httpCode !== 200) {
            error_log("[local_imagedesc] Erro na requisicao a API intermediaria. Codigo HTTP: {$httpCode}");
            return "Descricao indisponivel";
        }

        $responseData = json_decode($response, true);
        return $responseData['description'] ?? "Descricao indisponivel";
    }
}
