<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../login.php");
    exit();
}

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$success = '';
$error_pass = '';
$success_pass = '';
$error_profile = '';

// Procesar actualización de datos personales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nombre = trim($_POST['nombre_completo']);
    $email = trim($_POST['email']);
    $genero = trim($_POST['genero']);
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $biografia = trim($_POST['biografia'] ?? '');

    // Validaciones
    if (empty($nombre) || empty($email) || empty($genero)) {
        $error_profile = "Los campos nombre, email y género son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_profile = "El formato del email no es válido.";
    } else {
        // Verificar si el email ya existe en otro usuario
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error_profile = "Este email ya está en uso por otro usuario.";
        } else {
            // Procesar eliminación de foto de perfil
            if (isset($_POST['remove_foto_perfil']) && $_POST['remove_foto_perfil'] === '1') {
                if (!empty($usuario['foto_perfil'])) {
                    $foto_perfil = null;
                }
            } else {
                if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $foto_data = file_get_contents($_FILES['foto_perfil']['tmp_name']);
                    $foto_perfil = base64_encode($foto_data);
                } else {
                    $foto_perfil = $usuario['foto_perfil'] ?? null;
                }
            }

            $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo=?, email=?, genero=?, telefono=?, fecha_nacimiento=?, biografia=?, foto_perfil=? WHERE id=?");
            $stmt->execute([$nombre, $email, $genero, $telefono, $fecha_nacimiento, $biografia, $foto_perfil, $user_id]);
            
            // Actualizar datos en sesión
            $_SESSION['nombre_completo'] = $nombre;
            $_SESSION['email'] = $email;
            
            $usuario['nombre_completo'] = $nombre;
            $usuario['email'] = $email;
            $usuario['genero'] = $genero;
            $usuario['telefono'] = $telefono;
            $usuario['fecha_nacimiento'] = $fecha_nacimiento;
            $usuario['biografia'] = $biografia;
            $usuario['foto_perfil'] = $foto_perfil;
            $success = "Datos actualizados correctamente.";
        }
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $actual = $_POST['password_actual'];
    $nueva = $_POST['password_nueva'];
    $confirmar = $_POST['password_confirmar'];

    if (!password_verify($actual, $usuario['password'])) {
        $error_pass = "La contraseña actual es incorrecta.";
    } elseif ($nueva !== $confirmar) {
        $error_pass = "Las contraseñas nuevas no coinciden.";
    } elseif (strlen($nueva) < 8) {
        $error_pass = "La nueva contraseña debe tener al menos 8 caracteres.";
    } else {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password=? WHERE id=?");
        $stmt->execute([$hash, $user_id]);
        $success_pass = "Contraseña actualizada correctamente.";
    }
}

