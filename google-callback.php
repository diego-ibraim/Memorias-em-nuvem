<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

// Cria o cliente do Google
$google_client = new Google_Client();
$google_client->setClientId(GOOGLE_CLIENT_ID);
$google_client->setClientSecret(GOOGLE_CLIENT_SECRET);
$google_client->setRedirectUri(GOOGLE_REDIRECT_URI);
$google_client->addScope("email");
$google_client->addScope("profile");

// Verifica se o Google enviou o código de autorização
if (isset($_GET['code'])) {
    try {
        // Troca o código por um token de acesso
        $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            throw new Exception('Erro ao obter token de acesso: ' . $token['error_description']);
        }
        
        $google_client->setAccessToken($token);

        // Pega as informações do perfil do usuário do Google
        $google_oauth = new Google_Service_Oauth2($google_client);
        $google_account_info = $google_oauth->userinfo->get();

        $auth = new Auth($pdo);
        
        // Chama a função para encontrar ou CRIAR E SALVAR o usuário no seu banco de dados
        $user = $auth->findOrCreateGoogleUser($google_account_info);

        // Inicia a sessão normal para o usuário
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['usuario'];
        
        // Atualiza a data do último login
        $auth->updateLastLogin($user['user_id']);

        // Redireciona para a página inicial, agora logado
        redirect('home.php');

    } catch (Exception $e) {
        error_log("Erro no callback do Google: " . $e->getMessage());
        $_SESSION['login_error'] = "Ocorreu um erro durante o login com o Google. Tente novamente.";
        redirect('login.php');
    }
} else {
    // Se não houver código, volta para o login
    redirect('login.php');
}
?>