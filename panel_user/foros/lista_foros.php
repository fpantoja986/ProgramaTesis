<?php
session_start();

// Verificación básica de sesión
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../login.php');
    exit;
}

$user_name = $_SESSION['nombre_completo'] ?? 'Usuario';
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
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding: 40px 20px;
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
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
            margin: 0 10px;
        }
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="title">
                <i class="fas fa-comments"></i>
                Foros de Discusión
            </h1>
            <p class="subtitle">Conecta, discute y comparte ideas con la comunidad</p>
            <div class="welcome">
                <i class="fas fa-user-circle mr-2"></i>
                Bienvenido, <?= htmlspecialchars($user_name) ?>
            </div>
        </div>

        <!-- Navegación -->
        <div class="nav-bar">
            <a href="lista_foros.php" class="nav-link">
                <i class="fas fa-home mr-2"></i>Todos los Foros
            </a>
            <a href="../mis_temas.php" class="nav-link">
                <i class="fas fa-user-edit mr-2"></i>Mis Temas
            </a>
            <a href="../user_dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </a>
        </div>

        <!-- Foros -->
        <div class="forum-card">
            <div class="forum-header">
                <div class="forum-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="forum-title">Foro General</h3>
                <div class="forum-creator">
                    <i class="fas fa-user mr-1"></i>
                    Por Administrador
                </div>
            </div>
            <div class="forum-body">
                <p class="forum-description">
                    Discusiones generales sobre el sistema y temas diversos. Aquí puedes compartir ideas, hacer preguntas y participar en conversaciones con otros usuarios de la plataforma.
                </p>
                <div class="text-center">
                    <a href="#" class="btn-forum" onclick="alert('Modo demostración: Los foros reales estarán disponibles después de configurar la base de datos.')">
                        <i class="fas fa-arrow-right mr-2"></i>Entrar al Foro
                    </a>
                </div>
            </div>
        </div>

        <div class="forum-card">
            <div class="forum-header">
                <div class="forum-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3 class="forum-title">Soporte Técnico</h3>
                <div class="forum-creator">
                    <i class="fas fa-user mr-1"></i>
                    Por Administrador
                </div>
            </div>
            <div class="forum-body">
                <p class="forum-description">
                    Ayuda y soporte técnico para usuarios del sistema. Si tienes problemas técnicos, necesitas ayuda con alguna funcionalidad o tienes dudas sobre el uso de la plataforma, este es el lugar indicado.
                </p>
                <div class="text-center">
                    <a href="#" class="btn-forum" onclick="alert('Modo demostración: Los foros reales estarán disponibles después de configurar la base de datos.')">
                        <i class="fas fa-arrow-right mr-2"></i>Entrar al Foro
                    </a>
                </div>
            </div>
        </div>

        <div class="forum-card">
            <div class="forum-header">
                <div class="forum-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3 class="forum-title">Sugerencias</h3>
                <div class="forum-creator">
                    <i class="fas fa-user mr-1"></i>
                    Por Administrador
                </div>
            </div>
            <div class="forum-body">
                <p class="forum-description">
                    Propuestas y mejoras para el sistema. Comparte tus ideas para hacer que la plataforma sea mejor para todos. Tus sugerencias son muy valiosas para el desarrollo continuo.
                </p>
                <div class="text-center">
                    <a href="#" class="btn-forum" onclick="alert('Modo demostración: Los foros reales estarán disponibles después de configurar la base de datos.')">
                        <i class="fas fa-arrow-right mr-2"></i>Entrar al Foro
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>