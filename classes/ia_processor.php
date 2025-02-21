<?php
namespace block_descricao_imagens;

class ia_processor {
    public static function processar_imagem($caminho_imagem) {
        global $CFG;

        $log_file = $CFG->dirroot . "/blocks/descricao_imagens/debug_log.txt";
        $python_script = realpath($CFG->dirroot . "/blocks/descricao_imagens/chamar_api.py");
        $responses_dir = realpath($CFG->dirroot . "/blocks/descricao_imagens/respostas/");
        $image_path = realpath($caminho_imagem);

        if (!file_exists($image_path)) {
            file_put_contents($log_file, "Erro: Arquivo de imagem não encontrado: $image_path\n", FILE_APPEND);
            return "Erro: O arquivo de imagem não foi encontrado.";
        }

        if (!file_exists($python_script)) {
            file_put_contents($log_file, "Erro: Script Python não encontrado: $python_script\n", FILE_APPEND);
            return "Erro: O script Python não foi encontrado.";
        }

        if (!is_dir($responses_dir)) {
            mkdir($responses_dir, 0777, true);
        }

        // Definir o caminho correto do Python
        $python_path = "C:\\Python310\\python.exe";
        if (!file_exists($python_path)) {
            file_put_contents($log_file, "Erro: Python não encontrado: $python_path\n", FILE_APPEND);
            return "Erro: Python não encontrado.";
        }

        // Construção do comando
        $command = "\"$python_path\" \"$python_script\" \"$image_path\" 2>&1";
        file_put_contents($log_file, "Comando executado: $command\n", FILE_APPEND);

        // Executar script Python
        $output = shell_exec($command);
        file_put_contents($log_file, "Saída do comando: $output\n", FILE_APPEND);

        if (!$output || stripos($output, "ERRO:") !== false) {
            return "Erro ao executar o script Python: " . htmlspecialchars($output);
        }

        // Capturar a última linha da saída como o caminho do JSON
        $output_lines = explode("\n", trim($output));
        $json_path = trim(end($output_lines));

        if (!file_exists($json_path)) {
            file_put_contents($log_file, "Erro: JSON não encontrado: $json_path\n", FILE_APPEND);
            return "Erro ao gerar a descrição da imagem.";
        }

        // Mover JSON para pasta de respostas
        $novo_caminho_json = $responses_dir . DIRECTORY_SEPARATOR . basename($json_path);
        rename($json_path, $novo_caminho_json);
        file_put_contents($log_file, "JSON movido para: $novo_caminho_json\n", FILE_APPEND);

        return $novo_caminho_json;
    }
}