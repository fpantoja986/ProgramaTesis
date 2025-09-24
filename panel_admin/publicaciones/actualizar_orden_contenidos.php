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

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['contenidos']) || !is_array($data['contenidos']) || !isset($data['seccion_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos de reordenamiento inválidos']);
    exit;
}

$seccion_id = (int)$data['seccion_id'];
$contenidos = $data['contenidos'];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE contenidos SET orden_seccion = ? WHERE id = ? AND seccion_id = ?");
    
    foreach ($contenidos as $contenido) {
        $stmt->execute([
            $contenido['orden'], 
            $contenido['id'], 
            $seccion_id
        ]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Orden actualizado correctamente']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el orden: ' . $e->getMessage()]);
}
?>
