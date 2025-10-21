<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../../login.php');
    exit;
}

// Obtener reportes con información de usuarios y respuestas
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        resp.contenido as respuesta_contenido,
        resp.fecha_creacion as respuesta_fecha,
        u1.nombre_completo as reportador_nombre,
        u2.nombre_completo as reportado_nombre,
        t.titulo as tema_titulo,
        f.titulo as foro_titulo
    FROM reportes_respuestas r
    INNER JOIN respuestas_foro resp ON r.id_respuesta = resp.id
    INNER JOIN temas_foro t ON resp.id_tema = t.id
    INNER JOIN foros f ON t.id_foro = f.id
    INNER JOIN usuarios u1 ON r.id_usuario_reportador = u1.id
    INNER JOIN usuarios u2 ON r.id_usuario_reportado = u2.id
    ORDER BY r.fecha_reporte DESC
");
$stmt->execute();
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar reportes por estado
$stmt = $pdo->prepare("
    SELECT estado, COUNT(*) as total 
    FROM reportes_respuestas 
    GROUP BY estado
");
$stmt->execute();
$estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estadisticas_array = [];
foreach ($estadisticas as $stat) {
    $estadisticas_array[$stat['estado']] = $stat['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Reportes - Panel Admin</title>
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
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .stats-label {
            color: #6c757d;
            font-weight: 500;
        }
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 4px solid #dc3545;
        }
        .report-card.pendiente {
            border-left-color: #ffc107;
        }
        .report-card.revisado {
            border-left-color: #17a2b8;
        }
        .report-card.resuelto {
            border-left-color: #28a745;
        }
        .report-card.rechazado {
            border-left-color: #6c757d;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .report-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .report-content {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        .status-revisado {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-resuelto {
            background: #d4edda;
            color: #155724;
        }
        .status-rechazado {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-action {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        .btn-marcar-revisado {
            background: linear-gradient(45deg, #17a2b8, #138496);
            color: white;
            border: none;
        }
        .btn-resolver {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
        }
        .btn-rechazar {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            color: white;
            border: none;
        }
        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .filtros {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <?php include '../panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1><i class="fas fa-flag mr-3"></i>Gestionar Reportes</h1>
            <p class="mb-0">Administra los reportes de respuestas del foro</p>
        </div>

        <!-- Estadísticas -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-warning"><?= $estadisticas_array['pendiente'] ?? 0 ?></div>
                    <div class="stats-label">Pendientes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-info"><?= $estadisticas_array['revisado'] ?? 0 ?></div>
                    <div class="stats-label">Revisados</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-success"><?= $estadisticas_array['resuelto'] ?? 0 ?></div>
                    <div class="stats-label">Resueltos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number text-secondary"><?= $estadisticas_array['rechazado'] ?? 0 ?></div>
                    <div class="stats-label">Rechazados</div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <div class="row">
                <div class="col-md-4">
                    <label for="filtro_estado">Filtrar por estado:</label>
                    <select class="form-control" id="filtro_estado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendientes</option>
                        <option value="revisado">Revisados</option>
                        <option value="resuelto">Resueltos</option>
                        <option value="rechazado">Rechazados</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filtro_motivo">Filtrar por motivo:</label>
                    <select class="form-control" id="filtro_motivo">
                        <option value="">Todos los motivos</option>
                        <option value="spam">Spam</option>
                        <option value="inapropiado">Inapropiado</option>
                        <option value="acoso">Acoso</option>
                        <option value="desinformacion">Desinformación</option>
                        <option value="violencia">Violencia</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary btn-block" onclick="aplicarFiltros()">
                        <i class="fas fa-filter mr-2"></i>Aplicar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Lista de Reportes -->
        <div class="reportes-container">
            <?php if (empty($reportes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                    <h4>No hay reportes</h4>
                    <p class="text-muted">No se han encontrado reportes de respuestas.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reportes as $reporte): ?>
                    <div class="report-card <?= $reporte['estado'] ?>" data-estado="<?= $reporte['estado'] ?>" data-motivo="<?= $reporte['motivo'] ?>">
                        <div class="report-header">
                            <div>
                                <h5>Reporte #<?= $reporte['id'] ?></h5>
                                <div class="report-meta">
                                    <i class="fas fa-user mr-1"></i>Reportado por: <?= htmlspecialchars($reporte['reportador_nombre']) ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-calendar mr-1"></i><?= date('d M Y H:i', strtotime($reporte['fecha_reporte'])) ?>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge status-<?= $reporte['estado'] ?>">
                                    <?= ucfirst($reporte['estado']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <h6><i class="fas fa-comment mr-2"></i>Respuesta Reportada:</h6>
                                <div class="report-content">
                                    <strong>Autor:</strong> <?= htmlspecialchars($reporte['reportado_nombre']) ?><br>
                                    <strong>Foro:</strong> <?= htmlspecialchars($reporte['foro_titulo']) ?> > <?= htmlspecialchars($reporte['tema_titulo']) ?><br>
                                    <strong>Contenido:</strong><br>
                                    <em><?= htmlspecialchars(substr($reporte['respuesta_contenido'], 0, 200)) ?><?= strlen($reporte['respuesta_contenido']) > 200 ? '...' : '' ?></em>
                                </div>

                                <h6><i class="fas fa-exclamation-triangle mr-2"></i>Motivo del Reporte:</h6>
                                <div class="report-content">
                                    <strong>Motivo:</strong> <?= ucfirst($reporte['motivo']) ?><br>
                                    <?php if (!empty($reporte['descripcion'])): ?>
                                        <strong>Descripción:</strong><br>
                                        <?= nl2br(htmlspecialchars($reporte['descripcion'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6>Acciones:</h6>
                                <?php if ($reporte['estado'] === 'pendiente'): ?>
                                    <button class="btn btn-action btn-marcar-revisado" onclick="cambiarEstado(<?= $reporte['id'] ?>, 'revisado')">
                                        <i class="fas fa-eye mr-1"></i>Marcar Revisado
                                    </button>
                                    <button class="btn btn-action btn-resolver" onclick="cambiarEstado(<?= $reporte['id'] ?>, 'resuelto')">
                                        <i class="fas fa-check mr-1"></i>Resolver
                                    </button>
                                    <button class="btn btn-action btn-rechazar" onclick="cambiarEstado(<?= $reporte['id'] ?>, 'rechazado')">
                                        <i class="fas fa-times mr-1"></i>Rechazar
                                    </button>
                                <?php elseif ($reporte['estado'] === 'revisado'): ?>
                                    <button class="btn btn-action btn-resolver" onclick="cambiarEstado(<?= $reporte['id'] ?>, 'resuelto')">
                                        <i class="fas fa-check mr-1"></i>Resolver
                                    </button>
                                    <button class="btn btn-action btn-rechazar" onclick="cambiarEstado(<?= $reporte['id'] ?>, 'rechazado')">
                                        <i class="fas fa-times mr-1"></i>Rechazar
                                    </button>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <a href="ver_tema.php?id=<?= $reporte['id_respuesta'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-external-link-alt mr-1"></i>Ver Respuesta
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        function aplicarFiltros() {
            const estado = $('#filtro_estado').val();
            const motivo = $('#filtro_motivo').val();
            
            $('.report-card').each(function() {
                const cardEstado = $(this).data('estado');
                const cardMotivo = $(this).data('motivo');
                
                let mostrar = true;
                
                if (estado && cardEstado !== estado) {
                    mostrar = false;
                }
                
                if (motivo && cardMotivo !== motivo) {
                    mostrar = false;
                }
                
                if (mostrar) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        function cambiarEstado(reporteId, nuevoEstado) {
            const accion = nuevoEstado === 'revisado' ? 'marcar como revisado' : 
                          nuevoEstado === 'resuelto' ? 'resolver' : 'rechazar';
            
            // Si es resolver o rechazar, pedir mensaje personalizado
            if (nuevoEstado === 'resuelto' || nuevoEstado === 'rechazado') {
                Swal.fire({
                    title: `${accion.charAt(0).toUpperCase() + accion.slice(1)} Reporte`,
                    html: `
                        <div class="form-group">
                            <label for="mensaje_usuario">Mensaje para el usuario (opcional):</label>
                            <textarea id="mensaje_usuario" class="form-control" rows="4" 
                                placeholder="Escribe un mensaje personalizado para el usuario reportado..."></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        return {
                            mensaje: document.getElementById('mensaje_usuario').value
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        procesarCambioEstado(reporteId, nuevoEstado, result.value.mensaje);
                    }
                });
            } else {
                // Para revisado, no pedir mensaje
                Swal.fire({
                    title: '¿Confirmar acción?',
                    text: `¿Estás seguro de que quieres ${accion} este reporte?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, confirmar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        procesarCambioEstado(reporteId, nuevoEstado, '');
                    }
                });
            }
        }

        function procesarCambioEstado(reporteId, nuevoEstado, mensaje) {
            const formData = new FormData();
            formData.append('id_reporte', reporteId);
            formData.append('nuevo_estado', nuevoEstado);
            formData.append('mensaje_usuario', mensaje);

            fetch('procesar_reporte.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Acción realizada!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error || 'Error al procesar la acción', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        }
    </script>
</body>
</html>
