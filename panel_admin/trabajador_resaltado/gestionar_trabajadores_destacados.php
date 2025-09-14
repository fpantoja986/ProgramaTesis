<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// Obtener todos los trabajadores destacados con información del trabajador (solo usuarios verificados con rol 'usuario')
$stmt = $pdo->prepare("
    SELECT td.*, u.nombre_completo, u.email, u.foto_perfil,
           DATE_FORMAT(td.fecha_inicio, '%M %Y') as periodo_formato,
           CASE 
               WHEN (td.fecha_fin IS NULL OR td.fecha_fin >= CURDATE()) AND td.fecha_inicio <= CURDATE() THEN 'Activo'
               WHEN td.fecha_inicio > CURDATE() THEN 'Programado'
               ELSE 'Inactivo'
           END as estado
    FROM trabajadores_destacados td 
    INNER JOIN usuarios u ON td.id_usuario = u.id 
    WHERE u.rol = 'usuario' AND u.verificado = 1
    ORDER BY td.fecha_inicio DESC
");
$stmt->execute();
$trabajadores_destacados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener solo usuarios con rol 'usuario' (trabajadores) verificados para el selector
$stmt_usuarios = $pdo->prepare("
    SELECT id, nombre_completo, email, foto_perfil, rol 
    FROM usuarios 
    WHERE verificado = 1 AND rol = 'usuario'
    ORDER BY nombre_completo ASC
");
$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Trabajadores Destacados</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="trabajadores.css?v=1">
    
    
</head>
<body>
    <?php include '../panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <span>
                    <i class="fas fa-star mr-2"></i>Trabajadores Destacados del Mes
                    <span class="badge badge-light ml-2"><?= count($trabajadores_destacados) ?></span>
                </span>
                <button class="btn btn-create-destacado" data-toggle="modal" data-target="#modalCrearDestacado">
                    <i class="fas fa-plus-circle mr-2"></i>Destacar Trabajador
                </button>
            </h1>
        </div>

        <?php if (empty($trabajadores_destacados)): ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <h4>No hay trabajadores destacados</h4>
                <p>Comienza a reconocer el excelente trabajo de tu equipo</p>
                <button class="btn btn-create-destacado" data-toggle="modal" data-target="#modalCrearDestacado">
                    <i class="fas fa-plus-circle mr-2"></i>Destacar Primer Trabajador
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($trabajadores_destacados as $destacado): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card destacado-card">
                            <div class="destacado-header">
                                <span class="status-badge <?= $destacado['estado'] === 'Activo' ? 'status-active' : ($destacado['estado'] === 'Programado' ? 'status-programado' : 'status-inactive') ?>">
                                    <?= $destacado['estado'] ?>
                                </span>
                                <div class="empleado-info">
                                    <img src="<?= $destacado['foto_perfil'] ? '../../uploads/perfiles/' . $destacado['foto_perfil'] : '../../assets/img/default-avatar.png' ?>" 
                                         alt="Foto de perfil" class="empleado-avatar">
                                    <div class="empleado-details">
                                        <h5><?= htmlspecialchars($destacado['nombre_completo']) ?></h5>
                                        <small>
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?= htmlspecialchars($destacado['periodo_formato']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="destacado-body">
                                <div class="periodo-info">
                                    <small class="text-muted">
                                        <i class="fas fa-clock mr-1"></i>
                                        Desde: <?= date('d/m/Y', strtotime($destacado['fecha_inicio'])) ?>
                                        <?php if ($destacado['fecha_fin']): ?>
                                            - Hasta: <?= date('d/m/Y', strtotime($destacado['fecha_fin'])) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <div class="mensaje-merito">
                                    <h6><i class="fas fa-trophy mr-2"></i>Motivo del Reconocimiento:</h6>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($destacado['mensaje_merito'])) ?></p>
                                </div>

                                <div class="destacado-actions">
                                    <button class="btn btn-info btn-sm btn-editar-destacado" data-id="<?= $destacado['id'] ?>">
                                        <i class="fas fa-edit mr-1"></i>Editar
                                    </button>
                                    
                                    <?php if ($destacado['estado'] === 'Activo' || $destacado['estado'] === 'Programado'): ?>
                                        <button class="btn btn-warning btn-sm btn-finalizar-destacado" data-id="<?= $destacado['id'] ?>">
                                            <i class="fas fa-stop mr-1"></i>Finalizar
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-success btn-sm btn-reactivar-destacado" data-id="<?= $destacado['id'] ?>">
                                            <i class="fas fa-play mr-1"></i>Reactivar
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-danger btn-sm btn-eliminar-destacado" data-id="<?= $destacado['id'] ?>">
                                        <i class="fas fa-trash mr-1"></i>Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Crear/Editar Trabajador Destacado -->
    <div class="modal fade" id="modalCrearDestacado" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-star mr-2"></i><span id="modal-title">Destacar Trabajador del Mes</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formDestacado" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="destacado_id" name="id">
                        <input type="hidden" id="accion" name="accion" value="crear">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_usuario">Seleccionar Trabajador *</label>
                                    <select class="form-control" id="id_usuario" name="id_usuario" required>
                                        <option value="">Selecciona un trabajador...</option>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <option value="<?= $usuario['id'] ?>" data-foto="<?= $usuario['foto_perfil'] ?>">
                                                <?= htmlspecialchars($usuario['nombre_completo']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Vista Previa</label>
                                    <div class="text-center">
                                        <img id="preview-foto" src="../../assets/img/default-avatar.png" 
                                             alt="Vista previa" class="preview-image">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fecha_inicio">Fecha de Inicio *</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fecha_fin">Fecha de Fin (Opcional)</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                                    <small class="text-muted">Dejar vacío para que sea permanente hasta nueva orden</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="mensaje_merito">Mensaje de Reconocimiento *</label>
                            <textarea class="form-control" id="mensaje_merito" name="mensaje_merito" rows="4" required
                                      placeholder="Describe los logros y méritos que hacen destacar a este trabajador..."></textarea>
                            <small class="text-muted">Este mensaje se mostrará en la ventana emergente a todos los usuarios</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="mostrar_popup" name="mostrar_popup" checked>
                                <label class="custom-control-label" for="mostrar_popup">
                                    Mostrar ventana emergente a todos los usuarios al iniciar sesión
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i><span id="btn-text">Destacar Trabajador</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        // Establecer fecha de inicio por defecto (primer día del mes actual)
        $(document).ready(function() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const fechaInicio = firstDay.toISOString().split('T')[0];
            $('#fecha_inicio').val(fechaInicio);
        });

        // Preview de foto al seleccionar usuario
        $('#id_usuario').on('change', function() {
            const foto = $(this).find(':selected').data('foto');
            const fotoSrc = foto ? 
                '../../uploads/perfiles/' + foto : 
                '../../assets/img/default-avatar.png';
            $('#preview-foto').attr('src', fotoSrc);
        });

        // Crear/Editar trabajador destacado
        $('#formDestacado').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('procesar_destacado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#modalCrearDestacado').modal('hide');
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error || 'Error al procesar la solicitud', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Error de conexión', 'error'));
        });

        // Editar trabajador destacado
        $('.btn-editar-destacado').on('click', function() {
            const id = $(this).data('id');
            
            fetch(`obtener_destacado.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const destacado = data.destacado;
                    $('#destacado_id').val(destacado.id);
                    $('#accion').val('editar');
                    $('#id_usuario').val(destacado.id_usuario).trigger('change');
                    $('#fecha_inicio').val(destacado.fecha_inicio);
                    $('#fecha_fin').val(destacado.fecha_fin || '');
                    $('#mensaje_merito').val(destacado.mensaje_merito);
                    $('#mostrar_popup').prop('checked', destacado.mostrar_popup == 1);
                    
                    $('#modal-title').text('Editar Trabajador Destacado');
                    $('#btn-text').text('Guardar Cambios');
                    $('#modalCrearDestacado').modal('show');
                }
            });
        });

        // Finalizar período de trabajador destacado
        $('.btn-finalizar-destacado').on('click', function() {
            const id = $(this).data('id');
            
            Swal.fire({
                title: '¿Finalizar período?',
                text: 'Esto finalizará el período de reconocimiento del trabajador destacado.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, finalizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('accion', 'finalizar');
                    formData.append('id', id);
                    
                    fetch('procesar_destacado.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Finalizado!',
                                text: data.message,
                                icon: 'success',
                                timer: 2000
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.error, 'error');
                        }
                    });
                }
            });
        });

        // Reactivar trabajador destacado
        $('.btn-reactivar-destacado').on('click', function() {
            const id = $(this).data('id');
            
            const formData = new FormData();
            formData.append('accion', 'reactivar');
            formData.append('id', id);
            
            fetch('procesar_destacado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Reactivado!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            });
        });

        // Eliminar trabajador destacado
        $('.btn-eliminar-destacado').on('click', function() {
            const id = $(this).data('id');
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esto eliminará permanentemente el registro del trabajador destacado.',
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
                    
                    fetch('procesar_destacado.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: data.message,
                                icon: 'success',
                                timer: 2000
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.error, 'error');
                        }
                    });
                }
            });
        });

        // Reset modal al cerrarse
        $('#modalCrearDestacado').on('hidden.bs.modal', function() {
            $('#formDestacado')[0].reset();
            $('#destacado_id').val('');
            $('#accion').val('crear');
            $('#modal-title').text('Destacar Trabajador del Mes');
            $('#btn-text').text('Destacar Trabajador');
            $('#preview-foto').attr('src', '../../assets/img/default-avatar.png');
            
            // Establecer fecha de inicio por defecto
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const fechaInicio = firstDay.toISOString().split('T')[0];
            $('#fecha_inicio').val(fechaInicio);
        });
    </script>
</body>
</html>