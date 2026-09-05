<?php
// >>> CORREÇÃO: Garante que a sessão está ativa antes de destruí-la. <<<
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Carrega os arquivos de configuração e da classe de autenticação.
require_once 'config.php';
require_once 'auth.php';

// Cria uma instância da classe Auth, passando a conexão com o banco de dados.
$auth = new Auth($pdo);

// Executa o método de logout, que limpa e destrói os dados da sessão.
$auth->logout();

// Redireciona o usuário para a página de login de forma segura.
redirect('login.php');
exit();
?>