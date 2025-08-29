<?php
session_start();
require 'db.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?error=Método no permitido');
    exit;
}

// Sanitizar y validar entradas
$email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
$password = trim($_POST['password']);

if (!$email || empty($password)) {
    header('Location: login.php?error=Credenciales inválidas');
    exit;
}

try {
    // Consulta segura con todos los campos necesarios
    $stmt = $pdo->prepare("
        SELECT id, email, password, nombre_completo, rol, verificado 
        FROM usuarios 
        WHERE email = ? 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verificación de credenciales
    if (!$user || !password_verify($password, $user['password'])) {
        header('Location: login.php?error=Credenciales incorrectas');
        exit;
    }

    // Verificación de cuenta
    if (!$user['verificado']) {
        header('Location: login.php?error=Cuenta no verificada. Por favor cambia tu contraseña desde el enlace enviado a tu correo');
        exit;
    }

    // Configurar sesión segura
    $_SESSION = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'nombre' => $user['nombre_completo'],
        'rol' => $user['rol'],
        'logged_in' => true,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'last_login' => time()
    ];

    // Regenerar ID de sesión para prevenir fixation
    session_regenerate_id(true);

    // Redirección según rol
    $redirect = match($user['rol']) {
        'administrador' => 'panel_admin/admin_dashboard.php',
        'usuario' => 'user_dashboard.php',
        default => 'login.php?error=Rol no válido'
    };

    header("Location: $redirect");
    exit;

} catch (PDOException $e) {
    error_log("Error de login para $email: " . $e->getMessage());
    header('Location: login.php?error=Error en el sistema. Intente más tarde');
    exit;
}