<?php
include '../../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'error' => 'ID requerido']);
    exit;
}

try {
    // Obtener datos del trabajador destacado (solo usuarios verificados)
    $stmt = $pdo->prepare("
        SELECT 
            td.*,
            u.nombre_completo,
            u.email,
            u.foto_perfil,
            u.rol,
            admin.nombre_completo as creado_por_nombre
        FROM trabajadores_destacados td
        INNER JOIN usuarios u ON td.id_usuario = u.id
        INNER JOIN usuarios admin ON td.creado_por = admin.id
        WHERE td.id = ? AND u.rol = 'usuario' AND u.verificado = 1
    ");
    
    $stmt->execute([$id]);
    $destacado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$destacado) {
        echo json_encode(['success' => false, 'error' => 'Trabajador destacado no encontrado']);
        exit;
    }
    
    // Formatear datos para el formulario
    $destacado['fecha_inicio'] = $destacado['fecha_inicio'];
    $destacado['fecha_fin'] = $destacado['fecha_fin'] ?: '';
    $destacado['mostrar_popup'] = (bool)$destacado['mostrar_popup'];
    
    // Calcular estado actual basado en fechas
    $hoy = date('Y-m-d');
    $estado = 'Inactivo';
    
    if (!$destacado['fecha_fin'] || $destacado['fecha_fin'] >= $hoy) {
        if ($destacado['fecha_inicio'] <= $hoy) {
            $estado = 'Activo';
        } else {
            $estado = 'Programado';
        }
    } else {
        $estado = 'Finalizado';
    }
    
    $destacado['estado_calculado'] = $estado;
    
    echo json_encode([
        'success' => true,
        'destacado' => $destacado
    ]);
    
} catch (PDOException $e) {
    error_log("Error PDO en obtener_destacado.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>