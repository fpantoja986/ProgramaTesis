<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// Obtener TODAS las publicaciones con información del autor usando la nueva estructura
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.titulo,
        c.tipo,
        c.contenido_texto,
        c.archivo_path,
        c.fecha_creacion,
        u.nombre_completo as autor_nombre,
        u.id as autor_id
    FROM contenidos c 
    INNER JOIN usuarios u ON c.id_admin = u.id
    WHERE u.rol = 'administrador'
    ORDER BY c.fecha_creacion DESC
");
$stmt->execute();
$publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Todas las Publicaciones</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="styles_publicaciones.css">


</head>

<body>
    <?php include '../panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Todas las Publicaciones <span class="publication-count"><?= count($publicaciones) ?></span>
            </h1>
        </div>

        <?php if (count($publicaciones) === 0): ?>
            <div class="no-publications">
                <i class="fas fa-file-alt"></i>
                <p>No hay publicaciones aún.</p>
                <a href="subir_contenido.php" class="create-btn">
                    <i class="fas fa-plus-circle mr-2"></i>Crear primera publicación
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($publicaciones as $pub): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 publication-card" tabindex="0" role="button" aria-pressed="false"
                            data-id="<?= $pub['id'] ?>"
                            onclick="if(!event.target.closest('.btn, button, a')) { window.location.href='ver_publicacion.php?id=<?= $pub['id'] ?>'; }">
                            <span class="badge-tipo"><?= ucfirst($pub['tipo']) ?></span>

                            <?php if ($pub['archivo_path']): ?>
                                <?php if ($pub['tipo'] === 'imagen'): ?>
                                    <div class="card-img-container">
                                        <img src="servir_archivo.php?id=<?= $pub['id'] ?>" class="card-img-top" alt="Imagen">
                                    </div>
                                <?php elseif ($pub['tipo'] === 'video'): ?>
                                    <div class="media-icon-container">
                                        <i class="fas fa-play-circle media-icon"></i>
                                    </div>
                                <?php elseif ($pub['tipo'] === 'audio' || $pub['tipo'] === 'podcast'): ?>
                                    <div class="media-icon-container">
                                        <i class="fas fa-headphones media-icon"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="media-icon-container">
                                        <i class="fas fa-file-alt media-icon"></i>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="media-icon-container">
                                    <i class="fas fa-file-alt media-icon"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="card-title">
                                    <a
                                        href="ver_publicacion.php?id=<?= $pub['id'] ?>"><?= htmlspecialchars(mb_strimwidth($pub['titulo'], 0, 60, '...')) ?></a>
                                </h5>
                                <p class="card-text">
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($pub['contenido_texto'], 0, 120, '...'))) ?>
                                </p>
                            </div>

                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="publication-date">
                                            <i class="far fa-calendar-alt mr-1"></i><?= date('d M Y', strtotime($pub['fecha_creacion'])) ?>
                                        </small>
                                        <div class="publication-author">
                                            <span class="author-badge">
                                                <i class="fas fa-user-shield mr-1"></i><?= htmlspecialchars($pub['autor_nombre']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        <?php if ($pub['autor_id'] == $_SESSION['user_id']): ?>
                                            <!-- Solo el autor puede editar y eliminar -->
                                            <button class="btn btn-primary btn-sm btn-editar" data-id="<?= $pub['id'] ?>" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?= $pub['id'] ?>" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php else: ?>
                                            <!-- Otros admins solo pueden ver -->
                                            <button class="btn btn-secondary btn-sm" onclick="mostrarMensajePermiso()" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Edición -->
    <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalEditarLabel">
                        <i class="fas fa-edit mr-2"></i>Editar Publicación
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formEditar">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">

                        <div class="form-group">
                            <label for="edit_titulo">Título del contenido</label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_tipo">Tipo de contenido</label>
                            <select class="form-control" id="edit_tipo" name="tipo" required>
                                <option value="articulo">Artículo</option>
                                <option value="video">Video</option>
                                <option value="podcast">Podcast</option>
                                <option value="imagen">Imagen</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_contenido">Descripción del contenido</label>
                            <textarea class="form-control" id="edit_contenido" name="contenido_texto" rows="4"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="edit_archivo">Cambiar archivo (opcional)</label>
                            <input type="file" class="form-control-file" id="edit_archivo" name="archivo"
                                accept=".pdf,audio/*,video/*,image/*,.txt,.doc,.docx,.odt,.rtf,.xls,.xlsx,.ppt,.pptx">
                            <small class="form-text text-muted">Deja vacío si no quieres cambiar el archivo</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para mostrar mensaje de permisos
        function mostrarMensajePermiso() {
            Swal.fire({
                title: 'Sin permisos',
                text: 'Solo puedes modificar o eliminar tus propias publicaciones.',
                icon: 'info',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#4e73df'
            });
        }

        // Manejar eliminación de publicaciones
        document.querySelectorAll('.btn-eliminar').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.getAttribute('data-id');

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción eliminará la publicación de forma permanente.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('eliminar_publicacion.php?id=' + id)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Eliminado!',
                                        text: 'La publicación ha sido eliminada con éxito.',
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', data.error || 'No se pudo eliminar la publicación.', 'error');
                                }
                            })
                            .catch(() => {
                                Swal.fire('Error', 'Error de conexión al eliminar la publicación.', 'error');
                            });
                    }
                });
            });
        });

        // Manejar edición de publicaciones
        document.querySelectorAll('.btn-editar').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                cargarDatosPublicacion(id);
            });
        });

        // Cargar datos de la publicación en el modal
        function cargarDatosPublicacion(id) {
            fetch('obtener_publicacion.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.publicacion.id;
                        document.getElementById('edit_titulo').value = data.publicacion.titulo;
                        document.getElementById('edit_tipo').value = data.publicacion.tipo;
                        document.getElementById('edit_contenido').value = data.publicacion.contenido_texto || '';
                        $('#modalEditar').modal('show');
                    } else {
                        Swal.fire('Error', data.error || 'No se pudieron cargar los datos de la publicación.', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Error de conexión al cargar la publicación.', 'error');
                });
        }

        // Manejar envío del formulario de edición
        document.getElementById('formEditar').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('actualizar_publicacion.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $('#modalEditar').modal('hide');
                        Swal.fire({
                            title: '¡Actualizado!',
                            text: 'La publicación ha sido actualizada con éxito.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.error || 'No se pudo actualizar la publicación.', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Error de conexión al actualizar la publicación.', 'error');
                });
        });

        // Cambiar tipos de archivo permitidos según el tipo de contenido en el modal
        document.getElementById('edit_tipo').addEventListener('change', function() {
            const tipo = this.value;
            const archivoInput = document.getElementById('edit_archivo');

            switch (tipo) {
                case 'articulo':
                    archivoInput.setAttribute('accept', '.pdf');
                    break;
                case 'video':
                    archivoInput.setAttribute('accept', 'video/*,.mp4,.avi,.mov,.wmv,.flv,.webm');
                    break;
                case 'podcast':
                    archivoInput.setAttribute('accept', 'audio/*,.mp3,.wav,.ogg,.aac,.flac');
                    break;
                case 'imagen':
                    archivoInput.setAttribute('accept', 'image/*,.jpg,.jpeg,.png,.gif,.bmp,.svg,.webp');
                    break;
                default:
                    archivoInput.setAttribute('accept', '.pdf,audio/*,video/*,image/*,.txt,.doc,.docx,.odt,.rtf,.xls,.xlsx,.ppt,.pptx');
            }
        });
    </script>
</body>

</html>