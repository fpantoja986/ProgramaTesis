<?php
require 'db.php'; // Asegúrate de que este archivo contenga la conexión a la base de datos
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre_completo'];
    $email = $_POST['email'];
    $genero = $_POST['genero'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(50)); // Token de verificación único
    $rol = 'usuario'; // Todos los registros por el formulario serán usuarios


    try {
        // Insertar usuario y token en la base de datos
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, email, password, token_verificacion, verificado, genero, rol) VALUES (?, ?, ?, ?, 0, ?, ?)");
        $stmt->execute([$nombre, $email, $password, $token, $genero, $rol]);

        // Configurar PHPMailer para Gmail
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'fpantoja986@gmail.com'; // Tu correo
        $mail->Password = 'mhbz abyv isat goyy'; // Clave de aplicación de Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Configuración del correo
        $mail->setFrom('fpantoja986@gmail.com', 'Mujeres en Tech');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Verificacion de correo';
        $mail->Body    = 'Hola ' . htmlspecialchars($nombre) . ',<br><br>Gracias por registrarte en Mujeres en Tech. Por favor verifica tu correo haciendo clic en el siguiente enlace:<br><br><a href="http://localhost/ProgramaTesis/verificar.php?token=' . $token . '">Verificar correo</a><br><br>Si no te registraste, ignora este mensaje.';
        $mail->AltBody = 'Por favor verifica tu correo en: http://localhost/ProgramaTesis/verificar.php?token=' . $token;

        $mail->send();

        // Redirigir a la página de registro exitoso
        header("Location: registro_exitoso.html");
        exit();
    } catch (Exception $e) {
        die("El correo no pudo ser enviado. Error: {$mail->ErrorInfo}");
    } catch (PDOException $e) {
        die("Error al registrar: " . $e->getMessage());
    }
}
?>
