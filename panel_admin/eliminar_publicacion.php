<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo 'ID de publicación no especificado';
    exit;
}

$id = intval($_GET['id']);

// Verificar que la publicación pertenece al usuario actual
$stmtUser = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id = :id");
$stmtUser->bindParam(':id', $_SESSION['user_id']);
$stmtUser->execute();
$nombre_creador = $stmtUser->fetchColumn();

$stmtCheck = $pdo->prepare("SELECT archivo_path FROM contenidos WHERE id = :id AND creado_por = :creado_por");
$stmtCheck->bindParam(':id', $id);
$stmtCheck->bindParam(':creado_por', $nombre_creador);
$stmtCheck->execute();
$contenido = $stmtCheck->fetch();

if (!$contenido) {
    http_response_code(403);
    echo 'No autorizado para eliminar esta publicación';
    exit;
}

// Eliminar archivo físico si existe
if ($contenido['archivo_path']) {
    $file_path = '../' . $contenido['archivo_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Eliminar registro de la base de datos
$stmtDelete = $pdo->prepare("DELETE FROM contenidos WHERE id = :id AND creado_por = :creado_por");
$stmtDelete->bindParam(':id', $id);
$stmtDelete->bindParam(':creado_por', $nombre_creador);
if ($stmtDelete->execute()) {
    echo 'ok';
} else {
    http_response_code(500);
    echo 'Error al eliminar la publicación';
}
?>
