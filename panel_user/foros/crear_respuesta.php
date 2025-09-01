<?php
include '../db.php';
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

$contenido = trim($_POST['contenido'] ?? '');
$id_tema = (int)($_POST['id_tema'] ?? 0);

if (empty($contenido) || empty($id_tema)) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

try {
    // Verificar que el tema existe y no está cerrado
    $stmt = $pdo->prepare("
        SELECT t.cerrado, f.activo 
        FROM temas_foro t 
        INNER JOIN foros f ON t.id_foro = f.id 
        WHERE t.id = :id
    ");
    $stmt->bindParam(':id', $id_tema);
    $stmt->execute();
    $tema = $stmt->fetch();
    
    if (!$tema) {
        http_response_code(404);
        echo json_encode(['error' => 'El tema no existe']);
        exit;
    }
    
    if ($tema['cerrado']) {
        http_response_code(403);
        echo json_encode(['error' => 'Este tema está cerrado para respuestas']);
        exit;
    }
    
    if (!$tema['activo']) {
        http_response_code(403);
        echo json_encode(['error' => 'El foro no está disponible']);
        exit;
    }

    // Insertar la respuesta
    $stmt = $pdo->prepare("
        INSERT INTO respuestas_foro (contenido, id_tema, id_usuario, fecha_creacion) 
        VALUES (:contenido, :id_tema, :id_usuario, NOW())
    ");
    
    $stmt->bindParam(':contenido', $contenido);
    $stmt->bindParam(':id_tema', $id_tema);
    $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Respuesta enviada exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al enviar la respuesta']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>