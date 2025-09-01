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

$titulo = trim($_POST['titulo'] ?? '');
$contenido = trim($_POST['contenido'] ?? '');
$id_foro = (int)($_POST['id_foro'] ?? 0);

if (empty($titulo) || empty($contenido) || empty($id_foro)) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

try {
    // Verificar que el foro existe y está activo
    $stmt = $pdo->prepare("SELECT id FROM foros WHERE id = :id AND activo = 1");
    $stmt->bindParam(':id', $id_foro);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'El foro no existe o no está disponible']);
        exit;
    }

    // Insertar el nuevo tema
    $stmt = $pdo->prepare("
        INSERT INTO temas_foro (titulo, contenido, id_foro, id_usuario, fecha_creacion) 
        VALUES (:titulo, :contenido, :id_foro, :id_usuario, NOW())
    ");
    
    $stmt->bindParam(':titulo', $titulo);
    $stmt->bindParam(':contenido', $contenido);
    $stmt->bindParam(':id_foro', $id_foro);
    $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $tema_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Tema creado exitosamente',
            'tema_id' => $tema_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el tema']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>