<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

// Bloco de código para o Google Login
$google_client = new Google_Client();
$google_client->setClientId(GOOGLE_CLIENT_ID);
$google_client->setClientSecret(GOOGLE_CLIENT_SECRET);
$google_client->setRedirectUri(GOOGLE_REDIRECT_URI);
$google_client->addScope("email");
$google_client->addScope("profile");
$google_login_url = $google_client->createAuthUrl();

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if ($cpf === '' || $senha === '') {
        $erro = "Por favor, digite o CPF e a senha antes de enviar.";
    } else {
        try {
            $auth = new Auth($pdo);
            if ($auth->login($cpf, $senha)) {
                header("Location: home.php");
                exit();
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
    <title>Login | Memórias em Nuvem</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&family=Roboto:wght@500&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/3.png">
    <style>
        :root {
            --cor-principal: #cef1ff;
            --cor-acento-2: #ffded1;
            --cor-texto: #A0522D;
            --cor-titulo: #A0522D;
            --cor-titulo-hover: #8C4624;
            --sombra-cor: rgba(149, 173, 194, 0.2);
            --borda-arredondada: 15px;
        }
        html {
            background: linear-gradient(-45deg, var(--cor-principal), var(--cor-acento-2));
            background-attachment: fixed;
        }
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--cor-texto);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: var(--borda-arredondada);
            box-shadow: 0 8px 32px 0 var(--sombra-cor);
            border: 1px solid rgba(255, 255, 255, 0.25);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            text-align: center;
        }
        .logo { margin-bottom: 30px; }
        .logo img { width: 140px; height: auto; margin-bottom: 10px; border-radius:30px;}
        .logo h1 { font-family: 'Josefin Sans', sans-serif; color: var(--cor-titulo); font-size: 2rem; margin-bottom: 5px; }
        .logo p { font-size: 14px; opacity: 0.8; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input {
            width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px;
            background-color: rgba(255, 255, 255, 0.5); color: var(--cor-texto);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus { outline: none; border-color: var(--cor-titulo); background-color: #fff; box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2); }
        .btn {
            background-color: var(--cor-titulo); color: white; border: 1px solid var(--cor-titulo); padding: 12px 20px;
            border-radius: 10px; width: 100%; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            margin-top: 10px; box-sizing: border-box; text-decoration: none; display: flex; align-items: center; justify-content: center;
        }
        .btn:hover { background-color: var(--cor-titulo-hover); border-color: var(--cor-titulo-hover); transform: translateY(-2px); }
        .links { margin-top: 25px; font-size: 14px; }
        .links a { color: var(--cor-titulo); text-decoration: none; font-weight: 500; }
        .links a:hover { text-decoration: underline; }
        .error { color: #721c24; margin-bottom: 20px; font-size: 14px; background-color: #f8d7da; padding: 10px; border-radius: 8px; border: 1px solid #f5c6cb; }
        .footer { margin-top: 30px; font-size: 12px; opacity: 0.7; }
        .or-separator { display: flex; align-items: center; text-align: center; margin: 20px 0; color: #aaa; }
        .or-separator::before, .or-separator::after { content: ''; flex: 1; border-bottom: 1px solid #ddd; }
        .or-separator:not(:empty)::before { margin-right: .25em; }
        .or-separator:not(:empty)::after { margin-left: .25em; }
        .google-btn {
            background-color: #ffffff;
            color: #3c4043;
            border: 1px solid #d2e3fc;
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
            margin-top: 0;
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
        }
        .google-btn:hover {
            background-color: #f8f9fa;
            border-color: #c6dafc;
            box-shadow: 0 1px 3px 0 rgba(60,64,67,0.3), 0 2px 6px 2px rgba(60,64,67,0.15);
        }
        .google-btn img {
            margin-right: 12px;
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="img/2.png" alt="Memórias em Nuvem">
            <h1>Memórias em Nuvem</h1>
            <p>Viva o presente e preserve seu legado</p>
        </div>

        <?php if (isset($erro)): ?>
            <div class="error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" placeholder="123.456.789-00" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Sua senha" required>
            </div>
            <button type="submit" class="btn">Entrar</button>
        </form>

        <div class="or-separator">ou</div>

        <a href="<?= htmlspecialchars($google_login_url) ?>" class="btn google-btn">
            <img src="img/google-logo.png" alt="Logo do Google">
            <span>Entrar com o Google</span>
        </a>

        <div class="links">
            <a href="esqueci_senha.php" style="display:block; margin-bottom: 10px; text-align: center;">Esqueceu sua senha?</a>
            <a href="cadastro.php">Não tem uma conta? <strong>Registre-se</strong></a>
        </div>

        <div class="footer">
            © <?php echo date('Y'); ?> Memórias em Nuvem
        </div>
    </div>
    <script>
        document.getElementById('cpf').addEventListener('input', function(e) {let value = e.target.value.replace(/\D/g, '');if (value.length > 11) value = value.slice(0, 11);if (value.length > 9) {e.target.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');} else if (value.length > 6) {e.target.value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3');} else if (value.length > 3) {e.target.value = value.replace(/(\d{3})(\d{3})/, '$1.$2');} else {e.target.value = value;}});
    </script>
</body>
</html>