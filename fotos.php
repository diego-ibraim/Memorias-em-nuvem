<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// --- CONFIGURAÇÃO DE DIRETÓRIOS ---
$user_main_dir_fs = 'Uploads/' . $_SESSION['user_id'] . '/';
$video_upload_dir_fs = $user_main_dir_fs . 'videos/';
$album_upload_dir_fs = $user_main_dir_fs . 'albuns/';
if (!is_dir($user_main_dir_fs)) @mkdir($user_main_dir_fs, 0777, true);
if (!is_dir($video_upload_dir_fs)) @mkdir($video_upload_dir_fs, 0777, true);
if (!is_dir($album_upload_dir_fs)) @mkdir($album_upload_dir_fs, 0777, true);

// --- FUNÇÕES AUXILIARES ---
function sanitizeFileName($filename) {
    $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return $filename;
}

// CORREÇÃO ERRO 500: Verifica se a função já existe antes de declará-la.
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        return htmlspecialchars(trim($data));
    }
}

function deletePhoto($pdo, $photo_id, $user_id) {
    $stmt = $pdo->prepare("SELECT image_path FROM photos WHERE photo_id = ? AND user_id = ?");
    $stmt->execute([$photo_id, $user_id]);
    $photo = $stmt->fetch();
    if ($photo) {
        $full_path = 'Uploads/' . ltrim($photo['image_path'], '/');
        if (file_exists($full_path)) @unlink($full_path);
        
        $stmt_update_cover = $pdo->prepare("UPDATE photo_albums SET cover_photo_id = NULL WHERE cover_photo_id = ? AND user_id = ?");
        $stmt_update_cover->execute([$photo_id, $user_id]);

        $delete_stmt = $pdo->prepare("DELETE FROM photos WHERE photo_id = ?");
        $delete_stmt->execute([$photo_id]);
        return true;
    }
    return false;
}

