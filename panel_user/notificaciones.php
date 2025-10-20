<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['nombre_completo'] ?? 'Usuario';

// Procesar acciones de notificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_read':
                $notif_id = (int)$_POST['notif_id'];
                $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND id_usuario = ?");
                $stmt->execute([$notif_id, $user_id]);
                break;
                
            case 'mark_all_read':
                $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id_usuario = ?");
                $stmt->execute([$user_id]);
                break;
                
            case 'delete':
                $notif_id = (int)$_POST['notif_id'];
                $stmt = $pdo->prepare("DELETE FROM notificaciones WHERE id = ? AND id_usuario = ?");
                $stmt->execute([$notif_id, $user_id]);
                break;
        }
        header("Location: notificaciones.php");
        exit();
    }
}

// Obtener filtro
$filtro = $_GET['filtro'] ?? 'todas';
$where_clause = "WHERE id_usuario = ?";
$params = [$user_id];

if ($filtro === 'no_leidas') {
    $where_clause .= " AND leida = 0";
} elseif ($filtro === 'leidas') {
    $where_clause .= " AND leida = 1";
}

// Obtener notificaciones con paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Contar total de notificaciones
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones $where_clause");
$stmt->execute($params);
$total_notificaciones = $stmt->fetchColumn();
$total_pages = ceil($total_notificaciones / $limit);

// Obtener notificaciones
$params[] = $limit;
$params[] = $offset;
$stmt = $pdo->prepare("
    SELECT * FROM notificaciones 
    $where_clause 
    ORDER BY fecha_creacion DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar notificaciones no leídas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leida = 0");
$stmt->execute([$user_id]);
$notificaciones_no_leidas = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Panel de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .notifications-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .notification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        .notification-card.unread {
            border-left-color: #667eea;
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, white 10%);
        }
        .notification-icon {
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
        .notification-content {
            flex: 1;
        }
        .notification-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }
        .notification-message {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .notification-meta {
            font-size: 0.85rem;
            color: #adb5bd;
        }
        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-notification {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-mark-read {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        .btn-mark-read:hover {
            color: white;
            transform: scale(1.05);
        }
        .btn-delete {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
        }
        .btn-delete:hover {
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
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            margin-bottom: 2rem;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
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
                    <div class="notifications-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="mb-2">
                                    <i class="fas fa-bell mr-3"></i>
                                    Notificaciones
                                </h1>
                                <p class="mb-0 opacity-75">Mantente al día con las últimas actividades</p>
                            </div>
                            <div class="text-right">
                                <div class="stats-number"><?= $notificaciones_no_leidas ?></div>
                                <div class="stats-label">No leídas</div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?= $total_notificaciones ?></div>
                                <div class="stats-label">Total</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?= $notificaciones_no_leidas ?></div>
                                <div class="stats-label">No Leídas</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?= $total_notificaciones - $notificaciones_no_leidas ?></div>
                                <div class="stats-label">Leídas</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="filters-card">
                        <h5 class="mb-3">
                            <i class="fas fa-filter mr-2"></i>
                            Filtrar Notificaciones
                        </h5>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="notificaciones.php" 
                               class="btn btn-outline-primary btn-sm <?= $filtro === 'todas' ? 'active' : '' ?>">
                                <i class="fas fa-list mr-1"></i>Todas
                            </a>
                            <a href="notificaciones.php?filtro=no_leidas" 
                               class="btn btn-outline-warning btn-sm <?= $filtro === 'no_leidas' ? 'active' : '' ?>">
                                <i class="fas fa-exclamation-circle mr-1"></i>No Leídas
                            </a>
                            <a href="notificaciones.php?filtro=leidas" 
                               class="btn btn-outline-success btn-sm <?= $filtro === 'leidas' ? 'active' : '' ?>">
                                <i class="fas fa-check-circle mr-1"></i>Leídas
                            </a>
                        </div>
                        <?php if ($notificaciones_no_leidas > 0): ?>
                        <div class="mt-3">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="mark_all_read">
                                <button type="submit" class="btn btn-success btn-sm" 
                                        onclick="return confirm('¿Marcar todas las notificaciones como leídas?')">
                                    <i class="fas fa-check-double mr-1"></i>Marcar Todas como Leídas
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Lista de Notificaciones -->
                    <?php if (empty($notificaciones)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No hay notificaciones</h3>
                            <p>No tienes notificaciones <?= $filtro === 'todas' ? '' : $filtro ?>.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notificaciones as $notif): ?>
                            <div class="notification-card <?= !$notif['leida'] ? 'unread' : '' ?>">
                                <div class="d-flex align-items-start p-3">
                                    <div class="notification-icon" 
                                         style="background: <?= $notif['leida'] ? 'linear-gradient(45deg, #6c757d, #495057)' : 'linear-gradient(45deg, #667eea, #764ba2)' ?>;">
                                        <i class="fas fa-<?= $notif['tipo'] === 'respuesta' ? 'reply' : ($notif['tipo'] === 'tema' ? 'comment' : 'info') ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">
                                            <?= htmlspecialchars($notif['titulo']) ?>
                                        </div>
                                        <div class="notification-message">
                                            <?= nl2br(htmlspecialchars($notif['mensaje'])) ?>
                                        </div>
                                        <div class="notification-meta">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?= date('d M Y H:i', strtotime($notif['fecha_creacion'])) ?>
                                            <?php if (!$notif['leida']): ?>
                                                <span class="badge badge-primary ml-2">Nueva</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="notification-actions">
                                        <?php if (!$notif['leida']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                                <button type="submit" class="btn btn-mark-read btn-notification">
                                                    <i class="fas fa-check mr-1"></i>Leída
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('¿Eliminar esta notificación?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                            <button type="submit" class="btn btn-delete btn-notification">
                                                <i class="fas fa-trash mr-1"></i>Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Paginación -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Paginación de notificaciones">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="notificaciones.php?page=<?= $page - 1 ?><?= $filtro !== 'todas' ? '&filtro=' . $filtro : '' ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="notificaciones.php?page=<?= $i ?><?= $filtro !== 'todas' ? '&filtro=' . $filtro : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="notificaciones.php?page=<?= $page + 1 ?><?= $filtro !== 'todas' ? '&filtro=' . $filtro : '' ?>">
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
