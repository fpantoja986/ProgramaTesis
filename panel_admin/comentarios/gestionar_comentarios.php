<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit();
}

include '../../db.php';

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['nombre_completo'] ?? 'Administrador';

// Procesar acciones de moderación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $comentario_id = (int)($_POST['comentario_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'aprobar':
                $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET moderado = 1, id_moderador = ?, fecha_moderacion = NOW() WHERE id = ?");
                $stmt->execute([$admin_id, $comentario_id]);
                break;
                
            case 'rechazar':
                $motivo = trim($_POST['motivo_rechazo'] ?? '');
                $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET moderado = 2, id_moderador = ?, fecha_moderacion = NOW(), motivo_rechazo = ? WHERE id = ?");
                $stmt->execute([$admin_id, $motivo, $comentario_id]);
                break;
                
            case 'eliminar':
                $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET activo = 0 WHERE id = ?");
                $stmt->execute([$comentario_id]);
                break;
                
            case 'eliminar_permanente':
                // Eliminar permanentemente (también elimina respuestas)
                $stmt = $pdo->prepare("DELETE FROM comentarios_publicaciones WHERE id = ? OR id_comentario_padre = ?");
                $stmt->execute([$comentario_id, $comentario_id]);
                break;
                
            case 'restaurar':
                $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET activo = 1 WHERE id = ?");
                $stmt->execute([$comentario_id]);
                break;
                
            case 'marcar_reporte_revisado':
                // Actualizar el estado del reporte en el JSON
                $stmt = $pdo->prepare("SELECT reportes FROM comentarios_publicaciones WHERE id = ?");
                $stmt->execute([$comentario_id]);
                $reportes = json_decode($stmt->fetchColumn(), true);
                
                if ($reportes) {
                    foreach ($reportes as &$reporte) {
                        if ($reporte['estado'] === 'pendiente') {
                            $reporte['estado'] = 'revisado';
                            $reporte['fecha_revision'] = date('Y-m-d H:i:s');
                            $reporte['moderador'] = $admin_name;
                        }
                    }
                    
                    $stmt = $pdo->prepare("UPDATE comentarios_publicaciones SET reportes = ? WHERE id = ?");
                    $stmt->execute([json_encode($reportes), $comentario_id]);
                }
                break;
        }
        
        header("Location: gestionar_comentarios.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error en la base de datos: " . $e->getMessage();
    }
}

// Obtener filtros
$filtro = $_GET['filtro'] ?? 'todos';
$busqueda = $_GET['busqueda'] ?? '';

$where_conditions = [];
$params = [];

// Solo filtrar por activo si no estamos viendo eliminados
if ($filtro !== 'eliminados') {
    $where_conditions[] = "c.activo = 1";
}

if ($filtro === 'pendientes') {
    $where_conditions[] = "c.moderado = 0";
} elseif ($filtro === 'aprobados') {
    $where_conditions[] = "c.moderado = 1";
} elseif ($filtro === 'rechazados') {
    $where_conditions[] = "c.moderado = 2";
} elseif ($filtro === 'reportados') {
    $where_conditions[] = "c.total_reportes > 0";
} elseif ($filtro === 'eliminados') {
    $where_conditions[] = "c.activo = 0";
}

