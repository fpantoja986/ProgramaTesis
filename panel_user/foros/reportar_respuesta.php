<?php
include '../../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar si es admin o usuario
$es_admin = ($_SESSION['rol'] === 'administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$id_respuesta = (int)($_POST['id_respuesta'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$mensaje_admin = trim($_POST['mensaje_admin'] ?? ''); // Solo para admins

if (empty($id_respuesta) || empty($motivo)) {
    http_response_code(400);
    echo json_encode(['error' => 'El motivo del reporte es obligatorio']);
    exit;
}

// Si es admin, el mensaje personalizado es obligatorio
if ($es_admin && empty($mensaje_admin)) {
    http_response_code(400);
    echo json_encode(['error' => 'El mensaje del administrador es obligatorio']);
    exit;
}

// Motivos válidos
$motivos_validos = ['spam', 'inapropiado', 'acoso', 'desinformacion', 'violencia', 'otro'];
if (!in_array($motivo, $motivos_validos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Motivo de reporte no válido']);
    exit;
}

try {
    // Verificar que la respuesta existe
    $stmt = $pdo->prepare("
        SELECT r.id, r.id_usuario, r.contenido, u.nombre_completo as autor_nombre
        FROM respuestas_foro r 
        INNER JOIN usuarios u ON r.id_usuario = u.id 
        WHERE r.id = :id_respuesta
    ");
    $stmt->bindParam(':id_respuesta', $id_respuesta);
    $stmt->execute();
    $respuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$respuesta) {
        http_response_code(404);
        echo json_encode(['error' => 'La respuesta no existe']);
        exit;
    }
    
    // Verificar que no se está reportando a sí mismo
    if ($respuesta['id_usuario'] == $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'No puedes reportar tu propia respuesta']);
        exit;
    }
    
    // Verificar que no se ha reportado esta respuesta antes por el mismo usuario
    $stmt = $pdo->prepare("
        SELECT id FROM reportes_respuestas 
        WHERE id_respuesta = :id_respuesta AND id_usuario_reportador = :id_usuario
    ");
    $stmt->bindParam(':id_respuesta', $id_respuesta);
    $stmt->bindParam(':id_usuario', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Ya has reportado esta respuesta anteriormente']);
        exit;
    }
    
    // Insertar el reporte
    $stmt = $pdo->prepare("
        INSERT INTO reportes_respuestas 
        (id_respuesta, id_usuario_reportador, id_usuario_reportado, motivo, descripcion, estado, fecha_reporte) 
        VALUES (:id_respuesta, :id_usuario_reportador, :id_usuario_reportado, :motivo, :descripcion, 'pendiente', NOW())
    ");
    
    $stmt->bindParam(':id_respuesta', $id_respuesta);
    $stmt->bindParam(':id_usuario_reportador', $_SESSION['user_id']);
    $stmt->bindParam(':id_usuario_reportado', $respuesta['id_usuario']);
    $stmt->bindParam(':motivo', $motivo);
    $stmt->bindParam(':descripcion', $descripcion);
    
    if ($stmt->execute()) {
        $reporte_id = $pdo->lastInsertId();
        
        if ($es_admin) {
            // Si es admin reportando, notificar directamente al usuario con mensaje personalizado
            $titulo_notificacion = "Tu respuesta ha sido reportada por un administrador";
            $mensaje_notificacion = "Un administrador ha reportado tu respuesta por: " . ucfirst($motivo) . ".\n\nMensaje del administrador:\n" . $mensaje_admin;
            
            $stmt = $pdo->prepare("
                INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, fecha_creacion) 
                VALUES (?, ?, ?, 'reporte_admin', NOW())
            ");
            $stmt->execute([$respuesta['id_usuario'], $titulo_notificacion, $mensaje_notificacion]);
            
            // Marcar el reporte como resuelto automáticamente
            $stmt = $pdo->prepare("
                UPDATE reportes_respuestas 
                SET estado = 'resuelto', fecha_revision = NOW(), id_admin_revisor = ? 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $reporte_id]);
            
        } else {
            // Si es usuario reportando, notificar solo a administradores
            $stmt = $pdo->prepare("
                SELECT id FROM usuarios WHERE rol = 'administrador'
            ");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($admins as $admin_id) {
                $stmt = $pdo->prepare("
                    INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, fecha_creacion) 
                    VALUES (?, 'Nuevo reporte de respuesta', ?, 'reporte', NOW())
                ");
                $mensaje_admin_notif = "Nueva respuesta reportada por " . $motivo . ". Revisar reporte #" . $reporte_id;
                $stmt->execute([$admin_id, $mensaje_admin_notif]);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $es_admin ? 'Reporte enviado directamente al usuario' : 'Reporte enviado a administradores para revisión',
            'reporte_id' => $reporte_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el reporte']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
