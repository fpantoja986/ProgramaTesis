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
    
</head>

<body>
    <?php include 'panel_sidebar.php'; ?>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <h2 class="header-title text-center animate-fadeIn"><i class="fas fa-user-cog mr-2"></i>Configuración de Perfil</h2>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show animate-fadeIn" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_pass)): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate-fadeIn" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?= $error_pass ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_pass)): ?>
                    <div class="alert alert-success alert-dismissible fade show animate-fadeIn" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> <?= $success_pass ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="settings-container animate-fadeIn">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-style p-4 h-100">
                                <form method="POST" enctype="multipart/form-data">
                                    <h5 class="section-title"><i class="fas fa-user-circle mr-2"></i>Información Personal</h5>

                                    <div class="text-center mb-4">
                                        <div class="position-relative d-inline-block">
                                            <img src="<?= !empty($admin['foto_perfil']) ? 'data:image/jpeg;base64,' . $admin['foto_perfil'] : 'https://ui-avatars.com/api/?name=' . urlencode($admin['nombre_completo']) . '&size=150&background=4e73df&color=fff&bold=true' ?>" 
                                                 class="perfil-img mb-3" 
                                                 alt="Foto de perfil"
                                                 id="profileImagePreview">
                                            
                                            <div class="profile-actions">
                                                <label for="fotoPerfil" class="profile-action-btn" title="Cambiar foto">
                                                    <i class="fas fa-camera"></i>
                                                </label>
                                                <button type="button" class="profile-action-btn" onclick="document.getElementById('removeFotoPerfil').click()" title="Eliminar foto">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <input type="file" class="custom-file-input d-none" id="fotoPerfil" name="foto_perfil" accept="image/*">
                                        
                                        <div class="form-check mt-3">
                                            <input type="checkbox" class="form-check-input d-none" id="removeFotoPerfil" name="remove_foto_perfil" value="1">
                                        </div>

                                        <small class="form-text text-muted">Formatos: JPG, PNG. Tamaño máximo: 2MB</small>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark">Nombre completo</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="fas fa-user text-primary"></i></span>
                                            </div>
                                            <input type="text" name="nombre_completo" class="form-control" value="<?= htmlspecialchars($admin['nombre_completo'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark">Email</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="fas fa-envelope text-primary"></i></span>
                                            </div>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark">Género</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="fas fa-venus-mars text-primary"></i></span>
                                            </div>
                                            <select name="genero" class="form-control" required>
                                                <option value="Masculino" <?= ($admin['genero'] ?? '') == 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                                <option value="Femenino" <?= ($admin['genero'] ?? '') == 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                                                <option value="Otro" <?= ($admin['genero'] ?? '') == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button type="submit" name="update_profile" class="btn btn-primary btn-block mt-4 py-3">
                                        <i class="fas fa-save mr-2"></i> Guardar Cambios
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-md-6 mt-4 mt-md-0">
                            <div class="card-style p-4 h-100">
                                <form method="POST">
                                    <h5 class="section-title"><i class="fas fa-lock mr-2"></i>Seguridad</h5>

                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark">Contraseña actual</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="fas fa-key text-primary"></i></span>
                                            </div>
                                            <input type="password" name="password_actual" class="form-control" id="currentPassword" required placeholder="Ingresa tu contraseña actual">
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="#currentPassword">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark">Nueva contraseña</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                                            </div>
                                            <input type="password" name="password_nueva" class="form-control" id="newPassword" required placeholder="Mínimo 8 caracteres">
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="#newPassword">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark">Confirmar nueva contraseña</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                                            </div>
                                            <input type="password" name="password_confirmar" class="form-control" id="confirmPassword" required placeholder="Repite tu nueva contraseña">
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="#confirmPassword">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" name="change_password" class="btn btn-warning btn-block mt-4 py-3">
                                        <i class="fas fa-key mr-2"></i> Actualizar Contraseña
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
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

        // Vista previa de imagen
        document.getElementById('fotoPerfil').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImagePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
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

        // Efecto de carga para imagen
        const profileImg = document.getElementById('profileImagePreview');
        if (profileImg) {
            profileImg.onload = function() {
                this.classList.remove('img-loading');
            };
            if (!profileImg.complete) {
                profileImg.classList.add('img-loading');
            }
        }
    </script>
</body>

</html>