<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
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

// Función para obtener respuestas anidadas
function obtenerRespuestasAnidadas($pdo, $tema_id, $respuesta_padre_id = null) {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.nombre_completo as autor_nombre,
            u.foto_perfil as autor_foto
        FROM respuestas_foro r 
        INNER JOIN usuarios u ON r.id_usuario = u.id 
        WHERE r.id_tema = :id_tema AND r.id_respuesta_padre " . ($respuesta_padre_id ? "= :respuesta_padre" : "IS NULL") . "
        ORDER BY r.fecha_creacion ASC
    ");
    $stmt->bindParam(':id_tema', $tema_id);
    if ($respuesta_padre_id) {
        $stmt->bindParam(':respuesta_padre', $respuesta_padre_id);
    }
    $stmt->execute();
    $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada respuesta, obtener sus respuestas anidadas
    foreach ($respuestas as &$respuesta) {
        $respuesta['respuestas_hijas'] = obtenerRespuestasAnidadas($pdo, $tema_id, $respuesta['id']);
    }
    
    return $respuestas;
}

// Obtener respuestas principales (sin padre)
$respuestas = obtenerRespuestasAnidadas($pdo, $tema_id);

// Contar todas las respuestas (incluyendo anidadas)
function contarRespuestasRecursivo($respuestas) {
    $total = count($respuestas);
    foreach ($respuestas as $respuesta) {
        $total += contarRespuestasRecursivo($respuesta['respuestas_hijas']);
    }
    return $total;
}

$total_respuestas = contarRespuestasRecursivo($respuestas);

