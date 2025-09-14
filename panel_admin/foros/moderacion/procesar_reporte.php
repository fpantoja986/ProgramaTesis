<?php
include '../../../db.php';
session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Debe iniciar sesión para reportar contenido']);
    exit;
}

$respuesta_id = $_POST['respuesta_id'] ?? '';
$motivo = $_POST['motivo'] ?? '';
$comentario = trim($_POST['comentario'] ?? '');

// Validaciones
if (empty($respuesta_id) || empty($motivo)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // Verificar que la respuesta existe y está visible
    $stmt_check = $pdo->prepare("
        SELECT rf.*, u.nombre_completo 
        FROM respuestas_foro rf 
        INNER JOIN usuarios u ON rf.id_usuario = u.id
        WHERE rf.id = ? AND rf.visible = 1
    ");
    $stmt_check->execute([$respuesta_id]);
    $respuesta = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$respuesta) {
        echo json_encode(['success' => false, 'error' => 'Respuesta no encontrada o no disponible']);
        exit;
    }
    
    // Verificar que el usuario no esté reportando su propia respuesta
    if ($respuesta['id_usuario'] == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'No puedes reportar tu propia respuesta']);
        exit;
    }
    
    // Verificar que no haya reportado esta respuesta antes
    $stmt_duplicado = $pdo->prepare("
        SELECT id FROM reportes_respuestas 
        WHERE id_respuesta = ? AND id_reportante = ?
    ");
    $stmt_duplicado->execute([$respuesta_id, $_SESSION['user_id']]);
    
    if ($stmt_duplicado->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ya has reportado esta respuesta anteriormente']);
        exit;
    }
    
    // Insertar el reporte
    $stmt_reporte = $pdo->prepare("
        INSERT INTO reportes_respuestas (id_respuesta, id_reportante, motivo, comentario, fecha_reporte)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt_reporte->execute([$respuesta_id, $_SESSION['user_id'], $motivo, $comentario]);
    
    // Actualizar el estado de la respuesta a "reportado"
    $stmt_actualizar = $pdo->prepare("
        UPDATE respuestas_foro 
        SET estado_moderacion = 'reportado'
        WHERE id = ? AND estado_moderacion NOT IN ('rechazado', 'aprobado')
    ");
    $stmt_actualizar->execute([$respuesta_id]);
    
    // Verificar si hay múltiples reportes para marcar como contenido sensible
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) as total_reportes 
        FROM reportes_respuestas 
        WHERE id_respuesta = ? AND estado = 'pendiente'
    ");
    $stmt_count->execute([$respuesta_id]);
    $total_reportes = $stmt_count->fetchColumn();
    
    // Si hay 3 o más reportes, marcar como contenido sensible
    if ($total_reportes >= 3) {
        $stmt_sensible = $pdo->prepare("
            UPDATE respuestas_foro 
            SET contenido_sensible = 1 
            WHERE id = ?
        ");
        $stmt_sensible->execute([$respuesta_id]);
    }
    
    // Notificar a los administradores
    $stmt_admins = $pdo->prepare("
        SELECT id FROM usuarios WHERE rol = 'administrador'
    ");
    $stmt_admins->execute();
    $administradores = $stmt_admins->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($administradores)) {
        foreach ($administradores as $admin_id) {
            $stmt_notif = $pdo->prepare("
                INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, fecha_creacion)
                VALUES (?, 'moderacion', 'Nuevo reporte de contenido', ?, NOW())
            ");
            $mensaje_admin = "Se ha reportado una respuesta en el foro por: " . $motivo . ". Revisa el panel de moderación.";
            $stmt_notif->execute([$admin_id, $mensaje_admin]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Reporte enviado correctamente. Gracias por ayudar a mantener la comunidad segura.'
    ]);
    
} catch (PDOException $e) {
    error_log("Error PDO en procesar_reporte.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos. Por favor, inténtalo de nuevo.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>