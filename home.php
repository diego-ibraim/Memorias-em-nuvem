<?php
require_once 'config.php';
require_once 'auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pageTitleKey = 'page_title_home';
date_default_timezone_set('America/Sao_Paulo');

require_once 'header.php';

$auth = new Auth($pdo);
try {
    $user = $auth->getUserById($_SESSION['user_id']);
} catch (Exception $e) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$today = date('Y-m-d');
$welcomeMessage = "Bem-vindo, " . htmlspecialchars($user['usuario']);

function formatar_data_pt_br($timestamp) {
    $dias_semana = [
        'Monday'    => 'Segunda-feira', 'Tuesday'   => 'Terça-feira', 'Wednesday' => 'Quarta-feira',
        'Thursday'  => 'Quinta-feira', 'Friday'    => 'Sexta-feira', 'Saturday'  => 'Sábado', 'Sunday' => 'Domingo'
    ];
    $meses = [
        'January'   => 'de janeiro de', 'February'  => 'de fevereiro de', 'March' => 'de março de', 'April' => 'de abril de',
        'May'       => 'de maio de', 'June'      => 'de junho de', 'July' => 'de julho de', 'August'    => 'de agosto de',
        'September' => 'de setembro de', 'October'   => 'de outubro de', 'November'  => 'de novembro de', 'December'  => 'de dezembro de'
    ];
    $formato_ingles = date('l, d F Y H:i', $timestamp);
    $data_traduzida = str_replace(array_keys($dias_semana), array_values($dias_semana), $formato_ingles);
    $data_traduzida = str_replace(array_keys($meses), array_values($meses), $data_traduzida);
    return $data_traduzida;
}

$currentDate = formatar_data_pt_br(time());

