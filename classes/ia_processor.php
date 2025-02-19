<?php
namespace block_descricao_imagens;

class ia_processor {
    public static function processar_imagem($caminho_imagem) {
        global $CFG;

        $python_script = $CFG->dirroot . "/blocks/descricao_imagens/chamar_api.py"; // Caminho correto do script Python
        $image_path = realpath($caminho_imagem); // Obtém o caminho absoluto da imagem

        if (!file_exists($image_path)) {
            return "Erro: O arquivo de imagem não foi encontrado.";
        }

        if (!file_exists($python_script)) {
            return "Erro: O script Python não foi encontrado em: $python_script";
        }

        // Comando para forçar UTF-8 no terminal e executar o script Python
        $command = "chcp 65001 | python " . escapeshellarg($python_script) . " " . escapeshellarg($image_path) . " 2>&1";

        // Executa o comando e captura a saída
        $output = shell_exec($command);

        // Depuração: Mostrar saída completa
        if (!$output) {
            return "Erro ao executar o script Python.";
        }

        return trim($output); // Retorna a resposta gerada pelo script Python
    }
}
