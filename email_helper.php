<?php
require_once __DIR__ . '/email_config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    
    public static function enviarVerificacion($email, $nombre, $token) {
        try {
            $mail = new PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            $mail->SMTPDebug = EMAIL_DEBUG ? 2 : 0;
            $mail->CharSet = EMAIL_CHARSET;
            $mail->Timeout = 15;
            $mail->SMTPAutoTLS = true;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug: $str");
            };
            
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($email, $nombre);
            
            $mail->Subject = 'Verificación de correo - Tech Community';
            if (EMAIL_FORCE_PLAIN) {
                $mail->isHTML(false);
            } else {
                $mail->isHTML(true);
            }
            
            $verificationLink = BASE_URL . "/verificar.php?token=" . $token;
            
            if (!EMAIL_FORCE_PLAIN) {
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #7c3aed;'>¡Hola " . htmlspecialchars($nombre) . "!</h2>
                    <p>Gracias por registrarte en <strong>Tech Community</strong>.</p>
                    <p>Para completar tu registro, por favor verifica tu correo electrónico haciendo clic en el siguiente enlace:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$verificationLink' 
                           style='background-color: #7c3aed; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Verificar mi cuenta
                        </a>
                    </div>
                    <p><small>Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:</small></p>
                    <p><small style='color: #666;'>$verificationLink</small></p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                    <p><small>Si no te registraste en nuestro sitio, puedes ignorar este mensaje.</small></p>
                </div>
            ";
            $mail->AltBody = "Hola $nombre,\n\nGracias por registrarte en Tech Community.\n\nPara verificar tu cuenta, visita: $verificationLink\n\nSi no te registraste, ignora este mensaje.";
            } else {
                $mail->Body = "Hola $nombre\n\nPara verificar tu cuenta, copia y pega este enlace en tu navegador:\n$verificationLink\n\nSi no te registraste, ignora este mensaje.";
            }
            
            $mail->send();
            return ['success' => true, 'message' => 'Correo enviado correctamente'];
            
        } catch (Exception $e) {
            $err = trim(($e->getMessage() ?? '') . ' ' . ($mail->ErrorInfo ?? ''));
            if (stripos($err, 'Daily user sending limit exceeded') !== false || stripos($err, '5.4.5') !== false || stripos($err, 'reached a limit') !== false) {
                return [
                    'success' => false,
                    'message' => 'Has alcanzado el límite diario de envíos de Gmail. Intenta nuevamente en ~24 horas.',
                    'error_details' => $err
                ];
            }
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port = SMTP_PORT;
                $mail->SMTPDebug = EMAIL_DEBUG ? 2 : 0;
                $mail->CharSet = EMAIL_CHARSET;
                $mail->setFrom(FROM_EMAIL, FROM_NAME);
                $mail->addAddress($email, $nombre);
                $mail->isHTML(false);
                $verificationLink = BASE_URL . "/verificar.php?token=" . $token;
                $mail->Subject = 'Verificación de correo';
                $mail->Body = "Hola $nombre\n\nPara verificar tu cuenta, copia y pega este enlace en tu navegador:\n$verificationLink\n\nSi no te registraste, ignora este mensaje.";
                $mail->send();
                return ['success' => true, 'message' => 'Correo enviado en modo seguro'];
            } catch (Exception $e2) {
                return [
                    'success' => false,
                    'message' => 'Error al enviar correo: ' . $e->getMessage(),
                    'error_details' => ($mail->ErrorInfo ?? '') . ' | Fallback: ' . ($e2->getMessage())
                ];
            }
        }
    }
    
    public static function enviarPasswordTemporal($email, $nombre, $passwordTemporal, $token) {
        try {
            $mail = new PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            $mail->SMTPDebug = EMAIL_DEBUG ? 2 : 0;
            $mail->CharSet = EMAIL_CHARSET;
            $mail->Timeout = 15;
            $mail->SMTPAutoTLS = true;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug: $str");
            };
            
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($email, $nombre);
            
            $mail->Subject = 'Tu cuenta ha sido creada - Cambia tu contraseña';
            if (EMAIL_FORCE_PLAIN) {
                $mail->isHTML(false);
            } else {
                $mail->isHTML(true);
            }
            
            $resetLink = BASE_URL . "/cambiar_password.php?token=" . $token;
            
            if (!EMAIL_FORCE_PLAIN) {
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #7c3aed;'>¡Bienvenido/a, " . htmlspecialchars($nombre) . "!</h2>
                    <p>Tu cuenta ha sido creada exitosamente en <strong>Tech Community</strong>.</p>
                    <p>Se ha generado una contraseña temporal para tu cuenta:</p>
                    <div style='background-color: #f3f4f6; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                        <strong style='font-size: 18px; color: #7c3aed;'>$passwordTemporal</strong>
                    </div>
                    <p>Por seguridad, <strong>debes cambiar esta contraseña</strong> haciendo clic en el siguiente enlace:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$resetLink' 
                           style='background-color: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Cambiar contraseña
                        </a>
                    </div>
                    <p><small style='color: #dc2626;'>⚠️ Este enlace expira en 1 hora por seguridad.</small></p>
                    <p><small>Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:</small></p>
                    <p><small style='color: #666;'>$resetLink</small></p>
                </div>
            ";
            $mail->AltBody = "¡Bienvenido/a, $nombre!\n\nTu cuenta ha sido creada en Tech Community.\nContraseña temporal: $passwordTemporal\n\nCambia tu contraseña aquí: $resetLink\n\nEste enlace expira en 1 hora.";
            } else {
                $mail->Body = "Hola $nombre\n\nTu contraseña temporal: $passwordTemporal\nPara cambiarla, copia y pega este enlace en tu navegador:\n$resetLink\n\nEste enlace expira en 1 hora.";
            }
            
            $mail->send();
            return ['success' => true, 'message' => 'Correo con contraseña temporal enviado correctamente'];
            
        } catch (Exception $e) {
            $err = trim(($e->getMessage() ?? '') . ' ' . ($mail->ErrorInfo ?? ''));
            if (stripos($err, 'Daily user sending limit exceeded') !== false || stripos($err, '5.4.5') !== false || stripos($err, 'reached a limit') !== false) {
                return [
                    'success' => false,
                    'message' => 'Has alcanzado el límite diario de envíos de Gmail. Intenta nuevamente en ~24 horas.',
                    'error_details' => $err
                ];
            }
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port = SMTP_PORT;
                $mail->SMTPDebug = EMAIL_DEBUG ? 2 : 0;
                $mail->CharSet = EMAIL_CHARSET;
                $mail->setFrom(FROM_EMAIL, FROM_NAME);
                $mail->addAddress($email, $nombre);
                $mail->isHTML(false);
                $resetLink = BASE_URL . "/cambiar_password.php?token=" . $token;
                $mail->Subject = 'Acceso y cambio de contraseña';
                $mail->Body = "Hola $nombre\n\nTu contraseña temporal: $passwordTemporal\nPara cambiarla, copia y pega este enlace en tu navegador:\n$resetLink\n\nEste enlace expira en 1 hora.";
                $mail->send();
                return ['success' => true, 'message' => 'Correo enviado en modo seguro'];
            } catch (Exception $e2) {
                return [
                    'success' => false,
                    'message' => 'Error al enviar correo: ' . $e->getMessage(),
                    'error_details' => ($mail->ErrorInfo ?? '') . ' | Fallback: ' . ($e2->getMessage())
                ];
            }
        }
    }
}
?>
