<?php
// Mostrar mensajes de error por GET
$errorMsg = isset($_GET['error']) ? htmlspecialchars(urldecode($_GET['error'])) : null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mujeres en Tech - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-b from-purple-50 to-white">
        <div class="bg-white p-10 rounded-2xl shadow-xl w-full max-w-md border-0 relative">
            <div class="absolute inset-0 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl opacity-10 -z-10 relative"></div>

            <!-- Ícono -->
            <div class="flex justify-center mb-4">
                <svg class="w-16 h-16 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
                </svg>
            </div>

            <h1 class="text-3xl font-bold text-center text-purple-700 mb-4">Empoderando Mujeres en Tecnología</h1>
            <p class="text-center text-gray-600 mb-8 text-lg">Conectamos, inspiramos y apoyamos a mujeres en el desarrollo de software</p>

            <?php if ($errorMsg): ?>
                <div class="mb-6">
                    <div class="flex items-center p-4 rounded-lg bg-red-50 text-red-700 border-l-4 border-red-500">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" />
                        </svg>
                        <span><?= $errorMsg ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form action="procesar_login.php" method="POST" class="mb-6">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2" for="email">Correo Electrónico</label>
                    <input type="email" name="email" id="email" required
                        class="w-full px-4 py-3 border-0 rounded-lg bg-gray-50 focus:ring-2 focus:ring-purple-400 transition duration-200">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2" for="password">Contraseña</label>
                    <input type="password" name="password" id="password" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <button type="submit"
                    class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white py-3 rounded-lg hover:opacity-90 transition duration-200">
                    Iniciar Sesión
                </button>
            </form>

            <div class="text-center text-sm">
                <p class="text-gray-600">¿No tienes cuenta? <a href="registro.php" class="text-purple-600 hover:underline">Regístrate</a></p>
                <p class="mt-2 text-gray-500">¿Olvidaste tu contraseña? <a href="#" onclick="showResetModal()" class="text-purple-600 hover:underline">Restablecer</a></p>
            </div>
        </div>
    </div>

    <!-- Modal Restablecer Contraseña -->
    <div id="passwordResetModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-bold text-center text-purple-700 mb-4">Restablecer Contraseña</h2>
            <form id="resetPasswordForm">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Correo Electrónico</label>
                    <input type="email" id="resetEmail" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <button type="submit"
                    class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition duration-300">
                    Enviar Instrucciones
                </button>
            </form>
            <div class="mt-4 text-center">
                <button onclick="hideResetModal()" class="text-purple-600 hover:underline">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        function showResetModal() {
            document.getElementById('passwordResetModal').classList.remove('hidden');
        }

        function hideResetModal() {
            document.getElementById('passwordResetModal').classList.add('hidden');
        }

        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('resetEmail').value;

            fetch('procesar_reset.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        email: email
                    }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Instrucciones enviadas al correo.');
                        hideResetModal();
                    } else {
                        alert(data.message || '❌ Error al enviar el correo.');
                    }
                })
                .catch(err => {
                    alert('❌ Error de red o servidor.');
                    console.error(err);
                });
        });
    </script>
</body>

</html>