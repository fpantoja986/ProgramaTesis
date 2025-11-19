<?php
session_start();

// Verificación básica de sesión
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../../login.php');
    exit;
}

include '../../db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['nombre_completo'] ?? 'Usuario';

// Obtener foros de la base de datos
try {
    $stmt = $pdo->query("
        SELECT f.*, u.nombre_completo as creador_nombre,
               (SELECT COUNT(*) FROM temas_foro tf WHERE tf.id_foro = f.id) as total_temas,
               (SELECT COUNT(*) FROM respuestas_foro rf 
                INNER JOIN temas_foro tf ON rf.id_tema = tf.id 
                WHERE tf.id_foro = f.id) as total_respuestas
        FROM foros f 
        LEFT JOIN usuarios u ON f.id_admin = u.id 
        ORDER BY f.fecha_creacion DESC
    ");
    $foros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $foros = [];
    $error_message = "Error al cargar foros: " . $e->getMessage();
}
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
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container {
            display: flex;
            min-height: 100vh;
        }
        .content-area {
            flex: 1;
            padding: 20px;
            background-color: #f8f9fc;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 30px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        .title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 25px;
        }
        .welcome {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px 25px;
            border-radius: 25px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }
        .forum-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .forum-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .forum-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            padding: 25px;
            color: white;
        }
        .forum-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .forum-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .forum-creator {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .forum-body {
            padding: 25px;
        }
        .forum-description {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .btn-forum {
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
        .btn-forum:hover {
            color: white;
            text-decoration: none;
            transform: scale(1.05);
        }
        .nav-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .nav-link {
            color: #495057;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
            margin: 0 10px;
        }
        .nav-link:hover {
            background: #f8f9fa;
            color: #495057;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <div class="col-md-2">
            <?php include '../user_sidebar.php'; ?>
        </div>
        
        <!-- Contenido principal -->
        <div class="content-area">
            <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="title">
                <i class="fas fa-comments"></i>
                Foros de Discusión
            </h1>
            <p class="subtitle">Conecta, discute y comparte ideas con la comunidad</p>
            
        </div>

       
        <!-- Foros -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($foros)): ?>
            <div class="forum-card">
                <div class="forum-body text-center">
                    <div class="forum-icon mb-3">
                        <i class="fas fa-comments" style="font-size: 3rem; color: #6c757d;"></i>
                    </div>
                    <h4>No hay foros disponibles</h4>
                    <p class="text-muted">Los administradores aún no han creado foros de discusión.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($foros as $foro): ?>
                <div class="forum-card">
                    <div class="forum-header">
                        <div class="forum-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="forum-title"><?= htmlspecialchars($foro['titulo']) ?></h3>
                        <div class="forum-creator">
                            <i class="fas fa-user mr-1"></i>
                            Por <?= htmlspecialchars($foro['creador_nombre'] ?? 'Administrador') ?>
                        </div>
                    </div>
                    <div class="forum-body">
                        <p class="forum-description">
                            <?= htmlspecialchars($foro['descripcion']) ?>
                        </p>
                        
                        <!-- Estadísticas del foro -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <small class="text-muted">
                                    <i class="fas fa-comment mr-1"></i>
                                    <?= $foro['total_temas'] ?> temas
                                </small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">
                                    <i class="fas fa-reply mr-1"></i>
                                    <?= $foro['total_respuestas'] ?> respuestas
                                </small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Creado <?= date('d M Y', strtotime($foro['fecha_creacion'])) ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <a href="ver_foro.php?id=<?= $foro['id'] ?>" class="btn-forum">
                                <i class="fas fa-arrow-right mr-2"></i>Entrar al Foro
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>