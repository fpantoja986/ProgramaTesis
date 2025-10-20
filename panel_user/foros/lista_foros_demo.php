<?php
// Versión completamente independiente de la base de datos
session_start();

// Verificar sesión
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../login.php');
    exit;
}

$user_name = $_SESSION['nombre_completo'] ?? 'Usuario';

// Datos de ejemplo para foros (sin dependencia de base de datos)
$foros = [
    [
        'id' => 1,
        'titulo' => 'Foro General',
        'descripcion' => 'Discusiones generales sobre el sistema y temas diversos. Aquí puedes compartir ideas, hacer preguntas y participar en conversaciones con otros usuarios de la plataforma.',
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'creador_nombre' => 'Administrador',
        'total_temas' => 0,
        'total_respuestas' => 0,
        'ultima_actividad' => null
    ],
    [
        'id' => 2,
        'titulo' => 'Soporte Técnico',
        'descripcion' => 'Ayuda y soporte técnico para usuarios del sistema. Si tienes problemas técnicos, necesitas ayuda con alguna funcionalidad o tienes dudas sobre el uso de la plataforma, este es el lugar indicado.',
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'creador_nombre' => 'Administrador',
        'total_temas' => 0,
        'total_respuestas' => 0,
        'ultima_actividad' => null
    ],
    [
        'id' => 3,
        'titulo' => 'Sugerencias',
        'descripcion' => 'Propuestas y mejoras para el sistema. Comparte tus ideas para hacer que la plataforma sea mejor para todos. Tus sugerencias son muy valiosas para el desarrollo continuo.',
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'creador_nombre' => 'Administrador',
        'total_temas' => 0,
        'total_respuestas' => 0,
        'ultima_actividad' => null
    ],
    [
        'id' => 4,
        'titulo' => 'Anuncios',
        'descripcion' => 'Información importante y anuncios oficiales del sistema. Aquí encontrarás actualizaciones, cambios importantes y noticias relevantes para todos los usuarios.',
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'creador_nombre' => 'Administrador',
        'total_temas' => 0,
        'total_respuestas' => 0,
        'ultima_actividad' => null
    ]
];
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
        .demo-notice {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
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
                Bienvenido, <?= htmlspecialchars($user_name) ?>
            </div>
        </div>

        <!-- Aviso de Demo -->
        <div class="demo-notice">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Modo Demostración:</strong> Estos son foros de ejemplo. Para crear foros reales, ejecuta el script SQL de creación de tablas.
        </div>

        <!-- Barra de Navegación -->
        <div class="navigation-bar">
            <div class="nav-links">
                <a href="lista_foros.php" class="nav-link active">
                    <i class="fas fa-home mr-2"></i>Todos los Foros
                </a>
                <a href="../mis_temas.php" class="nav-link">
                    <i class="fas fa-user-edit mr-2"></i>Mis Temas
                </a>
                <a href="../user_dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
            </div>
        </div>

        <!-- Grid de Foros -->
        <div class="forum-grid">
            <?php foreach ($foros as $foro): ?>
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
                            <?= nl2br(htmlspecialchars($foro['descripcion'])) ?>
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
                                <i class="fas fa-calendar mr-1"></i>
                                Creado: <?= date('d M Y', strtotime($foro['fecha_creacion'])) ?>
                            </div>
                            <small>
                                <i class="fas fa-info-circle mr-1"></i>
                                Modo Demo
                            </small>
                        </div>

                        <div class="text-center mt-3">
                            <a href="#" class="btn-enter-forum" onclick="alert('Modo demostración: Los foros reales estarán disponibles después de configurar la base de datos.')">
                                <i class="fas fa-arrow-right mr-2"></i>Entrar al Foro
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
