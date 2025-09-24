<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

$seccion_id = (int)($_GET['id'] ?? 0);

if (empty($seccion_id)) {
    header('Location: gestionar_secciones.php');
    exit;
}

// Obtener información de la sección
$stmt = $pdo->prepare("SELECT * FROM secciones WHERE id = ?");
$stmt->execute([$seccion_id]);
$seccion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seccion) {
    header('Location: gestionar_secciones.php');
    exit;
}

// Obtener contenidos de la sección
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.nombre_completo as autor_nombre
    FROM contenidos c
    INNER JOIN usuarios u ON c.id_admin = u.id
    WHERE c.seccion_id = ?
    ORDER BY c.orden_seccion ASC, c.fecha_creacion DESC
");
$stmt->execute([$seccion_id]);
$contenidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contenidos de <?= htmlspecialchars($seccion['nombre']) ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .section-header {
            background: linear-gradient(135deg, <?= $seccion['color'] ?> 0%, <?= $seccion['color'] ?>dd 100%);
            color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .content-card {
            border-left: 4px solid <?= $seccion['color'] ?>;
            transition: all 0.3s ease;
        }
        .content-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .drag-handle {
            cursor: move;
            color: #6c757d;
        }
        .sortable-content {
            cursor: move;
        }
    </style>
</head>
<body>
    <?php include '../panel_sidebar.php'; ?>
    
    <div class="admin-container">
        <!-- Header de la sección -->
        <div class="section-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="<?= $seccion['icono'] ?> mr-3"></i>
                        <?= htmlspecialchars($seccion['nombre']) ?>
                    </h1>
                    <?php if ($seccion['descripcion']): ?>
                        <p class="mb-0 opacity-75"><?= htmlspecialchars($seccion['descripcion']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <div class="h4 mb-0"><?= count($contenidos) ?></div>
                    <small>contenidos</small>
                </div>
            </div>
        </div>

        <!-- Navegación -->
        <div class="row mb-4">
            <div class="col-md-6">
                <a href="gestionar_secciones.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Volver a Secciones
                </a>
                <a href="asignar_secciones.php" class="btn btn-info ml-2">
                    <i class="fas fa-tasks mr-2"></i>Asignar Contenidos
                </a>
            </div>
            <div class="col-md-6 text-right">
                <div class="btn-group">
                    <button class="btn btn-outline-primary" onclick="toggleOrden()">
                        <i class="fas fa-sort mr-2"></i>Reordenar
                    </button>
                    <button class="btn btn-outline-success" onclick="guardarOrden()" id="btnGuardar" style="display: none;">
                        <i class="fas fa-save mr-2"></i>Guardar Orden
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($contenidos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                Esta sección no tiene contenidos asignados aún.
                <a href="asignar_secciones.php" class="btn btn-primary btn-sm ml-2">
                    <i class="fas fa-plus mr-1"></i>Asignar Contenidos
                </a>
            </div>
        <?php else: ?>
            <!-- Lista de contenidos -->
            <div class="row" id="contenidosContainer">
                <?php foreach ($contenidos as $contenido): ?>
                    <div class="col-md-6 col-lg-4 mb-4 sortable-content" data-id="<?= $contenido['id'] ?>">
                        <div class="card content-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0">
                                        <a href="ver_publicacion.php?id=<?= $contenido['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars(mb_strimwidth($contenido['titulo'], 0, 40, '...')) ?>
                                        </a>
                                    </h6>
                                    <div class="drag-handle" style="display: none;">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                </div>
                                
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars(mb_strimwidth($contenido['contenido_texto'], 0, 100, '...')) ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">
                                            <i class="fas fa-user mr-1"></i>
                                            <?= htmlspecialchars($contenido['autor_nombre']) ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            <?= date('d M Y', strtotime($contenido['fecha_creacion'])) ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="badge badge-<?= $contenido['tipo'] === 'video' ? 'danger' : ($contenido['tipo'] === 'audio' ? 'info' : 'primary') ?>">
                                            <?= ucfirst($contenido['tipo']) ?>
                                        </span>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="fas fa-sort mr-1"></i>
                                                Orden: <?= $contenido['orden_seccion'] ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="ver_publicacion.php?id=<?= $contenido['id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-outline-warning btn-sm" onclick="quitarDeSeccion(<?= $contenido['id'] ?>)">
                                            <i class="fas fa-unlink"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        let sortable = null;
        let modoOrden = false;
        
        function toggleOrden() {
            modoOrden = !modeOrden;
            
            if (modeOrden) {
                // Activar modo reordenamiento
                document.querySelectorAll('.drag-handle').forEach(handle => {
                    handle.style.display = 'block';
                });
                
                document.getElementById('btnGuardar').style.display = 'inline-block';
                
                // Crear sortable
                sortable = new Sortable(document.getElementById('contenidosContainer'), {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen'
                });
                
            } else {
                // Desactivar modo reordenamiento
                document.querySelectorAll('.drag-handle').forEach(handle => {
                    handle.style.display = 'none';
                });
                
                document.getElementById('btnGuardar').style.display = 'none';
                
                if (sortable) {
                    sortable.destroy();
                    sortable = null;
                }
            }
        }
        
        function guardarOrden() {
            const contenidos = Array.from(document.querySelectorAll('.sortable-content')).map((el, index) => ({
                id: el.dataset.id,
                orden: index + 1
            }));
            
            fetch('actualizar_orden_contenidos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    contenidos: contenidos,
                    seccion_id: <?= $seccion_id ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', 'Orden actualizado correctamente', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        }
        
        function quitarDeSeccion(contenidoId) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Este contenido será movido a "Sin sección"',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('quitar_contenido_seccion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `contenido_id=${contenidoId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('¡Quitado!', 'Contenido removido de la sección', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.error, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
