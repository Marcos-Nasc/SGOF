<?php
// /app/Config/CobliConfig.php
require_once 'database.php';

class CobliConfig {
    
    // Método para resgatar a chave de forma segura
    public static function getApiKey() {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->query("SELECT api_key, status FROM integracoes_config WHERE nome_servico = 'Cobli' LIMIT 1");
        $config = $stmt->fetch();
        
        // Trava de segurança: impede requisições se a integração estiver desligada
        if (!$config || $config['status'] === 'Inativo' || empty($config['api_key'])) {
            throw new Exception("A integração com a Cobli está inativa ou a Chave de API não foi configurada.");
        }
        
        return $config['api_key'];
    }

    // Método para atualizar a data da última sincronização
    public static function registrarSincronizacao() {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("UPDATE integracoes_config SET ultima_sincronizacao = NOW() WHERE nome_servico = 'Cobli'");
        $stmt->execute();
    }
}
?>