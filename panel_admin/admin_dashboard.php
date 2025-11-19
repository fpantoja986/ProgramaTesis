<?php
session_start();

require '../db.php';
// Evitar caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// Consultas simplificadas para evitar errores
try {
    // 1. Contar usuarios totales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total_usuarios = $stmt->fetchColumn();

    // 2. Contar administradores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'administrador'");
    $total_admins = $stmt->fetchColumn();

    // 3. Contar usuarios normales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'usuario'");
    $total_users = $stmt->fetchColumn();

    // 4. Contar contenidos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contenidos");
    $total_contenidos = $stmt->fetchColumn();

    // 5. Contar foros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM foros");
    $total_foros = $stmt->fetchColumn();

    // 6. Contar secciones
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM secciones");
    $total_secciones = $stmt->fetchColumn();

    // 7. Usuarios recientes (últimos 5)
    $stmt = $pdo->query("SELECT nombre_completo, email, fecha_registro, rol FROM usuarios ORDER BY fecha_registro DESC LIMIT 5");
    $usuarios_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. Contenidos recientes (últimos 5)
    $stmt = $pdo->query("SELECT titulo, tipo, fecha_creacion FROM contenidos ORDER BY fecha_creacion DESC LIMIT 5");
    $contenidos_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si hay error, mostrar valores por defecto
    $total_usuarios = 0;
    $total_admins = 0;
    $total_users = 0;
    $total_contenidos = 0;
    $total_foros = 0;
    $total_secciones = 0;
    $usuarios_recientes = [];
    $contenidos_recientes = [];
    $error_message = "Error al cargar estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="stylesadmin.css?v=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'panel_sidebar.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard Administrador
            </h1>
            <p class="page-subtitle">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h3 class="card-title"><?php echo $total_usuarios; ?></h3>
                        <p class="card-text">Total Usuarios</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-shield fa-2x text-success mb-2"></i>
                        <h3 class="card-title"><?php echo $total_admins; ?></h3>
                        <p class="card-text">Administradores</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-file-alt fa-2x text-info mb-2"></i>
                        <h3 class="card-title"><?php echo $total_contenidos; ?></h3>
                        <p class="card-text">Contenidos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-comments fa-2x text-warning mb-2"></i>
                        <h3 class="card-title"><?php echo $total_foros; ?></h3>
                        <p class="card-text">Foros</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-folder fa-2x text-secondary mb-2"></i>
                        <h3 class="card-title"><?php echo $total_secciones; ?></h3>
                        <p class="card-text">Secciones</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user fa-2x text-dark mb-2"></i>
                        <h3 class="card-title"><?php echo $total_users; ?></h3>
                        <p class="card-text">Usuarios Normales</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tablas de Información Reciente -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-plus mr-2"></i>Usuarios Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($usuarios_recientes)): ?>
                            <p class="text-muted">No hay usuarios registrados</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Rol</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios_recientes as $usuario): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $usuario['rol'] === 'administrador' ? 'success' : 'primary'; ?>">
                                                        <?php echo ucfirst($usuario['rol']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-alt mr-2"></i>Contenidos Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($contenidos_recientes)): ?>
                            <p class="text-muted">No hay contenidos publicados</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contenidos_recientes as $contenido): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($contenido['titulo']); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo ucfirst($contenido['tipo']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($contenido['fecha_creacion'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt mr-2"></i>Acciones Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="users.php" class="btn btn-primary btn-block">
                                    <i class="fas fa-users mr-2"></i>Gestionar Usuarios
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="publicaciones/mis_publicaciones.php" class="btn btn-info btn-block">
                                    <i class="fas fa-file-alt mr-2"></i>Gestionar Contenidos
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="foros/gestionar_foros.php" class="btn btn-warning btn-block">
                                    <i class="fas fa-comments mr-2"></i>Gestionar Foros
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="publicaciones/gestionar_secciones.php" class="btn btn-success btn-block">
                                    <i class="fas fa-folder mr-2"></i>Gestionar Secciones
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="https://docs.google.com/forms/d/e/1FAIpQLSdf5oa2DdVaegvDQJ-Yqiil4RnwBqla4RwY5iuRopIZJzKaWw/viewform?usp=publish-editor" target="_blank" class="btn btn-outline-primary btn-block">
                                    <i class="fas fa-star mr-2"></i>Calificar Sistema
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>