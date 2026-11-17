<?php
// /app/Controllers/AtendimentoController.php
require_once '../config/database.php';

class AtendimentoController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Busca os dados completos de um atendimento específico
    public function getDetalhesAtendimento($id_atendimento) {
        $query = "SELECT 
                    a.*, 
                    f.nome AS nome_falecido, f.cpf AS cpf_falecido, f.local_obito,
                    c.nome AS nome_contratante, c.contato, c.grau_parentesco,
                    ol.laboratorio_destino, ol.capela_reservada, ol.horario_velorio,
                    u.nome AS nome_motorista
                  FROM atendimentos a
                  JOIN falecidos f ON a.id_falecido = f.id_falecido
                  JOIN contratantes c ON a.id_contratante = c.id_contratante
                  LEFT JOIN operacoes_logistica ol ON a.id_atendimento = ol.id_atendimento
                  LEFT JOIN usuarios u ON ol.id_motorista = u.id_usuario
                  WHERE a.id_atendimento = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id' => $id_atendimento]);
        return $stmt->fetch();
    }

    // Busca o histórico de ações para montar a Timeline
    public function getTimeline($id_atendimento) {
        $query = "SELECT t.*, u.nome AS nome_usuario 
                  FROM timeline_historico t
                  LEFT JOIN usuarios u ON t.id_usuario = u.id_usuario
                  WHERE t.id_atendimento = :id
                  ORDER BY t.data_hora DESC"; // Mais recentes primeiro
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id' => $id_atendimento]);
        return $stmt->fetchAll();
    }
}
?>