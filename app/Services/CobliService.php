<?php
// /app/Services/CobliService.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/sgof_logistica/config/CobliConfig.php';

class CobliService {
    
    private $baseUrl = 'https://api.cobli.co/public/v1'; 

    private function request($endpoint, $method = 'GET', $data = null) {
        $apiKey = CobliConfig::getApiKey();
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        $headers = [
            'Cobli-Api-Key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); 

        if ($data && $method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new Exception("Falha de comunicação com a Cobli: " . $error);
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $msgErro = $decodedResponse['message'] ?? 'Erro desconhecido na API externa.';
            throw new Exception("Erro Cobli ({$httpCode}): {$msgErro}");
        }

        return $decodedResponse;
    }

    public function testarConexao() {
        try {
            $this->request('/vehicles?limit=1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getVeiculosCobli() {
        return $this->request('/vehicles?limit=2000&page=1');
    }

    public function getMotoristasCobli() {
        return $this->request('/drivers?limit=2000&page=1');
    }

    public function getTelemetria($deviceId) {
        return $this->request("/devices/{$deviceId}/telemetry");
    }

    // === FUNÇÃO DE ALTA PERFORMANCE (RAIO-X ATIVADO) ===
    public function getDadosEmMassa($deviceIds) {
        $apiKey = CobliConfig::getApiKey();
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $resultados = [];

        $headers = [
            'Cobli-Api-Key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        foreach ($deviceIds as $id) {
            $chTel = curl_init();
            curl_setopt($chTel, CURLOPT_URL, "https://api.cobli.co/public/v1/devices/{$id}/telemetry");
            curl_setopt($chTel, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chTel, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($chTel, CURLOPT_TIMEOUT, 5); 
            curl_multi_add_handle($multiHandle, $chTel);
            $curlHandles['telemetria_' . $id] = $chTel;
        }

        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        foreach ($curlHandles as $key => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);

            list($tipo, $id) = explode('_', $key);
            if (!isset($resultados[$id])) { $resultados[$id] = []; }

            if ($httpCode >= 200 && $httpCode < 300) {
                $resultados[$id][$tipo] = json_decode($response, true);
            } else {
                $resultados[$id][$tipo] = ['ERRO_HTTP' => $httpCode, 'RESPOSTA' => $response];
            }
        }
        curl_multi_close($multiHandle);
        return $resultados;
    }
}
?>