<?php
// /app/Api/calcular_eta_cobli.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/CobliService.php';
require_once __DIR__ . '/../../config/CobliConfig.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

$data = json_decode(file_get_contents('php://input'), true);
$nome_motorista = $data['motorista'] ?? '';
$destino = $data['destino'] ?? '';

if (empty($nome_motorista) || empty($destino)) {
    echo json_encode(['status' => 'error', 'message' => 'Motorista e Destino são obrigatórios.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // 1. Achar o ID da Cobli do motorista digitado
    $stmtMot = $conn->prepare("SELECT cobli_driver_id FROM usuarios WHERE nome LIKE ? AND cobli_driver_id IS NOT NULL LIMIT 1");
    $stmtMot->execute(['%' . trim($nome_motorista) . '%']);
    $motorista = $stmtMot->fetch();

    if (!$motorista) {
        throw new Exception("Motorista não encontrado ou não sincronizado com a Cobli.");
    }

    $cobli_driver_id = $motorista['cobli_driver_id'];

    // 2. Perguntar para a Cobli em qual veículo esse motorista está logado hoje
    $cobli = new CobliService();
    $veiculos_api = $cobli->getVeiculosCobli();
    $lista_veiculos = $veiculos_api['data'] ?? [];

    $device_id = null;
    foreach ($lista_veiculos as $v) {
        if (isset($v['last_driver']) && $v['last_driver'] == $cobli_driver_id) {
            $device_id = $v['device_id'] ?? null;
            break;
        }
    }

    if (!$device_id) {
        throw new Exception("Este motorista não está vinculado a nenhum veículo no momento (ignição desligada ou app deslogado).");
    }

    // 3. Chamar a rota especial de ETA da Cobli que você descobriu!
    // Enviamos o endereço via URL (?address=...)
    $urlETA = "https://api.cobli.co/herbie-1.1/dash/device/{$device_id}/eta?address=" . urlencode($destino);
    
    $apiKey = CobliConfig::getApiKey();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlETA);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Cobli-Api-Key: ' . $apiKey,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        $erroDecode = json_decode($response, true);
        $msgErro = $erroDecode['message'] ?? 'Erro desconhecido na rota de ETA';
        throw new Exception("A Cobli recusou o cálculo: " . $msgErro);
    }

    $resultado = json_decode($response, true);

    // 4. Formata a resposta de tempo de maneira amigável
    $textoEta = "Calculado com sucesso";
    if (isset($resultado['eta'])) {
        $dataEta = date('d/m/Y H:i', strtotime($resultado['eta']));
        $textoEta = "Chegada aprox. às " . $dataEta;
    } elseif (isset($resultado['duration_text'])) {
        $textoEta = "Tempo de viagem: " . $resultado['duration_text'];
    }

    echo json_encode(['status' => 'success', 'eta' => $textoEta]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>