// --- PROCESSAMENTO DE POSTS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $msg = '';
    $erro = '';
    
    if (isset($_POST['nova_foto_avulsa']) && isset($_FILES['nova_foto']) && !empty($_FILES['nova_foto']['name'][0])) {
        $photo_title = !empty($_POST['photo_title']) ? sanitizeInput($_POST['photo_title']) : null;
        $photo_description = !empty($_POST['photo_description']) ? sanitizeInput($_POST['photo_description']) : null;
        $photo_capture_date = !empty($_POST['photo_capture_date']) ? $_POST['photo_capture_date'] : null;

        foreach ($_FILES['nova_foto']['tmp_name'] as $key => $tmp) {
            if ($_FILES['nova_foto']['error'][$key] == UPLOAD_ERR_OK) {
                $file_info = ['name' => sanitizeFileName($_FILES['nova_foto']['name'][$key]), 'tmp_name' => $tmp];
                $extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
                $is_video = in_array($extension, ['mp4', 'webm', 'ogg']);
                
                $upload_dir = $is_video ? $video_upload_dir_fs : $album_upload_dir_fs;
                $db_path_prefix = $is_video ? $_SESSION['user_id'] . '/videos/' : $_SESSION['user_id'] . '/albuns/';

                $file_name = uniqid('file_') . '_' . $file_info['name'];
                if (move_uploaded_file($file_info['tmp_name'], $upload_dir . $file_name)) {
                    $db_path = $db_path_prefix . $file_name;
                    $stmt = $pdo->prepare("INSERT INTO photos (user_id, image_path, upload_date, title, description, capture_date) VALUES (?, ?, CURDATE(), ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $db_path, $photo_title, $photo_description, $photo_capture_date]);
                    $msg = "Mídias adicionadas com sucesso!";
                } else { $erro .= "Erro no upload da mídia: " . htmlspecialchars($file_info['name']) . ". "; }
            } else { $erro .= "Erro no upload da mídia: " . htmlspecialchars($_FILES['nova_foto']['name'][$key]) . ". "; }
        }
    }
    elseif (isset($_POST['criar_album'])) {
        try {
            $pdo->beginTransaction();
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

            $stmt = $pdo->prepare("INSERT INTO photo_albums (user_id, title, description, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], sanitizeInput($_POST['nome_album']), sanitizeInput($_POST['descricao']), $start_date, $end_date]);
            $album_id = $pdo->lastInsertId();
            
            if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == UPLOAD_ERR_OK) {
                $file_info = ['name' => sanitizeFileName($_FILES['cover_photo']['name']), 'tmp_name' => $_FILES['cover_photo']['tmp_name']];
                $cover_name = uniqid('cover_') . '_' . $file_info['name'];
                if (move_uploaded_file($file_info['tmp_name'], $album_upload_dir_fs . $cover_name)) {
                    $db_path = $_SESSION['user_id'] . '/albuns/' . $cover_name;
                    $stmt_p = $pdo->prepare("INSERT INTO photos (user_id, image_path, upload_date) VALUES (?, ?, CURDATE())");
                    $stmt_p->execute([$_SESSION['user_id'], $db_path]);
                    $cover_id = $pdo->lastInsertId();
                    $stmt_u = $pdo->prepare("UPDATE photo_albums SET cover_photo_id = ? WHERE album_id = ?");
                    $stmt_u->execute([$cover_id, $album_id]);
                }
            }

            if (isset($_FILES['album_photos']) && !empty($_FILES['album_photos']['name'][0])) {
                foreach ($_FILES['album_photos']['tmp_name'] as $key => $tmp) {
                    if ($_FILES['album_photos']['error'][$key] == UPLOAD_ERR_OK) {
                        $file_info = ['name' => sanitizeFileName($_FILES['album_photos']['name'][$key]),'tmp_name' => $tmp];
                        $extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
                        $is_video = in_array($extension, ['mp4', 'webm', 'ogg']);
                        $upload_dir = $is_video ? $video_upload_dir_fs : $album_upload_dir_fs;
                        $db_path_prefix = $is_video ? $_SESSION['user_id'] . '/videos/' : $_SESSION['user_id'] . '/albuns/';
                        $file_name = uniqid('album_') . '_' . $file_info['name'];
                        if (move_uploaded_file($tmp, $upload_dir . $file_name)) {
                            $db_path = $db_path_prefix . $file_name;
                            $stmt_i = $pdo->prepare("INSERT INTO photos (user_id, image_path, album_id, upload_date) VALUES (?, ?, ?, CURDATE())");
                            $stmt_i->execute([$_SESSION['user_id'], $db_path, $album_id]);
                        } else { $erro .= "Erro no upload da mídia: " . htmlspecialchars($file_info['name']) . ". "; }
                    }
                }
            }
            $pdo->commit();
            $msg = "Álbum criado com sucesso!";
        } catch (Exception $e) { $pdo->rollBack(); $erro = "Erro ao criar o álbum: " . $e->getMessage(); }
    }
    elseif (isset($_POST['excluir_foto'])) {
        if (deletePhoto($pdo, $_POST['photo_id'], $_SESSION['user_id'])) {
            $msg = "Mídia excluída com sucesso!";
        } else { $erro = "Erro ao excluir a mídia."; }
    }
    elseif (isset($_POST['excluir_album'])) {
        try {
            $album_id = $_POST['album_id'];
            $pdo->beginTransaction();
            $stmt_photos = $pdo->prepare("SELECT photo_id FROM photos WHERE album_id = ? AND user_id = ?");
            $stmt_photos->execute([$album_id, $_SESSION['user_id']]);
            foreach ($stmt_photos->fetchAll() as $photo) { deletePhoto($pdo, $photo['photo_id'], $_SESSION['user_id']); }
            $stmt_cover = $pdo->prepare("SELECT cover_photo_id FROM photo_albums WHERE album_id = ?");
            $stmt_cover->execute([$album_id]);
            if ($cover = $stmt_cover->fetch() AND $cover['cover_photo_id']) { deletePhoto($pdo, $cover['cover_photo_id'], $_SESSION['user_id']); }
            $stmt_album = $pdo->prepare("DELETE FROM photo_albums WHERE album_id = ? AND user_id = ?");
            $stmt_album->execute([$album_id, $_SESSION['user_id']]);
            $pdo->commit();
            $msg = "Álbum excluído com sucesso!";
        } catch (Exception $e) { $pdo->rollBack(); $erro = "Erro ao excluir o álbum."; }
    }
    elseif (isset($_POST['editar_album'])) {
        try {
            $pdo->beginTransaction();
            $album_id = $_POST['album_id'];
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $stmt = $pdo->prepare("UPDATE photo_albums SET title = ?, description = ?, start_date = ?, end_date = ? WHERE album_id = ? AND user_id = ?");
            $stmt->execute([sanitizeInput($_POST['nome_album']), sanitizeInput($_POST['descricao']), $start_date, $end_date, $album_id, $_SESSION['user_id']]);

             if (isset($_FILES['nova_capa_album']) && $_FILES['nova_capa_album']['error'] == UPLOAD_ERR_OK) {
                $stmt_old_cover = $pdo->prepare("SELECT cover_photo_id FROM photo_albums WHERE album_id = ?");
                $stmt_old_cover->execute([$album_id]);
                $old_cover = $stmt_old_cover->fetch();
                $file_info = ['name' => sanitizeFileName($_FILES['nova_capa_album']['name']), 'tmp_name' => $_FILES['nova_capa_album']['tmp_name']];
                $cover_name = uniqid('cover_') . '_' . $file_info['name'];
                if (move_uploaded_file($file_info['tmp_name'], $album_upload_dir_fs . $cover_name)) {
                    $db_path = $_SESSION['user_id'] . '/albuns/' . $cover_name;
                    $stmt_p = $pdo->prepare("INSERT INTO photos (user_id, image_path, upload_date) VALUES (?, ?, CURDATE())");
                    $stmt_p->execute([$_SESSION['user_id'], $db_path]);
                    $new_cover_id = $pdo->lastInsertId();
                    $stmt_u = $pdo->prepare("UPDATE photo_albums SET cover_photo_id = ? WHERE album_id = ?");
                    $stmt_u->execute([$new_cover_id, $album_id]);
                    if ($old_cover && $old_cover['cover_photo_id']) {
                        deletePhoto($pdo, $old_cover['cover_photo_id'], $_SESSION['user_id']);
                    }
                } else { $erro .= "Erro no upload da capa. "; }
            }
            
            if (isset($_FILES['novas_fotos_album']) && !empty($_FILES['novas_fotos_album']['name'][0])) {
                foreach ($_FILES['novas_fotos_album']['tmp_name'] as $key => $tmp) {
                    if ($_FILES['novas_fotos_album']['error'][$key] == UPLOAD_ERR_OK) {
                        $file_info = ['name' => sanitizeFileName($_FILES['novas_fotos_album']['name'][$key]), 'tmp_name' => $tmp];
                        $extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
                        $is_video = in_array($extension, ['mp4', 'webm', 'ogg']);
                        $upload_dir = $is_video ? $video_upload_dir_fs : $album_upload_dir_fs;
                        $db_path_prefix = $is_video ? $_SESSION['user_id'] . '/videos/' : $_SESSION['user_id'] . '/albuns/';
                        $file_name = uniqid('album_') . '_' . $file_info['name'];
                        if (move_uploaded_file($tmp, $upload_dir . $file_name)) {
                            $db_path = $db_path_prefix . $file_name;
                            $stmt_i = $pdo->prepare("INSERT INTO photos (user_id, image_path, album_id, upload_date) VALUES (?, ?, ?, CURDATE())");
                            $stmt_i->execute([$_SESSION['user_id'], $db_path, $album_id]);
                        } else { $erro .= "Erro no upload da mídia: " . htmlspecialchars($file_info['name']) . ". "; }
                    }
                }
            }
            $pdo->commit();
            $msg = "Álbum atualizado com sucesso!";
        } catch (Exception $e) { $pdo->rollBack(); $erro = "Erro ao editar o álbum: " . $e->getMessage(); }
    }
    // NOVO: Lógica para mover uma foto avulsa para um álbum
    elseif (isset($_POST['mover_para_album'])) {
        $photo_id = $_POST['photo_id'];
        $album_id = $_POST['album_id'];
        $user_id = $_SESSION['user_id'];

        // Verifica se o álbum de destino pertence ao usuário (segurança)
        $stmt_check = $pdo->prepare("SELECT album_id FROM photo_albums WHERE album_id = ? AND user_id = ?");
        $stmt_check->execute([$album_id, $user_id]);
        if ($stmt_check->fetch()) {
            // Se o álbum é válido, move a foto
            $stmt_move = $pdo->prepare("UPDATE photos SET album_id = ? WHERE photo_id = ? AND user_id = ? AND album_id IS NULL");
            if ($stmt_move->execute([$album_id, $photo_id, $user_id])) {
                $msg = "Mídia movida para o álbum com sucesso!";
            } else {
                $erro = "Não foi possível mover a mídia.";
            }
        } else {
            $erro = "Álbum de destino inválido ou não pertence a você.";
        }
    }

    header("Location: fotos.php?msg=" . urlencode($msg) . "&erro=" . urlencode($erro));
    exit();
}

