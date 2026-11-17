<!-- /views/dashboard.php -->
<?php
session_start();
require_once '../app/Controllers/DashboardController.php';

$controller = new DashboardController();
$dados = $controller->getDadosDashboard();

// Preparando os dados para o JavaScript (Chart.js)
$labels_status = []; $valores_status = [];
foreach($dados['status'] as $s) {
    $labels_status[] = $s['status_geral'];
    $valores_status[] = $s['qtd'];
}

$labels_cat = []; $valores_cat = [];
foreach($dados['categorias'] as $c) {
    $labels_cat[] = $c['categoria_servico'];
    $valores_cat[] = $c['qtd'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGOF - Dashboard Gerencial</title>
    <!-- Importando o Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --text-muted: #94a3b8; --border-color: #334155; --accent-orange: #f59e0b; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-dark); color: var(--text-main); margin: 0; padding: 30px; }
        
        .header-dashboard { border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 30px; }
        .header-dashboard h1 { margin: 0; color: var(--accent-orange); font-size: 1.8rem; }
        
        /* KPIs no Topo */
        .kpi-container { display: flex; gap: 20px; margin-bottom: 30px; }
        .kpi-card { flex: 1; background: var(--bg-card); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; }
        .kpi-title { color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .kpi-value { font-size: 2.5rem; font-weight: bold; color: var(--text-main); }
        
        /* Container dos Gráficos */
        .charts-container { display: flex; gap: 20px; }
        .chart-box { flex: 1; background: var(--bg-card); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); height: 350px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .chart-title { align-self: flex-start; margin-top: 0; color: var(--text-muted); font-size: 1rem; width: 100%; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- 1. Importa a Sidebar Dinamicamente -->
    <?php include 'components/sidebar.php'; ?>

    <!-- 2. Engloba o conteúdo da página na classe main-content -->
    <div class="main-content">

    <div class="header-dashboard">
        <h1>Dashboard Gerencial</h1>
        <p style="color: var(--text-muted); margin: 5px 0 0 0;">Visão consolidada da operação funerária</p>
    </div>

    <!-- Indicadores Rápidos -->
    <div class="kpi-container">
        <div class="kpi-card">
            <div class="kpi-title">Total de Atendimentos</div>
            <div class="kpi-value"><?= $dados['total_geral'] ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Operações em Andamento</div>
            <!-- Exemplo simplificado, ideal é buscar os ativos reais -->
            <div class="kpi-value" style="color: #3b82f6;"><?= $dados['total_geral'] - $dados['total_atrasos'] ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Alertas / Atrasos</div>
            <div class="kpi-value" style="color: #ef4444;"><?= $dados['total_atrasos'] ?></div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="charts-container">
        <div class="chart-box">
            <h3 class="chart-title">Status Operacional</h3>
            <canvas id="graficoStatus"></canvas>
        </div>
        <div class="chart-box">
            <h3 class="chart-title">Serviços por Categoria</h3>
            <canvas id="graficoCategoria"></canvas>
        </div>
    </div>

    </div>

    <script>
        // Configuração de Cores para o Dark Mode
        Chart.defaults.color = '#94a3b8';
        
        // 1. Gráfico de Status (Doughnut)
        const ctxStatus = document.getElementById('graficoStatus').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_status) ?>,
                datasets: [{
                    data: <?= json_encode($valores_status) ?>,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                    borderColor: '#1e293b',
                    borderWidth: 2
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // 2. Gráfico de Categorias (Bar)
        const ctxCat = document.getElementById('graficoCategoria').getContext('2d');
        new Chart(ctxCat, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_cat) ?>,
                datasets: [{
                    label: 'Quantidade de Serviços',
                    data: <?= json_encode($valores_cat) ?>,
                    backgroundColor: '#f59e0b',
                    borderRadius: 6
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#334155' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>