<?php
include '../../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
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
$nuevo_contenido = trim($_POST['contenido'] ?? '');

if (empty($id_respuesta) || empty($nuevo_contenido)) {
    http_response_code(400);
    echo json_encode(['error' => 'El contenido es obligatorio']);
    exit;
}

try {
    // Verificar que la respuesta existe y pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT r.id, r.id_usuario, t.cerrado 
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
    
    // Verificar que la respuesta pertenece al usuario actual
    if ($respuesta['id_usuario'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para editar esta respuesta']);
        exit;
    }
    
    // Verificar que el tema no esté cerrado
    if ($respuesta['cerrado']) {
        http_response_code(400);
        echo json_encode(['error' => 'No se puede editar respuestas en temas cerrados']);
        exit;
    }
    
    // Actualizar la respuesta
    $stmt = $pdo->prepare("
        UPDATE respuestas_foro 
        SET contenido = :contenido, fecha_actualizacion = NOW() 
        WHERE id = :id_respuesta AND id_usuario = :id_usuario
    ");
    
    $stmt->bindParam(':contenido', $nuevo_contenido);
    $stmt->bindParam(':id_respuesta', $id_respuesta);
    $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Respuesta actualizada exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar la respuesta']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
