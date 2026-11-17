<?php
// /app/Api/sincronizar_frota.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/CobliService.php';
require_once __DIR__ . '/../../config/CobliConfig.php';

header('Content-Type: application/json');

try {
    $cobli = new CobliService();
    $resposta_api = $cobli->getVeiculosCobli(); 
    
    // 1. Extraímos da chave correta mostrada no print: 'data'
    $veiculos_cobli = $resposta_api['data'] ?? [];

    if (empty($veiculos_cobli)) {
        throw new Exception("Nenhum veículo encontrado ou formato inesperado.");
    }

    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    // 2. Preparei as queries já adicionando o "cobli_device_id"
    $stmtCheck = $conn->prepare("SELECT id_veiculo FROM veiculos WHERE placa = ?");
    $stmtUpdate = $conn->prepare("UPDATE veiculos SET cobli_vehicle_id = ?, cobli_device_id = ?, modelo = ? WHERE id_veiculo = ?");
    $stmtInsert = $conn->prepare("INSERT INTO veiculos (placa, modelo, cobli_vehicle_id, cobli_device_id) VALUES (?, ?, ?, ?)");

    $countNovos = 0;
    $countAtualizados = 0;

    foreach ($veiculos_cobli as $v) {
        // 3. Pegando os dados usando as chaves EXATAS do seu print
        $placa = $v['license_plate'] ?? '';
        $cobli_id = $v['id'] ?? '';
        $device_id = $v['device_id'] ?? null;
        
        // Juntamos a marca com o modelo para ficar melhor (Ex: "Fiat Strada Freedom Cs13")
        $marca = $v['brand'] ?? '';
        $modelo_puro = $v['model'] ?? '';
        $modelo_completo = trim($marca . ' ' . $modelo_puro);
        
        if (empty($modelo_completo)) {
            $modelo_completo = 'Não especificado';
        }

        // Pula se faltar o essencial
        if (empty($placa) || empty($cobli_id)) {
            continue; 
        }

        // Limpa formatação da placa
        $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa));

        $stmtCheck->execute([$placa]);
        $veiculo_existente = $stmtCheck->fetch();

        if ($veiculo_existente) {
            // Atualiza
            $stmtUpdate->execute([$cobli_id, $device_id, $modelo_completo, $veiculo_existente['id_veiculo']]);
            $countAtualizados++;
        } else {
            // Insere novo
            $stmtInsert->execute([$placa, $modelo_completo, $cobli_id, $device_id]);
            $countNovos++;
        }
    }

    CobliConfig::registrarSincronizacao();
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => "Sincronização concluída! $countNovos veículos novos cadastrados e $countAtualizados atualizados."
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>