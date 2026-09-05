<?php
require_once 'header.php';
require_once 'config.php';
requireLogin();

$capsuleId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM time_capsules WHERE capsule_id = ? AND user_id = ?");
$stmt->execute([$capsuleId, $_SESSION['user_id']]);
$capsule = $stmt->fetch();

if (!$capsule) {
    $_SESSION['error_message'] = "Cápsula não encontrada ou você não tem permissão para acessá-la.";
    redirect('capsula_tempo.php');
}

if ($capsule['is_opened']) {
    $_SESSION['error_message'] = "Esta cápsula já foi aberta.";
    redirect('capsula_tempo.php');
}

if (strtotime($capsule['open_date']) > time()) {
    $_SESSION['error_message'] = "Esta cápsula só pode ser aberta após " . date('d/m/Y', strtotime($capsule['open_date']));
    redirect('capsula_tempo.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open_capsule'])) {
    try {
        $stmt = $pdo->prepare("UPDATE time_capsules SET is_opened = 1, opened_at = CURRENT_TIMESTAMP WHERE capsule_id = ?");
        $stmt->execute([$capsuleId]);

        $_SESSION['success_message'] = "Cápsula aberta com sucesso!";
        redirect('capsula_tempo.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir Cápsula do Tempo - MeuDiário Online</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Josefin Sans', sans-serif;
            background-color: #fff0eb;
            color: #5f6368;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #ffe5b4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
        }

        .logout {
            background-color: #3e7c90;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        main {
            max-width: 700px;
            margin: auto;
            padding: 20px;
        }

        .surprise-box {
            background-color: #ede3ff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }

        .surprise-box h2 {
            margin-bottom: 10px;
        }

        .surprise-box p {
            margin-bottom: 20px;
        }

        .capsule-content {
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .capsule-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #777;
        }

        .capsule-message {
            margin: 20px 0;
            line-height: 1.6;
        }

        .attachments {
            margin-top: 20px;
        }

        .attachment {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 15px;
            vertical-align: top;
        }

        .attachment img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
        }

        audio {
            width: 100%;
            max-width: 300px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .later-btn {
            background-color: #e0e0e0;
        }

        .open-btn {
            background-color: #3e7c90;
            color: white;
        }

        footer {
            background-color: #3e7c90;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <header>
        <span>Suas memórias, guardadas no céu da sua história</span>
        <div>
            <a href="logout.php" class="logout">logout</a>
            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Usuário" style="border-radius: 50%; width: 32px; margin-left: 10px;">
        </div>
    </header>

    <main>
        <div class="surprise-box">
            <h2>🎁 Surpresa!!</h2>
            <p>Você está prestes a abrir uma carta do passado</p>
            <p>Escrita em <?= date('d/m/Y', strtotime($capsule['created_at'])) ?> para ser aberta em <?= date('d/m/Y', strtotime($capsule['open_date'])) ?></p>
            <p>Está pronto para ver o que <strong>você do passado</strong> escreveu?</p>
        </div>

        <div class="capsule-content">
            <div class="capsule-meta">
                <span>Para: <?= htmlspecialchars($capsule['recipient']) ?></span>
                <span>Escrita em: <?= date('d/m/Y', strtotime($capsule['created_at'])) ?></span>
            </div>
            
            <?php if (!empty($capsule['title'])): ?>
                <h3><?= htmlspecialchars($capsule['title']) ?></h3>
            <?php endif; ?>
            
            <div class="capsule-message">
                <?= nl2br(htmlspecialchars($capsule['message'])) ?>
            </div>
            
            <?php if ($capsule['photo_path'] || $capsule['audio_path'] || $capsule['drawing_path']): ?>
                <div class="attachments">
                    <h4>Anexos:</h4>
                    <?php if ($capsule['photo_path']): ?>
                        <div class="attachment">
                            <img src="uploads/capsules/<?= htmlspecialchars($capsule['photo_path']) ?>" alt="Foto">
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($capsule['audio_path']): ?>
                        <div class="attachment">
                            <audio controls>
                                <source src="uploads/capsules/<?= htmlspecialchars($capsule['audio_path']) ?>" type="audio/mpeg">
                                Seu navegador não suporta o elemento de áudio.
                            </audio>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($capsule['drawing_path']): ?>
                        <div class="attachment">
                            <img src="uploads/capsules/<?= htmlspecialchars($capsule['drawing_path']) ?>" alt="Desenho">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" action="abrir_capsula.php?id=<?= $capsuleId ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="actions">
                <button type="button" class="later-btn" onclick="window.location.href='capsula_tempo.php'">Depois</button>
                <button type="submit" name="open_capsule" class="open-btn">Confirmar Abertura</button>
            </div>
        </form>
    </main>

    <footer>
        © 2025 MeuDiário Online. Todos os direitos reservados.
    </footer>
</body>
</html>