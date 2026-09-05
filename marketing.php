<?php
// marketing.php - Módulo de Marketing 
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
require_once 'header.php';

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
$data_completa = strftime('%A, %d de %B de %Y', strtotime('today'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing - Memórias em Nuvem</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ESTILO PADRONIZADO PARA CONSISTÊNCIA COM O RESTO DO SITE */
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
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        main {
            padding: 25px;
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px dashed rgba(160, 82, 45, 0.3);
            padding-bottom: 20px;
        }

        .page-header h1 {
            font-family: 'Josefin Sans', sans-serif;
            font-size: 2.5rem;
            color: var(--cor-titulo);
            margin:0;
        }
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(12px);
            padding: 25px;
            border-radius: var(--borda-arredondada);
            box-shadow: 0 8px 32px 0 var(--sombra-cor);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        
        .card h2, .card h3 {
            margin-top: 0;
            font-family: 'Josefin Sans', sans-serif;
            color: var(--cor-titulo);
            border-bottom: 1px solid rgba(160, 82, 45, 0.2);
            padding-bottom: 10px;
            font-size: 1.5rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(160, 82, 45, 0.1);
        }
        
        th {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .btn {
            background-color: var(--cor-titulo);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background-color: var(--cor-titulo-hover);
            transform: translateY(-2px);
        }
        
        .nav-menu {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .nav-menu a {
            color: var(--cor-titulo);
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.4);
            transition: all 0.2s ease;
        }
        
        .nav-menu a:hover {
            background-color: white;
        }
        .nav-menu a.active {
            background-color: var(--cor-titulo);
            color: white;
        }
    </style>
</head>
<body>
    <main>
        <div class="page-header">
            <h1>Módulo de Marketing</h1>
            <p><?php echo ucfirst($data_completa); ?></p>
        </div>
        
        <nav class="nav-menu">
            <a href="marketing.php?pagina=campanhas" class="<?= (!isset($_GET['pagina']) || $_GET['pagina'] == 'campanhas') ? 'active' : '' ?>">Campanhas</a>
            <a href="marketing.php?pagina=leads" class="<?= ($_GET['pagina'] ?? '') == 'leads' ? 'active' : '' ?>">Leads</a>
            <a href="marketing.php?pagina=analise" class="<?= ($_GET['pagina'] ?? '') == 'analise' ? 'active' : '' ?>">Análise</a>
            <a href="marketing.php?pagina=relatorios" class="<?= ($_GET['pagina'] ?? '') == 'relatorios' ? 'active' : '' ?>">Relatórios</a>
        </nav>
        
        <?php
        $pagina = $_GET['pagina'] ?? 'campanhas';
        
        switch($pagina) {
            case 'leads':
                echo '<div class="card">
                        <h2>Gestão de Leads</h2>
                        <table>
                            <thead><tr><th>Nome</th><th>Contato</th><th>Interesse</th><th>Status</th></tr></thead><tbody>';
                try {
                    $stmt = $pdo->prepare("SELECT * FROM leads WHERE usuario_id = ? ORDER BY data_cadastro DESC LIMIT 5");
                    $stmt->execute([$_SESSION['user_id']]);
                    $leads = $stmt->fetchAll();
                    
                    if(count($leads) > 0) {
                        foreach($leads as $lead) { echo "<tr><td>".htmlspecialchars($lead['nome'])."</td><td>".htmlspecialchars($lead['email'])."</td><td>".htmlspecialchars($lead['interesse'])."</td><td>".htmlspecialchars($lead['status'])."</td></tr>"; }
                    } else { echo '<tr><td colspan="4" style="text-align:center; padding: 20px;">Nenhum lead cadastrado</td></tr>'; }
                } catch(PDOException $e) { echo '<tr><td colspan="4">Erro ao carregar leads</td></tr>'; }
                echo '</tbody></table><a href="#" class="btn">Novo Lead</a> <a href="#" class="btn">Ver Todos</a></div>';
                break;
                
            case 'analise':
                echo '<div class="card">
                        <h2>Análise de Mercado</h2>
                        <p><strong>Taxa de conversão:</strong> 12.5%</p>
                        <p><strong>ROI último mês:</strong> 3.2</p>
                        <p><strong>Leads convertidos:</strong> 24</p>
                        <div style="margin-top:20px;"><a href="#" class="btn">Análise Detalhada</a></div>
                      </div>';
                break;
                
            default: // Campanhas
                echo '<div class="card">
                        <h2>Campanhas Ativas</h2>
                        <table>
                            <thead><tr><th>Nome</th><th>Início</th><th>Término</th><th>Orçamento</th></tr></thead><tbody>';
                try {
                    $stmt = $pdo->prepare("SELECT * FROM campanhas WHERE usuario_id = ? AND data_termino >= CURDATE() ORDER BY data_inicio");
                    $stmt->execute([$_SESSION['user_id']]);
                    $campanhas = $stmt->fetchAll();
                    
                    if(count($campanhas) > 0) {
                        foreach($campanhas as $campanha) { echo "<tr><td>".htmlspecialchars($campanha['nome'])."</td><td>".date('d/m/Y', strtotime($campanha['data_inicio']))."</td><td>".date('d/m/Y', strtotime($campanha['data_termino']))."</td><td>R$ ".number_format($campanha['orcamento'], 2, ',', '.')."</td></tr>"; }
                    } else { echo '<tr><td colspan="4" style="text-align:center; padding: 20px;">Nenhuma campanha ativa</td></tr>'; }
                } catch(PDOException $e) { echo '<tr><td colspan="4">Erro ao carregar campanhas</td></tr>'; }
                echo '</tbody></table><a href="#" class="btn">Nova Campanha</a></div>';
        }
        ?>
        
        <div class="cards-container" style="margin-top: 30px;">
            <div class="card">
                <h3>Métricas Rápidas</h3>
                <p><strong>Leads este mês:</strong> 42</p>
                <p><strong>Conversões:</strong> 8</p>
                <p><strong>Taxa de abertura:</strong> 24%</p>
            </div>
            
            <div class="card">
                <h3>Ações Rápidas</h3>
                <a href="#" class="btn" style="display:block; text-align:center; margin-bottom:10px;">Criar Campanha</a>
                <a href="#" class="btn" style="display:block; text-align:center; margin-bottom:10px;">Importar Leads</a>
                <a href="#" class="btn" style="display:block; text-align:center;">Gerar Relatório</a>
            </div>
            
            <div class="card">
                <h3>Próximos Passos</h3>
                <ul>
                    <li>Analisar campanha de Natal</li>
                    <li>Segmentar lista de e-mails</li>
                    <li>Atualizar landing pages</li>
                </ul>
            </div>
        </div>
    </main>
</body>
</html>