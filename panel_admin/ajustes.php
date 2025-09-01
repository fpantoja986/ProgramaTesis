<?php
session_start();
// Evitar caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require '../db.php';

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin = null;

// Obtener datos del administrador
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no se encuentra el administrador, redirigir
if (!$admin) {
    header('Location: ../login.php');
    exit;
}

// Procesar actualización de datos personales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nombre = trim($_POST['nombre_completo']);
    $email = trim($_POST['email']);
    $genero = trim($_POST['genero']);
    $foto_perfil = $admin['foto_perfil'] ?? null;

    // Procesar eliminación de foto de perfil
    if (isset($_POST['remove_foto_perfil']) && $_POST['remove_foto_perfil'] === '1') {
        // Eliminar archivo físico si existe
        if (!empty($admin['foto_perfil'])) {
            $ruta_foto = '../uploads/perfiles/' . $admin['foto_perfil'];
            if (file_exists($ruta_foto)) {
                unlink($ruta_foto);
            }
        }
        $foto_perfil = null;
    } else {
        // Procesar foto de perfil si se subió
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            // Leer contenido del archivo
            $foto_data = file_get_contents($_FILES['foto_perfil']['tmp_name']);
            $foto_perfil = base64_encode($foto_data);
        }
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo=?, email=?, genero=?, foto_perfil=? WHERE id=?");
    $stmt->execute([$nombre, $email, $genero, $foto_perfil, $admin_id]);

    // Actualizar datos en la variable $admin
    $admin['nombre_completo'] = $nombre;
    $admin['email'] = $email;
    $admin['genero'] = $genero;
    $admin['foto_perfil'] = $foto_perfil;
    $success = "Datos actualizados correctamente.";
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $actual = $_POST['password_actual'];
    $nueva = $_POST['password_nueva'];
    $confirmar = $_POST['password_confirmar'];

    if (!password_verify($actual, $admin['password'])) {
        $error_pass = "La contraseña actual es incorrecta.";
    } elseif ($nueva !== $confirmar) {
        $error_pass = "Las contraseñas nuevas no coinciden.";
    } elseif (strlen($nueva) < 8) {
        $error_pass = "La nueva contraseña debe tener al menos 8 caracteres.";
    } else {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password=? WHERE id=?");
        $stmt->execute([$hash, $admin_id]);
        $success_pass = "Contraseña actualizada correctamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ajustes de Administrador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="stylesadmin.css?v=1">

</head>

<body>
    <?php include 'panel_sidebar.php'; ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <h2 class="header-title text-center"><i class="fas fa-cogs mr-2"></i>Ajustes de Administrador</h2>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_pass)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?= $error_pass ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_pass)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> <?= $success_pass ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="settings-container">
                    <div class="row">
                        <div class="col-md-6">
                            <form method="POST" enctype="multipart/form-data">
                                <h5 class="section-title">Información del perfil</h5>

                                <div class="text-center mb-4">
                                    <img src="<?= !empty($admin['foto_perfil']) ? 'data:image/jpeg;base64,' . $admin['foto_perfil'] : 'https://ui-avatars.com/api/?name=' . urlencode($admin['nombre_completo']) . '&size=150&background=4e73df&color=fff&bold=true' ?>" class="perfil-img mb-3 shadow" alt="Foto de perfil">

                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="fotoPerfil" name="foto_perfil">
                                        <label class="custom-file-label" for="fotoPerfil">Seleccionar imagen</label>
                                    </div>

                                    <small class="form-text text-muted mt-2">Formatos: JPG, PNG. Tamaño máximo: 2MB. Recomendado: 150x150px</small>

                                    <div class="form-check mt-3">
                                        <input type="checkbox" class="form-check-input" id="removeFotoPerfil" name="remove_foto_perfil" value="1">
                                        <label class="form-check-label text-danger" for="removeFotoPerfil">
                                            <i class="fas fa-trash-alt mr-1"></i> Eliminar foto actual
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Nombre completo</label>
                                    <input type="text" name="nombre_completo" class="form-control" value="<?= htmlspecialchars($admin['nombre_completo'] ?? '') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Género</label>
                                    <select name="genero" class="form-control" required>
                                        <option value="Masculino" <?= ($admin['genero'] ?? '') == 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="Femenino" <?= ($admin['genero'] ?? '') == 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                                        <option value="Otro" <?= ($admin['genero'] ?? '') == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-primary btn-block mt-4">
                                    <i class="fas fa-save mr-2"></i> Guardar cambios
                                </button>
                            </form>
                        </div>

                        <div class="col-md-1"></div>

                        <div class="col-md-5">
                            <form method="POST">
                                <h5 class="section-title">Cambiar contraseña</h5>

                                <div class="form-group">
                                    <label class="font-weight-bold">Contraseña actual</label>
                                    <div class="input-group">
                                        <input type="password" name="password_actual" class="form-control" id="currentPassword" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text toggle-password" data-target="#currentPassword">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Nueva contraseña</label>
                                    <div class="input-group">
                                        <input type="password" name="password_nueva" class="form-control" id="newPassword" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text toggle-password" data-target="#newPassword">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Mínimo 8 caracteres</small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Confirmar nueva contraseña</label>
                                    <div class="input-group">
                                        <input type="password" name="password_confirmar" class="form-control" id="confirmPassword" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text toggle-password" data-target="#confirmPassword">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="change_password" class="btn btn-warning btn-block mt-3">
                                    <i class="fas fa-key mr-2"></i> Actualizar contraseña
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">

                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Mostrar/ocultar contraseña
        document.querySelectorAll('.toggle-password').forEach(item => {
            item.addEventListener('click', function() {
                const target = document.querySelector(this.getAttribute('data-target'));
                if (target.type === 'password') {
                    target.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    target.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        });

        // Actualizar nombre de archivo en input file
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            var fileName = document.getElementById("fotoPerfil").files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });

        // Animación para las alertas
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('fade');
                    setTimeout(() => {
                        alert.remove();
                    }, 1000);
                }, 5000);
            });
        });
    </script>
</body>

</html>