<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/descricao_imagens/classes/ia_processor.php');

use block_descricao_imagens\ia_processor;

$upload_dir = __DIR__ . "/img_uploads/";  // Diretório onde a imagem será salva
$responses_dir = __DIR__ . "/respostas/"; // Diretório onde o JSON será salvo

// Criar diretórios caso não existam
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
if (!is_dir($responses_dir)) {
    mkdir($responses_dir, 0777, true);
}

// Verifica se a imagem foi enviada corretamente
if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    die("Erro: Nenhuma imagem foi enviada ou ocorreu um erro no upload.");
}

// Salva a imagem no diretório
$imagem_nome = basename($_FILES['imagem']['name']);
$caminho_imagem = $upload_dir . $imagem_nome;
move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_imagem);

// Debug: Exibir caminho da imagem salva
echo "<p>Imagem salva em: $caminho_imagem</p>";

// Chama a função de processamento da IA
$descricao = ia_processor::processar_imagem($caminho_imagem);

// Caminho onde o JSON será armazenado dentro de "respostas/"
$json_path = $responses_dir . pathinfo($imagem_nome, PATHINFO_FILENAME) . ".json";

// Salvar o resultado no JSON
file_put_contents($json_path, json_encode(["descricao" => $descricao], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// Debug: Mostrar onde o JSON foi salvo
echo "<p>Arquivo salvo em: $json_path</p>";

// Exibir a resposta gerada para o usuário
echo "<h3>Descrição gerada:</h3>";
if (!empty($descricao)) {
    echo "<p>" . htmlspecialchars($descricao) . "</p>";
} else {
    echo "<p>Não foi possível gerar uma descrição.</p>";
}
