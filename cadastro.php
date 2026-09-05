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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $auth = new Auth($pdo);
        
        $usuario = sanitizeInput($_POST['usuario']);
        $cpf = sanitizeInput($_POST['cpf']);
        $email = sanitizeInput($_POST['email']);
        $senha = $_POST['senha']; 
        
        $userId = $auth->register($usuario, $cpf, $email, $senha, $_FILES['foto_perfil']);
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $usuario; 
        
        header("Location: home.php");
        exit();
    } catch (PDOException $e) {
        $erro = strpos($e->getMessage(), 'Duplicate') !== false ? "CPF, usuário ou e-mail já existe" : "Erro no banco de dados: " . $e->getMessage();
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | Memórias em Nuvem</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&family=Roboto:wght@500&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/3.png" borde>
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
        html { height: 100%; background: linear-gradient(-45deg, #cef1ff, #ffded1); background-attachment: fixed; }
        body { font-family: 'Poppins', sans-serif; color: var(--cor-texto); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px 0; }
        .cadastro-container { background-color: rgba(255, 255, 255, 0.65); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-radius: var(--borda-arredondada); box-shadow: 0 8px 32px 0 var(--sombra-cor); border: 1px solid rgba(255, 255, 255, 0.25); width: 100%; max-width: 520px; padding: 40px; text-align: center; box-sizing: border-box; margin: 20px; }
        .logo h1 { font-family: 'Josefin Sans', sans-serif; color: var(--cor-titulo); font-size: 2.2rem; margin: 0 0 5px 0; }
        .logo p { font-size: 1rem; opacity: 0.8; margin: 0 0 30px 0; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 1rem; background-color: rgba(255, 255, 255, 0.5); color: var(--cor-texto); transition: all 0.3s ease; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: var(--cor-titulo); background-color: #fff; box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2); }
        .btn { background-color: var(--cor-titulo); color: white; border: none; padding: 12px 20px; border-radius: 10px; width: 100%; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 10px; text-decoration: none; display: flex; align-items: center; justify-content: center; box-sizing: border-box; }
        .btn:hover { background-color: var(--cor-titulo-hover); transform: translateY(-2px); }
        .links { margin-top: 25px; font-size: 0.9rem; }
        .links a { color: var(--cor-titulo); text-decoration: none; font-weight: 500; }
        .links a:hover { text-decoration: underline; }
        .error { color: #721c24; margin-bottom: 20px; font-size: 0.9rem; background-color: #f8d7da; padding: 10px; border-radius: 8px; border: 1px solid #f5c6cb; }
        .foto-perfil-container { margin-bottom: 25px; text-align: center; }
        .foto-perfil-label { display: flex; flex-direction: column; align-items: center; cursor: pointer; }
        .foto-perfil { width: 120px; height: 120px; border-radius: 50%; margin-bottom: 15px; border: 2px solid #e0e0e0; box-shadow: 0 2px 8px rgba(0,0,0,0.08); background-color: #f0f0f0; display: flex; justify-content: center; align-items: center; text-align: center; color: var(--cor-texto); font-size: 0.9rem; font-weight: 500; background-size: cover; background-position: center; background-repeat: no-repeat; transition: background-color 0.3s ease; }
        .foto-perfil span { line-height: 1.2; }
        .foto-perfil-label:hover .foto-perfil { background-color: #e9e9e9; }
        .foto-perfil-button { background-color: var(--cor-titulo); color: white; padding: 8px 24px; border-radius: 20px; font-weight: 500; font-size: 0.9rem; transition: background-color 0.3s ease; }
        .foto-perfil-label:hover .foto-perfil-button { background-color: var(--cor-titulo-hover); }
        #foto-input { display: none; }
        small { display: block; margin-top: 8px; font-size: 0.8rem; opacity: 0.7; }
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
        .google-btn img { /* >>> MODIFICAÇÃO: Estilo para a imagem do logo <<< */
            margin-right: 12px;
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <div class="cadastro-container">
        <div class="logo">
            <h1>Crie sua Conta</h1>
            <p>Comece a registrar suas memórias hoje mesmo.</p>
        </div>
        
        <?php if(isset($erro)): ?>
            <div class="error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="cadastro.php" enctype="multipart/form-data">
            <div class="foto-perfil-container">
                <label for="foto-input" class="foto-perfil-label">
                    <div class="foto-perfil" id="foto-preview"><span>Adicionar<br>Foto</span></div>
                    <div class="foto-perfil-button">Foto de Perfil</div>
                </label>
                <input type="file" id="foto-input" name="foto_perfil" accept="image/*">
            </div>
            <div class="form-group"><label for="usuario">Nome de usuário</label><input type="text" id="usuario" name="usuario" required></div>
            <div class="form-group"><label for="cpf">CPF</label><input type="text" id="cpf" name="cpf" required placeholder="123.456.789-00"></div>
            <div class="form-group"><label for="email">E-mail</label><input type="email" id="email" name="email" required></div>
            <div class="form-group"><label for="senha">Senha</label><input type="password" id="senha" name="senha" required minlength="8"><small>Mínimo de 8 caracteres.</small></div>
            <button type="submit" class="btn">Cadastrar</button>
        </form>

        <div class="or-separator">ou</div>

        <a href="<?= htmlspecialchars($google_login_url) ?>" class="btn google-btn">
            <img src="img/google-logo.png" alt="Logo do Google">
            <span>Cadastre-se com o Google</span>
        </a>
        
        <div class="links">
            <a href="login.php">Já tem uma conta? <strong>Faça login</strong></a>
        </div>
    </div>
    <script>
        document.getElementById('cpf').addEventListener('input', function(e) {let value = e.target.value.replace(/\D/g, '');if (value.length > 11) value = value.slice(0, 11);if (value.length > 9) { e.target.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4'); } else if (value.length > 6) { e.target.value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3'); } else if (value.length > 3) { e.target.value = value.replace(/(\d{3})(\d{3})/, '$1.$2'); } else { e.target.value = value; }});
        document.getElementById('foto-input').addEventListener('change', function(e) {if (e.target.files && e.target.files[0]) {const reader = new FileReader();reader.onload = function(event) {const previewDiv = document.getElementById('foto-preview');previewDiv.style.backgroundImage = `url('${event.target.result}')`;const textSpan = previewDiv.querySelector('span');if (textSpan) { textSpan.style.display = 'none'; }};reader.readAsDataURL(e.target.files[0]);}});
    </script>
</body>
</html>