<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/email_service.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_reminder'])) {
        $texto = trim($_POST['texto']);
        $data_hora = $_POST['data_hora'];
        if (!empty($texto) && !empty($data_hora)) {
            $stmt = $pdo->prepare("INSERT INTO lembretes (usuario_id, texto, data) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $texto, $data_hora]);
        }
    }
    elseif (isset($_POST['update_reminder'])) {
        $lembrete_id = $_POST['lembrete_id'];
        $texto = trim($_POST['texto']);
        $data_hora = $_POST['data_hora'];
        if (!empty($texto) && !empty($data_hora) && !empty($lembrete_id)) {
            $stmt = $pdo->prepare("UPDATE lembretes SET texto = ?, data = ? WHERE lembrete_id = ? AND usuario_id = ?");
            $stmt->execute([$texto, $data_hora, $lembrete_id, $user_id]);
        }
    }
    header("Location: lembretes.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $lembrete_id = (int)$_GET['id'];

    if ($action === 'complete') {
        $stmt_info = $pdo->prepare("SELECT l.texto, u.email, u.usuario FROM lembretes l JOIN usuarios u ON l.usuario_id = u.user_id WHERE l.lembrete_id = ? AND l.usuario_id = ?");
        $stmt_info->execute([$lembrete_id, $user_id]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($info) {
            $stmt_update = $pdo->prepare("UPDATE lembretes SET is_completed = 1 WHERE lembrete_id = ? AND usuario_id = ?");
            $stmt_update->execute([$lembrete_id, $user_id]);

            $subject = "✅ Tarefa Concluída: " . htmlspecialchars($info['texto']);
            $content = '<p>Olá, ' . htmlspecialchars($info['usuario']) . '!</p>'
                     . '<p>Excelente notícia! Você concluiu com sucesso a tarefa:</p>'
                     . '<p style="font-size: 18px; font-weight: bold; color: #55a630;">"' . htmlspecialchars($info['texto']) . '"</p>'
                     . '<p>Continue assim, com tudo em dia! 💪</p>';

            $fullHtmlEmail = createEmailTemplate('Tarefa Concluída!', $content);
            sendEmail($info['email'], $info['usuario'], $subject, $fullHtmlEmail);
        }
    }
    elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM lembretes WHERE lembrete_id = ? AND usuario_id = ?");
        $stmt->execute([$lembrete_id, $user_id]);
    }
    
    header("Location: lembretes.php");
    exit();
}

$stmt_pending = $pdo->prepare("SELECT * FROM lembretes WHERE usuario_id = ? AND is_completed = 0 ORDER BY data ASC");
$stmt_pending->execute([$user_id]);
$pending_reminders = $stmt_pending->fetchAll();

$stmt_completed = $pdo->prepare("SELECT * FROM lembretes WHERE usuario_id = ? AND is_completed = 1 ORDER BY data DESC LIMIT 10");
$stmt_completed->execute([$user_id]);
$completed_reminders = $stmt_completed->fetchAll();

