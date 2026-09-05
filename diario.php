<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

// Ativar exibição de erros para depuração - DESABILITE EM PRODUÇÃO
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Garante que o diretório 'uploads/' existe
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true) or die('Erro ao criar diretório uploads/');
}

$disallowedExtensions = [];
$audioExtensions = ['mp3', 'wav', 'aac', 'ogg', 'm4a', 'flac', 'wma'];

requireLogin($pdo);

function validateUrl($url) {
    if (empty($url)) return null;
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
}

// Sanitizador específico para conteúdo HTML rico gerado pelo editor
function sanitizeHtmlContent($html) {
    $allowedTags = '<p><br><b><strong><i><em><u><s><h1><h2><h3><h4><h5><h6><blockquote><pre><code><ul><ol><li><a><img><span><div>';
    $cleanHtml = strip_tags($html, $allowedTags);
    // Remove scripts embutidos ou atributos perigosos por regex
    return preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $cleanHtml);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entry'])) {
    try {
        if (!function_exists('validateCsrfToken') || !validateCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Token de segurança inválido. Tente novamente.");
        }
        $title = !empty($_POST['title']) ? sanitizeInput($_POST['title']) : null;
        $content = sanitizeHtmlContent($_POST['content']);
        $spotifyLink = validateUrl($_POST['spotify_link']);
        $youtubeLink = validateUrl($_POST['youtube_link']);
        $entryDate = !empty($_POST['entry_date']) ? $_POST['entry_date'] : date('Y-m-d');
        $attachedFilePaths = [];

        if (empty(trim(strip_tags($content, '<img>')))) { 
            throw new Exception("O conteúdo é obrigatório."); 
        }
        if (!strtotime($entryDate)) { throw new Exception("Data do registro inválida."); }
        
        if (!empty($_FILES['attached_files']['name'][0])) {
            $maxAudioFileSize = 3 * 1024 * 1024;
            $maxGeneralFileSize = 20 * 1024 * 1024;

            foreach ($_FILES['attached_files']['name'] as $key => $name) {
                $fileTmpName = $_FILES['attached_files']['tmp_name'][$key];
                $fileSize = $_FILES['attached_files']['size'][$key];
                $fileError = $_FILES['attached_files']['error'][$key];
                $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if ($fileError !== UPLOAD_ERR_OK) {
                    throw new Exception("Erro no upload do arquivo " . htmlspecialchars($name) . ". Código: " . $fileError);
                }
                
                if (!empty($disallowedExtensions) && in_array($fileExtension, $disallowedExtensions)) {
                    throw new Exception("Arquivos do tipo '." . htmlspecialchars($fileExtension) . "' não são permitidos por motivos de segurança.");
                }

                if (in_array($fileExtension, $audioExtensions)) {
                    if ($fileSize > $maxAudioFileSize) {
                        throw new Exception("O arquivo de áudio '" . htmlspecialchars($name) . "' excede o tamanho máximo de 3MB.");
                    }
                } else {
                    if ($fileSize > $maxGeneralFileSize) {
                        throw new Exception("O arquivo '" . htmlspecialchars($name) . "' excede o tamanho máximo de 20MB.");
                    }
                }
                
                $newFileName = uniqid('file_') . '.' . $fileExtension;
                $destination = $uploadDir . $newFileName;
                if (!move_uploaded_file($fileTmpName, $destination)) {
                    throw new Exception("Falha ao fazer upload do arquivo " . htmlspecialchars($name) . ".");
                }
                $attachedFilePaths[] = 'uploads/' . $newFileName;
            }
        }
        
        $attachedFilePathsString = !empty($attachedFilePaths) ? implode(',', $attachedFilePaths) : null;
        
        $stmt = $pdo->prepare(
            "INSERT INTO diary_entries (user_id, title, content, entry_date, attached_files_path, spotify_link, youtube_link) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $title, $content, $entryDate, $attachedFilePathsString, $spotifyLink, $youtubeLink]);
        
        $_SESSION['success_message'] = "Registro salvo com sucesso!";
        header('Location: diario.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_entry'])) {
    try {
        if (!function_exists('validateCsrfToken') || !validateCsrfToken($_POST['csrf_token'])) { throw new Exception("Token de segurança inválido. Tente novamente."); }
        $entryId = (int)$_POST['entry_id'];
        $stmt = $pdo->prepare("SELECT attached_files_path FROM diary_entries WHERE entry_id = ? AND user_id = ?");
        $stmt->execute([$entryId, $_SESSION['user_id']]);
        $entry = $stmt->fetch();
        if ($entry) {
            if (!empty($entry['attached_files_path'])) {
                $attachedFiles = explode(',', $entry['attached_files_path']);
                foreach ($attachedFiles as $filePath) {
                    $trimmedPath = trim($filePath);
                    if (file_exists($trimmedPath)) { unlink($trimmedPath); }
                }
            }
            $stmt = $pdo->prepare("DELETE FROM diary_entries WHERE entry_id = ? AND user_id = ?");
            $stmt->execute([$entryId, $_SESSION['user_id']]);
            $_SESSION['success_message'] = "Registro excluído com sucesso!";
            header('Location: diario.php');
            exit;
        } else {
            throw new Exception("Registro não encontrado ou você não tem permissão para excluí-lo.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_entry'])) {
    try {
        if (!function_exists('validateCsrfToken') || !validateCsrfToken($_POST['csrf_token'])) { throw new Exception("Token de segurança inválido. Tente novamente."); }
        $entryId = (int)$_POST['entry_id'];
        $title = !empty($_POST['title']) ? sanitizeInput($_POST['title']) : null;
        $content = sanitizeHtmlContent($_POST['content']);
        $spotifyLink = validateUrl($_POST['spotify_link']);
        $youtubeLink = validateUrl($_POST['youtube_link']);
        $entryDate = !empty($_POST['entry_date']) ? $_POST['entry_date'] : date('Y-m-d');

        if (empty(trim(strip_tags($content, '<img>')))) { 
            throw new Exception("O conteúdo é obrigatório."); 
        }
        if (!strtotime($entryDate)) { throw new Exception("Data do registro inválida."); }
        
        $stmt = $pdo->prepare("SELECT attached_files_path FROM diary_entries WHERE entry_id = ? AND user_id = ?");
        $stmt->execute([$entryId, $_SESSION['user_id']]);
        $existingEntry = $stmt->fetch();
        $existingFiles = ($existingEntry && !empty($existingEntry['attached_files_path'])) ? explode(',', $existingEntry['attached_files_path']) : [];
        
        $newAttachedFilePaths = [];
        if (!empty($_FILES['attached_files']['name'][0])) {
            $maxAudioFileSize = 3 * 1024 * 1024;
            $maxGeneralFileSize = 20 * 1024 * 1024;

            foreach ($_FILES['attached_files']['name'] as $key => $name) {
                $fileTmpName = $_FILES['attached_files']['tmp_name'][$key];
                $fileSize = $_FILES['attached_files']['size'][$key];
                $fileError = $_FILES['attached_files']['error'][$key];
                $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if ($fileError !== UPLOAD_ERR_OK) {
                    throw new Exception("Erro no upload do arquivo " . htmlspecialchars($name) . ". Código: " . $fileError);
                }
                
                if (!empty($disallowedExtensions) && in_array($fileExtension, $disallowedExtensions)) {
                    throw new Exception("Arquivos do tipo '." . htmlspecialchars($fileExtension) . "' não são permitidos por motivos de segurança.");
                }

                if (in_array($fileExtension, $audioExtensions)) {
                    if ($fileSize > $maxAudioFileSize) {
                        throw new Exception("O arquivo de áudio '" . htmlspecialchars($name) . "' excede o tamanho máximo de 3MB.");
                    }
                } else {
                    if ($fileSize > $maxGeneralFileSize) {
                        throw new Exception("O arquivo '" . htmlspecialchars($name) . "' excede o tamanho máximo de 20MB.");
                    }
                }
                
                $newFileName = uniqid('file_') . '.' . $fileExtension;
                $destination = $uploadDir . $newFileName;
                if (!move_uploaded_file($fileTmpName, $destination)) {
                    throw new Exception("Falha ao fazer upload do arquivo " . htmlspecialchars($name) . ".");
                }
                $newAttachedFilePaths[] = 'uploads/' . $newFileName;
            }
        }
        
        $attachedFilePathsString = !empty($newAttachedFilePaths) ? implode(',', $newAttachedFilePaths) : (!empty($existingFiles) ? implode(',', $existingFiles) : null);
        
        $stmt = $pdo->prepare(
            "UPDATE diary_entries SET title = ?, content = ?, entry_date = ?, attached_files_path = ?, spotify_link = ?, youtube_link = ?
             WHERE entry_id = ? AND user_id = ?"
        );
        $stmt->execute([$title, $content, $entryDate, $attachedFilePathsString, $spotifyLink, $youtubeLink, $entryId, $_SESSION['user_id']]);
        
        if (!empty($newAttachedFilePaths) && !empty($existingFiles)) {
            foreach ($existingFiles as $oldFilePath) {
                $trimmedOldPath = trim($oldFilePath);
                if (file_exists($trimmedOldPath) && !in_array($trimmedOldPath, $newAttachedFilePaths)) { unlink($trimmedOldPath); }
            }
        }
        $_SESSION['success_message'] = "Registro atualizado com sucesso!";
        header('Location: diario.php?view=' . $entryId);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once 'header.php';

function getYoutubeEmbed($url) {
    if (!$url) return '';
    preg_match('/(https?:\/\/(?:www\.|m\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11}))/i', $url, $matches);
    if (isset($matches[2])) {
        $videoID = $matches[2];
        return '<div class="responsive-embed"><iframe src="https://www.youtube.com/embed/' . $videoID . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
    }
    return '';
}

function getSpotifyEmbed($url) {
    if (!$url) return '';
    preg_match('/(https?:\/\/open\.spotify\.com\/(track|album|playlist|episode)\/([a-zA-Z0-9]{22}))/i', $url, $matches);
    if (isset($matches[3])) {
        $type = $matches[2];
        $id = $matches[3];
        $height = ($type === 'album' || $type === 'playlist') ? '352' : '152';
        return '<div class="spotify-embed"><iframe style="border-radius:12px" src="https://open.spotify.com/embed/' . $type . '/' . $id . '?utm_source=generator" width="100%" height="' . $height . '" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe></div>';
    }
    return '';
}

$viewEntryId = isset($_GET['view']) ? (int)$_GET['view'] : null;
$viewEntry = null;
if ($viewEntryId) {
    $stmt = $pdo->prepare("SELECT entry_id, user_id, title, content, entry_date, attached_files_path, spotify_link, youtube_link FROM diary_entries WHERE entry_id = ? AND user_id = ?");
    $stmt->execute([$viewEntryId, $_SESSION['user_id']]);
    $viewEntry = $stmt->fetch();
}

$baseSql = "SELECT entry_id, title, entry_date, content, attached_files_path FROM diary_entries WHERE user_id = ? ORDER BY entry_date DESC";
$params = [$_SESSION['user_id']];
$stmt = $pdo->prepare($baseSql);
$stmt->execute($params);
$entries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Quill.js Estilos + Resizer de Imagem -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    
    <title>Diário - Memórias em Nuvem</title>
    <style>
        :root {
            --cor-principal: #cef1ff;
            --cor-acento-1: #e7ffda;
            --cor-acento-2: #ffded1;
            --cor-acento-3: #fffcce;
            --cor-texto: #A0522D;
            --cor-titulo: #A0522D;
            --cor-titulo-hover: #8C4624;
            --cor-texto-comum: #5d5c61;
            --sombra-cor: rgba(149, 173, 194, 0.2);
            --borda-arredondada: 15px;
            --overlay-bg: rgba(0, 0, 0, 0.8);
            --modal-bg: #fff;
            --cor-perigo: #c62828;
        }
        
        .media-embed-container { margin-top: 25px; display: flex; flex-direction: column; gap: 25px; }
        .responsive-embed { 
            position: relative; overflow: hidden; padding-top: 56.25%;
            border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .responsive-embed iframe { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; 
        }
        .spotify-embed {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border-radius: 12px;
        }
        .spotify-embed iframe { display: block; border: 0; }

        html { background: linear-gradient(-45deg, var(--cor-principal), var(--cor-acento-2)); background-attachment: fixed; }
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh; }
        main { padding: 30px; max-width: 1200px; margin: 0 auto; flex-grow: 1; width: 100%; box-sizing: border-box; }
        .entry-form, .entry-view, .search-section, .entry-item {
            background-color: rgba(255, 255, 255, 0.65); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            padding: 25px; border-radius: var(--borda-arredondada); margin-bottom: 30px; box-shadow: 0 8px 32px 0 var(--sombra-cor);
            border: 1px solid rgba(255, 255, 255, 0.25); transition: box-shadow 0.3s ease;
        }
        .entry-form h2, .entry-view h2, .entry-list h2, .search-section h2 {
            font-family: 'Josefin Sans', sans-serif; color: var(--cor-titulo); margin-top: 0; margin-bottom: 25px;
            font-weight: 700; text-align: center; font-size: 2em;
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--cor-titulo); font-size: 1.1em; }
        input[type="text"], input[type="date"], input[type="search"] {
            width: 100%; padding: 12px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 1rem; transition: border-color 0.3s, box-shadow 0.3s; box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.7); color: var(--cor-texto-comum);
        }
        input[type="text"]:focus, input[type="date"]:focus, input[type="search"]:focus {
            border-color: var(--cor-titulo); box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.2); outline: none;
        }

        /* --- Customização do Quill Editor --- */
        .ql-toolbar.ql-snow {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px 8px 0 0;
            border-color: rgba(0, 0, 0, 0.15) !important;
            font-family: 'Poppins', sans-serif;
        }
        .ql-container.ql-snow {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 0 0 8px 8px;
            border-color: rgba(0, 0, 0, 0.15) !important;
            font-family: 'Poppins', sans-serif;
            font-size: 1.05rem;
            min-height: 250px;
        }
        .ql-editor {
            min-height: 250px;
        }
        .ql-editor img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
        }

        /* Toolbar vertical na lateral em dispositivos móveis */
        @media (max-width: 768px) {
            .form-group:has(.ql-toolbar) {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                align-items: stretch;
            }
            .form-group:has(.ql-toolbar) > label {
                width: 100%;
            }
            .ql-toolbar.ql-snow {
                width: 52px;
                display: flex;
                flex-direction: column;
                align-items: center;
                border-radius: 8px 0 0 8px;
                border-right: none !important;
                padding: 8px 4px;
                box-sizing: border-box;
                height: 380px;
                max-height: 380px;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .ql-toolbar.ql-snow .ql-formats {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-right: 0;
                margin-bottom: 10px;
                width: 100%;
            }
            .ql-toolbar.ql-snow button,
            .ql-toolbar.ql-snow .ql-picker {
                margin: 3px 0;
            }
            .ql-toolbar.ql-snow .ql-picker {
                width: 40px;
            }
            .ql-container.ql-snow {
                flex: 1;
                width: calc(100% - 52px);
                border-radius: 0 8px 8px 0;
                height: 380px;
                min-height: 380px;
                box-sizing: border-box;
            }
            .ql-editor {
                height: 100%;
                min-height: 380px;
                overflow-y: auto;
            }
        }

        .form-actions { display: flex; gap: 15px; margin-top: 30px; justify-content: flex-end; flex-wrap: wrap; }
        .form-actions .left-actions, .form-actions .right-actions { display: flex; gap: 15px; flex-wrap: wrap; }
        .save-btn, .cancel-btn, .back-btn, .view-all-btn, .delete-btn, .custom-file-upload, .edit-btn {
            padding: 8px 20px; border: 1px solid transparent; border-radius: 8px; cursor: pointer; font-weight: 600;
            transition: all 0.2s; font-size: 0.95em; display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; justify-content: center; font-family: 'Poppins', sans-serif;
        }
        .edit-btn { background-color: #f59e0b; color: white; border: 1px solid #f59e0b; }
        .edit-btn:hover { background-color: #d97706; border-color: #d97706; transform: translateY(-2px); }
        .save-btn { background-color: var(--cor-titulo); color: white; border-color: var(--cor-titulo); }
        .save-btn:hover { background-color: var(--cor-titulo-hover); border-color: var(--cor-titulo-hover); transform: translateY(-2px); }
        .cancel-btn, .back-btn { background-color: rgba(255, 255, 255, 0.8); color: var(--cor-texto-comum); border: 1px solid #ddd;}
        .cancel-btn:hover, .back-btn:hover { background-color: #fff; transform: translateY(-2px); border-color: #ccc; }
        .delete-btn { background-color: var(--cor-acento-2); color: var(--cor-perigo); border-color: var(--cor-perigo); }
        .delete-btn:hover { background-color: #ffd1c2; transform: translateY(-2px); }
        input[type="file"] { display: none; }
        .custom-file-upload { background-image: linear-gradient(135deg, var(--cor-acento-2), var(--cor-acento-3)); color: #a05a3c; }
        .custom-file-upload:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(160, 90, 60, 0.3); }
        .entry-list { margin-top: 40px; }
        .entry-item { border-left: 5px solid var(--cor-titulo); }
        .entry-item h3 { font-family: 'Josefin Sans', sans-serif; color: var(--cor-titulo); font-size: 1.6em; }
        .entry-meta { color: #5a7b9a; font-size: 0.9em; margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 10px; }
        .entry-meta i { color: var(--cor-titulo); }
        .entry-content { color: var(--cor-texto-comum); font-size: 1rem; margin-top: 20px; word-break: break-word; }
        .entry-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 10px 0; }
        .entry-item a { color: var(--cor-titulo); text-decoration: none; font-weight: 600; margin-top: 10px; display: inline-block; }
        .entry-item a:hover { text-decoration: underline; color: var(--cor-titulo-hover); }
        .success-message { color: #2e7d32; background-color: var(--cor-acento-1); padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a5d6a7; }
        .error-message { color: var(--cor-perigo); background-color: var(--cor-acento-2); padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ef9a9a; }
        footer { background-color: rgb(255, 255, 255); color: #A0522D; text-align: center; padding: 20px; margin-top: 40px; border-top: 1px solid #e0e0e0; }
        .search-section h2 { font-size: 1.8em; }
        .search-bar { display: flex; width: 100%; max-width: 700px; margin: 0 auto; }
        .search-bar input[type="search"] { flex-grow: 1; border-radius: 8px; }
        .search-bar button { border: none; background-color: var(--cor-titulo); color: white; padding: 0 25px; border-radius: 0 8px 8px 0; cursor: pointer; font-size: 1.2em; transition: background-color 0.3s; }
        .search-bar button:hover { background-color: var(--cor-titulo-hover); }
        .file-upload-info { margin-top: 10px; font-size: 0.9em; color: var(--cor-texto-comum); }
        .file-upload-info .file-name { font-weight: 600; color: var(--cor-titulo); }
        .entry-list .entry-files-container { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 25px; justify-content: center; }
        .entry-list .attached-file-item { display: flex; flex-direction: column; align-items: center; gap: 10px; background-color: rgba(255,255,255,0.5); padding: 15px 20px; border-radius: 10px; font-size: 1em; color: var(--cor-texto-comum); text-decoration: none; transition: background-color 0.2s, transform 0.2s; max-width: 200px; min-width: 150px; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.12); cursor: pointer; }
        .entry-list .attached-file-item:hover { background-color: rgba(255,255,255,0.8); transform: translateY(-3px); }
        .entry-list .attached-file-item img, .entry-list .attached-file-item i { color: var(--cor-titulo); }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: var(--overlay-bg); justify-content: center; align-items: center; }
        .modal-content-wrapper { position: relative; max-width: 90%; max-height: 90vh; display: flex; justify-content: center; align-items: center; background-color: var(--modal-bg); border-radius: 8px; box-shadow: 0 0 20px rgba(0, 0, 0, 0.5); padding: 20px; box-sizing: border-box; }
        .modal-content { display: block; max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px; }
        .close-button { position: absolute; top: 10px; right: 15px; color: #f1f1f1; font-size: 35px; font-weight: bold; transition: 0.3s; cursor: pointer; z-index: 1001; }
        .btn-voltar { position: fixed; bottom: 25px; left: 25px; z-index: 1000; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600; text-decoration: none; background-color: #ffded1; color: var(--cor-titulo); border: 1px solid rgba(160, 82, 45, 0.2); border-radius: 10px; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s ease-in-out; }
        .btn-voltar:hover { background-color: #fccab3; color: var(--cor-titulo-hover); transform: translateY(-3px) scale(1.05); box-shadow: 0 6px 20px rgba(255, 125, 87, 0.3); }
    </style>
</head>
<body>
    <main>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($viewEntry): ?>
            <section class="entry-view">
                <h2>Ver Registro do Diário</h2>
                <div class="entry-item" style="background: transparent; box-shadow: none; border: none; padding: 0; backdrop-filter: none;">
                    <?php if (isset($_GET['edit'])): ?>
                        <form id="diary-form" method="POST" action="diario.php?view=<?= $viewEntry['entry_id'] ?>" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="entry_id" value="<?= $viewEntry['entry_id'] ?>">
                            <input type="hidden" id="content" name="content">
                            
                            <div class="form-group"> 
                                <label for="title">Título (opcional)</label> 
                                <input type="text" id="title" name="title" value="<?= htmlspecialchars($viewEntry['title'] ?? '') ?>"> 
                            </div>
                            
                            <div class="form-group"> 
                                <label>Conteúdo</label> 
                                <div id="editor-container"><?= $viewEntry['content'] ?></div>
                            </div>

                            <div class="form-group"> <label for="youtube_link">Link do YouTube</label> <input type="text" id="youtube_link" name="youtube_link" placeholder="Cole o link do vídeo aqui" value="<?= htmlspecialchars($viewEntry['youtube_link'] ?? '') ?>"> </div>
                            <div class="form-group"> <label for="spotify_link">Link do Spotify</label> <input type="text" id="spotify_link" name="spotify_link" placeholder="Cole o link da música/playlist aqui" value="<?= htmlspecialchars($viewEntry['spotify_link'] ?? '') ?>"> </div>
                            <div class="form-group"> <label for="entry_date">Data</label> <input type="date" id="entry_date" name="entry_date" value="<?= htmlspecialchars($viewEntry['entry_date']) ?>"> </div>
                            <div class="form-group"> <label>Substituir Arquivos (opcional)</label> <label for="attached_files" class="custom-file-upload"><i class="fas fa-paperclip"></i> Escolher arquivo(s)</label> <input type="file" id="attached_files" name="attached_files[]" multiple> <div id="file-upload-info" class="file-upload-info">Nenhum ficheiro selecionado.</div> </div>
                            <div class="form-actions" style="justify-content: space-between;">
                                <div class="left-actions"> <button type="button" class="cancel-btn" onclick="window.location.href='diario.php?view=<?= $viewEntry['entry_id'] ?>'"><i class="fas fa-times"></i> Cancelar</button> </div>
                                <div class="right-actions"> <button type="submit" name="edit_entry" class="save-btn"><i class="fas fa-save"></i> Salvar Alterações</button> </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <h3><?= !empty($viewEntry['title']) ? htmlspecialchars($viewEntry['title']) : '(Sem título)' ?></h3>
                        <div class="entry-meta"> <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($viewEntry['entry_date'])) ?></span> </div>
                        <div class="entry-content"><?= $viewEntry['content'] ?></div>
                        
                        <div class="media-embed-container">
                            <?php echo getSpotifyEmbed($viewEntry['spotify_link']); ?>
                            <?php echo getYoutubeEmbed($viewEntry['youtube_link']); ?>
                        </div>

                        <?php if (!empty($viewEntry['attached_files_path'])): $viewAttachedFilesPaths = explode(',', $viewEntry['attached_files_path']); if (!empty($viewAttachedFilesPaths) && !($viewAttachedFilesPaths[0] === '')): ?> 
                            <div class="attached-file-full-display" style="margin-top: 25px; text-align: center; display: flex; flex-direction: column; gap: 25px; align-items: center;">
                            <?php foreach ($viewAttachedFilesPaths as $path): $trimmedPath = trim($path); if (file_exists($trimmedPath)): $fileMimeType = mime_content_type($trimmedPath);
                                if (strpos($fileMimeType, 'image/') === 0): ?> <img src="<?= htmlspecialchars($trimmedPath) ?>" alt="Anexo de Imagem" onclick="openModal('<?= htmlspecialchars($trimmedPath) ?>', 'image')" style="max-width: 80%; max-height: 400px; border-radius: 8px; cursor: pointer;">
                                <?php elseif (strpos($fileMimeType, 'video/') === 0): ?> <video controls controlsList="nodownload" src="<?= htmlspecialchars($trimmedPath) ?>" type="<?= htmlspecialchars($fileMimeType) ?>" style="max-width: 100%; border-radius: 8px;"></video>
                                <?php elseif (strpos($fileMimeType, 'audio/') === 0): ?> <div class="audio-player-container" style="width: 100%; max-width: 700px; margin: 15px auto; background-color: rgba(255,255,255,0.5); border-radius: 10px; padding: 20px;"> <audio controls controlsList="nodownload" preload="metadata" src="<?= htmlspecialchars($trimmedPath) ?>" type="<?= htmlspecialchars($fileMimeType) ?>" style="width: 100%;"></audio> </div>
                                <?php else: ?> <a href="<?= htmlspecialchars($trimmedPath) ?>" target="_blank" class="download-link" style="display: inline-flex; align-items: center; gap: 12px; padding: 12px 25px; background-color: var(--cor-principal); color: var(--cor-titulo); border-radius: 8px; text-decoration: none; font-weight: 600;"> <i class="fas fa-file-download"></i> <span>Baixar: <?= htmlspecialchars(basename($trimmedPath)) ?></span> </a>
                                <?php endif; endif; endforeach; ?>
                            </div>
                        <?php endif; endif; ?>

                        <div class="form-actions" style="justify-content: space-between;">
                            <div class="left-actions"> <a href="diario.php" class="back-btn"><i class="fas fa-arrow-left"></i> Voltar à Lista</a> </div>
                            <div class="right-actions">
                                <button class="edit-btn" onclick="window.location.href='diario.php?view=<?= $viewEntry['entry_id'] ?>&edit=1'"><i class="fas fa-edit"></i> Editar</button>
                                <form method="POST" action="diario.php" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este registro?');">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="entry_id" value="<?= $viewEntry['entry_id'] ?>">
                                    <button type="submit" name="delete_entry" class="delete-btn"><i class="fas fa-trash"></i> Excluir</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <section class="entry-form">
                <h2>Escrever no Diário</h2>
                <form id="diary-form" method="POST" action="diario.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" id="content" name="content">
                    
                    <div class="form-group"> 
                        <label for="title">Título (opcional)</label> 
                        <input type="text" id="title" name="title" placeholder="Título do registro"> 
                    </div>
                    
                    <div class="form-group"> 
                        <label>Conteúdo</label> 
                        <div id="editor-container"></div>
                    </div>

                    <div class="form-group">
                        <label for="youtube_link"><i class="fab fa-youtube" style="color: #FF0000;"></i> Link do YouTube (opcional)</label>
                        <input type="text" id="youtube_link" name="youtube_link" placeholder="Cole o link de um vídeo do YouTube">
                    </div>
                    <div class="form-group">
                        <label for="spotify_link"><i class="fab fa-spotify" style="color: #1DB954;"></i> Link do Spotify (opcional)</label>
                        <input type="text" id="spotify_link" name="spotify_link" placeholder="Cole o link de uma música, álbum ou playlist">
                    </div>

                    <div class="form-group"> <label for="attached_files">Anexar arquivo(s)</label> <label for="attached_files" class="custom-file-upload"><i class="fas fa-paperclip"></i> Escolher arquivo(s)</label> <input type="file" id="attached_files" name="attached_files[]" multiple> <div id="file-upload-info" class="file-upload-info">Nenhum ficheiro selecionado. (Áudio: máx 3MB, Outros: máx 20MB)</div> </div>
                    <div class="form-group"> <label for="entry_date">Data</label> <input type="date" id="entry_date" name="entry_date" value="<?= date('Y-m-d') ?>"> </div>
                    <div class="form-actions"> <div class="right-actions"> <button type="submit" name="save_entry" class="save-btn"><i class="fas fa-save"></i> Salvar Registro</button> </div> </div>
                </form>
            </section>
        
            <section class="search-section">
                <h2>Pesquisar nos Registros</h2>
                <div class="search-bar">
                    <input type="search" id="realtime-search-input" placeholder="Digite para pesquisar por título ou conteúdo..." autocomplete="off">
                </div>
            </section>

            <section class="entry-list">
                <h2>Meus Registros</h2>
                <div id="entry-list-container">
                    <?php if (empty($entries)): ?>
                        <p style="text-align: center; color: var(--cor-texto-comum);">Nenhum registro encontrado. Comece a escrever!</p>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                            <div class="entry-item">
                                <h3><?= !empty($entry['title']) ? htmlspecialchars($entry['title']) : '(Sem título)' ?></h3>
                                <div class="entry-meta"> <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($entry['entry_date'])) ?></span> </div>
                                <p style="color: var(--cor-texto-comum);">
                                    <?= htmlspecialchars(mb_substr(strip_tags($entry['content']), 0, 200)) ?>
                                    <?= mb_strlen(strip_tags($entry['content'])) > 200 ? '...' : '' ?>
                                </p>
                                <?php if (!empty($entry['attached_files_path'])): $listAttachedFilesPaths = explode(',', $entry['attached_files_path']); if (!empty($listAttachedFilesPaths) && !($listAttachedFilesPaths[0] === '')): ?>
                                    <div class="entry-files-container">
                                        <?php foreach ($listAttachedFilesPaths as $path): $trimmedPath = trim($path); if (file_exists($trimmedPath)):
                                            $fileExtension = strtolower(pathinfo($trimmedPath, PATHINFO_EXTENSION));
                                            $iconClass = 'fas fa-file-archive';
                                            $isImage = false;
                                            if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) { $iconClass = 'fas fa-image'; $isImage = true; }
                                            elseif (in_array($fileExtension, ['mp4', 'webm', 'ogg', 'mov', 'avi'])) { $iconClass = 'fas fa-video'; }
                                            elseif (in_array($fileExtension, $audioExtensions)) { $iconClass = 'fas fa-volume-up'; }
                                            elseif ($fileExtension === 'pdf') { $iconClass = 'fas fa-file-pdf'; }
                                            elseif (in_array($fileExtension, ['doc', 'docx'])) { $iconClass = 'fas fa-file-word'; }
                                            elseif (in_array($fileExtension, ['xls', 'xlsx'])) { $iconClass = 'fas fa-file-excel'; }
                                            elseif (in_array($fileExtension, ['ppt', 'pptx'])) { $iconClass = 'fas fa-file-powerpoint'; }
                                            elseif (in_array($fileExtension, ['zip', 'rar', '7z'])) { $iconClass = 'fas fa-file-archive'; }
                                            elseif ($fileExtension === 'txt') { $iconClass = 'fas fa-file-alt'; }
                                        ?>
                                            <a href="diario.php?view=<?= $entry['entry_id'] ?>" class="attached-file-item">
                                                <?php if ($isImage): ?> <img src="<?= htmlspecialchars($trimmedPath) ?>" alt="Anexo" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                                                <?php else: ?> <i class="<?= $iconClass ?>" style="font-size: 4em;"></i>
                                                <?php endif; ?>
                                            </a>
                                        <?php endif; endforeach; ?>
                                    </div>
                                <?php endif; endif; ?>
                                <a href="diario.php?view=<?= $entry['entry_id'] ?>">Ler registro completo <i class="fas fa-arrow-right"></i></a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p id="no-results-message" style="display: none; text-align: center; color: var(--cor-texto-comum);">Nenhum registro encontrado para o termo pesquisado.</p>
            </section>
        <?php endif; ?>
    </main>

    <div id="mediaModal" class="modal">
        <span class="close-button" onclick="closeModal()">×</span>
        <div class="modal-content-wrapper" id="modalContentWrapper"></div>
    </div>

    <!-- Scripts do Quill e Resizer -->
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-image-resize-module@3.0.0/image-resize.min.js"></script>

    <script>
        // Inicialização do Editor Quill com Redimensionamento de Imagem
        const editorContainer = document.getElementById('editor-container');
        let quill = null;

        if (editorContainer) {
            quill = new Quill('#editor-container', {
                theme: 'snow',
                placeholder: 'Escreva seus pensamentos, adicione imagens, formate cabeçalhos...',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'align': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['blockquote', 'code-block'],
                        ['link', 'image'],
                        ['clean']
                    ],
                    imageResize: {
                        modules: [ 'Resize', 'DisplaySize', 'Toolbar' ],
                        handleStyles: {
                            backgroundColor: '#A0522D',
                            border: 'none',
                            color: 'white'
                        }
                    }
                }
            });

            // Sincroniza o HTML do editor com o input hidden antes de disparar o envio do formulário
            const diaryForm = document.getElementById('diary-form');
            if (diaryForm) {
                diaryForm.addEventListener('submit', function(e) {
                    const contentInput = document.getElementById('content');
                    // Se o editor estiver totalmente vazio (apenas tags em branco do Quill), envia vazio
                    if (quill.getText().trim().length === 0 && !editorContainer.querySelector('img')) {
                        contentInput.value = '';
                    } else {
                        contentInput.value = quill.root.innerHTML;
                    }
                });
            }
        }

        const mediaModal = document.getElementById('mediaModal');
        const modalContentWrapper = document.getElementById('modalContentWrapper');
        function openModal(mediaSrc, type) {
            if (type === 'image') {
                modalContentWrapper.innerHTML = `<img src="${mediaSrc}" alt="Anexo" class="modal-content">`;
                mediaModal.style.display = 'flex';
            }
        }
        function closeModal() {
            mediaModal.style.display = 'none';
            modalContentWrapper.innerHTML = '';
        }
        mediaModal.addEventListener('click', (event) => { if (event.target === mediaModal) closeModal(); });

        const attachedFilesInput = document.getElementById('attached_files');
        const fileUploadInfo = document.getElementById('file-upload-info');
        if (attachedFilesInput) {
            attachedFilesInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    let fileNames = Array.from(this.files).map(file => file.name).join(', ');
                    fileUploadInfo.innerHTML = `Ficheiro(s) selecionado(s): <span class="file-name">${fileNames}</span>`;
                } else {
                    fileUploadInfo.textContent = 'Nenhum ficheiro selecionado. (Áudio: máx 3MB, Outros: máx 20MB)';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('realtime-search-input');
            const entryListContainer = document.getElementById('entry-list-container');
            const noResultsMessage = document.getElementById('no-results-message');

            if (searchInput && entryListContainer && noResultsMessage) {
                const entries = entryListContainer.querySelectorAll('.entry-item');

                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    let visibleCount = 0;

                    entries.forEach(function(entry) {
                        const title = entry.querySelector('h3').textContent.toLowerCase();
                        const content = entry.querySelector('p').textContent.toLowerCase();
                        const entryText = title + ' ' + content;

                        if (entryText.includes(searchTerm)) {
                            entry.style.display = '';
                            visibleCount++;
                        } else {
                            entry.style.display = 'none';
                        }
                    });

                    if (visibleCount === 0) {
                        noResultsMessage.style.display = 'block';
                    } else {
                        noResultsMessage.style.display = 'none';
                    }
                });
            }
        });
    </script>
    <?php include 'footer.php'; ?>

    <a href="home.php" class="btn-voltar">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</body>
</html>