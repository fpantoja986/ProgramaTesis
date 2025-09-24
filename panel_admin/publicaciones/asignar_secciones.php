<?php
include '../../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// Obtener contenidos sin sección
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.nombre_completo as autor_nombre
    FROM contenidos c
    INNER JOIN usuarios u ON c.id_admin = u.id
    WHERE c.seccion_id IS NULL
    ORDER BY c.fecha_creacion DESC
");
$stmt->execute();
$contenidos_sin_seccion = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las secciones
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        COUNT(c.id) as total_contenidos
    FROM secciones s
    LEFT JOIN contenidos c ON s.id = c.seccion_id
    WHERE s.visible = 1
    GROUP BY s.id
    ORDER BY s.orden ASC
");
$stmt->execute();
$secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Secciones</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .content-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .content-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .section-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .drag-over {
            background-color: #e3f2fd !important;
            border-color: #2196f3 !important;
        }
        .dragging {
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include '../panel_sidebar.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tasks mr-2"></i>Asignar Secciones
            </h1>
            <a href="gestionar_secciones.php" class="btn btn-secondary">
                <i class="fas fa-cog mr-2"></i>Gestionar Secciones
            </a>
        </div>

        <?php if (empty($contenidos_sin_seccion)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-2"></i>
                ¡Excelente! Todos los contenidos ya tienen una sección asignada.
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Contenidos sin sección -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Contenidos sin Sección (<?= count($contenidos_sin_seccion) ?>)
                            </h5>
                        </div>
                        <div class="card-body" id="contenidosSinSeccion">
                            <?php foreach ($contenidos_sin_seccion as $contenido): ?>
                                <div class="content-card card mb-3 draggable-content" 
                                     data-id="<?= $contenido['id'] ?>"
                                     draggable="true">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title mb-1">
                                                    <?= htmlspecialchars(mb_strimwidth($contenido['titulo'], 0, 50, '...')) ?>
                                                </h6>
                                                <p class="card-text text-muted small mb-1">
                                                    <?= htmlspecialchars(mb_strimwidth($contenido['contenido_texto'], 0, 80, '...')) ?>
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user mr-1"></i>
                                                        <?= htmlspecialchars($contenido['autor_nombre']) ?>
                                                    </small>
                                                    <span class="badge badge-<?= $contenido['tipo'] === 'video' ? 'danger' : ($contenido['tipo'] === 'audio' ? 'info' : 'primary') ?>">
                                                        <?= ucfirst($contenido['tipo']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-2">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Secciones disponibles -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-layer-group mr-2"></i>
                                Secciones Disponibles
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($secciones as $seccion): ?>
                                <div class="section-card card mb-3 drop-zone" 
                                     data-seccion-id="<?= $seccion['id'] ?>"
                                     style="border-left-color: <?= $seccion['color'] ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title mb-1">
                                                    <i class="<?= $seccion['icono'] ?> mr-2" style="color: <?= $seccion['color'] ?>"></i>
                                                    <?= htmlspecialchars($seccion['nombre']) ?>
                                                </h6>
                                                <?php if ($seccion['descripcion']): ?>
                                                    <p class="card-text text-muted small mb-1">
                                                        <?= htmlspecialchars($seccion['descripcion']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-file-alt mr-1"></i>
                                                        <?= $seccion['total_contenidos'] ?> contenidos
                                                    </small>
                                                    <span class="badge badge-light">
                                                        <i class="fas fa-sort mr-1"></i>
                                                        Orden: <?= $seccion['orden'] ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instrucciones -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle mr-2"></i>Instrucciones:</h6>
                        <ul class="mb-0">
                            <li>Arrastra los contenidos desde la columna izquierda hacia las secciones de la derecha</li>
                            <li>Los contenidos se moverán automáticamente a la sección seleccionada</li>
                            <li>Puedes reorganizar el orden de los contenidos dentro de cada sección</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Configurar drag and drop
        document.addEventListener('DOMContentLoaded', function() {
            const contenidos = document.querySelectorAll('.draggable-content');
            const secciones = document.querySelectorAll('.drop-zone');
            
            // Configurar elementos arrastrables
            contenidos.forEach(contenido => {
                contenido.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('text/plain', this.dataset.id);
                    this.classList.add('dragging');
                });
                
                contenido.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                });
            });
            
            // Configurar zonas de destino
            secciones.forEach(seccion => {
                seccion.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('drag-over');
                });
                
                seccion.addEventListener('dragleave', function(e) {
                    this.classList.remove('drag-over');
                });
                
                seccion.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');
                    
                    const contenidoId = e.dataTransfer.getData('text/plain');
                    const seccionId = this.dataset.seccionId;
                    
                    // Mover contenido a la sección
                    moverContenido(contenidoId, seccionId);
                });
            });
        });
        
        function moverContenido(contenidoId, seccionId) {
            fetch('asignar_contenido_seccion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `contenido_id=${contenidoId}&seccion_id=${seccionId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remover el contenido de la lista
                    const contenidoElement = document.querySelector(`[data-id="${contenidoId}"]`);
                    if (contenidoElement) {
                        contenidoElement.remove();
                    }
                    
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        title: '¡Asignado!',
                        text: 'El contenido ha sido asignado a la sección correctamente',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Verificar si quedan contenidos sin sección
                    const contenidosRestantes = document.querySelectorAll('.draggable-content');
                    if (contenidosRestantes.length === 0) {
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        }
    </script>
</body>
</html>
