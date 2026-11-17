<!-- /views/painel_parceiro.php -->
<?php
session_start();
require_once '../config/database.php';

// Simulando o login de um Parceiro
$perfil_parceiro = $_SESSION['perfil_usuario'] ?? 'Floricultura'; 
$nome_parceiro = $_SESSION['nome_usuario'] ?? 'Florindo Flores';

$db = new Database();
$conn = $db->getConnection();

// Busca apenas os serviços ativos para exibição ao parceiro
// Na prática, você adicionaria um "WHERE" para filtrar só o que compete a eles.
$query = "SELECT 
            a.id_atendimento, a.tipo_funeral, a.fase_atual,
            f.nome AS nome_falecido,
            ol.capela_reservada, ol.horario_velorio
          FROM atendimentos a
          JOIN falecidos f ON a.id_falecido = f.id_falecido
          LEFT JOIN operacoes_logistica ol ON a.id_atendimento = ol.id_atendimento
          WHERE a.status_geral != 'Concluido'
          ORDER BY ol.horario_velorio ASC";

$stmt = $conn->prepare($query);
$stmt->execute();
$servicos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Demandas - <?= htmlspecialchars($nome_parceiro) ?></title>
    <style>
        :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --accent-orange: #f59e0b; --border-color: #334155; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-dark); color: var(--text-main); margin: 0; padding: 0; }
        
        .header-parceiro { background: #0b1120; padding: 25px 30px; border-bottom: 2px solid var(--accent-orange); display: flex; justify-content: space-between; align-items: center; }
        .header-parceiro h1 { margin: 0; color: #f8fafc; font-size: 1.5rem; }
        .header-parceiro span { color: var(--accent-orange); font-weight: bold; }
        .btn-sair { background: transparent; color: #94a3b8; border: 1px solid #94a3b8; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.85rem; }

        .container { padding: 30px; max-width: 1200px; margin: 0 auto; }
        .painel-title { color: #94a3b8; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }

        .grid-servicos { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        
        .card-servico { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 20px; border-left: 4px solid var(--accent-orange); }
        .card-header { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .os-badge { background: #334155; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .status-badge { color: var(--accent-orange); font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        
        .nome-falecido { font-size: 1.3rem; margin: 0 0 15px 0; font-weight: bold; }
        
        .info-row { display: flex; flex-direction: column; margin-bottom: 15px; background: rgba(15, 23, 42, 0.4); padding: 10px; border-radius: 6px; }
        .info-label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
        .info-valor { font-size: 1rem; font-weight: 500; }
        
        .alerta-horario { color: #10b981; font-weight: bold; font-size: 1.1rem; }
    </style>
</head>
<body>

    <div class="header-parceiro">
        <h1>Painel de Demandas | <span><?= htmlspecialchars($nome_parceiro) ?></span></h1>
        <a href="login.php" class="btn-sair">Sair do Sistema</a>
    </div>

    <div class="container">
        <h2 class="painel-title">Serviços Ativos e Agendamentos</h2>

        <?php if(empty($servicos)): ?>
            <div style="text-align: center; color: #94a3b8; padding: 50px;">
                Não há serviços designados para você no momento.
            </div>
        <?php else: ?>
            <div class="grid-servicos">
                <?php foreach($servicos as $servico): ?>
                    <div class="card-servico">
                        <div class="card-header">
                            <span class="os-badge">#OS-<?= str_pad($servico['id_atendimento'], 4, '0', STR_PAD_LEFT) ?></span>
                            <span class="status-badge"><?= htmlspecialchars($servico['fase_atual']) ?></span>
                        </div>
                        
                        <h3 class="nome-falecido"><?= htmlspecialchars($servico['nome_falecido']) ?></h3>
                        
                        <div class="info-row">
                            <span class="info-label">Destino / Capela</span>
                            <span class="info-valor"><?= htmlspecialchars($servico['capela_reservada'] ?? 'Aguardando definição') ?></span>
                        </div>

                        <div class="info-row" style="border-left: 2px solid #10b981;">
                            <span class="info-label">Horário do Velório / Entrega</span>
                            <span class="info-valor alerta-horario">
                                <?= $servico['horario_velorio'] ? date('d/m/Y - H:i', strtotime($servico['horario_velorio'])) : 'Não agendado' ?>
                            </span>
                        </div>
                        
                        <div style="font-size: 0.85rem; color: #94a3b8; text-align: right; margin-top: 10px;">
                            Tipo: <?= htmlspecialchars($servico['tipo_funeral']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>