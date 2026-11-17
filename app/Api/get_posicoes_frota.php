<?php
// /app/Api/get_posicoes_frota.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/CobliService.php';
require_once __DIR__ . '/../../config/CobliConfig.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

try {
    $db = new Database();
    $conn = $db->getConnection();
    $cobli = new CobliService();
    
    // 1. Busca os veículos cadastrados
    $stmt = $conn->query("
        SELECT 
            id_veiculo,
            placa,
            modelo,
            cobli_device_id,
            cobli_vehicle_id
        FROM veiculos 
        WHERE cobli_device_id IS NOT NULL AND cobli_device_id != ''
    ");
    $veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Busca todos os motoristas sincronizados para fazermos o "de-para" na memória com rapidez
    $stmtMot = $conn->query("SELECT id_usuario, nome, cobli_driver_id FROM usuarios WHERE cobli_driver_id IS NOT NULL");
    $motoristasMap = [];
    while ($m = $stmtMot->fetch(PDO::FETCH_ASSOC)) {
        $motoristasMap[$m['cobli_driver_id']] = $m['nome'];
    }
    
    $stmtUpsert = $conn->prepare("
        INSERT INTO cobli_veiculos_posicoes (id_veiculo, latitude, longitude, velocidade, ignicao, data_hora_atualizacao)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        latitude = VALUES(latitude), longitude = VALUES(longitude), velocidade = VALUES(velocidade), ignicao = VALUES(ignicao), data_hora_atualizacao = NOW()
    ");
    
    $deviceIds = array_column($veiculos, 'cobli_device_id');
    $dadosAPI = !empty($deviceIds) ? $cobli->getDadosEmMassa($deviceIds) : [];
    
    // Também podemos buscar os detalhes gerais dos veículos para ver o 'last_driver' direto da API se necessário
    $veiculosCobliGeral = [];
    try {
        $respVeiculos = $cobli->getVeiculosCobli();
        $listaV = $respVeiculos['data'] ?? [];
        foreach($listaV as $item) {
            if(isset($item['id'])) {
                $veiculosCobliGeral[$item['id']] = $item;
            }
        }
    } catch(Exception $ex) {}

    $dados_formatados = [];
    
    foreach ($veiculos as $v) {
        $deviceId = $v['cobli_device_id'];
        $cobliVehicleId = $v['cobli_vehicle_id'];
        $res = $dadosAPI[$deviceId] ?? null;
        
        if (!$res || empty($res['telemetria']) || isset($res['telemetria']['ERRO_HTTP'])) {
            continue;
        }

        $tel = $res['telemetria']['data'] ?? $res['telemetria'];
        $lat = $tel['latitude'] ?? null;
        $lng = $tel['longitude'] ?? null;
        $vel = (float) ($tel['speed'] ?? 0);
        
        $ignicao_raw = $tel['ignition_on'] ?? false;
        $ign = filter_var($ignicao_raw, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        // Descobre o ID do motorista atual (pode vir na telemetria ou no objeto geral do veículo)
        $cobliDriverId = $tel['last_driver'] ?? $tel['driver_id'] ?? null;
        if (!$cobliDriverId && isset($veiculosCobliGeral[$cobliVehicleId])) {
            $cobliDriverId = $veiculosCobliGeral[$cobliVehicleId]['last_driver'] ?? null;
        }

        // Traduz o ID da Cobli para o Nome do Motorista salvo na nossa tabela 'usuarios'
        $nomeMotorista = 'Não alocado';
        if ($cobliDriverId && isset($motoristasMap[$cobliDriverId])) {
            $nomeMotorista = $motoristasMap[$cobliDriverId];
        }

        if ($lat !== null && $lng !== null) {
            $stmtUpsert->execute([$v['id_veiculo'], $lat, $lng, $vel, $ign]);
            
            $dados_formatados[] = [
                'id_veiculo' => $v['id_veiculo'],
                'placa' => $v['placa'],
                'modelo' => $v['modelo'],
                'latitude' => $lat,
                'longitude' => $lng,
                'velocidade' => round($vel),
                'ignicao' => $ign,
                'previsao_chegada' => 'Aguardando OS',
                'nome_motorista' => $nomeMotorista, // Nome real do motorista injetado aqui!
                'id_atendimento' => null
            ];
        }
    }

    echo json_encode(['status' => 'success', 'dados' => $dados_formatados]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>