require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Lembretes - Memórias em Nuvem</title>
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
        html { background: linear-gradient(-45deg, var(--cor-principal), var(--cor-acento-2)); background-attachment: fixed; }
        body { font-family: 'Poppins', sans-serif; color: var(--cor-texto); margin: 0; padding: 0; }
        main { padding: 25px; max-width: 800px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-title { font-family: 'Josefin Sans', sans-serif; font-size: 2.2rem; color: var(--cor-titulo); text-align: center; margin-bottom: 25px; }
        .card { background-color: rgba(255, 255, 255, 0.65); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 25px; border-radius: var(--borda-arredondada); margin-bottom: 30px; box-shadow: 0 8px 32px 0 var(--sombra-cor); border: 1px solid rgba(255, 255, 255, 0.25); }
        .card h3 { margin-top: 0; font-family: 'Josefin Sans', sans-serif; font-weight: 600; color: var(--cor-titulo); font-size: 1.5rem; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(160, 82, 45, 0.2); padding-bottom: 12px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;}
        .form-group input { width: 100%; padding: 12px; border: 1px solid rgba(0,0,0,0.1); border-radius: 10px; font-family: 'Poppins', sans-serif; box-sizing: border-box; background-color: rgba(255, 255, 255, 0.5); color: var(--cor-texto); transition: all 0.3s ease; }
        .form-group input:focus { outline: none; border-color: var(--cor-titulo); background-color: #fff; box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2); }
        .form-button { background-color: var(--cor-titulo); color: white; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 1rem; font-family: 'Poppins', sans-serif; }
        .form-button:hover { background-color: var(--cor-titulo-hover); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .cancel-button { background-color: #f0f0f0; color: #555; }
        .cancel-button:hover { background-color: #e0e0e0; }
        
        .reminder-item { background-color: rgba(255, 255, 255, 0.25); padding: 15px; border-radius: 8px; border-left: 5px solid; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s ease-in-out; }
        .reminder-item.pending { border-color: var(--cor-acento-3); }
        .reminder-item.overdue { border-color: #e57373; background-color: rgba(255, 222, 209, 0.7); }
        .reminder-item.completed { border-color: #55a630; background-color: rgba(231, 255, 218, 0.5); }
        .reminder-item:hover { transform: translateX(5px); background-color: rgba(255, 255, 255, 0.5); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .reminder-content { flex-grow: 1; }
        .reminder-content p { margin: 0; font-size: 1rem; font-weight: 500; }
        .reminder-date { font-size: 0.85rem; color: var(--cor-titulo-hover); margin-top: 5px; opacity: 0.9; }
        .reminder-item.completed .reminder-content { text-decoration: line-through; color: #3a7d29; }
        .reminder-actions { display: flex; gap: 15px; margin-left: 20px; align-items: center; }
        .reminder-actions a, .reminder-actions button { background: none; border: none; padding: 0; cursor: pointer; color: var(--cor-titulo); text-decoration: none; font-size: 1.2rem; transition: all 0.2s ease; }
        .reminder-actions a:hover, .reminder-actions button:hover { transform: scale(1.2); }
        .reminder-actions a.action-delete, .reminder-actions button.action-delete { color: #e57373; }
        .reminder-actions a.action-delete:hover, .reminder-actions button.action-delete:hover { color: #d32f2f; }
        .reminder-actions a.action-complete, .reminder-actions button.action-complete { color: #55a630; }
        .reminder-actions a.action-complete:hover, .reminder-actions button.action-complete:hover { color: #388e3c; }

        .modal-overlay { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; }
        .modal-content { width: 90%; max-width: 500px; }
        .modal-content .card { margin-bottom: 0; }
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }

        .no-reminders-message { color: var(--cor-texto); text-align: center; padding: 25px; background-color: rgba(255, 255, 255, 0.2); border-radius: 10px;}
        .btn-voltar { position: fixed; bottom: 25px; left: 25px; z-index: 1000; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600; text-decoration: none; background-color: #ffded1; color: var(--cor-titulo); border: 1px solid rgba(160, 82, 45, 0.2); border-radius: 10px; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s ease-in-out; }
        .btn-voltar:hover { background-color: #fccab3; color: var(--cor-titulo-hover); transform: translateY(-3px) scale(1.05); box-shadow: 0 6px 20px rgba(255, 125, 87, 0.3); }
    </style>
</head>
<body>
    <main>
        <h1 class="page-title"><i class="fas fa-bell"></i> Meus Lembretes</h1>

        <section class="card" id="form-add">
            <h3><i class="fas fa-plus-circle"></i> Adicionar Novo Lembrete</h3>
            <form method="POST" action="lembretes.php">
                <div class="form-group">
                    <label for="texto">O que você quer lembrar?</label>
                    <input type="text" id="texto" name="texto" required placeholder="Ex: Ligar para o dentista">
                </div>
                <div class="form-group">
                    <label for="data_hora">Data e Hora</label>
                    <input type="datetime-local" id="data_hora" name="data_hora" required>
                </div>
                <button type="submit" name="add_reminder" class="form-button"><i class="fas fa-plus"></i> Salvar Lembrete</button>
            </form>
        </section>

        <section class="card">
            <h3><i class="fas fa-thumbtack"></i> Pendentes</h3>
            <?php if (empty($pending_reminders)): ?>
                <p class="no-reminders-message">Nenhum lembrete pendente. Você está em dia!</p>
            <?php else: ?>
                <?php foreach ($pending_reminders as $reminder): 
                    $is_overdue = strtotime($reminder['data']) < time();
                    $data_para_input = date('Y-m-d\TH:i', strtotime($reminder['data']));
                ?>
                    <div class="reminder-item <?= $is_overdue ? 'overdue' : 'pending' ?>">
                        <div class="reminder-content">
                            <p><?= htmlspecialchars($reminder['texto']) ?></p>
                            <p class="reminder-date">
                                <i class="fas fa-calendar-alt"></i> <?= date('d/m/Y \à\s H:i', strtotime($reminder['data'])) ?>
                                <?= $is_overdue ? ' (Atrasado)' : '' ?>
                            </p>
                        </div>
                        <div class="reminder-actions">
                            <a href="?action=complete&id=<?= $reminder['lembrete_id'] ?>" class="action-complete" title="Marcar como Concluído"><i class="fas fa-check-circle"></i></a>
                            
                            <button type="button" class="action-edit" title="Editar" 
                                    onclick="abrirModalEdicao(<?= $reminder['lembrete_id'] ?>, '<?= htmlspecialchars(addslashes($reminder['texto']), ENT_QUOTES) ?>', '<?= $data_para_input ?>')">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?action=delete&id=<?= $reminder['lembrete_id'] ?>" class="action-delete" onclick="return confirm('Tem certeza que deseja excluir este lembrete?');" title="Excluir"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        
        <section class="card">
            <h3><i class="fas fa-check-double"></i> Concluídos</h3>
            <?php if (empty($completed_reminders)): ?>
                <p class="no-reminders-message">Nenhum lembrete foi concluído ainda.</p>
            <?php else: ?>
                <?php foreach ($completed_reminders as $reminder): ?>
                    <div class="reminder-item completed">
                        <div class="reminder-content">
                            <p><?= htmlspecialchars($reminder['texto']) ?></p>
                            <p class="reminder-date"><i class="fas fa-calendar-check"></i> Concluído em <?= date('d/m/Y', strtotime($reminder['data'])) ?></p>
                        </div>
                        <div class="reminder-actions">
                            <a href="?action=delete&id=<?= $reminder['lembrete_id'] ?>" class="action-delete" onclick="return confirm('Tem certeza que deseja excluir este lembrete?');" title="Excluir"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <a href="home.php" class="btn-voltar">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </main>

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="card">
                <h3><i class="fas fa-edit"></i> Editar Lembrete</h3>
                <form method="POST" action="lembretes.php">
                    <input type="hidden" id="edit_lembrete_id" name="lembrete_id">
                    
                    <div class="form-group">
                        <label for="edit_texto">O que você quer lembrar?</label>
                        <input type="text" id="edit_texto" name="texto" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_data_hora">Data e Hora</label>
                        <input type="datetime-local" id="edit_data_hora" name="data_hora" required>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="form-button cancel-button" onclick="fecharModalEdicao()">Cancelar</button>
                        <button type="submit" name="update_reminder" class="form-button"><i class="fas fa-save"></i> Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        const editModal = document.getElementById('editModal');
        const editLembreteIdInput = document.getElementById('edit_lembrete_id');
        const editTextoInput = document.getElementById('edit_texto');
        const editDataHoraInput = document.getElementById('edit_data_hora');

        function abrirModalEdicao(id, texto, dataHora) {
            editLembreteIdInput.value = id;
            editTextoInput.value = texto;
            editDataHoraInput.value = dataHora;
            editModal.style.display = 'flex';
        }

        function fecharModalEdicao() {
            editModal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == editModal) {
                fecharModalEdicao();
            }
        }
    </script>
</body>
</html>