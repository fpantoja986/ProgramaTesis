<?php
include '../db.php';
session_start();
// Evitar caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}
// Recuperar solo usuarios que no son administradores
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rol != 'administrador'");
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
    <title>Gestión de Usuarios</title>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6f42c1;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-container {
            margin-left: 90px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                margin-left: 0;
                margin-top: 70px;
                padding: 15px;
            }
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .users-count {
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            font-size: 0.9rem;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-success:hover {
            background-color: #17a673;
            border-color: #17a673;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(28, 200, 138, 0.3);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #3a5fc8;
            border-color: #3a5fc8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(78, 115, 223, 0.3);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background-color: #d52a1a;
            border-color: #d52a1a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 74, 59, 0.3);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 15px 20px;
            font-weight: 700;
            color: var(--primary-color);
            border-radius: 12px 12px 0 0 !important;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 0 0 12px 12px;
        }
        
        .table {
            margin-bottom: 0;
            width: 100%;
        }
        
        .table th {
            border-top: none;
            font-weight: 700;
            color: var(--dark-text);
            background-color: #f8f9fc;
            padding: 15px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #e3e6f0;
        }
        
        .table tr {
            transition: background-color 0.2s;
        }
        
        .table tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .badge-verified {
            background-color: var(--success-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .badge-not-verified {
            background-color: var(--warning-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .badge-role {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-user {
            background-color: #858796;
            color: white;
        }
        
        .badge-admin {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: var(--card-shadow);
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-title {
            font-weight: 700;
        }
        
        .close {
            color: white;
            opacity: 0.9;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #d1d3e2;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        
        .nav-link {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
            padding: 10px 15px;
            border-radius: 8px;
        }
        
        .nav-link:hover {
            background-color: rgba(78, 115, 223, 0.1);
            color: #3a5fc8;
            text-decoration: none;
        }
        
        .nav-link i {
            margin-right: 8px;
        }
        
        .gender-icon {
            margin-right: 5px;
        }
        
        .form-check-input:checked {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
    </style>
</head>

<body>
    <?php include 'panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Usuarios Registrados <span class="users-count"><?php echo count($usuarios); ?></span></h1>
            <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-plus-circle mr-2"></i>Nuevo Usuario
            </button>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-users mr-2"></i>Lista de Usuarios
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
                                    <span class="badge-role badge-<?= $usuario['rol'] === 'administrador' ? 'admin' : 'user' ?>">
                                        <?= htmlspecialchars($usuario['rol']); ?>
                                    </span>
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

        <a href="admins.php" class="nav-link mt-3">
            <i class="fas fa-arrow-right mr-2"></i>Ver administradores
        </a>
    </div>

    <!-- Modal Agregar Usuario -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="add-user-form" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Agregar Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Campos comunes -->
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
                    <!-- Campo oculto para forzar el rol "usuario" -->
                    <input type="hidden" name="rol" value="usuario">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Agregar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de edición -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="edit-user-form" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar Usuario</h5>
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
        });

        document.querySelectorAll('.eliminar-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción eliminará el usuario de forma permanente.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('eliminar_usuario.php?id=' + userId)
                            .then(response => {
                                if (!response.ok) throw new Error('Error al eliminar');
                                return response.text();
                            })
                            .then(() => {
                                Swal.fire({
                                    title: '¡Eliminado!',
                                    text: 'El usuario ha sido eliminado con éxito.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            })
                            .catch(error => {
                                Swal.fire('Error', 'No se pudo eliminar el usuario.', 'error');
                            });
                    }
                });
            });
        });

        $('#add-user-form').on('submit', function(e) {
            e.preventDefault();

            // Mostrar loader mientras se procesa
            Swal.fire({
                title: 'Registrando usuario',
                html: 'Por favor espera...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'agregar_usuario.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    Swal.close();
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Usuario registrado',
                            text: res.message,
                            timer: 2500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en el servidor',
                        text: 'No se pudo procesar la solicitud. Detalles: ' + error
                    });
                }
            });
        });
    </script>
</body>

</html>