<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $tipo_contenido = $_POST['tipo_contenido'] ?? '';
    $contenido_texto = $_POST['contenido_texto'] ?? null;
    $archivo_path = null;

    // Validar título y tipo de contenido
    if (empty($titulo) || empty($tipo_contenido)) {
        $_SESSION['mensaje_error'] = 'Título y tipo de contenido son obligatorios.';
        header('Location: subir_contenido.php');
        exit;
    }

    // Procesar archivo si no es artículo
    if ($tipo_contenido !== 'articulo' && isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['archivo']['tmp_name'];
        $file_name = basename($_FILES['archivo']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['pdf', 'mp3', 'wav', 'mp4', 'avi', 'mov', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'doc', 'docx', 'odt', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx'];

        if (!in_array($file_ext, $allowed_exts)) {
            $_SESSION['mensaje_error'] = 'Tipo de archivo no permitido.';
            header('Location: subir_contenido.php');
            exit;
        }

        // Leer contenido del archivo
        $file_data = file_get_contents($file_tmp);
        $archivo_path = base64_encode($file_data);
    }

    // Obtener nombre del usuario creador
    $stmtUser = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id = :id");
    $stmtUser->bindParam(':id', $_SESSION['user_id']);
    $stmtUser->execute();
    $nombre_creador = $stmtUser->fetchColumn();

    // Insertar en base de datos
    $sql = "INSERT INTO contenidos (titulo, tipo, contenido_texto, archivo_path, creado_por, fecha_creacion) VALUES (:titulo, :tipo, :contenido_texto, :archivo_path, :creado_por, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':titulo', $titulo);
    $stmt->bindParam(':tipo', $tipo_contenido);
    $stmt->bindParam(':contenido_texto', $contenido_texto);
    $stmt->bindParam(':archivo_path', $archivo_path, PDO::PARAM_LOB);
    $stmt->bindParam(':creado_por', $nombre_creador);

    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = 'Contenido subido exitosamente.';
        header('Location: subir_contenido.php');
        exit;
    } else {
        $_SESSION['mensaje_error'] = 'Error al guardar el contenido en la base de datos.';
        header('Location: subir_contenido.php');
        exit;
    }
} else {
    header('Location: subir_contenido.php');
    exit;
}
?>
