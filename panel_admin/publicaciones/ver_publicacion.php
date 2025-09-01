<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id'])) {
    echo '<div class="container mt-4"><div class="alert alert-warning" role="alert">ID de publicación no especificado.</div></div>';
    exit;
}

$id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM contenidos WHERE id = ?");
$stmt->execute([$id]);
$publicacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publicacion) {
    echo '<div class="container mt-4"><div class="alert alert-warning" role="alert">Publicación no encontrada.</div></div>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title><?= htmlspecialchars($publicacion['titulo']) ?></title>
    <link rel="stylesheet" href="styles_publicaciones.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row no-gutters">
            <?php include '../panel_sidebar.php'; ?>
            <main class="col-md-9 content">
                <div class="publicacion-card">
                    <div class="publicacion-header">
                        <span class="badge-tipo"><?= htmlspecialchars($publicacion['tipo']) ?></span>
                        <h1 class="h3 mb-0"><?= htmlspecialchars($publicacion['titulo']) ?></h1>
                    </div>

                    <div class="publicacion-body">
                        <h5 class="text-primary mb-3">Descripción:</h5>
                        <div class="contenido-texto">
                            <?= nl2br(htmlspecialchars($publicacion['contenido_texto'])) ?>
                        </div>

                        <?php if ($publicacion['archivo_path']): ?>
                            <div class="media-container">
                                <?php
                                $tipo = $publicacion['tipo'];
                                $id = $publicacion['id'];
                                if (in_array($tipo, ['imagen'])): ?>
                                    <img src="servir_archivo.php?id=<?= $id ?>" alt="Archivo adjunto" class="img-fluid w-100" />
                                <?php elseif (in_array($tipo, ['video'])): ?>
                                    <video controls class="w-100" style="max-height: 500px;">
                                        <source src="servir_archivo.php?id=<?= $id ?>" type="video/mp4" />
                                        Tu navegador no soporta video.
                                    </video>
                                <?php elseif (in_array($tipo, ['audio', 'podcast'])): ?>
                                    <div class="p-4 bg-light rounded">
                                        <audio controls class="w-100">
                                            <source src="servir_archivo.php?id=<?= $id ?>" type="audio/mpeg" />
                                            Tu navegador no soporta audio.
                                        </audio>
                                    </div>
                                <?php else: ?>
                                    <iframe src="servir_archivo.php?id=<?= $id ?>" style="width:100%; height:500px; border:none;" class="border rounded"></iframe>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="publicacion-meta">
                            <div class="meta-item">
                                <i class="far fa-calendar-alt mr-1"></i>
                                <strong>Fecha:</strong> <?= htmlspecialchars($publicacion['fecha_creacion']) ?>
                            </div>
                            <div class="meta-item">
                                <i class="far fa-user mr-1"></i>
                                <strong>Publicado por:</strong> <?= htmlspecialchars($publicacion['creado_por']) ?>
                            </div>
                        </div>

                        <a href="mis_publicaciones.php" class="btn btn-volver">
                            <i class="fas fa-arrow-left"></i> Volver a Mis Publicaciones
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
</body>

</html>