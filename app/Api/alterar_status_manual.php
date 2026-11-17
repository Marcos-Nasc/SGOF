<?php
// /app/Api/alterar_status_manual.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');
$dados = json_decode(file_get_contents('php://input'), true);

if (!isset($dados['id_atendimento']) || !isset($dados['nova_fase'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
    exit;
}

$id_atendimento = $dados['id_atendimento'];
$nova_fase = $dados['nova_fase'];
$id_usuario = $_SESSION['id_usuario'] ?? 1; // Fallback para testes

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // 1. Consulta qual era a fase anterior para registrar no histórico
    $stmtGet = $conn->prepare("SELECT fase_atual FROM atendimentos WHERE id_atendimento = :id");
    $stmtGet->execute(['id' => $id_atendimento]);
    $fase_antiga = $stmtGet->fetchColumn();

    if ($fase_antiga === $nova_fase) {
        throw new Exception("O atendimento já está nesta fase.");
    }

    // 2. Atualiza a fase no banco de dados
    $stmtUpd = $conn->prepare("UPDATE atendimentos SET fase_atual = :fase WHERE id_atendimento = :id");
    $stmtUpd->execute(['fase' => $nova_fase, 'id' => $id_atendimento]);

    // 3. Registra a ação na Timeline com a flag de que foi manual
    $acao = "Status Alterado Manualmente";
    $descricao = "Operação movida de '" . mb_strtoupper($fase_antiga) . "' para '" . mb_strtoupper($nova_fase) . "'";
    
    $stmtTime = $conn->prepare("INSERT INTO timeline_historico (id_atendimento, id_usuario, acao_executada, descricao) VALUES (:id, :user, :acao, :desc)");
    $stmtTime->execute([
        'id' => $id_atendimento,
        'user' => $id_usuario,
        'acao' => $acao,
        'desc' => $descricao
    ]);

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Fase atualizada com sucesso.']);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>