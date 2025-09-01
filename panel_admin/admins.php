<?php
include '../db.php';
session_start();
// Evitar caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// Recuperar solo usuarios con rol 'administrador'
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rol = 'administrador'");
$stmt->execute();
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="stylesadmin.css?v=1">
    <title>Administradores</title>


</head>

<body>
    <?php include 'panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Administradores del Sistema <span class="admin-count"><?php echo count($usuarios); ?></span></h1>
            <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addAdminModal">
                <i class="fas fa-plus-circle mr-2"></i>Nuevo Administrador
            </button>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-users-cog mr-2"></i>Lista de Administradores
            </div>
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre completo</th>
                            <th>Email</th>
                            <th>Género</th>
                            <th>Rol</th>
                            <th>Verificado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nombre_completo']); ?></td>
                                <td><?= htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <?php
                                    $icon = 'fa-user';
                                    if ($usuario['genero'] === 'Masculino') $icon = 'fa-mars';
                                    if ($usuario['genero'] === 'Femenino') $icon = 'fa-venus';
                                    ?>
                                    <i class="fas <?= $icon; ?> gender-icon"></i>
                                    <?= htmlspecialchars($usuario['genero'] ?? 'No definido'); ?>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?= htmlspecialchars($usuario['rol']); ?></span>
                                </td>
                                <td>
                                    <?php if ($usuario['verificado']): ?>
                                        <span class="badge-verified"><i class="fas fa-check-circle mr-1"></i>Sí</span>
                                    <?php else: ?>
                                        <span class="badge-not-verified"><i class="fas fa-times-circle mr-1"></i>No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm btn-edit" data-usuario='<?= json_encode($usuario, JSON_HEX_APOS); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm eliminar-btn" data-id="<?= $usuario['id']; ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <a href="users.php" class="back-link">
            <i class="fas fa-arrow-left"></i>Volver a la gestión de usuarios
        </a>
    </div>

    <!-- Modal Agregar Admin -->
    <div class="modal fade" id="addAdminModal" tabindex="-1" role="dialog" aria-labelledby="addAdminLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="add-admin-form" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Agregar Administrador</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre completo</label>
                        <input type="text" class="form-control" name="nombre_completo" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Género</label>
                        <select class="form-control" name="genero" required>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <input type="hidden" name="rol" value="administrador">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Agregar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal edición -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="edit-user-form" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar Administrador</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="form-group">
                        <label>Nombre completo</label>
                        <input type="text" class="form-control" name="nombre_completo" id="edit-nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" id="edit-email" required>
                    </div>
                    <div class="form-group">
                        <label>Género</label>
                        <select class="form-control" name="genero" id="edit-genero">
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rol</label>
                        <select class="form-control" name="rol" id="edit-rol">
                            <option value="usuario">Usuario</option>
                            <option value="administrador">Administrador</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="verificado" id="edit-verificado">
                        <label class="form-check-label" for="edit-verificado">Verificado</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            $('.btn-edit').on('click', function() {
                const usuario = $(this).data('usuario');
                $('#edit-id').val(usuario.id);
                $('#edit-nombre').val(usuario.nombre_completo);
                $('#edit-email').val(usuario.email);
                $('#edit-genero').val(usuario.genero);
                $('#edit-rol').val(usuario.rol);
                $('#edit-verificado').prop('checked', usuario.verificado == 1);
                $('#editUserModal').modal('show');
            });

            $('#edit-user-form').on('submit', function(e) {
                e.preventDefault();
                $.post('editar_usuario_modal.php', $(this).serialize(), function(response) {
                    if (response === 'ok') {
                        location.reload();
                    } else {
                        alert('Error al actualizar');
                    }
                });
            });

            $('#add-admin-form').on('submit', function(e) {
                e.preventDefault();

                // Mostrar loader mientras se procesa
                Swal.fire({
                    title: 'Registrando administrador',
                    html: 'Por favor espera...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar datos al servidor
                $.ajax({
                    url: 'agregar_usuario.php', // Mismo endpoint pero con validación de rol
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json', // Forzar respuesta como JSON
                    success: function(res) {
                        Swal.close();
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Administrador registrado!',
                                text: res.message,
                                timer: 2500,
                                showConfirmButton: false,
                                willClose: () => {
                                    location.reload(); // Recargar después de cerrar
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al registrar',
                                text: res.message || 'Error desconocido',
                                confirmButtonText: 'Entendido'
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.close();
                        let errorMsg = 'Error en la conexión con el servidor';

                        try {
                            const res = JSON.parse(xhr.responseText);
                            errorMsg = res.message || errorMsg;
                        } catch (e) {
                            errorMsg = `${errorMsg} (Código ${xhr.status})`;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error crítico',
                            html: `<small>${errorMsg}</small>`,
                            confirmButtonText: 'Reintentar'
                        });
                    }
                });
            });
        });

        document.querySelectorAll('.eliminar-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción eliminará el administrador.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(result => {
                    if (result.isConfirmed) {
                        fetch('eliminar_usuario.php?id=' + userId)
                            .then(r => r.text())
                            .then(() => {
                                Swal.fire('¡Eliminado!', 'El administrador ha sido eliminado.', 'success')
                                    .then(() => location.reload());
                            })
                            .catch(() => {
                                Swal.fire('Error', 'No se pudo eliminar el administrador.', 'error');
                            });
                    }
                });
            });
        });
    </script>
</body>

</html>