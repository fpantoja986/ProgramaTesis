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

$contenido = trim($_POST['contenido'] ?? '');
$id_tema = (int)($_POST['id_tema'] ?? 0);
$id_respuesta_padre = !empty($_POST['id_respuesta_padre']) ? (int)$_POST['id_respuesta_padre'] : null;

if (empty($contenido) || empty($id_tema)) {
    http_response_code(400);
    echo json_encode(['error' => 'El contenido es obligatorio']);
    exit;
}

try {
    // Verificar que el tema existe y no está cerrado
    $stmt = $pdo->prepare("SELECT cerrado FROM temas_foro WHERE id = :id");
    $stmt->bindParam(':id', $id_tema);
    $stmt->execute();
    $tema = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tema) {
        http_response_code(404);
        echo json_encode(['error' => 'El tema no existe']);
        exit;
    }
    
    if ($tema['cerrado']) {
        http_response_code(400);
        echo json_encode(['error' => 'Este tema está cerrado']);
        exit;
    }

    // Si es una respuesta a otra respuesta, verificar que existe
    if ($id_respuesta_padre) {
        $stmt = $pdo->prepare("SELECT id FROM respuestas_foro WHERE id = :id AND id_tema = :id_tema");
        $stmt->bindParam(':id', $id_respuesta_padre);
        $stmt->bindParam(':id_tema', $id_tema);
        $stmt->execute();
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'La respuesta padre no existe']);
            exit;
        }
    }

    // Insertar la nueva respuesta
    $stmt = $pdo->prepare("
        INSERT INTO respuestas_foro (contenido, id_tema, id_respuesta_padre, id_usuario, fecha_creacion) 
        VALUES (:contenido, :id_tema, :id_respuesta_padre, :id_usuario, NOW())
    ");
    
    $stmt->bindParam(':contenido', $contenido);
    $stmt->bindParam(':id_tema', $id_tema);
    $stmt->bindParam(':id_respuesta_padre', $id_respuesta_padre);
    $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $respuesta_id = $pdo->lastInsertId();
        
        // Crear notificaciones
        if ($id_respuesta_padre) {
            // Notificar al autor de la respuesta padre
            $stmt = $pdo->prepare("SELECT id_usuario FROM respuestas_foro WHERE id = ?");
            $stmt->execute([$id_respuesta_padre]);
            $autor_respuesta = $stmt->fetchColumn();
            
            if ($autor_respuesta != $_SESSION['user_id']) {
                $stmt = $pdo->prepare("
                    INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, fecha_creacion) 
                    VALUES (?, 'Nueva respuesta a tu comentario', ?, 'respuesta', NOW())
                ");
                $stmt->execute([$autor_respuesta, substr($contenido, 0, 100)]);
            }
        } else {
            // Notificar al autor del tema (si no es el mismo usuario)
            $stmt = $pdo->prepare("SELECT id_usuario FROM temas_foro WHERE id = ?");
            $stmt->execute([$id_tema]);
            $autor_tema = $stmt->fetchColumn();
            
            if ($autor_tema != $_SESSION['user_id']) {
                $stmt = $pdo->prepare("
                    INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, fecha_creacion) 
                    VALUES (?, 'Nueva respuesta en tu tema', ?, 'respuesta', NOW())
                ");
                $stmt->execute([$autor_tema, substr($contenido, 0, 100)]);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Respuesta creada exitosamente',
            'respuesta_id' => $respuesta_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear la respuesta']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
