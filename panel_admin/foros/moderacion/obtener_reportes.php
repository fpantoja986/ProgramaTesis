<?php
include '../../../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$respuesta_id = $_GET['respuesta_id'] ?? '';

if (empty($respuesta_id)) {
    echo json_encode(['success' => false, 'error' => 'ID de respuesta requerido']);
    exit;
}

try {
    // Obtener reportes de la respuesta
    $stmt = $pdo->prepare("
        SELECT rr.*, 
               u.nombre_completo as reportante_nombre,
               u.email as reportante_email,
               DATE_FORMAT(rr.fecha_reporte, '%d/%m/%Y %H:%i') as fecha_reporte_format
        FROM reportes_respuestas rr
        INNER JOIN usuarios u ON rr.id_reportante = u.id
        WHERE rr.id_respuesta = ?
        ORDER BY rr.fecha_reporte DESC
    ");
    
    $stmt->execute([$respuesta_id]);
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para el frontend
    foreach ($reportes as &$reporte) {
        $reporte['fecha_reporte'] = $reporte['fecha_reporte_format'];
        // Traducir motivos al español
        switch ($reporte['motivo']) {
            case 'spam':
                $reporte['motivo_texto'] = 'Spam o contenido repetitivo';
                break;
            case 'contenido_ofensivo':
                $reporte['motivo_texto'] = 'Contenido ofensivo';
                break;
            case 'informacion_falsa':
                $reporte['motivo_texto'] = 'Información falsa';
                break;
            case 'acoso':
                $reporte['motivo_texto'] = 'Acoso o intimidación';
                break;
            case 'contenido_inapropiado':
                $reporte['motivo_texto'] = 'Contenido inapropiado';
                break;
            case 'otro':
                $reporte['motivo_texto'] = 'Otro motivo';
                break;
            default:
                $reporte['motivo_texto'] = ucfirst($reporte['motivo']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'reportes' => $reportes
    ]);
    
} catch (PDOException $e) {
    error_log("Error PDO en obtener_reportes.php: " . $e->getMessage());
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