// Función para mostrar respuestas anidadas
function mostrarRespuestasAnidadas($respuestas, $nivel = 0) {
    foreach ($respuestas as $respuesta) {
        $margen = $nivel * 30; // Margen izquierdo para anidación
        ?>
        <div class="response-item" style="margin-left: <?= $margen ?>px; <?= $nivel > 0 ? 'border-left: 3px solid #667eea; padding-left: 15px;' : '' ?>">
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
                <div class="response-actions">
                    <button class="btn-reply-to-response" data-respuesta-id="<?= $respuesta['id'] ?>" data-autor="<?= htmlspecialchars($respuesta['autor_nombre']) ?>">
                        <i class="fas fa-reply mr-1"></i>Responder
                    </button>
                    <?php if ($respuesta['id_usuario'] == $_SESSION['user_id']): ?>
                        <!-- Botones para respuesta propia -->
                        <button class="btn-edit-response" data-respuesta-id="<?= $respuesta['id'] ?>" data-contenido="<?= htmlspecialchars($respuesta['contenido']) ?>">
                            <i class="fas fa-edit mr-1"></i>Editar
                        </button>
                        <button class="btn-delete-response" data-respuesta-id="<?= $respuesta['id'] ?>" data-autor="<?= htmlspecialchars($respuesta['autor_nombre']) ?>">
                            <i class="fas fa-trash mr-1"></i>Eliminar
                        </button>
                    <?php else: ?>
                        <!-- Botón de reportar para respuestas de otros -->
                        <button class="btn-report-response" data-respuesta-id="<?= $respuesta['id'] ?>" data-autor="<?= htmlspecialchars($respuesta['autor_nombre']) ?>" data-contenido="<?= htmlspecialchars(substr($respuesta['contenido'], 0, 100)) ?>">
                            <i class="fas fa-flag mr-1"></i>Reportar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="response-content">
                <?= nl2br(htmlspecialchars($respuesta['contenido'])) ?>
            </div>
        </div>
        <?php
        // Mostrar respuestas hijas recursivamente
        if (!empty($respuesta['respuestas_hijas'])) {
            mostrarRespuestasAnidadas($respuesta['respuestas_hijas'], $nivel + 1);
        }
    }
}
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

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            flex: 1;
            padding: 20px;
        }

        .topic-container {
            max-width: 1200px;
            margin: 0 auto;
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

        .response-actions {
            margin-left: auto;
        }

        .btn-reply-to-response {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-reply-to-response:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-report-response {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-left: 8px;
        }

        .btn-report-response:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        .btn-edit-response {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-left: 8px;
        }

        .btn-edit-response:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }

        .btn-delete-response {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-left: 8px;
        }

        .btn-delete-response:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
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
    <div class="main-container">
        <!-- Sidebar -->
        <div class="col-md-2">
            <?php include '../user_sidebar.php'; ?>
        </div>
        
        <!-- Contenido principal -->
        <div class="content-area">
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
                <?php mostrarRespuestasAnidadas($respuestas); ?>
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
        </div>
    </div>

    <!-- Modal Responder a Respuesta -->
    <div class="modal fade" id="modalRespuestaRespuesta" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-reply mr-2"></i>Responder a <span id="autorRespuesta"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formRespuestaRespuesta">
                    <div class="modal-body">
                        <input type="hidden" name="id_tema" value="<?= $tema_id ?>">
                        <input type="hidden" name="id_respuesta_padre" id="idRespuestaPadre">
                        <div class="form-group">
                            <label for="contenido_respuesta_respuesta">Tu respuesta</label>
                            <textarea class="form-control" id="contenido_respuesta_respuesta" name="contenido" rows="6" required
                                placeholder="Escribe tu respuesta aquí..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white;">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Respuesta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Reportar Respuesta -->
    <div class="modal fade" id="modalReportarRespuesta" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-flag mr-2"></i>Reportar Respuesta
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formReportarRespuesta">
                    <div class="modal-body">
                        <input type="hidden" name="id_respuesta" id="idRespuestaReportar">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Respuesta a reportar:</strong> <span id="contenidoReportar"></span>
                        </div>
                        <div class="form-group">
                            <label for="motivo_reporte">Motivo del reporte</label>
                            <select class="form-control" id="motivo_reporte" name="motivo" required>
                                <option value="">Selecciona un motivo</option>
                                <option value="spam">Spam o contenido promocional</option>
                                <option value="inapropiado">Contenido inapropiado u ofensivo</option>
                                <option value="acoso">Acoso o intimidación</option>
                                <option value="desinformacion">Desinformación o contenido falso</option>
                                <option value="violencia">Contenido violento o amenazante</option>
                                <option value="otro">Otro motivo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="descripcion_reporte">Descripción adicional (opcional)</label>
                            <textarea class="form-control" id="descripcion_reporte" name="descripcion" rows="4" 
                                placeholder="Proporciona más detalles sobre el problema..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white;">
                            <i class="fas fa-flag mr-2"></i>Enviar Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Respuesta -->
    <div class="modal fade" id="modalEditarRespuesta" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(45deg, #28a745, #20c997); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit mr-2"></i>Editar Respuesta
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formEditarRespuesta">
                    <div class="modal-body">
                        <input type="hidden" name="id_respuesta" id="idRespuestaEditar">
                        <div class="form-group">
                            <label for="contenido_editar">Contenido de la respuesta</label>
                            <textarea class="form-control" id="contenido_editar" name="contenido" rows="6" required
                                placeholder="Escribe tu respuesta aquí..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn" style="background: linear-gradient(45deg, #28a745, #20c997); color: white;">
                            <i class="fas fa-save mr-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
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

        // Manejar botones de responder a respuesta
        $(document).on('click', '.btn-reply-to-response', function() {
            const respuestaId = $(this).data('respuesta-id');
            const autor = $(this).data('autor');
            
            $('#idRespuestaPadre').val(respuestaId);
            $('#autorRespuesta').text(autor);
            $('#modalRespuestaRespuesta').modal('show');
        });

        // Crear respuesta a respuesta
        $('#formRespuestaRespuesta').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('crear_respuesta.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#modalRespuestaRespuesta').modal('hide');
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

        // Manejar botones de reportar respuesta
        $(document).on('click', '.btn-report-response', function() {
            const respuestaId = $(this).data('respuesta-id');
            const contenido = $(this).data('contenido');
            
            $('#idRespuestaReportar').val(respuestaId);
            $('#contenidoReportar').text(contenido + '...');
            $('#modalReportarRespuesta').modal('show');
        });

        // Enviar reporte de respuesta
        $('#formReportarRespuesta').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('reportar_respuesta.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#modalReportarRespuesta').modal('hide');
                    Swal.fire({
                        title: '¡Reporte Enviado!',
                        text: 'Tu reporte ha sido enviado a los administradores. Gracias por mantener la comunidad segura.',
                        icon: 'success',
                        timer: 3000
                    });
                } else {
                    Swal.fire('Error', data.error || 'Error al enviar el reporte', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        });

        // Manejar botones de editar respuesta
        $(document).on('click', '.btn-edit-response', function() {
            const respuestaId = $(this).data('respuesta-id');
            const contenido = $(this).data('contenido');
            
            $('#idRespuestaEditar').val(respuestaId);
            $('#contenido_editar').val(contenido);
            $('#modalEditarRespuesta').modal('show');
        });

        // Editar respuesta
        $('#formEditarRespuesta').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('editar_respuesta.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#modalEditarRespuesta').modal('hide');
                    Swal.fire({
                        title: '¡Respuesta Actualizada!',
                        text: 'Tu respuesta ha sido actualizada exitosamente.',
                        icon: 'success',
                        timer: 2000
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error || 'Error al actualizar la respuesta', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        });

        // Manejar botones de eliminar respuesta
        $(document).on('click', '.btn-delete-response', function() {
            const respuestaId = $(this).data('respuesta-id');
            const autor = $(this).data('autor');
            
            Swal.fire({
                title: '¿Eliminar respuesta?',
                text: `¿Estás seguro de que quieres eliminar tu respuesta? Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('eliminar_respuesta.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_respuesta=${respuestaId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Respuesta Eliminada!',
                                text: 'Tu respuesta ha sido eliminada exitosamente.',
                                icon: 'success',
                                timer: 2000
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.error || 'Error al eliminar la respuesta', 'error');
                        }
                    })
                    .catch(() => {
                        Swal.fire('Error', 'Error de conexión', 'error');
                    });
                }
            });
        });
    </script>
</body>
</html>
