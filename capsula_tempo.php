<?php
session_start();

require_once 'config.php';

$userId = $_SESSION['user_id'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_capsule'])) {
    try {
        if (!validateCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Token de segurança inválido. Por favor, tente novamente.");
        }

        $recipient = sanitizeInput($_POST['recipient']);
        $message = sanitizeInput($_POST['message']);
        $openDate = $_POST['open_date'];
        $title = !empty($_POST['title']) ? sanitizeInput($_POST['title']) : null;

        if (empty($recipient) || empty($message) || empty($openDate)) {
            throw new Exception("Destinatário, mensagem e data de abertura são obrigatórios.");
        }
        
        if (strtotime($openDate) <= time()) {
            throw new Exception("A data de abertura deve ser no futuro.");
        }

        $photoPath = null;
        $drawingPath = null;
        $uploadDir = UPLOAD_DIR . 'capsules/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!empty($_FILES['photo']['name'])) {
            $photoPath = uploadFile($_FILES['photo'], $uploadDir);
        }

        if (!empty($_POST['drawing_data'])) {
            if (preg_match('/^data:image\/(\w+);base64,/', $_POST['drawing_data'], $type)) {
                $drawingData = substr($_POST['drawing_data'], strpos($_POST['drawing_data'], ',') + 1);
                $type = strtolower($type[1]);

                if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                    throw new \Exception('Tipo de imagem inválido para o desenho.');
                }
                $drawingData = base64_decode($drawingData);
                if ($drawingData === false) {
                    throw new \Exception('Falha ao decodificar o desenho (base64).');
                }
                
                $drawingPath = 'drawing_' . uniqid() . '.' . $type;
                file_put_contents($uploadDir . $drawingPath, $drawingData);

            } else {
                 throw new \Exception('URI de dados do desenho inválido.');
            }
        }

        $stmt = $pdo->prepare("INSERT INTO time_capsules (user_id, title, recipient, message, open_date, photo_path, drawing_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $recipient, $message, $openDate, $photoPath, $drawingPath]);

        $_SESSION['success_message'] = "Cápsula do tempo criada com sucesso! Ela estará pronta para ser aberta em " . date('d/m/Y', strtotime($openDate)) . ".";
        header("Location: capsula_tempo.php");
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$stmt = $pdo->prepare("
    SELECT * FROM time_capsules 
    WHERE user_id = ? 
    ORDER BY 
        CASE WHEN is_opened = 1 THEN 1 ELSE 0 END, 
        open_date ASC
");
$stmt->execute([$userId]);
$capsules = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM time_capsules WHERE user_id = ? AND open_date <= CURDATE() AND is_opened = 0");
$stmt->execute([$userId]);
$readyToOpenCount = $stmt->fetch()['count'];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cápsula do Tempo - Memórias em Nuvem</title>
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
            max-width: 800px;
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
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;}
        .form-group input[type="text"], .form-group input[type="date"], .form-group textarea {
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
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--cor-titulo);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2);
        }
        textarea { resize: vertical; min-height: 120px; }

        .date-options { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
        .date-options button {
            flex-grow: 1;
            padding: 8px 12px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            color: var(--cor-texto);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .date-options button:hover { background-color: #fff; border-color: var(--cor-titulo); }
        
        .attachments { display: flex; gap: 20px; justify-content: center; margin-top: 10px; flex-wrap: wrap; }
        .attachment-item { text-align: center; }
        
        .attachment-btn {
            background-color: rgba(255,255,255,0.4);
            border: 2px dashed rgba(0,0,0,0.15);
            color: var(--cor-texto);
            width: 120px;
            height: 100px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        .attachment-btn i { font-size: 1.8rem; line-height: 1; }
        .attachment-btn span { font-size: 0.9rem; line-height: 1.2; }
        .attachment-btn:hover { border-color: var(--cor-titulo); background-color: rgba(255,255,255,0.8); }
        
        .preview-container { 
            display: none; 
            text-align: center;
            width: 120px;
            height: 100px;
        }
        .preview-container img { max-width: 120px; max-height: 75px; border-radius: 6px; margin-bottom: 5px; cursor: pointer; border: 1px solid #ddd; }
        .remove-btn { background: #e74c3c; color: white; border: none; padding: 4px 8px; font-size: 11px; border-radius: 15px; cursor: pointer; display: inline-block; margin-top: 5px; }
        .remove-btn:hover { background: #c0392b; }

        .drawing-canvas { display: none; width: 100%; height: 300px; border-radius: 10px; border: 1px solid #ccc; margin-top: 15px; background-color: white; touch-action: none; }
        .drawing-tools { display: none; justify-content: center; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        
        .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px; }
        .form-button {
            background-color: var(--cor-titulo);
            color: white; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 600; cursor: pointer;
            transition: all 0.3s ease; font-size: 1rem; font-family: 'Poppins', sans-serif;
        }
        .form-button:hover { background-color: var(--cor-titulo-hover); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .cancel-button { background-color: #f0f0f0; color: #555; }
        .cancel-button:hover { background-color: #e0e0e0; }
        
        .capsule-item {
            padding: 20px; border-radius: var(--borda-arredondada); margin-bottom: 15px;
            background-color: rgba(255, 255, 255, 0.4); border: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 5px solid; transition: all 0.3s ease;
        }
        .capsule-item:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        .capsule-item.status-locked { border-left-color: #a05a3c; } /* Cor ajustada */
        .capsule-item.status-ready { border-left-color: #ffc107; background-color: rgba(255, 252, 206, 0.6); }
        .capsule-item.status-opened { border-left-color: #55a630; background-color: rgba(231, 255, 218, 0.6); }

        .capsule-item h4 { margin: 0 0 10px 0; color: var(--cor-titulo); font-size: 1.2rem; font-family: 'Josefin Sans', sans-serif; }
        .capsule-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 0.9em; color: var(--cor-titulo-hover); flex-wrap: wrap; gap: 10px;}
        .capsule-status { padding: 4px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: 600; color: #fff; }
        .status-locked .capsule-status { background-color: #a05a3c; } /* Cor ajustada */
        .status-ready .capsule-status { background-color: #ffc107; color: #333; }
        .status-opened .capsule-status { background-color: #55a630; }

        .capsule-content p { margin: 0; }
        .open-button { margin-top: 15px; }

        .capsule-attachments { margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1); }
        .capsule-attachments h5 { margin: 0 0 10px 0; }
        .attachment-previews { display: flex; gap: 15px; flex-wrap: wrap; }

        .alert, .error-message, .success-message {
            padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500;
            border: 1px solid; text-align: center;
        }
        .alert { background-color: #fff3cd; border-color: #ffeeba; color: #856404; }
        .error-message { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .success-message { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }

        .image-modal-overlay { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); justify-content: center; align-items: center; }
        .image-modal-content { max-width: 90%; max-height: 90%; }
        .close-modal-btn { position: absolute; top: 20px; right: 35px; color: white; font-size: 40px; font-weight: bold; cursor: pointer; }
        
        .btn-voltar {
            position: fixed; bottom: 25px; left: 25px; z-index: 1000;
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
            font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600; text-decoration: none;
            background-color: #ffded1; color: var(--cor-titulo); border: 1px solid rgba(160, 82, 45, 0.2);
            border-radius: 10px; cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
        }
        .btn-voltar:hover {
            background-color: #fccab3;
            color: var(--cor-titulo-hover);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 20px rgba(255, 125, 87, 0.3);
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>
    <main>
        <h1 class="page-title"><i class="fas fa-hourglass-half"></i> Cápsula do Tempo</h1>

        <?php if ($readyToOpenCount > 0): ?>
            <div class="alert">
                <i class="fas fa-envelope-open-text"></i> Você tem <strong><?= $readyToOpenCount ?></strong> cápsula(s) pronta(s) para ser(em) aberta(s)!
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-pencil-alt"></i> Crie uma mensagem para o futuro</h2>

            <form method="POST" action="capsula_tempo.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="form-group">
                    <label for="title">Título da cápsula (opcional)</label>
                    <input type="text" id="title" name="title" placeholder="Ex: Expectativas para daqui a 1 ano">
                </div>

                <div class="form-group">
                    <label for="recipient">Para quem é esta mensagem?</label>
                    <input type="text" id="recipient" name="recipient" placeholder="Ex: Para o meu 'eu' do futuro" required>
                </div>

                <div class="form-group">
                    <label for="open_date">Quando esta cápsula poderá ser aberta?</label>
                    <div class="date-options">
                        <button type="button" onclick="setOpenDate(1, 'months')">1 mês</button>
                        <button type="button" onclick="setOpenDate(6, 'months')">6 meses</button>
                        <button type="button" onclick="setOpenDate(1, 'year')">1 ano</button>
                        <button type="button" onclick="setOpenDate(5, 'years')">5 anos</button>
                    </div>
                    <input type="date" id="open_date" name="open_date" required>
                </div>

                <div class="form-group">
                    <label for="message">Sua mensagem</label>
                    <textarea id="message" name="message" placeholder="Escreva aqui seus pensamentos, sonhos e conselhos para o seu 'eu' do futuro..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Anexos (opcional)</label>
                    <div class="attachments">
                        <div class="attachment-item">
                            <label for="photoInput">
                                <div class="attachment-btn" id="photoBtnContainer">
                                    <i class="fas fa-camera-retro"></i>
                                    <span>Adicionar foto</span>
                                </div>
                            </label>
                            <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;">
                            <div class="preview-container" id="photoPreviewContainer">
                                <img id="photoPreview" src="#" alt="Prévia" onclick="openImageModal(this.src)">
                                <button type="button" class="remove-btn" id="removePhotoBtn"><i class="fas fa-times"></i> Remover</button>
                            </div>
                        </div>

                        <div class="attachment-item">
                             <div class="attachment-btn" id="drawingBtn">
                                 <i class="fas fa-paint-brush"></i>
                                 <span>Fazer desenho</span>
                               </div>
                             <div class="preview-container" id="drawingPreviewContainer">
                                 <img id="drawingPreview" src="#" alt="Prévia" onclick="openImageModal(this.src)">
                                 <button type="button" class="remove-btn" id="removeDrawingBtn"><i class="fas fa-times"></i> Remover</button>
                             </div>
                        </div>
                    </div>
                </div>

                <canvas id="drawingCanvas" class="drawing-canvas"></canvas>
                <div id="drawingTools" class="drawing-tools">
                    <button type="button" class="form-button" onclick="finishDrawing()"><i class="fas fa-check"></i> Concluir Desenho</button>
                    <button type="button" class="form-button cancel-button" onclick="cancelDrawing()">Cancelar</button>
                </div>
                <input type="hidden" id="drawing_data" name="drawing_data">

                <div class="form-actions">
                    <button type="reset" class="form-button cancel-button">Limpar</button>
                    <button type="submit" name="send_capsule" class="form-button"><i class="fas fa-paper-plane"></i> Selar Cápsula</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-archive"></i> Minhas Cápsulas</h3>
            
            <?php if (empty($capsules)): ?>
                <p>Você ainda não criou nenhuma cápsula do tempo.</p>
            <?php else: ?>
                <?php foreach ($capsules as $capsule): 
                    $isOpened = $capsule['is_opened'];
                    $isReady = !$isOpened && strtotime($capsule['open_date']) <= time();
                    
                    $statusClass = 'status-locked';
                    if ($isOpened) $statusClass = 'status-opened';
                    if ($isReady) $statusClass = 'status-ready';
                ?>
                    <div class="capsule-item <?= $statusClass ?>">
                        <h4><?= !empty($capsule['title']) ? htmlspecialchars($capsule['title']) : 'Cápsula Sem Título' ?></h4>
                        <div class="capsule-meta">
                            <span><i class="fas fa-user-astronaut"></i> Para: <?= htmlspecialchars($capsule['recipient']) ?></span>
                            <span class="capsule-status">
                                <?php if ($isOpened): ?>
                                    <i class="fas fa-lock-open"></i> Aberta em <?= date('d/m/Y', strtotime($capsule['opened_at'])) ?>
                                <?php elseif ($isReady): ?>
                                    <i class="fas fa-envelope-open"></i> Pronta para abrir!
                                <?php else: ?>
                                    <i class="fas fa-lock"></i> Lacrada até <?= date('d/m/Y', strtotime($capsule['open_date'])) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="capsule-content">
                            <?php if ($isOpened): ?>
                                <p><?= nl2br(htmlspecialchars($capsule['message'])) ?></p>
                                
                                <?php if ($capsule['photo_path'] || $capsule['drawing_path']): ?>
                                    <div class="capsule-attachments">
                                        <h5>Anexos guardados:</h5>
                                        <div class="attachment-previews">
                                            <?php if ($capsule['photo_path']): ?><div class="preview-container" style="display:block;"><img src="uploads/capsules/<?= htmlspecialchars($capsule['photo_path']) ?>" alt="Foto" onclick="openImageModal(this.src)"></div><?php endif; ?>
                                            <?php if ($capsule['drawing_path']): ?><div class="preview-container" style="display:block;"><img src="uploads/capsules/<?= htmlspecialchars($capsule['drawing_path']) ?>" alt="Desenho" onclick="openImageModal(this.src)"></div><?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php elseif ($isReady): ?>
                                <p>Esta mensagem do passado está esperando por você!</p>
                                <a href="abrir_capsula.php?id=<?= $capsule['capsule_id'] ?>" class="form-button open-button"><i class="fas fa-box-open"></i> Abrir Agora</a>
                            <?php else: ?>
                                <p>Esta cápsula permanece selada, guardando suas memórias e expectativas.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    
    <div id="imageModal" class="image-modal-overlay" onclick="closeImageModal()">
        <span class="close-modal-btn" onclick="closeImageModal()">&times;</span>
        <img id="modalImageContent" class="image-modal-content">
    </div>

    <script>
        function setOpenDate(value, unit) {
            const date = new Date();
            if (unit === 'month' || unit === 'months') date.setMonth(date.getMonth() + value);
            if (unit === 'year' || unit === 'years') date.setFullYear(date.getFullYear() + value);
            document.getElementById('open_date').value = date.toISOString().split('T')[0];
        }
        document.getElementById('open_date').min = new Date(Date.now() + 86400000).toISOString().split('T')[0];

        function setupAttachmentPreview(inputId, btnContainerId, previewContainerId, previewElId, removeBtnId, type) {
            const input = document.getElementById(inputId);
            const btnContainer = document.getElementById(btnContainerId);
            const previewContainer = document.getElementById(previewContainerId);
            const previewEl = document.getElementById(previewElId);
            const removeBtn = document.getElementById(removeBtnId);

            input.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    previewEl.src = URL.createObjectURL(file);
                    previewContainer.style.display = 'block';
                    btnContainer.parentElement.style.display = 'none';
                }
            });

            removeBtn.addEventListener('click', function() {
                input.value = '';
                URL.revokeObjectURL(previewEl.src);
                previewEl.src = type === 'image' ? '#' : '';
                previewContainer.style.display = 'none';
                btnContainer.parentElement.style.display = 'block';
            });
        }

        setupAttachmentPreview('photoInput', 'photoBtnContainer', 'photoPreviewContainer', 'photoPreview', 'removePhotoBtn', 'image');

        const drawingBtn = document.getElementById('drawingBtn');
        const drawingCanvas = document.getElementById('drawingCanvas');
        const drawingTools = document.getElementById('drawingTools');
        const drawingData = document.getElementById('drawing_data');
        const drawingPreviewContainer = document.getElementById('drawingPreviewContainer');
        const drawingPreview = document.getElementById('drawingPreview');
        const removeDrawingBtn = document.getElementById('removeDrawingBtn');
        const ctx = drawingCanvas.getContext('2d');
        let isDrawing = false;
        
        drawingBtn.addEventListener('click', function() {
            drawingCanvas.style.display = 'block';
            drawingTools.style.display = 'flex';
            drawingCanvas.width = drawingCanvas.clientWidth;
            drawingCanvas.height = drawingCanvas.clientHeight;
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, drawingCanvas.width, drawingCanvas.height);
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.strokeStyle = 'black';
            drawingBtn.style.display = 'none'; 
        });

        function finishDrawing() {
            const dataUrl = drawingCanvas.toDataURL('image/png');
            drawingData.value = dataUrl;
            drawingPreview.src = dataUrl;
            drawingPreviewContainer.style.display = 'block';
            
            drawingCanvas.style.display = 'none';
            drawingTools.style.display = 'none';
            isDrawing = false;
        }

        function cancelDrawing() {
            drawingCanvas.style.display = 'none';
            drawingTools.style.display = 'none';
            isDrawing = false;
            drawingBtn.style.display = 'flex';
        }

        removeDrawingBtn.addEventListener('click', function() {
            drawingData.value = '';
            drawingPreview.src = '#';
            drawingPreviewContainer.style.display = 'none';
            drawingBtn.style.display = 'flex'; 
        });
        
        function getPos(canvas, evt) {
            const rect = canvas.getBoundingClientRect();
            const clientX = evt.touches ? evt.touches[0].clientX : evt.clientX;
            const clientY = evt.touches ? evt.touches[0].clientY : evt.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        }
        
        function startDrawing(e) { isDrawing = true; [lastX, lastY] = [getPos(drawingCanvas, e).x, getPos(drawingCanvas, e).y]; }
        function draw(e) { if (!isDrawing) return; e.preventDefault(); const pos = getPos(drawingCanvas, e); ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(pos.x, pos.y); ctx.stroke(); [lastX, lastY] = [pos.x, pos.y]; }
        function stopDrawing() { isDrawing = false; }
        
        let lastX, lastY;
        drawingCanvas.addEventListener('mousedown', startDrawing);
        drawingCanvas.addEventListener('mousemove', draw);
        drawingCanvas.addEventListener('mouseup', stopDrawing);
        drawingCanvas.addEventListener('mouseout', stopDrawing);
        drawingCanvas.addEventListener('touchstart', startDrawing);
        drawingCanvas.addEventListener('touchmove', draw);
        drawingCanvas.addEventListener('touchend', stopDrawing);

        const imageModal = document.getElementById('imageModal');
        const modalImageContent = document.getElementById('modalImageContent');
        
        function openImageModal(src) { modalImageContent.src = src; imageModal.style.display = 'flex'; }
        function closeImageModal() { imageModal.style.display = 'none'; }
    </script>
     <?php require_once 'footer.php'; ?>
     <a href="home.php" class="btn-voltar">
         <i class="fas fa-arrow-left"></i> Voltar
     </a>
</body>
</html>