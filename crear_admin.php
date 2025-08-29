<?php
require 'db.php'; // Asegúrate que aquí se conecte correctamente a la base de datos con $pdo

try {
    // Datos del administrador
    $nombre = 'Administrador';
    $email = 'administrador@gmail.com';
    $passwordPlano = 'administrador'; // Contraseña simple
    $passwordHash = password_hash($passwordPlano, PASSWORD_BCRYPT);
    $verificado = 1;
    $rol = 'administrador';

    // Verificar si ya existe
    $checkStmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $checkStmt->execute([$email]);
    $existe = $checkStmt->fetch();

    if ($existe) {
        echo "El administrador ya existe.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, email, password, verificado, rol) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $passwordHash, $verificado, $rol]);
        echo "✅ Administrador creado correctamente.";
    }
} catch (PDOException $e) {
    die("❌ Error al crear el administrador: " . $e->getMessage());
}
?>
