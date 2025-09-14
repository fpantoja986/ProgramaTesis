<?php
include '../../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../../login.php');
    exit;
}

// Obtener respuestas reportadas o que necesitan moderación
$stmt = $pdo->prepare("
    SELECT rf.*, 
           t.titulo as tema_titulo,
           f.titulo as foro_titulo,
           u.nombre_completo as usuario_nombre,
           u.email as usuario_email,
           rf.fecha_creacion,
           CASE 
               WHEN rf.estado_moderacion = 'pendiente' THEN 'Pendiente'
               WHEN rf.estado_moderacion = 'aprobado' THEN 'Aprobado'
               WHEN rf.estado_moderacion = 'rechazado' THEN 'Rechazado'
               WHEN rf.estado_moderacion = 'reportado' THEN 'Reportado'
               WHEN rf.estado_moderacion = 'advertencia' THEN 'Advertido'
               ELSE 'Sin revisar'
           END as estado_texto,
           (SELECT COUNT(*) FROM reportes_respuestas rr WHERE rr.id_respuesta = rf.id) as total_reportes
    FROM respuestas_foro rf
    INNER JOIN temas_foro t ON rf.id_tema = t.id
    INNER JOIN foros f ON t.id_foro = f.id
    INNER JOIN usuarios u ON rf.id_usuario = u.id
    WHERE rf.estado_moderacion IN ('pendiente', 'reportado') 
       OR rf.contenido_sensible = 1
       OR EXISTS (SELECT 1 FROM reportes_respuestas rr WHERE rr.id_respuesta = rf.id AND rr.estado = 'pendiente')
    ORDER BY 
        CASE rf.estado_moderacion
            WHEN 'reportado' THEN 1
            WHEN 'pendiente' THEN 2
            ELSE 3
        END,
        rf.fecha_creacion DESC
");
$stmt->execute();
$respuestas_moderacion = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN estado_moderacion = 'pendiente' THEN 1 END) as pendientes,
        COUNT(CASE WHEN estado_moderacion = 'reportado' THEN 1 END) as reportadas,
        COUNT(CASE WHEN estado_moderacion = 'aprobado' THEN 1 END) as aprobadas,
        COUNT(CASE WHEN estado_moderacion = 'rechazado' THEN 1 END) as rechazadas
    FROM respuestas_foro 
    WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderación de Foros</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="moderacion.css">


</head>

