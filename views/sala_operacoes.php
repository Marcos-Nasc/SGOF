<!-- /views/sala_operacoes.php -->
<?php
require_once '../app/Controllers/LogisticaController.php';
$controller = new LogisticaController();
$dadosTV = $controller->getIndicadoresSalaTV();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGOF - Monitoramento TV</title>
    <!-- O meta refresh abaixo recarrega a página a cada 60 segundos -->
    <meta http-equiv="refresh" content="60">
    <style>
        :root { --bg-dark: #000000; --bg-card: #0f172a; --text-main: #f8fafc; --accent-orange: #f59e0b; --accent-red: #ef4444; --accent-green: #10b981; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-main); margin: 0; padding: 20px; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        
        .tv-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #334155; padding-bottom: 20px; margin-bottom: 20px; }
        .tv-title { font-size: 2.5rem; color: var(--accent-orange); margin: 0; font-weight: 800; letter-spacing: 2px; }
        .tv-clock { font-size: 3rem; font-weight: bold; font-variant-numeric: tabular-nums; }

        .kpi-container { display: flex; gap: 20px; margin-bottom: 30px; }
        .kpi-box { flex: 1; background: var(--bg-card); border-radius: 12px; padding: 30px; text-align: center; border: 1px solid #334155; }
        .kpi-title { font-size: 1.2rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
        .kpi-value { font-size: 4rem; font-weight: bold; margin-top: 10px; }
        .val-red { color: var(--accent-red); }
        .val-green { color: var(--accent-green); }
        .val-blue { color: #3b82f6; }

        .list-container { background: var(--bg-card); border-radius: 12px; padding: 30px; border: 1px solid #334155; flex-grow: 1; }
        .list-title { font-size: 1.5rem; color: var(--accent-orange); margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; font-size: 1.2rem; }
        th, td { text-align: left; padding: 15px; border-bottom: 1px solid #1e293b; }
        th { color: #94a3b8; font-weight: normal; text-transform: uppercase; font-size: 1rem; }
        tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
    <!-- 1. Importa a Sidebar Dinamicamente -->
    <?php include 'components/sidebar.php'; ?>

    <!-- 2. Engloba o conteúdo da página na classe main-content -->
    <div class="main-content">

    <div class="tv-header">
        <h1 class="tv-title">SALA DE OPERAÇÕES</h1>
        <div class="tv-clock" id="relogio">00:00:00</div>
    </div>

    <div class="kpi-container">
        <div class="kpi-box">
            <div class="kpi-title">Serviços Ativos</div>
            <div class="kpi-value val-blue"><?= $dadosTV['ativos'] ?></div>
        </div>
        <div class="kpi-box" style="border-color: <?= $dadosTV['atrasos'] > 0 ? 'var(--accent-red)' : '#334155' ?>;">
            <div class="kpi-title">Alertas / Atrasos</div>
            <div class="kpi-value <?= $dadosTV['atrasos'] > 0 ? 'val-red' : 'val-green' ?>"><?= $dadosTV['atrasos'] ?></div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Veículos em Rota</div>
            <div class="kpi-value val-green"><?= $dadosTV['veiculos_rota'] ?></div>
        </div>
    </div>

    <div class="list-container">
        <h2 class="list-title">Próximos Eventos Programados</h2>
        <table>
            <thead>
                <tr>
                    <th>OS</th>
                    <th>Falecido</th>
                    <th>Local</th>
                    <th>Horário Previsto</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($dadosTV['proximos_eventos'])): ?>
                    <tr><td colspan="4" style="text-align: center; color: #94a3b8;">Nenhum evento programado.</td></tr>
                <?php else: ?>
                    <?php foreach($dadosTV['proximos_eventos'] as $evento): ?>
                        <tr>
                            <td style="color: #94a3b8;">#OS-<?= str_pad($evento['id_atendimento'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td style="font-weight: bold;"><?= htmlspecialchars($evento['nome']) ?></td>
                            <td><?= htmlspecialchars($evento['capela_reservada'] ?? 'A definir') ?></td>
                            <td style="color: var(--accent-orange);"><?= date('d/m/Y H:i', strtotime($evento['horario_velorio'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>

    <script>
        // Script para o relógio em tempo real da TV
        function atualizarRelogio() {
            const agora = new Date();
            const horas = String(agora.getHours()).padStart(2, '0');
            const minutos = String(agora.getMinutes()).padStart(2, '0');
            const segundos = String(agora.getSeconds()).padStart(2, '0');
            document.getElementById('relogio').textContent = `${horas}:${minutos}:${segundos}`;
        }
        setInterval(atualizarRelogio, 1000);
        atualizarRelogio();
    </script>
</body>
</html>