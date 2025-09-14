<?php
include '../../../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'aprobar':
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('ID de respuesta requerido');
            }
            
            // Verificar que la respuesta existe
            $stmt_check = $pdo->prepare("
                SELECT rf.*, u.nombre_completo, u.email 
                FROM respuestas_foro rf 
                INNER JOIN usuarios u ON rf.id_usuario = u.id
                WHERE rf.id = ?
            ");
            $stmt_check->execute([$id]);
            $respuesta = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$respuesta) {
                throw new Exception('Respuesta no encontrada');
            }
            
            // Aprobar la respuesta
            $stmt_aprobar = $pdo->prepare("
                UPDATE respuestas_foro 
                SET estado_moderacion = 'aprobado',
                    contenido_sensible = 0,
                    visible = 1,
                    moderado_por = ?,
                    fecha_moderacion = NOW()
                WHERE id = ?
            ");
            $stmt_aprobar->execute([$_SESSION['user_id'], $id]);
            
            // Marcar reportes como resueltos
            $stmt_reportes = $pdo->prepare("
                UPDATE reportes_respuestas 
                SET estado = 'resuelto', 
                    resuelto_por = ?,
                    fecha_resolucion = NOW()
                WHERE id_respuesta = ? AND estado = 'pendiente'
            ");
            $stmt_reportes->execute([$_SESSION['user_id'], $id]);
            
            // Registrar en log
            $stmt_log = $pdo->prepare("
                INSERT INTO log_moderacion (id_respuesta, id_moderador, accion, motivo, fecha)
                VALUES (?, ?, 'aprobar', 'Respuesta aprobada por moderador', NOW())
            ");
            $stmt_log->execute([$id, $_SESSION['user_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Respuesta aprobada exitosamente'
            ]);
            break;
            
        case 'rechazar':
            $id = $_POST['id'] ?? '';
            $motivo = $_POST['motivo'] ?? 'Contenido inapropiado';
            
            if (empty($id)) {
                throw new Exception('ID de respuesta requerido');
            }
            
            // Obtener datos de la respuesta y usuario
            $stmt_check = $pdo->prepare("
                SELECT rf.*, u.nombre_completo, u.email 
                FROM respuestas_foro rf 
                INNER JOIN usuarios u ON rf.id_usuario = u.id
                WHERE rf.id = ?
            ");
            $stmt_check->execute([$id]);
            $respuesta = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$respuesta) {
                throw new Exception('Respuesta no encontrada');
            }
            
            // Marcar como rechazada (ocultar pero no eliminar)
            $stmt_rechazar = $pdo->prepare("
                UPDATE respuestas_foro 
                SET estado_moderacion = 'rechazado',
                    visible = 0,
                    moderado_por = ?,
                    fecha_moderacion = NOW(),
                    motivo_rechazo = ?
                WHERE id = ?
            ");
            $stmt_rechazar->execute([$_SESSION['user_id'], $motivo, $id]);
            
            // Registrar en log de moderación
            $stmt_log = $pdo->prepare("
                INSERT INTO log_moderacion (id_respuesta, id_moderador, accion, motivo, fecha)
                VALUES (?, ?, 'rechazar', ?, NOW())
            ");
            $stmt_log->execute([$id, $_SESSION['user_id'], $motivo]);
            
            // Marcar reportes como resueltos
            $stmt_reportes = $pdo->prepare("
                UPDATE reportes_respuestas 
                SET estado = 'resuelto', 
                    resuelto_por = ?,
                    fecha_resolucion = NOW()
                WHERE id_respuesta = ? AND estado = 'pendiente'
            ");
            $stmt_reportes->execute([$_SESSION['user_id'], $id]);
            
            // Enviar notificación al usuario
            $stmt_notif = $pdo->prepare("
                INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, fecha_creacion)
                VALUES (?, 'moderacion', 'Contenido moderado', ?, NOW())
            ");
            $mensaje_notif = "Tu respuesta ha sido moderada por incumplir las normas de la comunidad. Motivo: " . $motivo;
            $stmt_notif->execute([$respuesta['id_usuario'], $mensaje_notif]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Respuesta rechazada y usuario notificado'
            ]);
            break;
            
        case 'advertir':
            $respuesta_id = $_POST['respuesta_id'] ?? '';
            $motivo = trim($_POST['motivo'] ?? '');
            $ocultar = isset($_POST['ocultar_respuesta']) ? 1 : 0;
            
            if (empty($respuesta_id) || empty($motivo)) {
                throw new Exception('Datos requeridos incompletos');
            }
            
            // Obtener datos de la respuesta y usuario
            $stmt_check = $pdo->prepare("
                SELECT rf.*, u.nombre_completo, u.email 
                FROM respuestas_foro rf 
                INNER JOIN usuarios u ON rf.id_usuario = u.id
                WHERE rf.id = ?
            ");
            $stmt_check->execute([$respuesta_id]);
            $respuesta = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$respuesta) {
                throw new Exception('Respuesta no encontrada');
            }
            
            // Actualizar estado de la respuesta
            $stmt_actualizar = $pdo->prepare("
                UPDATE respuestas_foro 
                SET estado_moderacion = 'advertencia',
                    visible = ?,
                    moderado_por = ?,
                    fecha_moderacion = NOW()
                WHERE id = ?
            ");
            $stmt_actualizar->execute([$ocultar ? 0 : 1, $_SESSION['user_id'], $respuesta_id]);
            
            // Registrar advertencia
            $stmt_advertencia = $pdo->prepare("
                INSERT INTO advertencias_usuarios (id_usuario, id_respuesta, motivo, creado_por, fecha_creacion)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt_advertencia->execute([$respuesta['id_usuario'], $respuesta_id, $motivo, $_SESSION['user_id']]);
            
            // Registrar en log de moderación
            $stmt_log = $pdo->prepare("
                INSERT INTO log_moderacion (id_respuesta, id_moderador, accion, motivo, fecha)
                VALUES (?, ?, 'advertir', ?, NOW())
            ");
            $stmt_log->execute([$respuesta_id, $_SESSION['user_id'], $motivo]);
            
            // Enviar notificación al usuario
            $stmt_notif = $pdo->prepare("
                INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, fecha_creacion)
                VALUES (?, 'advertencia', 'Advertencia de moderación', ?, NOW())
            ");
            $mensaje_notif = "Has recibido una advertencia por tu respuesta en el foro. Motivo: " . $motivo . ". Por favor, revisa las normas de la comunidad.";
            $stmt_notif->execute([$respuesta['id_usuario'], $mensaje_notif]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Advertencia enviada al usuario'
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Error PDO en procesar_moderacion.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos. Por favor, inténtalo de nuevo.'
    ]);
}
?>