<?php
include '../db.php';
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
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6f42c1;
            --accent-color: #36b9cc;
            --light-bg: #f8f9fc;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #5a5c69;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            min-height: 100vh;
            padding-top: 1rem;
            box-shadow: var(--card-shadow);
        }

        .content {
            padding: 2rem;
            margin-left: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content > * {
            width: 100%;
            max-width: 900px;
        }
        
        .publicacion-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .publicacion-card:hover {
            transform: translateY(-5px);
        }
        
        .publicacion-header {
            background: linear-gradient(120deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        
        .badge-tipo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }
        
        .publicacion-body {
            padding: 2rem;
        }
        
        .publicacion-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e3e6f0;
            color: #858796;
            font-size: 0.9rem;
        }
        
        .media-container {
            margin: 1.5rem 0;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        
        .btn-volver {
            background: linear-gradient(120deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            margin-top: 1.5rem;
        }
        
        .btn-volver:hover {
            transform: translateX(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            color: white;
        }
        
        .btn-volver i {
            margin-right: 0.5rem;
        }
        
        .contenido-texto {
            line-height: 1.8;
            font-size: 1.05rem;
            color: #4a4b4d;
        }
        
        @media (max-width: 768px) {
            .publicacion-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .meta-item {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row no-gutters">
            <?php include 'panel_sidebar.php'; ?>
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