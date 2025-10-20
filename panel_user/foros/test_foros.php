<?php
// Archivo de prueba simple para verificar que el sistema funciona
session_start();

// Verificar sesión
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
    <title>Prueba de Foros</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            padding: 40px 20px;
        }
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        .btn-test {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 10px;
        }
        .btn-test:hover {
            color: white;
            text-decoration: none;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Sistema de Foros - Prueba Exitosa</h1>
            <p>Bienvenido, <?= htmlspecialchars($user_name) ?></p>
        </div>
        
        <div class="card p-4">
            <h3>🎉 ¡El sistema está funcionando correctamente!</h3>
            <p>Esta página de prueba confirma que:</p>
            <ul>
                <li>✅ La sesión de usuario está activa</li>
                <li>✅ El nombre del usuario se muestra correctamente</li>
                <li>✅ No hay errores HTTP 500</li>
                <li>✅ El sistema está operativo</li>
            </ul>
            
            <div class="text-center mt-4">
                <a href="lista_foros.php" class="btn-test">
                    <i class="fas fa-comments mr-2"></i>Ir a Lista de Foros
                </a>
                <a href="../user_dashboard.php" class="btn-test">
                    <i class="fas fa-home mr-2"></i>Volver al Dashboard
                </a>
            </div>
        </div>
        
        <div class="card p-4">
            <h4>📋 Información del Sistema</h4>
            <p><strong>Usuario:</strong> <?= htmlspecialchars($user_name) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['email'] ?? 'No disponible') ?></p>
            <p><strong>Rol:</strong> <?= htmlspecialchars($_SESSION['rol'] ?? 'No disponible') ?></p>
            <p><strong>Fecha:</strong> <?= date('d M Y H:i:s') ?></p>
        </div>
    </div>
</body>
</html>
