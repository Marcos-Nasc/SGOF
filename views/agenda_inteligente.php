<!-- /views/agenda_inteligente.php -->
<?php
session_start();
require_once '../app/Controllers/LogisticaController.php';

$controller = new LogisticaController();
$data_hoje = date('Y-m-d');
$data_selecionada = $_GET['data'] ?? $data_hoje;

$eventos = $controller->getAgendaEventos($data_selecionada);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGOF - Agenda Inteligente</title>
    <style>
    :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --text-muted: #94a3b8; --border-color: #334155; --accent-orange: #f59e0b; --accent-red: #ef4444; }
    
    /* 1. CORREÇÃO AQUI: Tiramos o padding e colocamos display: flex */
    /* 1. O Body segura a Sidebar (Esquerda) e o Main (Direita) */
body { 
    font-family: 'Segoe UI', sans-serif; 
    background: var(--bg-dark); 
    color: var(--text-main); 
    margin: 0; 
    display: flex; 
    align-items: flex-start; 
    min-height: 100vh;
}

/* 2. O Main Content organiza a página de cima para baixo */
.main-content {
    margin-left: 260px; 
    width: calc(100% - 260px);
    padding: 30px; 
    box-sizing: border-box;
    display: flex;
    flex-direction: column; /* ISSO AQUI SALVA O LAYOUT: Força os itens a ficarem um embaixo do outro */
}

/* 3. Ajuste do título para não ficar gigante */
.header-agenda { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    border-bottom: 1px solid var(--border-color); 
    padding-bottom: 20px; 
    margin-bottom: 30px; 
}

.header-agenda h1 { 
    margin: 0; 
    color: var(--accent-orange);
    font-size: 1.8rem; /* Controlando o tamanho do H1 */
}
    
    .date-picker-form { display: flex; gap: 10px; align-items: center; }
    .date-picker-form input[type="date"] { padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: white; }
    .btn-filtrar { background: var(--accent-orange); color: black; font-weight: bold; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
    
    .agenda-container { background: var(--bg-card); border-radius: 12px; padding: 30px; border: 1px solid var(--border-color); }
    
    .evento-row { display: flex; align-items: flex-start; padding: 20px 0; border-bottom: 1px dashed var(--border-color); }
    .evento-row:last-child { border-bottom: none; }
    
    .evento-horario { font-size: 1.5rem; font-weight: bold; color: var(--accent-orange); width: 120px; flex-shrink: 0; }
    
    .evento-detalhes { flex-grow: 1; }
    .evento-titulo { font-size: 1.2rem; margin: 0 0 10px 0; }
    .os-badge { background: #334155; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; margin-right: 10px; }
    
    .recursos-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px; }
    .recurso-item { background: rgba(15, 23, 42, 0.5); padding: 10px; border-radius: 6px; border-left: 3px solid #3b82f6; font-size: 0.85rem; }
    .recurso-item.alerta { border-left-color: var(--accent-red); background: rgba(239, 68, 68, 0.1); }
    
    .r-label { display: block; color: var(--text-muted); margin-bottom: 4px; font-size: 0.75rem; text-transform: uppercase; }
    .r-valor { font-weight: 500; }
    .alerta-texto { color: var(--accent-red); font-weight: bold; }
</style>
</head>
<body>
    <!-- 1. Importa a Sidebar Dinamicamente -->
    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">

    <div class="header-agenda">
        <h1>Agenda Inteligente</h1>
        <form class="date-picker-form" method="GET" action="agenda_inteligente.php">
            <label style="color: var(--text-muted);">Data Operacional:</label>
            <input type="date" name="data" value="<?= htmlspecialchars($data_selecionada) ?>">
            <button type="submit" class="btn-filtrar">Atualizar</button>
        </form>
    </div>

    <div class="agenda-container">
        <?php if(empty($eventos)): ?>
            <p style="text-align: center; color: var(--text-muted);">Nenhum evento agendado para esta data.</p>
        <?php else: ?>
            <?php foreach($eventos as $evento): ?>
                
                <?php 
                    // Formata a exibição do horário
                    $hora_exibicao = date('H:i', strtotime($evento['horario_velorio']));
                    $alerta_motorista = empty($evento['nome_motorista']);
                    $alerta_capela = empty($evento['capela_reservada']);
                ?>

                <div class="evento-row">
                    <div class="evento-horario">
                        <?= $hora_exibicao ?>
                    </div>
                    
                    <div class="evento-detalhes">
                        <h3 class="evento-titulo">
                            <span class="os-badge">#OS-<?= str_pad($evento['id_atendimento'], 4, '0', STR_PAD_LEFT) ?></span>
                            <?= htmlspecialchars($evento['nome_falecido']) ?>
                        </h3>
                        
                        <div style="font-size: 0.9rem; color: #cbd5e1; margin-bottom: 15px;">
                            Serviço: <?= htmlspecialchars($evento['tipo_funeral']) ?>
                        </div>

                        <div class="recursos-grid">
                            <!-- Recurso: Capela -->
                            <div class="recurso-item <?= $alerta_capela ? 'alerta' : '' ?>">
                                <span class="r-label">Local (Capela)</span>
                                <span class="r-valor <?= $alerta_capela ? 'alerta-texto' : '' ?>">
                                    <?= htmlspecialchars($evento['capela_reservada'] ?? 'Pendente de Reserva') ?>
                                </span>
                            </div>
                            
                            <!-- Recurso: Motorista -->
                            <div class="recurso-item <?= $alerta_motorista ? 'alerta' : '' ?>">
                                <span class="r-label">Motorista Designado</span>
                                <span class="r-valor <?= $alerta_motorista ? 'alerta-texto' : '' ?>">
                                    <?= htmlspecialchars($evento['nome_motorista'] ?? 'Pendente de Atribuição') ?>
                                </span>
                            </div>

                            <!-- Recurso: Laboratório -->
                            <div class="recurso-item">
                                <span class="r-label">Laboratório</span>
                                <span class="r-valor">
                                    <?= htmlspecialchars($evento['laboratorio_destino'] ?? 'Não definido') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>