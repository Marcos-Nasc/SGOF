<?php
// /app/Api/Webhooks/cobli_receiver.php

// 1. Webhooks precisam responder rápido. Retornamos HTTP 200 imediatamente.
http_response_code(200);

require_once '../../../config/database.php';

// 2. Captura o "pacote" JSON que a Cobli enviou
$payload = file_get_contents('php://input');
$dados = json_decode($payload, true);

// Se não houver dados válidos, encerramos a execução silenciosamente
if (!$dados) {
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // A documentação de Webhooks de telemetria geralmente envia o ID do veículo, coordenadas, ignição e velocidade
    // Vamos mapear os dados (adaptável conforme a versão exata do payload da Cobli)
    $cobli_vehicle_id = $dados['vehicleId'] ?? $dados['vehicle_id'] ?? null;
    $lat = $dados['latitude'] ?? null;
    $lng = $dados['longitude'] ?? null;
    $velocidade = $dados['speed'] ?? 0;
    $ignicao = isset($dados['ignition']) ? (int)$dados['ignition'] : 0; // 1 = Ligado, 0 = Desligado

    // Se faltar o ID do carro ou a coordenada, não temos o que salvar
    if (!$cobli_vehicle_id || $lat === null || $lng === null) {
        exit;
    }

    // 3. Descobre qual é o ID interno do veículo na nossa plataforma
    $stmtVeiculo = $conn->prepare("SELECT id_veiculo FROM veiculos WHERE cobli_vehicle_id = ? LIMIT 1");
    $stmtVeiculo->execute([$cobli_vehicle_id]);
    $veiculo = $stmtVeiculo->fetch();

    if (!$veiculo) {
        // Se o carro não existe na nossa base, ignoramos o evento
        exit;
    }

    $id_veiculo_interno = $veiculo['id_veiculo'];

    // 4. Salva a localização no nosso Cache de Alta Performance (UPSERT)
    // O comando ON DUPLICATE KEY UPDATE garante que sempre teremos apenas UMA linha por carro, atualizada com a última posição.
    $sql = "INSERT INTO cobli_veiculos_posicoes (id_veiculo, latitude, longitude, velocidade, ignicao, data_hora_atualizacao) 
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            velocidade = VALUES(velocidade),
            ignicao = VALUES(ignicao),
            data_hora_atualizacao = NOW()";

    $stmtCache = $conn->prepare($sql);
    $stmtCache->execute([
        $id_veiculo_interno,
        $lat,
        $lng,
        $velocidade,
        $ignicao
    ]);

    // Opcional: Aqui você também poderia verificar se o carro entrou em alguma "Geofence" (Cemitério, Hospital)
    // e disparar a atualização automática da Timeline da Logística se quisesse automatizar ainda mais.

} catch (Exception $e) {
    // Para endpoints de Webhook, o ideal é registrar erros em um arquivo de log local para auditoria
    error_log("Erro no Webhook Cobli: " . $e->getMessage());
}
?>