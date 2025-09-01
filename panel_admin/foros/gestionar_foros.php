<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// Obtener todos los foros con información del creador
$stmt = $pdo->prepare("
    SELECT f.*, u.nombre_completo as creador_nombre,
           (SELECT COUNT(*) FROM temas_foro t WHERE t.id_foro = f.id) as total_temas,
           (SELECT COUNT(*) FROM respuestas_foro r 
            INNER JOIN temas_foro t ON r.id_tema = t.id 
            WHERE t.id_foro = f.id) as total_respuestas
    FROM foros f 
    INNER JOIN usuarios u ON f.id_admin = u.id 
    ORDER BY f.fecha_creacion DESC
");
$stmt->execute();
$foros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Foros</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="styles_foro.css">

</head>
<body>
    <?php include '../panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-comments mr-2"></i>Gestionar Foros
                <span class="badge badge-primary ml-2"><?= count($foros) ?></span>
            </h1>
            <button class="btn btn-create-forum" data-toggle="modal" data-target="#modalCrearForo">
                <i class="fas fa-plus-circle mr-2"></i>Crear Nuevo Foro
            </button>
        </div>

        <?php if (empty($foros)): ?>
            <div class="text-center py-5">
                <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No hay foros creados</h4>
                <p class="text-muted">Crea tu primer foro para que los usuarios puedan interactuar</p>
                <button class="btn btn-create-forum" data-toggle="modal" data-target="#modalCrearForo">
                    <i class="fas fa-plus-circle mr-2"></i>Crear Primer Foro
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($foros as $foro): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card forum-card">
                            <div class="forum-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($foro['titulo']) ?></h5>
                                        <small class="opacity-75">
                                            <i class="fas fa-user mr-1"></i>
                                            Creado por: <?= htmlspecialchars($foro['creador_nombre']) ?>
                                        </small>
                                    </div>
                                    <span class="status-badge <?= $foro['activo'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $foro['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="forum-body">
                                <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($foro['descripcion'])) ?></p>
                                
                                <div class="forum-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-list-alt mr-1"></i>
                                        <span class="stat-number"><?= $foro['total_temas'] ?></span> Temas
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-comments mr-1"></i>
                                        <span class="stat-number"><?= $foro['total_respuestas'] ?></span> Respuestas
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?= date('d M Y', strtotime($foro['fecha_creacion'])) ?>
                                    </div>
                                </div>

                                <div class="forum-actions">
                                    <a href="ver_foro.php?id=<?= $foro['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye mr-1"></i>Ver Foro
                                    </a>
                                    <?php if ($foro['id_admin'] == $_SESSION['user_id']): ?>
                                        <button class="btn btn-info btn-sm btn-editar-foro" data-id="<?= $foro['id'] ?>">
                                            <i class="fas fa-edit mr-1"></i>Editar
                                        </button>
                                        <button class="btn btn-<?= $foro['activo'] ? 'warning' : 'success' ?> btn-sm btn-toggle-status" 
                                                data-id="<?= $foro['id'] ?>" data-status="<?= $foro['activo'] ?>">
                                            <i class="fas fa-<?= $foro['activo'] ? 'pause' : 'play' ?> mr-1"></i>
                                            <?= $foro['activo'] ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                        <button class="btn btn-danger btn-sm btn-eliminar-foro" data-id="<?= $foro['id'] ?>">
                                            <i class="fas fa-trash mr-1"></i>Eliminar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Crear Foro -->
    <div class="modal fade" id="modalCrearForo" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle mr-2"></i>Crear Nuevo Foro
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formCrearForo">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="titulo_foro">Título del Foro</label>
                            <input type="text" class="form-control" id="titulo_foro" name="titulo" required 
                                   placeholder="Ej: Discusión General">
                        </div>
                        <div class="form-group">
                            <label for="descripcion_foro">Descripción</label>
                            <textarea class="form-control" id="descripcion_foro" name="descripcion" rows="3" 
                                      placeholder="Describe de qué trata este foro..."></textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="activo_foro" name="activo" checked>
                                <label class="custom-control-label" for="activo_foro">Activar foro inmediatamente</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Crear Foro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Foro -->
    <div class="modal fade" id="modalEditarForo" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit mr-2"></i>Editar Foro
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formEditarForo">
                    <div class="modal-body">
                        <input type="hidden" id="edit_foro_id" name="id">
                        <div class="form-group">
                            <label for="edit_titulo_foro">Título del Foro</label>
                            <input type="text" class="form-control" id="edit_titulo_foro" name="titulo" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_descripcion_foro">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion_foro" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="edit_activo_foro" name="activo">
                                <label class="custom-control-label" for="edit_activo_foro">Foro activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">
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
        // Crear nuevo foro
        $('#formCrearForo').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('procesar_foro.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#modalCrearForo').modal('hide');
                    Swal.fire({
                        title: '¡Creado!',
                        text: 'El foro ha sido creado exitosamente.',
                        icon: 'success',
                        timer: 2000
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error || 'Error al crear el foro', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Error de conexión', 'error'));
        });

        // Editar foro
        $('.btn-editar-foro').on('click', function() {
            const id = $(this).data('id');
            fetch(`obtener_foro.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#edit_foro_id').val(data.foro.id);
                    $('#edit_titulo_foro').val(data.foro.titulo);
                    $('#edit_descripcion_foro').val(data.foro.descripcion);
                    $('#edit_activo_foro').prop('checked', data.foro.activo == 1);
                    $('#modalEditarForo').modal('show');
                }
            });
        });

        // Guardar cambios de edición
        $('#formEditarForo').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('accion', 'editar');
            
            fetch('procesar_foro.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#modalEditarForo').modal('hide');
                    Swal.fire({
                        title: '¡Actualizado!',
                        text: 'El foro ha sido actualizado exitosamente.',
                        icon: 'success',
                        timer: 2000
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error || 'Error al actualizar el foro', 'error');
                }
            });
        });

        // Toggle status del foro
        $('.btn-toggle-status').on('click', function() {
            const id = $(this).data('id');
            const currentStatus = $(this).data('status');
            const newStatus = currentStatus ? 0 : 1;
            
            const formData = new FormData();
            formData.append('accion', 'toggle_status');
            formData.append('id', id);
            formData.append('activo', newStatus);
            
            fetch('procesar_foro.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Actualizado!',
                        text: `El foro ha sido ${newStatus ? 'activado' : 'desactivado'}.`,
                        icon: 'success',
                        timer: 2000
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error || 'Error al cambiar el estado', 'error');
                }
            });
        });

        // Eliminar foro
        $('.btn-eliminar-foro').on('click', function() {
            const id = $(this).data('id');
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esto eliminará el foro y todos sus temas y respuestas permanentemente.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('accion', 'eliminar');
                    formData.append('id', id);
                    
                    fetch('procesar_foro.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: 'El foro ha sido eliminado exitosamente.',
                                icon: 'success',
                                timer: 2000
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.error || 'Error al eliminar el foro', 'error');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>