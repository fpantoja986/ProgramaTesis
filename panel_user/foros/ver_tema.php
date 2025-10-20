<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$tema_id = (int)($_GET['id'] ?? 0);

if (!$tema_id) {
    header('Location: lista_foros.php');
    exit;
}

// Obtener información del tema
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        f.titulo as foro_titulo,
        f.id as foro_id,
        u.nombre_completo as autor_nombre,
        u.foto_perfil as autor_foto
    FROM temas_foro t 
    INNER JOIN foros f ON t.id_foro = f.id 
    INNER JOIN usuarios u ON t.id_usuario = u.id 
    WHERE t.id = :id
");
$stmt->bindParam(':id', $tema_id);
$stmt->execute();
$tema = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tema) {
    header('Location: lista_foros.php');
    exit;
}

// Obtener respuestas del tema con información del autor
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        u.nombre_completo as autor_nombre,
        u.foto_perfil as autor_foto
    FROM respuestas_foro r 
    INNER JOIN usuarios u ON r.id_usuario = u.id 
    WHERE r.id_tema = :id_tema
    ORDER BY r.fecha_creacion ASC
");
$stmt->bindParam(':id_tema', $tema_id);
$stmt->execute();
$respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar respuestas
$total_respuestas = count($respuestas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tema['titulo']) ?> - Tema</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .topic-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .topic-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .topic-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .topic-meta {
            font-size: 1rem;
            opacity: 0.9;
        }

        .topic-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .author-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }

        .author-details h6 {
            margin-bottom: 5px;
            color: #495057;
        }

        .author-details small {
            color: #6c757d;
        }

        .topic-text {
            line-height: 1.6;
            color: #495057;
            font-size: 1.1rem;
        }

        .responses-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .response-item {
            border-bottom: 1px solid #f8f9fa;
            padding: 25px 0;
        }

        .response-item:last-child {
            border-bottom: none;
        }

        .response-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .response-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
        }

        .response-meta {
            flex: 1;
        }

        .response-author {
            font-weight: 600;
            color: #495057;
            margin-bottom: 3px;
        }

        .response-date {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .response-content {
            color: #495057;
            line-height: 1.6;
            margin-left: 57px;
        }

        .new-response-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .btn-reply {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            color: white;
            padding: 12px 25px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-reply:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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

        .status-badges {
            margin-bottom: 15px;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 10px;
        }

        .badge-pinned {
            background: #ffc107;
            color: #212529;
        }

        .badge-closed {
            background: #6c757d;
            color: white;
        }

        .empty-responses {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-responses i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <div class="topic-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="lista_foros.php"><i class="fas fa-home"></i> Foros</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="ver_foro.php?id=<?= $tema['foro_id'] ?>"><?= htmlspecialchars($tema['foro_titulo']) ?></a>
                </li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($tema['titulo']) ?></li>
            </ol>
        </nav>

        <!-- Header del Tema -->
        <div class="topic-header">
            <div class="status-badges">
                <?php if ($tema['fijado']): ?>
                    <span class="badge-status badge-pinned">
                        <i class="fas fa-thumbtack"></i> Fijado
                    </span>
                <?php endif; ?>
                <?php if ($tema['cerrado']): ?>
                    <span class="badge-status badge-closed">
                        <i class="fas fa-lock"></i> Cerrado
                    </span>
                <?php endif; ?>
            </div>
            <h1 class="topic-title"><?= htmlspecialchars($tema['titulo']) ?></h1>
            <div class="topic-meta">
                <i class="fas fa-user mr-2"></i>Por <?= htmlspecialchars($tema['autor_nombre']) ?>
                <span class="mx-2">•</span>
                <i class="fas fa-calendar mr-2"></i><?= date('d M Y H:i', strtotime($tema['fecha_creacion'])) ?>
                <span class="mx-2">•</span>
                <i class="fas fa-comments mr-2"></i><?= $total_respuestas ?> respuesta<?= $total_respuestas !== 1 ? 's' : '' ?>
            </div>
        </div>

        <!-- Contenido del Tema -->
        <div class="topic-content">
            <div class="author-info">
                <img src="<?= !empty($tema['autor_foto']) ? 'data:image/jpeg;base64,' . $tema['autor_foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($tema['autor_nombre']) ?>" 
                     class="author-avatar" alt="Avatar">
                <div class="author-details">
                    <h6><?= htmlspecialchars($tema['autor_nombre']) ?></h6>
                    <small>
                        <i class="fas fa-calendar mr-1"></i>
                        <?= date('d M Y H:i', strtotime($tema['fecha_creacion'])) ?>
                    </small>
                </div>
            </div>
            <div class="topic-text">
                <?= nl2br(htmlspecialchars($tema['contenido'])) ?>
            </div>
        </div>

        <!-- Respuestas -->
        <div class="responses-section">
            <h3 class="mb-4">
                <i class="fas fa-reply mr-2"></i>
                Respuestas (<?= $total_respuestas ?>)
            </h3>

            <?php if (empty($respuestas)): ?>
                <div class="empty-responses">
                    <i class="fas fa-comments"></i>
                    <h4>No hay respuestas aún</h4>
                    <p>¡Sé el primero en responder a este tema!</p>
                </div>
            <?php else: ?>
                <?php foreach ($respuestas as $respuesta): ?>
                    <div class="response-item">
                        <div class="response-header">
                            <img src="<?= !empty($respuesta['autor_foto']) ? 'data:image/jpeg;base64,' . $respuesta['autor_foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($respuesta['autor_nombre']) ?>" 
                                 class="response-avatar" alt="Avatar">
                            <div class="response-meta">
                                <div class="response-author"><?= htmlspecialchars($respuesta['autor_nombre']) ?></div>
                                <div class="response-date">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= date('d M Y H:i', strtotime($respuesta['fecha_creacion'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="response-content">
                            <?= nl2br(htmlspecialchars($respuesta['contenido'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Nueva Respuesta -->
        <?php if (!$tema['cerrado']): ?>
        <div class="new-response-section">
            <h4 class="mb-4">
                <i class="fas fa-plus-circle mr-2"></i>
                Responder al Tema
            </h4>
            <form id="formNuevaRespuesta">
                <input type="hidden" name="id_tema" value="<?= $tema_id ?>">
                <div class="form-group">
                    <label for="contenido_respuesta">Tu Respuesta</label>
                    <textarea class="form-control" id="contenido_respuesta" name="contenido" rows="6" required 
                              placeholder="Escribe tu respuesta aquí..."></textarea>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn btn-reply">
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Respuesta
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="new-response-section text-center">
            <div class="alert alert-warning">
                <i class="fas fa-lock mr-2"></i>
                Este tema está cerrado. No se pueden agregar nuevas respuestas.
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        // Crear nueva respuesta
        $('#formNuevaRespuesta').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('crear_respuesta.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Respuesta Enviada!',
                        text: 'Tu respuesta ha sido publicada exitosamente.',
                        icon: 'success',
                        timer: 2000
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error || 'Error al enviar la respuesta', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        });
    </script>
</body>
</html>
