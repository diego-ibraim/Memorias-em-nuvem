<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'email_service.php';

$mensagem = '';
$erro = '';

// Se o método da requisição for POST, tenta processar a solicitação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Formato de e-mail inválido.";
    } else {
        try {
            $auth = new Auth($pdo);
            $result = $auth->generatePasswordResetToken($email);

            // Se um usuário com o e-mail for encontrado, envia o e-mail
            if ($result) {
                $reset_link = rtrim(SITE_URL, '/') . '/redefinir_senha.php?token=' . $result['token'];
                $subject = 'Redefinição de Senha - Memórias em Nuvem';
                $content = '<p>Olá, ' . htmlspecialchars($result['name']) . '!</p>'
                         . '<p>Recebemos uma solicitação para redefinir a senha da sua conta. Se não foi você, por favor, ignore este e-mail.</p>'
                         . '<p>Para criar uma nova senha, clique no botão abaixo. Este link expirará em 1 hora.</p>'
                         . '<p style="text-align:center; margin: 25px 0;">'
                         . '<a href="' . $reset_link . '" class="button">Redefinir Senha Agora</a>'
                         . '</p>'
                         . '<p>Se o botão não funcionar, copie e cole o seguinte link no seu navegador:</p>'
                         . '<p><a href="' . $reset_link . '">' . $reset_link . '</a></p>';

                $fullHtmlEmail = createEmailTemplate('Redefinição de Senha', $content);
                sendEmail($result['email'], $result['name'], $subject, $fullHtmlEmail);
            }

            // Exibe uma mensagem genérica para evitar a enumeração de e-mails
            $mensagem = 'Se houver uma conta associada a este e-mail, um link de redefinição de senha foi enviado. Por favor, verifique sua caixa de entrada e a pasta de spam.';

        } catch (Exception $e) {
            error_log("Erro em esqueci_senha.php: " . $e->getMessage());
            $erro = "Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha | Memórias em Nuvem</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .logo h1 { font-family: 'Josefin Sans', sans-serif; color: var(--cor-titulo); font-size: 2rem; margin:0 0 10px 0; }
        .logo p { font-size: 14px; opacity: 0.8; margin-bottom: 25px;}
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
        .btn:hover { background-color: var(--cor-titulo-hover); transform: translateY(-2px); }
        .links { margin-top: 25px; font-size: 14px; }
        .links a { color: var(--cor-titulo); text-decoration: none; font-weight: 500; }
        .links a:hover { text-decoration: underline; }
        .message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; margin-bottom: 20px; padding: 10px; border-radius: 8px; font-size: 14px; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; margin-bottom: 20px; padding: 10px; border-radius: 8px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Recuperar Senha</h1>
            <p>Insira seu e-mail para receber as instruções.</p>
        </div>

        <?php if ($mensagem): ?><div class="message"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="error"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

        <?php if (!$mensagem): // Oculta o formulário após o envio ?>
        <form method="POST" action="esqueci_senha.php">
            <div class="form-group">
                <label for="email">Seu E-mail</label>
                <input type="email" id="email" name="email" placeholder="seuemail@exemplo.com" required>
            </div>
            <button type="submit" class="btn">Enviar Link de Recuperação</button>
        </form>
        <?php endif; ?>

        <div class="links">
            <a href="login.php">Voltar para o Login</a>
        </div>
    </div>
</body>
</html>