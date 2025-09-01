<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Gestión de Contenidos</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap"
        rel="stylesheet">

   <link rel="stylesheet" href="styles_publicaciones.css">
</head>

<body>
    <?php include '../panel_sidebar.php'; ?>

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
                        <!-- Mensajes inline -->
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

                            <div class="mb-4" id="archivo_group">
                                <label for="archivo" class="form-label">Archivo multimedia</label>
                                <input type="file" class="form-control" id="archivo" name="archivo" style="display:none;"
                                    accept=".pdf,audio/*,video/*,image/*,.txt,.doc,.docx,.odt,.rtf,.xls,.xlsx,.ppt,.pptx">
                                <button type="button" class="file-upload-btn" id="btnSeleccionarArchivo">
                                    <i class="fas fa-file-upload me-2"></i>Seleccionar archivo
                                </button>
                                <div id="archivo_seleccionado" style="display:none; margin-top:10px;">
                                    <div class="alert alert-success d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span id="nombre_archivo"></span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="btnCambiarArchivo">
                                            <i class="fas fa-edit me-1"></i>Cambiar
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted" id="formato_permitido">Formatos permitidos: PDF, audio, video, imagen, texto, documentos Office.</small>
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

            // Reiniciar formulario cuando se muestra el toast de éxito
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

        // Función para mostrar mensaje inline
        function showInlineMessage(type, message) {
            const element = document.getElementById(`inline${type.charAt(0).toUpperCase() + type.slice(1)}`);
            const textElement = document.getElementById(`inline${type.charAt(0).toUpperCase() + type.slice(1)}Text`);

            if (element && textElement) {
                textElement.textContent = message;
                element.style.display = 'flex';

                // Reiniciar formulario cuando se muestra mensaje inline de éxito
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

        // Función para mostrar mensaje inline de éxito (para demo manual)
        function showSuccessInline() {
            showInlineMessage('success', '¡Este es un mensaje de éxito inline! Se ha completado la operación correctamente.');
        }

        // Eventos que se ejecutan cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar mensajes de sesión PHP (solo si existen)
            <?php if (isset($_SESSION['mensaje_exito'])): ?>
                showSuccessToast('<?= htmlspecialchars($_SESSION['mensaje_exito']) ?>');
                <?php unset($_SESSION['mensaje_exito']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['mensaje_error'])): ?>
                showErrorToast('<?= htmlspecialchars($_SESSION['mensaje_error']) ?>');
                <?php unset($_SESSION['mensaje_error']); ?>
            <?php endif; ?>

            // IMPORTANTE: NO interceptamos el envío del formulario
            // El formulario se enviará normalmente a procesar_subida.php
        });

        // Funcionalidad del botón de seleccionar archivo
        document.getElementById('btnSeleccionarArchivo').addEventListener('click', function() {
            document.getElementById('archivo').click();
        });

        // Manejar cuando se selecciona un archivo
        document.getElementById('archivo').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const archivo = this.files[0];
                const nombreArchivo = archivo.name;

                // Ocultar botón de seleccionar
                document.getElementById('btnSeleccionarArchivo').style.display = 'none';

                // Mostrar información del archivo seleccionado
                document.getElementById('nombre_archivo').textContent = nombreArchivo;
                document.getElementById('archivo_seleccionado').style.display = 'block';
            }
        });

        // Botón para cambiar archivo
        document.getElementById('btnCambiarArchivo').addEventListener('click', function() {
            // Mostrar nuevamente el botón de seleccionar
            document.getElementById('btnSeleccionarArchivo').style.display = 'inline-flex';

            // Ocultar la información del archivo
            document.getElementById('archivo_seleccionado').style.display = 'none';

            // Limpiar el input de archivo
            document.getElementById('archivo').value = '';

            // Abrir selector de archivos
            document.getElementById('archivo').click();
        });

        // Mostrar/ocultar campo de archivo y cambiar formatos permitidos según el tipo de contenido
        const tipoContenidoSelect = document.getElementById('tipo_contenido');
        const archivoGroup = document.getElementById('archivo_group');
        const archivoInput = document.getElementById('archivo');
        const formatoPermitido = document.getElementById('formato_permitido');

        function toggleInputFields() {
            const tipo = tipoContenidoSelect.value;

            // El campo de archivo siempre está visible
            archivoGroup.style.display = 'block';

            // Cambiar los formatos permitidos según el tipo
            switch (tipo) {
                case 'articulo':
                    archivoInput.setAttribute('accept', '.pdf');
                    formatoPermitido.textContent = 'Formato permitido: Solo PDF para artículos.';
                    break;
                case 'video':
                    archivoInput.setAttribute('accept', 'video/*,.mp4,.avi,.mov,.wmv,.flv,.webm');
                    formatoPermitido.textContent = 'Formatos permitidos: MP4, AVI, MOV, WMV, FLV, WebM.';
                    break;
                case 'podcast':
                    archivoInput.setAttribute('accept', 'audio/*,.mp3,.wav,.ogg,.aac,.flac');
                    formatoPermitido.textContent = 'Formatos permitidos: MP3, WAV, OGG, AAC, FLAC.';
                    break;
                case 'imagen':
                    archivoInput.setAttribute('accept', 'image/*,.jpg,.jpeg,.png,.gif,.bmp,.svg,.webp');
                    formatoPermitido.textContent = 'Formatos permitidos: JPG, PNG, GIF, BMP, SVG, WebP.';
                    break;
                default:
                    archivoInput.setAttribute('accept', '.pdf,audio/*,video/*,image/*,.txt,.doc,.docx,.odt,.rtf,.xls,.xlsx,.ppt,.pptx');
                    formatoPermitido.textContent = 'Formatos permitidos: PDF, audio, video, imagen, texto, documentos Office.';
            }

            // Limpiar archivo seleccionado al cambiar tipo
            archivoInput.value = '';
            document.getElementById('archivo_seleccionado').style.display = 'none';
            document.getElementById('btnSeleccionarArchivo').style.display = 'inline-flex';
        }

        tipoContenidoSelect.addEventListener('change', toggleInputFields);
        window.addEventListener('load', toggleInputFields);
    </script>
</body>

</html>