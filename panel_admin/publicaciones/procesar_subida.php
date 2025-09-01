<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo_contenido = trim($_POST['tipo_contenido'] ?? '');
    $contenido_texto = trim($_POST['contenido_texto'] ?? '') ?: null;
    $archivo_path = null;

    // Validar título y tipo de contenido
    if (empty($titulo) || empty($tipo_contenido)) {
        $_SESSION['mensaje_error'] = 'Título y tipo de contenido son obligatorios.';
        header('Location: subir_contenido.php');
        exit;
    }

    // Procesar archivo si no es artículo o si es artículo con PDF
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['archivo']['tmp_name'];
        $file_name = basename($_FILES['archivo']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validar extensiones según tipo de contenido
        $allowed_exts = [];
        switch($tipo_contenido) {
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

        // Validar tamaño del archivo (50MB máximo)
        if ($_FILES['archivo']['size'] > 50 * 1024 * 1024) {
            $_SESSION['mensaje_error'] = 'El archivo es demasiado grande. Tamaño máximo: 50MB.';
            header('Location: subir_contenido.php');
            exit;
        }

        if (!in_array($file_ext, $allowed_exts)) {
            $_SESSION['mensaje_error'] = 'Tipo de archivo no permitido para ' . $tipo_contenido . '.';
            header('Location: subir_contenido.php');
            exit;
        }

        // Leer contenido del archivo
        $file_data = file_get_contents($file_tmp);
        if ($file_data === false) {
            $_SESSION['mensaje_error'] = 'Error al leer el archivo.';
            header('Location: subir_contenido.php');
            exit;
        }
        $archivo_path = base64_encode($file_data);
    }

    try {
        // Insertar en base de datos con la nueva estructura
        $sql = "INSERT INTO contenidos (titulo, tipo, contenido_texto, archivo_path, id_admin, fecha_creacion) VALUES (:titulo, :tipo, :contenido_texto, :archivo_path, :id_admin, NOW())";
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':titulo', $titulo, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $tipo_contenido, PDO::PARAM_STR);
        $stmt->bindParam(':contenido_texto', $contenido_texto, PDO::PARAM_STR);
        $stmt->bindParam(':archivo_path', $archivo_path, PDO::PARAM_LOB);
        $stmt->bindParam(':id_admin', $_SESSION['user_id'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            $nuevo_id = $pdo->lastInsertId();
            $_SESSION['mensaje_exito'] = 'Contenido subido exitosamente con ID: ' . $nuevo_id;
            header('Location: subir_contenido.php');
            exit;
        } else {
            $_SESSION['mensaje_error'] = 'Error al guardar el contenido en la base de datos.';
            header('Location: subir_contenido.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['mensaje_error'] = 'Error en la base de datos: ' . $e->getMessage();
        header('Location: subir_contenido.php');
        exit;
    }
} else {
    header('Location: subir_contenido.php');
    exit;
}
?>