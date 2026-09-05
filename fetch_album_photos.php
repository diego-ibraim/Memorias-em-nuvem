<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit();
}

require_once 'config.php'; // Certifique-se de que config.php está disponível aqui

$album_id = filter_input(INPUT_GET, 'album_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$album_id) {
    echo json_encode(['error' => 'ID do álbum inválido.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT photo_id, image_path FROM photos WHERE album_id = ? AND user_id = ? ORDER BY upload_date DESC");
    $stmt->execute([$album_id, $user_id]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($photos);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar fotos do álbum: ' . $e->getMessage()]);
}
?>