<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// Obtener el nombre completo del usuario actual
$stmtUser = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id = :id");
$stmtUser->bindParam(':id', $_SESSION['user_id']);
$stmtUser->execute();
$nombre_creador = $stmtUser->fetchColumn();

// Obtener publicaciones creadas por el usuario
$stmt = $pdo->prepare("SELECT * FROM contenidos WHERE creado_por = :creado_por ORDER BY fecha_creacion DESC");
$stmt->bindParam(':creado_por', $nombre_creador);
$stmt->execute();
$publicaciones = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mis Publicaciones</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="stylesadmin.css">

    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6f42c1;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --hover-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }

        body {
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-container {
            margin-left: 90px;
            padding: 20px;
            transition: all 0.3s;
        }

        @media (max-width: 768px) {
            .admin-container {
                margin-left: 0;
                margin-top: 70px;
                padding: 15px;
            }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e3e6f0;
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.8rem;
        }

        .publication-count {
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            font-size: 0.9rem;
        }

        .no-publications {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }

        .no-publications i {
            font-size: 4rem;
            color: #d1d3e2;
            margin-bottom: 20px;
        }

        .no-publications p {
            font-size: 1.2rem;
            color: var(--dark-text);
            margin-bottom: 25px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: #3a5fc8;
            border-color: #3a5fc8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(78, 115, 223, 0.3);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-danger:hover {
            background-color: #d52a1a;
            border-color: #d52a1a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 74, 59, 0.3);
        }

        .publication-card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }

        .publication-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
            cursor: pointer;
        }

        .card-img-container {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .card-img-top {
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .publication-card:hover .card-img-top {
            transform: scale(1.05);
        }

        .media-icon-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 200px;
            background: linear-gradient(135deg, #f8f9fc 0%, #e3e6f0 100%);
        }

        .card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: calc(100% - 200px);
        }

        .card-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 12px;
            font-size: 1.2rem;
        }

        .card-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s;
        }

        .card-title a:hover {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .card-text {
            color: var(--dark-text);
            flex-grow: 1;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .card-footer {
            background-color: transparent;
            border-top: 1px solid #e3e6f0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .publication-date {
            color: #858796;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .badge-tipo {
            position: absolute;
            top: 12px;
            right: 12px;
            background-color: rgba(255, 255, 255, 0.9);
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .media-icon {
            font-size: 3.5rem;
            color: var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #d1d3e2;
            margin-bottom: 20px;
        }

        .empty-state-text {
            font-size: 1.2rem;
            color: var(--dark-text);
            margin-bottom: 25px;
        }

        .create-btn {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }

        .create-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(78, 115, 223, 0.4);
            color: white;
        }
    </style>
</head>

<body>
    <?php include 'panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Mis Publicaciones <span class="publication-count"><?= count($publicaciones) ?></span>
            </h1>
        </div>

        <?php if (count($publicaciones) === 0): ?>
            <div class="no-publications">
                <i class="fas fa-file-alt"></i>
                <p>No tienes publicaciones aún.</p>
                <a href="subir_contenido.php" class="create-btn">
                    <i class="fas fa-plus-circle mr-2"></i>Crear primera publicación
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($publicaciones as $pub): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 publication-card" tabindex="0" role="button" aria-pressed="false"
                            data-id="<?= $pub['id'] ?>"
                            onclick="if(!event.target.closest('.btn, button, a')) { window.location.href='ver_publicacion.php?id=<?= $pub['id'] ?>'; }">
                            <span class="badge-tipo"><?= ucfirst($pub['tipo']) ?></span>

                            <?php if ($pub['archivo_path']): ?>
                                <?php if ($pub['tipo'] === 'imagen'): ?>
                                    <div class="card-img-container">
                                        <img src="servir_archivo.php?id=<?= $pub['id'] ?>" class="card-img-top" alt="Imagen">
                                    </div>
                                <?php elseif ($pub['tipo'] === 'video'): ?>
                                    <div class="media-icon-container">
                                        <i class="fas fa-play-circle media-icon"></i>
                                    </div>
                                <?php elseif ($pub['tipo'] === 'audio' || $pub['tipo'] === 'podcast'): ?>
                                    <div class="media-icon-container">
                                        <i class="fas fa-headphones media-icon"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="media-icon-container">
                                        <i class="fas fa-file-alt media-icon"></i>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="media-icon-container">
                                    <i class="fas fa-file-alt media-icon"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="card-title">
                                    <a
                                        href="ver_publicacion.php?id=<?= $pub['id'] ?>"><?= htmlspecialchars(mb_strimwidth($pub['titulo'], 0, 60, '...')) ?></a>
                                </h5>
                                <p class="card-text">
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($pub['contenido_texto'], 0, 120, '...'))) ?></p>
                            </div>

                            <div class="card-footer">
                                <small class="publication-date">
                                    <i
                                        class="far fa-calendar-alt mr-1"></i><?= date('d M Y', strtotime($pub['fecha_creacion'])) ?>
                                </small>
                                <div class="action-buttons">
                                    <a href="editar_publicacion.php?id=<?= $pub['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?= $pub['id'] ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.btn-eliminar').forEach(button => {
            button.addEventListener('click', function (e) {
                e.stopPropagation(); // Prevent card click event
                const id = this.getAttribute('data-id');
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción eliminará la publicación de forma permanente.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('eliminar_publicacion.php?id=' + id)
                            .then(response => {
                                if (!response.ok) throw new Error('Error al eliminar');
                                return response.text();
                            })
                            .then(() => {
                                Swal.fire({
                                    title: '¡Eliminado!',
                                    text: 'La publicación ha sido eliminada con éxito.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            })
                            .catch(() => {
                                Swal.fire('Error', 'No se pudo eliminar la publicación.', 'error');
                            });
                    }
                });
            });
        });
    </script>
</body>

</html>