<body>
    <?php include '../../panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-shield-alt mr-2"></i>Moderación de Foros
            </h1>
            <p class="mb-0">Gestiona el contenido inapropiado y mantén un ambiente seguro</p>
        </div>

        <!-- Estadísticas -->
        <div class="row stats-row">
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="stat-number"><?= $stats['reportadas'] ?></div>
                    <div>Reportadas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-number"><?= $stats['pendientes'] ?></div>
                    <div>Pendientes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-number"><?= $stats['aprobadas'] ?></div>
                    <div>Aprobadas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card secondary">
                    <div class="stat-number"><?= $stats['rechazadas'] ?></div>
                    <div>Rechazadas</div>
                </div>
            </div>
        </div>

        <!-- Respuestas para moderar -->
        <?php if (empty($respuestas_moderacion)): ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4>¡Todo está al día!</h4>
                <p class="text-muted">No hay respuestas que requieran moderación en este momento</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($respuestas_moderacion as $respuesta): ?>
                    <div class="col-12">
                        <div class="card moderacion-card">
                            <div class="respuesta-header">
                                <div class="respuesta-meta">
                                    <div class="usuario-info">
                                        <img src="<?= $respuesta['usuario_email'] ? 'https://www.gravatar.com/avatar/' . md5($respuesta['usuario_email']) . '?d=identicon&s=40' : '../../../assets/img/default-avatar.png' ?>"
                                            alt="Avatar" class="usuario-avatar">
                                        <div>
                                            <strong><?= htmlspecialchars($respuesta['usuario_nombre']) ?></strong>
                                            <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])) ?></div>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="estado-badge estado-<?= strtolower($respuesta['estado_moderacion']) ?>">
                                            <?= $respuesta['estado_texto'] ?>
                                        </span>
                                        <?php if ($respuesta['total_reportes'] > 0): ?>
                                            <div class="reportes-info mt-1">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <?= $respuesta['total_reportes'] ?> reporte(s)
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="small">
                                    <strong>Foro:</strong> <?= htmlspecialchars($respuesta['foro_titulo']) ?> |
                                    <strong>Tema:</strong> <?= htmlspecialchars($respuesta['tema_titulo']) ?>
                                </div>
                            </div>

                            <div class="respuesta-contenido">
                                <?= nl2br(htmlspecialchars($respuesta['contenido'])) ?>

                                <?php
                                // Detectar palabras potencialmente problemáticas
                                $palabras_sensibles = ['idiota', 'estúpido', 'tonto', 'inútil', 'basura', 'mierda', 'maldito', 'odio', 'imbécil', 'estupidez'];
                                $contenido_lower = strtolower($respuesta['contenido']);
                                $palabras_encontradas = [];
                                foreach ($palabras_sensibles as $palabra) {
                                    if (strpos($contenido_lower, $palabra) !== false) {
                                        $palabras_encontradas[] = $palabra;
                                    }
                                }

                                if (!empty($palabras_encontradas)): ?>
                                    <div class="palabras-detectadas">
                                        <strong><i class="fas fa-exclamation-circle"></i> Contenido sensible detectado:</strong>
                                        <?php foreach ($palabras_encontradas as $palabra): ?>
                                            <span class="palabra-sensible"><?= htmlspecialchars($palabra) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="respuesta-acciones">
                                <button class="btn btn-success btn-action btn-aprobar" data-id="<?= $respuesta['id'] ?>">
                                    <i class="fas fa-check mr-1"></i>Aprobar
                                </button>
                                <button class="btn btn-warning btn-action btn-advertir" data-id="<?= $respuesta['id'] ?>">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Advertir
                                </button>
                                <button class="btn btn-danger btn-action btn-rechazar" data-id="<?= $respuesta['id'] ?>">
                                    <i class="fas fa-times mr-1"></i>Rechazar
                                </button>
                                <button class="btn btn-info btn-action btn-ver-reportes" data-id="<?= $respuesta['id'] ?>">
                                    <i class="fas fa-eye mr-1"></i>Ver Reportes
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Ver Reportes -->
    <div class="modal fade" id="modalReportes" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-flag mr-2"></i>Reportes de la Respuesta
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="contenido-reportes">
                    <!-- Se carga dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Advertencia -->
    <div class="modal fade" id="modalAdvertencia" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Enviar Advertencia
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formAdvertencia">
                    <div class="modal-body">
                        <input type="hidden" id="respuesta_id_advertencia" name="respuesta_id">
                        <div class="form-group">
                            <label for="motivo_advertencia">Motivo de la advertencia:</label>
                            <textarea class="form-control" id="motivo_advertencia" name="motivo" rows="3" required
                                placeholder="Explica por qué se envía esta advertencia..."></textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="ocultar_respuesta" name="ocultar_respuesta">
                                <label class="custom-control-label" for="ocultar_respuesta">
                                    Ocultar respuesta temporalmente
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Advertencia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        // Aprobar respuesta
        $('.btn-aprobar').on('click', function() {
            const id = $(this).data('id');
            moderarRespuesta(id, 'aprobar', 'Respuesta aprobada exitosamente');
        });

        // Rechazar respuesta
        $('.btn-rechazar').on('click', function() {
            const id = $(this).data('id');

            Swal.fire({
                title: '¿Rechazar respuesta?',
                text: 'La respuesta será oculta y se notificará al usuario',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, rechazar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    moderarRespuesta(id, 'rechazar', 'Respuesta rechazada');
                }
            });
        });

        // Advertir usuario
        $('.btn-advertir').on('click', function() {
            const id = $(this).data('id');
            $('#respuesta_id_advertencia').val(id);
            $('#modalAdvertencia').modal('show');
        });

        // Ver reportes
        $('.btn-ver-reportes').on('click', function() {
            const id = $(this).data('id');
            cargarReportes(id);
        });

        // Función para moderar respuesta
        function moderarRespuesta(id, accion, mensaje) {
            const formData = new FormData();
            formData.append('accion', accion);
            formData.append('id', id);

            fetch('procesar_moderacion.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Procesado!',
                            text: mensaje,
                            icon: 'success',
                            timer: 2000
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.error || 'Error al procesar', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Error de conexión', 'error'));
        }

        // Enviar advertencia
        $('#formAdvertencia').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('accion', 'advertir');

            fetch('procesar_moderacion.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $('#modalAdvertencia').modal('hide');
                        Swal.fire({
                            title: '¡Advertencia enviada!',
                            text: 'Se ha notificado al usuario',
                            icon: 'success',
                            timer: 2000
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.error || 'Error al enviar advertencia', 'error');
                    }
                });
        });

        // Cargar reportes
        function cargarReportes(respuestaId) {
            fetch(`obtener_reportes.php?respuesta_id=${respuestaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        if (data.reportes.length === 0) {
                            html = '<p class="text-muted">No hay reportes para esta respuesta.</p>';
                        } else {
                            data.reportes.forEach(reporte => {
                                html += `
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <strong>${reporte.reportante_nombre}</strong>
                                        <small class="text-muted">${reporte.fecha_reporte}</small>
                                    </div>
                                    <div><strong>Motivo:</strong> ${reporte.motivo}</div>
                                    ${reporte.comentario ? `<div><strong>Comentario:</strong> ${reporte.comentario}</div>` : ''}
                                </div>
                            `;
                            });
                        }
                        $('#contenido-reportes').html(html);
                        $('#modalReportes').modal('show');
                    }
                });
        }
    </script>
</body>

</html>