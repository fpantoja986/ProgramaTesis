<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['nombre_completo'] ?? 'Usuario';

// Obtener filtros
$filtro_tipo = $_GET['tipo'] ?? 'todos';
$filtro_fecha = $_GET['fecha'] ?? 'todos';

// Construir consulta base
$where_conditions = ["id_usuario = ?"];
$params = [$user_id];

if ($filtro_tipo !== 'todos') {
    $where_conditions[] = "tipo = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_fecha !== 'todos') {
    switch ($filtro_fecha) {
        case 'hoy':
            $where_conditions[] = "DATE(fecha_creacion) = CURDATE()";
            break;
        case 'semana':
            $where_conditions[] = "fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'mes':
            $where_conditions[] = "fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Obtener actividad con paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Contar total de actividades
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM (
        SELECT 'tema' as tipo, fecha_creacion FROM temas_foro WHERE $where_clause
        UNION ALL
        SELECT 'respuesta' as tipo, fecha_creacion FROM respuestas_foro WHERE $where_clause
    ) as actividad
");
$stmt->execute($params);
$total_actividades = $stmt->fetchColumn();
$total_pages = ceil($total_actividades / $limit);

// Obtener actividad detallada
$stmt = $pdo->prepare("
    SELECT 
        'tema' as tipo,
        t.id,
        t.titulo as contenido,
        t.fecha_creacion,
        f.titulo as contexto,
        f.id as contexto_id,
        'foro' as contexto_tipo,
        (SELECT COUNT(*) FROM respuestas_foro WHERE id_tema = t.id) as respuestas_count
    FROM temas_foro t 
    INNER JOIN foros f ON t.id_foro = f.id 
    WHERE $where_clause
    
    UNION ALL
    
    SELECT 
        'respuesta' as tipo,
        r.id,
        LEFT(r.contenido, 100) as contenido,
        r.fecha_creacion,
        CONCAT(f.titulo, ' - ', t.titulo) as contexto,
        t.id as contexto_id,
        'tema' as contexto_tipo,
        0 as respuestas_count
    FROM respuestas_foro r 
    INNER JOIN temas_foro t ON r.id_tema = t.id 
    INNER JOIN foros f ON t.id_foro = f.id 
    WHERE $where_clause
    
    ORDER BY fecha_creacion DESC 
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas del usuario
$stmt = $pdo->prepare("SELECT COUNT(*) FROM temas_foro WHERE id_usuario = ?");
$stmt->execute([$user_id]);
$total_temas = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM respuestas_foro WHERE id_usuario = ?");
$stmt->execute([$user_id]);
$total_respuestas = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT fecha_registro FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$fecha_registro = $stmt->fetchColumn();

// Calcular días como miembro
$dias_miembro = (time() - strtotime($fecha_registro)) / (60 * 60 * 24);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Actividad - Panel de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .activity-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .activity-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        .activity-card.tema {
            border-left-color: #667eea;
        }
        .activity-card.respuesta {
            border-left-color: #28a745;
        }
        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            margin-right: 1rem;
        }
        .activity-content {
            flex: 1;
        }
        .activity-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }
        .activity-description {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .activity-meta {
            font-size: 0.85rem;
            color: #adb5bd;
        }
        .activity-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .activity-link:hover {
            color: #764ba2;
            text-decoration: none;
        }
        .filters-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin: 0 auto 1rem;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #495057;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 12px;
            width: 2px;
            height: calc(100% + 1rem);
            background: #e9ecef;
        }
        .timeline-item:last-child::after {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <?php include 'user_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="p-4">
                    <!-- Header -->
                    <div class="activity-header">
                        <h1 class="mb-2">
                            <i class="fas fa-history mr-3"></i>
                            Mi Actividad
                        </h1>
                        <p class="mb-0 opacity-75">Historial completo de tu participación en la plataforma</p>
                    </div>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="stats-number"><?= $total_temas ?></div>
                                <div class="stats-label">Temas Creados</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #28a745, #20c997);">
                                    <i class="fas fa-reply"></i>
                                </div>
                                <div class="stats-number"><?= $total_respuestas ?></div>
                                <div class="stats-label">Respuestas</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="stats-number"><?= floor($dias_miembro) ?></div>
                                <div class="stats-label">Días como Miembro</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #17a2b8, #138496);">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stats-number"><?= $total_temas + $total_respuestas ?></div>
                                <div class="stats-label">Total Actividades</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="filters-card">
                        <h5 class="mb-3">
                            <i class="fas fa-filter mr-2"></i>
                            Filtrar Actividad
                        </h5>
                        <form method="GET" class="row">
                            <div class="col-md-4">
                                <label for="tipo">Tipo de Actividad</label>
                                <select id="tipo" name="tipo" class="form-control">
                                    <option value="todos" <?= $filtro_tipo === 'todos' ? 'selected' : '' ?>>Todos</option>
                                    <option value="tema" <?= $filtro_tipo === 'tema' ? 'selected' : '' ?>>Temas</option>
                                    <option value="respuesta" <?= $filtro_tipo === 'respuesta' ? 'selected' : '' ?>>Respuestas</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha">Período</label>
                                <select id="fecha" name="fecha" class="form-control">
                                    <option value="todos" <?= $filtro_fecha === 'todos' ? 'selected' : '' ?>>Todos</option>
                                    <option value="hoy" <?= $filtro_fecha === 'hoy' ? 'selected' : '' ?>>Hoy</option>
                                    <option value="semana" <?= $filtro_fecha === 'semana' ? 'selected' : '' ?>>Esta Semana</option>
                                    <option value="mes" <?= $filtro_fecha === 'mes' ? 'selected' : '' ?>>Este Mes</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-2"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Lista de Actividades -->
                    <?php if (empty($actividades)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h3>No hay actividad</h3>
                            <p>No tienes actividad registrada con los filtros seleccionados.</p>
                            <a href="foros/lista_foros.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus mr-2"></i>Crear Primer Tema
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($actividades as $actividad): ?>
                                <div class="activity-card <?= $actividad['tipo'] ?>">
                                    <div class="d-flex align-items-start p-3">
                                        <div class="activity-icon" 
                                             style="background: <?= $actividad['tipo'] === 'tema' ? 'linear-gradient(45deg, #667eea, #764ba2)' : 'linear-gradient(45deg, #28a745, #20c997)' ?>;">
                                            <i class="fas fa-<?= $actividad['tipo'] === 'tema' ? 'comment-plus' : 'reply' ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <?php if ($actividad['tipo'] === 'tema'): ?>
                                                    <i class="fas fa-comment-plus mr-1"></i>
                                                    Creaste un tema: 
                                                    <a href="foros/ver_tema.php?id=<?= $actividad['id'] ?>" class="activity-link">
                                                        <?= htmlspecialchars($actividad['contenido']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <i class="fas fa-reply mr-1"></i>
                                                    Respondiste en: 
                                                    <a href="foros/ver_tema.php?id=<?= $actividad['contexto_id'] ?>" class="activity-link">
                                                        <?= htmlspecialchars($actividad['contexto']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="activity-description">
                                                <?php if ($actividad['tipo'] === 'tema'): ?>
                                                    <i class="fas fa-comments mr-1"></i>
                                                    Foro: <?= htmlspecialchars($actividad['contexto']) ?>
                                                    <?php if ($actividad['respuestas_count'] > 0): ?>
                                                        <span class="badge badge-primary ml-2">
                                                            <?= $actividad['respuestas_count'] ?> respuesta<?= $actividad['respuestas_count'] > 1 ? 's' : '' ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($actividad['contenido']) ?>...
                                                <?php endif; ?>
                                            </div>
                                            <div class="activity-meta">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?= date('d M Y H:i', strtotime($actividad['fecha_creacion'])) ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-tag mr-1"></i>
                                                <?= ucfirst($actividad['tipo']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Paginación -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Paginación de actividad">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="actividad.php?page=<?= $page - 1 ?><?= $filtro_tipo !== 'todos' ? '&tipo=' . $filtro_tipo : '' ?><?= $filtro_fecha !== 'todos' ? '&fecha=' . $filtro_fecha : '' ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="actividad.php?page=<?= $i ?><?= $filtro_tipo !== 'todos' ? '&tipo=' . $filtro_tipo : '' ?><?= $filtro_fecha !== 'todos' ? '&fecha=' . $filtro_fecha : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="actividad.php?page=<?= $page + 1 ?><?= $filtro_tipo !== 'todos' ? '&tipo=' . $filtro_tipo : '' ?><?= $filtro_fecha !== 'todos' ? '&fecha=' . $filtro_fecha : '' ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
