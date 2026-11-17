<!-- /views/integracoes.php -->
<?php
session_start();
require_once '../config/database.php';

// Busca o status atual da integração no banco
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT status, ultima_sincronizacao FROM integracoes_config WHERE nome_servico = 'Cobli' LIMIT 1");
$cobliConfig = $stmt->fetch();

$status = $cobliConfig['status'] ?? 'Inativo';
$ultimaSync = $cobliConfig['ultima_sincronizacao'] ? date('d/m/Y H:i', strtotime($cobliConfig['ultima_sincronizacao'])) : 'Nunca sincronizado';
$isConectado = ($status === 'Ativo');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGOF - Integrações</title>
    <style>
        :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --accent-orange: #f59e0b; --accent-green: #10b981; --accent-red: #ef4444; --border-color: #334155; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-dark); color: var(--text-main); margin: 0; display: flex; align-items: flex-start; min-height: 100vh; }
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 30px; box-sizing: border-box; display: flex; flex-direction: column; }
        
        .page-header { border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 30px; }
        .page-header h1 { margin: 0; color: var(--accent-orange); }

        /* Card de Integração */
        .integration-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; max-width: 600px; }
        .integration-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px dashed var(--border-color); padding-bottom: 15px; }
        .integration-header h2 { margin: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 10px; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .status-on { background: rgba(16, 185, 129, 0.2); color: var(--accent-green); border: 1px solid var(--accent-green); }
        .status-off { background: rgba(239, 68, 68, 0.2); color: var(--accent-red); border: 1px solid var(--accent-red); }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-dark); color: white; box-sizing: border-box; font-family: monospace; }
        
        .info-text { font-size: 0.8rem; color: #94a3b8; margin-bottom: 20px; }
        
        .actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.9rem; transition: 0.2s; }
        .btn-primary { background: var(--accent-orange); color: black; }
        .btn-secondary { background: transparent; color: white; border: 1px solid var(--border-color); }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Configurações de Integração</h1>
            <p style="color: #94a3b8; margin-top: 5px;">Gerencie as conexões do SGOF com plataformas externas.</p>
        </div>

        <div class="integration-card">
            <div class="integration-header">
                <h2>🛰️ Rastreamento Cobli</h2>
                <span class="status-badge <?= $isConectado ? 'status-on' : 'status-off' ?>">
                    <?= $isConectado ? '🟢 Conectado' : '🔴 Desconectado' ?>
                </span>
            </div>

            <div class="form-group">
                <label>Chave de Acesso (API Key)</label>
                <!-- O input fica vazio propositalmente ao carregar para não expor a chave salva -->
                <input type="password" id="apiKey" placeholder="<?= $isConectado ? '******** (Chave salva e criptografada)' : 'Cole sua chave da Cobli aqui...' ?>">
            </div>

            <div class="info-text">
                Última sincronização de frota: <strong><?= $ultimaSync ?></strong>
            </div>

            <div class="actions">
                <button class="btn btn-primary" onclick="salvarConfiguracao()">Salvar Chave</button>
                <button class="btn btn-secondary" onclick="testarConexao()">Testar Conexão</button>
                <?php if($isConectado): ?>
                    <button class="btn btn-secondary" style="border-color: var(--accent-green); color: var(--accent-green);" onclick="sincronizarFrota()">Sincronizar Veículos</button>
                    <!-- NOVO BOTÃO DE MOTORISTAS -->
                    <button class="btn btn-secondary" style="border-color: #38bdf8; color: #38bdf8;" onclick="sincronizarMotoristas()">Sincronizar Motoristas</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function salvarConfiguracao() {
            const key = document.getElementById('apiKey').value;
            if(!key) { alert("Insira uma chave válida."); return; }

            fetch('../app/Api/configurar_cobli.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'salvar', api_key: key })
            })
            .then(async res => {
                const textoPuro = await res.text(); // Pega a resposta como texto puro primeiro
                console.log("Resposta crua do PHP:", textoPuro); // Mostra o erro real no F12 Console
                try {
                    return JSON.parse(textoPuro);
                } catch (e) {
                    throw new Error("O PHP gerou um erro HTML:\n\n" + textoPuro);
                }
            })
            .then(data => { 
                alert(data.message); 
                if(data.status === 'success') window.location.reload(); 
            })
            .catch(err => {
                console.error(err);
                alert("Erro capturado! Veja o Console (F12) para ler o erro HTML do PHP.");
            });
        }

        function testarConexao() {
            fetch('../app/Api/configurar_cobli.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'testar' })
            })
            .then(res => res.json())
            .then(data => { alert(data.message); });
        }

        function sincronizarFrota() {
            if(!confirm("Deseja importar a lista atualizada de veículos da Cobli para o sistema?")) return;
            
            fetch('../app/Api/sincronizar_frota.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => { alert(data.message); window.location.reload(); });
        }

        function sincronizarMotoristas() {
            if(!confirm("Deseja importar a lista atualizada de motoristas da Cobli?")) return;
            
            fetch('../app/Api/sincronizar_motoristas.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => { 
                alert(data.message); 
                if(data.status === 'success') window.location.reload(); 
            })
            .catch(err => {
                console.error(err);
                alert("Erro ao sincronizar motoristas.");
            });
        }
    </script>
</body>
</html>