<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include '../../db.php';

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'crear_comentario':
                $id_publicacion = (int)$_POST['id_publicacion'];
                $comentario = trim($_POST['comentario']);
                $id_comentario_padre = !empty($_POST['id_comentario_padre']) ? (int)$_POST['id_comentario_padre'] : null;
                
                if (empty($comentario)) {
                    $response['message'] = 'El comentario no puede estar vacío';
                    break;
                }
                
                if (strlen($comentario) > 1000) {
                    $response['message'] = 'El comentario es demasiado largo (máximo 1000 caracteres)';
                    break;
                }
                
                // Verificar si la tabla existe
                $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios_publicaciones'");
                if ($stmt->rowCount() == 0) {
                    $response['message'] = 'La tabla de comentarios no existe. Por favor, ejecuta el script de instalación.';
                    break;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO comentarios_publicaciones (id_publicacion, id_usuario, comentario, id_comentario_padre) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$id_publicacion, $user_id, $comentario, $id_comentario_padre]);
                
                $response['success'] = true;
                $response['message'] = 'Comentario publicado exitosamente';
                $response['comentario_id'] = $pdo->lastInsertId();
                break;
                
            case 'like_comentario':
                $id_comentario = (int)$_POST['id_comentario'];
                
                // Obtener likes existentes
                $stmt = $pdo->prepare("SELECT likes FROM comentarios_publicaciones WHERE id = ?");
                $stmt->execute([$id_comentario]);
                $likes_existentes = $stmt->fetchColumn();
                
                $likes = $likes_existentes ? json_decode($likes_existentes, true) : [];
                
                // Verificar si el usuario ya dio like
                $usuario_ya_likeo = false;
                foreach ($likes as $index => $like) {
                    if ($like['usuario_id'] == $user_id) {
                        $usuario_ya_likeo = true;
                        // Quitar like
                        unset($likes[$index]);
                        $likes = array_values($likes); // Reindexar array
                        $response['liked'] = false;
                        break;
                    }
                }
                
                if (!$usuario_ya_likeo) {
                    // Dar like
                    $likes[] = [
                        'usuario_id' => $user_id,
                        'fecha' => date('Y-m-d H:i:s')
                    ];
                    $response['liked'] = true;
                }
                
                // Actualizar likes en la base de datos
                $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET likes = ?, total_likes = ? WHERE id = ?");
                $stmt->execute([json_encode($likes), count($likes), $id_comentario]);
                
                $response['likes_count'] = count($likes);
                $response['success'] = true;
                break;
                
            case 'eliminar_comentario':
                $id_comentario = (int)$_POST['id_comentario'];
                
                // Verificar si la tabla existe
                $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios_publicaciones'");
                if ($stmt->rowCount() == 0) {
                    $response['message'] = 'La tabla de comentarios no existe.';
                    break;
                }
                
                // Verificar que el comentario existe
                $stmt = $pdo->prepare("SELECT id_usuario FROM comentarios_publicaciones WHERE id = ? AND activo = 1");
                $stmt->execute([$id_comentario]);
                $comentario = $stmt->fetch();
                
                if (!$comentario) {
                    $response['message'] = 'Comentario no encontrado';
                    break;
                }
                
                // Verificar permisos: el usuario solo puede eliminar sus propios comentarios
                if ($comentario['id_usuario'] != $user_id) {
                    $response['message'] = 'No tienes permisos para eliminar este comentario';
                    break;
                }
                
                // Eliminar el comentario (marcar como inactivo)
                $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET activo = 0 WHERE id = ?");
                $stmt->execute([$id_comentario]);
                
                $response['success'] = true;
                $response['message'] = 'Comentario eliminado exitosamente';
                break;
                
            case 'reportar_comentario':
                $id_comentario = (int)$_POST['id_comentario'];
                $motivo = $_POST['motivo'] ?? '';
                $descripcion = trim($_POST['descripcion'] ?? '');
                
                if (empty($motivo)) {
                    $response['message'] = 'Debes seleccionar un motivo para el reporte';
                    break;
                }
                
                // Verificar si ya reportó este comentario
                $stmt = $pdo->prepare("SELECT id FROM comentarios_publicaciones WHERE id = ? AND JSON_CONTAINS(reportes, JSON_OBJECT('usuario_id', ?))");
                $stmt->execute([$id_comentario, $user_id]);
                
                if ($stmt->fetch()) {
                    $response['message'] = 'Ya has reportado este comentario anteriormente';
                    break;
                }
                
                // Obtener reportes existentes
                $stmt = $pdo->prepare("SELECT reportes FROM comentarios_publicaciones WHERE id = ?");
                $stmt->execute([$id_comentario]);
                $reportes_existentes = $stmt->fetchColumn();
                
                $reportes = $reportes_existentes ? json_decode($reportes_existentes, true) : [];
                $reportes[] = [
                    'usuario_id' => $user_id,
                    'motivo' => $motivo,
                    'descripcion' => $descripcion,
                    'fecha' => date('Y-m-d H:i:s'),
                    'estado' => 'pendiente'
                ];
                
                // Actualizar reportes
                $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET reportes = ?, total_reportes = ? WHERE id = ?");
                $stmt->execute([json_encode($reportes), count($reportes), $id_comentario]);
                
                $response['success'] = true;
                $response['message'] = 'Comentario reportado exitosamente';
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
