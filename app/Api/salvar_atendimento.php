<?php
// /app/Api/salvar_atendimento.php
session_start();
require_once '../../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->getConnection();
    
    $id_usuario_criador = $_SESSION['id_usuario'] ?? 1; 

    try {
        $conn->beginTransaction();

        // 1. Inserir Falecido
        $stmtFalecido = $conn->prepare("INSERT INTO falecidos (nome, cpf, local_obito) VALUES (?, ?, ?)");
        $stmtFalecido->execute([$_POST['falecido_nome'], $_POST['falecido_cpf'], $_POST['local_obito']]);
        $id_falecido = $conn->lastInsertId();

        // 2. Inserir Contratante
        $stmtContratante = $conn->prepare("INSERT INTO contratantes (nome, contato, grau_parentesco) VALUES (?, ?, ?)");
        $stmtContratante->execute([$_POST['contratante_nome'], $_POST['contratante_contato'], $_POST['grau_parentesco']]);
        $id_contratante = $conn->lastInsertId();

        // 3. Criar o Atendimento (Card Central)
        $stmtAtendimento = $conn->prepare("INSERT INTO atendimentos (id_falecido, id_contratante, categoria_servico, tipo_funeral, fase_atual) VALUES (?, ?, ?, ?, 'Conferencia')");
        $stmtAtendimento->execute([$id_falecido, $id_contratante, $_POST['categoria_servico'], $_POST['tipo_funeral']]);
        $id_atendimento = $conn->lastInsertId();

        // 4. NOVO: Salvar as demandas terceirizadas na tabela de logística
        $stmtLogistica = $conn->prepare("INSERT INTO operacoes_logistica (id_atendimento, tipo_ornamentacao, coroa_flores, necessita_despachante) VALUES (?, ?, ?, ?)");
        $necessita_despachante = ($_POST['necessita_despachante'] === 'Sim') ? 1 : 0;
        $stmtLogistica->execute([$id_atendimento, $_POST['tipo_ornamentacao'], $_POST['coroa_flores'], $necessita_despachante]);

        // 5. Iniciar a Timeline
        $stmtTimeline = $conn->prepare("INSERT INTO timeline_historico (id_atendimento, id_usuario, acao_executada, descricao) VALUES (?, ?, ?, ?)");
        $descricao = 'Serviço de ' . $_POST['tipo_funeral'] . ' (' . $_POST['categoria_servico'] . ') registrado no sistema.';
        $stmtTimeline->execute([$id_atendimento, $id_usuario_criador, 'Atendimento criado', $descricao]);

        $conn->commit();

        header("Location: ../../views/central_logistica.php?status=sucesso");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        die("Erro ao salvar o atendimento: " . $e->getMessage());
    }
}
?>