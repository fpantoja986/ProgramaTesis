<?php
include '../db.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "ID de contenido no especificado.";
    exit;
}

$id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT archivo_path, tipo FROM contenidos WHERE id = ?");
$stmt->execute([$id]);
$contenido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contenido || empty($contenido['archivo_path'])) {
    http_response_code(404);
    echo "Archivo no encontrado.";
    exit;
}

// Decodificar base64
$archivo_data = base64_decode($contenido['archivo_path']);

// Determinar tipo MIME específico según tipo de contenido
$mime_type = 'application/octet-stream';
switch ($contenido['tipo']) {
    case 'imagen':
        $mime_type = 'image/jpeg'; // assuming jpeg, adjust if needed
        break;
    case 'video':
        $mime_type = 'video/mp4'; // assuming mp4, adjust if needed
        break;
    case 'audio':
        $mime_type = 'audio/mpeg'; // assuming mp3, adjust if needed
        break;
    case 'articulo':
        $mime_type = 'text/plain';
        break;
    case 'podcast':
        $mime_type = 'audio/mpeg';
        break;
    default:
        $mime_type = 'application/octet-stream';
        break;
}

// Support HTTP Range requests for streaming
$size = strlen($archivo_data);
$length = $size;
$start = 0;
$end = $size - 1;

header("Content-Type: $mime_type");
header('Accept-Ranges: bytes');

if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    list(, $range) = explode('=', $range, 2);
    if (strpos($range, ',') !== false) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
    }
    if ($range == '-') {
        $start = $size - substr($range, 1);
    } else {
        $range = explode('-', $range);
        $start = intval($range[0]);
        $end = (isset($range[1]) && is_numeric($range[1])) ? intval($range[1]) : $end;
    }
    if ($start > $end || $start > $size - 1 || $end >= $size) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
    }
    $length = $end - $start + 1;
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$size");
} else {
    header('HTTP/1.1 200 OK');
}

header("Content-Length: $length");
header('Content-Disposition: inline; filename="archivo"');

echo substr($archivo_data, $start, $length);
exit;
?>