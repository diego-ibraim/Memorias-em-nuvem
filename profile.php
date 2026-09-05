<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// A chamada requireLogin($pdo) agora está no final do config.php,
// então esta linha garante que a verificação de segurança seja executada.
require_once 'config.php';
require_once 'email_service.php'; // Necessário para enviar o e-mail de verificação
require_once 'auth.php'; // Incluído para usar a classe Auth

// 1. Verificação de Segurança e ID do Usuário
if (!isset($_SESSION['user_id'])) {
    // A verificação em config.php já deve ter redirecionado, mas é uma segurança extra.
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['user_id'];
$auth = new Auth($pdo); // Instancia a classe Auth

// 2. Lógica de Processamento de Formulários
$verification_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_verification'])) {
    unset($_SESSION['email_change_pending']);
    header("Location: profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verification_code'])) {
    if (isset($_SESSION['email_change_pending'])) {
        $pending_data = $_SESSION['email_change_pending'];
        $submitted_code = trim($_POST['verification_code']);

        if (time() > $pending_data['expires']) {
            unset($_SESSION['email_change_pending']);
            header("Location: profile.php?status=code_expired");
            exit();
        }

        if ($submitted_code == $pending_data['code']) {
            $stmt_update_email = $pdo->prepare("UPDATE usuarios SET email = ? WHERE user_id = ?");
            $stmt_update_email->execute([$pending_data['new_email'], $userId]);
            
            unset($_SESSION['email_change_pending']);
            header("Location: profile.php?status=email_updated");
            exit();
        } else {
            $_SESSION['email_change_pending']['attempts'] = ($_SESSION['email_change_pending']['attempts'] ?? 0) + 1;
            
            if ($_SESSION['email_change_pending']['attempts'] >= 3) {
                unset($_SESSION['email_change_pending']);
                header("Location: profile.php?status=too_many_attempts");
                exit();
            }
            
            $remaining_attempts = 3 - $_SESSION['email_change_pending']['attempts'];
            $verification_error = "Código inválido. Você tem mais {$remaining_attempts} tentativa(s).";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['verification_code']) && !isset($_POST['cancel_verification'])) {
    $stmt_user = $pdo->prepare("SELECT usuario, email, foto_perfil, google_id FROM usuarios WHERE user_id = ?");
    $stmt_user->execute([$userId]);
    $currentUser = $stmt_user->fetch();
    
    $isGoogleUserForPost = !is_null($currentUser['google_id']);
    $current_email = $currentUser['email'];
    $foto_perfil_path = $currentUser['foto_perfil'];

    $new_email_input = $_POST['email'] ?? $current_email;
    $senha_nova = $_POST['senha'] ?? ''; // <-- CORREÇÃO APLICADA AQUI
    $foto_perfil_file = $_FILES['foto_perfil'];

    $update_params = [];
    $update_sql_parts = [];

    if (isset($foto_perfil_file) && $foto_perfil_file['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/profiles/";
        if (!is_dir($target_dir)) @mkdir($target_dir, 0755, true);
        
        $fileExtension = strtolower(pathinfo($foto_perfil_file["name"], PATHINFO_EXTENSION));
        $newFileName = 'user_' . $userId . '_' . uniqid() . '.' . $fileExtension;
        $target_file = $target_dir . $newFileName;
        
        if (move_uploaded_file($foto_perfil_file["tmp_name"], $target_file)) {
            if (!empty($foto_perfil_path) && file_exists($target_dir . $foto_perfil_path)) {
                 @unlink($target_dir . $foto_perfil_path);
            }
            $update_sql_parts[] = "foto_perfil = ?";
            $update_params[] = $newFileName;
        }
    }

    if (!$isGoogleUserForPost && !empty($senha_nova)) {
        if (strlen($senha_nova) < 8) {
             header("Location: profile.php?status=password_short");
             exit();
        }
        $update_sql_parts[] = "senha = ?";
        $update_params[] = password_hash($senha_nova, PASSWORD_DEFAULT);
    }

    if (!empty($update_sql_parts)) {
        $update_params[] = $userId;
        $sql = "UPDATE usuarios SET " . implode(', ', $update_sql_parts) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($update_params);
    }
    
    if (!$isGoogleUserForPost && $new_email_input !== $current_email) {
        if (!filter_var($new_email_input, FILTER_VALIDATE_EMAIL)) {
            header("Location: profile.php?status=invalid_email_format");
            exit();
        }
        $stmt_check_email = $pdo->prepare("SELECT user_id FROM usuarios WHERE email = ? AND user_id != ?");
        $stmt_check_email->execute([$new_email_input, $userId]);
        if ($stmt_check_email->fetch()) {
            header("Location: profile.php?status=email_taken");
            exit();
        }
        $verification_code = rand(100000, 999999);
        $_SESSION['email_change_pending'] = [
            'new_email' => $new_email_input,
            'code'      => $verification_code,
            'expires'   => time() + 600,
            'attempts'  => 0
        ];
        $subject = 'Confirme seu novo e-mail - Memórias em Nuvem';
        $content = '<p>Olá, ' . htmlspecialchars($currentUser['usuario']) . '!</p>' . '<p>Use o código: <strong>' . $verification_code . '</strong></p>';
        $htmlEmail = createEmailTemplate('Verificação de E-mail', $content);
        sendEmail($new_email_input, $currentUser['usuario'], $subject, $htmlEmail);
        header("Location: profile.php?status=verification_sent");
        exit();
    }
    header("Location: profile.php?status=success");
    exit();
}

// 3. Define o título e inclui o header
$pageTitle = 'Meu Perfil';
require_once 'header.php';

// 4. Busca dados do usuário para exibir na página
$user_page_data = $auth->getUserById($userId);
$isGoogleUser = !is_null($user_page_data['google_id']);

$foto_src_page = 'img/padrao.jpg';
if (!empty($user_page_data['foto_perfil'])) {
    if(filter_var($user_page_data['foto_perfil'], FILTER_VALIDATE_URL)) {
         $foto_src_page = htmlspecialchars($user_page_data['foto_perfil']);
    } else if (file_exists('uploads/profiles/' . $user_page_data['foto_perfil'])) {
        $foto_src_page = 'uploads/profiles/' . htmlspecialchars($user_page_data['foto_perfil']);
    }
}
?>

<head>
    <style>
        :root {
            --cor-principal: #cef1ff; --cor-acento-2: #ffded1; --cor-texto: #A0522D;
            --cor-titulo: #A0522D; --cor-titulo-hover: #8C4624; --sombra-cor: rgba(149, 173, 194, 0.2);
            --borda-arredondada: 15px;
        }
        html { background: linear-gradient(-45deg, var(--cor-principal), var(--cor-acento-2)); background-attachment: fixed; }
        body { font-family: 'Poppins', sans-serif; color: var(--cor-texto); margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .page-container { padding: 25px; max-width: 700px; margin: 0 auto; width: 100%; box-sizing: border-box; flex: 1; }
        .profile-card {
            background-color: rgba(255, 255, 255, 0.65); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            padding: 35px; border-radius: var(--borda-arredondada); box-shadow: 0 8px 32px 0 var(--sombra-cor);
            border: 1px solid rgba(255, 255, 255, 0.25); transition: all 0.3s ease;
        }
        .profile-card:hover { transform: translateY(-5px); box-shadow: 0 12px 40px 0 rgba(149, 173, 194, 0.3); }
        .status-message { padding: 12px; margin-bottom: 25px; border-radius: 8px; text-align: center; font-weight: 500; }
        .status-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-info { background-color: #cce5ff; color: #004085; border: 1px solid #b8daff; }
        .status-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        h1, h3 { font-family: 'Josefin Sans', sans-serif; color: var(--cor-titulo); text-align: center; }
        h1 { margin-top: 0; font-weight: 600; font-size: 2.2rem; display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 30px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input, .form-control-static {
            width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 10px; font-family: 'Poppins', sans-serif;
            font-size: 1rem; background-color: rgba(255, 255, 255, 0.5); color: var(--cor-texto); transition: all 0.3s ease; box-sizing: border-box;
        }
        .form-control-static { background-color: rgba(224, 224, 224, 0.3); } /* Adicionei para melhor visualização */
        .form-group input:focus { outline: none; border-color: var(--cor-titulo); background-color: #fff; box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2); }
        .form-group input[disabled] { background-color: rgba(224, 224, 224, 0.5); cursor: not-allowed; }
        .btn-submit, .btn-cancel {
            background-color: var(--cor-titulo); color: white; border: none; padding: 12px 25px; border-radius: 10px; font-size: 1rem;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 10px; display: block; width: 100%;
        }
        .btn-submit:hover { background-color: var(--cor-titulo-hover); transform: translateY(-2px); }
        .btn-cancel { background-color: #6c757d; }
        .btn-cancel:hover { background-color: #5a6268; }
        .profile-pic-container { text-align: center; margin-bottom: 20px; }
        .profile-pic-preview { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s ease; }
        .profile-pic-preview:hover { transform: scale(1.05); }
        .form-group-upload { text-align: center; margin-bottom: 30px; }
        .btn-upload { display: inline-flex; align-items: center; gap: 10px; padding: 10px 20px; background-color: var(--cor-acento-2); color: var(--cor-titulo); border: 1px solid rgba(160, 82, 45, 0.2); border-radius: 10px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease-in-out; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); }
        .btn-upload:hover { background-color: #fccab3; color: var(--cor-titulo-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 125, 87, 0.3); }
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.85); align-items: center; justify-content: center; }
        .modal-content { margin: auto; display: block; max-width: 80%; max-height: 80%; border-radius: 10px; animation: zoom 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes zoom { from {transform: scale(0.5);} to {transform: scale(1);} }
        .close-modal { position: absolute; top: 20px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; transition: 0.3s; cursor: pointer; }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="profile-card">
            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] == 'success'): ?> <p class="status-message status-success">Perfil atualizado com sucesso!</p> <?php endif; ?>
                <?php if ($_GET['status'] == 'email_updated'): ?> <p class="status-message status-success">Seu e-mail foi alterado com sucesso!</p> <?php endif; ?>
                <?php if ($_GET['status'] == 'verification_sent'): ?> <p class="status-message status-info">Suas outras informações foram salvas. <br>Enviamos um código para o seu novo e-mail para confirmar a alteração.</p> <?php endif; ?>
                <?php if ($_GET['status'] == 'email_taken'): ?> <p class="status-message status-error"><b>Erro:</b> O e-mail informado já está em uso por outra conta.</p> <?php endif; ?>
                <?php if ($_GET['status'] == 'invalid_email_format'): ?> <p class="status-message status-error"><b>Erro:</b> O formato do novo e-mail é inválido.</p> <?php endif; ?>
                <?php if ($_GET['status'] == 'password_short'): ?> <p class="status-message status-error"><b>Erro:</b> A senha deve ter no mínimo 8 caracteres.</p> <?php endif; ?>
                <?php if ($_GET['status'] == 'invalid_code'): ?> <p class="status-message status-error"><b>Erro:</b> Código de verificação inválido. Por favor, tente alterar o e-mail novamente.</p> <?php endif; ?>
                <?php if ($_GET['status'] == 'code_expired'): ?> <p class="status-message status-error"><b>Erro:</b> O código de verificação expirou. Por favor, tente alterar o e-mail novamente.</p> <?php endif; ?>
                <?php if ($_GET['status'] == 'too_many_attempts'): ?> <p class="status-message status-error"><b>Erro:</b> Muitas tentativas inválidas. Por segurança, o processo foi cancelado. Tente novamente.</p> <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['email_change_pending'])): ?>
                <div class="verification-container">
                    <h3><i class="fas fa-envelope-check"></i> Verifique seu Novo E-mail</h3>
                    <?php if (!empty($verification_error)): ?>
                        <p class="status-message status-error"><?= $verification_error ?></p>
                    <?php endif; ?>
                    <p style="text-align: center; line-height: 1.6;">Enviamos um código de 6 dígitos para <strong><?= htmlspecialchars($_SESSION['email_change_pending']['new_email']); ?></strong>. Insira o código abaixo para confirmar a alteração. O código expira em 10 minutos.</p>
                    <form method="POST" action="profile.php">
                        <div class="form-group">
                            <label for="verification_code">Código de Verificação</label>
                            <input type="text" name="verification_code" id="verification_code" required maxlength="6" pattern="\d{6}" title="Insira o código de 6 dígitos.">
                        </div>
                        <button type="submit" class="btn-submit">Confirmar Novo E-mail</button>
                    </form>
                    <form method="POST" action="profile.php">
                        <input type="hidden" name="cancel_verification" value="1">
                        <button type="submit" class="btn-cancel">Cancelar Alteração de E-mail</button>
                    </form>
                </div>
            <?php else: ?>
                <h1><i class="fas fa-user-edit"></i> Meu Perfil</h1>
                <form method="POST" action="profile.php" enctype="multipart/form-data">
                    <div class="profile-pic-container">
                        <img id="profileImage" src="<?= $foto_src_page ?>" alt="Foto de Perfil" class="profile-pic-preview">
                    </div>
                    <div class="form-group form-group-upload">
                        <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" style="display: none;">
                        <label for="foto_perfil" class="btn-upload"><i class="fas fa-camera"></i> Escolha uma foto nova</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Nome de Usuário</label>
                        <div class="form-control-static"><?= htmlspecialchars($user_page_data['usuario']); ?></div>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <?php if ($isGoogleUser): ?>
                            <div class="form-control-static"><?= htmlspecialchars($user_page_data['email']); ?> (Vinculado ao Google)</div>
                        <?php else: ?>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_page_data['email']); ?>" required>
                        <?php endif; ?>
                    </div>

                    <?php if (!is_null($user_page_data['cpf'])): ?>
                        <div class="form-group">
                            <label>CPF</label>
                             <div class="form-control-static"><?= htmlspecialchars($user_page_data['cpf']); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isGoogleUser): ?>
                        <div class="form-group">
                            <label for="senha">Nova Senha</label>
                            <input type="password" id="senha" name="senha" placeholder="Deixe em branco para manter a senha atual">
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn-submit">
                        <?= $isGoogleUser ? 'Atualizar Foto' : 'Atualizar Informações' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <span class="close-modal" onclick="document.getElementById('imageModal').style.display='none'">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('foto_perfil');
            const previewImage = document.getElementById('profileImage');
            
            if (fileInput && previewImage) {
                fileInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImage.src = e.target.result;
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }

            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            
            if (modal && previewImage && modalImg) {
                previewImage.addEventListener('click', function() {
                    modal.style.display = "flex";
                    modalImg.src = this.src;
                });
                window.addEventListener('click', function(event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                });
            }
        });
    </script>
    
    <?php include 'footer.php'; ?>
</body>
</html>