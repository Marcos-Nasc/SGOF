<!-- /views/novo_atendimento.php -->
<?php
session_start();
require_once '../config/database.php';

// 1. Busca motoristas do banco
$db = new Database();
$conn = $db->getConnection();
$motoristasAtivos = $conn->query("SELECT nome FROM usuarios WHERE perfil = 'Motorista'")->fetchAll(PDO::FETCH_ASSOC);

// 2. Listas Oficiais Padronizadas
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGOF - Novo Atendimento</title>
    <style>
        :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --accent-orange: #f59e0b; --border-color: #334155; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-dark); color: var(--text-main); margin: 0; display: flex; }
        
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 30px; box-sizing: border-box; }
        .form-container { background: var(--bg-card); padding: 30px; border-radius: 12px; max-width: 800px; margin: 0 auto; border: 1px solid var(--border-color); }
        .form-section { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color); }
        .form-section h3 { color: var(--accent-orange); margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 0.85rem; margin-bottom: 5px; color: #94a3b8; }
        input, select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-dark); color: white; box-sizing: border-box; }
        .btn-submit { background: var(--accent-orange); color: black; font-weight: bold; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-size: 1rem; margin-top: 10px; }
        .btn-submit:hover { opacity: 0.9; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="form-container">
            <h2 style="margin-top: 0; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">Abertura de Novo Atendimento</h2>
            
            <form action="../app/Api/salvar_atendimento.php" method="POST">
                
                <!-- Dados do Falecido -->
                <div class="form-section">
                    <h3>Dados do Falecido</h3>
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" name="falecido_nome" required>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>CPF</label>
                            <input type="text" name="falecido_cpf">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Local do Óbito (Hospital/Residência)</label>
                            <input type="text" name="local_obito">
                        </div>
                    </div>
                </div>

                <!-- Dados do Contratante -->
                <div class="form-section">
                    <h3>Dados do Contratante</h3>
                    <div class="form-group">
                        <label>Nome Completo (Responsável)</label>
                        <input type="text" name="contratante_nome" required>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Telefone / Contato</label>
                            <input type="text" name="contratante_contato" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Grau de Parentesco</label>
                            <input type="text" name="grau_parentesco">
                        </div>
                    </div>
                </div>

                <!-- Dados do Serviço -->
                <div class="form-section">
                    <h3>Dados Estruturais do Serviço</h3>
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Categoria</label>
                            <select name="categoria_servico" required>
                                <option value="Particular">Particular</option>
                                <option value="Plano">Plano Familiar</option>
                                <option value="Prefeitura">Demanda da Prefeitura</option>
                                <option value="Gratuidade">Gratuidade</option>
                                <option value="Doacao">Doação</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Tipo de Funeral</label>
                            <select name="tipo_funeral" required>
                                <option value="Sepultamento">Sepultamento</option>
                                <option value="Cremação">Cremação</option>
                                <option value="Translado">Translado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- NOVO BLOCO: Roteirização e Integrações -->
                <div class="form-section" style="background: rgba(15, 23, 42, 0.4); padding: 15px; border-radius: 8px;">
                    <h3>Alocação Logística Inicial (Opcional)</h3>
                    <p style="font-size: 0.8rem; color: #94a3b8; margin-top: -10px; margin-bottom: 15px;">Deixe em branco se a base de logística for definir posteriormente.</p>
                    
                    <div style="display: flex; gap: 15px;">
                        <!-- Motorista (Autocompletar Inteligente) -->
                        <div class="form-group" style="flex: 1;">
                            <label>🚚 Motorista / Veículo</label>
                            <input type="text" name="nome_motorista" placeholder="Digite o nome..." list="listaMotoristas">
                            <datalist id="listaMotoristas">
                                <?php foreach($motoristasAtivos as $mot): ?>
                                    <option value="<?= htmlspecialchars($mot['nome']) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <!-- Floricultura (Select) -->
                        <div class="form-group" style="flex: 1;">
                            <label>🌸 Floricultura</label>
                            <select name="nome_floricultura">
                                <option value="">Não definido</option>
                                <?php foreach($floriculturasOficiais as $flor): ?>
                                    <option value="<?= $flor ?>"><?= $flor ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <!-- Laboratório (Select) -->
                        <div class="form-group" style="flex: 1;">
                            <label>🔬 Laboratório Destino</label>
                            <select name="laboratorio_destino">
                                <option value="">Não definido</option>
                                <?php foreach($labsOficiais as $lab): ?>
                                    <option value="<?= $lab ?>"><?= $lab ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Agente Cerimonial (Texto Livre) -->
                        <div class="form-group" style="flex: 1;">
                            <label>🕴️ Agente / Cerimonialista</label>
                            <input type="text" name="nome_cerimonialista" placeholder="Nome do responsável">
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <!-- Capela (Select) -->
                        <div class="form-group" style="flex: 2;">
                            <label>📍 Capela Reservada</label>
                            <select name="capela_reservada">
                                <option value="">Não definido</option>
                                <?php foreach($capelasOficiais as $cap): ?>
                                    <option value="<?= $cap ?>"><?= $cap ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Horário do Velório -->
                        <div class="form-group" style="flex: 1;">
                            <label>🕒 Horário do Velório</label>
                            <input type="datetime-local" name="horario_velorio">
                        </div>
                    </div>
                </div>

                <!-- Demandas Terceirizadas e Extras (Oculto ou Mesclado se necessário) -->
                <div class="form-section">
                    <h3>Extras e Despachante</h3>
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Coroa de Flores</label>
                            <select name="coroa_flores">
                                <option value="Não">Não</option>
                                <option value="Sim (Pequena)">Sim (Pequena)</option>
                                <option value="Sim (Média)">Sim (Média)</option>
                                <option value="Sim (Grande)">Sim (Grande)</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Necessita de Despachante?</label>
                            <select name="necessita_despachante">
                                <option value="Não">Não (Família providenciará)</option>
                                <option value="Sim">Sim (Acionar parceiro)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Abrir Processo e Enviar para Logística</button>
            </form>
        </div>
    </div>

</body>
</html>