<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// Obtener todas las secciones con contador de contenidos
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        COUNT(c.id) as total_contenidos,
        COUNT(CASE WHEN s.visible = 1 THEN c.id END) as contenidos_visibles
    FROM secciones s
    LEFT JOIN contenidos c ON s.id = c.seccion_id
    GROUP BY s.id
    ORDER BY s.orden ASC, s.nombre ASC
");
$stmt->execute();
$secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener contenidos sin sección
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_sin_seccion
    FROM contenidos 
    WHERE seccion_id IS NULL
");
$stmt->execute();
$sin_seccion = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Secciones</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../dark-mode.css">
    <style>
        .section-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .content-counter {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.9em;
        }
        .drag-handle {
            cursor: move;
            color: #6c757d;
        }
        .section-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .section-card:hover .section-actions {
            opacity: 1;
        }
        .color-preview {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }
        .sortable-section {
            cursor: move;
        }
    </style>
</head>
<body>
    <?php include '../panel_sidebar.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-layer-group mr-2"></i>Gestionar Secciones
            </h1>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCrearSeccion">
                <i class="fas fa-plus mr-2"></i>Nueva Sección
            </button>
        </div>

        <!-- Resumen de secciones -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= count($secciones) ?></h4>
                                <p class="mb-0">Total Secciones</p>
                            </div>
                            <i class="fas fa-layer-group fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= array_sum(array_column($secciones, 'total_contenidos')) ?></h4>
                                <p class="mb-0">Total Contenidos</p>
                            </div>
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= $sin_seccion['total_sin_seccion'] ?></h4>
                                <p class="mb-0">Sin Sección</p>
                            </div>
                            <i class="fas fa-question-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= count(array_filter($secciones, function($s) { return $s['visible'] == 1; })) ?></h4>
                                <p class="mb-0">Secciones Visibles</p>
                            </div>
                            <i class="fas fa-eye fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de secciones -->
        <div class="row" id="seccionesContainer">
            <?php foreach ($secciones as $seccion): ?>
                <div class="col-md-6 col-lg-4 mb-4 sortable-section" data-id="<?= $seccion['id'] ?>">
                    <div class="card section-card h-100" style="border-left-color: <?= $seccion['color'] ?>">
                        <div class="section-header p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <i class="<?= $seccion['icono'] ?> fa-lg mr-2"></i>
                                    <h5 class="mb-0"><?= htmlspecialchars($seccion['nombre']) ?></h5>
                                </div>
                                <div class="section-actions">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-light btn-sm" onclick="editarSeccion(<?= $seccion['id'] ?>)" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-light btn-sm" onclick="toggleVisibilidad(<?= $seccion['id'] ?>, <?= $seccion['visible'] ?>)" title="<?= $seccion['visible'] ? 'Ocultar' : 'Mostrar' ?>">
                                            <i class="fas fa-<?= $seccion['visible'] ? 'eye-slash' : 'eye' ?>"></i>
                                        </button>
                                        <button class="btn btn-light btn-sm" onclick="eliminarSeccion(<?= $seccion['id'] ?>)" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($seccion['descripcion']): ?>
                                <p class="card-text text-muted"><?= htmlspecialchars($seccion['descripcion']) ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="content-counter">
                                    <i class="fas fa-file-alt mr-1"></i>
                                    <?= $seccion['total_contenidos'] ?> contenidos
                                </div>
                                <div class="drag-handle">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <span class="badge badge-<?= $seccion['visible'] ? 'success' : 'secondary' ?>">
                                    <i class="fas fa-<?= $seccion['visible'] ? 'eye' : 'eye-slash' ?> mr-1"></i>
                                    <?= $seccion['visible'] ? 'Visible' : 'Oculta' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-sort mr-1"></i>Orden: <?= $seccion['orden'] ?>
                                </small>
                                <a href="contenidos_por_seccion.php?id=<?= $seccion['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-list mr-1"></i>Ver Contenidos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sección para contenidos sin sección -->
        <?php if ($sin_seccion['total_sin_seccion'] > 0): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Contenidos sin Sección (<?= $sin_seccion['total_sin_seccion'] ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">Hay contenidos que no han sido asignados a ninguna sección.</p>
                            <a href="asignar_secciones.php" class="btn btn-warning mt-2">
                                <i class="fas fa-tasks mr-2"></i>Asignar Secciones
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Crear Sección -->
    <div class="modal fade" id="modalCrearSeccion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus mr-2"></i>Nueva Sección
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formCrearSeccion">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="nombre">Nombre de la sección *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="color">Color</label>
                                    <input type="color" class="form-control" id="color" name="color" value="#007bff">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="icono">Icono</label>
                                    <select class="form-control" id="icono" name="icono">
                                        <option value="fas fa-folder">📁 Carpeta</option>
                                        <option value="fas fa-newspaper">📰 Noticias</option>
                                        <option value="fas fa-book">📚 Tutoriales</option>
                                        <option value="fas fa-download">⬇️ Recursos</option>
                                        <option value="fas fa-calendar-alt">📅 Eventos</option>
                                        <option value="fas fa-video">🎥 Videos</option>
                                        <option value="fas fa-microphone">🎤 Podcasts</option>
                                        <option value="fas fa-image">🖼️ Imágenes</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="visible" name="visible" checked>
                                <label class="form-check-label" for="visible">
                                    Sección visible para usuarios
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Sección</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Sección -->
    <div class="modal fade" id="modalEditarSeccion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit mr-2"></i>Editar Sección
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formEditarSeccion">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <!-- Mismo contenido que el modal crear -->
                        <div class="form-group">
                            <label for="edit_nombre">Nombre de la sección *</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_descripcion">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_color">Color</label>
                                    <input type="color" class="form-control" id="edit_color" name="color">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_icono">Icono</label>
                                    <select class="form-control" id="edit_icono" name="icono">
                                        <option value="fas fa-folder">📁 Carpeta</option>
                                        <option value="fas fa-newspaper">📰 Noticias</option>
                                        <option value="fas fa-book">📚 Tutoriales</option>
                                        <option value="fas fa-download">⬇️ Recursos</option>
                                        <option value="fas fa-calendar-alt">📅 Eventos</option>
                                        <option value="fas fa-video">🎥 Videos</option>
                                        <option value="fas fa-microphone">🎤 Podcasts</option>
                                        <option value="fas fa-image">🖼️ Imágenes</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_visible" name="visible">
                                <label class="form-check-label" for="edit_visible">
                                    Sección visible para usuarios
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Actualizar Sección</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="../dark-mode.js"></script>
    
    <script>
        // Crear sección
        document.getElementById('formCrearSeccion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('procesar_seccion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', 'Sección creada correctamente', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        });

        // Editar sección
        function editarSeccion(id) {
            fetch(`obtener_seccion.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const s = data.seccion;
                        document.getElementById('edit_id').value = s.id;
                        document.getElementById('edit_nombre').value = s.nombre;
                        document.getElementById('edit_descripcion').value = s.descripcion || '';
                        document.getElementById('edit_color').value = s.color;
                        document.getElementById('edit_icono').value = s.icono;
                        document.getElementById('edit_visible').checked = s.visible == 1;
                        
                        $('#modalEditarSeccion').modal('show');
                    }
                });
        }

        // Actualizar sección
        document.getElementById('formEditarSeccion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('procesar_seccion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', 'Sección actualizada correctamente', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            });
        });

        // Toggle visibilidad
        function toggleVisibilidad(id, visible) {
            fetch('procesar_seccion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_visibility&id=${id}&visible=${visible ? 0 : 1}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            });
        }

        // Eliminar sección
        function eliminarSeccion(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción eliminará la sección y moverá sus contenidos a "Sin sección"',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('procesar_seccion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete&id=${id}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('¡Eliminado!', 'Sección eliminada correctamente', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.error, 'error');
                        }
                    });
                }
            });
        }

        // Drag & Drop para reordenar
        new Sortable(document.getElementById('seccionesContainer'), {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                const secciones = Array.from(document.querySelectorAll('.sortable-section')).map((el, index) => ({
                    id: el.dataset.id,
                    orden: index + 1
                }));
                
                fetch('procesar_seccion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'reorder',
                        secciones: secciones
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire('Error', 'No se pudo actualizar el orden', 'error');
                    }
                });
            }
        });
    </script>
</body>
</html>
