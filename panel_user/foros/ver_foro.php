<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
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

    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            flex: 1;
            padding: 20px;
        }

        .forum-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .forum-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .forum-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .forum-description {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .forum-meta {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .topic-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .topic-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        .topic-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .topic-title {
            color: #495057;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        .topic-title a {
            color: inherit;
            text-decoration: none;
        }

        .topic-title a:hover {
            color: #667eea;
            text-decoration: none;
        }

        .topic-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .topic-stats {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 12px 12px;
        }

        .stat-item {
            text-align: center;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .stat-number {
            font-weight: bold;
            color: #495057;
            display: block;
            font-size: 1.1rem;
        }

        .topic-pinned {
            border-left: 4px solid #ffc107;
        }

        .topic-closed {
            opacity: 0.7;
            background: #f8f9fa;
        }

        .btn-new-topic {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-new-topic:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .pinned-badge {
            background: #ffc107;
            color: #212529;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .closed-badge {
            background: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .empty-forum {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-forum i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        .btn-view-topic {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }

        .btn-view-topic:hover {
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            color: #6c757d;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <div class="col-md-2">
            <?php include '../user_sidebar.php'; ?>
        </div>
        
        <!-- Contenido principal -->
        <div class="content-area">
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
                        <div class="stat-item">
                            <a href="ver_tema.php?id=<?= $tema['id'] ?>" class="btn-view-topic">
                                <i class="fas fa-eye mr-1"></i>Ver Tema
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
            </div>
        </div>
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