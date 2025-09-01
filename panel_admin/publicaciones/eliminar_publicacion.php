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
    echo json_encode(['error' => 'ID de publicación requerido']);
    exit;
}

$publicacion_id = (int)$_GET['id'];

try {
    // Verificar que la publicación existe y obtener información del autor
    $stmt = $pdo->prepare("SELECT id_admin FROM contenidos WHERE id = :id");
    $stmt->bindParam(':id', $publicacion_id, PDO::PARAM_INT);
    $stmt->execute();
    $publicacion = $stmt->fetch();

    if (!$publicacion) {
        http_response_code(404);
        echo json_encode(['error' => 'Publicación no encontrada']);
        exit;
    }

    // Verificar que el usuario actual es el autor de la publicación
    if ($publicacion['id_admin'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para eliminar esta publicación. Solo puedes eliminar tus propias publicaciones.']);
        exit;
    }

    // Eliminar la publicación
    $stmt = $pdo->prepare("DELETE FROM contenidos WHERE id = :id AND id_admin = :id_admin");
    $stmt->bindParam(':id', $publicacion_id, PDO::PARAM_INT);
    $stmt->bindParam(':id_admin', $_SESSION['user_id'], PDO::PARAM_INT);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Publicación eliminada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar la publicación']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>