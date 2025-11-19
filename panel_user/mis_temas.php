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
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_foro = $_GET['foro'] ?? 'todos';

// Construir consulta base
$where_conditions = ["t.id_usuario = ?"];
$params = [$user_id];

if ($filtro_estado !== 'todos') {
    switch ($filtro_estado) {
        case 'activos':
            $where_conditions[] = "t.cerrado = 0";
            break;
        case 'cerrados':
            $where_conditions[] = "t.cerrado = 1";
            break;
        case 'fijados':
            $where_conditions[] = "t.fijado = 1";
            break;
    }
}

if ($filtro_foro !== 'todos') {
    $where_conditions[] = "t.id_foro = ?";
    $params[] = $filtro_foro;
}

$where_clause = implode(' AND ', $where_conditions);

// Obtener temas con paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Contar total de temas
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM temas_foro t 
    INNER JOIN foros f ON t.id_foro = f.id 
    WHERE $where_clause
");
$stmt->execute($params);
$total_temas = $stmt->fetchColumn();
$total_pages = ceil($total_temas / $limit);

// Obtener temas con información detallada
$params[] = $limit;
$params[] = $offset;
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        f.titulo as foro_titulo,
        f.id as foro_id,
        (SELECT COUNT(*) FROM respuestas_foro r WHERE r.id_tema = t.id) as total_respuestas,
        (SELECT MAX(r.fecha_creacion) FROM respuestas_foro r WHERE r.id_tema = t.id) as ultima_respuesta,
        (SELECT COUNT(*) FROM respuestas_foro r WHERE r.id_tema = t.id AND r.id_usuario = ?) as mis_respuestas
    FROM temas_foro t 
    INNER JOIN foros f ON t.id_foro = f.id 
    WHERE $where_clause
    ORDER BY t.fijado DESC, t.fecha_creacion DESC 
    LIMIT ? OFFSET ?
