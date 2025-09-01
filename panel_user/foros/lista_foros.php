<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Obtener todos los foros activos con estadísticas
$stmt = $pdo->prepare("
    SELECT 
        f.id,
        f.titulo,
        f.descripcion,
        f.fecha_creacion,
        u.nombre_completo as creador_nombre,
        (SELECT COUNT(*) FROM temas_foro t WHERE t.id_foro = f.id) as total_temas,
        (SELECT COUNT(*) FROM respuestas_foro r 
         INNER JOIN temas_foro t ON r.id_tema = t.id 
         WHERE t.id_foro = f.id) as total_respuestas,
        (SELECT MAX(GREATEST(
            COALESCE(t.fecha_creacion, '1970-01-01'),
            COALESCE((SELECT MAX(r.fecha_creacion) FROM respuestas_foro r WHERE r.id_tema = t.id), '1970-01-01')
        )) FROM temas_foro t WHERE t.id_foro = f.id) as ultima_actividad
    FROM foros f 
    INNER JOIN usuarios u ON f.id_admin = u.id 
    WHERE f.activo = 1
    ORDER BY f.fecha_creacion DESC
");
$stmt->execute();
$foros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foros de Discusión</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .forums-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .main-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding: 40px 20px;
        }

        .main-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .main-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 25px;
        }

        .user-welcome {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px 25px;
            border-radius: 25px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }

        .forum-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .forum-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            border: none;
        }

        .forum-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .forum-card-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            padding: 25px;
            color: white;
            position: relative;
        }

        .forum-card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .forum-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .forum-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .forum-creator {
            font-size: 0.9rem;
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }

        .forum-card-body {
            padding: 25px;
        }

        .forum-description {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .forum-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fc;
            border-radius: 10px;
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .forum-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .btn-enter-forum {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-enter-forum:hover {
            color: white;
            text-decoration: none;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .empty-forums {
            text-align: center;
            color: white;
            padding: 60px 20px;
        }

        .empty-forums i {
            font-size: 5rem;
            margin-bottom: 25px;
            opacity: 0.7;
        }

        .activity-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .activity-recent {
            background-color: #28a745;
            animation: pulse 2s infinite;
        }

        .activity-old {
            background-color: #ffc107;
        }

        .activity-inactive {
            background-color: #6c757d;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .navigation-bar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 30px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="forums-container">
        <!-- Header Principal -->
        <div class="main-header">
            <h1 class="main-title">
                <i class="fas fa-comments"></i>
                Foros de Discusión
            </h1>
            <p class="main-subtitle">Conecta, discute y comparte ideas con la comunidad</p>
            <div class="user-welcome">
                <i class="fas fa-user-circle mr-2"></i>
                Bienvenido, <?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario') ?>
            </div>
        </div>

        <!-- Barra de Navegación -->
        <div class="navigation-bar">
            <div class="nav-links">
                <a href="lista_foros.php" class="nav-link active">
                    <i class="fas fa-home mr-2"></i>Todos los Foros
                </a>
                <a href="mis_temas.php" class="nav-link">
                    <i class="fas fa-user-edit mr-2"></i>Mis Temas
                </a>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                    <a href="gestionar_foros.php" class="nav-link">
                        <i class="fas fa-cogs mr-2"></i>Gestionar Foros
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Grid de Foros -->
        <?php if (empty($foros)): ?>
            <div class="empty-forums">
                <i class="fas fa-comments"></i>
                <h3>No hay foros disponibles</h3>
                <p>Los administradores aún no han creado foros para la discusión.</p>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                    <a href="gestionar_foros.php" class="btn-enter-forum mt-3">
                        <i class="fas fa-plus mr-2"></i>Crear Primer Foro
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="forum-grid">
                <?php foreach ($foros as $foro): ?>
                    <?php
                    // Determinar actividad
                    $actividad = 'inactive';
                    if ($foro['ultima_actividad']) {
                        $dias = (time() - strtotime($foro['ultima_actividad'])) / (60 * 60 * 24);
                        if ($dias <= 1) {
                            $actividad = 'recent';
                        } elseif ($dias <= 7) {
                            $actividad = 'old';
                        }
                    }
                    ?>
                    <div class="card forum-card">
                        <div class="forum-card-header">
                            <div class="forum-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3 class="forum-title"><?= htmlspecialchars($foro['titulo']) ?></h3>
                            <div class="forum-creator">
                                <i class="fas fa-user mr-1"></i>
                                Por <?= htmlspecialchars($foro['creador_nombre']) ?>
                            </div>
                        </div>
                        <div class="forum-card-body">
                            <p class="forum-description">
                                <?= nl2br(htmlspecialchars($foro['descripcion'] ?: 'Sin descripción disponible')) ?>
                            </p>
                            
                            <div class="forum-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $foro['total_temas'] ?></span>
                                    <span class="stat-label">Temas</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= $foro['total_respuestas'] ?></span>
                                    <span class="stat-label">Respuestas</span>
                                </div>
                            </div>

                            <div class="forum-meta">
                                <div>
                                    <span class="activity-indicator activity-<?= $actividad ?>"></span>
                                    <?php if ($foro['ultima_actividad']): ?>
                                        Última actividad: <?= date('d M', strtotime($foro['ultima_actividad'])) ?>
                                    <?php else: ?>
                                        Sin actividad reciente
                                    <?php endif; ?>
                                </div>
                                <small>
                                    <i class="fas fa-calendar mr-1"></i>
                                    <?= date('d M Y', strtotime($foro['fecha_creacion'])) ?>
                                </small>
                            </div>

                            <div class="text-center mt-3">
                                <a href="ver_foro.php?id=<?= $foro['id'] ?>" class="btn-enter-forum">
                                    <i class="fas fa-arrow-right mr-2"></i>Entrar al Foro
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>