$msg = $_GET['msg'] ?? '';
$erro = $_GET['erro'] ?? '';

// --- QUERIES PARA EXIBIÇÃO ---
$fotos_recentes_stmt = $pdo->prepare("SELECT p.photo_id, p.image_path, p.title, p.description, p.capture_date FROM photos p LEFT JOIN photo_albums pa ON p.photo_id = pa.cover_photo_id WHERE p.user_id = ? AND p.album_id IS NULL AND pa.album_id IS NULL ORDER BY p.created_at DESC");
$fotos_recentes_stmt->execute([$_SESSION['user_id']]);
$fotos_recentes = $fotos_recentes_stmt->fetchAll();

$albuns_stmt = $pdo->prepare("SELECT pa.*, p.image_path AS cover_image_path FROM photo_albums pa LEFT JOIN photos p ON pa.cover_photo_id = p.photo_id WHERE pa.user_id = ? ORDER BY pa.created_at DESC");
$albuns_stmt->execute([$_SESSION['user_id']]);
$albuns = $albuns_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Galeria - Memórias em Nuvem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'poppins': ['Poppins', 'sans-serif'], 'josefin': ['Josefin Sans', 'sans-serif'] },
                    colors: { 'principal': '#cef1ff', 'acento': '#ffded1', 'texto': '#A0522D', 'titulo': '#A0522D', 'titulo-hover': '#8C4624', 'perigo': '#e57373', 'secundaria': '#a0aec0' }
                }
            }
        }
    </script>
    <style>
        body { background-image: linear-gradient(-45deg, #cef1ff, #ffded1); }
        .modal { display: none; }
        .modal.is-open { display: flex; animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .btn-voltar { position: fixed; bottom: 25px; left: 25px; z-index: 1000; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600; text-decoration: none; background-color: #ffded1; color: #A0522D; border: 1px solid rgba(160, 82, 45, 0.2); border-radius: 10px; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s ease-in-out; }
        .btn-voltar:hover { background-color: #fccab3; color: #8C4624; transform: translateY(-3px) scale(1.05); box-shadow: 0 6px 20px rgba(255, 125, 87, 0.3); }
        
        /* NOVO: Padroniza o thumbnail */
        .media-card-thumbnail {
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .media-card-thumbnail img, .media-card-thumbnail video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .description-content { max-height: 0; overflow: hidden; transition: max-height 0.5s ease-in-out; word-wrap: break-word; }
        .description-content.expanded { max-height: 1000px; }
        #search-results-container { display: none; }

        /* NOVO: Estilo para o visualizador (tamanho real responsivo) */
        #viewer-content img, #viewer-content video {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 0.5rem;
            box-shadow: 0 0 40px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body class="bg-gray-100 font-poppins bg-fixed">
    <?php require_once 'header.php'; ?>

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <h1 class="text-4xl font-bold text-titulo font-josefin mb-8 text-center"><i class="fas fa-images"></i> Minha Galeria</h1>

        <?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

        <div class="bg-white/60 backdrop-blur-lg rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-semibold text-titulo font-josefin flex items-center gap-3 mb-4"><i class="fas fa-search"></i> Pesquisar Mídias e Álbuns</h2>
            <input type="search" id="gallery-search" placeholder="Digite para pesquisar por título, descrição ou data (AAAA-MM-DD)..." class="w-full px-4 py-2 bg-white/70 border border-amber-800/20 rounded-lg focus:ring-titulo focus:border-titulo">
        </div>
        
        <div id="search-results-container" class="mb-8">
            <h2 class="text-2xl font-semibold text-titulo font-josefin mb-6 pb-4 border-b border-amber-800/20"><i class="fas fa-search"></i> Resultados da Pesquisa</h2>
            <div id="search-results-content"></div>
        </div>

        <div class="bg-white/60 backdrop-blur-lg rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-semibold text-titulo font-josefin flex items-center gap-3 mb-4"><i class="fas fa-upload"></i>Adicionar Mídias Avulsas</h2>
            <form method="POST" action="fotos.php" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="photo_title" class="block text-texto text-sm font-medium mb-2">Título (opcional):</label>
                        <input type="text" name="photo_title" id="photo_title" class="w-full px-3 py-2 bg-white/70 border border-amber-800/20 rounded-lg focus:ring-titulo focus:border-titulo">
                    </div>
                    <div>
                        <label for="photo_capture_date" class="block text-texto text-sm font-medium mb-2">Data da Foto (opcional):</label>
                        <input type="date" name="photo_capture_date" id="photo_capture_date" class="w-full px-3 py-2 bg-white/70 border border-amber-800/20 rounded-lg focus:ring-titulo focus:border-titulo">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="photo_description" class="block text-texto text-sm font-medium mb-2">Descrição (opcional):</label>
                    <textarea name="photo_description" id="photo_description" rows="2" class="w-full px-3 py-2 bg-white/70 border border-amber-800/20 rounded-lg focus:ring-titulo focus:border-titulo"></textarea>
                </div>
                 <div class="mb-4">
                    <label for="nova_foto" class="block text-texto text-sm font-medium mb-2">Adicionar arquivos de mídia (aplicará os dados acima a todos):</label>
                    <div class="flex items-center gap-4">
                        <label for="nova_foto" class="cursor-pointer bg-titulo text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-titulo-hover transition-transform transform hover:-translate-y-0.5 flex items-center gap-2">
                            <i class="fas fa-file-video"></i> <span>Escolher Mídia...</span>
                        </label>
                        <input type="file" id="nova_foto" name="nova_foto[]" class="hidden" accept="image/*,video/mp4,video/webm,video/ogg" multiple required onchange="updateFileName(this.id, 'file-name-nova-foto', true)">
                        <span id="file-name-nova-foto" class="text-sm text-gray-500">Nenhuma Mídia selecionada</span>
                    </div>
                </div>
                <button type="submit" name="nova_foto_avulsa" class="bg-titulo text-white font-semibold py-2 px-5 rounded-lg shadow-md hover:bg-titulo-hover transition-transform transform hover:-translate-y-0.5 flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i> Enviar Mídia
                </button>
            </form>
        </div>

        <div class="bg-white/60 backdrop-blur-lg rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-amber-800/20">
                <h2 class="text-2xl font-semibold text-titulo font-josefin flex items-center gap-3 m-0"><i class="fas fa-photo-video"></i>Meus Álbuns</h2>
                <button class="bg-titulo text-white font-semibold py-2 px-5 rounded-lg shadow-md hover:bg-titulo-hover transition-transform transform hover:-translate-y-0.5 flex items-center gap-2" onclick="showModal('novo-album-modal')">
                    <i class="fas fa-plus"></i> Novo Álbum
                </button>
            </div>
            <div id="album-list" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                <?php foreach ($albuns as $album): ?>
                    <div class="searchable-item" data-search-text="<?= strtolower(htmlspecialchars($album['title'] . ' ' . $album['description'] . ' ' . $album['start_date'] . ' ' . $album['end_date'])) ?>">
                        <div class="aspect-square rounded-xl overflow-hidden relative group shadow-md hover:shadow-xl transition-all cursor-pointer" onclick="showModal('album-modal-<?= $album['album_id'] ?>')">
                            <img class="w-full h-full object-cover" src="<?= 'Uploads/' . ltrim($album['cover_image_path'] ?? 'img/placeholder.jpg', '/') ?>" alt="<?= htmlspecialchars($album['title']) ?>">
                            <div class="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-all"></div>
                            <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/70 to-transparent">
                                <h3 class="text-white font-semibold text-sm truncate"><?= htmlspecialchars($album['title']) ?></h3>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center text-white text-4xl opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-eye"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($albuns)): ?> <p class="col-span-full text-center text-gray-500">Nenhum álbum criado ainda.</p> <?php endif; ?>
            </div>
             <p id="no-album-results" class="col-span-full text-center text-gray-500 mt-4" style="display: none;">Nenhum álbum encontrado.</p>
        </div>
        
        <div class="bg-transparent p-0">
            <h2 class="text-2xl font-semibold text-titulo font-josefin flex items-center gap-3 mb-6 pb-4 border-b border-amber-800/20"><i class="fas fa-history"></i>Mídias Avulsas Recentes</h2>
            <div id="media-list" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                <?php foreach ($fotos_recentes as $foto): ?>
                    <?php 
                        $foto_src = 'Uploads/' . ltrim($foto['image_path'], '/');
                        $extension = strtolower(pathinfo($foto['image_path'], PATHINFO_EXTENSION));
                        $is_video = in_array($extension, ['mp4', 'webm', 'ogg']);
                    ?>
                    <div class="searchable-item bg-white rounded-lg shadow-lg overflow-hidden flex flex-col" data-search-text="<?= strtolower(htmlspecialchars($foto['title'] . ' ' . $foto['description'] . ' ' . $foto['capture_date'])) ?>">
                        <div class="media-card-thumbnail cursor-pointer" onclick="openMediaViewer('<?= htmlspecialchars($foto_src) ?>', <?= $is_video ? 'true' : 'false' ?>)">
                            <?php if ($is_video): ?>
                                <video src="<?= htmlspecialchars($foto_src) ?>" muted loop preload="metadata"></video>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($foto_src) ?>" alt="<?= htmlspecialchars($foto['title'] ?? 'Mídia Recente') ?>">
                            <?php endif; ?>
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="font-bold text-lg text-titulo font-josefin"><?= htmlspecialchars($foto['title'] ?: 'Mídia sem título') ?></h3>
                            <?php if ($foto['capture_date']): ?>
                                <p class="text-sm text-gray-500 mb-2"><i class="fas fa-calendar-alt fa-fw mr-1"></i><?= date('d/m/Y', strtotime($foto['capture_date'])) ?></p>
                            <?php endif; ?>
                            
                            <div class="flex-grow">
                                <?php if (!empty($foto['description'])): ?>
                                    <span class="text-sm text-titulo hover:underline cursor-pointer" onclick="toggleDescription(this)">Ver descrição</span>
                                    <div class="description-content mt-2 text-gray-700 text-sm">
                                        <?= nl2br(htmlspecialchars($foto['description'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between">
                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta mídia?');">
                                    <input type="hidden" name="photo_id" value="<?= $foto['photo_id'] ?>">
                                    <button type="submit" name="excluir_foto" class="text-titulo-hover hover:text-red-500 text-2xl" title="Excluir"><i class="fas fa-trash-alt"></i></button>
                                </form>
                                
                                <button class="text-titulo-hover hover:text-green-500 text-2xl" 
                                        onclick="openMoveToAlbumModal(<?= $foto['photo_id'] ?>)" 
                                        title="Mover para Álbum">
                                    <i class="fas fa-folder-plus"></i>
                                </button>
                                <button class="text-titulo-hover hover:text-blue-500 text-2xl" onclick="shareMedia('<?= htmlspecialchars($foto_src) ?>', '<?= htmlspecialchars(addslashes($foto['title'] ?? 'Mídia')) ?>')" title="Compartilhar"><i class="fas fa-share-alt"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($fotos_recentes)): ?> <p class="col-span-full text-center text-gray-500">Nenhuma mídia na galeria principal.</p> <?php endif; ?>
            </div>
             <p id="no-media-results" class="col-span-full text-center text-gray-500 mt-4" style="display: none;">Nenhuma mídia encontrada.</p>
        </div>
    </div>

    <?php foreach ($albuns as $album): ?>
        <div id="album-modal-<?= $album['album_id'] ?>" class="modal fixed inset-0 bg-black/80 z-40 p-4 justify-center items-center">
            <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-2xl p-6 relative max-w-6xl w-full max-h-full flex flex-col">
                <button class="absolute top-2 right-4 text-3xl text-gray-600 hover:text-gray-900" onclick="closeModal('album-modal-<?= $album['album_id'] ?>')">×</button>
                <div class="text-center mb-4">
                    <h2 class="text-3xl font-semibold text-titulo font-josefin"><?= htmlspecialchars($album['title']) ?></h2>
                    <p class="text-gray-600 mt-1"><?= htmlspecialchars($album['description']) ?></p>
                     <?php if($album['start_date']): ?>
                        <p class="text-sm text-gray-500 mt-2"><i class="fas fa-calendar-alt"></i> De: <?= date('d/m/Y', strtotime($album['start_date'])) ?> <?php if($album['end_date']): ?> - Até: <?= date('d/m/Y', strtotime($album['end_date'])) ?> <?php endif; ?></p>
                     <?php endif; ?>
                </div>
                <div class="flex-grow overflow-y-auto p-4 border-t border-b border-amber-800/20">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <?php
                            $fotos_album_stmt = $pdo->prepare("SELECT * FROM photos WHERE album_id = ?");
                            $fotos_album_stmt->execute([$album['album_id']]);
                            $fotos_do_album = $fotos_album_stmt->fetchAll();
                            if (empty($fotos_do_album)) {
                                echo "<p class='col-span-full text-center text-gray-500'>Este álbum está vazio.</p>";
                            } else {
                                foreach ($fotos_do_album as $foto_item) {
                                    $foto_src_album = 'Uploads/' . ltrim($foto_item['image_path'], '/');
                                    $extension = strtolower(pathinfo($foto_item['image_path'], PATHINFO_EXTENSION));
                                    $is_video_album = in_array($extension, ['mp4', 'webm', 'ogg']);
                                    echo '<div class="aspect-square rounded-xl overflow-hidden relative group shadow-md hover:shadow-xl transition-all cursor-pointer" onclick="openMediaViewer(\'' . htmlspecialchars($foto_src_album) . '\', ' . ($is_video_album ? 'true' : 'false') . ')">';
                                    if ($is_video_album) { echo '<video class="w-full h-full object-cover" src="' . htmlspecialchars($foto_src_album) . '" muted preload="metadata"></video>'; } 
                                    else { echo '<img class="w-full h-full object-cover" src="' . htmlspecialchars($foto_src_album) . '" alt="Mídia do álbum">'; }
                                    echo '<div class="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-all flex items-center justify-center text-white text-3xl opacity-0 group-hover:opacity-100"><i class="fas fa-expand"></i></div></div>';
                                }
                            }
                        ?>
                    </div>
                </div>
                <div class="mt-4 flex justify-center flex-wrap gap-4">
                    <button class="bg-blue-500 text-white font-semibold py-2 px-5 rounded-lg shadow-md hover:bg-blue-600" onclick="shareAlbum('<?= $album['album_id'] ?>', '<?= htmlspecialchars(addslashes($album['title'])) ?>')"><i class="fas fa-share-alt"></i> Compartilhar Álbum</button>
                    <button class="bg-gray-500 text-white font-semibold py-2 px-5 rounded-lg shadow-md hover:bg-gray-600" onclick="closeModal('album-modal-<?= $album['album_id'] ?>'); showModal('editar-album-modal-<?= $album['album_id'] ?>');"><i class="fas fa-pencil-alt"></i> Editar</button>
                    <form class="m-0" method="POST" onsubmit="return confirm('Excluir este álbum e TODAS as suas mídias? Essa ação não pode ser desfeita.');">
                        <input type="hidden" name="album_id" value="<?= $album['album_id'] ?>">
                        <button type="submit" name="excluir_album" class="bg-perigo text-white font-semibold py-2 px-5 rounded-lg shadow-md hover:bg-red-600"><i class="fas fa-trash-alt"></i> Excluir</button>
                    </form>
                </div>
            </div>
        </div>

        <div id="editar-album-modal-<?= $album['album_id'] ?>" class="modal fixed inset-0 bg-black/80 z-40 p-4 justify-center items-center">
            <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-2xl p-6 relative max-w-lg w-full max-h-full flex flex-col">
                <button class="absolute top-2 right-4 text-3xl text-gray-600 hover:text-gray-900" onclick="closeModal('editar-album-modal-<?= $album['album_id'] ?>')">×</button>
                <h2 class="text-2xl font-semibold text-titulo font-josefin mb-4">Editar Álbum</h2>
                <form method="POST" action="fotos.php" enctype="multipart/form-data" class="flex-grow overflow-y-auto pr-2">
                    <input type="hidden" name="album_id" value="<?= $album['album_id'] ?>">
                    <div class="mb-4"><label class="block text-texto text-sm font-medium mb-1">Nome:</label><input type="text" name="nome_album" value="<?= htmlspecialchars($album['title']) ?>" required class="w-full px-3 py-2 bg-white/70 border rounded-lg"></div>
                    <div class="mb-4"><label class="block text-texto text-sm font-medium mb-1">Descrição:</label><textarea name="descricao" rows="3" class="w-full px-3 py-2 bg-white/70 border rounded-lg"><?= htmlspecialchars($album['description']) ?></textarea></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-texto text-sm font-medium mb-1">Data de Início:</label><input type="date" name="start_date" value="<?= htmlspecialchars($album['start_date'] ?? '') ?>" class="w-full px-3 py-2 bg-white/70 border rounded-lg"></div>
                        <div><label class="block text-texto text-sm font-medium mb-1">Data de Fim:</label><input type="date" name="end_date" value="<?= htmlspecialchars($album['end_date'] ?? '') ?>" class="w-full px-3 py-2 bg-white/70 border rounded-lg"></div>
                    </div>
                    <div class="mb-4"><label class="block text-texto text-sm font-medium mb-1">Mudar Foto de Capa:</label><div class="flex items-center gap-4"><label for="edit-capa-<?= $album['album_id'] ?>" class="cursor-pointer bg-titulo text-white font-semibold py-2 px-4 rounded-lg hover:bg-titulo-hover"><i class="fas fa-camera"></i> <span>Escolher</span></label><span id="file-name-edit-capa-<?= $album['album_id'] ?>" class="text-sm text-gray-500">Nenhum arquivo</span><input id="edit-capa-<?= $album['album_id'] ?>" type="file" name="nova_capa_album" accept="image/*" class="hidden" onchange="updateFileName(this.id, 'file-name-edit-capa-<?= $album['album_id'] ?>')"></div></div>
                    <div class="mb-4"><label class="block text-texto text-sm font-medium mb-1">Adicionar mais mídias:</label><div class="flex items-center gap-4"><label for="edit-fotos-<?= $album['album_id'] ?>" class="cursor-pointer bg-titulo text-white font-semibold py-2 px-4 rounded-lg hover:bg-titulo-hover"><i class="fas fa-plus"></i> <span>Escolher</span></label><span id="file-name-edit-fotos-<?= $album['album_id'] ?>" class="text-sm text-gray-500">Nenhum arquivo</span><input id="edit-fotos-<?= $album['album_id'] ?>" type="file" name="novas_fotos_album[]" accept="image/*,video/mp4,video/webm,video/ogg" multiple class="hidden" onchange="updateFileName(this.id, 'file-name-edit-fotos-<?= $album['album_id'] ?>', true)"></div></div>
                    <div class="mt-6 flex justify-end gap-4">
                        <button type="button" class="bg-gray-500 text-white font-semibold py-2 px-5 rounded-lg" onclick="closeModal('editar-album-modal-<?= $album['album_id'] ?>');"><i class="fas fa-times"></i> Cancelar</button>
                        <button type="submit" name="editar_album" class="bg-titulo text-white font-semibold py-2 px-5 rounded-lg"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div id="novo-album-modal" class="modal fixed inset-0 bg-black/80 z-40 p-4 justify-center items-center">
        <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-2xl p-6 relative max-w-lg w-full max-h-full flex flex-col">
            <button class="absolute top-2 right-4 text-3xl text-gray-600 hover:text-gray-900" onclick="closeModal('novo-album-modal')">×</button>
            <h2 class="text-2xl font-semibold text-titulo font-josefin mb-4">Criar Novo Álbum</h2>
            <form method="POST" action="fotos.php" enctype="multipart/form-data" class="flex-grow overflow-y-auto pr-2">
                <div class="mb-4"><label class="block text-texto text-sm font-medium mb-1">Nome do Álbum:</label><input type="text" name="nome_album" required class="w-full px-3 py-2 bg-white/70 border rounded-lg"></div>
                <div class="mb-4"><label class="block text-texto text-sm font-medium mb-1">Descrição:</label><textarea name="descricao" rows="3" class="w-full px-3 py-2 bg-white/70 border rounded-lg"></textarea></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div><label class="block text-texto text-sm font-medium mb-1">Data de Início:</label><input type="date" name="start_date" class="w-full px-3 py-2 bg-white/70 border rounded-lg"></div>
                    <div><label class="block text-texto text-sm font-medium mb-1">Data de Fim:</label><input type="date" name="end_date" class="w-full px-3 py-2 bg-white/70 border rounded-lg"></div>
                </div>
                <div class="mb-4"><label class="block text-texto text-sm font-medium mb-1">Foto de Capa:</label><div class="flex items-center gap-4"><label for="novo-capa" class="cursor-pointer bg-titulo text-white font-semibold py-2 px-4 rounded-lg hover:bg-titulo-hover"><i class="fas fa-camera"></i> <span>Escolher</span></label><span id="file-name-novo-capa" class="text-sm text-gray-500">Nenhum arquivo</span><input id="novo-capa" type="file" name="cover_photo" accept="image/*" class="hidden" onchange="updateFileName(this.id, 'file-name-novo-capa')"></div></div>
                <div class="mb-4"><label class="block text-texto text-sm font-medium mb-1">Mídias do Álbum:</label><div class="flex items-center gap-4"><label for="novo-fotos" class="cursor-pointer bg-titulo text-white font-semibold py-2 px-4 rounded-lg hover:bg-titulo-hover"><i class="fas fa-plus"></i> <span>Escolher</span></label><span id="file-name-novo-fotos" class="text-sm text-gray-500">Nenhum arquivo</span><input id="novo-fotos" type="file" name="album_photos[]" accept="image/*,video/mp4,video/webm,video/ogg" multiple class="hidden" onchange="updateFileName(this.id, 'file-name-novo-fotos', true)"></div></div>
                <div class="mt-6 flex justify-end"><button type="submit" name="criar_album" class="bg-titulo text-white font-semibold py-2 px-5 rounded-lg hover:bg-titulo-hover"><i class="fas fa-check"></i> Criar Álbum</button></div>
            </form>
        </div>
    </div>

    <div id="move-to-album-modal" class="modal fixed inset-0 bg-black/80 z-40 p-4 justify-center items-center">
        <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-2xl p-6 relative max-w-lg w-full max-h-full flex flex-col">
            <button class="absolute top-2 right-4 text-3xl text-gray-600 hover:text-gray-900" onclick="closeModal('move-to-album-modal')">×</button>
            <h2 class="text-2xl font-semibold text-titulo font-josefin mb-4">Mover Mídia para um Álbum</h2>
            
            <form method="POST" action="fotos.php">
                <input type="hidden" name="photo_id" id="move-photo-id-input">
                
                <div class="mb-4">
                    <label for="album-select" class="block text-texto text-sm font-medium mb-2">Selecione o Álbum de Destino:</label>
                    <select name="album_id" id="album-select" required class="w-full px-3 py-2 bg-white/70 border border-amber-800/20 rounded-lg focus:ring-titulo focus:border-titulo">
                        <option value="" disabled selected>Escolha um álbum...</option>
                        <?php foreach ($albuns as $album): ?>
                            <option value="<?= $album['album_id'] ?>"><?= htmlspecialchars($album['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="text-sm text-gray-600 mb-4">
                    Ainda não tem o álbum que deseja? <a href="#" onclick="closeModal('move-to-album-modal'); showModal('novo-album-modal'); return false;" class="text-titulo hover:underline">Crie um novo álbum primeiro</a>.
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" name="mover_para_album" class="bg-titulo text-white font-semibold py-2 px-5 rounded-lg hover:bg-titulo-hover flex items-center gap-2">
                        <i class="fas fa-check"></i> Mover Mídia
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="media-viewer-modal" class="modal fixed inset-0 bg-black/90 z-50 p-4 justify-center items-center cursor-pointer" onclick="closeModal('media-viewer-modal')">
        <button class="absolute top-4 right-6 text-4xl text-white hover:text-gray-300 z-10">×</button>
        <div id="viewer-content" class="relative w-full h-full flex items-center justify-center p-4" onclick="event.stopPropagation()">
            <img id="viewer-image" class="hidden">
            <video id="viewer-video" class="hidden" controls controlsList="nodownload"></video>
        </div>
    </div>

    <a href="home.php" class="btn-voltar"><i class="fas fa-arrow-left"></i> Voltar</a>
    
    <?php include 'footer.php'; ?>

    <script>
        function showModal(modalId) { 
            const modal = document.getElementById(modalId);
            if(modal) modal.classList.add('is-open');
        }

        function closeModal(modalId) { 
            const modal = document.getElementById(modalId);
            if(modal) {
                modal.classList.remove('is-open');
                // Pausa vídeos ao fechar o lightbox
                if (modalId === 'media-viewer-modal') {
                    const video = document.getElementById('viewer-video');
                    video.pause();
                    video.src = ""; // Limpa o source para parar o buffering
                    const image = document.getElementById('viewer-image');
                    image.src = "";
                }
            }
        }

        // NOVO: Função para abrir o modal de mover foto e definir o ID da foto
        function openMoveToAlbumModal(photoId) {
            // Coloca o ID da foto no campo escondido do formulário do modal
            document.getElementById('move-photo-id-input').value = photoId;
            // Exibe o modal
            showModal('move-to-album-modal');
        }

        function updateFileName(inputId, spanId, multiple = false) {
            const input = document.getElementById(inputId);
            const span = document.getElementById(spanId);
            if (input.files && input.files.length > 0) {
                if (multiple) { span.textContent = `${input.files.length} arquivo(s) selecionado(s)`; } 
                else { span.textContent = input.files[0].name; }
            } else { span.textContent = 'Nenhum arquivo selecionado'; }
        }

        function toggleDescription(element) {
            const content = element.nextElementSibling;
            content.classList.toggle('expanded');
            element.textContent = content.classList.contains('expanded') ? 'Ocultar descrição' : 'Ver descrição';
        }
        
        async function shareMedia(url, title) {
            try {
                const absoluteUrl = new URL(url, window.location.origin).href;
                const response = await fetch(absoluteUrl);
                if (!response.ok) throw new Error('Erro ao buscar a mídia para compartilhar.');
                const blob = await response.blob();
                const extension = url.split('.').pop().toLowerCase();
                const fileName = `${title.replace(/[^a-z0-9]/gi, '_')}.${extension}`;
                const file = new File([blob], fileName, { type: blob.type });
                
                if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ files: [file], title: title, text: `Veja esta mídia: ${title}` });
                } else {
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                }
            } catch (error) {
                console.error('Erro ao compartilhar mídia:', error);
                alert('Não foi possível compartilhar a mídia. O download pode ter sido iniciado como alternativa.');
            }
        }

        async function shareAlbum(albumId, albumTitle) {
            const modal = document.getElementById(`album-modal-${albumId}`);
            if (!modal) return;
            
            const mediaElements = modal.querySelectorAll('.grid img, .grid video');
            if (mediaElements.length === 0) {
                alert('Este álbum está vazio e não pode ser compartilhado.');
                return;
            }

            const shareButton = modal.querySelector(`button[onclick^="shareAlbum"]`);
            shareButton.disabled = true;
            shareButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparando...';

            try {
                const files = [];
                const fetchPromises = Array.from(mediaElements).map(async (el, index) => {
                    const url = el.src;
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`Falha ao buscar ${url}`);
                    const blob = await response.blob();
                    const extension = url.split('.').pop().toLowerCase();
                    const fileName = `${albumTitle.replace(/[^a-z0-9]/gi, '_')}_${index + 1}.${extension}`;
                    return new File([blob], fileName, { type: blob.type });
                });

                const fileList = await Promise.all(fetchPromises);

                if (navigator.share && navigator.canShare && navigator.canShare({ files: fileList })) {
                    await navigator.share({
                        files: fileList,
                        title: `Álbum: ${albumTitle}`,
                        text: `Veja as mídias do álbum "${albumTitle}"`
                    });
                } else {
                    alert('Seu navegador não suporta o compartilhamento de múltiplos arquivos. Tente compartilhar as mídias individualmente.');
                }
            } catch (error) {
                console.error('Erro ao compartilhar o álbum:', error);
                alert('Ocorreu um erro ao tentar compartilhar o álbum.');
            } finally {
                shareButton.disabled = false;
                shareButton.innerHTML = '<i class="fas fa-share-alt"></i> Compartilhar Álbum';
            }
        }

        function openMediaViewer(src, isVideo) {
            const modal = document.getElementById('media-viewer-modal');
            const imageEl = document.getElementById('viewer-image');
            const videoEl = document.getElementById('viewer-video');

            if (isVideo) {
                imageEl.classList.add('hidden');
                videoEl.classList.remove('hidden');
                videoEl.src = src;
                videoEl.play();
            } else {
                videoEl.classList.add('hidden');
                imageEl.classList.remove('hidden');
                imageEl.src = src;
            }
            showModal('media-viewer-modal');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('gallery-search');
            const searchResultsContainer = document.getElementById('search-results-container');
            const searchResultsContent = document.getElementById('search-results-content');
            
            const originalAlbumList = document.getElementById('album-list');
            const originalMediaList = document.getElementById('media-list');

            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase().trim();

                    if (searchTerm === '') {
                        searchResultsContainer.style.display = 'none';
                        searchResultsContent.innerHTML = '';
                        return;
                    }

                    searchResultsContainer.style.display = 'block';
                    searchResultsContent.innerHTML = '';
                    let resultsFound = 0;

                    // Search Albums
                    const albumHeader = '<h3 class="text-xl font-semibold text-titulo font-josefin mb-4">Álbuns Encontrados</h3>';
                    let albumResultsHTML = '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">';
                    let albumsFound = 0;
                    originalAlbumList.querySelectorAll('.searchable-item').forEach(item => {
                        if (item.dataset.searchText.includes(searchTerm)) {
                            albumResultsHTML += item.outerHTML;
                            albumsFound++;
                        }
                    });
                    albumResultsHTML += '</div>';
                    if (albumsFound > 0) {
                        searchResultsContent.innerHTML += albumHeader + albumResultsHTML;
                        resultsFound += albumsFound;
                    }

                    // Search Media
                    const mediaHeader = '<h3 class="text-xl font-semibold text-titulo font-josefin mt-8 mb-4">Mídias Encontradas</h3>';
                    let mediaResultsHTML = '<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">';
                    let mediaFound = 0;
                    originalMediaList.querySelectorAll('.searchable-item').forEach(item => {
                        if (item.dataset.searchText.includes(searchTerm)) {
                            mediaResultsHTML += item.outerHTML;
                            mediaFound++;
                        }
                    });
                    mediaResultsHTML += '</div>';
                    if (mediaFound > 0) {
                        searchResultsContent.innerHTML += mediaHeader + mediaResultsHTML;
                        resultsFound += mediaFound;
                    }

                    if (resultsFound === 0) {
                        searchResultsContent.innerHTML = '<p class="text-center text-gray-500">Nenhum resultado encontrado para sua busca.</p>';
                    }
                });
            }
        });

        window.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>