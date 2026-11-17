<!-- /views/painel_motorista.php -->
<?php
session_start();
require_once '../app/Controllers/MotoristaController.php';

// Simulação de segurança: garante que o usuário logado é um motorista
$id_usuario = $_SESSION['id_usuario'] ?? 2; // ID de teste para Motorista
$nome_motorista = $_SESSION['nome_usuario'] ?? 'Roberto Fonseca';

$controller = new MotoristaController();
$corridas = $controller->getMinhasCorridas($id_usuario);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <!-- Viewport crucial para funcionar perfeitamente em celulares -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SGOF - Motorista</title>
    <style>
        :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --accent-orange: #f59e0b; --accent-green: #10b981; --border-color: #334155; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-dark); color: var(--text-main); margin: 0; padding: 0; padding-bottom: 30px; }
        
        .topbar { background: #0b1120; padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .topbar h2 { margin: 0; color: var(--accent-orange); font-size: 1.2rem; }
        .topbar-user { font-size: 0.85rem; color: #94a3b8; }

        .container-mobile { padding: 20px; }
        .page-title { margin-top: 0; margin-bottom: 20px; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }

        /* Mobile Card */
        .corrida-card { background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .corrida-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .os-tag { background: #334155; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .fase-tag { color: var(--accent-orange); font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        
        .falecido-nome { font-size: 1.2rem; margin: 0 0 5px 0; font-weight: bold; }
        .servico-tipo { font-size: 0.9rem; color: #94a3b8; margin-bottom: 15px; }

        .rota-box { background: rgba(15, 23, 42, 0.5); padding: 15px; border-radius: 8px; border-left: 3px solid #3b82f6; margin-bottom: 20px; }
        .rota-item { margin-bottom: 10px; }
        .rota-item:last-child { margin-bottom: 0; }
        .rota-label { font-size: 0.75rem; color: #94a3b8; display: block; text-transform: uppercase; }
        .rota-valor { font-size: 0.95rem; font-weight: 500; }

        /* Botão de Ação Gigante para o Motorista */
        .btn-acao { background: var(--accent-green); color: white; width: 100%; border: none; padding: 15px; border-radius: 8px; font-size: 1rem; font-weight: bold; text-transform: uppercase; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-acao:active { opacity: 0.8; transform: scale(0.98); }
    </style>
</head>
<body>

    <div class="topbar">
        <h2>SGOF<span>.</span></h2>
        <div class="topbar-user">👤 <?= htmlspecialchars($nome_motorista) ?></div>
    </div>

    <div class="container-mobile">
        <h3 class="page-title">Minhas Corridas Ativas</h3>

        <?php if(empty($corridas)): ?>
            <div style="text-align: center; color: #94a3b8; padding: 40px 0;">
                <p>Nenhum serviço atribuído a você no momento.</p>
                <p style="font-size: 2rem;">☕</p>
            </div>
        <?php else: ?>
            <?php foreach($corridas as $corrida): ?>
                <div class="corrida-card">
                    <div class="corrida-header">
                        <span class="os-tag">#OS-<?= str_pad($corrida['id_atendimento'], 4, '0', STR_PAD_LEFT) ?></span>
                        <span class="fase-tag"><?= htmlspecialchars($corrida['fase_atual']) ?></span>
                    </div>

                    <h4 class="falecido-nome"><?= htmlspecialchars($corrida['nome_falecido']) ?></h4>
                    <div class="servico-tipo"><?= htmlspecialchars($corrida['tipo_funeral']) ?></div>

                    <div class="rota-box">
                        <div class="rota-item">
                            <span class="rota-label">Origem (Coleta)</span>
                            <span class="rota-valor"><?= htmlspecialchars($corrida['local_obito'] ?? 'Verificar com Logística') ?></span>
                        </div>
                        <div class="rota-item" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border-color);">
                            <span class="rota-label">Destino (Laboratório / Capela)</span>
                            <span class="rota-valor"><?= htmlspecialchars($corrida['laboratorio_destino'] ?? $corrida['capela_reservada'] ?? 'A definir') ?></span>
                        </div>
                    </div>

                    <!-- Botão de Atualização que enviará o log para a Logística -->
                    <button class="btn-acao" onclick="registrarCheckin(<?= $corrida['id_atendimento'] ?>)">
                        📍 Informar Chegada no Local
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function registrarCheckin(idAtendimento) {
            if(confirm("Confirmar chegada ao local? A logística será notificada.")) {
                // Aqui faremos a chamada AJAX para registrar o check-in na Timeline
                alert("Check-in registrado com sucesso para OS #" + idAtendimento + "!");
                // window.location.reload();
            }
        }
    </script>
</body>
</html>