<?php
// /app/Controllers/AuthController.php
session_start();
require_once '../../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        die("Por favor, preencha todos os campos.");
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Busca o usuário pelo e-mail
    $stmt = $conn->prepare("SELECT id_usuario, nome, email, senha, perfil FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    // Em um sistema real, usaríamos password_verify(). Para simplificar o teste, vamos usar comparação direta ou um hash simples.
    // if ($usuario && password_verify($senha, $usuario['senha'])) {
    if ($usuario && $senha === $usuario['senha']) { // Simplificado para ambiente de desenvolvimento
        
        // 1. Cria a sessão do usuário
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nome_usuario'] = $usuario['nome'];
        $_SESSION['perfil_usuario'] = $usuario['perfil'];

        // 2. Roteamento Inteligente baseado no Perfil
        switch (strtolower($usuario['perfil'])) {
            case 'administrador':
            case 'supervisor':
            case 'logistica':
                header("Location: ../../views/dashboard.php");
                break;
            
            case 'motorista':
                header("Location: ../../views/painel_motorista.php");
                break;
            
            case 'floricultura':
            case 'laboratorio':
            case 'despachante':
                header("Location: ../../views/painel_parceiro.php");
                break;
                
            default:
                // Fallback de segurança
                header("Location: ../../views/login.php?erro=perfil_desconhecido");
                break;
        }
        exit;
    } else {
        // Redireciona de volta para o login com erro
        header("Location: ../../views/login.php?erro=credenciais_invalidas");
        exit;
    }
} else {
    // Acesso direto ao arquivo sem POST
    header("Location: ../../views/login.php");
    exit;
}
?>