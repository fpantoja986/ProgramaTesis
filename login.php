<?php
// Mostrar mensajes de error por GET
$errorMsg = isset($_GET['error']) ? htmlspecialchars(urldecode($_GET['error'])) : null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Community - Iniciar Sesión</title>
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

            <h1 class="text-3xl font-bold text-center text-purple-700 mb-4">Comunidad de Desarrolladores</h1>
            <p class="text-center text-gray-600 mb-8 text-lg">Conectamos, inspiramos y apoyamos a desarrolladores en el mundo del software</p>

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
            
            <!-- Mensaje de estado -->
            <div id="resetMessage" class="mb-4 p-3 rounded-lg text-center text-sm hidden"></div>
            
            <form id="resetPasswordForm">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Correo Electrónico</label>
                    <input type="email" id="resetEmail" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <button type="submit" id="resetSubmitBtn"
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
            document.getElementById('resetEmail').focus();
        }

        function hideResetModal() {
            document.getElementById('passwordResetModal').classList.add('hidden');
            document.getElementById('resetPasswordForm').reset();
            hideResetMessage();
        }

        function showResetMessage(message, type) {
            const messageDiv = document.getElementById('resetMessage');
            messageDiv.textContent = message;
            messageDiv.className = `mb-4 p-3 rounded-lg text-center text-sm ${
                type === 'success' 
                    ? 'bg-green-50 text-green-700 border border-green-200' 
                    : 'bg-red-50 text-red-700 border border-red-200'
            }`;
            messageDiv.classList.remove('hidden');
        }

        function hideResetMessage() {
            const messageDiv = document.getElementById('resetMessage');
            messageDiv.classList.add('hidden');
        }

        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('resetEmail').value.trim();
            const submitBtn = document.getElementById('resetSubmitBtn');
            
            // Validación básica
            if (!email) {
                showResetMessage('Por favor ingresa tu email', 'error');
                return;
            }

            // Validar formato de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showResetMessage('Por favor ingresa un email válido', 'error');
                return;
            }

            // Deshabilitar botón y mostrar estado
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';
            hideResetMessage();

            // Crear la petición
            const requestData = {
                email: email
            };

            console.log('🚀 Enviando solicitud:', requestData);

            // Primero, probar si el endpoint existe
            fetch('procesar_reset.php', {
                method: 'OPTIONS'
            })
            .then(() => {
                console.log('✅ Endpoint alcanzable');
                
                // Ahora hacer la petición real
                return fetch('procesar_reset.php', {
                    method: 'POST',
                    body: JSON.stringify(requestData),
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
            })
            .then(response => {
                console.log('📡 Status:', response.status);
                console.log('📄 Headers:', [...response.headers.entries()]);
                
                // Verificar si es JSON
                const contentType = response.headers.get('Content-Type') || '';
                
                if (!contentType.includes('application/json')) {
                    console.warn('⚠️ Respuesta no es JSON:', contentType);
                    return response.text().then(text => {
                        console.log('📝 Respuesta como texto:', text.substring(0, 500));
                        throw new Error(`El servidor devolvió: ${text.substring(0, 100)}`);
                    });
                }

                return response.json();
            })
            .then(data => {
                console.log('📨 Respuesta JSON:', data);
                
                if (data.success) {
                    showResetMessage(data.message, 'success');
                    document.getElementById('resetPasswordForm').style.display = 'none';
                    
                    // Cerrar modal después de 3 segundos
                    setTimeout(() => {
                        hideResetModal();
                        document.getElementById('resetPasswordForm').style.display = 'block';
                    }, 3000);
                } else {
                    showResetMessage(data.message || 'Error desconocido del servidor', 'error');
                }
            })
            .catch(error => {
                console.error('❌ Error completo:', error);
                console.error('❌ Stack trace:', error.stack);
                
                let errorMessage = '❌ ';
                
                if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                    errorMessage += 'No se puede conectar al servidor. Verifica que XAMPP esté ejecutándose.';
                } else if (error.message.includes('JSON')) {
                    errorMessage += 'El servidor devolvió una respuesta inválida.';
                } else if (error.message.includes('404')) {
                    errorMessage += 'Archivo procesar_reset.php no encontrado.';
                } else if (error.message.includes('500')) {
                    errorMessage += 'Error interno del servidor. Revisa los logs de PHP.';
                } else {
                    errorMessage += error.message;
                }
                
                showResetMessage(errorMessage, 'error');
                
                // Info adicional para debug
                console.log('🔍 URL actual:', window.location.href);
                console.log('🔍 Navegador:', navigator.userAgent);
            })
            .finally(() => {
                // Rehabilitar botón
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Instrucciones';
            });
        });
    </script>
</body>

</html>