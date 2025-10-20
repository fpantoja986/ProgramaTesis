<?php
// Evitar salidas no deseadas (buffering)
ob_start();
header('Content-Type: application/json');
session_start();

// Configuración de errores (solo registrar, no mostrar en pantalla)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Incluir conexión a la base de datos y PHPMailer
include '../db.php';
require '../email_helper.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Solo se acepta POST.'
    ]);
    exit;
}

try {
    // 1. Validar datos del formulario
    $requiredFields = ['nombre_completo', 'email', 'rol', 'genero'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo '$field' es requerido.");
        }
    }

    // 2. Sanitizar datos
    $nombre     = trim($_POST['nombre_completo']);
    $email      = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $rol        = $_POST['rol'];
    $genero     = $_POST['genero'];
    $verificado = 0; // Todos los nuevos usuarios empiezan como no verificados
    // Antes de insertar, verificar si el email ya existe

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'El email ya está registrado.']);
        exit;
    }

    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("El email proporcionado no es válido.");
    }

    // 3. Contraseña temporal y token
    $passwordTemporal = bin2hex(random_bytes(4)); // Ej: "3a7f9b2c"
    $hash = password_hash($passwordTemporal, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(50));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 4. Insertar en la base de datos
    $sql = "INSERT INTO usuarios 
            (nombre_completo, email, password, rol, genero, verificado, token_reset, token_expira, require_reset_pass)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $email, $hash, $rol, $genero, $verificado, $token, $expira]);

    // 5. Enviar correo con contraseña temporal usando el helper
    $emailResult = EmailHelper::enviarPasswordTemporal($email, $nombre, $passwordTemporal, $token);
    
    if ($emailResult['success']) {
        // Respuesta de éxito completo
        echo json_encode([
            'success' => true,
            'message' => 'Usuario registrado y correo enviado correctamente.'
        ]);
    } else {
        // Usuario creado pero correo falló - registrar error para debugging
        error_log("Error al enviar correo desde panel admin: " . $emailResult['message']);
        if (isset($emailResult['error_details'])) {
            error_log("Detalles del error: " . $emailResult['error_details']);
        }
        
        // Respuesta parcial de éxito (usuario creado pero correo falló)
        echo json_encode([
            'success' => true, // Aún es éxito porque el usuario se creó
            'message' => 'Usuario registrado correctamente, pero hubo un problema al enviar el correo. El usuario puede usar la contraseña temporal: ' . $passwordTemporal,
            'warning' => 'Error de correo: ' . $emailResult['message']
        ]);
    }
} catch (PDOException $e) {
    // Error de base de datos
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Otros errores (validación, etc.)
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Limpiar buffer y asegurar que no haya salidas adicionales
ob_end_flush();
