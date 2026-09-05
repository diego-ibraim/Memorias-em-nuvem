<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
requireLogin($pdo);

$userId = $_SESSION['user_id'];
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Ação inválida.'];

    try {
        switch ($_POST['action']) {
            case 'add_transaction':
                $description = sanitizeInput($_POST['description']);
                $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
                $type = in_array($_POST['type'], ['income', 'expense']) ? $_POST['type'] : null;
                $category = sanitizeInput($_POST['category']);
                $date = $_POST['date'];
                
                if ($description && $amount && $type && $category && $date) {
                    $stmt = $pdo->prepare("INSERT INTO financial_transactions (user_id, description, amount, type, transaction_date, category) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $description, $amount, $type, $date, $category]);
                    $response = ['success' => true, 'message' => 'Transação adicionada com sucesso!'];
                } else { $response['message'] = 'Todos os campos são obrigatórios.'; }
                break;

            case 'get_transaction_details':
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM financial_transactions WHERE transaction_id = ? AND user_id = ?");
                    $stmt->execute([$id, $userId]);
                    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($transaction) {
                        $response = ['success' => true, 'data' => $transaction];
                    } else { $response['message'] = 'Transação não encontrada.'; }
                } else { $response['message'] = 'ID inválido.'; }
                break;

            case 'edit_transaction':
                $id = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
                $description = sanitizeInput($_POST['description']);
                $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
                $type = in_array($_POST['type'], ['income', 'expense']) ? $_POST['type'] : null;
                $category = sanitizeInput($_POST['category']);
                $date = $_POST['date'];

                if ($id && $description && $amount && $type && $category && $date) {
                    $stmt = $pdo->prepare("UPDATE financial_transactions SET description = ?, amount = ?, type = ?, category = ?, transaction_date = ? WHERE transaction_id = ? AND user_id = ?");
                    $stmt->execute([$description, $amount, $type, $category, $date, $id, $userId]);
                    $response = ['success' => true, 'message' => 'Transação atualizada com sucesso!'];
                } else { $response['message'] = 'Todos os campos são obrigatórios para edição.'; }
                break;

            case 'get_report':
                $start = $_POST['start_date'];
                $end = $_POST['end_date'];
                $category = $_POST['category'];
                $type = $_POST['type'];

                $sql = "SELECT * FROM financial_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?";
                $params = [$userId, $start, $end];

                // Lógica revertida para funcionar com o <select>
                if ($category !== 'all') {
                    $sql .= " AND category = ?";
                    $params[] = $category;
                }
                if ($type !== 'all') {
                    $sql .= " AND type = ?";
                    $params[] = $type;
                }
                $sql .= " ORDER BY transaction_date DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $transactions = $stmt->fetchAll();
                
                $response = ['success' => true, 'transactions' => $transactions];
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        $response['message'] = 'Erro no servidor: ' . $e->getMessage();
    }
    echo json_encode($response);
    exit();
}

$meses_abreviados = ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"];

$current_month = date('Y-m');
$start_date = $current_month . '-01';
$end_date = date("Y-m-t", strtotime($start_date));

$stmt_summary = $pdo->prepare("SELECT SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income, SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense FROM financial_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?");
$stmt_summary->execute([$userId, $start_date, $end_date]);
$summary = $stmt_summary->fetch();
$total_income = $summary['total_income'] ?? 0;
$total_expense = $summary['total_expense'] ?? 0;
$balance = $total_income - $total_expense;

$stmt_latest = $pdo->prepare("SELECT * FROM financial_transactions WHERE user_id = ? ORDER BY transaction_date DESC, transaction_id DESC LIMIT 5");
$stmt_latest->execute([$userId]);
$latest_transactions = $stmt_latest->fetchAll();

$annual_data = ['labels' => [], 'income' => [], 'expense' => []];
for ($i = 5; $i >= 0; $i--) {
    $month_date = new DateTime("first day of -$i month");
    $month_index = (int)$month_date->format('n') - 1;
    $month_key = $meses_abreviados[$month_index];
    $month_start = $month_date->format('Y-m-01');
    $month_end = $month_date->format('Y-m-t');
    
    $stmt_month_data = $pdo->prepare("SELECT SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income, SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense FROM financial_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?");
    $stmt_month_data->execute([$userId, $month_start, $month_end]);
    $result = $stmt_month_data->fetch();
    
    $annual_data['labels'][] = $month_key;
    $annual_data['income'][] = $result['income'] ?? 0;
    $annual_data['expense'][] = $result['expense'] ?? 0;
}
$annual_chart_json = json_encode($annual_data);

