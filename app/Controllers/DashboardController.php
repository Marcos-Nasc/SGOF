<?php
// /app/Controllers/DashboardController.php
require_once '../config/database.php';

class DashboardController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Busca dados consolidados para os gráficos
    public function getDadosDashboard() {
        $dados = [];

        // 1. Resumo Geral de Status
        $stmtStatus = $this->conn->query("SELECT status_geral, COUNT(*) as qtd FROM atendimentos GROUP BY status_geral");
        $dados['status'] = $stmtStatus->fetchAll();

        // 2. Serviços por Categoria (Particular, Plano, Prefeitura, etc.)
        $stmtCategoria = $this->conn->query("SELECT categoria_servico, COUNT(*) as qtd FROM atendimentos GROUP BY categoria_servico");
        $dados['categorias'] = $stmtCategoria->fetchAll();

        // 3. Indicadores Rápidos (KPIs)
        $stmtTotal = $this->conn->query("SELECT COUNT(*) as total FROM atendimentos");
        $dados['total_geral'] = $stmtTotal->fetch()['total'];

        $stmtAtrasos = $this->conn->query("SELECT COUNT(*) as total FROM atendimentos WHERE status_geral = 'Atrasado'");
        $dados['total_atrasos'] = $stmtAtrasos->fetch()['total'];

        return $dados;
    }
}
?>