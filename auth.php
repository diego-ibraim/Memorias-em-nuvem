<?php
require_once 'config.php';

class Auth {
    private $pdo;
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_TIME_MINUTES = 15;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($usuario, $cpf, $email, $senha, $foto_perfil = null) {
        if (empty($usuario) || empty($cpf) || empty($email) || empty($senha)) {
            throw new Exception("Todos os campos, exceto a foto, são obrigatórios.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("E-mail inválido.");
        }
        if (strlen($senha) < 8) {
            throw new Exception("A senha deve ter pelo menos 8 caracteres.");
        }
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11) {
            throw new Exception("CPF inválido. Deve conter 11 dígitos.");
        }
        $stmt = $this->pdo->prepare("SELECT user_id FROM usuarios WHERE usuario = ? OR cpf = ? OR email = ?");
        $stmt->execute([$usuario, $cpf, $email]);
        if ($stmt->fetch()) {
            throw new Exception("Usuário, CPF ou e-mail já cadastrado.");
        }
        $foto_perfil_path = null;
        if ($foto_perfil && $foto_perfil['error'] === UPLOAD_ERR_OK) {
            $user_upload_dir = UPLOAD_DIR . 'profiles/';
            if (!file_exists($user_upload_dir)) {
                mkdir($user_upload_dir, 0777, true);
            }
            $ext = pathinfo($foto_perfil['name'], PATHINFO_EXTENSION);
            $foto_perfil_path = 'profile_' . uniqid() . '.' . $ext;
            $destination = $user_upload_dir . $foto_perfil_path;
            if (!move_uploaded_file($foto_perfil['tmp_name'], $destination)) {
                throw new Exception("Falha ao enviar a foto de perfil.");
            }
        }
        
        // Salva a senha em texto puro (sem password_hash)
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (usuario, cpf, email, senha, foto_perfil) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuario, $cpf, $email, $senha, $foto_perfil_path]);
        return $this->pdo->lastInsertId();
    }

    public function login($cpf, $senha) {
        $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
        
        $stmt = $this->pdo->prepare("SELECT user_id, google_id, cpf, senha, usuario, failed_login_attempts, lockout_until FROM usuarios WHERE cpf = ?");
        $stmt->execute([$cpf_limpo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Tenta buscar pelo CPF original sem limpar para verificar formatação
            $stmt_com_mascara = $this->pdo->prepare("SELECT user_id FROM usuarios WHERE cpf = ?");
            $stmt_com_mascara->execute([$cpf]);
            if ($stmt_com_mascara->fetch()) {
                throw new Exception("ERRO IDENTIFICADO: O CPF está gravado com pontos/traço no banco. Use apenas números.");
            }

            throw new Exception("CPF ou senha incorretos.");
        }

        if (!is_null($user['google_id']) && is_null($user['senha'])) {
            throw new Exception("Esta conta está vinculada ao Google. Por favor, use o botão 'Entrar com o Google'.");
        }

        if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
            $remaining = strtotime($user['lockout_until']) - time();
            $minutes = ceil($remaining / 60);
            throw new Exception("Conta bloqueada por excesso de tentativas. Tente novamente em {$minutes} minuto(s).");
        }

        // Comparação direta de texto simples (sem password_verify)
        if ($senha !== $user['senha']) {
            $attempts = $user['failed_login_attempts'] + 1;
            if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                $lockout_until = date('Y-m-d H:i:s', time() + (self::LOCKOUT_TIME_MINUTES * 60));
                $update_stmt = $this->pdo->prepare("UPDATE usuarios SET failed_login_attempts = ?, lockout_until = ? WHERE user_id = ?");
                $update_stmt->execute([$attempts, $lockout_until, $user['user_id']]);
            } else {
                $update_stmt = $this->pdo->prepare("UPDATE usuarios SET failed_login_attempts = ? WHERE user_id = ?");
                $update_stmt->execute([$attempts, $user['user_id']]);
            }
            throw new Exception("CPF ou senha incorretos.");
        }

        $update_stmt = $this->pdo->prepare("UPDATE usuarios SET failed_login_attempts = 0, lockout_until = NULL WHERE user_id = ?");
        $update_stmt->execute([$user['user_id']]);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['usuario'];
        $this->updateLastLogin($user['user_id']);
        return true;
    }

    public function findOrCreateGoogleUser($google_user_data) {
        $google_id = $google_user_data->getId();
        $email = $google_user_data->getEmail();
        $nome = $google_user_data->getName();
        $foto_perfil_url = $google_user_data->getPicture();

        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE google_id = ?");
        $stmt->execute([$google_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) { return $user; }

        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $update_stmt = $this->pdo->prepare("UPDATE usuarios SET google_id = ? WHERE user_id = ?");
            $update_stmt->execute([$google_id, $user['user_id']]);
            $user['google_id'] = $google_id; 
            return $user;
        }

        $username = $nome;
        $stmt_check_username = $this->pdo->prepare("SELECT user_id FROM usuarios WHERE usuario = ?");
        $stmt_check_username->execute([$username]);
        while ($stmt_check_username->fetch()) {
            $username = $nome . rand(100, 999);
            $stmt_check_username->execute([$username]);
        }
        
        $foto_perfil_path = null;
        if ($foto_perfil_url) {
            $foto_content = @file_get_contents($foto_perfil_url);
            if ($foto_content) {
                $profile_dir = UPLOAD_DIR . 'profiles/';
                if (!is_dir($profile_dir)) { mkdir($profile_dir, 0777, true); }
                $foto_perfil_path = 'profile_google_' . uniqid() . '.jpg';
                $destination = $profile_dir . $foto_perfil_path;
                file_put_contents($destination, $foto_content);
            }
        }
        
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (google_id, usuario, email, foto_perfil) VALUES (?, ?, ?, ?)");
        $stmt->execute([$google_id, $username, $email, $foto_perfil_path]);
        $userId = $this->pdo->lastInsertId();
        return $this->getUserById($userId);
    }
    
    public function updateCpf($userId, $cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11) { throw new Exception("CPF inválido. Deve conter 11 dígitos."); }
        $stmt = $this->pdo->prepare("SELECT user_id FROM usuarios WHERE cpf = ? AND user_id != ?");
        $stmt->execute([$cpf, $userId]);
        if ($stmt->fetch()) { throw new Exception("Este CPF já está sendo utilizado por outra conta."); }
        $update_stmt = $this->pdo->prepare("UPDATE usuarios SET cpf = ? WHERE user_id = ?");
        return $update_stmt->execute([$cpf, $userId]);
    }

    public function logout() {
        if (session_status() !== PHP_SESSION_NONE) {
            $_SESSION = [];
            session_destroy();
        }
    }

    public function updateLastLogin($userId) { 
        $stmt = $this->pdo->prepare("UPDATE usuarios SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?"); 
        $stmt->execute([$userId]); 
    }
    
    public function getUserById($userId) { 
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE user_id = ?"); 
        $stmt->execute([$userId]); 
        $user = $stmt->fetch(PDO::FETCH_ASSOC); 
        if (!$user) { throw new Exception("Usuário não encontrado."); } 
        return $user; 
    }

    public function generatePasswordResetToken($email) {
        $stmt = $this->pdo->prepare("SELECT user_id, usuario, google_id, senha FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (!is_null($user['google_id']) && is_null($user['senha'])) {
                error_log("Tentativa de redefinição de senha para a conta Google: " . $email);
                return null;
            }
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600);
            $update_stmt = $this->pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expires_at = ? WHERE user_id = ?");
            $update_stmt->execute([$token, $expires_at, $user['user_id']]);
            return ['token' => $token, 'email' => $email, 'name' => $user['usuario']];
        }
        return null;
    }

    public function verifyPasswordResetToken($token) {
        if (empty($token)) { return false; }
        $stmt = $this->pdo->prepare("SELECT user_id, reset_token_expires_at FROM usuarios WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if ($user && strtotime($user['reset_token_expires_at']) > time()) {
            return true;
        }
        return false;
    }

    public function resetPassword($token, $newPassword) {
        if (!$this->verifyPasswordResetToken($token)) {
            throw new Exception("Token inválido ou expirado. Por favor, solicite uma nova redefinição.");
        }
        if (strlen($newPassword) < 8) {
            throw new Exception("A nova senha deve ter pelo menos 8 caracteres.");
        }
        // Atualiza a senha em texto simples diretamente
        $stmt = $this->pdo->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE reset_token = ?");
        return $stmt->execute([$newPassword, $token]);
    }
}