");
array_unshift($params, $user_id);
$stmt->execute($params);
$temas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de foros para el filtro
$stmt = $pdo->prepare("SELECT id, titulo FROM foros WHERE activo = 1 ORDER BY titulo");
$stmt->execute();
$foros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas del usuario
$stmt = $pdo->prepare("SELECT COUNT(*) FROM temas_foro WHERE id_usuario = ?");
$stmt->execute([$user_id]);
$total_mis_temas = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM temas_foro WHERE id_usuario = ? AND cerrado = 0");
$stmt->execute([$user_id]);
$temas_activos = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM temas_foro WHERE id_usuario = ? AND fijado = 1");
$stmt->execute([$user_id]);
$temas_fijados = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM respuestas_foro WHERE id_usuario = ?");
$stmt->execute([$user_id]);
$total_respuestas = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Temas - Panel de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .themes-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .theme-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .theme-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        .theme-card.pinned {
            border-left-color: #ffc107;
            background: linear-gradient(90deg, rgba(255, 193, 7, 0.05) 0%, white 10%);
        }
        .theme-card.closed {
            border-left-color: #6c757d;
            opacity: 0.8;
        }
        .theme-card.active {
            border-left-color: #28a745;
        }
        .theme-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f8f9fa;
        }
        .theme-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .theme-title a {
            color: inherit;
            text-decoration: none;
        }
        .theme-title a:hover {
            color: #667eea;
            text-decoration: none;
        }
        .theme-meta {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .theme-content {
            color: #6c757d;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .theme-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .theme-stats {
            display: flex;
            gap: 1rem;
        }
        .stat-item {
            text-align: center;
            color: #6c757d;
            font-size: 0.85rem;
        }
        .stat-number {
            font-weight: bold;
            color: #495057;
            display: block;
            font-size: 1.1rem;
        }
        .theme-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-theme {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-view {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        .btn-view:hover {
            color: white;
            transform: scale(1.05);
        }
        .btn-edit {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
        }
        .btn-edit:hover {
            color: white;
            transform: scale(1.05);
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
        .badge-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-pinned {
            background: #ffc107;
            color: #212529;
        }
        .badge-closed {
            background: #6c757d;
            color: white;
        }
        .badge-active {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2">
                <?php include 'user_sidebar.php'; ?>
            </div>
            <div class="col-md-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="themes-header">
                        <h1 class="mb-2">
                            <i class="fas fa-user-edit mr-3"></i>
                            Mis Temas
                        </h1>
                        <p class="mb-0 opacity-75">Gestiona todos los temas que has creado</p>
                    </div>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="stats-number"><?= $total_mis_temas ?></div>
                                <div class="stats-label">Total Temas</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #28a745, #20c997);">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                                <div class="stats-number"><?= $temas_activos ?></div>
                                <div class="stats-label">Activos</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                                    <i class="fas fa-thumbtack"></i>
                                </div>
                                <div class="stats-number"><?= $temas_fijados ?></div>
                                <div class="stats-label">Fijados</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon" style="background: linear-gradient(45deg, #17a2b8, #138496);">
                                    <i class="fas fa-reply"></i>
                                </div>
                                <div class="stats-number"><?= $total_respuestas ?></div>
                                <div class="stats-label">Mis Respuestas</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="filters-card">
                        <h5 class="mb-3">
                            <i class="fas fa-filter mr-2"></i>
                            Filtrar Temas
                        </h5>
                        <form method="GET" class="row">
                            <div class="col-md-4">
                                <label for="estado">Estado del Tema</label>
                                <select id="estado" name="estado" class="form-control">
                                    <option value="todos" <?= $filtro_estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                                    <option value="activos" <?= $filtro_estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                                    <option value="cerrados" <?= $filtro_estado === 'cerrados' ? 'selected' : '' ?>>Cerrados</option>
                                    <option value="fijados" <?= $filtro_estado === 'fijados' ? 'selected' : '' ?>>Fijados</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="foro">Foro</label>
                                <select id="foro" name="foro" class="form-control">
                                    <option value="todos" <?= $filtro_foro === 'todos' ? 'selected' : '' ?>>Todos los Foros</option>
                                    <?php foreach ($foros as $foro): ?>
                                        <option value="<?= $foro['id'] ?>" <?= $filtro_foro == $foro['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($foro['titulo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-2"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Lista de Temas -->
                    <?php if (empty($temas)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <h3>No tienes temas creados</h3>
                            <p>Comienza participando en los foros creando tu primer tema.</p>
                            <a href="foros/lista_foros.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus mr-2"></i>Crear Primer Tema
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($temas as $tema): ?>
                            <div class="theme-card <?= $tema['fijado'] ? 'pinned' : ($tema['cerrado'] ? 'closed' : 'active') ?>">
                                <div class="theme-header">
                                    <h5 class="theme-title">
                                        <a href="foros/ver_tema.php?id=<?= $tema['id'] ?>">
                                            <?php if ($tema['fijado']): ?>
                                                <span class="badge-status badge-pinned mr-2">
                                                    <i class="fas fa-thumbtack"></i> Fijado
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($tema['cerrado']): ?>
                                                <span class="badge-status badge-closed mr-2">
                                                    <i class="fas fa-lock"></i> Cerrado
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-status badge-active mr-2">
                                                    <i class="fas fa-play-circle"></i> Activo
                                                </span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($tema['titulo']) ?>
                                        </a>
                                    </h5>
                                    <div class="theme-meta">
                                        <i class="fas fa-comments mr-1"></i>
                                        Foro: <?= htmlspecialchars($tema['foro_titulo']) ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-clock mr-1"></i>
                                        Creado: <?= date('d M Y H:i', strtotime($tema['fecha_creacion'])) ?>
                                    </div>
                                    <div class="theme-content">
                                        <?= htmlspecialchars(substr($tema['contenido'], 0, 150)) ?>...
                                    </div>
                                </div>
                                <div class="theme-footer">
                                    <div class="theme-stats">
                                        <div class="stat-item">
                                            <span class="stat-number"><?= $tema['total_respuestas'] ?></span>
                                            Respuestas
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number"><?= $tema['mis_respuestas'] ?></span>
                                            Mis Respuestas
                                        </div>
                                        <div class="stat-item">
                                            <?php if ($tema['ultima_respuesta']): ?>
                                                <span class="stat-number"><?= date('d M', strtotime($tema['ultima_respuesta'])) ?></span>
                                                Última actividad
                                            <?php else: ?>
                                                <span class="stat-number">-</span>
                                                Sin respuestas
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="theme-actions">
                                        <a href="foros/ver_tema.php?id=<?= $tema['id'] ?>" class="btn btn-view btn-theme">
                                            <i class="fas fa-eye mr-1"></i>Ver
                                        </a>
                                        <?php if (!$tema['cerrado']): ?>
                                            <a href="foros/ver_tema.php?id=<?= $tema['id'] ?>" class="btn btn-edit btn-theme">
                                                <i class="fas fa-reply mr-1"></i>Responder
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Paginación -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Paginación de temas">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="mis_temas.php?page=<?= $page - 1 ?><?= $filtro_estado !== 'todos' ? '&estado=' . $filtro_estado : '' ?><?= $filtro_foro !== 'todos' ? '&foro=' . $filtro_foro : '' ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="mis_temas.php?page=<?= $i ?><?= $filtro_estado !== 'todos' ? '&estado=' . $filtro_estado : '' ?><?= $filtro_foro !== 'todos' ? '&foro=' . $filtro_foro : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="mis_temas.php?page=<?= $page + 1 ?><?= $filtro_estado !== 'todos' ? '&estado=' . $filtro_estado : '' ?><?= $filtro_foro !== 'todos' ? '&foro=' . $filtro_foro : '' ?>">
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
