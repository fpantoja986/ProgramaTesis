<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

// Fetch publications created by admins
$stmt = $pdo->prepare("SELECT * FROM contenidos WHERE id_admin IN (SELECT email FROM usuarios WHERE rol = 'administrador') ORDER BY fecha_creacion DESC");
$stmt->execute();
$publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Panel de Usuario - Publicaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            min-height: 100vh;
            padding-top: 1rem;
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
    </style>
</head>
<? include('trabajador_resaltado/popup_trabajador_destacado.php'); ?>
<body>
    <div class="container-fluid">
        <div class="row no-gutters">
            <main class="col-md-9 content bg-white rounded shadow-sm p-4">
                <h1>Publicaciones de Administradores</h1>
                <?php if (count($publicaciones) === 0): ?>
                    <p>No hay publicaciones disponibles.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($publicaciones as $pub): ?>
                            <a href="../panel_admin/ver_publicacion.php?id=<?= $pub['id'] ?>" class="list-group-item list-group-item-action">
                                <h5 class="mb-1"><?= htmlspecialchars($pub['titulo']) ?></h5>
                                <small>Fecha: <?= htmlspecialchars($pub['fecha_creacion']) ?></small>
                                <p class="mb-1"><?= nl2br(htmlspecialchars(substr($pub['contenido_texto'], 0, 150))) ?>...</p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <hr />
                <a href="ajustes_usuario.php" class="btn btn-primary mt-3">Ajustes de Usuario</a>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
