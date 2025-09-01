<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$foro_id = (int)($_GET['id'] ?? 0);

if (!$foro_id) {
    header('Location: lista_foros.php');
    exit;
}

// Obtener información del foro
$stmt = $pdo->prepare("
    SELECT f.*, u.nombre_completo as creador_nombre 
    FROM foros f 
    INNER JOIN usuarios u ON f.id_admin = u.id 
    WHERE f.id = :id AND f.activo = 1
");
$stmt->bindParam(':id', $foro_id);
$stmt->execute();
$foro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$foro) {
    header('Location: lista_foros.php');
    exit;
}

// Obtener temas del foro con información del autor y estadísticas
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        u.nombre_completo as autor_nombre,
        (SELECT COUNT(*) FROM respuestas_foro r WHERE r.id_tema = t.id) as total_respuestas,
        (SELECT MAX(r.fecha_creacion) FROM respuestas_foro r WHERE r.id_tema = t.id) as ultima_respuesta
    FROM temas_foro t 
    INNER JOIN usuarios u ON t.id_usuario = u.id 
    WHERE t.id_foro = :id_foro
    ORDER BY t.fijado DESC, t.fecha_creacion DESC
");
$stmt->bindParam(':id_foro', $foro_id);
$stmt->execute();
$temas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($foro['titulo']) ?> - Foro</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="styles_foro.css">
</head>
<body>
    <?php include '../panel_sidebar.php'; ?>

    <div class="forum-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="lista_foros.php"><i class="fas fa-home"></i> Foros</a>
                </li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($foro['titulo']) ?></li>
            </ol>
        </nav>

        <!-- Header del Foro -->
        <div class="forum-header">
            <h1 class="forum-title">
                <i class="fas fa-comments mr-3"></i>
                <?= htmlspecialchars($foro['titulo']) ?>
            </h1>
            <p class="forum-description"><?= nl2br(htmlspecialchars($foro['descripcion'])) ?></p>
            <div class="forum-meta">
                <i class="fas fa-user mr-2"></i>Creado por <?= htmlspecialchars($foro['creador_nombre']) ?>
                <span class="mx-2">•</span>
                <i class="fas fa-calendar mr-2"></i><?= date('d M Y', strtotime($foro['fecha_creacion'])) ?>
                <span class="mx-2">•</span>
                <i class="fas fa-list mr-2"></i><?= count($temas) ?> temas
            </div>
        </div>

        <!-- Botón para crear nuevo tema -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Temas de Discusión</h3>
            <button class="btn btn-new-topic" data-toggle="modal" data-target="#modalNuevoTema">
                <i class="fas fa-plus mr-2"></i>Nuevo Tema
            </button>
        </div>

        <!-- Lista de Temas -->
        <?php if (empty($temas)): ?>
            <div class="empty-forum">
                <i class="fas fa-comments"></i>
                <h4>No hay temas aún</h4>
                <p>¡Sé el primero en iniciar una conversación!</p>
                <button class="btn btn-new-topic mt-3" data-toggle="modal" data-target="#modalNuevoTema">
                    <i class="fas fa-plus mr-2"></i>Crear Primer Tema
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($temas as $tema): ?>
                <div class="card topic-card <?= $tema['fijado'] ? 'topic-pinned' : '' ?> <?= $tema['cerrado'] ? 'topic-closed' : '' ?>">
                    <div class="topic-header">
                        <h5 class="topic-title">
                            <a href="ver_tema.php?id=<?= $tema['id'] ?>">
                                <?php if ($tema['fijado']): ?>
                                    <span class="pinned-badge mr-2">
                                        <i class="fas fa-thumbtack"></i> Fijado
                                    </span>
                                <?php endif; ?>
                                <?php if ($tema['cerrado']): ?>
                                    <span class="closed-badge mr-2">
                                        <i class="fas fa-lock"></i> Cerrado
                                    </span>
                                <?php endif; ?>
                                <?= htmlspecialchars($tema['titulo']) ?>
                            </a>
                        </h5>
                        <div class="topic-meta">
                            <i class="fas fa-user mr-1"></i>
                            Por <?= htmlspecialchars($tema['autor_nombre']) ?>
                            <span class="mx-2">•</span>
                            <i class="fas fa-clock mr-1"></i>
                            <?= date('d M Y H:i', strtotime($tema['fecha_creacion'])) ?>
                        </div>
                    </div>
                    <div class="topic-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?= $tema['total_respuestas'] ?></span>
                            Respuestas
                        </div>
                        <div class="stat-item">
                            <?php if ($tema['ultima_respuesta']): ?>
                                <span class="stat-number"><?= date('d M', strtotime($tema['ultima_respuesta'])) ?></span>
                                Última respuesta
                            <?php else: ?>
                                <span class="stat-number">-</span>
                                Sin respuestas
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal Nuevo Tema -->
    <div class="modal fade" id="modalNuevoTema" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle mr-2"></i>Crear Nuevo Tema
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formNuevoTema">
                    <div class="modal-body">
                        <input type="hidden" name="id_foro" value="<?= $foro_id ?>">
                        <div class="form-group">
                            <label for="titulo_tema">Título del Tema</label>
                            <input type="text" class="form-control" id="titulo_tema" name="titulo" required
                                placeholder="¿Sobre qué quieres hablar?">
                        </div>
                        <div class="form-group">
                            <label for="contenido_tema">Contenido</label>
                            <textarea class="form-control" id="contenido_tema" name="contenido" rows="8" required
                                placeholder="Escribe tu mensaje aquí..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white;">
                            <i class="fas fa-paper-plane mr-2"></i>Crear Tema
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        // Crear nuevo tema
        $('#formNuevoTema').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('crear_tema.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $('#modalNuevoTema').modal('hide');
                        Swal.fire({
                            title: '¡Tema Creado!',
                            text: 'Tu tema ha sido publicado exitosamente.',
                            icon: 'success',
                            timer: 2000
                        }).then(() => {
                            window.location.href = 'ver_tema.php?id=' + data.tema_id;
                        });
                    } else {
                        Swal.fire('Error', data.error || 'Error al crear el tema', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Error de conexión', 'error');
                });
        });
    </script>
</body>

</html>