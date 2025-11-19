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
        
        /* Estilos para comentarios estilo Instagram */
        .comentarios-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .comentario-form {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }
        
        .comentario-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #667eea;
        }
        
        .comentario-input-container {
            flex: 1;
        }
        
        .comentario-textarea {
            border: 1px solid #e9ecef;
            border-radius: 20px;
            padding: 12px 20px;
            resize: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .comentario-textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .comentario-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        
        .caracteres-restantes {
            font-size: 12px;
        }
        
        .comentarios-lista {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .comentario-item {
            display: flex;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .comentario-item:hover {
            background: #e9ecef;
        }
        
        .comentario-content {
            flex: 1;
        }
        
        .comentario-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .comentario-nombre {
            font-weight: 600;
            color: #495057;
            margin-right: 10px;
        }
        
        .comentario-fecha {
            font-size: 12px;
            color: #6c757d;
        }
        
        .comentario-texto {
            color: #495057;
            line-height: 1.4;
            margin-bottom: 10px;
        }
        
        .comentario-actions-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn-like {
            background: none;
            border: none;
            color: #6c757d;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-like:hover {
            color: #dc3545;
        }
        
        .btn-like.liked {
            color: #dc3545;
        }
        
        .btn-responder {
            background: none;
            border: none;
            color: #6c757d;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-responder:hover {
            color: #667eea;
        }
        
        .btn-eliminar {
            background: none;
            border: none;
            color: #dc3545;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-eliminar:hover {
            color: #c82333;
        }
        
        .btn-reportar {
            background: none;
            border: none;
            color: #6c757d;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-reportar:hover {
            color: #ffc107;
        }
        
        .respuestas-container {
            margin-left: 55px;
            margin-top: 15px;
        }
        
        .respuesta-item {
            display: flex;
            margin-bottom: 15px;
            padding: 12px;
            background: white;
            border-radius: 12px;
            border-left: 3px solid #667eea;
        }
        
        .formulario-respuesta {
            margin-left: 55px;
            margin-top: 10px;
            display: none;
        }
        
        .formulario-respuesta.show {
            display: block;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        
        .empty-comments {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .empty-comments i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .pdf-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .pdf-actions {
            text-align: center;
        }
        
        .pdf-actions .btn {
            margin: 0 10px;
        }
        
        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            text-decoration: none;
            transform: translateX(-3px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="publicacion-card">
            <div class="publicacion-header">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <a href="publicaciones.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <span class="badge-tipo"><?= htmlspecialchars($publicacion['tipo'] ?? 'Publicación') ?></span>
                </div>
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
                        <?php elseif ($publicacion['tipo'] === 'articulo'): ?>
                            <div class="pdf-container">
                                <iframe src="../panel_admin/publicaciones/servir_archivo.php?id=<?= $publicacion['id'] ?>" 
                                        style="width:100%; height:600px; border:none;" 
                                        class="border rounded"
                                        type="application/pdf">
                                </iframe>
                                <div class="pdf-actions mt-3">
                                    <a href="../panel_admin/publicaciones/servir_archivo.php?id=<?= $publicacion['id'] ?>" 
                                       target="_blank" 
                                       class="btn btn-primary">
                                        <i class="fas fa-external-link-alt mr-2"></i>Abrir en nueva pestaña
                                    </a>
                                    <a href="../panel_admin/publicaciones/servir_archivo.php?id=<?= $publicacion['id'] ?>&download=1" 
                                       class="btn btn-success">
                                        <i class="fas fa-download mr-2"></i>Descargar PDF
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-file mr-2"></i>
                                <a href="../panel_admin/publicaciones/servir_archivo.php?id=<?= $publicacion['id'] ?>" 
                                   target="_blank" 
                                   class="text-decoration-none">
                                    Ver archivo adjunto
                                </a>
                            </div>
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

                <!-- Sección de Comentarios -->
                <div class="comentarios-section">
                    <h3 class="mb-4">
                        <i class="fas fa-comments mr-2"></i>
                        Comentarios
                    </h3>
                    
                    <!-- Formulario para nuevo comentario -->
                    <div class="comentario-form mb-4">
                        <div class="d-flex align-items-start">
                            <img src="<?= !empty($_SESSION['foto_perfil'] ?? '') ? 'data:image/jpeg;base64,' . $_SESSION['foto_perfil'] : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['nombre_completo'] ?? 'Usuario') ?>" 
                                 class="comentario-avatar" alt="Tu avatar">
                            <div class="comentario-input-container">
                                <textarea class="form-control comentario-textarea" 
                                          placeholder="Escribe un comentario..." 
                                          rows="2" 
                                          maxlength="1000"></textarea>
                                <div class="comentario-actions">
                                    <small class="text-muted caracteres-restantes">1000 caracteres restantes</small>
                                    <button class="btn btn-primary btn-sm" onclick="publicarComentario()">
                                        <i class="fas fa-paper-plane mr-1"></i> Publicar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de comentarios -->
                    <div id="comentarios-container" class="comentarios-lista">
                        <!-- Los comentarios se cargarán aquí via AJAX -->
                    </div>
                </div>
                
                <!-- Modal para reportar comentarios -->
                <div class="modal fade" id="modalReporte" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-flag mr-2"></i>Reportar Comentario
                                </h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="formReporte">
                                    <input type="hidden" id="comentario_id_reporte">
                                    <div class="form-group">
                                        <label for="motivo_reporte">Motivo del reporte:</label>
                                        <select class="form-control" id="motivo_reporte" required>
                                            <option value="">Selecciona un motivo</option>
                                            <option value="spam">Spam</option>
                                            <option value="inapropiado">Contenido inapropiado</option>
                                            <option value="ofensivo">Contenido ofensivo</option>
                                            <option value="irrelevante">Irrelevante</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="descripcion_reporte">Descripción (opcional):</label>
                                        <textarea class="form-control" id="descripcion_reporte" rows="3" 
                                                  placeholder="Proporciona más detalles sobre el problema..."></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-warning" onclick="enviarReporte()">
                                    <i class="fas fa-flag mr-1"></i>Reportar
                                </button>
                            </div>
                        </div>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script>
        const publicacionId = <?= $id ?>;
        let comentariosCargados = false;
        
        // Cargar comentarios al cargar la página
        $(document).ready(function() {
            cargarComentarios();
            
            // Contador de caracteres
            $('.comentario-textarea').on('input', function() {
                const maxLength = 1000;
                const currentLength = $(this).val().length;
                const remaining = maxLength - currentLength;
                $('.caracteres-restantes').text(remaining + ' caracteres restantes');
                
                if (remaining < 50) {
                    $('.caracteres-restantes').addClass('text-warning');
                } else {
                    $('.caracteres-restantes').removeClass('text-warning');
                }
            });
        });
        
        function cargarComentarios() {
            $('#comentarios-container').html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> Cargando comentarios...</div>');
            
            $.get('comentarios/obtener_comentarios.php', {id_publicacion: publicacionId})
                .done(function(response) {
                    // Parsear la respuesta JSON si viene como string
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('Error al parsear JSON:', e);
                            $('#comentarios-container').html(`
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Error al procesar respuesta del servidor
                                </div>
                            `);
                            return;
                        }
                    }
                    
                    if (response.success) {
                        mostrarComentarios(response.comentarios || []);
                    } else {
                        $('#comentarios-container').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Error al cargar comentarios: ${response.message || 'Error desconocido'}
                            </div>
                        `);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Error de conexión:', error);
                    console.error('Status:', status);
                    console.error('XHR:', xhr);
                    $('#comentarios-container').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Error de conexión: ${error}
                        </div>
                    `);
                });
        }
        
        function mostrarComentarios(comentarios) {
            if (comentarios.length === 0) {
                $('#comentarios-container').html(`
                    <div class="empty-comments">
                        <i class="fas fa-comment-slash"></i>
                        <h5>Sin comentarios</h5>
                        <p>¡Sé el primero en comentar!</p>
                    </div>
                `);
                return;
            }
            
            let html = '';
            comentarios.forEach(function(comentario) {
                html += crearComentarioHTML(comentario);
            });
            
            $('#comentarios-container').html(html);
        }
        
        function crearComentarioHTML(comentario) {
            const fecha = new Date(comentario.fecha_creacion);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const avatar = comentario.foto_perfil ? 
                `data:image/jpeg;base64,${comentario.foto_perfil}` : 
                `https://ui-avatars.com/api/?name=${encodeURIComponent(comentario.nombre_completo)}`;
            
            const esMio = comentario.id_usuario == <?= $_SESSION['user_id'] ?>;
            const likedClass = comentario.user_liked > 0 ? 'liked' : '';
            
            let respuestasHTML = '';
            if (comentario.respuestas && comentario.respuestas.length > 0) {
                respuestasHTML = '<div class="respuestas-container">';
                comentario.respuestas.forEach(function(respuesta) {
                    respuestasHTML += crearRespuestaHTML(respuesta);
                });
                respuestasHTML += '</div>';
            }
            
            return `
                <div class="comentario-item" data-comentario-id="${comentario.id}">
                    <img src="${avatar}" class="comentario-avatar" alt="${comentario.nombre_completo}">
                    <div class="comentario-content">
                        <div class="comentario-header">
                            <span class="comentario-nombre">${comentario.nombre_completo}</span>
                            <span class="comentario-fecha">${fechaFormateada}</span>
                        </div>
                        <div class="comentario-texto">${comentario.comentario}</div>
                        <div class="comentario-actions-buttons">
                            <button class="btn-like ${likedClass}" onclick="toggleLike(${comentario.id})">
                                <i class="fas fa-heart"></i>
                                <span class="likes-count">${comentario.likes_count}</span>
                            </button>
                            <button class="btn-responder" onclick="mostrarFormularioRespuesta(${comentario.id})">
                                <i class="fas fa-reply mr-1"></i>Responder
                            </button>
                            ${!esMio ? `<button class="btn-reportar" onclick="mostrarModalReporte(${comentario.id})">
                                <i class="fas fa-flag mr-1"></i>Reportar
                            </button>` : ''}
                            ${esMio ? `<button class="btn-eliminar" onclick="eliminarComentario(${comentario.id})">
                                <i class="fas fa-trash mr-1"></i>Eliminar
                            </button>` : ''}
                        </div>
                        ${respuestasHTML}
                        <div class="formulario-respuesta" id="form-respuesta-${comentario.id}">
                            <div class="d-flex align-items-start">
                                <img src="<?= !empty($_SESSION['foto_perfil'] ?? '') ? 'data:image/jpeg;base64,' . $_SESSION['foto_perfil'] : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['nombre_completo'] ?? 'Usuario') ?>" 
                                     class="comentario-avatar" alt="Tu avatar">
                                <div class="comentario-input-container">
                                    <textarea class="form-control comentario-textarea" 
                                              placeholder="Escribe una respuesta..." 
                                              rows="2" 
                                              maxlength="1000"
                                              data-comentario-padre="${comentario.id}"></textarea>
                                    <div class="comentario-actions">
                                        <small class="text-muted caracteres-restantes">1000 caracteres restantes</small>
                                        <button class="btn btn-primary btn-sm" onclick="publicarRespuesta(${comentario.id})">
                                            <i class="fas fa-paper-plane mr-1"></i> Responder
                                        </button>
                                        <button class="btn btn-secondary btn-sm" onclick="ocultarFormularioRespuesta(${comentario.id})">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function crearRespuestaHTML(respuesta) {
            const fecha = new Date(respuesta.fecha_creacion);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const avatar = respuesta.foto_perfil ? 
                `data:image/jpeg;base64,${respuesta.foto_perfil}` : 
                `https://ui-avatars.com/api/?name=${encodeURIComponent(respuesta.nombre_completo)}`;
            
            const esMio = respuesta.id_usuario == <?= $_SESSION['user_id'] ?>;
            const likedClass = respuesta.user_liked > 0 ? 'liked' : '';
            
            return `
                <div class="respuesta-item" data-comentario-id="${respuesta.id}">
                    <img src="${avatar}" class="comentario-avatar" alt="${respuesta.nombre_completo}">
                    <div class="comentario-content">
                        <div class="comentario-header">
                            <span class="comentario-nombre">${respuesta.nombre_completo}</span>
                            <span class="comentario-fecha">${fechaFormateada}</span>
                        </div>
                        <div class="comentario-texto">${respuesta.comentario}</div>
                        <div class="comentario-actions-buttons">
                            <button class="btn-like ${likedClass}" onclick="toggleLike(${respuesta.id})">
                                <i class="fas fa-heart"></i>
                                <span class="likes-count">${respuesta.likes_count}</span>
                            </button>
                            ${!esMio ? `<button class="btn-reportar" onclick="mostrarModalReporte(${respuesta.id})">
                                <i class="fas fa-flag mr-1"></i>Reportar
                            </button>` : ''}
                            ${esMio ? `<button class="btn-eliminar" onclick="eliminarComentario(${respuesta.id})">
                                <i class="fas fa-trash mr-1"></i>Eliminar
                            </button>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }
        
        function publicarComentario() {
            const comentario = $('.comentario-textarea').val().trim();
            
            if (!comentario) {
                mostrarMensaje('Por favor escribe un comentario', 'warning');
                return;
            }
            
            // Deshabilitar el botón para evitar doble envío
            const btnPublicar = $('.comentario-actions button');
            btnPublicar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Publicando...');
            
            $.post('comentarios/procesar_comentario.php', {
                action: 'crear_comentario',
                id_publicacion: publicacionId,
                comentario: comentario
            })
            .done(function(response) {
                // Parsear la respuesta JSON si viene como string
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error('Error al parsear JSON:', e);
                        mostrarMensaje('Error al procesar respuesta del servidor', 'danger');
                        return;
                    }
                }
                
                if (response.success) {
                    mostrarMensaje('¡Comentario agregado con éxito!', 'success');
                    $('.comentario-textarea').val('');
                    $('.caracteres-restantes').text('1000 caracteres restantes');
                    cargarComentarios();
                } else {
                    mostrarMensaje('Error: ' + (response.message || 'Error desconocido'), 'danger');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Error al publicar comentario:', error);
                mostrarMensaje('Error de conexión: ' + error, 'danger');
            })
            .always(function() {
                // Rehabilitar el botón
                btnPublicar.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Publicar');
            });
        }
        
        function publicarRespuesta(comentarioPadreId) {
            const textarea = $(`#form-respuesta-${comentarioPadreId} .comentario-textarea`);
            const comentario = textarea.val().trim();
            
            if (!comentario) {
                mostrarMensaje('Por favor escribe una respuesta', 'warning');
                return;
            }
            
            // Deshabilitar el botón
            const btnResponder = $(`#form-respuesta-${comentarioPadreId} .btn-primary`);
            btnResponder.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Respondiendo...');
            
            $.post('comentarios/procesar_comentario.php', {
                action: 'crear_comentario',
                id_publicacion: publicacionId,
                comentario: comentario,
                id_comentario_padre: comentarioPadreId
            })
            .done(function(response) {
                // Parsear la respuesta JSON si viene como string
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error('Error al parsear JSON:', e);
                        mostrarMensaje('Error al procesar respuesta del servidor', 'danger');
                        return;
                    }
                }
                
                if (response.success) {
                    mostrarMensaje('¡Respuesta agregada con éxito!', 'success');
                    textarea.val('');
                    ocultarFormularioRespuesta(comentarioPadreId);
                    cargarComentarios();
                } else {
                    mostrarMensaje('Error: ' + (response.message || 'Error desconocido'), 'danger');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Error al publicar respuesta:', error);
                mostrarMensaje('Error de conexión: ' + error, 'danger');
            })
            .always(function() {
                // Rehabilitar el botón
                btnResponder.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Responder');
            });
        }
        
        function toggleLike(comentarioId) {
            $.post('comentarios/procesar_comentario.php', {
                action: 'like_comentario',
                id_comentario: comentarioId
            })
            .done(function(response) {
                // Parsear la respuesta JSON si viene como string
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error('Error al parsear JSON:', e);
                        alert('Error al procesar respuesta del servidor');
                        return;
                    }
                }
                
                if (response.success) {
                    const btn = $(`[data-comentario-id="${comentarioId}"] .btn-like`);
                    const countSpan = btn.find('.likes-count');
                    
                    if (response.liked) {
                        btn.addClass('liked');
                    } else {
                        btn.removeClass('liked');
                    }
                    
                    countSpan.text(response.likes_count);
                } else {
                    alert('Error: ' + response.message);
                }
            })
            .fail(function() {
                alert('Error de conexión');
            });
        }
        
        function eliminarComentario(comentarioId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este comentario?\n\nEsta acción no se puede deshacer.')) {
                return;
            }
            
            // Mostrar loading
            const btnEliminar = $(`[data-comentario-id="${comentarioId}"] .btn-eliminar`);
            const textoOriginal = btnEliminar.html();
            btnEliminar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Eliminando...');
            
            $.post('comentarios/procesar_comentario.php', {
                action: 'eliminar_comentario',
                id_comentario: comentarioId
            })
            .done(function(response) {
                // Parsear la respuesta JSON si viene como string
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error('Error al parsear JSON:', e);
                        mostrarMensaje('Error al procesar respuesta del servidor', 'danger');
                        return;
                    }
                }
                
                if (response.success) {
                    mostrarMensaje('¡Comentario eliminado exitosamente!', 'success');
                    cargarComentarios();
                } else {
                    mostrarMensaje('Error: ' + (response.message || 'Error desconocido'), 'danger');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Error al eliminar comentario:', error);
                mostrarMensaje('Error de conexión: ' + error, 'danger');
            })
            .always(function() {
                // Rehabilitar el botón
                btnEliminar.prop('disabled', false).html(textoOriginal);
            });
        }
        
        function mostrarFormularioRespuesta(comentarioId) {
            $(`#form-respuesta-${comentarioId}`).addClass('show');
            $(`#form-respuesta-${comentarioId} .comentario-textarea`).focus();
        }
        
        function ocultarFormularioRespuesta(comentarioId) {
            $(`#form-respuesta-${comentarioId}`).removeClass('show');
            $(`#form-respuesta-${comentarioId} .comentario-textarea`).val('');
        }
        
        function mostrarMensaje(mensaje, tipo = 'info') {
            // Crear el mensaje
            const alertClass = `alert-${tipo}`;
            const iconClass = tipo === 'success' ? 'fa-check-circle' : 
                             tipo === 'danger' ? 'fa-exclamation-triangle' : 
                             tipo === 'warning' ? 'fa-exclamation-circle' : 'fa-info-circle';
            
            const mensajeHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="margin: 10px 0;">
                    <i class="fas ${iconClass} mr-2"></i>
                    ${mensaje}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            
            // Insertar el mensaje al inicio de la sección de comentarios
            $('.comentarios-section').prepend(mensajeHtml);
            
            // Auto-ocultar después de 5 segundos
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
        
        function mostrarModalReporte(comentarioId) {
            $('#comentario_id_reporte').val(comentarioId);
            $('#motivo_reporte').val('');
            $('#descripcion_reporte').val('');
            $('#modalReporte').modal('show');
        }
        
        function enviarReporte() {
            const comentarioId = $('#comentario_id_reporte').val();
            const motivo = $('#motivo_reporte').val();
            const descripcion = $('#descripcion_reporte').val();
            
            if (!motivo) {
                alert('Por favor selecciona un motivo para el reporte');
                return;
            }
            
            $.post('comentarios/procesar_comentario.php', {
                action: 'reportar_comentario',
                id_comentario: comentarioId,
                motivo: motivo,
                descripcion: descripcion
            })
            .done(function(response) {
                // Parsear la respuesta JSON si viene como string
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error('Error al parsear JSON:', e);
                        alert('Error al procesar respuesta del servidor');
                        return;
                    }
                }
                
                if (response.success) {
                    alert('Comentario reportado exitosamente. Gracias por tu colaboración.');
                    $('#modalReporte').modal('hide');
                } else {
                    alert('Error: ' + response.message);
                }
            })
            .fail(function() {
                alert('Error de conexión');
            });
        }
    </script>
</body>
</html>


