<?php
// /app/Api/acionar_etapa.php
session_start();
require_once '../../config/database.php';
require_once '../Controllers/LogisticaController.php'; // Supondo que a lógica do LogisticaCore foi unificada aqui

header('Content-Type: application/json');

// Recebe os dados em formato JSON enviados pelo Javascript
$dados = json_decode(file_get_contents('php://input'), true);

if (!isset($dados['id_atendimento']) || !isset($dados['acao'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
    exit;
}

$id_atendimento = $dados['id_atendimento'];
$acao = $dados['acao'];
$id_usuario = $_SESSION['id_usuario'] ?? 1; // Fallback para 1 (Logística) para testes

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->beginTransaction();

    // 1. Descobrir a próxima fase baseada na ação (Exemplo simplificado)
    $nova_fase = 'Logistica'; 
    if ($acao === 'Encaminhar ao Laboratório') $nova_fase = 'Laboratorio';
    if ($acao === 'Agendar Sepultamento') $nova_fase = 'Sepultamento';

    // 2. Atualiza o status do Card (Atendimento)
    $stmtUpdate = $conn->prepare("UPDATE atendimentos SET fase_atual = :fase WHERE id_atendimento = :id");
    $stmtUpdate->execute(['fase' => $nova_fase, 'id' => $id_atendimento]);

    // 3. Registra na Timeline de forma inalterável
    $stmtTimeline = $conn->prepare("INSERT INTO timeline_historico (id_atendimento, id_usuario, acao_executada) VALUES (:id_atendimento, :id_usuario, :acao)");
    $stmtTimeline->execute([
        'id_atendimento' => $id_atendimento,
        'id_usuario' => $id_usuario,
        'acao' => $acao
    ]);

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Etapa avançada com sucesso!', 'nova_fase' => $nova_fase]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Erro interno ao processar a ação.']);
}
?>