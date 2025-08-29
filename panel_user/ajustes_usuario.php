<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../login.php");
    exit();
}

include '../panel_admin/panel_sidebar.php';
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

// Procesar actualización de datos personales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nombre = trim($_POST['nombre_completo']);
    $email = trim($_POST['email']);
    $genero = trim($_POST['genero']);

    // Procesar eliminación de foto de perfil
    if (isset($_POST['remove_foto_perfil']) && $_POST['remove_foto_perfil'] === '1') {
        if (!empty($usuario['foto_perfil'])) {
            $ruta_foto = '../uploads/perfiles/' . $usuario['foto_perfil'];
            if (file_exists($ruta_foto)) {
                unlink($ruta_foto);
            }
        }
        $foto_perfil = null;
    } else {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $foto_data = file_get_contents($_FILES['foto_perfil']['tmp_name']);
            $foto_perfil = base64_encode($foto_data);
        } else {
            $foto_perfil = $usuario['foto_perfil'] ?? null;
        }
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo=?, email=?, genero=?, foto_perfil=? WHERE id=?");
    $stmt->execute([$nombre, $email, $genero, $foto_perfil, $user_id]);
    $usuario['nombre_completo'] = $nombre;
    $usuario['email'] = $email;
    $usuario['genero'] = $genero;
    $usuario['foto_perfil'] = $foto_perfil;
    $success = "Datos actualizados correctamente.";
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ajustes de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            min-height: 100vh;
            padding-top: 1rem;
        }
        .content {
            padding: 2rem;
            margin-left: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .content > * {
            width: 100%;
            max-width: 900px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row no-gutters">
            <?php include '../panel_admin/panel_sidebar.php'; ?>
            <main class="col-md-9 content bg-white rounded shadow-sm p-4">
                <h2 class="mb-4 text-primary">Ajustes de Usuario</h2>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if (!empty($error_pass)): ?>
                    <div class="alert alert-danger"><?= $error_pass ?></div>
                <?php endif; ?>
                <?php if (!empty($success_pass)): ?>
                    <div class="alert alert-success"><?= $success_pass ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group text-center">
                        <img src="<?= !empty($usuario['foto_perfil']) ? 'data:image/jpeg;base64,' . $usuario['foto_perfil'] : 'https://ui-avatars.com/api/?name=' . urlencode($usuario['nombre_completo']) ?>" class="perfil-img mb-2 rounded-circle" alt="Foto de perfil" style="width:120px; height:120px; object-fit: cover;">
                    </div>
                    <div class="form-group">
                        <label>Nombre completo</label>
                        <input type="text" name="nombre_completo" class="form-control" value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Género</label>
                        <select name="genero" class="form-control" required>
                            <option value="Masculino" <?= $usuario['genero'] == 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Femenino" <?= $usuario['genero'] == 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                            <option value="Otro" <?= $usuario['genero'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="removeFotoPerfil" name="remove_foto_perfil" value="1">
                        <label class="form-check-label" for="removeFotoPerfil">Quitar foto de perfil actual</label>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary btn-block">Guardar cambios</button>
                </form>
                <hr />
                <form method="POST">
                    <h5 class="mb-3">Cambiar contraseña</h5>
                    <div class="form-group">
                        <label>Contraseña actual</label>
                        <input type="password" name="password_actual" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nueva contraseña</label>
                        <input type="password" name="password_nueva" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmar nueva contraseña</label>
                        <input type="password" name="password_confirmar" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning btn-block">Actualizar contraseña</button>
                </form>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
