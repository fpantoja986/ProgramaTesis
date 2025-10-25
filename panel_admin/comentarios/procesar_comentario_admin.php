<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include '../../db.php';

$admin_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'eliminar_comentario_admin':
                $id_comentario = (int)$_POST['id_comentario'];
                
                // Verificar si la tabla existe
                $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios_publicaciones'");
                if ($stmt->rowCount() == 0) {
                    $response['message'] = 'La tabla de comentarios no existe.';
                    break;
                }
                
                // Verificar que el comentario existe
                $stmt = $pdo->prepare("SELECT id, id_usuario, comentario FROM comentarios_publicaciones WHERE id = ? AND activo = 1");
                $stmt->execute([$id_comentario]);
                $comentario = $stmt->fetch();
                
                if (!$comentario) {
                    $response['message'] = 'Comentario no encontrado';
                    break;
                }
                
                // Los administradores pueden eliminar cualquier comentario
                $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET activo = 0 WHERE id = ?");
                $stmt->execute([$id_comentario]);
                
                $response['success'] = true;
                $response['message'] = 'Comentario eliminado exitosamente por el administrador';
                break;
                
            case 'eliminar_comentario_permanente':
                $id_comentario = (int)$_POST['id_comentario'];
                
                // Verificar si la tabla existe
                $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios_publicaciones'");
                if ($stmt->rowCount() == 0) {
                    $response['message'] = 'La tabla de comentarios no existe.';
                    break;
                }
                
                // Verificar que el comentario existe
                $stmt = $pdo->prepare("SELECT id FROM comentarios_publicaciones WHERE id = ?");
                $stmt->execute([$id_comentario]);
                
                if (!$stmt->fetch()) {
                    $response['message'] = 'Comentario no encontrado';
                    break;
                }
                
                // Eliminar permanentemente (también elimina respuestas)
                $stmt = $pdo->prepare("DELETE FROM comentarios_publicaciones WHERE id = ? OR id_comentario_padre = ?");
                $stmt->execute([$id_comentario, $id_comentario]);
                
                $response['success'] = true;
                $response['message'] = 'Comentario eliminado permanentemente';
                break;
                
            case 'restaurar_comentario':
                $id_comentario = (int)$_POST['id_comentario'];
                
                // Verificar si la tabla existe
                $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios_publicaciones'");
                if ($stmt->rowCount() == 0) {
                    $response['message'] = 'La tabla de comentarios no existe.';
                    break;
                }
                
                // Restaurar comentario
                $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET activo = 1 WHERE id = ?");
                $stmt->execute([$id_comentario]);
                
                $response['success'] = true;
                $response['message'] = 'Comentario restaurado exitosamente';
                break;
                
            default:
                $response['message'] = 'Acción no válida';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Método no permitido';
}

header('Content-Type: application/json');
echo json_encode($response);
?>
