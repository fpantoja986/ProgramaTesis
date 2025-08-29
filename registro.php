<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio | Mujeres en Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h1 class="text-2xl font-bold text-center text-purple-700 mb-6">Registro de Mujeres en Tech</h1>
            
            <form action="procesar_registro.php" method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Nombre Completo</label>
                    <input type="text" name="nombre_completo" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Correo Electrónico</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="mb-4">
    <label class="block text-gray-700 mb-2">Género</label>
    <select name="genero" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        <option value="">Selecciona una opción</option>
        <option value="Femenino">Femenino</option>
        <option value="Masculino">Masculino</option>
        <option value="No binario">No binario</option>
        <option value="Prefiero no decirlo">Prefiero no decirlo</option>
    </select>
</div>

                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Contraseña</label>
                    <input type="password" name="password" id="passwordInput" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div id="passwordStrength" class="h-2 rounded-full" style="width: 0%"></div>
                    </div>
                    <p id="passwordStrengthText" class="mt-1 text-xs text-gray-500">Debe contener 8+ caracteres, incluyendo números, minúsculas y mayúsculas</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Confirmar Contraseña</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                
                <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition duration-300">Registrarse</button>
            </form>
            
            <div class="mt-4 text-center">
                <p class="text-gray-600">¿Ya tienes cuenta? <a href="login.php" class="text-purple-600 hover:underline">Inicia sesión</a> · <a href="olvide_password.php" class="text-purple-600 hover:underline">¿Olvidaste tu contraseña?</a></p>
            </div>
        </div>
    </div>
</body>
<script>
document.getElementById('passwordInput').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('passwordStrengthText');
    let strength = 0;
    
    // Check length
    if (password.length >= 8) strength += 25;
    
    // Check for lowercase, uppercase, numbers
    if (/[a-z]/.test(password)) strength += 25;
    if (/[A-Z]/.test(password)) strength += 25;
    if (/[0-9]/.test(password)) strength += 25;
    
    // Update UI
    strengthBar.style.width = strength + '%';
    
    // Change color based on strength
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
</html>
