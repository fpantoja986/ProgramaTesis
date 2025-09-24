<?php
include '../../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$contenido_id = (int)($_POST['contenido_id'] ?? 0);

if (empty($contenido_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de contenido requerido']);
    exit;
}

try {
    // Verificar que el contenido existe
    $stmt = $pdo->prepare("SELECT id FROM contenidos WHERE id = ?");
    $stmt->execute([$contenido_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Contenido no encontrado']);
        exit;
    }
    
    // Quitar contenido de la sección (establecer seccion_id = NULL)
    $stmt = $pdo->prepare("UPDATE contenidos SET seccion_id = NULL, orden_seccion = 0 WHERE id = ?");
    
    if ($stmt->execute([$contenido_id])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Contenido removido de la sección correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al quitar el contenido de la sección']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
