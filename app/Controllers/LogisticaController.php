<?php
// /app/Controllers/LogisticaController.php

require_once '../config/database.php';

class LogisticaController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Busca todos os atendimentos para exibir no Dashboard/Kanban
    public function listarAtendimentosAtivos() {
        $query = "SELECT 
                    a.id_atendimento, a.categoria_servico, a.fase_atual, a.progresso_percentual, a.tipo_funeral,
                    f.nome AS nome_falecido, f.local_obito,
                    c.nome AS nome_contratante, c.grau_parentesco,
                    ol.horario_velorio, ol.horario_sepultamento_cremacao, ol.capela_reservada,
                    u.nome AS nome_motorista
                  FROM atendimentos a
                  JOIN falecidos f ON a.id_falecido = f.id_falecido
                  JOIN contratantes c ON a.id_contratante = c.id_contratante
                  LEFT JOIN operacoes_logistica ol ON a.id_atendimento = ol.id_atendimento
                  LEFT JOIN usuarios u ON ol.id_motorista = u.id_usuario
                  WHERE a.status_geral IN ('Em Andamento', 'Aguardando', 'Atrasado')
                  ORDER BY a.data_abertura DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Busca e agrupa os atendimentos pelas fases do Kanban
    public function getKanbanData() {
        $query = "SELECT 
                    a.id_atendimento, a.fase_atual, a.categoria_servico,
                    f.nome AS nome_falecido,
                    ol.capela_reservada
                  FROM atendimentos a
                  JOIN falecidos f ON a.id_falecido = f.id_falecido
                  LEFT JOIN operacoes_logistica ol ON a.id_atendimento = ol.id_atendimento
                  WHERE a.status_geral != 'Concluido'
                  ORDER BY a.data_abertura DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultados = $stmt->fetchAll();

        // Inicializa o array com as colunas oficiais do requisito
        $kanban = [
            'Atendimento' => [],
            'Conferencia' => [],
            'Logistica' => [],
            'Laboratorio' => [],
            'Ornamentacao' => [],
            'Capela' => [],
            'Velorio' => [],
            'Sepultamento' => [],
            'Finalizados' => []
        ];

        // Distribui os cards nas colunas correspondentes
        foreach ($resultados as $row) {
            $fase = $row['fase_atual'];
            if (isset($kanban[$fase])) {
                $kanban[$fase][] = $row;
            }
        }

        return $kanban;
    }

    public function getIndicadoresSalaTV() {
        // Busca contagem de serviços ativos (não concluídos)
        $stmtAtivos = $this->conn->query("SELECT COUNT(*) as total FROM atendimentos WHERE status_geral != 'Concluido'");
        $ativos = $stmtAtivos->fetch()['total'];

        // Busca serviços marcados como 'Atrasado'
        $stmtAtrasos = $this->conn->query("SELECT COUNT(*) as total FROM atendimentos WHERE status_geral = 'Atrasado'");
        $atrasos = $stmtAtrasos->fetch()['total'];

        // Busca os próximos 5 eventos (Velórios ou Sepultamentos) ordenados por horário mais próximo
        $queryProximos = "SELECT a.id_atendimento, f.nome, ol.horario_velorio, ol.capela_reservada 
                          FROM atendimentos a 
                          JOIN falecidos f ON a.id_falecido = f.id_falecido 
                          JOIN operacoes_logistica ol ON a.id_atendimento = ol.id_atendimento 
                          WHERE ol.horario_velorio > NOW() AND a.status_geral != 'Concluido'
                          ORDER BY ol.horario_velorio ASC LIMIT 5";
        $proximos = $this->conn->query($queryProximos)->fetchAll();

        return [
            'ativos' => $ativos,
            'atrasos' => $atrasos,
            'veiculos_rota' => 2, // Dado fixo temporário até a integração com a Cobli
            'proximos_eventos' => $proximos
        ];
    }

    public function getAgendaEventos($data_alvo = null) {
        // Se não passar data, assume o dia atual
        if (!$data_alvo) {
            $data_alvo = date('Y-m-d');
        }

        $query = "SELECT 
                    a.id_atendimento, a.tipo_funeral,
                    f.nome AS nome_falecido,
                    ol.horario_velorio, ol.horario_sepultamento_cremacao, 
                    ol.capela_reservada, ol.laboratorio_destino,
                    u.nome AS nome_motorista
                  FROM atendimentos a
                  JOIN falecidos f ON a.id_falecido = f.id_falecido
                  JOIN operacoes_logistica ol ON a.id_atendimento = ol.id_atendimento
                  LEFT JOIN usuarios u ON ol.id_motorista = u.id_usuario
                  WHERE DATE(ol.horario_velorio) = :data 
                     OR DATE(ol.horario_sepultamento_cremacao) = :data
                  ORDER BY ol.horario_velorio ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['data' => $data_alvo]);
        
        return $stmt->fetchAll();
    }
}
?>