<?php
ob_start();
session_start();
require_once '../../config/database.php';
require_once '../Services/CobliService.php';

// Limpa qualquer buffer e descarta qualquer texto gerado antes
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

$json_input = file_get_contents('php://input');
$dados = json_decode($json_input, true);

if (!$dados) {
    echo json_encode(['status' => 'error', 'message' => 'JSON inválido enviado.']);
    exit;
}

$acao = $dados['acao'] ?? '';
$db = new Database();
$conn = $db->getConnection();

if ($acao === 'salvar') {
    $apiKey = $dados['api_key'] ?? '';
    $stmt = $conn->prepare("UPDATE integracoes_config SET api_key = ?, status = 'Ativo' WHERE nome_servico = 'Cobli'");
    if ($stmt->execute([$apiKey])) {
        echo json_encode(['status' => 'success', 'message' => 'Configuração salva com sucesso!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar no banco.']);
    }
    exit;
}

if ($acao === 'testar') {
    try {
        $cobli = new CobliService();
        $sucesso = $cobli->testarConexao();
        
        if ($sucesso) {
            echo json_encode(['status' => 'success', 'message' => 'Sucesso! Comunicação estabelecida.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Falha na conexão. Chave inválida ou serviço indisponível.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Ação não reconhecida.']);