<?php
// /app/Api/registrar_checkin.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');
$dados = json_decode(file_get_contents('php://input'), true);

if (!isset($dados['id_atendimento'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID não informado.']);
    exit;
}

$id_atendimento = $dados['id_atendimento'];
$id_usuario = $_SESSION['id_usuario'] ?? 2; // ID do motorista logado
$nome_motorista = $_SESSION['nome_usuario'] ?? 'Motorista';

$db = new Database();
$conn = $db->getConnection();

try {
    // Insere o log na timeline da Logística
    $acao = "Check-in de Rota";
    $descricao = "O motorista " . $nome_motorista . " informou chegada ao local de destino.";

    $stmt = $conn->prepare("INSERT INTO timeline_historico (id_atendimento, id_usuario, acao_executada, descricao) VALUES (:id, :user, :acao, :desc)");
    $stmt->execute([
        'id' => $id_atendimento,
        'user' => $id_usuario,
        'acao' => $acao,
        'desc' => $descricao
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Check-in registrado.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao registrar check-in.']);
}
?>