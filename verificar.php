<?php
require 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Buscar al usuario con el token
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_verificacion = ? AND verificado = 0");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Marcar como verificado
        $update = $pdo->prepare("UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE id = ?");
        $update->execute([$usuario['id']]);

        $mensaje = "✅ Tu cuenta ha sido verificada exitosamente. Ya puedes iniciar sesión.";
    } else {
        $mensaje = "❌ Token inválido o ya ha sido verificado.";
    }
} else {
    $mensaje = "❌ No se proporcionó ningún token.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Cuenta</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-purple-50 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-md rounded-lg p-8 text-center max-w-md w-full">
        <h1 class="text-xl font-bold text-purple-700 mb-4">Verificación de cuenta</h1>
        <p class="text-gray-700"><?php echo $mensaje; ?></p>

        <div class="mt-6">
            <a href="login.php" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">Ir al login</a>
        </div>
    </div>
</body>
</html>
