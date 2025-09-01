<?php
include '../db.php';
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
    // Obtener la publicación
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre_completo as autor_nombre 
        FROM contenidos c 
        INNER JOIN usuarios u ON c.id_admin = u.id 
        WHERE c.id = :id
    ");
    $stmt->bindParam(':id', $publicacion_id, PDO::PARAM_INT);
    $stmt->execute();
    $publicacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$publicacion) {
        http_response_code(404);
        echo json_encode(['error' => 'Publicación no encontrada']);
        exit;
    }

    // Verificar que el usuario actual es el autor de la publicación
    if ($publicacion['id_admin'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para editar esta publicación. Solo puedes editar tus propias publicaciones.']);
        exit;
    }

    // Remover datos binarios del archivo para el JSON
    unset($publicacion['archivo_path']);

    echo json_encode([
        'success' => true,
        'publicacion' => $publicacion
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>