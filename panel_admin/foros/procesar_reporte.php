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

$id_reporte = (int)($_POST['id_reporte'] ?? 0);
$nuevo_estado = trim($_POST['nuevo_estado'] ?? '');
$mensaje_usuario = trim($_POST['mensaje_usuario'] ?? ''); // Mensaje opcional para el usuario

if (empty($id_reporte) || empty($nuevo_estado)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros requeridos']);
    exit;
}

// Estados válidos
$estados_validos = ['pendiente', 'revisado', 'resuelto', 'rechazado'];
if (!in_array($nuevo_estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Estado no válido']);
    exit;
}

try {
    // Obtener información del reporte
    $stmt = $pdo->prepare("
        SELECT r.*, resp.contenido as respuesta_contenido, u.nombre_completo as reportado_nombre
        FROM reportes_respuestas r
        INNER JOIN respuestas_foro resp ON r.id_respuesta = resp.id
        INNER JOIN usuarios u ON r.id_usuario_reportado = u.id
        WHERE r.id = :id_reporte
    ");
    $stmt->bindParam(':id_reporte', $id_reporte);
    $stmt->execute();
    $reporte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reporte) {
        http_response_code(404);
        echo json_encode(['error' => 'Reporte no encontrado']);
        exit;
    }
    
    // Actualizar el estado del reporte
    $stmt = $pdo->prepare("
        UPDATE reportes_respuestas 
        SET estado = :nuevo_estado, 
            fecha_revision = NOW(), 
            id_admin_revisor = :id_admin
        WHERE id = :id_reporte
    ");
    $stmt->bindParam(':nuevo_estado', $nuevo_estado);
    $stmt->bindParam(':id_admin', $_SESSION['user_id']);
    $stmt->bindParam(':id_reporte', $id_reporte);
    
    if ($stmt->execute()) {
        // Crear notificación para el usuario reportado según el estado
        $titulo_notificacion = '';
        $mensaje_notificacion = '';
        
        switch ($nuevo_estado) {
            case 'revisado':
                $titulo_notificacion = "Tu respuesta está siendo revisada";
                $mensaje_notificacion = "Tu respuesta reportada está siendo revisada por los administradores. Te notificaremos cuando se tome una decisión.";
                break;
            case 'resuelto':
                $titulo_notificacion = "Reporte resuelto";
                $mensaje_notificacion = "El reporte sobre tu respuesta ha sido resuelto. La respuesta ha sido moderada según las políticas de la comunidad.";
                if (!empty($mensaje_usuario)) {
                    $mensaje_notificacion .= "\n\nMensaje del administrador:\n" . $mensaje_usuario;
                }
                break;
            case 'rechazado':
                $titulo_notificacion = "Reporte rechazado";
                $mensaje_notificacion = "El reporte sobre tu respuesta ha sido rechazado. No se encontraron violaciones a las políticas de la comunidad.";
                if (!empty($mensaje_usuario)) {
                    $mensaje_notificacion .= "\n\nMensaje del administrador:\n" . $mensaje_usuario;
                }
                break;
        }
        
        if (!empty($titulo_notificacion)) {
            $stmt = $pdo->prepare("
                INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, fecha_creacion) 
                VALUES (?, ?, ?, 'reporte', NOW())
            ");
            $stmt->execute([$reporte['id_usuario_reportado'], $titulo_notificacion, $mensaje_notificacion]);
        }
        
        // Si se resuelve el reporte, también notificar al reportador
        if ($nuevo_estado === 'resuelto') {
            $stmt = $pdo->prepare("
                INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, fecha_creacion) 
                VALUES (?, 'Reporte resuelto', 'El reporte que enviaste ha sido resuelto. Gracias por mantener la comunidad segura.', 'reporte', NOW())
            ");
            $stmt->execute([$reporte['id_usuario_reportador']]);
        }
        
        $mensajes = [
            'revisado' => 'Reporte marcado como revisado',
            'resuelto' => 'Reporte resuelto exitosamente',
            'rechazado' => 'Reporte rechazado'
        ];
        
        echo json_encode([
            'success' => true,
            'message' => $mensajes[$nuevo_estado]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el reporte']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
