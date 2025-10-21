<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['nombre_completo'] ?? 'Usuario';

// Obtener estadísticas del usuario
$stats = [];

// Contar temas creados por el usuario
$stmt = $pdo->prepare("SELECT COUNT(*) FROM temas_foro WHERE id_usuario = ?");
$stmt->execute([$user_id]);
$stats['temas_creados'] = $stmt->fetchColumn();

// Contar respuestas del usuario
$stmt = $pdo->prepare("SELECT COUNT(*) FROM respuestas_foro WHERE id_usuario = ?");
$stmt->execute([$user_id]);
$stats['respuestas'] = $stmt->fetchColumn();

// Contar notificaciones no leídas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leida = 0");
$stmt->execute([$user_id]);
$stats['notificaciones'] = $stmt->fetchColumn();

// Obtener publicaciones recientes de administradores
$stmt = $pdo->prepare("
    SELECT c.*, u.nombre_completo as admin_nombre 
    FROM contenidos c 
    INNER JOIN usuarios u ON c.id_admin = u.email 
    WHERE u.rol = 'administrador' 
    ORDER BY c.fecha_creacion DESC 
    LIMIT 5
");
$stmt->execute();
$publicaciones_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener temas recientes del usuario
$stmt = $pdo->prepare("
    SELECT t.*, f.titulo as foro_titulo 
    FROM temas_foro t 
    INNER JOIN foros f ON t.id_foro = f.id 
    WHERE t.id_usuario = ? 
    ORDER BY t.fecha_creacion DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$temas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividad reciente del usuario
$stmt = $pdo->prepare("
    SELECT 'tema' as tipo, t.titulo as contenido, t.fecha_creacion as fecha, f.titulo as contexto
    FROM temas_foro t 
    INNER JOIN foros f ON t.id_foro = f.id 
    WHERE t.id_usuario = ?
    UNION ALL
    SELECT 'respuesta' as tipo, r.contenido, r.fecha_creacion as fecha, CONCAT(f.titulo, ' - ', t.titulo) as contexto
    FROM respuestas_foro r 
    INNER JOIN temas_foro t ON r.id_tema = t.id 
    INNER JOIN foros f ON t.id_foro = f.id 
    WHERE r.id_usuario = ?
    ORDER BY fecha DESC 
    LIMIT 10
");
$stmt->execute([$user_id, $user_id]);
$actividad_reciente = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Panel de Usuario - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            border: none;
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
            margin-bottom: 1rem;
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
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        .content-card h5 {
            color: #495057;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .list-item {
            padding: 1rem;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.3s ease;
        }
        .list-item:hover {
            background-color: #f8f9fa;
        }
        .list-item:last-child {
            border-bottom: none;
        }
        .activity-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 0.9rem;
        }
        .btn-action {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            color: white;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
                    <!-- Header del Dashboard -->
                    <div class="dashboard-header">
                        <h1 class="mb-2">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Bienvenido, <?= htmlspecialchars($user_name) ?>
                        </h1>
                        <p class="mb-0 opacity-75">Aquí tienes un resumen de tu actividad en la plataforma</p>
                    </div>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="stats-card text-center">
                                <div class="stats-icon mx-auto" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="stats-number"><?= $stats['temas_creados'] ?></div>
                                <div class="stats-label">Temas Creados</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stats-card text-center">
                                <div class="stats-icon mx-auto" style="background: linear-gradient(45deg, #28a745, #20c997);">
                                    <i class="fas fa-reply"></i>
                                </div>
                                <div class="stats-number"><?= $stats['respuestas'] ?></div>
                                <div class="stats-label">Respuestas</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stats-card text-center">
                                <div class="stats-icon mx-auto" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="stats-number"><?= $stats['notificaciones'] ?></div>
                                <div class="stats-label">Notificaciones</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Publicaciones Recientes -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5>
                                    <i class="fas fa-newspaper text-primary mr-2"></i>
                                    Publicaciones Recientes
                                </h5>
                                <?php if (empty($publicaciones_recientes)): ?>
                                    <p class="text-muted">No hay publicaciones disponibles.</p>
                                <?php else: ?>
                                    <?php foreach ($publicaciones_recientes as $pub): ?>
                                        <div class="list-item">
                                            <h6 class="mb-1">
                                                <a href="ver_publicacion.php?id=<?= $pub['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($pub['titulo']) ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-user mr-1"></i>
                                                <?= htmlspecialchars($pub['admin_nombre']) ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?= date('d M Y', strtotime($pub['fecha_creacion'])) ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="publicaciones.php" class="btn-action">
                                            <i class="fas fa-eye mr-2"></i>Ver Todas
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Mis Temas Recientes -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5>
                                    <i class="fas fa-user-edit text-success mr-2"></i>
                                    Mis Temas Recientes
                                </h5>
                                <?php if (empty($temas_recientes)): ?>
                                    <p class="text-muted">No has creado temas aún.</p>
                                    <div class="text-center">
                                        <a href="foros/lista_foros.php" class="btn-action">
                                            <i class="fas fa-plus mr-2"></i>Crear Primer Tema
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($temas_recientes as $tema): ?>
                                        <div class="list-item">
                                            <h6 class="mb-1">
                                                <a href="foros/ver_tema.php?id=<?= $tema['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($tema['titulo']) ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-comments mr-1"></i>
                                                <?= htmlspecialchars($tema['foro_titulo']) ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?= date('d M Y', strtotime($tema['fecha_creacion'])) ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="mis_temas.php" class="btn-action">
                                            <i class="fas fa-list mr-2"></i>Ver Todos
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad Reciente -->
                    <div class="content-card">
                        <h5>
                            <i class="fas fa-history text-info mr-2"></i>
                            Actividad Reciente
                        </h5>
                        <?php if (empty($actividad_reciente)): ?>
                            <p class="text-muted">No hay actividad reciente.</p>
                        <?php else: ?>
                            <?php foreach ($actividad_reciente as $actividad): ?>
                                <div class="activity-item">
                                    <div class="activity-icon" style="background: <?= $actividad['tipo'] === 'tema' ? 'linear-gradient(45deg, #667eea, #764ba2)' : 'linear-gradient(45deg, #28a745, #20c997)' ?>; color: white;">
                                        <i class="fas <?= $actividad['tipo'] === 'tema' ? 'fa-comment-plus' : 'fa-reply' ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-medium">
                                            <?= $actividad['tipo'] === 'tema' ? 'Creaste un tema' : 'Respondiste en un tema' ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars(substr($actividad['contenido'], 0, 50)) ?>...
                                        </div>
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?= date('d M Y H:i', strtotime($actividad['fecha'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="actividad.php" class="btn-action">
                                    <i class="fas fa-history mr-2"></i>Ver Actividad Completa
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
