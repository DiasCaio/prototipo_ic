<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/descricao_imagens/classes/ia_processor.php');

use block_descricao_imagens\ia_processor;

header('Content-Type: application/json'); // Garante que sempre retornamos JSON

$upload_dir = __DIR__ . "/img_uploads/";
$responses_dir = __DIR__ . "/respostas/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
if (!is_dir($responses_dir)) {
    mkdir($responses_dir, 0777, true);
}

if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["erro" => "Erro: Nenhuma imagem foi enviada ou ocorreu um erro no upload."]);
    exit;
}

$imagem_nome = basename($_FILES['imagem']['name']);
$caminho_imagem = $upload_dir . $imagem_nome;
move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_imagem);

$json_path = ia_processor::processar_imagem($caminho_imagem);

if (stripos($json_path, "Erro") !== false) {
    echo json_encode(["erro" => $json_path]);
    exit;
}

if (!file_exists($json_path)) {
    echo json_encode(["erro" => "Erro ao gerar a descrição da imagem."]);
    exit;
}

$conteudo_json = file_get_contents($json_path);
$dados = json_decode($conteudo_json, true);

echo json_encode(["descricao" => $dados['descricao'] ?? "Descrição não encontrada."]);
