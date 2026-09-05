<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

// Verificação de login (essencial para segurança)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- LÓGICA DE PROCESSAMENTO DE FORMULÁRIOS ---

// 1. Adicionar um novo objetivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    $category = $_POST['category'];
    $term = $_POST['term'];

    if (!empty($title) && !empty($deadline) && !empty($category) && !empty($term)) {
        $stmt = $pdo->prepare(
            "INSERT INTO goals (user_id, title, description, deadline, category, term, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$user_id, $title, $description, $deadline, $category, $term]);
        header("Location: objetivo.php?status=added");
        exit();
    }
}

// 2. Atualizar um objetivo existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_goal'])) {
    $goal_id = $_POST['goal_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    $category = $_POST['category'];
    $term = $_POST['term'];

    if (!empty($title) && !empty($deadline) && !empty($goal_id)) {
        $stmt = $pdo->prepare(
            "UPDATE goals SET title = ?, description = ?, deadline = ?, category = ?, term = ?, updated_at = NOW() WHERE goal_id = ? AND user_id = ?"
        );
        $stmt->execute([$title, $description, $deadline, $category, $term, $goal_id, $user_id]);
        header("Location: objetivo.php?status=updated");
        exit();
    }
}

// 3. Marcar um objetivo como concluído
if (isset($_GET['complete'])) {
    $goal_id = $_GET['complete'];
    $stmt = $pdo->prepare("UPDATE goals SET is_completed = 1, updated_at = NOW() WHERE goal_id = ? AND user_id = ?");
    $stmt->execute([$goal_id, $user_id]);
    header("Location: objetivo.php?status=completed");
    exit();
}

// 4. Excluir um objetivo
if (isset($_GET['delete'])) {
    $goal_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM goals WHERE goal_id = ? AND user_id = ?");
    $stmt->execute([$goal_id, $user_id]);
    header("Location: objetivo.php?status=deleted");
    exit();
}


// --- LÓGICA DE BUSCA DE DADOS ---

// Objetivos em andamento
$stmt_ongoing = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND is_completed = 0 ORDER BY deadline ASC");
$stmt_ongoing->execute([$user_id]);
$ongoing_goals = $stmt_ongoing->fetchAll();

// Objetivos concluídos
$stmt_completed = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND is_completed = 1 ORDER BY updated_at DESC");
$stmt_completed->execute([$user_id]);
$completed_goals = $stmt_completed->fetchAll();