$stmt = $pdo->prepare("SELECT * FROM lembretes WHERE usuario_id = ? AND DATE(data) = ? AND is_completed = 0 ORDER BY data ASC");
$stmt->execute([$_SESSION['user_id'], $today]);
$reminders = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND is_completed = 0 AND deadline BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY) ORDER BY deadline ASC");
$stmt->execute([$_SESSION['user_id'], $today, $today]);
$goals = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início - Memórias em Nuvem</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --cor-principal: #cef1ff;
            --cor-acento-1: #e7ffda;
            --cor-acento-2: #ffded1;
            --cor-acento-3: #fffcce;
            --cor-texto: #A0522D;
            --cor-titulo: #A0522D;
            --cor-titulo-hover: #8C4624;
            --sombra-cor: rgba(149, 173, 194, 0.2);
            --borda-arredondada: 15px;
        }
        html {
            background: linear-gradient(-45deg, #cef1ff, #ffded1);
            background-attachment: fixed;
        }
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--cor-texto);
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .home-container {
            padding: 25px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
            flex: 1;
        }
        
        /* --- CSS DO CARROSSEL SIMPLIFICADO --- */
        .carousel-container {
            max-width: 1000px;
            max-height: 300px;
            position: relative;
            margin: 0 auto 25px auto;
            aspect-ratio: 16 / 9;
        }
        .ad-slide {
            display: none;
            width: 100%;
            height: 100%;
        }
        .ad-slide img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Garante que a imagem não seja cortada */
            vertical-align: middle;
        }
        .fade-anim {
            animation-name: fade;
            animation-duration: 1.5s;
        }
        @keyframes fade {
            from {opacity: .4}
            to {opacity: 1}
        }
        /* --- FIM DO CSS DO CARROSSEL --- */
        
        .widget {
            background-color: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 25px;
            border-radius: var(--borda-arredondada);
            margin-bottom: 25px;
            box-shadow: 0 8px 32px 0 var(--sombra-cor);
            border: 1px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
        }
        .widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(149, 173, 194, 0.3);
        }
        .widget h2, .widget h3 {
            margin-top: 0;
            font-family: 'Josefin Sans', sans-serif;
            font-weight: 600;
            color: var(--cor-titulo);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .welcome-section h2 { font-size: 2.2rem; }
        .welcome-section p { font-size: 1.1rem; }
        .widget h3 {
            font-size: 1.3rem;
            border-bottom: 1px solid rgba(160, 82, 45, 0.2);
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .widget h3 i { color: var(--cor-titulo); }
        .quick-access {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .quick-access-btn {
            background-color: rgba(255, 255, 255, 0.8);
            color: var(--cor-texto);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 15px;
            border-radius: 12px;
            text-align: left;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 4px var(--sombra-cor);
        }
        .quick-access-btn:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 6px 15px var(--sombra-cor);
            background-color: white;
            border-color: #ffded1;
        }
        .quick-access-btn i {
            font-size: 1.2rem;
            background-image: linear-gradient(135deg, var(--cor-acento-2), var(--cor-acento-3));
            color: var(--cor-titulo);
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .quick-access-btn:hover i {
            transform: scale(1.1) rotate(-10deg);
            box-shadow: 0 4px 10px rgba(160, 90, 60, 0.3);
        }
        .widget-item-list { display: flex; flex-direction: column; gap: 15px; }
        .widget-item {
            background-color: rgba(255, 255, 255, 0.25); padding: 15px; border-radius: 8px;
            border-left: 5px solid; transition: all 0.2s ease-in-out;
        }
        .widget-item:hover {
            transform: translateX(5px); background-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .widget-item-content { flex-grow: 1; }
        .widget-item h4 { margin: 0 0 5px 0; color: var(--cor-titulo); font-size: 0.95rem; font-weight: 600; }
        .widget-item p { margin: 0; font-size: 0.9rem; color: var(--cor-texto); font-weight: 400; }
        .widget-item .item-meta { font-size: 0.85rem; color: var(--cor-texto); font-weight: 300; opacity: 0.8; }
        .reminder-item { border-color: var(--cor-acento-3); }
        .reminder-item.overdue { background-color: rgba(255, 222, 209, 0.7); border-color: #e57373; }
        .reminder-item.overdue p { color: var(--cor-titulo); font-weight: 600; }
        .reminder-item.highlight-upcoming { background-color: rgba(231, 255, 218, 0.7); border-color: #55a630; }
        .goal-item { border-color: #48cae4; }
        .link-ver-todos {
            display: inline-block; margin-top: 20px; font-weight: 600; color: var(--cor-titulo);
            text-decoration: none; font-size: 0.9rem; transition: all 0.2s ease;
        }
        .link-ver-todos:hover { text-decoration: underline; color: var(--cor-titulo-hover); }

        @media (min-width: 768px) {
            .carousel-container {
                aspect-ratio: 2.5 / 1;
            }
        }
        @media (min-width: 1024px) {
            .home-container {
                display: grid;
                grid-template-columns: 2.5fr 1fr;
                grid-template-rows: auto auto 1fr;
                grid-template-areas:
                    "carousel carousel"
                    "welcome welcome"
                    "main sidebar";
                gap: 30px;
            }
            .carousel-container { grid-area: carousel; } 
            .welcome-section { grid-area: welcome; }
            .main-content-area { grid-area: main; margin-bottom: 0; }
            .sidebar-area { grid-area: sidebar; }
            .quick-access { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

    <div class="home-container">

        <div class="carousel-container">
            <div class="ad-slide fade-anim">
                <img src="img/ad1.png" alt="Anúncio 1">
            </div>
            <div class="ad-slide fade-anim">
                <img src="img/ad2.png" alt="Anúncio 2">
            </div>
        </div>

        <section class="widget welcome-section">
            <h2><?= $welcomeMessage ?></h2>
            <p><?= $currentDate ?></p>
        </section>

        <div class="main-content-area">
            <section class="widget">
                <h3><i class="fas fa-rocket"></i> Acesso Rápido</h3>
                <div class="quick-access">
                    <button class="quick-access-btn" onclick="window.location.href='profile.php'"><i class="fas fa-user-circle fa-fw"></i><span>Perfil</span></button>
                    <button class="quick-access-btn" onclick="window.location.href='diario.php'"><i class="fas fa-book-open fa-fw"></i><span>Diário</span></button>
                    <button class="quick-access-btn" onclick="window.location.href='fotos.php'"><i class="fas fa-images fa-fw"></i><span>Galeria</span></button>
                    <button class="quick-access-btn" onclick="window.location.href='objetivo.php'"><i class="fas fa-bullseye fa-fw"></i><span>Objetivos</span></button>
                    <button class="quick-access-btn" onclick="window.location.href='lembretes.php'"><i class="fas fa-bell fa-fw"></i><span>Lembretes</span></button>
                    <button class="quick-access-btn" onclick="window.location.href='capsula_tempo.php'"><i class="fas fa-hourglass-half fa-fw"></i><span>Cápsula do Tempo</span></button>
                    <button class="quick-access-btn" onclick="window.location.href='financeiro.php'"><i class="fas fa-wallet fa-fw"></i><span>Financeiro</span></button>
                    <button class="quick-access-btn" onclick="window.location.href='saude.php'"><i class="fas fa-heart-pulse fa-fw"></i><span>Saúde</span></button>
                </div>
            </section>
        </div>

        <div class="sidebar-area">
            <section class="widget">
                <h3><i class="fas fa-thumbtack"></i> Lembretes de Hoje</h3>
                <div class="widget-item-list">
                    <?php if (empty($reminders)): ?>
                        <p>Nenhum lembrete pendente para hoje.</p>
                    <?php else: ?>
                        <?php foreach ($reminders as $reminder): ?>
                            <?php
                                $isOverdue = strtotime($reminder['data']) < time();
                                $isUpcoming = false;
                                if (!$isOverdue) {
                                    $diffMinutes = floor((strtotime($reminder['data']) - time()) / 60);
                                    if ($diffMinutes <= 30) $isUpcoming = true;
                                }
                            ?>
                            <div class="widget-item reminder-item <?= $isOverdue ? 'overdue' : '' ?> <?= $isUpcoming ? 'highlight-upcoming' : '' ?>">
                                <div class="widget-item-content">
                                    <p><?= htmlspecialchars($reminder['texto']) ?></p>
                                    <p class="item-meta">
                                        às <?= date('H:i', strtotime($reminder['data'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="lembretes.php" class="link-ver-todos">+ Ver todos</a>
            </section>

            <section class="widget">
                <h3><i class="fas fa-bullseye"></i> Objetivos Próximos</h3>
                <div class="widget-item-list">
                    <?php if (empty($goals)): ?>
                        <p>Nenhum objetivo com prazo na próxima semana.</p>
                    <?php else: ?>
                        <?php foreach ($goals as $goal): ?>
                            <div class="widget-item goal-item">
                                <div class="widget-item-content">
                                    <h4><?= htmlspecialchars($goal['title']) ?></h4>
                                    <p class="item-meta">Prazo: <?= date('d/m/Y', strtotime($goal['deadline'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="objetivo.php" class="link-ver-todos">+ Ver todos</a>
            </section>
        </div>

    </div>
    
    <?php include 'footer.php'; ?>

    <script>
        let slideIndex = 0;
        carousel();

        function carousel() {
            const slides = document.getElementsByClassName("ad-slide");
            if (slides.length === 0) return; // Sai se não houver slides

            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }

            slideIndex++;
            if (slideIndex > slides.length) {
                slideIndex = 1;
            }

            slides[slideIndex - 1].style.display = "block";
            setTimeout(carousel, 4000); // Muda a imagem a cada 4 segundos
        }
    </script>
</body>
</html>