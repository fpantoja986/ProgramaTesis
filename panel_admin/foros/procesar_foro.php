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

$accion = $_POST['accion'] ?? 'crear';

try {
    switch ($accion) {
        case 'crear':
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;
            
            if (empty($titulo)) {
                echo json_encode(['error' => 'El título es obligatorio']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO foros (titulo, descripcion, id_admin, activo) VALUES (:titulo, :descripcion, :id_admin, :activo)");
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':id_admin', $_SESSION['user_id']);
            $stmt->bindParam(':activo', $activo);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Foro creado exitosamente']);
            } else {
                echo json_encode(['error' => 'Error al crear el foro']);
            }
            break;
            
        case 'editar':
            $id = (int)($_POST['id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;
            
            if (empty($id) || empty($titulo)) {
                echo json_encode(['error' => 'Datos incompletos']);
                exit;
            }
            
            // Verificar permisos
            $stmt = $pdo->prepare("SELECT id_admin FROM foros WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $foro = $stmt->fetch();
            
            if (!$foro || $foro['id_admin'] != $_SESSION['user_id']) {
                echo json_encode(['error' => 'No tienes permisos para editar este foro']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE foros SET titulo = :titulo, descripcion = :descripcion, activo = :activo WHERE id = :id");
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':activo', $activo);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Foro actualizado exitosamente']);
            } else {
                echo json_encode(['error' => 'Error al actualizar el foro']);
            }
            break;
            
        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0);
            
            if (empty($id)) {
                echo json_encode(['error' => 'ID de foro requerido']);
                exit;
            }
            
            // Verificar permisos
            $stmt = $pdo->prepare("SELECT id_admin FROM foros WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $foro = $stmt->fetch();
            
            if (!$foro || $foro['id_admin'] != $_SESSION['user_id']) {
                echo json_encode(['error' => 'No tienes permisos para modificar este foro']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE foros SET activo = :activo WHERE id = :id");
            $stmt->bindParam(':activo', $activo);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado exitosamente']);
            } else {
                echo json_encode(['error' => 'Error al cambiar el estado']);
            }
            break;
            
        case 'eliminar':
            $id = (int)($_POST['id'] ?? 0);
            
            if (empty($id)) {
                echo json_encode(['error' => 'ID de foro requerido']);
                exit;
            }
            
            // Verificar permisos
            $stmt = $pdo->prepare("SELECT id_admin FROM foros WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $foro = $stmt->fetch();
            
            if (!$foro || $foro['id_admin'] != $_SESSION['user_id']) {
                echo json_encode(['error' => 'No tienes permisos para eliminar este foro']);
                exit;
            }
            
            // Eliminar foro (CASCADE eliminará temas y respuestas)
            $stmt = $pdo->prepare("DELETE FROM foros WHERE id = :id");
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Foro eliminado exitosamente']);
            } else {
                echo json_encode(['error' => 'Error al eliminar el foro']);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Acción no válida']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>