require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Objetivos - Memórias em Nuvem</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
        }

        main {
            padding: 25px;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .page-title {
            font-family: 'Josefin Sans', sans-serif;
            font-size: 2.2rem;
            color: var(--cor-titulo);
            text-align: center;
            margin-bottom: 25px;
        }

        .card {
            background-color: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 25px;
            border-radius: var(--borda-arredondada);
            margin-bottom: 30px;
            box-shadow: 0 8px 32px 0 var(--sombra-cor);
            border: 1px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(149, 173, 194, 0.3);
        }

        .card h2, .card h3 {
            margin-top: 0;
            font-family: 'Josefin Sans', sans-serif;
            font-weight: 600;
            color: var(--cor-titulo);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(160, 82, 45, 0.2);
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;}
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.5);
            color: var(--cor-texto);
            transition: all 0.3s ease;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: var(--cor-titulo);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }

        .form-button {
            background-color: var(--cor-titulo);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }
        .form-button:hover { background-color: var(--cor-titulo-hover); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .cancel-button { background-color: #f0f0f0; color: #555; }
        .cancel-button:hover { background-color: #e0e0e0; }
        
        .goal-item {
            background-color: rgba(255, 255, 255, 0.25);
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            transition: all 0.2s ease-in-out;
            border-color: #f59e0b; /* Laranja/dourado para objetivos em andamento */
        }
        .goal-item:hover { transform: translateX(5px); background-color: rgba(255, 255, 255, 0.5); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .goal-item.completed { border-color: #55a630; text-decoration: none; background-color: rgba(231, 255, 218, 0.5); }
        
        .goal-item-content { flex-grow: 1; }
        .goal-item h4 { margin: 0 0 5px 0; color: var(--cor-titulo); font-size: 1rem; font-weight: 600; }
        .goal-item.completed h4 { color: #3a7d29; text-decoration: line-through;}
        .goal-item p { margin: 0; font-size: 0.9rem; color: var(--cor-texto); }
        .goal-item p.description { margin-top: 8px; font-size: 0.9rem; color: #555; font-style: italic;}
        
        .goal-actions { display: flex; gap: 12px; margin-left: 20px; }
        .goal-actions a, .goal-actions button {
            color: var(--cor-titulo); 
            text-decoration: none; 
            font-size: 1.1rem; 
            transition: all 0.2s ease;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }
        .goal-actions a:hover, .goal-actions button:hover { transform: scale(1.2); }
        .goal-actions a.delete-btn { color: #e57373; }
        .goal-actions a.delete-btn:hover { color: #d32f2f; }
        .goal-actions a.complete-btn { color: #55a630; }
        .goal-actions a.complete-btn:hover { color: #388e3c; }

        .no-goals-message { color: var(--cor-texto); text-align: center; padding: 25px; background-color: rgba(255, 255, 255, 0.2); border-radius: 10px;}

        .modal-overlay { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; }
        .modal-content { width: 90%; max-width: 500px; }
        .modal-content .card { margin-bottom: 0; padding: 20px 25px; }
        .modal-content .card h3 { margin-bottom: 15px; }
        .modal-content .form-group { margin-bottom: 10px; }
        .modal-content .form-group label { margin-bottom: 5px; }
        .modal-content .form-grid { gap: 15px; }
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }

        .btn-voltar { position: fixed; bottom: 25px; left: 25px; z-index: 1000; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600; text-decoration: none; background-color: #ffded1; color: var(--cor-titulo); border: 1px solid rgba(160, 82, 45, 0.2); border-radius: 10px; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s ease-in-out; }
        .btn-voltar:hover { background-color: #fccab3; color: var(--cor-titulo-hover); transform: translateY(-3px) scale(1.05); box-shadow: 0 6px 20px rgba(255, 125, 87, 0.3); }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            main { padding: 15px; }
            .goal-item { flex-direction: column; align-items: flex-start; }
            .goal-actions { margin-left: 0; margin-top: 15px; }
        }
    </style>
</head>
<body>
    <main>
        <h1 class="page-title"><i class="fas fa-bullseye"></i> Meus Objetivos</h1>

        <section class="card" id="form">
            <h2>
                <i class="fas fa-plus-circle"></i> Adicionar Novo Objetivo
            </h2>
            <form method="POST" action="objetivo.php">
                <div class="form-group full-width">
                    <label for="title">Título do Objetivo</label>
                    <input type="text" id="title" name="title" value="" required placeholder="Ex: Aprender a programar em PHP">
                </div>

                <div class="form-group full-width">
                    <label for="description">Descrição (opcional)</label>
                    <textarea id="description" name="description" placeholder="Ex: Concluir um curso online e desenvolver um projeto pessoal."></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="deadline">Prazo Final</label>
                        <input type="date" id="deadline" name="deadline" value="" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Categoria</label>
                        <select id="category" name="category" required>
                            <option value="" disabled selected>Selecione...</option>
                            <option value="produtividade">Produtividade</option>
                            <option value="saude">Saúde</option>
                            <option value="pessoal">Pessoal</option>
                            <option value="financeiro">Financeiro</option>
                            <option value="carreira">Carreira</option>
                            <option value="relacionamento">Relacionamento</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="term">Duração</label>
                        <select id="term" name="term" required>
                            <option value="" disabled selected>Selecione...</option>
                            <option value="curto">Curto Prazo</option>
                            <option value="medio">Médio Prazo</option>
                            <option value="longo">Longo Prazo</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <button type="submit" name="add_goal" class="form-button"><i class="fas fa-plus"></i> Adicionar Objetivo</button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2><i class="fas fa-tasks"></i> Objetivos em Andamento</h2>
            <?php if (empty($ongoing_goals)): ?>
                <p class="no-goals-message">Você não tem nenhum objetivo em andamento. Que tal adicionar um novo?</p>
            <?php else: ?>
                <?php foreach ($ongoing_goals as $goal): ?>
                    <div class="goal-item">
                        <div class="goal-item-content">
                            <h4><?= htmlspecialchars($goal['title']) ?></h4>
                            <p><strong>Prazo:</strong> <?= date('d/m/Y', strtotime($goal['deadline'])) ?></p>
                            <?php if (!empty($goal['description'])): ?>
                                <p class="description"><?= nl2br(htmlspecialchars($goal['description'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="goal-actions">
                            <a href="?complete=<?= $goal['goal_id'] ?>" class="complete-btn" title="Marcar como Concluído"><i class="fas fa-check-square"></i></a>
                            <button type="button" title="Editar" 
                                    onclick="abrirModalEdicao(
                                        <?= $goal['goal_id'] ?>, 
                                        '<?= htmlspecialchars(addslashes($goal['title']), ENT_QUOTES) ?>', 
                                        '<?= htmlspecialchars(addslashes($goal['description']), ENT_QUOTES) ?>',
                                        '<?= $goal['deadline'] ?>',
                                        '<?= $goal['category'] ?>',
                                        '<?= $goal['term'] ?>'
                                    )">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <a href="?delete=<?= $goal['goal_id'] ?>" class="delete-btn" onclick="return confirm('Tem certeza que deseja excluir este objetivo?');" title="Excluir"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2><i class="fas fa-check-circle"></i> Objetivos Concluídos</h2>
            <?php if (empty($completed_goals)): ?>
                <p class="no-goals-message">Nenhum objetivo foi concluído ainda.</p>
            <?php else: ?>
                <?php foreach ($completed_goals as $goal): ?>
                     <div class="goal-item completed">
                        <div class="goal-item-content">
                            <h4><?= htmlspecialchars($goal['title']) ?></h4>
                            <p><strong>Concluído em:</strong> <?= date('d/m/Y', strtotime($goal['updated_at'])) ?></p>
                        </div>
                         <div class="goal-actions">
                             <a href="?delete=<?= $goal['goal_id'] ?>" class="delete-btn" onclick="return confirm('Tem certeza que deseja excluir este objetivo?');" title="Excluir"><i class="fas fa-trash-alt"></i></a>
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
                <h3><i class="fas fa-edit"></i> Editar Objetivo</h3>
                <form method="POST" action="objetivo.php">
                    <input type="hidden" id="edit_goal_id" name="goal_id">
                    
                    <div class="form-group full-width">
                        <label for="edit_title">Título do Objetivo</label>
                        <input type="text" id="edit_title" name="title" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="edit_description">Descrição (opcional)</label>
                        <textarea id="edit_description" name="description"></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_deadline">Prazo Final</label>
                            <input type="date" id="edit_deadline" name="deadline" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_category">Categoria</label>
                            <select id="edit_category" name="category" required>
                                <option value="produtividade">Produtividade</option>
                                <option value="saude">Saúde</option>
                                <option value="pessoal">Pessoal</option>
                                <option value="financeiro">Financeiro</option>
                                <option value="carreira">Carreira</option>
                                <option value="relacionamento">Relacionamento</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="edit_term">Duração</label>
                            <select id="edit_term" name="term" required>
                                <option value="curto">Curto Prazo</option>
                                <option value="medio">Médio Prazo</option>
                                <option value="longo">Longo Prazo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="form-button cancel-button" onclick="fecharModalEdicao()">Cancelar</button>
                        <button type="submit" name="update_goal" class="form-button"><i class="fas fa-save"></i> Atualizar Objetivo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        const editModal = document.getElementById('editModal');
        const editGoalIdInput = document.getElementById('edit_goal_id');
        const editTitleInput = document.getElementById('edit_title');
        const editDescriptionInput = document.getElementById('edit_description');
        const editDeadlineInput = document.getElementById('edit_deadline');
        const editCategoryInput = document.getElementById('edit_category');
        const editTermInput = document.getElementById('edit_term');

        function abrirModalEdicao(id, title, description, deadline, category, term) {
            editGoalIdInput.value = id;
            editTitleInput.value = title;
            editDescriptionInput.value = description;
            editDeadlineInput.value = deadline;
            editCategoryInput.value = category;
            editTermInput.value = term;
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