<?php
include '../../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de foro requerido']);
    exit;
}

$foro_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM foros WHERE id = :id");
    $stmt->bindParam(':id', $foro_id, PDO::PARAM_INT);
    $stmt->execute();
    $foro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$foro) {
        http_response_code(404);
        echo json_encode(['error' => 'Foro no encontrado']);
        exit;
    }

    // Verificar permisos (solo el creador puede editar)
    if ($foro['id_admin'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para editar este foro']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'foro' => $foro
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>