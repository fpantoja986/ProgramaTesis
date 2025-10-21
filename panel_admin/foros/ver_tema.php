<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../../login.php');
    exit;
}

$tema_id = (int)($_GET['id'] ?? 0);

if (!$tema_id) {
    header('Location: gestionar_foros.php');
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
    header('Location: gestionar_foros.php');
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
        <div class="reply-card" style="margin-left: <?= $margen ?>px; <?= $nivel > 0 ? 'border-left: 3px solid #667eea; padding-left: 15px;' : '' ?>">
            <div class="reply-author">
                <img src="<?= !empty($respuesta['autor_foto']) ? 'data:image/jpeg;base64,' . $respuesta['autor_foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($respuesta['autor_nombre']) ?>" 
                     class="reply-avatar" alt="Autor">
                <div class="reply-author-info">
                    <h6><?= htmlspecialchars($respuesta['autor_nombre']) ?></h6>
                    <small><?= date('d M Y H:i', strtotime($respuesta['fecha_creacion'])) ?></small>
                </div>
                <div class="reply-actions">
                    <button class="btn-reply-to-response" data-respuesta-id="<?= $respuesta['id'] ?>" data-autor="<?= htmlspecialchars($respuesta['autor_nombre']) ?>">
                        <i class="fas fa-reply mr-1"></i>Responder
                    </button>
                    <?php if ($respuesta['id_usuario'] == $_SESSION['user_id']): ?>
                        <!-- Botones para respuesta propia del admin -->
                        <button class="btn-edit-response" data-respuesta-id="<?= $respuesta['id'] ?>" data-contenido="<?= htmlspecialchars($respuesta['contenido']) ?>">
                            <i class="fas fa-edit mr-1"></i>Editar
                        </button>
                        <button class="btn-delete-response" data-respuesta-id="<?= $respuesta['id'] ?>" data-autor="<?= htmlspecialchars($respuesta['autor_nombre']) ?>">
                            <i class="fas fa-trash mr-1"></i>Eliminar
                        </button>
                    <?php else: ?>
                        <!-- Botón de reportar para respuestas de otros -->
                        <button class="btn-report-response-admin" data-respuesta-id="<?= $respuesta['id'] ?>" data-autor="<?= htmlspecialchars($respuesta['autor_nombre']) ?>" data-contenido="<?= htmlspecialchars(substr($respuesta['contenido'], 0, 100)) ?>">
                            <i class="fas fa-flag mr-1"></i>Reportar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="reply-content">
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
    <link rel="stylesheet" href="../stylesadmin.css">
    
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-container {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        .topic-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .breadcrumb {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            color: #764ba2;
        }
        .topic-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .topic-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .topic-meta {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            margin-top: 20px;
        }
        .topic-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .author-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }
        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #667eea;
        }
        .author-details h5 {
            margin: 0;
            color: #495057;
            font-weight: 600;
        }
        .author-details small {
            color: #6c757d;
        }
        .content-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #495057;
            margin-bottom: 20px;
        }
        .topic-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        .btn-reply {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reply:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            color: white;
            text-decoration: none;
            background: #5a6268;
        }
        .replies-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .replies-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        .replies-count {
            font-size: 1.3rem;
            font-weight: 600;
            color: #495057;
        }
        .reply-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .reply-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .reply-author {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .reply-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #667eea;
        }
        .reply-author-info h6 {
            margin: 0;
            color: #495057;
            font-weight: 600;
        }
        .reply-author-info small {
            color: #6c757d;
        }
        .reply-content {
            font-size: 1rem;
            line-height: 1.6;
            color: #495057;
            margin-bottom: 15px;
        }
        .reply-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .empty-replies {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .empty-replies i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        .modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }
        .modal-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
        }
        .modal-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .modal-footer {
            border: none;
            padding: 20px 30px;
            background: #f8f9fa;
        }
        .status-badges {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pinned {
            background: #28a745;
            color: white;
        }
        .status-closed {
            background: #dc3545;
            color: white;
        }
        .reply-actions {
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
        .btn-report-response-admin {
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
        .btn-report-response-admin:hover {
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
    </style>
</head>
<body>
    <?php include '../panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="topic-container">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="gestionar_foros.php"><i class="fas fa-home"></i> Foros</a>
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
                        <span class="status-badge status-pinned">
                            <i class="fas fa-thumbtack mr-1"></i>Fijado
                        </span>
                    <?php endif; ?>
                    <?php if ($tema['cerrado']): ?>
                        <span class="status-badge status-closed">
                            <i class="fas fa-lock mr-1"></i>Cerrado
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="topic-title"><?= htmlspecialchars($tema['titulo']) ?></h1>
                <div class="topic-meta">
                    <i class="fas fa-user mr-2"></i>Por <?= htmlspecialchars($tema['autor_nombre']) ?>
                    <span class="mx-2">•</span>
                    <i class="fas fa-calendar mr-2"></i><?= date('d M Y H:i', strtotime($tema['fecha_creacion'])) ?>
                    <span class="mx-2">•</span>
                    <i class="fas fa-comments mr-2"></i><?= $total_respuestas ?> respuestas
                </div>
            </div>

            <!-- Contenido del Tema -->
            <div class="topic-content">
                <div class="author-info">
                    <img src="<?= !empty($tema['autor_foto']) ? 'data:image/jpeg;base64,' . $tema['autor_foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($tema['autor_nombre']) ?>" 
                         class="author-avatar" alt="Autor">
                    <div class="author-details">
                        <h5><?= htmlspecialchars($tema['autor_nombre']) ?></h5>
                        <small>Autor del tema</small>
                    </div>
                </div>
                <div class="content-text">
                    <?= nl2br(htmlspecialchars($tema['contenido'])) ?>
                </div>
                <div class="topic-actions">
                    <a href="ver_foro.php?id=<?= $tema['foro_id'] ?>" class="btn-back">
                        <i class="fas fa-arrow-left mr-2"></i>Volver al Foro
                    </a>
                    <?php if (!$tema['cerrado']): ?>
                        <button class="btn-reply" data-toggle="modal" data-target="#modalRespuesta">
                            <i class="fas fa-reply mr-2"></i>Responder
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Respuestas -->
            <div class="replies-section">
                <div class="replies-header">
                    <h3 class="replies-count">
                        <i class="fas fa-comments mr-2"></i>Respuestas (<?= $total_respuestas ?>)
                    </h3>
                </div>

                <?php if (empty($respuestas)): ?>
                    <div class="empty-replies">
                        <i class="fas fa-comment-slash"></i>
                        <h5>No hay respuestas aún</h5>
                        <p>¡Sé el primero en responder a este tema!</p>
                    </div>
                <?php else: ?>
                    <?php mostrarRespuestasAnidadas($respuestas); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Respuesta -->
    <div class="modal fade" id="modalRespuesta" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-reply mr-2"></i>Responder al Tema
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formRespuesta">
                    <div class="modal-body">
                        <input type="hidden" name="id_tema" value="<?= $tema_id ?>">
                        <div class="form-group">
                            <label for="contenido_respuesta">Tu respuesta</label>
                            <textarea class="form-control" id="contenido_respuesta" name="contenido" rows="6" required
                                placeholder="Escribe tu respuesta aquí..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white;">
                            <i class="fas fa-paper-plane mr-2"></i>Publicar Respuesta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Responder a Respuesta -->
    <div class="modal fade" id="modalRespuestaRespuesta" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
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

    <!-- Modal Reportar Respuesta (Admin) -->
    <div class="modal fade" id="modalReportarRespuestaAdmin" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-flag mr-2"></i>Reportar Respuesta (Administrador)
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formReportarRespuestaAdmin">
                    <div class="modal-body">
                        <input type="hidden" name="id_respuesta" id="idRespuestaReportarAdmin">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Como administrador, este reporte se enviará directamente al usuario con tu mensaje personalizado.</strong>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Respuesta a reportar:</strong> <span id="contenidoReportarAdmin"></span>
                        </div>
                        <div class="form-group">
                            <label for="motivo_reporte_admin">Motivo del reporte</label>
                            <select class="form-control" id="motivo_reporte_admin" name="motivo" required>
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
                            <label for="mensaje_admin">Mensaje del administrador <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="mensaje_admin" name="mensaje_admin" rows="4" required
                                placeholder="Escribe el mensaje que recibirá el usuario..."></textarea>
                            <small class="form-text text-muted">Este mensaje se enviará directamente al usuario reportado.</small>
                        </div>
                        <div class="form-group">
                            <label for="descripcion_reporte_admin">Descripción adicional (opcional)</label>
                            <textarea class="form-control" id="descripcion_reporte_admin" name="descripcion" rows="3" 
                                placeholder="Información adicional para el registro interno..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white;">
                            <i class="fas fa-flag mr-2"></i>Enviar Reporte al Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Respuesta (Admin) -->
    <div class="modal fade" id="modalEditarRespuestaAdmin" tabindex="-1" role="dialog">
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
                <form id="formEditarRespuestaAdmin">
                    <div class="modal-body">
                        <input type="hidden" name="id_respuesta" id="idRespuestaEditarAdmin">
                        <div class="form-group">
                            <label for="contenido_editar_admin">Contenido de la respuesta</label>
                            <textarea class="form-control" id="contenido_editar_admin" name="contenido" rows="6" required
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
        $('#formRespuesta').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('crear_respuesta.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $('#modalRespuesta').modal('hide');
                        Swal.fire({
                            title: '¡Respuesta Publicada!',
                            text: 'Tu respuesta ha sido publicada exitosamente.',
                            icon: 'success',
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.error || 'Error al publicar la respuesta', 'error');
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

        // Manejar botones de reportar respuesta (Admin)
        $(document).on('click', '.btn-report-response-admin', function() {
            const respuestaId = $(this).data('respuesta-id');
            const contenido = $(this).data('contenido');
            
            $('#idRespuestaReportarAdmin').val(respuestaId);
            $('#contenidoReportarAdmin').text(contenido + '...');
            $('#modalReportarRespuestaAdmin').modal('show');
        });

        // Enviar reporte de respuesta (Admin)
        $('#formReportarRespuestaAdmin').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../reportar_respuesta.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#modalReportarRespuestaAdmin').modal('hide');
                    Swal.fire({
                        title: '¡Reporte Enviado!',
                        text: 'El reporte ha sido enviado directamente al usuario con tu mensaje personalizado.',
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

        // Manejar botones de editar respuesta (Admin)
        $(document).on('click', '.btn-edit-response', function() {
            const respuestaId = $(this).data('respuesta-id');
            const contenido = $(this).data('contenido');
            
            $('#idRespuestaEditarAdmin').val(respuestaId);
            $('#contenido_editar_admin').val(contenido);
            $('#modalEditarRespuestaAdmin').modal('show');
        });

        // Editar respuesta (Admin)
        $('#formEditarRespuestaAdmin').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../editar_respuesta.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#modalEditarRespuestaAdmin').modal('hide');
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

        // Manejar botones de eliminar respuesta (Admin)
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
                    fetch('../eliminar_respuesta.php', {
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
