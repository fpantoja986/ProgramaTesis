<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Headers básicos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Función para responder con JSON
function jsonResponse($success, $message, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Solo se permiten solicitudes POST', 405);
}

try {
    // Verificar archivos necesarios
    if (!file_exists('db.php')) {
        jsonResponse(false, 'Archivo db.php no encontrado', 500);
    }
    
    // Incluir base de datos
    require_once 'db.php';
    
    // Verificar e incluir PHPMailer
    if (file_exists('../vendor/autoload.php')) {
        require_once '../vendor/autoload.php';
    } elseif (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
    } else {
        jsonResponse(false, 'PHPMailer no está instalado', 500);
    }

    // Verificar conexión a base de datos
    if (!isset($pdo)) {
        jsonResponse(false, 'Error de conexión a la base de datos', 500);
    }

    // Leer input
    $input_raw = file_get_contents('php://input');
    
    if (empty($input_raw)) {
        jsonResponse(false, 'No se recibieron datos', 400);
    }

    $input = json_decode($input_raw, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(false, 'Datos JSON inválidos: ' . json_last_error_msg(), 400);
    }

    // Procesar solicitud de reset
    if (isset($input['email'])) {
        $email = trim($input['email']);

        // Validaciones
        if (empty($email)) {
            jsonResponse(false, 'El email es obligatorio', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'El formato del email no es válido', 400);
        }

        // Buscar usuario
        try {
            $stmt = $pdo->prepare("SELECT id, nombre_completo, email, verificado FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en consulta de usuario: " . $e->getMessage());
            jsonResponse(false, 'Error al buscar el usuario', 500);
        }

        if (!$user) {
            jsonResponse(false, 'No existe una cuenta con ese correo', 404);
        }

        if ($user['verificado'] == 0) {
            jsonResponse(false, 'Esta cuenta aún no ha sido verificada', 400);
        }

        // Generar token
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardar token
        try {
            $update = $pdo->prepare("UPDATE usuarios SET token_reset = ?, token_expira = ? WHERE id = ?");
            $result = $update->execute([$token, $expira, $user['id']]);
            
            if (!$result) {
                jsonResponse(false, 'Error al guardar el token', 500);
            }
        } catch (PDOException $e) {
            error_log("Error al guardar token: " . $e->getMessage());
            jsonResponse(false, 'Error al procesar la solicitud', 500);
        }

        // Usar PHPMailer con nombres completos de clase
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'fpantoja986@gmail.com';
            $mail->Password = 'mhbz abyv isat goyy';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 0;

            // Configuración del email
            $mail->setFrom('fpantoja986@gmail.com', 'Mujeres en Tech');
            $mail->addAddress($user['email'], $user['nombre_completo']);

            $link = "http://localhost/ProgramaTesis/cambiar_password.php?token=" . $token;

            $mail->isHTML(true);
            $mail->Subject = 'Restablecer Contraseña - Mujeres en Tech';
            
            $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='background: linear-gradient(135deg, #7c3aed, #ec4899); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h1>Restablecer Contraseña</h1>
                        <p>Mujeres en Tech</p>
                    </div>
                    <div style='background-color: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px;'>
                        <h2>Hola, {$user['nombre_completo']}</h2>
                        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
                        
                        <div style='text-align: center; margin: 20px 0;'>
                            <a href='$link' style='display: inline-block; background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                                Restablecer mi Contraseña
                            </a>
                        </div>
                        
                        <div style='background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;'>
                            <p><strong>Información importante:</strong></p>
                            <ul>
                                <li>Este enlace es válido por <strong>1 hora solamente</strong></li>
                                <li>Solo puedes usarlo una vez</li>
                                <li>Si no fuiste tú quien solicitó este restablecimiento, ignora este email</li>
                            </ul>
                        </div>
                        
                        <hr style='margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;'>
                        <p style='font-size: 12px; color: #6b7280;'>
                            Si el botón no funciona, copia y pega este enlace:<br>
                            <a href='$link' style='color: #7c3aed; word-break: break-all;'>$link</a>
                        </p>
                    </div>
                </div>
            </body>
            </html>";

            $mail->AltBody = "Hola {$user['nombre_completo']}, para restablecer tu contraseña visita: $link (válido por 1 hora)";

            $mail->send();

            jsonResponse(true, 'Te hemos enviado un enlace para restablecer tu contraseña. Revisa tu email (incluyendo spam).');

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Error PHPMailer: " . $e->getMessage());
            jsonResponse(false, 'No se pudo enviar el correo. Error: ' . $e->getMessage(), 500);
        }
    }

    // Procesar cambio de contraseña
    if (isset($input['token']) && isset($input['new_password'])) {
        $token = $input['token'];
        $newPassword = $input['new_password'];

        if (strlen($newPassword) < 8) {
            jsonResponse(false, 'La contraseña debe tener al menos 8 caracteres', 400);
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id, email, verificado FROM usuarios WHERE token_reset = ? AND token_expira > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $pdo->rollBack();
                jsonResponse(false, 'El enlace no es válido o ha expirado', 400);
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            if ($user['verificado'] == 0) {
                $update = $pdo->prepare("UPDATE usuarios SET password = ?, token_reset = NULL, token_expira = NULL, verificado = 1 WHERE id = ?");
                $mensaje = 'Tu cuenta ha sido verificada y tu contraseña establecida correctamente.';
            } else {
                $update = $pdo->prepare("UPDATE usuarios SET password = ?, token_reset = NULL, token_expira = NULL WHERE id = ?");
                $mensaje = 'Tu contraseña ha sido actualizada correctamente.';
            }

            $result = $update->execute([$hash, $user['id']]);

            if (!$result) {
                $pdo->rollBack();
                jsonResponse(false, 'Error al actualizar la contraseña', 500);
            }

            $pdo->commit();
            jsonResponse(true, $mensaje);

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error PDO: " . $e->getMessage());
            jsonResponse(false, 'Error de base de datos', 500);
        }
    }

    jsonResponse(false, 'Solicitud inválida', 400);

} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    jsonResponse(false, 'Error del servidor: ' . $e->getMessage(), 500);
}