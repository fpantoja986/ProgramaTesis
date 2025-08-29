<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Gestión de Contenidos</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --accent-color: #4e54c8;
            --light-bg: #f8f9fa;
            --dark-text: #2d3748;
            --light-text: #718096;
            --success-color: #48bb78;
            --danger-color: #f56565;
            --warning-color: #ecc94b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark-text);
            min-height: 100vh;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(106, 17, 203, 0.2);
        }

        .admin-header h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .admin-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            padding: 15px 20px;
            border: none;
        }

        .card-body {
            padding: 25px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(106, 17, 203, 0.25);
        }

        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #5a0db5, #1c67e0);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #edf2f7;
            color: var(--dark-text);
            border: none;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: var(--dark-text);
        }

        .file-list {
            list-style: none;
            padding: 0;
        }

        .file-list li {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s;
        }

        .file-list li:last-child {
            border-bottom: none;
        }

        .file-list li:hover {
            background: #f7fafc;
        }

        .file-list a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
            display: flex;
            align-items: center;
        }

        .file-list a:hover {
            color: var(--secondary-color);
        }

        .file-list i {
            margin-right: 10px;
            font-size: 18px;
        }

        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .modal-title {
            font-weight: 600;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Elegant file upload button */
        .file-upload-btn {
            background: linear-gradient(to right, #4e54c8, #8f94fb);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .file-upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.4);
        }

        .file-upload-btn i {
            margin-right: 8px;
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .custom-toast {
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin-bottom: 15px;
            max-width: 400px;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .toast-success {
            background: white;
            border-left: 5px solid var(--success-color);
        }

        .toast-error {
            background: white;
            border-left: 5px solid var(--danger-color);
        }

        .toast-warning {
            background: white;
            border-left: 5px solid var(--warning-color);
        }

        .toast-header {
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 12px 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .toast-body {
            padding: 15px;
            background: rgba(255, 255, 255, 0.98);
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .success-icon {
            color: var(--success-color);
        }

        .error-icon {
            color: var(--danger-color);
        }

        .warning-icon {
            color: var(--warning-color);
        }

        /* SweetAlert2 customization */
        .swal2-popup {
            border-radius: 15px;
            padding: 2em;
        }

        /* Inline messages */
        .inline-message {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s ease;
        }

        .inline-success {
            background-color: rgba(72, 187, 120, 0.15);
            border-left: 4px solid var(--success-color);
            color: #22543d;
        }

        .inline-error {
            background-color: rgba(245, 101, 101, 0.15);
            border-left: 4px solid var(--danger-color);
            color: #742a2a;
        }

        .inline-warning {
            background-color: rgba(236, 201, 75, 0.15);
            border-left: 4px solid var(--warning-color);
            color: #744210;
        }

        .message-icon {
            margin-right: 10px;
            font-size: 20px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .admin-header {
                padding: 20px;
            }

            .admin-header h1 {
                font-size: 1.8rem;
            }

            .card-body {
                padding: 20px;
            }

            .toast-container {
                left: 20px;
                right: 20px;
            }

            .custom-toast {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include 'panel_sidebar.php'; ?>

    <div class="admin-container">
        <!-- Toast notifications container -->
        <div class="toast-container" id="toastContainer"></div>

        <div class="admin-header">
            <h1><i class="fas fa-cloud-upload-alt me-2"></i>Subir Contenido</h1>
            <p>Gestiona y organiza todo tu contenido multimedia</p>
        </div>

        

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-upload me-2"></i>Formulario de Subida
                    </div>
                    <div class="card-body">
                        <!-- Ejemplo de mensaje inline -->
                        <div class="inline-message inline-success" id="inlineSuccess" style="display: none;">
                            <i class="fas fa-check-circle message-icon"></i>
                            <span id="inlineSuccessText">Contenido subido exitosamente</span>
                        </div>

                        <div class="inline-message inline-error" id="inlineError" style="display: none;">
                            <i class="fas fa-times-circle message-icon"></i>
                            <span id="inlineErrorText">Error al subir el contenido</span>
                        </div>

                        <form method="POST" enctype="multipart/form-data" action="procesar_subida.php" id="uploadForm">
                            <div class="mb-4">
                                <label for="titulo" class="form-label">Título del contenido</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>

                            <div class="mb-4">
                                <label for="tipo_contenido" class="form-label">Tipo de contenido</label>
                                <select class="form-select" id="tipo_contenido" name="tipo_contenido" required>
                                    <option value="articulo">Artículo</option>
                                    <option value="video">Video</option>
                                    <option value="podcast">Podcast</option>
                                    <option value="imagen">Imagen</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="contenido_texto" class="form-label">Descripción del contenido</label>
                                <textarea class="form-control" id="contenido_texto" name="contenido_texto"
                                    rows="5"></textarea>
                            </div>

                            <div class="mb-4" id="archivo_group" style="display:none;">
                                <label for="archivo" class="form-label">Archivo multimedia</label>
                                <input type="file" class="form-control-file" id="archivo" name="archivo"
                                    accept=".pdf,audio/*,video/*,image/*,.txt,.doc,.docx,.odt,.rtf,.xls,.xlsx,.ppt,.pptx">
                                <small class="form-text text-muted">Formatos permitidos: PDF, audio, video, imagen,
                                    texto, documentos Office.</small>
                            </div>

                            <div class="mb-4">
                                <button type="button" class="file-upload-btn" id="btnSeleccionarArchivo">
                                    <i class="fas fa-file-upload me-2"></i>Seleccionar archivo
                                </button>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-cloud-upload-alt me-2"></i>Subir Contenido
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Información de Ayuda
                    </div>
                    <div class="card-body">
                        <p><strong>Tipos de contenido:</strong></p>
                        <ul class="ps-3">
                            <li>Artículo: Contenido textual</li>
                            <li>Video: Archivos de video</li>
                            <li>Podcast: Archivos de audio</li>
                            <li>Imagen: Archivos de imagen</li>
                        </ul>
                        <p class="mt-3"><small class="text-muted">Tamaño máximo de archivo: 50MB</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Función para mostrar notificación toast de éxito
        function showSuccessToast(message = "Operación completada con éxito") {
            const toast = createToast('success', 'Éxito', message);
            document.getElementById('toastContainer').appendChild(toast);
            
            // 🔹 REINICIAR FORMULARIO cuando se muestra el toast de éxito
            document.getElementById('uploadForm').reset();
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Función para mostrar notificación toast de error
        function showErrorToast(message = "Ha ocurrido un error inesperado") {
            const toast = createToast('error', 'Error', message);
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Función para mostrar notificación toast de advertencia
        function showWarningToast(message = "Advertencia: acción requerida") {
            const toast = createToast('warning', 'Advertencia', message);
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 5000);
            }, 5000);
        }

        // Función para crear un toast
        function createToast(type, title, message) {
            const toast = document.createElement('div');
            toast.className = `custom-toast toast-${type}`;

            let iconClass = '';
            if (type === 'success') iconClass = 'fas fa-check-circle success-icon';
            if (type === 'error') iconClass = 'fas fa-times-circle error-icon';
            if (type === 'warning') iconClass = 'fas fa-exclamation-triangle warning-icon';

            toast.innerHTML = `
                <div class="toast-header">
                    <div class="toast-icon">
                        <i class="${iconClass}"></i>
                    </div>
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;

            // Animación de entrada
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            }, 10);

            return toast;
        }

        // Función para mostrar mensaje inline de éxito
        function showInlineMessage(type, message) {
            const element = document.getElementById(`inline${type.charAt(0).toUpperCase() + type.slice(1)}`);
            const textElement = document.getElementById(`inline${type.charAt(0).toUpperCase() + type.slice(1)}Text`);

            if (element && textElement) {
                textElement.textContent = message;
                element.style.display = 'flex';

                // 🔹 REINICIAR FORMULARIO cuando se muestra mensaje inline de éxito
                if (type === 'success') {
                    document.getElementById('uploadForm').reset();
                }

                // Ocultar después de 5 segundos
                setTimeout(() => {
                    element.style.opacity = '0';
                    setTimeout(() => {
                        element.style.display = 'none';
                        element.style.opacity = '1';
                    }, 300);
                }, 5000);
            }
        }

        // Función para mostrar mensaje inline de éxito (para demo)
        function showSuccessInline() {
            showInlineMessage('success', '¡Este es un mensaje de éxito inline! Se ha completado la operación correctamente.');
        }

        document.addEventListener('DOMContentLoaded', function () {
            <?php if (isset($_SESSION['mensaje_exito'])): ?>
                showSuccessToast('<?= htmlspecialchars($_SESSION['mensaje_exito']) ?>');
                // El formulario ya se reinicia dentro de showSuccessToast()
                <?php unset($_SESSION['mensaje_exito']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['mensaje_error'])): ?>
                showErrorToast('<?= htmlspecialchars($_SESSION['mensaje_error']) ?>');
                <?php unset($_SESSION['mensaje_error']); ?>
            <?php endif; ?>

            // Simular envío de formulario para demo
            document.getElementById('uploadForm').addEventListener('submit', function (e) {
                e.preventDefault();
                // Simular procesamiento
                setTimeout(() => {
                    showSuccessToast('El contenido se ha subido exitosamente');
                    showInlineMessage('success', 'El contenido se ha subido exitosamente');
                }, 1000);
            });
        });
    </script>

    <script>
        document.getElementById('btnSeleccionarArchivo').addEventListener('click', function () {
            document.getElementById('archivo').click();
        });

        const tipoContenidoSelect = document.getElementById('tipo_contenido');
        const archivoGroup = document.getElementById('archivo_group');

        function toggleInputFields() {
            const tipo = tipoContenidoSelect.value;
            if (tipo === 'articulo') {
                archivoGroup.style.display = 'none';
            } else {
                archivoGroup.style.display = 'block';
            }
        }

        tipoContenidoSelect.addEventListener('change', toggleInputFields);
        window.addEventListener('load', toggleInputFields);
    </script>
</body>

</html>