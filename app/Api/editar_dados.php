<?php
// /app/Api/editar_dados.php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id_atendimento'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID de atendimento não fornecido.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $id_atendimento = $data['id_atendimento'];
    $lab = $data['laboratorio_destino'] ?? null;
    $capela = $data['capela_reservada'] ?? null;
    $horario = !empty($data['horario_velorio']) ? $data['horario_velorio'] : null;
    
    $nome_motorista = $data['nome_motorista'] ?? null;
    $floricultura = $data['nome_floricultura'] ?? null;
    $cerimonialista = $data['nome_cerimonialista'] ?? null;
    $eta = $data['previsao_entrega'] ?? null;

    // 1. Tentar descobrir o ID do motorista baseado no nome que foi digitado
    $id_motorista = null;
    if ($nome_motorista) {
        $stmtMot = $conn->prepare("SELECT id_usuario FROM usuarios WHERE nome LIKE ? AND perfil = 'Motorista' LIMIT 1");
        // O LIKE com '%' permite que a gente ache digitando apenas o primeiro nome
        $stmtMot->execute(['%' . trim($nome_motorista) . '%']);
        $mot = $stmtMot->fetch();
        if ($mot) {
            $id_motorista = $mot['id_usuario'];
        }
    }

    // 2. Verifica se a operação já existe
    $stmtCheck = $conn->prepare("SELECT id_operacao FROM operacoes_logistica WHERE id_atendimento = ?");
    $stmtCheck->execute([$id_atendimento]);
    $op = $stmtCheck->fetch();

    if ($op) {
        // Atualiza a existente
        $sql = "UPDATE operacoes_logistica 
                SET laboratorio_destino = ?, capela_reservada = ?, horario_velorio = ?, 
                    id_motorista = ?, nome_floricultura = ?, nome_cerimonialista = ?, previsao_entrega = ?
                WHERE id_atendimento = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lab, $capela, $horario, $id_motorista, $floricultura, $cerimonialista, $eta, $id_atendimento]);
    } else {
        // Cria uma nova ficha de operação se não existir
        $sql = "INSERT INTO operacoes_logistica 
                (id_atendimento, laboratorio_destino, capela_reservada, horario_velorio, id_motorista, nome_floricultura, nome_cerimonialista, previsao_entrega) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_atendimento, $lab, $capela, $horario, $id_motorista, $floricultura, $cerimonialista, $eta]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Dados operacionais salvos!']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>