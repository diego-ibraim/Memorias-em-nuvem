<?php
// header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Sao_Paulo');

require_once 'config.php';
require_once 'auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$auth = new Auth($pdo);
$user = $auth->getUserById($_SESSION['user_id']);

$profile_image_path = 'img/default-profile.jpg';
if (!empty($user['foto_perfil'])) {
    $potential_path = 'uploads/profiles/' . htmlspecialchars($user['foto_perfil']);
    if (file_exists($potential_path)) {
        $profile_image_path = $potential_path;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memórias em Nuvem - <?= $pageTitle ?? 'Início' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="img/3.png" borde>
    <style>
        @keyframes backgroundGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
      
        header {
            background-color:rgb(255, 255, 255);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            flex-wrap: wrap; 
        }

        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .header-phrase {
            flex-grow: 1;
            text-align: center;
            font-size: 1.1em;
            color: #A0522D;
            font-weight: bold;
            padding: 0 10px;
        }
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.8em;
            color: #A0522D;
            cursor: pointer;
            padding: 5px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #A0522D;
        }

        .menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: #fffaf0;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            z-index: 998;
            width: 100%;
            box-sizing: border-box;
            transform: translateY(-10px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .menu.active {
            display: grid;
            transform: translateY(0);
            opacity: 1;
        }
        .menu a {
            text-decoration: none;
            color: #A0522D;
            font-weight: bold;
            padding: 12px 25px;
            transition: background-color 0.2s ease;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .menu a:hover {
            background-color: #f7e8c7;
        }

        .menu .menu-logout {
            color: #A0522D;
            margin-top: 10px;
            border-top: 2px solid #e0c8b0;
            grid-column: 1 / -1; 
            justify-content: center;
        }
        .menu .menu-logout:hover {
            background-color: #fbe9e7;
        }

        @media (max-width: 768px) {
            header {
                justify-content: center;
                align-items: center;
                gap: 20px;
                padding: 15px;
            }
        
            .header-phrase {
                order: 1;
                flex-basis: 100%;
                text-align: center;
                margin-bottom: 15px;
            }
        
            .header-controls {
                display: contents;
            }
        
            .logo { order: 2; }
            .menu-toggle { order: 3; }
            .user-info { order: 4; }
        
            .logo img {
                width: 50px;
                height: 50px;
                margin-right: 0;
            }
            
            .menu { grid-template-columns: 1fr; }
            .menu a, .menu .menu-logout { justify-content: flex-start; }
        }
        
        @media (min-width: 769px) {
            .menu {
                width: 600px;
                left: auto;
                right: 20px;
                border-radius: 0 0 12px 12px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="home.php"><img src="img/2.png" alt="logo"></a>
        </div>
        
        <span class="header-phrase">Viva o presente e preserve seu legado</span>

        <div class="header-controls">
            <button class="menu-toggle" aria-label="Toggle Navigation" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="user-info">
                <a href="profile.php">
                    <img src="<?= $profile_image_path ?>" alt="Usuário">
                </a>
            </div>
        </div>

        <nav class="menu">
            <a href="home.php"><i class="fas fa-home fa-fw"></i>Início</a>
            <a href="diario.php"><i class="fas fa-book-open fa-fw"></i>Diário</a>
            <a href="fotos.php"><i class="fas fa-images fa-fw"></i>Galeria</a>
            <a href="objetivo.php"><i class="fas fa-bullseye fa-fw"></i>Objetivos</a>
            <a href="lembretes.php"><i class="fas fa-bell fa-fw"></i>Lembretes</a>
            <a href="capsula_tempo.php"><i class="fas fa-hourglass-half fa-fw"></i>Cápsula do Tempo</a>
            <a href="financeiro.php"><i class="fas fa-wallet fa-fw"></i>Financeiro</a>
            <a href="saude.php"><i class="fas fa-heart-pulse fa-fw"></i>Saúde</a>
            
            <a href="logout.php" class="menu-logout"><i class="fas fa-sign-out-alt fa-fw"></i>Sair</a>
        </nav>
    </header>

    <script>
        function toggleMenu() {
            const menu = document.querySelector('.menu');
            menu.classList.toggle('active');
        }
    </script>
</body>
</html>