// Obtener estadísticas del usuario
$stmt = $pdo->prepare("SELECT COUNT(*) FROM temas_foro WHERE id_usuario = ?");
$stmt->execute([$user_id]);
$temas_creados = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM respuestas_foro WHERE id_usuario = ?");
$stmt->execute([$user_id]);
$respuestas = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT fecha_registro FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$fecha_registro = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ajustes de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .settings-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        .form-group label {
            font-weight: 600;
            color: #495057;
        }
        .btn-save {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-danger-custom {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            border-radius: 25px;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-danger-custom:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            margin: 0 auto 1rem;
        }
        .stats-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #495057;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <?php include 'user_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="p-4">
                    <!-- Header del Perfil -->
                    <div class="profile-header text-center">
                        <img src="<?= !empty($usuario['foto_perfil']) ? 'data:image/jpeg;base64,' . $usuario['foto_perfil'] : 'https://ui-avatars.com/api/?name=' . urlencode($usuario['nombre_completo']) ?>" 
                             class="profile-avatar mb-3" alt="Foto de perfil">
                        <h1 class="mb-2"><?= htmlspecialchars($usuario['nombre_completo']) ?></h1>
                        <p class="mb-0 opacity-75">
                            <i class="fas fa-envelope mr-2"></i>
                            <?= htmlspecialchars($usuario['email']) ?>
                        </p>
                    </div>

                    <!-- Estadísticas del Usuario -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="stats-number"><?= $temas_creados ?></div>
                                <div class="stats-label">Temas Creados</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #28a745, #20c997);">
                                    <i class="fas fa-reply"></i>
                                </div>
                                <div class="stats-number"><?= $respuestas ?></div>
                                <div class="stats-label">Respuestas</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="stats-number"><?= date('d M', strtotime($fecha_registro)) ?></div>
                                <div class="stats-label">Miembro desde</div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Datos Personales -->
                    <div class="settings-card">
                        <h4 class="section-title">
                            <i class="fas fa-user-edit mr-2"></i>
                            Información Personal
                        </h4>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle mr-2"></i>
                                <?= $success ?>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_profile)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <?= $error_profile ?>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre_completo">
                                            <i class="fas fa-user mr-1"></i>
                                            Nombre completo
                                        </label>
                                        <input type="text" id="nombre_completo" name="nombre_completo" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">
                                            <i class="fas fa-envelope mr-1"></i>
                                            Email
                                        </label>
                                        <input type="email" id="email" name="email" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($usuario['email']) ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="genero">
                                            <i class="fas fa-venus-mars mr-1"></i>
                                            Género
                                        </label>
                                        <select id="genero" name="genero" class="form-control" required>
                                            <option value="Masculino" <?= $usuario['genero'] == 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                            <option value="Femenino" <?= $usuario['genero'] == 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                                            <option value="Otro" <?= $usuario['genero'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="telefono">
                                            <i class="fas fa-phone mr-1"></i>
                                            Teléfono
                                        </label>
                                        <input type="tel" id="telefono" name="telefono" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fecha_nacimiento">
                                            <i class="fas fa-birthday-cake mr-1"></i>
                                            Fecha de nacimiento
                                        </label>
                                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" 
                                               class="form-control" 
                                               value="<?= $usuario['fecha_nacimiento'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="biografia">
                                    <i class="fas fa-quote-left mr-1"></i>
                                    Biografía
                                </label>
                                <textarea id="biografia" name="biografia" class="form-control" rows="4" 
                                          placeholder="Cuéntanos algo sobre ti..."><?= htmlspecialchars($usuario['biografia'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="foto_perfil">
                                    <i class="fas fa-camera mr-1"></i>
                                    Foto de perfil
                                </label>
                                <input type="file" id="foto_perfil" name="foto_perfil" 
                                       class="form-control-file" 
                                       accept="image/*">
                                <small class="form-text text-muted">
                                    Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                                </small>
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="removeFotoPerfil" 
                                       name="remove_foto_perfil" value="1">
                                <label class="form-check-label" for="removeFotoPerfil">
                                    <i class="fas fa-trash mr-1"></i>
                                    Quitar foto de perfil actual
                                </label>
                            </div>

                            <div class="text-right">
                                <button type="submit" name="update_profile" class="btn btn-save">
                                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Cambio de Contraseña -->
                    <div class="settings-card">
                        <h4 class="section-title">
                            <i class="fas fa-lock mr-2"></i>
                            Cambiar Contraseña
                        </h4>
                        
                        <?php if (!empty($error_pass)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <?= $error_pass ?>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_pass)): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle mr-2"></i>
                                <?= $success_pass ?>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="password_actual">
                                            <i class="fas fa-key mr-1"></i>
                                            Contraseña actual
                                        </label>
                                        <input type="password" id="password_actual" name="password_actual" 
                                               class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="password_nueva">
                                            <i class="fas fa-lock mr-1"></i>
                                            Nueva contraseña
                                        </label>
                                        <input type="password" id="password_nueva" name="password_nueva" 
                                               class="form-control" required minlength="8">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="password_confirmar">
                                            <i class="fas fa-lock mr-1"></i>
                                            Confirmar contraseña
                                        </label>
                                        <input type="password" id="password_confirmar" name="password_confirmar" 
                                               class="form-control" required minlength="8">
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <button type="submit" name="change_password" class="btn btn-danger-custom">
                                    <i class="fas fa-key mr-2"></i>Actualizar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de contraseñas
        document.getElementById('password_confirmar').addEventListener('input', function() {
            const password = document.getElementById('password_nueva').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
