<?php
include '../../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            crearSeccion();
            break;
        case 'update':
            actualizarSeccion();
            break;
        case 'delete':
            eliminarSeccion();
            break;
        case 'toggle_visibility':
            toggleVisibilidad();
            break;
        case 'reorder':
            reordenarSecciones();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

function crearSeccion() {
    global $pdo;
    
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $color = $_POST['color'] ?? '#007bff';
    $icono = $_POST['icono'] ?? 'fas fa-folder';
    $visible = isset($_POST['visible']) ? 1 : 0;
    
    if (empty($nombre)) {
        echo json_encode(['error' => 'El nombre es obligatorio']);
        return;
    }
    
    // Verificar que el nombre sea único
    $stmt = $pdo->prepare("SELECT id FROM secciones WHERE nombre = ?");
    $stmt->execute([$nombre]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Ya existe una sección con ese nombre']);
        return;
    }
    
    // Obtener el siguiente orden
    $stmt = $pdo->prepare("SELECT MAX(orden) as max_orden FROM secciones");
    $stmt->execute();
    $result = $stmt->fetch();
    $orden = ($result['max_orden'] ?? 0) + 1;
    
    $stmt = $pdo->prepare("
        INSERT INTO secciones (nombre, descripcion, color, icono, visible, orden) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$nombre, $descripcion, $color, $icono, $visible, $orden])) {
        echo json_encode(['success' => true, 'message' => 'Sección creada correctamente']);
    } else {
        echo json_encode(['error' => 'Error al crear la sección']);
    }
}

function actualizarSeccion() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $color = $_POST['color'] ?? '#007bff';
    $icono = $_POST['icono'] ?? 'fas fa-folder';
    $visible = isset($_POST['visible']) ? 1 : 0;
    
    if (empty($id) || empty($nombre)) {
        echo json_encode(['error' => 'ID y nombre son obligatorios']);
        return;
    }
    
    // Verificar que el nombre sea único (excluyendo la sección actual)
    $stmt = $pdo->prepare("SELECT id FROM secciones WHERE nombre = ? AND id != ?");
    $stmt->execute([$nombre, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Ya existe una sección con ese nombre']);
        return;
    }
    
    $stmt = $pdo->prepare("
        UPDATE secciones 
        SET nombre = ?, descripcion = ?, color = ?, icono = ?, visible = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([$nombre, $descripcion, $color, $icono, $visible, $id])) {
        echo json_encode(['success' => true, 'message' => 'Sección actualizada correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar la sección']);
    }
}

function eliminarSeccion() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    
    if (empty($id)) {
        echo json_encode(['error' => 'ID de sección requerido']);
        return;
    }
    
    // Verificar que la sección existe
    $stmt = $pdo->prepare("SELECT id FROM secciones WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Sección no encontrada']);
        return;
    }
    
    // Mover contenidos a "sin sección" (seccion_id = NULL)
    $stmt = $pdo->prepare("UPDATE contenidos SET seccion_id = NULL WHERE seccion_id = ?");
    $stmt->execute([$id]);
    
    // Eliminar la sección
    $stmt = $pdo->prepare("DELETE FROM secciones WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Sección eliminada correctamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar la sección']);
    }
}

function toggleVisibilidad() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    $visible = (int)($_POST['visible'] ?? 0);
    
    if (empty($id)) {
        echo json_encode(['error' => 'ID de sección requerido']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE secciones SET visible = ? WHERE id = ?");
    
    if ($stmt->execute([$visible, $id])) {
        echo json_encode(['success' => true, 'message' => 'Visibilidad actualizada']);
    } else {
        echo json_encode(['error' => 'Error al actualizar la visibilidad']);
    }
}

function reordenarSecciones() {
    global $pdo;
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['secciones']) || !is_array($data['secciones'])) {
        echo json_encode(['error' => 'Datos de reordenamiento inválidos']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("UPDATE secciones SET orden = ? WHERE id = ?");
        
        foreach ($data['secciones'] as $seccion) {
            $stmt->execute([$seccion['orden'], $seccion['id']]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Orden actualizado correctamente']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Error al actualizar el orden: ' . $e->getMessage()]);
    }
}
?>
