<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../login.php');
    exit();
}

include '../db.php';

if (!isset($_GET['id'])) {
    echo '<div class="container mt-4"><div class="alert alert-warning" role="alert">ID de publicación no especificado.</div></div>';
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("\n        SELECT \n            c.*,\n            COALESCE(u1.nombre_completo, u2.nombre_completo) as autor_nombre,\n            COALESCE(u1.foto_perfil, u2.foto_perfil) as autor_foto\n        FROM contenidos c \n        LEFT JOIN usuarios u1 ON c.id_admin = u1.id AND u1.rol = 'administrador'\n        LEFT JOIN usuarios u2 ON c.id_admin = u2.email AND u2.rol = 'administrador'\n        WHERE c.id = ?\n    ");
    $stmt->execute([$id]);
    $publicacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$publicacion) {
        echo '<div class="container mt-4"><div class="alert alert-warning" role="alert">Publicación no encontrada.</div></div>';
        exit;
    }
} catch (PDOException $e) {
    echo '<div class="container mt-4"><div class="alert alert-danger" role="alert">Error al cargar la publicación.</div></div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($publicacion['titulo']) ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .publicacion-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin: 20px auto;
            max-width: 1000px;
            overflow: hidden;
        }
        .publicacion-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            padding: 30px;
            color: white;
        }
        .badge-tipo {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-block;
        }
        .publicacion-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .publicacion-body {
            padding: 40px;
        }
        .contenido-texto {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #495057;
            margin-bottom: 30px;
        }
        .media-container {
            margin: 30px 0;
            text-align: center;
        }
        .media-container img {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .media-container video,
        .media-container iframe {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .publicacion-meta {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin: 30px 0;
        }
        .meta-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        .meta-item:last-child {
            margin-bottom: 0;
        }
        .meta-item i {
            color: #667eea;
            margin-right: 10px;
            width: 20px;
        }
        .admin-info {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin: 30px 0;
        }
        .admin-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #667eea;
        }
        .btn-volver {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .btn-volver:hover {
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .container-fluid {
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="publicacion-card">
            <div class="publicacion-header">
                <span class="badge-tipo"><?= htmlspecialchars($publicacion['tipo'] ?? 'Publicación') ?></span>
                <h1><?= htmlspecialchars($publicacion['titulo']) ?></h1>
            </div>

            <div class="publicacion-body">
                <div class="contenido-texto">
                    <?= nl2br(htmlspecialchars($publicacion['contenido_texto'])) ?>
                </div>

                <?php if (!empty($publicacion['archivo_path'])): ?>
                    <div class="media-container">
                        <?php if (in_array($publicacion['tipo'], ['imagen'])): ?>
                            <img src="../panel_admin/publicaciones/servir_archivo.php?id=<?= $publicacion['id'] ?>" class="img-fluid" alt="Archivo adjunto">
                        <?php elseif (in_array($publicacion['tipo'], ['video'])): ?>
                            <video controls class="w-100" style="max-height: 500px;">
                                <source src="../panel_admin/publicaciones/servir_archivo.php?id=<?= $publicacion['id'] ?>" type="video/mp4">
                                Tu navegador no soporta video.
                            </video>
                        <?php elseif (in_array($publicacion['tipo'], ['audio', 'podcast'])): ?>
                            <div class="p-4 bg-light rounded">
                                <audio controls class="w-100">
                                    <source src="../panel_admin/publicaciones/servir_archivo.php?id=<?= $publicacion['id'] ?>" type="audio/mpeg">
                                    Tu navegador no soporta audio.
                                </audio>
                            </div>
                        <?php else: ?>
                            <iframe src="../panel_admin/publicaciones/servir_archivo.php?id=<?= $publicacion['id'] ?>" style="width:100%; height:500px; border:none;" class="border rounded"></iframe>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="publicacion-meta">
                    <div class="meta-item">
                        <i class="far fa-calendar-alt"></i>
                        <strong>Fecha:</strong> <?= date('d M Y H:i', strtotime($publicacion['fecha_creacion'])) ?>
                    </div>
                    <div class="meta-item">
                        <i class="far fa-user"></i>
                        <strong>Publicado por:</strong> <?= htmlspecialchars($publicacion['autor_nombre'] ?? 'Administrador') ?>
                    </div>
                </div>

                <div class="admin-info">
                    <img src="<?= !empty($publicacion['autor_foto']) ? 'data:image/jpeg;base64,' . $publicacion['autor_foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($publicacion['autor_nombre'] ?? 'Admin') ?>" 
                         class="admin-avatar" alt="Autor">
                    <div>
                        <div class="font-weight-bold" style="font-size: 1.1rem;"><?= htmlspecialchars($publicacion['autor_nombre'] ?? 'Administrador') ?></div>
                        <small class="text-muted">Administrador</small>
                    </div>
                </div>

                <div class="text-center">
                    <a href="publicaciones.php" class="btn-volver">
                        <i class="fas fa-arrow-left mr-2"></i> Volver a Publicaciones
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
</body>
</html>