require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro - Memórias em Nuvem</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --cor-principal: #cef1ff; --cor-acento-1: #e7ffda; --cor-acento-2: #ffded1;
            --cor-acento-3: #fffcce; --cor-texto: #A0522D; --cor-titulo: #A0522D;
            --cor-titulo-hover: #8C4624; --cor-despesa: #e57373; --cor-receita: #55a630;
            --sombra-cor: rgba(149, 173, 194, 0.2); --borda-arredondada: 15px;
        }
        html { background: linear-gradient(-45deg, var(--cor-principal), var(--cor-acento-2)); background-attachment: fixed; }
        body { font-family: 'Poppins', sans-serif; color: var(--cor-texto); margin: 0; }
        main { padding: 25px; max-width: 1200px; margin: 0 auto; }
        .page-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 25px; }
        .page-header h1 { font-family: 'Josefin Sans', sans-serif; font-size: 2.2rem; color: var(--cor-titulo); margin: 0; }
        .header-actions button { background: var(--cor-titulo); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; }
        .header-actions button:hover { background-color: var(--cor-titulo-hover); transform: translateY(-2px); }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
        .card { background-color: rgba(255, 255, 255, 0.65); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 25px; border-radius: var(--borda-arredondada); margin-bottom: 25px; box-shadow: 0 8px 32px 0 var(--sombra-cor); border: 1px solid rgba(255, 255, 255, 0.25); }
        .card h2 { margin-top: 0; font-family: 'Josefin Sans', sans-serif; font-weight: 600; color: var(--cor-titulo); font-size: 1.5rem; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(160, 82, 45, 0.2); padding-bottom: 12px; margin-bottom: 20px; }
        .summary-card { text-align: center; }
        .summary-card .icon { font-size: 2.5rem; margin-bottom: 10px; }
        .summary-card h3 { color: var(--cor-texto); font-size: 1rem; margin: 0; font-family: 'Poppins', sans-serif; font-weight: 400; opacity: 0.9; }
        .summary-card p { font-size: 2rem; font-weight: 600; margin: 5px 0 0 0; font-family: 'Josefin Sans', sans-serif; }
        .income { color: var(--cor-receita); } .expense { color: var(--cor-despesa); } .balance { color: var(--cor-titulo); }
        .transaction-list ul { list-style: none; padding: 0; margin: 0; }
        .transaction-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; border-radius: 12px; margin-bottom: 10px; background-color: rgba(255, 255, 255, 0.25); border-left: 5px solid; transition: all 0.3s ease; }
        .transaction-item:hover { transform: translateX(5px); background-color: rgba(255, 255, 255, 0.5); }
        .transaction-item.type-income { border-left-color: var(--cor-receita); } .transaction-item.type-expense { border-left-color: var(--cor-despesa); }
        .t-icon { font-size: 1.8rem; width: 40px; text-align: center; margin-right: 15px; }
        .t-details { flex-grow: 1; }
        .t-details p { margin: 0; line-height: 1.4; }
        .t-desc { font-weight: 600; color: var(--cor-titulo); }
        .t-cat { font-size: 0.85rem; color: var(--cor-texto); opacity: 0.8; }
        .t-amount { font-weight: 600; font-size: 1.2rem; }
        .t-actions { display: flex; gap: 10px; }
        .t-actions button { background: none; border: none; cursor: pointer; color: var(--cor-texto); font-size: 1rem; opacity: 0.6; transition: opacity 0.3s; }
        .t-actions button:hover { opacity: 1; color: var(--cor-titulo-hover); }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; }
        .modal-content { background-color: rgba(239, 247, 251, 0.85); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); margin: auto; padding: 30px; border-radius: var(--borda-arredondada); max-width: 500px; width: 90%; position: relative; border: 1px solid rgba(255,255,255,0.4); box-shadow: 0 8px 32px 0 var(--sombra-cor); }
        .close-btn { color: var(--cor-texto); position: absolute; top: 15px; right: 25px; font-size: 28px; font-weight: bold; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid rgba(0,0,0,0.1); border-radius: 10px; font-family: 'Poppins', sans-serif; box-sizing: border-box; background-color: rgba(255, 255, 255, 0.5); color: var(--cor-texto); transition: all 0.3s ease; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--cor-titulo); background-color: #fff; box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2); }
        .form-button { background-color: var(--cor-titulo); color: white; border: none; padding: 12px 20px; border-radius: 10px; cursor: pointer; font-weight: 600; width: 100%; font-size: 1rem; transition: all 0.3s ease; }
        .form-button:hover { background-color: var(--cor-titulo-hover); }
        #reportResult .transaction-item { background-color: rgba(0,0,0,0.05); }
        .btn-voltar { position: fixed; bottom: 25px; left: 25px; z-index: 1000; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600; text-decoration: none; background-color: var(--cor-acento-2); color: var(--cor-titulo); border: 1px solid rgba(160, 82, 45, 0.2); border-radius: 10px; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s ease-in-out; }
        .btn-voltar:hover { background-color: #fccab3; color: var(--cor-titulo-hover); transform: translateY(-3px) scale(1.05); box-shadow: 0 6px 20px rgba(255, 125, 87, 0.3); }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>
    <main>
        <div class="page-header">
            <h1><i class="fas fa-wallet"></i> Meu Financeiro</h1>
            <div class="header-actions">
                <button onclick="openModal('addTransactionModal')"><i class="fas fa-plus"></i> Nova Transação</button>
                <button onclick="openModal('reportModal')"><i class="fas fa-chart-pie"></i> Relatórios</button>
            </div>
        </div>
        
        <div class="card">
            <h2>Resumo do Mês</h2>
            <div class="grid-container">
                <div class="summary-card">
                    <div class="icon income"><i class="fas fa-arrow-alt-circle-up"></i></div>
                    <h3>Receitas</h3>
                    <p class="income">R$ <?= number_format($total_income, 2, ',', '.') ?></p>
                </div>
                <div class="summary-card">
                    <div class="icon expense"><i class="fas fa-arrow-alt-circle-down"></i></div>
                    <h3>Despesas</h3>
                    <p class="expense">R$ <?= number_format($total_expense, 2, ',', '.') ?></p>
                </div>
                <div class="summary-card">
                    <div class="icon balance"><i class="fas fa-balance-scale"></i></div>
                    <h3>Saldo Atual</h3>
                    <p class="balance">R$ <?= number_format($balance, 2, ',', '.') ?></p>
                </div>
            </div>
        </div>

        <div class="grid-container" style="align-items: flex-start;">
            <div class="card">
                 <h2><i class="fas fa-chart-line"></i> Evolução (Últimos 6 meses)</h2>
                 <canvas id="annualEvolutionChart" style="max-height: 320px;"></canvas>
            </div>

            <div class="card">
                <h2><i class="fas fa-history"></i> Últimas Transações</h2>
                <div class="transaction-list">
                    <ul>
                        <?php if (empty($latest_transactions)): ?><li><p style="text-align: center; opacity: 0.8;">Nenhuma transação encontrada.</p></li><?php else: foreach($latest_transactions as $t): ?>
                            <li class="transaction-item type-<?= $t['type'] ?>">
                                <div class="t-icon <?= $t['type'] ?>"><i class="fas <?= $t['type'] == 'income' ? 'fa-plus-circle' : 'fa-minus-circle' ?>"></i></div>
                                <div class="t-details">
                                    <p class="t-desc"><?= htmlspecialchars($t['description']) ?></p>
                                    <p class="t-cat"><?= htmlspecialchars(ucfirst($t['category'])) ?> - <?= date('d/m/Y', strtotime($t['transaction_date'])) ?></p>
                                </div>
                                <div class="t-amount-actions" style="display: flex; align-items: center; gap: 15px;">
                                     <p class="t-amount <?= $t['type'] ?>"><?= ($t['type'] == 'income' ? '+' : '-') ?> R$ <?= number_format($t['amount'], 2, ',', '.') ?></p>
                                     <div class="t-actions">
                                         <button onclick="openEditModal(<?= $t['transaction_id'] ?>)" title="Editar Transação"><i class="fas fa-pencil-alt"></i></button>
                                     </div>
                                </div>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    
    <div id="addTransactionModal" class="modal"><div class="modal-content"><span class="close-btn" onclick="closeModal('addTransactionModal')">&times;</span><h2><i class="fas fa-plus-circle"></i> Nova Transação</h2><form id="addTransactionForm"><input type="hidden" name="action" value="add_transaction"><div class="form-group"><label for="desc-add">Descrição</label><input id="desc-add" type="text" name="description" required></div><div class="form-group"><label for="amount-add">Valor (R$)</label><input id="amount-add" type="number" step="0.01" name="amount" placeholder="Ex: 50.75" required></div><div class="form-group"><label for="type-add">Tipo</label><select id="type-add" name="type" required><option value="expense">Despesa</option><option value="income">Receita</option></select></div><div class="form-group"><label for="cat-add">Categoria</label><select id="cat-add" name="category" required><option value="outros">Outros</option><option value="mercado">Mercado</option><option value="academia">Academia</option><option value="faculdade">Faculdade</option><option value="casa">Casa</option><option value="transporte">Transporte</option><option value="lazer">Lazer</option><option value="saude">Saúde</option></select></div><div class="form-group"><label for="date-add">Data</label><input id="date-add" type="date" name="date" value="<?= date('Y-m-d')?>" required></div><button type="submit" class="form-button">Salvar</button></form></div></div>

    <div id="editTransactionModal" class="modal"><div class="modal-content"><span class="close-btn" onclick="closeModal('editTransactionModal')">&times;</span><h2><i class="fas fa-pencil-alt"></i> Editar Transação</h2><form id="editTransactionForm"><input type="hidden" name="action" value="edit_transaction"><input type="hidden" id="transaction_id_edit" name="transaction_id"><div class="form-group"><label for="desc-edit">Descrição</label><input id="desc-edit" type="text" name="description" required></div><div class="form-group"><label for="amount-edit">Valor (R$)</label><input id="amount-edit" type="number" step="0.01" name="amount" placeholder="Ex: 50.75" required></div><div class="form-group"><label for="type-edit">Tipo</label><select id="type-edit" name="type" required><option value="expense">Despesa</option><option value="income">Receita</option></select></div><div class="form-group"><label for="cat-edit">Categoria</label><select id="cat-edit" name="category" required><option value="outros">Outros</option><option value="mercado">Mercado</option><option value="academia">Academia</option><option value="faculdade">Faculdade</option><option value="casa">Casa</option><option value="transporte">Transporte</option><option value="lazer">Lazer</option><option value="saude">Saúde</option></select></div><div class="form-group"><label for="date-edit">Data</label><input id="date-edit" type="date" name="date" required></div><button type="submit" class="form-button">Atualizar</button></form></div></div>

    <div id="reportModal" class="modal"><div class="modal-content"><span class="close-btn" onclick="closeModal('reportModal')">&times;</span><h2><i class="fas fa-chart-pie"></i> Relatório de Transações</h2><form id="reportForm"><input type="hidden" name="action" value="get_report"><div class="form-group"><label for="start-report">De:</label><input id="start-report" type="date" name="start_date" value="<?= date('Y-m-01') ?>" required></div><div class="form-group"><label for="end-report">Até:</label><input id="end-report" type="date" name="end_date" value="<?= date('Y-m-t') ?>" required></div><div class="form-group"><label for="type-report">Tipo:</label><select id="type-report" name="type"><option value="all">Todas</option><option value="income">Receitas</option><option value="expense">Despesas</option></select></div><div class="form-group"><label for="cat-report">Categoria</label><select id="cat-report" name="category"><option value="all">Todas</option><option value="outros">Outros</option><option value="mercado">Mercado</option><option value="academia">Academia</option><option value="faculdade">Faculdade</option><option value="casa">Casa</option><option value="transporte">Transporte</option><option value="lazer">Lazer</option><option value="saude">Saúde</option></select></div><button type="submit" class="form-button">Gerar Relatório</button></form><div id="reportResult" style="margin-top:20px;"></div></div></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const textColor = getComputedStyle(document.documentElement).getPropertyValue('--cor-texto');
        const gridColor = 'rgba(160, 82, 45, 0.1)';

        const annualCtx = document.getElementById('annualEvolutionChart').getContext('2d');
        const chartData = <?= $annual_chart_json ?>;
        
        new Chart(annualCtx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                { label: 'Receitas', data: chartData.income, borderColor: 'var(--cor-receita)', backgroundColor: 'rgba(85, 166, 48, 0.2)', fill: true, tension: 0.4 },
                { label: 'Despesas', data: chartData.expense, borderColor: 'var(--cor-despesa)', backgroundColor: 'rgba(229, 115, 115, 0.2)', fill: true, tension: 0.4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } }, x: { ticks: { color: textColor }, grid: { color: gridColor } } }, plugins: { legend: { labels: { color: textColor, font: { family: 'Poppins' } } } } }
        });

        document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if(e.target === m) closeModal(m.id); }));
        
        document.getElementById('addTransactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('financeiro.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) { alert(data.message); location.reload(); } 
                else { alert('Erro: ' + data.message); }
            }).catch(err => console.error('Error:', err));
        });

        document.getElementById('editTransactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('financeiro.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) { alert(data.message); location.reload(); } 
                else { alert('Erro: ' + data.message); }
            }).catch(err => console.error('Error:', err));
        });
        
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('financeiro.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) displayReport(data.transactions);
                else alert('Erro ao gerar relatório: ' + data.message);
            }).catch(err => console.error('Error:', err));
        });
    });

    function displayReport(transactions) {
        const resultDiv = document.getElementById('reportResult');
        let html = `<h3 style="border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 10px;">Transações Encontradas</h3><ul style="list-style:none; padding:0; max-height: 300px; overflow-y: auto;">`;
        if (transactions.length > 0) {
            transactions.forEach(t => {
                const date = new Date(t.transaction_date + 'T03:00:00Z').toLocaleDateString('pt-BR');
                const isIncome = t.type === 'income';
                const amountClass = isIncome ? 'income' : 'expense';
                const typeClass = isIncome ? 'type-income' : 'type-expense';
                const amountSign = isIncome ? '+' : '-';
                const description = t.description.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                const category = t.category.charAt(0).toUpperCase() + t.category.slice(1);

                html += `<li class="transaction-item ${typeClass}">
                            <div class="t-details">
                                <p class="t-desc">${description}</p>
                                <p class="t-cat">${category} em ${date}</p>
                            </div>
                             <div class="t-amount-actions" style="display: flex; align-items: center; gap: 15px;">
                                <p class="t-amount ${amountClass}">${amountSign} R$ ${parseFloat(t.amount).toFixed(2).replace('.',',')}</p>
                                <div class="t-actions">
                                    <button onclick="openEditModal(${t.transaction_id})" title="Editar Transação"><i class="fas fa-pencil-alt"></i></button>
                                </div>
                             </div>
                        </li>`;
            });
        } else {
            html += '<li>Nenhuma transação encontrada para este período/categoria.</li>';
        }
        html += '</ul>';
        resultDiv.innerHTML = html;
    }

    function openEditModal(transactionId) {
        const formData = new FormData();
        formData.append('action', 'get_transaction_details');
        formData.append('id', transactionId);

        fetch('financeiro.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const t = data.data;
                document.getElementById('transaction_id_edit').value = t.transaction_id;
                document.getElementById('desc-edit').value = t.description;
                document.getElementById('amount-edit').value = t.amount;
                document.getElementById('type-edit').value = t.type;
                document.getElementById('cat-edit').value = t.category;
                document.getElementById('date-edit').value = t.transaction_date;
                openModal('editTransactionModal');
            } else {
                alert('Erro ao buscar detalhes da transação: ' + data.message);
            }
        }).catch(err => console.error('Error:', err));
    }

    function openModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
    function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
    </script>

    <a href="home.php" class="btn-voltar">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>

    <?php include 'footer.php'; ?>
</body>
</html>