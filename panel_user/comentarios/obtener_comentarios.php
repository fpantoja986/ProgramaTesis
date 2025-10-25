<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include '../../db.php';

$id_publicacion = (int)($_GET['id_publicacion'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$id_publicacion) {
    echo json_encode(['success' => false, 'message' => 'ID de publicación requerido']);
    exit();
}

try {
    // Verificar si la tabla existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios_publicaciones'");
    if ($stmt->rowCount() == 0) {
        // Si la tabla no existe, devolver array vacío
        echo json_encode([
            'success' => true,
            'comentarios' => []
        ]);
        exit();
    }
    
    // Obtener comentarios principales (no respuestas) - solo los aprobados
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.nombre_completo,
            u.foto_perfil,
            COALESCE(c.total_likes, 0) as likes_count
        FROM comentarios_publicaciones c
        LEFT JOIN usuarios u ON c.id_usuario = u.id
        WHERE c.id_publicacion = ? AND c.id_comentario_padre IS NULL AND c.activo = 1 AND c.moderado = 1
        ORDER BY c.fecha_creacion DESC
    ");
    $stmt->execute([$id_publicacion]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar si el usuario actual dio like a cada comentario
    foreach ($comentarios as &$comentario) {
        $likes = json_decode($comentario['likes'] ?? '[]', true);
        $comentario['user_liked'] = 0;
        
        if ($likes && is_array($likes)) {
            foreach ($likes as $like) {
                if (isset($like['usuario_id']) && $like['usuario_id'] == $user_id) {
                    $comentario['user_liked'] = 1;
                    break;
                }
            }
        }
    }
    
    // Para cada comentario principal, obtener sus respuestas - solo las aprobadas
    foreach ($comentarios as &$comentario) {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                u.nombre_completo,
                u.foto_perfil,
                COALESCE(c.total_likes, 0) as likes_count
            FROM comentarios_publicaciones c
            LEFT JOIN usuarios u ON c.id_usuario = u.id
            WHERE c.id_comentario_padre = ? AND c.activo = 1 AND c.moderado = 1
            ORDER BY c.fecha_creacion ASC
        ");
        $stmt->execute([$comentario['id']]);
        $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Verificar si el usuario actual dio like a cada respuesta
        foreach ($respuestas as &$respuesta) {
            $likes = json_decode($respuesta['likes'] ?? '[]', true);
            $respuesta['user_liked'] = 0;
            
            if ($likes && is_array($likes)) {
                foreach ($likes as $like) {
                    if (isset($like['usuario_id']) && $like['usuario_id'] == $user_id) {
                        $respuesta['user_liked'] = 1;
                        break;
                    }
                }
            }
        }
        
        $comentario['respuestas'] = $respuestas;
    }
    
    echo json_encode([
        'success' => true,
        'comentarios' => $comentarios
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener comentarios: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error general: ' . $e->getMessage()
    ]);
}
?>
