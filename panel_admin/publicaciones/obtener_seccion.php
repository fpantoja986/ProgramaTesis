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
    echo json_encode(['error' => 'ID de sección requerido']);
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM secciones WHERE id = ?");
    $stmt->execute([$id]);
    $seccion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seccion) {
        http_response_code(404);
        echo json_encode(['error' => 'Sección no encontrada']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'seccion' => $seccion
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
