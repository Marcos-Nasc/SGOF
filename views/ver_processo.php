<!-- /views/ver_processo.php -->
<?php
session_start();
require_once '../config/database.php';
require_once '../app/Controllers/AtendimentoController.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID do atendimento não fornecido.");
}

$controller = new AtendimentoController();
$dados = $controller->getDetalhesAtendimento($id);
$timeline = $controller->getTimeline($id);

if (!$dados) {
    die("Atendimento não encontrado.");
}

// Busca os motoristas no banco para o Autocompletar
$db = new Database();
$conn = $db->getConnection();
$motoristasAtivos = $conn->query("SELECT nome FROM usuarios WHERE perfil = 'Motorista'")->fetchAll(PDO::FETCH_ASSOC);

// Função Auxiliar para gerar links do Google Maps
function getMapsLink($endereco) {
    if(empty($endereco) || $endereco === 'Não definido') return '#';
    return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($endereco);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGOF - Processo #<?= str_pad($dados['id_atendimento'], 4, '0', STR_PAD_LEFT) ?></title>
    <style>
        :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --accent-orange: #f59e0b; --accent-green: #10b981; --accent-red: #ef4444; --accent-blue: #38bdf8; --border-color: #334155; }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: var(--bg-dark); 
            color: var(--text-main); 
            margin: 0; 
            display: flex; 
            align-items: flex-start; 
            min-height: 100vh;
        }

        .main-content {
            margin-left: 260px; 
            width: calc(100% - 260px);
            padding: 30px; 
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        
        .header-processo { border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 20px; }
        .header-processo h1 { margin: 0; color: var(--accent-orange); }
        .badge { background: rgba(245, 158, 11, 0.2); color: var(--accent-orange); padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        
        .container { display: flex; gap: 30px; }
        
        .col-detalhes { flex: 2; background: var(--bg-card); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); }
        .section-title { font-size: 1rem; color: #94a3b8; border-bottom: 1px solid var(--border-color); padding-bottom: 5px; margin-top: 25px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .info-item label { display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 3px; }
        .info-item span { font-size: 1rem; font-weight: 500; }
        
        .col-timeline { flex: 1; background: var(--bg-card); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); height: fit-content; }
        .timeline-item { border-left: 2px solid var(--accent-green); padding-left: 15px; margin-bottom: 20px; position: relative; }
        .timeline-item::before { content: ''; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--accent-green); }
        .timeline-time { font-size: 0.75rem; color: #94a3b8; }
        .timeline-action { font-weight: bold; margin: 5px 0; font-size: 0.95rem; }
        .timeline-user { font-size: 0.8rem; color: var(--accent-orange); }

        .modal-input { width: 100%; padding: 8px; border-radius: 4px; background: var(--bg-dark); color: white; border: 1px solid var(--border-color); box-sizing: border-box; }
        .modal-label { display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px; margin-top: 15px; }
        .btn-calc { background: rgba(56, 189, 248, 0.1); color: var(--accent-blue); border: 1px solid var(--accent-blue); padding: 8px 12px; border-radius: 4px; cursor: pointer; white-space: nowrap; font-weight: bold; }
        .btn-calc:hover { background: rgba(56, 189, 248, 0.2); }
        
        .action-link { color: var(--accent-blue); text-decoration: none; font-size: 1rem; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
        .action-link:hover { color: white; text-decoration: underline; }
        .action-link.green { color: var(--accent-green); }
        .action-link.green:hover { color: white; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="header-processo">
            <h1>Processo #OS-2026-<?= str_pad($dados['id_atendimento'], 4, '0', STR_PAD_LEFT) ?></h1>
            <div style="margin-top: 10px;">
                <span class="badge"><?= mb_strtoupper($dados['categoria_servico']) ?></span>
                <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: var(--accent-green);"><?= mb_strtoupper($dados['fase_atual']) ?></span>
            </div>
        </div>

        <div class="container">
            <div class="col-detalhes">
                <h2 style="margin-top: 0;"><?= htmlspecialchars($dados['nome_falecido']) ?></h2>
                
                <div class="section-title">Informações do Óbito</div>
                <div class="info-grid">
                    <div class="info-item"><label>CPF</label><span><?= htmlspecialchars($dados['cpf_falecido'] ?? 'Não informado') ?></span></div>
                    <div class="info-item"><label>Origem / Local</label><span><?= htmlspecialchars($dados['local_obito'] ?? 'Não informado') ?></span></div>
                </div>

                <div class="section-title">Contratante / Família</div>
                <div class="info-grid">
                    <div class="info-item"><label>Nome</label><span><?= htmlspecialchars($dados['nome_contratante']) ?></span></div>
                    <div class="info-item"><label>Grau de Parentesco</label><span><?= htmlspecialchars($dados['grau_parentesco']) ?></span></div>
                    <div class="info-item"><label>Contato</label><span><?= htmlspecialchars($dados['contato']) ?></span></div>
                </div>

                <!-- SEÇÃO OPERACIONAL COM LINKS ATIVOS -->
                <div class="section-title">
                    <span>Dados Operacionais & Logística</span>
                    <button onclick="abrirModalEdicao()" style="background: transparent; border: 1px solid #94a3b8; color: #94a3b8; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">
                        ✎ Atualizar Integrações
                    </button>
                </div>
                <div class="info-grid">
                    <div class="info-item"><label>Serviço</label><span><?= htmlspecialchars($dados['tipo_funeral']) ?></span></div>
                    
                    <!-- Link do Motorista (leva pro mapa de frota) -->
                    <div class="info-item">
                        <label>🚚 Motorista Designado</label>
                        <?php if(!empty($dados['nome_motorista'])): ?>
                            <a href="monitoramento_frota.php" class="action-link green" title="Ver frota no mapa">
                                <?= htmlspecialchars($dados['nome_motorista']) ?> ↗
                            </a>
                        <?php else: ?>
                            <span style="color: var(--accent-green);">Aguardando Atribuição</span>
                        <?php endif; ?>
                    </div>

                    <div class="info-item">
                        <label>🌸 Floricultura</label>
                        <?php if(!empty($dados['nome_floricultura'])): ?>
                            <a href="<?= getMapsLink($dados['nome_floricultura']) ?>" target="_blank" class="action-link">
                                <?= htmlspecialchars($dados['nome_floricultura']) ?> ↗
                            </a>
                        <?php else: ?>
                            <span>Não definido</span>
                        <?php endif; ?>
                    </div>

                    <div class="info-item">
                        <label>🔬 Laboratório Destino</label>
                        <?php if(!empty($dados['laboratorio_destino'])): ?>
                            <a href="<?= getMapsLink($dados['laboratorio_destino']) ?>" target="_blank" class="action-link">
                                <?= htmlspecialchars($dados['laboratorio_destino']) ?> ↗
                            </a>
                        <?php else: ?>
                            <span>Não definido</span>
                        <?php endif; ?>
                    </div>

                    <div class="info-item"><label>🕴️ Agente / Cerimonialista</label><span><?= htmlspecialchars($dados['nome_cerimonialista'] ?? 'Não definido') ?></span></div>
                    
                    <div class="info-item">
                        <label>📍 Capela / Velório</label>
                        <?php if(!empty($dados['capela_reservada'])): ?>
                            <a href="<?= getMapsLink($dados['capela_reservada']) ?>" target="_blank" class="action-link">
                                <?= htmlspecialchars($dados['capela_reservada']) ?> ↗
                            </a>
                        <?php else: ?>
                            <span>Não definido</span>
                        <?php endif; ?>
                    </div>

                    <div class="info-item"><label>🕒 Horário do Velório</label><span><?= !empty($dados['horario_velorio']) ? date('d/m/Y H:i', strtotime($dados['horario_velorio'])) : 'Aguardando' ?></span></div>
                    
                    <div class="info-item" style="grid-column: span 2; background: rgba(56, 189, 248, 0.05); padding: 10px; border-radius: 6px; border: 1px dashed var(--accent-blue);">
                        <label style="color: var(--accent-blue);">⏱️ Previsão de Entrega (ETA Cobli)</label>
                        <span style="font-size: 1.1rem; color: white;"><?= htmlspecialchars($dados['previsao_entrega'] ?? 'Destino ou veículo não informados') ?></span>
                    </div>
                </div>

                <div class="section-title" style="color: var(--accent-red); border-color: var(--accent-red); margin-top: 30px;">
                    Controle Administrativo (Override)
                </div>
                <div class="info-grid" style="grid-template-columns: 1fr auto; align-items: end; background: rgba(239, 68, 68, 0.05); padding: 15px; border-radius: 8px; border: 1px dashed var(--accent-red);">
                    <div class="info-item">
                        <label style="color: var(--accent-red);">Mover processo manualmente para:</label>
                        <select id="selectFaseManual" class="modal-input">
                            <option value="Atendimento" <?= $dados['fase_atual'] == 'Atendimento' ? 'selected' : '' ?>>Atendimento</option>
                            <option value="Conferencia" <?= $dados['fase_atual'] == 'Conferencia' ? 'selected' : '' ?>>Conferência</option>
                            <option value="Logistica" <?= $dados['fase_atual'] == 'Logistica' ? 'selected' : '' ?>>Logística</option>
                            <option value="Em Movimento" <?= $dados['fase_atual'] == 'Em Movimento' ? 'selected' : '' ?>>Em Movimento</option>
                            <option value="Laboratorio" <?= $dados['fase_atual'] == 'Laboratorio' ? 'selected' : '' ?>>Laboratório</option>
                            <option value="Ornamentacao" <?= $dados['fase_atual'] == 'Ornamentacao' ? 'selected' : '' ?>>Ornamentação</option>
                            <option value="Capela" <?= $dados['fase_atual'] == 'Capela' ? 'selected' : '' ?>>Capela</option>
                            <option value="Velorio" <?= $dados['fase_atual'] == 'Velorio' ? 'selected' : '' ?>>Velório</option>
                            <option value="Sepultamento" <?= $dados['fase_atual'] == 'Sepultamento' ? 'selected' : '' ?>>Sepultamento / Cremação</option>
                            <option value="Finalizados" <?= $dados['fase_atual'] == 'Finalizados' ? 'selected' : '' ?>>Finalizados</option>
                        </select>
                    </div>
                    <button onclick="alterarStatusManual(<?= $dados['id_atendimento'] ?>)" style="background: var(--accent-red); color: white; padding: 9px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                        Forçar Atualização
                    </button>
                </div>
            </div>

            <div class="col-timeline">
                <h3 style="margin-top: 0; color: #f8fafc; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Linha do Tempo</h3>
                <?php if(empty($timeline)): ?>
                    <p style="font-size: 0.85rem; color: #94a3b8;">Nenhum registro encontrado.</p>
                <?php else: ?>
                    <?php foreach($timeline as $evento): ?>
                        <div class="timeline-item">
                            <div class="timeline-time"><?= date('d/m/Y H:i:s', strtotime($evento['data_hora'])) ?></div>
                            <div class="timeline-action"><?= htmlspecialchars($evento['acao_executada']) ?></div>
                            <div class="timeline-user">Por: <?= htmlspecialchars($evento['nome_usuario'] ?? 'Sistema') ?></div>
                            <?php if($evento['descricao']): ?>
                                <div style="font-size: 0.8rem; margin-top: 5px; color: #94a3b8;"><?= htmlspecialchars($evento['descricao']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- =============================================================== -->
    <!-- MODAL INTEGRADO COM AUTOCOMPLETAR (DATALIST HTML5)            -->
    <!-- =============================================================== -->
    <div id="modalEdicao" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center;">
        <div style="background: var(--bg-card); width: 450px; max-height: 90vh; overflow-y: auto; padding: 25px; border-radius: 12px; border: 1px solid var(--border-color);">
            <h3 style="margin-top: 0; color: var(--accent-orange);">Integrações da Logística</h3>
            
            <!-- Campo Motorista com Autocompletar -->
            <label class="modal-label">Motorista (Equipe Interna)</label>
            <input type="text" id="editMotorista" class="modal-input" placeholder="Digite para buscar..." list="listaMotoristas" value="<?= htmlspecialchars($dados['nome_motorista'] ?? '') ?>">
            <datalist id="listaMotoristas">
                <?php foreach($motoristasAtivos as $mot): ?>
                    <option value="<?= htmlspecialchars($mot['nome']) ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <label class="modal-label">Agente / Cerimonialista (Capela)</label>
            <input type="text" id="editCerimonialista" class="modal-input" placeholder="Nome do responsável..." value="<?= htmlspecialchars($dados['nome_cerimonialista'] ?? '') ?>">

            <!-- Campo Floricultura com Autocompletar -->
            <label class="modal-label">Floricultura (Parceiro)</label>
            <input type="text" id="editFloricultura" class="modal-input" placeholder="Digite ou selecione..." list="listaFloriculturas" value="<?= htmlspecialchars($dados['nome_floricultura'] ?? '') ?>">
            <datalist id="listaFloriculturas">
                <option value="Ornamentaçao Florindo (Caju)"></option>
            </datalist>

            <!-- Campo Laboratório com Autocompletar -->
            <label class="modal-label">Laboratório Destino (Preparação)</label>
            <input type="text" id="editLab" class="modal-input" placeholder="Digite ou selecione..." list="listaLabs" value="<?= htmlspecialchars($dados['laboratorio_destino'] ?? '') ?>">
            <datalist id="listaLabs">
                <option value="Laboratório da Penitencia (Caju)"></option>
                <option value="Laboratório Labtemforj (Inhauma)"></option>
            </datalist>

            <!-- Campo Capela com Autocompletar -->
            <label class="modal-label">Capela Reservada / Sala de Preparo</label>
            <input type="text" id="editCapela" class="modal-input" placeholder="Digite ou selecione..." list="listaCapelas" value="<?= htmlspecialchars($dados['capela_reservada'] ?? '') ?>">
            <datalist id="listaCapelas">
                <option value="São Francisco Xavier (Cemitério) e Sala de Preparo"></option>
                <option value="São Francisco Xavier (Crematório) Premium"></option>
                <option value="Ilha do Governador - Capelas normais e Premium"></option>
                <option value="Santa Cruz"></option>
                <option value="Ricardo de Albuquerque - Capelas normais e Premium e Sala de Preparo"></option>
                <option value="Realengo"></option>
                <option value="Guaratiba"></option>
                <option value="Paqueta"></option>
            </datalist>

            <label class="modal-label">Horário do Velório</label>
            <input type="datetime-local" id="editHorario" class="modal-input" value="<?= !empty($dados['horario_velorio']) ? date('Y-m-d\TH:i', strtotime($dados['horario_velorio'])) : '' ?>">

            <!-- Área de Roteirização ETA Cobli -->
            <label class="modal-label">Previsão de Entrega (ETA)</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="editEta" class="modal-input" placeholder="Aguardando cálculo..." value="<?= htmlspecialchars($dados['previsao_entrega'] ?? '') ?>">
                <button type="button" class="btn-calc" onclick="calcularETACobli(<?= $dados['id_atendimento'] ?>)">📡 Obter da Cobli</button>
            </div>
            <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 5px;">* A Cobli utilizará o endereço do Laboratório ou Capela para calcular o tempo da rota.</div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
                <button onclick="fecharModalEdicao()" style="background: transparent; color: white; border: 1px solid var(--border-color); padding: 8px 15px; border-radius: 4px; cursor: pointer;">Cancelar</button>
                <button onclick="salvarEdicao(<?= $dados['id_atendimento'] ?>)" style="background: var(--accent-orange); color: black; font-weight: bold; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Salvar e Registrar</button>
            </div>
        </div>
    </div>

    <!-- Scripts de Interação -->
    <script>
    function alterarStatusManual(idAtendimento) {
        const novaFase = document.getElementById('selectFaseManual').value;
        if(!confirm(`Mover a OS #${idAtendimento} para a fase: ${novaFase.toUpperCase()}?`)) return;

        fetch('../app/Api/alterar_status_manual.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_atendimento: idAtendimento, nova_fase: novaFase })
        }).then(r => r.json()).then(d => d.status === 'success' ? window.location.reload() : alert('Erro: ' + d.message)).catch(console.error);
    }

    function abrirModalEdicao() { document.getElementById('modalEdicao').style.display = 'flex'; }
    function fecharModalEdicao() { document.getElementById('modalEdicao').style.display = 'none'; }

    function calcularETACobli(idAtendimento) {
        const destino = document.getElementById('editLab').value || document.getElementById('editCapela').value;
        const motorista = document.getElementById('editMotorista').value;
        
        if(!destino || !motorista) {
            alert("Para calcular o ETA, preencha o Motorista e o endereço do Laboratório ou Capela!");
            return;
        }

        document.getElementById('editEta').value = "Calculando rota...";

        fetch('../app/Api/calcular_eta_cobli.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ motorista: motorista, destino: destino })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('editEta').value = data.eta;
            } else {
                alert("Não foi possível calcular: " + data.message);
                document.getElementById('editEta').value = "Erro no cálculo";
            }
        })
        .catch(err => {
            alert('Falha na comunicação com o servidor Cobli.');
            document.getElementById('editEta').value = "";
        });
    }

    function salvarEdicao(idAtendimento) {
        const payload = {
            id_atendimento: idAtendimento,
            nome_motorista: document.getElementById('editMotorista').value,
            nome_cerimonialista: document.getElementById('editCerimonialista').value,
            nome_floricultura: document.getElementById('editFloricultura').value,
            laboratorio_destino: document.getElementById('editLab').value,
            capela_reservada: document.getElementById('editCapela').value,
            horario_velorio: document.getElementById('editHorario').value,
            previsao_entrega: document.getElementById('editEta').value
        };

        fetch('../app/Api/editar_dados.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => data.status === 'success' ? window.location.reload() : alert('Erro: ' + data.message))
        .catch(error => alert('Falha de comunicação com o servidor.'));
    }
    </script>
</body>
</html>