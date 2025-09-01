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

$publicacion_id = (int)($_POST['id'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$tipo = trim($_POST['tipo'] ?? '');
$contenido_texto = trim($_POST['contenido_texto'] ?? '') ?: null;

if (empty($publicacion_id) || empty($titulo) || empty($tipo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos requeridos faltantes']);
    exit;
}

try {
    // Verificar que la publicación existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT id_admin FROM contenidos WHERE id = :id");
    $stmt->bindParam(':id', $publicacion_id, PDO::PARAM_INT);
    $stmt->execute();
    $publicacion = $stmt->fetch();

    if (!$publicacion) {
        http_response_code(404);
        echo json_encode(['error' => 'Publicación no encontrada']);
        exit;
    }

    if ($publicacion['id_admin'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para editar esta publicación']);
        exit;
    }

    // Procesar archivo si se subió uno nuevo
    $archivo_path = null;
    $actualizar_archivo = false;

    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['archivo']['tmp_name'];
        $file_name = basename($_FILES['archivo']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validar extensiones según tipo de contenido
        $allowed_exts = [];
        switch($tipo) {
            case 'articulo':
                $allowed_exts = ['pdf'];
                break;
            case 'video':
                $allowed_exts = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
                break;
            case 'podcast':
                $allowed_exts = ['mp3', 'wav', 'ogg', 'aac', 'flac'];
                break;
            case 'imagen':
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
                break;
        }

        if ($_FILES['archivo']['size'] > 50 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'El archivo es demasiado grande. Tamaño máximo: 50MB']);
            exit;
        }

        if (!in_array($file_ext, $allowed_exts)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo de archivo no permitido para ' . $tipo]);
            exit;
        }

        $file_data = file_get_contents($file_tmp);
        if ($file_data === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al leer el archivo']);
            exit;
        }
        
        $archivo_path = base64_encode($file_data);
        $actualizar_archivo = true;
    }

    // Construir consulta de actualización
    if ($actualizar_archivo) {
        $sql = "UPDATE contenidos SET titulo = :titulo, tipo = :tipo, contenido_texto = :contenido_texto, archivo_path = :archivo_path WHERE id = :id AND id_admin = :id_admin";
    } else {
        $sql = "UPDATE contenidos SET titulo = :titulo, tipo = :tipo, contenido_texto = :contenido_texto WHERE id = :id AND id_admin = :id_admin";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':titulo', $titulo, PDO::PARAM_STR);
    $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
    $stmt->bindParam(':contenido_texto', $contenido_texto, PDO::PARAM_STR);
    $stmt->bindParam(':id', $publicacion_id, PDO::PARAM_INT);
    $stmt->bindParam(':id_admin', $_SESSION['user_id'], PDO::PARAM_INT);
    
    if ($actualizar_archivo) {
        $stmt->bindParam(':archivo_path', $archivo_path, PDO::PARAM_LOB);
    }

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Publicación actualizada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se realizaron cambios o error al actualizar']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>