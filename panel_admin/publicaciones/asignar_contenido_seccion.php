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
$seccion_id = (int)($_POST['seccion_id'] ?? 0);

if (empty($contenido_id) || empty($seccion_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de contenido y sección requeridos']);
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
    
    // Verificar que la sección existe y está visible
    $stmt = $pdo->prepare("SELECT id FROM secciones WHERE id = ? AND visible = 1");
    $stmt->execute([$seccion_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Sección no encontrada o no visible']);
        exit;
    }
    
    // Obtener el siguiente orden dentro de la sección
    $stmt = $pdo->prepare("SELECT MAX(orden_seccion) as max_orden FROM contenidos WHERE seccion_id = ?");
    $stmt->execute([$seccion_id]);
    $result = $stmt->fetch();
    $orden = ($result['max_orden'] ?? 0) + 1;
    
    // Asignar contenido a la sección
    $stmt = $pdo->prepare("UPDATE contenidos SET seccion_id = ?, orden_seccion = ? WHERE id = ?");
    
    if ($stmt->execute([$seccion_id, $orden, $contenido_id])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Contenido asignado a la sección correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al asignar el contenido']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
