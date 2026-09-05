<?php
/**
 * Ponto de Entrada e Página de Apresentação (Landing Page).
 *
 * Este script verifica se o usuário tem uma sessão ativa.
 * - Se o usuário estiver logado, ele é redirecionado para a página 'home.php'.
 * - Se não estiver logado, exibe o conteúdo da landing page.
 */

// 1. Inclui o arquivo de configuração.
// Isso inicia a sessão e nos dá acesso às funções globais.
require_once 'config.php';

// 2. Verifica se a sessão do usuário já está ativa.
if (isset($_SESSION['user_id'])) {
    // 3. Se estiver logado, redireciona para a página principal do sistema.
    redirect('home.php');
}

// 4. Se não estiver logado, o script continua e renderiza o HTML abaixo.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Memórias em Nuvem - Capture, conecte e reviva suas memórias em um santuário digital seguro e elegante.">
    <title>Memórias em Nuvem - Onde seu legado ganha vida</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                        'josefin': ['Josefin Sans', 'sans-serif']
                    },
                    colors: {
                        'principal': '#cef1ff',
                        'acento-1': '#e7ffda',
                        'acento-2': '#ffded1',
                        'acento-3': '#fffcce',
                        'texto': '#804b2b',
                        'titulo': '#a55a30',
                        'titulo-hover': '#6b331c',
                        'fundo': '#fefbfa'
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --cor-principal: #cef1ff;
            --cor-acento-1: #e7ffda;
            --cor-acento-2: #ffded1;
            --cor-acento-3: #fffcce;
            --cor-texto: #804b2b;
            --cor-titulo: #a55a30;
            --cor-titulo-hover: #6b331c;
            --cor-fundo: #fefbfa;
            --borda-arredondada: 18px;
        }
        html { 
            scroll-behavior: smooth; 
            scroll-padding-top: 120px;
        }
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--cor-texto);
            margin: 0;
            padding: 0;
            background-color: var(--cor-fundo);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            overflow-x: hidden;
        }

        /* General Styles */
        .content-section { padding: 100px 20px; }
        .section-title { 
            font-size: clamp(2.5rem, 5vw, 3.5rem); 
            color: var(--cor-titulo); 
            margin-bottom: 24px; 
            text-align: center; 
            font-family: 'Josefin Sans', sans-serif; 
            font-weight: 700;
            line-height: 1.2;
        }
        .section-subtitle { 
            font-size: clamp(1rem, 2vw, 1.2rem); 
            max-width: 700px; 
            margin: 0 auto 60px; 
            color: var(--cor-texto); 
            line-height: 1.8; 
            text-align: center; 
        }

        /* Header */
        .landing-header {
            padding: 16px 24px;
            background: linear-gradient(to bottom, rgba(253, 252, 250, 0.9), rgba(253, 252, 250, 0.95));
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(230, 230, 230, 0.3);
            position: sticky; 
            top: 0; 
            z-index: 1000;
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
        .logo-container { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }
        .logo-container img { 
            width: 48px; 
            height: 48px; 
            border-radius: 50%; 
            border: 2px solid var(--cor-acento-2);
        }
        .logo-container h1 { 
            font-family: 'Josefin Sans', sans-serif; 
            color: var(--cor-titulo); 
            font-size: 1.75rem; 
            margin: 0;
        }
        .nav-buttons { 
            display: flex; 
            gap: 10px; 
        }
        .btn {
            font-size: 0.95rem;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-login {
            background-color: transparent;
            color: var(--cor-titulo);
            border: 2px solid var(--cor-acento-2);
        }
        .btn-login:hover {
            background-color: var(--cor-acento-2);
            color: var(--cor-titulo-hover);
            transform: translateY(-2px);
        }
        .btn-signup {
            background: linear-gradient(135deg, var(--cor-titulo), var(--cor-titulo-hover));
            color: white;
            box-shadow: 0 4px 15px rgba(160, 82, 45, 0.4);
        }
        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.5);
        }

        /* Hero Section */
        .hero-section {
            padding: 80px 24px;
            position: relative;
            text-align: center;
            background: linear-gradient(135deg, var(--cor-principal), var(--cor-acento-2));
            overflow: hidden;
        }
        .aurora-bg {
            position: absolute;
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }
        .aurora-bg::before, .aurora-bg::after {
            content: '';
            position: absolute;
            width: 600px; 
            height: 600px;
            border-radius: 50%;
            opacity: 0.6;
            animation: auroraMove 25s ease-in-out infinite alternate;
        }
        .aurora-bg::before { 
            background: radial-gradient(circle, var(--cor-acento-1), transparent 70%); 
            top: -20%; 
            left: -20%; 
        }
        .aurora-bg::after { 
            background: radial-gradient(circle, var(--cor-acento-3), transparent 70%); 
            bottom: -20%; 
            right: -20%; 
            animation-delay: -12s; 
        }
        @keyframes auroraMove { 
            0% { transform: translate(0, 0) scale(1); opacity: 0.6; }
            100% { transform: translate(150px, 150px) scale(1.2); opacity: 0.8; }
        }
        .hero-container { 
            position: relative; 
            z-index: 2; 
            max-width: 1000px; 
            margin: 0 auto; 
        }
        .hero-text h2 {
            font-family: 'Josefin Sans', sans-serif;
            font-size: clamp(2.8rem, 6vw, 4.5rem);
            line-height: 1.15;
            margin: 0 0 20px;
            background: linear-gradient(135deg, var(--cor-titulo), var(--cor-titulo-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-text p {
            font-size: clamp(1rem, 2vw, 1.15rem);
            color: var(--cor-texto);
            max-width: 600px;
            margin: 0 auto 32px;
            line-height: 1.7;
        }
        .hero-images {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .hero-images img {
            width: 100%;
            max-width: 450px;
            border-radius: var(--borda-arredondada);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border: 6px solid var(--cor-fundo);
            transition: transform 0.4s ease;
        }
        .hero-images img:hover {
            transform: scale(1.03);
        }
        @media (max-width: 767px) {
            .hero-images .second-image {
                display: none;
            }
        }

        /* Timeline Section */
        .timeline-section {
            background-color: var(--cor-fundo);
        }
        .timeline-container {
            position: relative;
            max-width: 1100px;
            margin: auto;
        }
        .timeline-container::after {
            content: '';
            position: absolute;
            width: 6px;
            background: linear-gradient(to bottom, var(--cor-principal), var(--cor-acento-1), var(--cor-acento-2));
            top: 0;
            left: 50%;
            margin-left: -3px;
            height: 100%;
            z-index: 1;
            border-radius: 3px;
        }
        .timeline-item {
            padding: 20px 30px;
            position: relative;
            width: 50%;
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }
        .timeline-item.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .timeline-item.left { left: 0; }
        .timeline-item.right { left: 50%; }
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            right: -10px;
            background-color: var(--cor-fundo);
            border: 3px solid var(--cor-titulo);
            top: 25px;
            border-radius: 50%;
            z-index: 2;
        }
        .right::after { left: -10px; }
        .timeline-content {
            padding: 24px;
            background: white;
            border-radius: var(--borda-arredondada);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .timeline-content:hover {
            transform: translateY(-5px);
        }
        .timeline-content i {
            font-size: 2.2rem;
            color: var(--cor-titulo);
            margin-bottom: 12px;
        }
        .timeline-content h3 {
            font-family: 'Josefin Sans', sans-serif;
            font-size: 1.6rem;
            margin: 0 0 10px;
            color: var(--cor-titulo);
        }
        .timeline-content p {
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Plans Section */
        .plans-section {
            background: linear-gradient(to bottom, var(--cor-fundo), var(--cor-acento-3));
        }
        .plans-container {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: auto;
        }
        .plan-card {
            background: white;
            border-radius: var(--borda-arredondada);
            padding: 32px;
            flex: 1 1 300px;
            max-width: 360px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            position: relative;
        }
        .plan-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        .plan-card h3 {
            font-family: 'Josefin Sans', sans-serif;
            font-size: 1.6rem;
            color: var(--cor-titulo);
            margin: 0 0 16px;
        }
        .plan-price {
            font-family: 'Josefin Sans', sans-serif;
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--cor-titulo);
            margin: 16px 0;
        }
        .plan-price span {
            font-size: 1rem;
            font-weight: 400;
        }
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 24px 0;
            text-align: left;
        }
        .plan-features li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        .plan-features i {
            color: var(--cor-titulo);
        }
        .plan-card.recommended {
            border: 2px solid var(--cor-acento-2);
            transform: scale(1.03);
        }
        .popular-badge {
            background: var(--cor-titulo);
            color: white;
            padding: 6px 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 20px;
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
        }
        .promo-highlight {
            background: linear-gradient(135deg, var(--cor-acento-1), #faffeb);
            color: var(--cor-titulo-hover);
            padding: 12px;
            border-radius: 10px;
            font-size: 1rem;
            margin: -10px auto 24px;
            border: 1px solid var(--cor-acento-2);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: relative;
            z-index: 2;
        }
        .promo-highlight .promo-title {
            font-family: 'Josefin Sans', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            display: block;
        }
        .promo-highlight .promo-subtitle {
            font-size: 0.9rem;
            display: block;
            margin-top: 4px;
        }

        /* Footer */
        .footer {
            background: linear-gradient(to top, var(--cor-titulo), var(--cor-titulo-hover));
            color: white;
            padding: 40px 24px;
            text-align: center;
        }
        .footer p {
            margin: 0 0 16px;
            font-size: 0.95rem;
        }
        .footer-links a {
            color: var(--cor-acento-3);
            margin: 0 12px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        .footer-links a:hover {
            color: var(--cor-acento-1);
        }

        /* Responsiveness */
        @media (max-width: 1024px) {
            .timeline-container::after { left: 25px; }
            .timeline-item { 
                width: 100%; 
                padding-left: 60px; 
                padding-right: 20px; 
            }
            .timeline-item.left::after, .timeline-item.right::after { left: 15px; }
            .timeline-item.right { left: 0; }
        }
        @media (max-width: 768px) {
            .landing-header { 
                flex-direction: column; 
                gap: 16px; 
                padding: 12px 16px; 
            }
            .content-section { padding: 60px 16px; }
            .hero-text h2 { font-size: clamp(2.2rem, 5vw, 3.2rem); }
            .plan-card { flex: 1 1 100%; max-width: 100%; }
        }
    </style>
</head>
<body>
    <header class="landing-header" id="main-header" role="banner">
        <div class="logo-container">
            <img src="img/2.png" alt="Logo Memórias em Nuvem" aria-label="Logo Memórias em Nuvem">
            <h1>Memórias em Nuvem</h1>
        </div>
        <nav class="nav-buttons" role="navigation">
            <a href="login.php" class="btn btn-login" aria-label="Entrar na sua conta">Entrar</a>
            <a href="cadastro.php" class="btn btn-signup" aria-label="Criar uma nova conta">Comece Gratuitamente</a>
        </nav>
    </header>

    <main role="main">
        <section class="hero-section" aria-labelledby="hero-title">
            <div class="aurora-bg"></div>
            <div class="hero-container">
                <div class="hero-text">
                    <h2 id="hero-title">Viva o presente e preserve seu legado</h2>
                    <p>Mais que um diário, um santuário digital para sua jornada. Capture, conecte e reviva as memórias que definem quem você é, com beleza e segurança.</p>
                    <a href="#planos" class="btn btn-signup" style="padding: 12px 32px; font-size: 1rem;" aria-label="Descubra os planos disponíveis">Descubra seu plano</a>
                </div>
                <div class="hero-images">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRTBwCVcKHSsjgJEndoIciBFhePIWRcstvpkg&s" alt="Pessoa navegando em uma galeria digital de memórias em um tablet" class="first-image">
                    <img src="https://images.trustinnews.pt/uploads/sites/5/2023/02/230224_GettyImages-129714169-1600x1067.jpg" alt="Pessoa planejando metas em um caderno com um laptop ao lado" class="second-image hidden md:block">
                </div>
            </div>
        </section>

        <section class="timeline-section content-section" aria-labelledby="timeline-title">
            <h2 class="section-title" id="timeline-title">Uma Jornada Extraordinária</h2>
            <p class="section-subtitle">Sua história se desdobra em uma experiência única, onde cada ferramenta foi criada para dar vida e cor ao seu legado.</p>
            <div class="timeline-container">
                <div class="timeline-item left">
                    <div class="timeline-content">
                        <i class="fas fa-book-open" aria-hidden="true"></i>
                        <h3>O Diário da Sua Alma</h3>
                        <p>Vá além das palavras. Anexe fotos, músicas e vídeos que capturam a essência dos momentos.</p>
                    </div>
                </div>
                <div class="timeline-item right">
                    <div class="timeline-content">
                        <i class="fas fa-bullseye" aria-hidden="true"></i>
                        <h3>Conquiste Seus Horizontes</h3>
                        <p>Sonhos se tornam realidade com um bom plano. Defina suas metas e celebre cada vitória no caminho.</p>
                    </div>
                </div>
                <div class="timeline-item left">
                    <div class="timeline-content">
                        <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                        <h3>Mensagens para o Futuro</h3>
                        <p>Envie conselhos, previsões e memórias para o seu "eu" do futuro. Um presente que só o tempo entrega.</p>
                    </div>
                </div>
                <div class="timeline-item right">
                    <div class="timeline-content">
                        <i class="fas fa-wallet" aria-hidden="true"></i>
                        <h3>Organize Sua Vida</h3>
                        <p>Gerencie suas finanças e saúde. Menos preocupação, mais tempo para viver o que realmente importa.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="plans-section content-section" id="planos" aria-labelledby="plans-title">
            <h2 class="section-title" id="plans-title">Um Espaço para Cada História</h2>
            <p class="section-subtitle">Sua jornada é única. Seu plano também deve ser. Todos os nossos planos incluem acesso total às ferramentas, mudando apenas o armazenamento e o nível de suporte.</p>
            <div class="plans-container">
                
                <div class="plan-card">
                    <h3>Essencial</h3>
                    <div class="plan-price">Gratuito</div>
                    <ul class="plan-features">
                        <li><i class="fa-solid fa-database" aria-hidden="true"></i><strong>500 MB</strong> de armazenamento seguro</li>
                        <li><i class="fa-solid fa-book-open" aria-hidden="true"></i>Diário ilimitado com anexos</li>
                        <li><i class="fa-solid fa-photo-film" aria-hidden="true"></i>Upload de fotos, vídeos e áudios</li>
                        <li><i class="fa-solid fa-brain" aria-hidden="true"></i>Organização inteligente e busca</li>
                        <li><i class="fa-solid fa-bullseye" aria-hidden="true"></i>Módulo de Metas e Objetivos</li>
                        <li><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>Cápsulas do Tempo ilimitadas</li>
                        <li><i class="fa-solid fa-headset" aria-hidden="true"></i>Suporte </li>
                    </ul>
                    <a href="cadastro.php" class="btn btn-login" aria-label="Começar com o plano Essencial">Começar Gratuitamente</a>
                </div>

                <div class="plan-card recommended">
                    <div class="popular-badge">Mais Popular</div>
                    <h3>Premium</h3>
                    <div class="plan-price">R$19,90<span>/mês</span></div>
                    
                    <div class="promo-highlight">
                        <span class="promo-title">🎉 OFERTA EXCLUSIVA 🎉</span>
                        <span class="promo-subtitle">Assine o plano anual e ganhe <strong>1 MÊS GRÁTIS!</strong></span>
                    </div>

                    <ul class="plan-features">
                        <li><i class="fa-solid fa-database" aria-hidden="true"></i><strong>10 GB</strong> de armazenamento seguro</li>
                        <li><i class="fa-solid fa-book-open" aria-hidden="true"></i>Diário ilimitado com anexos</li>
                        <li><i class="fa-solid fa-photo-film" aria-hidden="true"></i>Upload de fotos, vídeos e áudios</li>
                        <li><i class="fa-solid fa-brain" aria-hidden="true"></i>Organização inteligente e busca</li>
                        <li><i class="fa-solid fa-bullseye" aria-hidden="true"></i>Módulo de Metas e Objetivos</li>
                        <li><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>Cápsulas do Tempo ilimitadas</li>
                        <li><i class="fa-solid fa-headset" aria-hidden="true"></i><strong>Suporte prioritário</strong> </li>
                    </ul>
                    <a href="cadastro.php" class="btn btn-signup" aria-label="Assinar o plano Premium">Assinar o Premium</a>
                </div>

                <div class="plan-card">
                    <h3>Premium Plus</h3>
                    <div class="plan-price">R$39,90<span>/mês</span></div>
                     <ul class="plan-features">
                        <li><i class="fa-solid fa-database" aria-hidden="true"></i><strong>100 GB</strong> de armazenamento seguro</li>
                        <li><i class="fa-solid fa-book-open" aria-hidden="true"></i>Diário ilimitado com anexos</li>
                        <li><i class="fa-solid fa-photo-film" aria-hidden="true"></i>Upload de fotos, vídeos e áudios</li>
                        <li><i class="fa-solid fa-brain" aria-hidden="true"></i>Organização inteligente e busca</li>
                        <li><i class="fa-solid fa-bullseye" aria-hidden="true"></i>Módulo de Metas e Objetivos</li>
                        <li><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>Cápsulas do Tempo ilimitadas</li>
                        <li><i class="fa-solid fa-star" aria-hidden="true"></i><strong>Suporte 24 Horas</strong> </li>
                    </ul>
                    <a href="cadastro.php" class="btn btn-login" aria-label="Escolher o plano Legado">Assinar Premium Plus</a>
                </div>

            </div>
        </section>
    </main>

   <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Timeline animation
            const timelineItems = document.querySelectorAll('.timeline-item');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.3 });
            timelineItems.forEach(item => observer.observe(item));

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = document.querySelector(anchor.getAttribute('href'));
                    target.scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    </script>
</body>
</html>