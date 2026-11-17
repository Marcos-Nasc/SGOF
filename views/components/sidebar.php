<!-- /views/components/sidebar.php -->
<style>
    /* Estilos exclusivos da Sidebar */
    .sidebar {
        width: 260px;
        background-color: #0b1120;
        border-right: 1px solid #334155;
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
    }

    .sidebar-brand {
        padding: 25px 20px;
        border-bottom: 1px solid #1e293b;
    }

    .sidebar-brand h2 {
        color: #f8fafc;
        margin: 0;
        font-size: 1.5rem;
        letter-spacing: 1px;
    }

    .sidebar-brand span {
        color: #f59e0b; /* Laranja da Logística */
    }

    .sidebar-menu {
        padding: 20px 0;
        flex-grow: 1;
        overflow-y: auto;
    }

    .menu-title {
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 0 25px;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0 0 25px 0;
    }

    .sidebar-nav li a {
        display: flex;
        align-items: center;
        padding: 12px 25px;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 0.95rem;
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }

    .sidebar-nav li a:hover {
        background-color: #1e293b;
        color: #f59e0b;
        border-left-color: #f59e0b;
    }

    .sidebar-nav li a.active {
        background-color: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border-left-color: #f59e0b;
        font-weight: 500;
    }

    .sidebar-user {
        padding: 20px 25px;
        border-top: 1px solid #1e293b;
        background-color: #0f172a;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .avatar {
        width: 40px;
        height: 40px;
        background-color: #3b82f6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }

    .user-info {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        color: #f8fafc;
        font-size: 0.9rem;
        font-weight: bold;
    }

    .user-role {
        color: #94a3b8;
        font-size: 0.75rem;
    }

    /* Ajuste para o conteúdo principal não ficar escondido atrás da sidebar */
    .main-content {
        margin-left: 260px; /* Mesma largura da sidebar */
        width: calc(100% - 260px);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
</style>

<aside class="sidebar">
    <div class="sidebar-brand">
        <h2>SGOF<span>.</span></h2>
        <div style="color: #94a3b8; font-size: 0.8rem; margin-top: 5px;">Gestão Operacional</div>
    </div>

    <div class="sidebar-menu">
        <div class="menu-title">Visão Geral</div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php">Dashboard Gerencial</a></li>
        </ul>

        <div class="menu-title">Operação Logística</div>
        <ul class="sidebar-nav">
            <li><a href="novo_atendimento.php">⊕ Novo Atendimento</a></li>
            <li><a href="central_logistica.php">Central de Cards</a></li>
            <li><a href="kanban.php">Kanban Operacional</a></li>
            <li><a href="agenda_inteligente.php">Agenda Inteligente</a></li>
        </ul>

        <div class="menu-title">Monitoramento</div>
        <ul class="sidebar-nav">
            <!-- Target _blank para abrir a TV em outra aba/monitor -->
            <li><a href="sala_operacoes.php" target="_blank">Sala de Operações (TV) ⧉</a></li>
        </ul>
    </div>

    <div class="sidebar-user">
        <div class="avatar">MP</div>
        <div class="user-info">
            <!-- Utilizando dados de sessão caso existam, ou um fallback visual -->
            <span class="user-name"><?= $_SESSION['nome_usuario'] ?? 'Marcos Paulo' ?></span>
            <span class="user-role"><?= $_SESSION['perfil_usuario'] ?? 'Logística Master' ?></span>
        </div>
    </div>
</aside>