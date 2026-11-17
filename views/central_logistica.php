<!-- /views/central_logistica.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../app/Controllers/LogisticaController.php';

// Busca os atendimentos ativos
$controller = new LogisticaController();
$atendimentos = $controller->listarAtendimentosAtivos();

// Busca motoristas do banco
$db = new Database();
$conn = $db->getConnection();
$motoristasAtivos = $conn->query("SELECT nome FROM usuarios WHERE perfil = 'Motorista'")->fetchAll(PDO::FETCH_ASSOC);

// Listas Oficiais Padronizadas
$labsOficiais = [
    'Laboratório da Penitencia (Caju)', 
    'Laboratório Labtemforj (Inhauma)'
];

$capelasOficiais = [
    'São Francisco Xavier (Cemitério) e Sala de Preparo',
    'São Francisco Xavier (Crematório) Premium',
    'Ilha do Governador - Capelas normais e Premium',
    'Santa Cruz',
    'Ricardo de Albuquerque - Capelas normais e Premium e Sala de Preparo',
    'Realengo',
    'Guaratiba',
    'Paqueta'
];

$floriculturasOficiais = ['Ornamentaçao Florindo (Caju)'];

// Sequência lógica (Pipeline) do processo logístico
$fluxoFases = [
    'Atendimento' => 'Conferencia',
    'Conferencia' => 'Logistica',
    'Logistica' => 'Em Movimento',
    'Em Movimento' => 'Laboratorio',
    'Laboratorio' => 'Ornamentacao',
    'Ornamentacao' => 'Capela',
    'Capela' => 'Velorio',
    'Velorio' => 'Sepultamento',
    'Sepultamento' => 'Finalizados',
    'Finalizados' => null
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGOF - Central de Operações</title>
    <style>
        :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --text-muted: #94a3b8; --accent-orange: #f59e0b; --accent-green: #10b981; --accent-blue: #3b82f6; --border-color: #334155; }
        
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-dark); color: var(--text-main); display: flex; flex-direction: column; height: 100vh; }
        
        .topbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background-color: #0b1120; border-bottom: 1px solid var(--border-color); }
        .topbar-left h1 { margin: 0; font-size: 1.2rem; letter-spacing: 1px; }
        .topbar-stats { display: flex; gap: 30px; }
        .stat-item { text-align: center; font-size: 0.85rem; color: var(--text-muted); }
        .stat-item strong { display: block; font-size: 1.1rem; color: var(--text-main); margin-top: 4px; }
        .btn-novo-atendimento { background-color: var(--accent-orange); color: #000; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-novo-atendimento:hover { opacity: 0.9; }

        .subnav { display: flex; gap: 20px; padding: 15px 30px; border-bottom: 1px solid var(--border-color); background-color: var(--bg-dark); }
        .subnav a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 500; }
        .subnav a.active { color: var(--accent-orange); border-bottom: 2px solid var(--accent-orange); padding-bottom: 5px; }

        .board-container { padding: 20px 30px; display: flex; gap: 20px; overflow-x: auto; flex-grow: 1; align-items: flex-start; }

        .card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; min-width: 380px; max-width: 400px; padding: 20px; display: flex; flex-direction: column; gap: 12px; }
        .card-header { display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: bold; }
        .badge-particular { background-color: rgba(245, 158, 11, 0.2); color: var(--accent-orange); padding: 4px 8px; border-radius: 4px; }
        .badge-fase { background-color: rgba(16, 185, 129, 0.2); color: var(--accent-green); padding: 4px 8px; border-radius: 4px; }

        .card-title { margin: 0; font-size: 1.2rem; }
        .card-subtitle { font-size: 0.85rem; color: var(--text-muted); margin: 0; }

        /* Estilo do Resumo Estático no Card (Sem Selects) */
        .op-summary { background: rgba(15, 23, 42, 0.5); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
        .summary-item { font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
        .summary-item span { font-weight: 500; color: #f8fafc; }
        .summary-item .pendente { color: var(--text-muted); font-style: italic; font-weight: normal; }

        .card-actions { margin-top: 15px; display: flex; gap: 10px; justify-content: space-between; }
        .btn-action { flex: 1; background-color: transparent; border: 1px solid var(--border-color); color: white; padding: 10px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: bold; transition: 0.2s; text-align: center; text-decoration: none; }
        .btn-action:hover { background-color: rgba(255, 255, 255, 0.05); }
        
        .btn-avancar { flex: 2; background-color: var(--accent-green); color: #000; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: bold; transition: 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .btn-avancar:hover { opacity: 0.9; transform: translateY(-1px); }

        /* MODAL DE CONFIRMAÇÃO */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center; }
        .modal-content { background: var(--bg-card); width: 450px; padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); }
        .modal-content h3 { margin-top: 0; color: var(--accent-orange); border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        .op-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 15px; }
        .op-field label { font-size: 0.8rem; color: var(--text-muted); }
        .op-select { width: 100%; padding: 8px; border-radius: 4px; background: var(--bg-dark); color: white; border: 1px solid var(--border-color); font-size: 0.9rem; }
        .op-select:focus { border-color: var(--accent-orange); outline: none; }
        
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; }
        .btn-cancel { background: transparent; color: white; border: 1px solid var(--border-color); padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .btn-confirm { background: var(--accent-green); color: black; font-weight: bold; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>CENTRAL<br><span style="color: var(--text-muted); font-size: 0.9rem;">OPS</span></h1>
        </div>
        <div class="topbar-stats">
            <div class="stat-item">SERVIÇOS ATIVOS<br><strong style="color: var(--accent-blue);">● <?= count($atendimentos) ?></strong></div>
            <div class="stat-item">FROTA COBLI<br><strong style="color: var(--accent-green);">Ativa</strong></div>
        </div>
        <div class="topbar-actions">
            <button class="btn-novo-atendimento">⊕ Novo Atendimento</button>
        </div>
    </div>

    <div class="subnav">
        <a href="#" class="active">Central de Triagem Rápida</a>
        <a href="kanban.php">Kanban Operacional</a>
    </div>

    <div class="board-container">
        
        <?php if(empty($atendimentos)): ?>
            <p style="color: var(--text-muted); text-align: center; width: 100%; margin-top: 50px;">Nenhum serviço ativo pendente de ação na logística.</p>
        <?php else: ?>

            <?php foreach ($atendimentos as $os): 
                $faseAtual = $os['fase_atual'];
                $proximaFase = $fluxoFases[$faseAtual] ?? 'Finalizados';
                $id = $os['id_atendimento'];
            ?>
            <div class="card">
                <div class="card-header">
                    <span style="color: var(--text-muted);">#OS-2026-<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?></span>
                    <div>
                        <span class="badge-particular"><?= mb_strtoupper($os['categoria_servico']) ?></span>
                        <span class="badge-fase"><?= mb_strtoupper($faseAtual) ?></span>
                    </div>
                </div>
                
                <div>
                    <h2 class="card-title"><?= htmlspecialchars($os['nome_falecido']) ?></h2>
                    <p class="card-subtitle">Contratante: <?= htmlspecialchars($os['nome_contratante']) ?></p>
                </div>

                <!-- CARD LIMPO (Visualização Informativa) -->
                <div class="op-summary">
                    <div class="summary-item">
                        🚚 
                        <?php if(!empty($os['nome_motorista'])): ?>
                            <span style="color: var(--accent-green);"><?= htmlspecialchars($os['nome_motorista']) ?></span>
                        <?php else: ?>
                            <span class="pendente">Sem motorista</span>
                        <?php endif; ?>
                    </div>
                    <div class="summary-item">
                        🔬 
                        <?php if(!empty($os['laboratorio_destino'])): ?>
                            <span><?= htmlspecialchars($os['laboratorio_destino']) ?></span>
                        <?php else: ?>
                            <span class="pendente">Lab não definido</span>
                        <?php endif; ?>
                    </div>
                    <div class="summary-item">
                        📍 
                        <?php if(!empty($os['capela_reservada'])): ?>
                            <span><?= htmlspecialchars($os['capela_reservada']) ?></span>
                        <?php else: ?>
                            <span class="pendente">Capela não definida</span>
                        <?php endif; ?>
                    </div>
                    <div class="summary-item">
                        🌸 
                        <?php if(!empty($os['nome_floricultura'])): ?>
                            <span><?= htmlspecialchars($os['nome_floricultura']) ?></span>
                        <?php else: ?>
                            <span class="pendente">Sem floricultura</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-actions">
                    <a href="ver_processo.php?id=<?= $id ?>" class="btn-action">Ver Completo</a>
                    
                    <?php if($proximaFase): ?>
                        <!-- Dispara o Modal passando os dados do banco pré-selecionados -->
                        <button class="btn-avancar" onclick="abrirModalAvanco(
                            <?= $id ?>, 
                            '<?= $proximaFase ?>', 
                            '<?= htmlspecialchars($os['nome_motorista'] ?? '', ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($os['laboratorio_destino'] ?? '', ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($os['capela_reservada'] ?? '', ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($os['nome_floricultura'] ?? '', ENT_QUOTES) ?>'
                        )">
                            Avançar p/ <?= mb_strtoupper($proximaFase) ?> ➔
                        </button>
                    <?php else: ?>
                        <button class="btn-avancar" style="background: var(--border-color); color: white;" disabled>
                            Processo Finalizado
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>
        
    </div>
    </div>

    <!-- MODAL INVISÍVEL: SÓ APARECE QUANDO O BOTÃO AVANÇAR É CLICADO -->
    <div id="modalAvanco" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitleText">Revisão de Avanço</h3>
            <p style="font-size: 0.85rem; color: #94a3b8; margin-top: -10px; margin-bottom: 20px;">
                Revise ou altere as atribuições antes de mover o processo.
            </p>
            
            <input type="hidden" id="modalAtendimentoId">
            <input type="hidden" id="modalProximaFase">
            
            <div class="op-field">
                <label>🚚 Motorista / Veículo</label>
                <select id="modalMot" class="op-select">
                    <option value="">Aguardando atribuição...</option>
                    <?php foreach($motoristasAtivos as $mot): ?>
                        <option value="<?= htmlspecialchars($mot['nome']) ?>"><?= htmlspecialchars($mot['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="op-field">
                <label>🔬 Laboratório Destino</label>
                <select id="modalLab" class="op-select">
                    <option value="">Não definido</option>
                    <?php foreach($labsOficiais as $lab): ?>
                        <option value="<?= $lab ?>"><?= $lab ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="op-field">
                <label>📍 Capela Reservada</label>
                <select id="modalCap" class="op-select">
                    <option value="">Não definido</option>
                    <?php foreach($capelasOficiais as $cap): ?>
                        <option value="<?= $cap ?>"><?= $cap ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="op-field">
                <label>🌸 Floricultura</label>
                <select id="modalFlor" class="op-select">
                    <option value="">Não definido</option>
                    <?php foreach($floriculturasOficiais as $flor): ?>
                        <option value="<?= $flor ?>"><?= $flor ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-actions">
                <button onclick="fecharModal()" class="btn-cancel">Cancelar</button>
                <button onclick="efetivarAvanco()" class="btn-confirm" id="btnConfirmarModal">Confirmar e Avançar</button>
            </div>
        </div>
    </div>

<script>
    // 1. Abre o modal e injeta os dados pré-selecionados
    function abrirModalAvanco(id, proximaFase, motorista, lab, capela, flor) {
        document.getElementById('modalAtendimentoId').value = id;
        document.getElementById('modalProximaFase').value = proximaFase;
        
        document.getElementById('modalTitleText').innerText = 'Avançar para: ' + proximaFase.toUpperCase();
        
        // Seta os selects com as informações atuais do banco
        document.getElementById('modalMot').value = motorista;
        document.getElementById('modalLab').value = lab;
        document.getElementById('modalCap').value = capela;
        document.getElementById('modalFlor').value = flor;
        
        document.getElementById('modalAvanco').style.display = 'flex';
    }

    function fecharModal() {
        document.getElementById('modalAvanco').style.display = 'none';
    }

    // 2. Dispara as APIs para salvar alterações (se houver) e avançar a fase
    async function efetivarAvanco() {
        
        const btn = document.getElementById('btnConfirmarModal');
        btn.innerText = 'Processando...';
        btn.disabled = true;

        const idAtendimento = document.getElementById('modalAtendimentoId').value;
        const proximaFase = document.getElementById('modalProximaFase').value;
        
        const motorista = document.getElementById('modalMot').value;
        const laboratorio = document.getElementById('modalLab').value;
        const capela = document.getElementById('modalCap').value;
        const floricultura = document.getElementById('modalFlor').value;

        try {
            // Salva as integrações validadas pelo operador
            const reqSalvar = await fetch('../app/Api/editar_dados.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_atendimento: idAtendimento,
                    nome_motorista: motorista,
                    laboratorio_destino: laboratorio,
                    capela_reservada: capela,
                    nome_floricultura: floricultura
                })
            });
            const resSalvar = await reqSalvar.json();
            
            if(resSalvar.status !== 'success') {
                alert('Erro ao salvar os dados: ' + resSalvar.message);
                btn.innerText = 'Confirmar e Avançar';
                btn.disabled = false;
                return;
            }

            // Move o processo para a próxima fase
            const reqAvancar = await fetch('../app/Api/alterar_status_manual.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id_atendimento: idAtendimento, 
                    nova_fase: proximaFase 
                })
            });
            const resAvancar = await reqAvancar.json();

            if(resAvancar.status === 'success') {
                window.location.reload(); // Recarrega para aplicar as mudanças
            } else {
                alert('Os dados foram salvos, mas houve erro ao avançar a fase: ' + resAvancar.message);
                btn.innerText = 'Confirmar e Avançar';
                btn.disabled = false;
            }

        } catch (error) {
            console.error('Falha de comunicação', error);
            alert('Falha de comunicação com o servidor.');
            btn.innerText = 'Confirmar e Avançar';
            btn.disabled = false;
        }
    }
</script>
</body>
</html>