<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirect_page = $_POST['redirect_page'] ?? 'visao_geral';
    $userId = $_SESSION['user_id'];

    try {
        switch ($action) {
            case 'add_consulta':
                $stmt = $pdo->prepare("INSERT INTO consultas (user_id, profissional, especialidade, data, local, notas) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $_POST['profissional'], $_POST['especialidade'], $_POST['data'], $_POST['local'], $_POST['notas']]);
                break;
            case 'edit_consulta':
                $stmt = $pdo->prepare("UPDATE consultas SET profissional=?, especialidade=?, data=?, local=?, notas=? WHERE consulta_id=? AND user_id=?");
                $stmt->execute([$_POST['profissional'], $_POST['especialidade'], $_POST['data'], $_POST['local'], $_POST['notas'], $_POST['id'], $userId]);
                break;
            case 'delete_consulta':
                $stmt = $pdo->prepare("DELETE FROM consultas WHERE consulta_id = ? AND user_id = ?");
                $stmt->execute([$_POST['id'], $userId]);
                break;

            case 'add_exame':
                $stmt = $pdo->prepare("INSERT INTO exames (user_id, tipo, data, local, preparo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $_POST['tipo'], $_POST['data'], $_POST['local'], $_POST['preparo']]);
                break;
            case 'edit_exame':
                $stmt = $pdo->prepare("UPDATE exames SET tipo=?, data=?, local=?, preparo=? WHERE exame_id=? AND user_id=?");
                $stmt->execute([$_POST['tipo'], $_POST['data'], $_POST['local'], $_POST['preparo'], $_POST['id'], $userId]);
                break;
            case 'delete_exame':
                 $stmt = $pdo->prepare("DELETE FROM exames WHERE exame_id = ? AND user_id = ?");
                $stmt->execute([$_POST['id'], $userId]);
                break;

            case 'add_medicamento':
                $data_termino = !empty($_POST['data_termino']) ? $_POST['data_termino'] : null;
                $stmt = $pdo->prepare("INSERT INTO medicamentos (user_id, nome, dosagem, frequencia, data_inicio, data_termino) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $_POST['nome'], $_POST['dosagem'], $_POST['frequencia'], $_POST['data_inicio'], $data_termino]);
                break;
            case 'edit_medicamento':
                $data_termino_edit = !empty($_POST['data_termino']) ? $_POST['data_termino'] : null;
                $stmt = $pdo->prepare("UPDATE medicamentos SET nome=?, dosagem=?, frequencia=?, data_inicio=?, data_termino=? WHERE medicamento_id=? AND user_id=?");
                $stmt->execute([$_POST['nome'], $_POST['dosagem'], $_POST['frequencia'], $_POST['data_inicio'], $data_termino_edit, $_POST['id'], $userId]);
                break;
            case 'delete_medicamento':
                $stmt = $pdo->prepare("DELETE FROM medicamentos WHERE medicamento_id = ? AND user_id = ?");
                $stmt->execute([$_POST['id'], $userId]);
                break;
        }
        header("Location: saude.php?pagina=" . $redirect_page);
        exit();
    } catch (Exception $e) {
        die("Erro ao processar a ação. Por favor, tente novamente. Detalhe: " . $e->getMessage());
    }
}


$userId = $_SESSION['user_id'];
$pagina = $_GET['pagina'] ?? 'visao_geral';

$dias_semana = ['Sunday' => 'Domingo', 'Monday' => 'Segunda-feira', 'Tuesday' => 'Terça-feira', 'Wednesday' => 'Quarta-feira', 'Thursday' => 'Quinta-feira', 'Friday' => 'Sexta-feira', 'Saturday' => 'Sábado'];
$meses = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
$dia_semana_en = date('l');
$dia_mes = date('d');
$mes_num = (int)date('n');
$ano = date('Y');
$data_completa = $dias_semana[$dia_semana_en] . ", " . $dia_mes . " de " . $meses[$mes_num] . " de " . $ano;

function fetchData($pdo, $userId, $table) {
    $queries = [
        'consultas' => "SELECT * FROM consultas WHERE user_id = :userId ORDER BY data DESC",
        'exames' => "SELECT * FROM exames WHERE user_id = :userId ORDER BY data DESC",
        'medicamentos' => "SELECT * FROM medicamentos WHERE user_id = :userId ORDER BY data_inicio DESC",
    ];
    if (!isset($queries[$table])) return [];
    
    $stmt = $pdo->prepare($queries[$table]);
    $stmt->execute(['userId' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saúde - Memórias em Nuvem</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --cor-principal: #cef1ff;
            --cor-acento-2: #ffded1;
            --cor-titulo: #A0522D;
            --cor-titulo-hover: #8C4624;
            --cor-texto-comum: #3e2723;
            --sombra-cor: rgba(149, 173, 194, 0.2);
            --borda-arredondada: 15px;
            --cor-perigo: #c62828;
            --cor-perigo-fundo: #ffcdd2;
        }
        html {
            background: linear-gradient(-45deg, var(--cor-principal), var(--cor-acento-2));
            background-attachment: fixed;
        }
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--cor-texto-comum);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
            flex-grow: 1;
            width: 100%;
            box-sizing: border-box;
        }
        h1, h2, h3, th {
            font-family: 'Josefin Sans', sans-serif;
            color: var(--cor-titulo);
        }
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .page-header h1 {
            font-size: 2.8em;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .page-header p {
            font-size: 1.1em;
            color: var(--cor-titulo);
            opacity: 0.9;
            margin-top: 0;
        }
        
        .nav-saude {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 40px;
            background-color: rgba(255, 255, 255, 0.5);
            padding: 12px;
            border-radius: var(--borda-arredondada);
            box-shadow: 0 4px 15px var(--sombra-cor);
        }
        .nav-saude a {
            text-decoration: none;
            color: var(--cor-titulo);
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-saude a:hover {
            background-color: rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
        }
        .nav-saude a.active {
            background-color: var(--cor-titulo);
            color: white;
            box-shadow: 0 4px 10px rgba(160, 82, 45, 0.3);
        }
        
        .widget-card {
            background-color: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 25px;
            border-radius: var(--borda-arredondada);
            margin-bottom: 30px;
            box-shadow: 0 8px 32px 0 var(--sombra-cor);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .widget-header {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(160, 82, 45, 0.2);
            padding-bottom: 15px;
            margin-bottom: 25px;
            gap: 15px;
        }
        .widget-header h2 {
            font-size: 2em;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn {
            padding: 10px 22px; border: none; border-radius: 8px; cursor: pointer;
            font-weight: 600; transition: all 0.2s; font-size: 0.95em;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; justify-content: center; font-family: 'Poppins', sans-serif;
        }
        .btn-principal {
            background-color: var(--cor-titulo); color: white;
            border: 1px solid var(--cor-titulo);
        }
        .btn-principal:hover {
            background-color: var(--cor-titulo-hover);
            border-color: var(--cor-titulo-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(140, 70, 36, 0.2);
        }
        .table-wrapper { overflow-x: auto; }
        .styled-table { width: 100%; border-collapse: collapse; text-align: left; }
        .styled-table th, .styled-table td { padding: 12px 15px; }
        .styled-table thead tr { border-bottom: 2px solid var(--cor-titulo); }
        .styled-table tbody tr { border-bottom: 1px solid rgba(0,0,0,0.08); }
        .styled-table tbody tr:last-of-type { border-bottom: none; }
        .styled-table tbody tr:hover { background-color: rgba(255, 222, 209, 0.4); }
        .actions-cell { text-align: right; }
        .actions-cell form { display: inline-block; margin: 0; }
        .btn-acao { padding: 6px 10px; font-size: 0.85em; margin: 0 2px; border-radius: 6px; }
        .btn-edit { background-color: #f59e0b; color: white; }
        .btn-edit:hover { background-color: #d97706; }
        .btn-delete { background-color: var(--cor-perigo-fundo); color: var(--cor-perigo); border: 1px solid var(--cor-perigo);}
        .btn-delete:hover { background-color: #ffc1c1; }
        
        .modal { display: none; position: fixed; inset: 0; z-index: 50; background-color: rgba(0,0,0,0.7); justify-content: center; align-items: center; padding: 1rem; }
        .modal.flex { display: flex; }
        .modal-content {
            background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(15px);
            border-radius: var(--borda-arredondada); box-shadow: 0 8px 32px 0 var(--sombra-cor);
            padding: 25px; max-width: 500px; width: 100%; position: relative;
            max-height: 90vh; overflow-y: auto;
        }
        .modal-content h2 { text-align:center; font-size: 1.8em; margin-bottom: 20px; }
        .modal-close-btn { position: absolute; top: 10px; right: 15px; font-size: 1.8rem; color: #888; cursor: pointer; border:none; background:none; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--cor-titulo); font-size: 1em; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;
            font-family: 'Poppins', sans-serif; font-size: 0.95rem;
            background-color: rgba(255, 255, 255, 0.7); color: var(--cor-texto-comum);
            box-sizing: border-box;
        }
        
        .btn-voltar {
          position: fixed; bottom: 25px; left: 25px; z-index: 1000;
          display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
          font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600; text-decoration: none;
          background-color: #ffded1; color: var(--cor-titulo);
          border: 1px solid rgba(160, 82, 45, 0.2); border-radius: 10px; cursor: pointer;
          box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s ease-in-out;
        }
        .btn-voltar:hover {
          background-color: #fccab3; color: var(--cor-titulo-hover);
          transform: translateY(-3px) scale(1.05);
          box-shadow: 0 6px 20px rgba(255, 125, 87, 0.3);
        }

        .grid-overview { display: grid; grid-template-columns: 1fr; gap: 30px; }
        .event-list { list-style: none; padding: 0; margin: 0; }
        .event-list li { background-color: rgba(255, 222, 209, 0.5); padding: 12px; border-radius: 8px; margin-bottom: 12px; }
        .event-list li:last-child { margin-bottom: 0; }

        @media (min-width: 768px) {
            .grid-overview { grid-template-columns: repeat(2, 1fr); }
            .widget-header { flex-direction: row; }
        }
        
        @media (max-width: 480px) {
            .modal-content { padding: 20px; }
            .modal-content h2 { font-size: 1.5em; }
            .nav-saude a { padding: 8px 12px; font-size: 0.9em; }
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>
    <main>
        <div class="max-w-7xl mx-auto w-full">
           

            <nav class="nav-saude">
                <a href="saude.php?pagina=visao_geral" class="<?= $pagina == 'visao_geral' ? 'active' : '' ?>"><i class="fas fa-home mr-2"></i>Visão Geral</a>
                <a href="saude.php?pagina=consultas" class="<?= $pagina == 'consultas' ? 'active' : '' ?>"><i class="fas fa-user-md mr-2"></i>Consultas</a>
                <a href="saude.php?pagina=exames" class="<?= $pagina == 'exames' ? 'active' : '' ?>"><i class="fas fa-microscope mr-2"></i>Exames</a>
                <a href="saude.php?pagina=medicamentos" class="<?= $pagina == 'medicamentos' ? 'active' : '' ?>"><i class="fas fa-pills mr-2"></i>Medicamentos</a>
            </nav>

            <?php
            function renderActionButtons($type, $item) {
                $id = $item[$type.'_id'];
                echo '<div class="actions-cell">
                        <a href="#" class="btn btn-acao btn-edit" onclick=\'openEditModal("'.$type.'", '.json_encode($item).')\' title="Editar"><i class="fas fa-edit"></i></a>
                        <form method="POST" onsubmit="return confirm(\'Tem certeza que deseja excluir?\');" style="display:inline;">
                            <input type="hidden" name="action" value="delete_'.$type.'"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="redirect_page" value="'.$type.'s">
                            <button type="submit" class="btn btn-acao btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>';
            }

            switch($pagina):
                case 'consultas':
                    $consultas = fetchData($pdo, $userId, 'consultas');
                    echo '<div class="widget-card">
                            <header class="widget-header">
                                <h2><i class="fas fa-user-md"></i> Minhas Consultas</h2>
                                <button onclick="openModal(\'modal-add-consulta\')" class="btn btn-principal"><i class="fas fa-plus"></i> Agendar Consulta</button>
                            </header>
                            <div class="table-wrapper"><table class="styled-table">
                                <thead><tr><th>Especialidade</th><th>Profissional</th><th>Data</th><th>Local</th><th style="text-align:right;">Ações</th></tr></thead><tbody>';
                    if (empty($consultas)) { echo '<tr><td colspan="5" style="text-align:center; padding: 2rem;">Nenhuma consulta encontrada.</td></tr>'; } 
                    else { foreach($consultas as $item) { echo '<tr><td>'.htmlspecialchars($item['especialidade']).'</td><td>'.htmlspecialchars($item['profissional']).'</td><td style="white-space:nowrap;">'.date('d/m/Y H:i', strtotime($item['data'])).'</td><td>'.htmlspecialchars($item['local']).'</td><td>'; renderActionButtons('consulta', $item); echo '</td></tr>'; } }
                    echo '</tbody></table></div></div>';
                    break;

                case 'exames':
                    $exames = fetchData($pdo, $userId, 'exames');
                    echo '<div class="widget-card">
                            <header class="widget-header">
                                <h2><i class="fas fa-microscope"></i> Meus Exames</h2>
                                <button onclick="openModal(\'modal-add-exame\')" class="btn btn-principal"><i class="fas fa-plus"></i> Agendar Exame</button>
                            </header>
                            <div class="table-wrapper"><table class="styled-table">
                                <thead><tr><th>Tipo</th><th>Data</th><th>Local</th><th style="text-align:right;">Ações</th></tr></thead><tbody>';
                    if (empty($exames)) { echo '<tr><td colspan="4" style="text-align:center; padding: 2rem;">Nenhum exame encontrado.</td></tr>'; }
                    else { foreach($exames as $item) { echo '<tr><td>'.htmlspecialchars($item['tipo']).'</td><td style="white-space:nowrap;">'.date('d/m/Y', strtotime($item['data'])).'</td><td>'.htmlspecialchars($item['local']).'</td><td>'; renderActionButtons('exame', $item); echo '</td></tr>'; } }
                    echo '</tbody></table></div></div>';
                    break;

                case 'medicamentos':
                    $medicamentos = fetchData($pdo, $userId, 'medicamentos');
                    echo '<div class="widget-card">
                            <header class="widget-header">
                                <h2><i class="fas fa-pills"></i> Meus Medicamentos</h2>
                                <button onclick="openModal(\'modal-add-medicamento\')" class="btn btn-principal"><i class="fas fa-plus"></i> Adicionar</button>
                            </header>
                            <div class="table-wrapper"><table class="styled-table">
                                <thead><tr><th>Nome</th><th>Dosagem</th><th>Frequência</th><th>Início</th><th>Término</th><th style="text-align:right;">Ações</th></tr></thead><tbody>';
                    if (empty($medicamentos)) { echo '<tr><td colspan="6" style="text-align:center; padding: 2rem;">Nenhum medicamento encontrado.</td></tr>'; }
                    else { foreach($medicamentos as $item) { echo '<tr><td>'.htmlspecialchars($item['nome']).'</td><td>'.htmlspecialchars($item['dosagem']).'</td><td>'.htmlspecialchars($item['frequencia']).'</td><td style="white-space:nowrap;">'.date('d/m/Y', strtotime($item['data_inicio'])).'</td><td style="white-space:nowrap;">'.($item['data_termino'] ? date('d/m/Y', strtotime($item['data_termino'])) : 'Contínuo').'</td><td>'; renderActionButtons('medicamento', $item); echo '</td></tr>'; } }
                    echo '</tbody></table></div></div>';
                    break;

                default: // visao_geral
                    $stmt_events = $pdo->prepare("(SELECT 'Consulta' as tipo, especialidade as descricao, data FROM consultas WHERE user_id = :userId AND data >= CURDATE()) UNION ALL (SELECT 'Exame' as tipo, tipo as descricao, data FROM exames WHERE user_id = :userId AND data >= CURDATE()) ORDER BY data ASC LIMIT 5");
                    $stmt_events->execute(['userId' => $userId]);
                    $upcoming_events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

                    $stmt_goals = $pdo->prepare("SELECT * FROM goals WHERE user_id = :userId AND category = 'saude' AND is_completed = 0 LIMIT 5");
                    $stmt_goals->execute(['userId' => $userId]);
                    $health_goals = $stmt_goals->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo '<div class="grid-overview">
                            <div class="widget-card"><header class="widget-header"><h2><i class="fas fa-calendar-check"></i> Próximos Eventos</h2></header><ul class="event-list">';
                    if (empty($upcoming_events)) { echo '<li style="text-align:center; padding: 1rem;">Nenhum evento futuro agendado.</li>'; } 
                    else { foreach ($upcoming_events as $event) { echo '<li><strong>'.ucfirst(htmlspecialchars($event['tipo'])).':</strong> '.htmlspecialchars($event['descricao']).' em <strong>'.date('d/m/Y', strtotime($event['data'])).'</strong></li>'; } }
                    echo '</ul></div>
                              <div class="widget-card"><header class="widget-header"><h2><i class="fas fa-bullseye"></i> Metas de Saúde Ativas</h2></header><ul class="event-list">';
                    if (empty($health_goals)) { echo '<li style="text-align:center; padding: 1rem;">Nenhuma meta de saúde ativa.</li>'; } 
                    else { foreach($health_goals as $goal) { echo '<li>'.htmlspecialchars($goal['title']).' - Prazo: <strong>'.date('d/m/Y', strtotime($goal['deadline'])).'</strong></li>'; } }
                    echo '</ul><a href="objetivo.php" class="btn" style="margin-top: 20px; background-color: var(--cor-acento-2); color: var(--cor-titulo); border: 1px solid rgba(160, 82, 45, 0.2);">Ver todas as metas</a></div></div>';
                    break;
            endswitch;
            ?>
        </div>
    </main>
    
    <?php
    function renderModal($type, $action, $title) {
        $fields = [
            'consulta' => [
                ['label' => 'Especialidade', 'name' => 'especialidade', 'type' => 'text', 'required' => true],
                ['label' => 'Profissional', 'name' => 'profissional', 'type' => 'text', 'required' => true],
                ['label' => 'Data e Hora', 'name' => 'data', 'type' => 'datetime-local', 'required' => true],
                ['label' => 'Local', 'name' => 'local', 'type' => 'text'],
                ['label' => 'Notas', 'name' => 'notas', 'type' => 'textarea'],
            ],
            'exame' => [
                ['label' => 'Tipo de Exame', 'name' => 'tipo', 'type' => 'text', 'required' => true],
                ['label' => 'Data', 'name' => 'data', 'type' => 'date', 'required' => true],
                ['label' => 'Local', 'name' => 'local', 'type' => 'text'],
                ['label' => 'Preparo', 'name' => 'preparo', 'type' => 'textarea'],
            ],
            'medicamento' => [
                ['label' => 'Nome', 'name' => 'nome', 'type' => 'text', 'required' => true],
                ['label' => 'Dosagem', 'name' => 'dosagem', 'type' => 'text'],
                ['label' => 'Frequência', 'name' => 'frequencia', 'type' => 'text', 'required' => true, 'placeholder' => 'Ex: 1x ao dia'],
                ['label' => 'Data de Início', 'name' => 'data_inicio', 'type' => 'date', 'required' => true],
                ['label' => 'Data de Término', 'name' => 'data_termino', 'type' => 'date'],
            ]
        ];
        $modalId = "modal-{$action}-{$type}";
        $formIdPrefix = ($action === 'edit' ? "edit-{$type}-" : "");
        echo "<div id='{$modalId}' class='modal'>
                <div class='modal-content'>
                    <button onclick='closeModal(\"{$modalId}\")' class='modal-close-btn'>&times;</button>
                    <h2>{$title}</h2>
                    <form method='POST'>
                        <input type='hidden' name='action' value='{$action}_{$type}'>
                        <input type='hidden' name='redirect_page' value='{$type}s'>";
        if ($action === 'edit') {
            echo "<input type='hidden' name='id' id='edit-{$type}-id'>";
        }
        foreach ($fields[$type] as $field) {
            $required = isset($field['required']) ? 'required' : '';
            $placeholder = isset($field['placeholder']) ? "placeholder='{$field['placeholder']}'" : '';
            $inputId = $formIdPrefix . $field['name'];
            echo "<div class='form-group'><label for='{$inputId}'>{$field['label']}</label>";
            if ($field['type'] === 'textarea') {
                echo "<textarea name='{$field['name']}' id='{$inputId}' rows='3' {$required}></textarea>";
            } else {
                echo "<input type='{$field['type']}' name='{$field['name']}' id='{$inputId}' {$required} {$placeholder}>";
            }
            echo "</div>";
        }
        echo "<button type='submit' class='btn btn-principal' style='width:100%; margin-top:10px;'>Salvar</button>
                    </form>
                </div>
              </div>";
    }
    
    renderModal('consulta', 'add', 'Agendar Nova Consulta');
    renderModal('consulta', 'edit', 'Editar Consulta');
    renderModal('exame', 'add', 'Agendar Novo Exame');
    renderModal('exame', 'edit', 'Editar Exame');
    renderModal('medicamento', 'add', 'Adicionar Medicamento');
    renderModal('medicamento', 'edit', 'Editar Medicamento');
    ?>

    <a href="home.php" class="btn-voltar">
       <i class="fas fa-arrow-left"></i> Voltar
    </a>

<script>
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('flex');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('flex');
    }
}

function openEditModal(type, itemData) {
    if (itemData.data && itemData.data.includes(' ')) {
        itemData.data = itemData.data.replace(' ', 'T');
    }

    if (type === 'consulta') {
        document.getElementById('edit-consulta-id').value = itemData.consulta_id;
        document.getElementById('edit-consulta-especialidade').value = itemData.especialidade;
        document.getElementById('edit-consulta-profissional').value = itemData.profissional;
        document.getElementById('edit-consulta-data').value = itemData.data;
        document.getElementById('edit-consulta-local').value = itemData.local;
        document.getElementById('edit-consulta-notas').value = itemData.notas;
        openModal('modal-edit-consulta');
    } else if (type === 'exame') {
        document.getElementById('edit-exame-id').value = itemData.exame_id;
        document.getElementById('edit-exame-tipo').value = itemData.tipo;
        document.getElementById('edit-exame-data').value = itemData.data;
        document.getElementById('edit-exame-local').value = itemData.local;
        document.getElementById('edit-exame-preparo').value = itemData.preparo;
        openModal('modal-edit-exame');
    } else if (type === 'medicamento') {
        document.getElementById('edit-medicamento-id').value = itemData.medicamento_id;
        document.getElementById('edit-medicamento-nome').value = itemData.nome;
        document.getElementById('edit-medicamento-dosagem').value = itemData.dosagem;
        document.getElementById('edit-medicamento-frequencia').value = itemData.frequencia;
        document.getElementById('edit-medicamento-data_inicio').value = itemData.data_inicio;
        document.getElementById('edit-medicamento-data_termino').value = itemData.data_termino;
        openModal('modal-edit-medicamento');
    }
}

window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal.flex');
    modals.forEach(modal => {
        if (event.target == modal) {
            closeModal(modal.id);
        }
    });
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>