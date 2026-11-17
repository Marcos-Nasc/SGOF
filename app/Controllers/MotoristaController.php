<?php
// /app/Controllers/MotoristaController.php
require_once '../config/database.php';

class MotoristaController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Busca apenas os serviços ativos onde este motorista foi escalado
    public function getMinhasCorridas($id_motorista) {
        $query = "SELECT 
                    a.id_atendimento, a.tipo_funeral, a.fase_atual,
                    f.nome AS nome_falecido, f.local_obito,
                    ol.laboratorio_destino, ol.capela_reservada, ol.horario_velorio
                  FROM atendimentos a
                  JOIN falecidos f ON a.id_falecido = f.id_falecido
                  JOIN operacoes_logistica ol ON a.id_atendimento = ol.id_atendimento
                  WHERE ol.id_motorista = :id_motorista 
                  AND a.status_geral != 'Concluido'
                  ORDER BY ol.horario_velorio ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_motorista' => $id_motorista]);
        return $stmt->fetchAll();
    }
}
?>