<?php
// /app/Api/sincronizar_motoristas.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/CobliService.php';
require_once __DIR__ . '/../../config/CobliConfig.php';

header('Content-Type: application/json');

try {
    $cobli = new CobliService();
    $resposta_api = $cobli->getMotoristasCobli(); 
    
    // 1. A lista vem dentro da chave 'data'
    $motoristas_cobli = $resposta_api['data'] ?? [];

    if (empty($motoristas_cobli)) {
        throw new Exception("Nenhum motorista retornado pela Cobli.");
    }

    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    $stmtCheck = $conn->prepare("SELECT id_usuario FROM usuarios WHERE cobli_driver_id = ?");
    $stmtUpdate = $conn->prepare("UPDATE usuarios SET nome = ? WHERE id_usuario = ?");
    
    $senhaPadrao = password_hash('123456', PASSWORD_DEFAULT);
    
    // Insere gerando um e-mail corporativo interno caso a Cobli não envie o e-mail real
    $stmtInsert = $conn->prepare("
        INSERT INTO usuarios (nome, email, senha_hash, perfil, cobli_driver_id) 
        VALUES (?, ?, ?, 'Motorista', ?)
    ");

    $countNovos = 0;
    $countAtualizados = 0;

    foreach ($motoristas_cobli as $m) {
        // Trata os campos flexíveis (name ou nome)
        $nome = $m['name'] ?? $m['nome'] ?? 'Motorista sem nome';
        $cobli_driver_id = $m['id'] ?? '';
        $cpf = $m['cpf'] ?? '';

        if (empty($cobli_driver_id)) {
            continue; 
        }

        // Se o motorista não tiver e-mail na API, criamos um e-mail interno único usando o CPF ou o ID
        $email = $m['email'] ?? '';
        if (empty($email)) {
            $identificador = !empty($cpf) ? preg_replace('/[^0-9]/', '', $cpf) : substr($cobli_driver_id, 0, 8);
            $email = "motorista_{$identificador}@sgof.local";
        }

        // Verifica se o motorista já está cadastrado pelo ID da Cobli
        $stmtCheck->execute([$cobli_driver_id]);
        $usuario_existente = $stmtCheck->fetch();

        if ($usuario_existente) {
            // Atualiza o nome caso tenha mudado
            $stmtUpdate->execute([$nome, $usuario_existente['id_usuario']]);
            $countAtualizados++;
        } else {
            // Insere o novo motorista
            try {
                $stmtInsert->execute([$nome, $email, $senhaPadrao, $cobli_driver_id]);
                $countNovos++;
            } catch (PDOException $ex) {
                // Se der duplicidade de e-mail por segurança, criamos um com sufixo randômico
                if ($ex->getCode() == 23000) {
                    $emailAlternativo = "motorista_" . uniqid() . "@sgof.local";
                    $stmtInsert->execute([$nome, $emailAlternativo, $senhaPadrao, $cobli_driver_id]);
                    $countNovos++;
                } else {
                    throw $ex;
                }
            }
        }
    }

    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => "Sincronização de motoristas concluída! $countNovos novos cadastrados e $countAtualizados atualizados."
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>