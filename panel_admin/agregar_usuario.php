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
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    // 5. Enviar correo con PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fpantoja986@gmail.com'; // Cambiar si es necesario
        $mail->Password   = 'mhbz abyv isat goyy';   // Usar variables de entorno en producción
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('fpantoja986@gmail.com', 'Mujeres en Tech');
        $mail->addAddress($email, $nombre);

        // Enlace para cambiar contraseña
        $link = "http://localhost/ProgramaTesis/cambiar_password.php?token=$token";

        $mail->isHTML(true);
        $mail->Subject = 'Tu cuenta ha sido creada - Cambia tu contrasena';
        $mail->Body    = "
            <h2>¡Bienvenido/a, $nombre!</h2>
            <p>Tu cuenta ha sido creada con una contraseña temporal:</p>
            <p><strong>$passwordTemporal</strong></p>
            <p>Por seguridad, debes cambiarla haciendo clic en el siguiente enlace:</p>
            <p><a href='$link' style='color: #007bff;'>Cambiar contraseña</a></p>
            <p><small>Este enlace expira en 1 hora.</small></p>";

        $mail->AltBody = "Contraseña temporal: $passwordTemporal. Cambia tu contraseña aquí: $link";

        $mail->send();

        // Respuesta de éxito
        echo json_encode([
            'success' => true,
            'message' => 'Usuario registrado y correo enviado correctamente.'
        ]);
    } catch (Exception $emailException) {
        // Si falla el correo pero el usuario se registró
        echo json_encode([
            'success' => true, // Aún es éxito porque el usuario se creó
            'message' => 'Usuario registrado, pero el correo no pudo enviarse: ' . $emailException->getMessage()
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
