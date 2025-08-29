<?php
require 'db.php';

// Obtener el token de la URL solo si no es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $nuevaPassword = $_POST['nueva_password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';

    if (empty($token) || empty($nuevaPassword)) {
        $mensaje = "❌ Token y nueva contraseña son requeridos.";
    } elseif ($nuevaPassword !== $confirmarPassword) {
        $mensaje = "❌ Las contraseñas no coinciden.";
    } elseif (strlen($nuevaPassword) < 8) {
        $mensaje = "❌ La contraseña debe tener al menos 8 caracteres.";
    } else {
        // Verificar el token otra vez
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_reset = ? AND token_expira > NOW()");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $hashedPassword = password_hash($nuevaPassword, PASSWORD_BCRYPT);

            // Actualizar contraseña y eliminar el token
            $update = $pdo->prepare("UPDATE usuarios SET password = ?, token_reset = NULL, token_expira = NULL WHERE id = ?");
            $update->execute([$hashedPassword, $usuario['id']]);

            $mensaje = "✅ Tu contraseña ha sido restablecida correctamente. Ya puedes iniciar sesión.";
        } else {
            $mensaje = "❌ Token inválido o expirado.";
        }
    }
} else {
    $token = $_GET['token'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-xl font-bold text-purple-700 text-center mb-4">Restablecer Contraseña</h1>

        <?php if (isset($mensaje)): ?>
            <p class="mb-4 text-center text-sm <?php echo strpos($mensaje, '✅') !== false ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo $mensaje; ?>
            </p>
        <?php endif; ?>

        <?php if ((!isset($mensaje) || strpos($mensaje, '✅') === false) && !empty($token)): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Nueva Contraseña</label>
                    <input type="password" name="nueva_password" id="passwordInput" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div id="passwordStrength" class="h-2 rounded-full" style="width: 0%"></div>
                    </div>
                    <p id="passwordStrengthText" class="mt-1 text-xs text-gray-500">Debe contener 8+ caracteres, incluyendo números, minúsculas y mayúsculas</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Confirmar Contraseña</label>
                    <input type="password" name="confirmar_password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition">
                    Restablecer Contraseña
                </button>
            </form>
        <?php elseif (empty($token)): ?>
            <p class="mb-4 text-center text-red-600 text-sm">❌ Token no proporcionado o inválido.</p>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <a href="login.php" class="text-purple-600 hover:underline">Volver al inicio de sesión</a>
        </div>
    </div>
    <script>
        document.getElementById('passwordInput').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');
            let strength = 0;

            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password)) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;

            strengthBar.style.width = strength + '%';

            if (strength < 50) {
                strengthBar.style.backgroundColor = 'red';
                strengthText.textContent = 'Contraseña débil';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = 'orange';
                strengthText.textContent = 'Contraseña moderada';
            } else {
                strengthBar.style.backgroundColor = 'green';
                strengthText.textContent = 'Contraseña fuerte';
            }
        });
    </script>
</body>
</html>