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

$id_respuesta = (int)($_POST['id_respuesta'] ?? 0);

if (empty($id_respuesta)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de respuesta requerido']);
    exit;
}

try {
    // Verificar que la respuesta existe y pertenece al admin
    $stmt = $pdo->prepare("
        SELECT r.id, r.id_usuario, r.id_tema, t.cerrado 
        FROM respuestas_foro r 
        INNER JOIN temas_foro t ON r.id_tema = t.id 
        WHERE r.id = :id_respuesta
    ");
    $stmt->bindParam(':id_respuesta', $id_respuesta);
    $stmt->execute();
    $respuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$respuesta) {
        http_response_code(404);
        echo json_encode(['error' => 'La respuesta no existe']);
        exit;
    }
    
    // Verificar que la respuesta pertenece al admin actual
    if ($respuesta['id_usuario'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para eliminar esta respuesta']);
        exit;
    }
    
    // Verificar que el tema no esté cerrado
    if ($respuesta['cerrado']) {
        http_response_code(400);
        echo json_encode(['error' => 'No se puede eliminar respuestas en temas cerrados']);
        exit;
    }
    
    // Eliminar respuestas hijas primero (si las hay)
    $stmt = $pdo->prepare("DELETE FROM respuestas_foro WHERE id_respuesta_padre = :id_respuesta");
    $stmt->bindParam(':id_respuesta', $id_respuesta);
    $stmt->execute();
    
    // Eliminar reportes relacionados
    $stmt = $pdo->prepare("DELETE FROM reportes_respuestas WHERE id_respuesta = :id_respuesta");
    $stmt->bindParam(':id_respuesta', $id_respuesta);
    $stmt->execute();
    
    // Eliminar la respuesta principal
    $stmt = $pdo->prepare("DELETE FROM respuestas_foro WHERE id = :id_respuesta AND id_usuario = :id_usuario");
    $stmt->bindParam(':id_respuesta', $id_respuesta);
    $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Respuesta eliminada exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar la respuesta']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
