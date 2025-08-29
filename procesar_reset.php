<?php
require 'db.php';
require '../vendor/autoload.php'; // Ajusta la ruta si es necesario

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Solo POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Solo se permiten solicitudes POST']));
}

// Leer datos JSON
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Datos JSON inválidos']));
}

// --- FLUJO 1: Solicitud de restablecimiento (envío de correo) ---
if (isset($input['email'])) {
    $email = trim($input['email']);

    // Buscar usuario por email
    $stmt = $pdo->prepare("SELECT id, nombre_completo, email FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No existe una cuenta con ese correo.']);
        exit;
    }

    // Generar token y fecha de expiración
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Guardar token en la base de datos
    $update = $pdo->prepare("UPDATE usuarios SET token_reset = ?, token_expira = ? WHERE id = ?");
    $update->execute([$token, $expira, $user['id']]);

    // Enviar correo con PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fpantoja986@gmail.com'; // Cambia por tu correo
        $mail->Password   = 'mhbz abyv isat goyy';   // Usa variable de entorno en producción
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('fpantoja986@gmail.com', 'Mujeres en Tech');
        $mail->addAddress($user['email'], $user['nombre_completo']);

        // Enlace para restablecer contraseña
        $link = "http://localhost/ProgramaTesis/cambiar_password.php?token=$token";

        $mail->isHTML(true);
        $mail->Subject = 'Restablece tu contraseña';
        $mail->Body    = "
            <h2>Hola, {$user['nombre_completo']}!</h2>
            <p>Recibimos una solicitud para restablecer tu contraseña.</p>
            <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
            <p><a href='$link' style='color: #7c3aed;'>Restablecer contraseña</a></p>
            <p><small>Este enlace expira en 1 hora. Si no solicitaste este cambio, ignora este mensaje.</small></p>";

        $mail->AltBody = "Restablece tu contraseña aquí: $link";

        $mail->send();

        echo json_encode([
            'success' => true,
            'message' => 'Se enviaron las instrucciones al correo.'
        ]);
    } catch (Exception $emailException) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo enviar el correo: ' . $emailException->getMessage()
        ]);
    }
    exit;
}

// --- FLUJO 2: Restablecimiento de contraseña ---
if (empty($input['token']) || empty($input['new_password'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Token y nueva contraseña son requeridos']));
}

$token = $input['token'];
$newPassword = $input['new_password'];

// Validar longitud de contraseña
if (strlen($newPassword) < 8) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres']));
}

try {
    $pdo->beginTransaction();

    // Buscar usuario con token válido
    $stmt = $pdo->prepare("
        SELECT id, email 
        FROM usuarios 
        WHERE token_reset = ? 
        AND token_expira > NOW() 
        LIMIT 1 FOR UPDATE
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('El enlace de restablecimiento no es válido o ha expirado');
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $update = $pdo->prepare("
        UPDATE usuarios 
        SET 
            password = :password,
            token_reset = NULL,
            token_expira = NULL,
            require_reset_pass = 0,
            updated_at = NOW()
        WHERE id = :id
    ");

    $update->execute([
        ':password' => $hash,
        ':id' => $user['id']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error PDO en cambiar_password: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor al procesar la solicitud'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en cambiar_password: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}