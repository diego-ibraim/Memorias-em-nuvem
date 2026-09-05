<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'auth.php';

// Apenas usuários logados podem acessar esta página
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
    exit();
}

$auth = new Auth($pdo);
$user = $auth->getUserById($_SESSION['user_id']);

// Se o usuário já tem CPF ou não é um usuário Google, redireciona para a home
if (!is_null($user['cpf']) || is_null($user['google_id'])) {
    redirect('home.php');
    exit();
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cpf'])) {
    try {
        $cpf = sanitizeInput($_POST['cpf']);
        if ($auth->updateCpf($_SESSION['user_id'], $cpf)) {
            // Sucesso! Redireciona para a página inicial.
            redirect('home.php');
            exit();
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete seu Cadastro - Memórias em Nuvem</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --cor-principal: #cef1ff; --cor-acento-2: #ffded1; --cor-texto: #A0522D;
            --cor-titulo: #A0522D; --cor-titulo-hover: #8C4624; --sombra-cor: rgba(149, 173, 194, 0.2);
            --borda-arredondada: 15px;
        }
        html { background: linear-gradient(-45deg, var(--cor-principal), var(--cor-acento-2)); background-attachment: fixed; }
        body { font-family: 'Poppins', sans-serif; color: var(--cor-texto); display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container {
            background-color: rgba(255, 255, 255, 0.65); backdrop-filter: blur(12px); border-radius: var(--borda-arredondada);
            box-shadow: 0 8px 32px 0 var(--sombra-cor); border: 1px solid rgba(255, 255, 255, 0.25);
            width: 100%; max-width: 450px; padding: 40px; text-align: center;
        }
        h1 { font-family: 'Josefin Sans', sans-serif; color: var(--cor-titulo); font-size: 2rem; margin:0 0 10px 0; }
        p { font-size: 1rem; opacity: 0.9; margin-bottom: 25px; line-height: 1.6;}
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input {
            width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 10px;
            font-size: 1rem; background-color: rgba(255, 255, 255, 0.5); color: var(--cor-texto);
            transition: all 0.3s ease; box-sizing: border-box;
        }
        .form-group input:focus { outline: none; border-color: var(--cor-titulo); background-color: #fff; box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2); }
        .btn {
            background-color: var(--cor-titulo); color: white; border: none; padding: 12px 20px;
            border-radius: 10px; width: 100%; font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: all 0.3s ease; margin-top: 10px; box-sizing: border-box;
        }
        .btn:hover { background-color: var(--cor-titulo-hover); transform: translateY(-2px); }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; margin-bottom: 20px; padding: 10px; border-radius: 8px; font-size: 14px; }
        .logout-link { margin-top: 20px; font-size: 0.9rem;}
        .logout-link a { color: var(--cor-titulo); }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quase lá, <?= htmlspecialchars($user['usuario']) ?>!</h1>
        <p>Para continuar e ter acesso à plataforma, por favor, informe seu CPF. Este passo é obrigatório.</p>

        <?php if ($errorMessage): ?><div class="error"><?php echo htmlspecialchars($errorMessage); ?></div><?php endif; ?>

        <form method="POST" action="completar-cadastro.php">
            <div class="form-group">
                <label for="cpf">Seu CPF</label>
                <input type="text" id="cpf" name="cpf" placeholder="123.456.789-00" required>
            </div>
            <button type="submit" name="add_cpf" class="btn">Salvar e Continuar</button>
        </form>

        <div class="logout-link">
            <a href="logout.php">Sair e voltar para o login</a>
        </div>
    </div>
    <script>
        document.getElementById('cpf').addEventListener('input', function(e) {let value = e.target.value.replace(/\D/g, '');if (value.length > 11) value = value.slice(0, 11);if (value.length > 9) {e.target.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');} else if (value.length > 6) {e.target.value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3');} else if (value.length > 3) {e.target.value = value.replace(/(\d{3})(\d{3})/, '$1.$2');} else {e.target.value = value;}});
    </script>
</body>
</html>