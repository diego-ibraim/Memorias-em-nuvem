<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

$token = $_GET['token'] ?? null;
$auth = new Auth($pdo);
$erro = '';
$mensagem = '';
$token_valido = false;

// Verifica se o token existe e é válido
if (!$token) {
    $erro = "Token não fornecido. Utilize o link enviado para seu e-mail.";
} else {
    try {
        if ($auth->verifyPasswordResetToken($token)) {
            $token_valido = true;
        } else {
            $erro = "Este token é inválido ou já expirou. Por favor, solicite uma nova redefinição de senha.";
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Se o formulário for enviado e o token for válido, tenta redefinir a senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $nova_senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];

    if ($nova_senha !== $confirma_senha) {
        $erro = "As senhas não coincidem.";
    } elseif (strlen($nova_senha) < 8) {
        $erro = "A nova senha deve ter pelo menos 8 caracteres.";
    } else {
        try {
            if ($auth->resetPassword($token, $nova_senha)) {
                $mensagem = "Sua senha foi redefinida com sucesso! Agora você pode fazer o login com a nova senha.";
                $token_valido = false; // Oculta o formulário após o sucesso
            } else {
                $erro = "Ocorreu um erro ao redefinir sua senha. Tente novamente.";
            }
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha | Memórias em Nuvem</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/2.png">
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
            width: 100%; max-width: 420px; padding: 40px; text-align: center;
        }
        h1 { font-family: 'Josefin Sans', sans-serif; color: var(--cor-titulo); font-size: 2rem; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input {
            width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 10px;
            font-size: 14px; background-color: rgba(255, 255, 255, 0.5); color: var(--cor-texto);
            transition: all 0.3s ease; box-sizing: border-box;
        }
        .form-group input:focus { outline: none; border-color: var(--cor-titulo); background-color: #fff; box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2); }
        .btn {
            background-color: var(--cor-titulo); color: white; border: none; padding: 12px 20px;
            border-radius: 10px; width: 100%; font-size: 14px; font-weight: 600; cursor: pointer;
            transition: all 0.3s ease; margin-top: 10px; box-sizing: border-box;
        }
        .links { margin-top: 25px; font-size: 14px; }
        .links a { color: var(--cor-titulo); text-decoration: none; font-weight: 500; }
        .message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; margin-bottom: 20px; padding: 15px; border-radius: 8px; font-size: 14px; line-height: 1.5; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; margin-bottom: 20px; padding: 15px; border-radius: 8px; font-size: 14px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Crie uma Nova Senha</h1>

        <?php if ($mensagem): ?>
            <div class="message"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if ($token_valido): ?>
        <form method="POST" action="redefinir_senha.php?token=<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="senha">Nova Senha</label>
                <input type="password" id="senha" name="senha" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirma_senha">Confirme a Nova Senha</label>
                <input type="password" id="confirma_senha" name="confirma_senha" required>
            </div>
            <button type="submit" class="btn">Redefinir Senha</button>
        </form>
        <?php endif; ?>

        <div class="links">
            <a href="login.php">Ir para o Login</a>
        </div>
    </div>
</body>
</html>