if (!empty($busqueda)) {
    $where_conditions[] = "(c.comentario LIKE ? OR u.nombre_completo LIKE ? OR c.titulo LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$where_clause = implode(' AND ', $where_conditions);

// Obtener comentarios
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.nombre_completo as autor_nombre,
        u.foto_perfil as autor_foto,
        u.email as autor_email,
        cont.titulo as publicacion_titulo,
        m.nombre_completo as moderador_nombre
    FROM comentarios_publicaciones c
    LEFT JOIN usuarios u ON c.id_usuario = u.id
    LEFT JOIN contenidos cont ON c.id_publicacion = cont.id
    LEFT JOIN usuarios m ON c.id_moderador = m.id
    WHERE $where_clause
    ORDER BY c.fecha_creacion DESC
    LIMIT 50
");
$stmt->execute($params);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats = [];
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comentarios_publicaciones WHERE activo = 1");
$stmt->execute();
$stats['total'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comentarios_publicaciones WHERE activo = 1 AND moderado = 0");
$stmt->execute();
$stats['pendientes'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comentarios_publicaciones WHERE activo = 1 AND moderado = 1");
$stmt->execute();
$stats['aprobados'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comentarios_publicaciones WHERE activo = 1 AND total_reportes > 0");
$stmt->execute();
$stats['reportados'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comentarios_publicaciones WHERE activo = 0");
$stmt->execute();
$stats['eliminados'] = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Comentarios - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            text-align: center;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .comentario-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            padding: 1.5rem;
        }
        .comentario-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .comentario-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .comentario-meta {
            flex: 1;
        }
        .comentario-nombre {
            font-weight: 600;
            color: #495057;
            margin: 0;
        }
        .comentario-fecha {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .comentario-texto {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
            line-height: 1.6;
        }
        .comentario-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-moderation {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-aprobar {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        .btn-rechazar {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
        }
        .btn-eliminar {
            background: linear-gradient(45deg, #6c757d, #495057);
            color: white;
        }
        .btn-reportes {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            color: #212529;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        .status-aprobado {
            background: #d4edda;
            color: #155724;
        }
        .status-rechazado {
            background: #f8d7da;
            color: #721c24;
        }
        .reportes-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .reporte-item {
            background: white;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #ffc107;
        }
        .filters-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <?php include '../panel_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="p-4">
                    <h1 class="mb-4">
                        <i class="fas fa-comments mr-3"></i>
                        Gestión de Comentarios
                    </h1>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="stats-number"><?= $stats['total'] ?></div>
                                <div class="text-muted">Total</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="stats-number text-warning"><?= $stats['pendientes'] ?></div>
                                <div class="text-muted">Pendientes</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="stats-number text-success"><?= $stats['aprobados'] ?></div>
                                <div class="text-muted">Aprobados</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="stats-number text-danger"><?= $stats['reportados'] ?></div>
                                <div class="text-muted">Reportados</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="stats-number text-secondary"><?= $stats['eliminados'] ?></div>
                                <div class="text-muted">Eliminados</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="filters-card">
                        <form method="GET" class="row">
                            <div class="col-md-4">
                                <label for="filtro">Filtrar por estado:</label>
                                <select name="filtro" id="filtro" class="form-control">
                                    <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                                    <option value="pendientes" <?= $filtro === 'pendientes' ? 'selected' : '' ?>>Pendientes</option>
                                    <option value="aprobados" <?= $filtro === 'aprobados' ? 'selected' : '' ?>>Aprobados</option>
                                    <option value="rechazados" <?= $filtro === 'rechazados' ? 'selected' : '' ?>>Rechazados</option>
                                    <option value="reportados" <?= $filtro === 'reportados' ? 'selected' : '' ?>>Reportados</option>
                                    <option value="eliminados" <?= $filtro === 'eliminados' ? 'selected' : '' ?>>Eliminados</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="busqueda">Buscar:</label>
                                <input type="text" name="busqueda" id="busqueda" class="form-control" 
                                       placeholder="Buscar por comentario, autor o publicación..." 
                                       value="<?= htmlspecialchars($busqueda) ?>">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Lista de comentarios -->
                    <?php if (empty($comentarios)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                            <h4>No hay comentarios</h4>
                            <p class="text-muted">No se encontraron comentarios con los filtros seleccionados.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="comentario-card">
                                <div class="comentario-header">
                                    <img src="<?= !empty($comentario['autor_foto']) ? 'data:image/jpeg;base64,' . $comentario['autor_foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($comentario['autor_nombre']) ?>" 
                                         class="comentario-avatar" alt="Avatar">
                                    <div class="comentario-meta">
                                        <h6 class="comentario-nombre"><?= htmlspecialchars($comentario['autor_nombre']) ?></h6>
                                        <div class="comentario-fecha">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?= date('d M Y H:i', strtotime($comentario['fecha_creacion'])) ?>
                                            <span class="ml-3">
                                                <i class="fas fa-newspaper mr-1"></i>
                                                <?= htmlspecialchars($comentario['publicacion_titulo']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch ($comentario['moderado']) {
                                            case 0:
                                                $status_class = 'status-pendiente';
                                                $status_text = 'Pendiente';
                                                break;
                                            case 1:
                                                $status_class = 'status-aprobado';
                                                $status_text = 'Aprobado';
                                                break;
                                            case 2:
                                                $status_class = 'status-rechazado';
                                                $status_text = 'Rechazado';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                        <?php if ($comentario['total_reportes'] > 0): ?>
                                            <span class="badge badge-warning ml-2">
                                                <i class="fas fa-flag"></i> <?= $comentario['total_reportes'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="comentario-texto">
                                    <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                                </div>
                                
                                <?php if ($comentario['total_reportes'] > 0): ?>
                                    <div class="reportes-info">
                                        <h6><i class="fas fa-flag mr-2"></i>Reportes (<?= $comentario['total_reportes'] ?>)</h6>
                                        <?php
                                        $reportes = json_decode($comentario['reportes'], true);
                                        if ($reportes):
                                            foreach ($reportes as $reporte):
                                        ?>
                                            <div class="reporte-item">
                                                <strong>Motivo:</strong> <?= ucfirst($reporte['motivo']) ?>
                                                <?php if (!empty($reporte['descripcion'])): ?>
                                                    <br><strong>Descripción:</strong> <?= htmlspecialchars($reporte['descripcion']) ?>
                                                <?php endif; ?>
                                                <br><small class="text-muted">
                                                    Reportado el <?= date('d M Y H:i', strtotime($reporte['fecha'])) ?>
                                                    - Estado: <?= ucfirst($reporte['estado']) ?>
                                                </small>
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="comentario-actions">
                                    <?php if ($comentario['moderado'] == 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="aprobar">
                                            <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                            <button type="submit" class="btn-moderation btn-aprobar">
                                                <i class="fas fa-check mr-1"></i> Aprobar
                                            </button>
                                        </form>
                                        
                                        <button class="btn-moderation btn-rechazar" onclick="mostrarModalRechazo(<?= $comentario['id'] ?>)">
                                            <i class="fas fa-times mr-1"></i> Rechazar
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($comentario['total_reportes'] > 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="marcar_reporte_revisado">
                                            <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                            <button type="submit" class="btn-moderation btn-reportes">
                                                <i class="fas fa-check-double mr-1"></i> Marcar Reportes Revisados
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($comentario['activo'] == 1): ?>
                                        <!-- Botones para comentarios activos -->
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('¿Estás seguro de eliminar este comentario?')">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                            <button type="submit" class="btn-moderation btn-eliminar">
                                                <i class="fas fa-trash mr-1"></i> Eliminar
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('¿Eliminar PERMANENTEMENTE este comentario? Esta acción no se puede deshacer.')">
                                            <input type="hidden" name="action" value="eliminar_permanente">
                                            <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                            <button type="submit" class="btn-moderation" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white;">
                                                <i class="fas fa-trash-alt mr-1"></i> Eliminar Permanente
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <!-- Botones para comentarios eliminados -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="restaurar">
                                            <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                            <button type="submit" class="btn-moderation" style="background: linear-gradient(45deg, #28a745, #20c997); color: white;">
                                                <i class="fas fa-undo mr-1"></i> Restaurar
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('¿Eliminar PERMANENTEMENTE este comentario? Esta acción no se puede deshacer.')">
                                            <input type="hidden" name="action" value="eliminar_permanente">
                                            <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                            <button type="submit" class="btn-moderation" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white;">
                                                <i class="fas fa-trash-alt mr-1"></i> Eliminar Permanente
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para rechazar comentario -->
    <div class="modal fade" id="modalRechazo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rechazar Comentario</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" id="formRechazo">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="rechazar">
                        <input type="hidden" name="comentario_id" id="comentario_id_rechazo">
                        <div class="form-group">
                            <label for="motivo_rechazo">Motivo del rechazo:</label>
                            <textarea class="form-control" name="motivo_rechazo" id="motivo_rechazo" 
                                      rows="3" placeholder="Explica por qué se rechaza este comentario..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Rechazar Comentario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function mostrarModalRechazo(comentarioId) {
            $('#comentario_id_rechazo').val(comentarioId);
            $('#motivo_rechazo').val('');
            $('#modalRechazo').modal('show');
        }
    </script>
</body>
</html>
