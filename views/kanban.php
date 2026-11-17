<!-- /views/kanban.php -->
<?php
session_start();
require_once '../app/Controllers/LogisticaController.php';

$controller = new LogisticaController();
$colunasKanban = $controller->getKanbanData();

// Nomes de exibição amigáveis, AGORA COM A NOVA FASE "Em Movimento"
$titulosColunas = [
    'Atendimento' => 'Atendimento',
    'Conferencia' => 'Conferência',
    'Logistica' => 'Logística',
    'Em Movimento' => 'Em Movimento', /* <-- NOVA FASE */
    'Laboratorio' => 'Laboratório',
    'Ornamentacao' => 'Ornamentação',
    'Capela' => 'Capela',
    'Velorio' => 'Velório',
    'Sepultamento' => 'Sepultamento / Cremação',
    'Finalizados' => 'Finalizados'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGOF - Kanban Operacional</title>
    <style>
        :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --text-muted: #94a3b8; --border-color: #334155; --accent-orange: #f59e0b; --accent-green: #10b981; --accent-blue: #38bdf8; --accent-pink: #ec4899; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-dark); color: var(--text-main); margin: 0; display: flex; flex-direction: column; height: 100vh; }
        
        .header { padding: 20px 30px; border-bottom: 1px solid var(--border-color); background: #0b1120; }
        .header h1 { margin: 0; font-size: 1.5rem; color: var(--accent-orange); }

        .kanban-board {
            display: flex;
            gap: 20px;
            padding: 20px 30px;
            overflow-x: auto;
            flex-grow: 1;
            align-items: flex-start;
        }

        .kanban-column {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            min-width: 300px;
            max-width: 300px;
            display: flex;
            flex-direction: column;
            max-height: 100%;
        }

        .column-header {
            padding: 15px;
            font-weight: bold;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .column-count {
            background: var(--bg-dark);
            color: var(--text-muted);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .column-body {
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .kanban-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 12px;
            cursor: pointer;
            transition: transform 0.2s, border-color 0.2s;
        }
        
        .kanban-card:hover {
            border-color: var(--accent-orange);
            transform: translateY(-2px);
        }

        .k-card-id { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 5px; display: block; }
        .k-card-title { margin: 0 0 8px 0; font-size: 1rem; font-weight: 600; }
        .k-card-info { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 4px; }
        .badge { background: rgba(245, 158, 11, 0.1); color: var(--accent-orange); padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; float: right; }
        
        /* Rodapé Dinâmico do Card */
        .k-card-driver {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed var(--border-color);
            font-size: 0.8rem;
            font-weight: 500;
        }
        .color-logistica { color: var(--accent-green); }
        .color-lab { color: var(--accent-blue); }
        .color-ornamentacao { color: var(--accent-pink); }
        .color-atendimento { color: var(--accent-orange); }
        .unallocated { color: var(--text-muted); font-style: italic; }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-dark); }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Kanban Operacional</h1>
        </div>

        <div class="kanban-board">
            <?php foreach ($titulosColunas as $faseKey => $tituloColuna): ?>
                <?php $cards = $colunasKanban[$faseKey] ?? []; ?>
                <div class="kanban-column">
                    <div class="column-header">
                        <span><?= $tituloColuna ?></span>
                        <span class="column-count"><?= count($cards) ?></span>
                    </div>
                    <div class="column-body">
                        
                        <?php if (empty($cards)): ?>
                            <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 20px 0;">Sem serviços</div>
                        <?php else: ?>
                            <?php foreach ($cards as $card): ?>
                                <div class="kanban-card" onclick="window.location.href='ver_processo.php?id=<?= $card['id_atendimento'] ?>'">
                                    <span class="badge"><?= mb_strtoupper($card['categoria_servico'] ?? '') ?></span>
                                    <span class="k-card-id">#OS-2026-<?= str_pad($card['id_atendimento'], 4, '0', STR_PAD_LEFT) ?></span>
                                    <h3 class="k-card-title"><?= htmlspecialchars($card['nome_falecido'] ?? 'Não informado') ?></h3>
                                    
                                    <!-- Apenas mostra o local se já houver um definido -->
                                    <?php if(!empty($card['capela_reservada'])): ?>
                                        <div class="k-card-info">📍 Local: <?= htmlspecialchars($card['capela_reservada']) ?></div>
                                    <?php endif; ?>
                                    
                                    <!-- ============================================== -->
                                    <!-- LÓGICA INTELIGENTE DO RESPONSÁVEL POR ETAPA -->
                                    <!-- ============================================== -->
                                    <?php 
                                        $textoResponsavel = '';
                                        $classeCor = '';
                                        $icone = '';
                                        $nome = null;

                                        switch ($faseKey) {
                                            case 'Ornamentacao':
                                                $icone = '🌸';
                                                $classeCor = 'color-ornamentacao';
                                                $nome = $card['nome_floricultura'] ?? $card['tipo_ornamentacao'] ?? null;
                                                $textoResponsavel = $nome ? "Floricultura: $nome" : '⚠️ Floricultura pendente';
                                                break;
                                                
                                            case 'Laboratorio':
                                                $icone = '🔬';
                                                $classeCor = 'color-lab';
                                                $nome = $card['nome_laboratorio'] ?? $card['laboratorio_destino'] ?? null;
                                                $textoResponsavel = $nome ? "Lab: $nome" : '⚠️ Laboratório pendente';
                                                break;
                                                
                                            case 'Logistica':
                                            case 'Em Movimento':
                                                $icone = '🚚';
                                                $classeCor = 'color-logistica';
                                                $nome = $card['nome_motorista'] ?? null;
                                                $textoResponsavel = $nome ? "Motorista: $nome" : '⚠️ Sem motorista alocado';
                                                break;
                                                
                                            case 'Capela':
                                            case 'Velorio':
                                            case 'Sepultamento':
                                                $icone = '🕴️';
                                                $classeCor = 'color-atendimento';
                                                $nome = $card['nome_cerimonialista'] ?? null;
                                                $textoResponsavel = $nome ? "Agente: $nome" : '⚠️ Cerimonial pendente';
                                                break;
                                                
                                            default: // Atendimento, Conferencia
                                                $icone = '👨‍💻';
                                                $classeCor = 'color-atendimento';
                                                $nome = $card['nome_atendente'] ?? null;
                                                $textoResponsavel = $nome ? "Atendente: $nome" : '⏳ Aguardando triagem';
                                                break;
                                        }

                                        // Se não tiver nome, perde a cor de destaque e fica cinza (unallocated)
                                        $classeFinal = $nome ? "k-card-driver $classeCor" : "k-card-driver unallocated";
                                    ?>
                                    <div class="<?= $classeFinal ?>">
                                        <?= $icone . ' ' . htmlspecialchars($textoResponsavel) ?>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>