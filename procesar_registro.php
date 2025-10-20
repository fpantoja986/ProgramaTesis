<?php
require 'db.php'; // Asegúrate de que este archivo contenga la conexión a la base de datos
require 'email_helper.php';

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

        // Enviar correo de verificación usando el helper
        $emailResult = EmailHelper::enviarVerificacion($email, $nombre, $token);
        
        if ($emailResult['success']) {
            // Redirigir a la página de registro exitoso
            header("Location: registro_exitoso.html");
            exit();
        } else {
            // Usuario creado pero correo falló
            error_log("Error al enviar correo de verificación: " . $emailResult['message']);
            header("Location: registro_exitoso.html?email_error=1");
            exit();
        }
        
    } catch (PDOException $e) {
        die("Error al registrar: " . $e->getMessage());
    }
}
?>
