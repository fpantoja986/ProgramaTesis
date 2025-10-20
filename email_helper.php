<?php
/**
 * Helper para envío de correos electrónicos
 */

require_once 'email_config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    
    /**
     * Envía un correo de verificación de cuenta
     */
    public static function enviarVerificacion($email, $nombre, $token) {
        try {
            $mail = new PHPMailer(true);
            
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            // Debugging deshabilitado para producción
            $mail->SMTPDebug = 0;
            
            // Configuración del correo
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($email, $nombre);
            
            $mail->isHTML(true);
            $mail->Subject = 'Verificación de correo - Mujeres en Tech';
            
            $verificationLink = BASE_URL . "/verificar.php?token=" . $token;
            
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #7c3aed;'>¡Hola " . htmlspecialchars($nombre) . "!</h2>
                    <p>Gracias por registrarte en <strong>Mujeres en Tech</strong>.</p>
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
            
            $mail->AltBody = "Hola $nombre,\n\nGracias por registrarte en Mujeres en Tech.\n\nPara verificar tu cuenta, visita: $verificationLink\n\nSi no te registraste, ignora este mensaje.";
            
            $mail->send();
            return ['success' => true, 'message' => 'Correo enviado correctamente'];
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Error al enviar correo: ' . $e->getMessage(),
                'error_details' => $mail->ErrorInfo ?? 'No hay información adicional'
            ];
        }
    }
    
    /**
     * Envía un correo con contraseña temporal
     */
    public static function enviarPasswordTemporal($email, $nombre, $passwordTemporal, $token) {
        try {
            $mail = new PHPMailer(true);
            
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            // Debugging deshabilitado para producción
            $mail->SMTPDebug = 0;
            
            // Configuración del correo
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($email, $nombre);
            
            $mail->isHTML(true);
            $mail->Subject = 'Tu cuenta ha sido creada - Cambia tu contraseña';
            
            $resetLink = BASE_URL . "/cambiar_password.php?token=" . $token;
            
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #7c3aed;'>¡Bienvenido/a, " . htmlspecialchars($nombre) . "!</h2>
                    <p>Tu cuenta ha sido creada exitosamente en <strong>Mujeres en Tech</strong>.</p>
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
            
            $mail->AltBody = "¡Bienvenido/a, $nombre!\n\nTu cuenta ha sido creada.\nContraseña temporal: $passwordTemporal\n\nCambia tu contraseña aquí: $resetLink\n\nEste enlace expira en 1 hora.";
            
            $mail->send();
            return ['success' => true, 'message' => 'Correo con contraseña temporal enviado correctamente'];
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Error al enviar correo: ' . $e->getMessage(),
                'error_details' => $mail->ErrorInfo ?? 'No hay información adicional'
            ];
        }
    }
}
?>
