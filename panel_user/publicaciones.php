<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['nombre_completo'] ?? 'Usuario';

// Inicializar variables
$publicaciones = [];
$categorias = [];
$total_publicaciones = 0;
$total_pages = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$categoria_filtro = $_GET['categoria'] ?? '';

try {
    // Verificar si la tabla contenidos existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'contenidos'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        // Verificar estructura de la tabla contenidos
        $stmt = $pdo->query("DESCRIBE contenidos");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Contar total de publicaciones (manejar tanto id_admin como email)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM contenidos c 
            LEFT JOIN usuarios u1 ON c.id_admin = u1.id AND u1.rol = 'administrador'
            LEFT JOIN usuarios u2 ON c.id_admin = u2.email AND u2.rol = 'administrador'
            WHERE (u1.id IS NOT NULL OR u2.id IS NOT NULL)
        ");
        $stmt->execute();
        $total_publicaciones = $stmt->fetchColumn();
        $total_pages = ceil($total_publicaciones / $limit);

        // Obtener publicaciones con información del administrador
        if ($categoria_filtro) {
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       COALESCE(u1.nombre_completo, u2.nombre_completo) as admin_nombre,
                       COALESCE(u1.foto_perfil, u2.foto_perfil) as admin_foto
                FROM contenidos c 
                LEFT JOIN usuarios u1 ON c.id_admin = u1.id AND u1.rol = 'administrador'
                LEFT JOIN usuarios u2 ON c.id_admin = u2.email AND u2.rol = 'administrador'
                WHERE (u1.id IS NOT NULL OR u2.id IS NOT NULL) AND c.categoria = :categoria
                ORDER BY c.fecha_creacion DESC 
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindParam(':categoria', $categoria_filtro);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       COALESCE(u1.nombre_completo, u2.nombre_completo) as admin_nombre,
                       COALESCE(u1.foto_perfil, u2.foto_perfil) as admin_foto
                FROM contenidos c 
                LEFT JOIN usuarios u1 ON c.id_admin = u1.id AND u1.rol = 'administrador'
                LEFT JOIN usuarios u2 ON c.id_admin = u2.email AND u2.rol = 'administrador'
                WHERE (u1.id IS NOT NULL OR u2.id IS NOT NULL)
                ORDER BY c.fecha_creacion DESC 
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Obtener categorías/secciones si existen
        if (in_array('categoria', $columns)) {
            $stmt = $pdo->prepare("SELECT DISTINCT categoria FROM contenidos WHERE categoria IS NOT NULL AND categoria != ''");
            $stmt->execute();
            $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } else {
        // Si no existe la tabla contenidos, mostrar mensaje informativo
        $publicaciones = [];
        $categorias = [];
        $total_publicaciones = 0;
        $total_pages = 0;
    }
} catch (PDOException $e) {
    // Manejar errores de base de datos
    error_log("Error en publicaciones.php: " . $e->getMessage());
    $publicaciones = [];
    $categorias = [];
    $total_publicaciones = 0;
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicaciones - Panel de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .publication-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .publication-card:hover {
            transform: translateY(-5px);
        }
        .publication-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }
        .publication-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .publication-meta {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .publication-body {
            padding: 2rem;
        }
        .publication-content {
            line-height: 1.6;
            color: #495057;
            margin-bottom: 1.5rem;
        }
        .publication-footer {
            padding: 1rem 2rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-info {
            display: flex;
            align-items: center;
        }
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.75rem;
            object-fit: cover;
        }
        .filters-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        .pagination-card {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        .btn-read {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            color: white;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-read:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .category-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="text-primary">
                            <i class="fas fa-newspaper mr-3"></i>
                            Publicaciones
                        </h1>
                        <div class="text-muted">
                            <i class="fas fa-calendar mr-2"></i>
                            <?= date('d M Y') ?>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <?php if (!empty($categorias)): ?>
                    <div class="filters-card">
                        <h5 class="mb-3">
                            <i class="fas fa-filter mr-2"></i>
                            Filtrar por Categoría
                        </h5>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="publicaciones.php" class="btn btn-outline-primary btn-sm <?= !$categoria_filtro ? 'active' : '' ?>">
                                Todas
                            </a>
                            <?php foreach ($categorias as $categoria): ?>
                                <a href="publicaciones.php?categoria=<?= urlencode($categoria) ?>" 
                                   class="btn btn-outline-primary btn-sm <?= $categoria_filtro === $categoria ? 'active' : '' ?>">
                                    <?= htmlspecialchars($categoria) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Lista de Publicaciones -->
                    <?php if (empty($publicaciones)): ?>
                        <div class="empty-state">
                            <i class="fas fa-newspaper"></i>
                            <h3>No hay publicaciones disponibles</h3>
                            <?php if ($total_publicaciones === 0): ?>
                                <p>Los administradores aún no han publicado contenido.</p>
                                <p class="text-muted small mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Las publicaciones aparecerán aquí cuando los administradores las creen.
                                </p>
                            <?php else: ?>
                                <p>No se encontraron publicaciones con los filtros seleccionados.</p>
                                <a href="publicaciones.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-refresh mr-2"></i>Ver Todas las Publicaciones
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($publicaciones as $pub): ?>
                            <div class="publication-card">
                                <div class="publication-header">
                                    <h2 class="publication-title">
                                        <?= htmlspecialchars($pub['titulo']) ?>
                                    </h2>
                                    <div class="publication-meta">
                                        <i class="fas fa-calendar mr-2"></i>
                                        <?= date('d M Y H:i', strtotime($pub['fecha_creacion'])) ?>
                                        <?php if (!empty($pub['categoria'])): ?>
                                            <span class="ml-3">
                                                <i class="fas fa-tag mr-1"></i>
                                                <span class="category-badge"><?= htmlspecialchars($pub['categoria']) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="publication-body">
                                    <div class="publication-content">
                                        <?php
                                        $contenido = $pub['contenido_texto'] ?? '';
                                        $contenido_preview = strlen($contenido) > 300 ? substr($contenido, 0, 300) . '...' : $contenido;
                                        echo nl2br(htmlspecialchars($contenido_preview));
                                        ?>
                                    </div>
                                </div>
                                <div class="publication-footer">
                                    <div class="admin-info">
                                        <img src="<?= !empty($pub['admin_foto']) ? 'data:image/jpeg;base64,' . $pub['admin_foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($pub['admin_nombre']) ?>" 
                                             class="admin-avatar" alt="Admin">
                                        <div>
                                            <div class="font-weight-medium"><?= htmlspecialchars($pub['admin_nombre']) ?></div>
                                            <small class="text-muted">Administrador</small>
                                        </div>
                                    </div>
                                    <a href="ver_publicacion.php?id=<?= $pub['id'] ?>" class="btn-read">
                                        <i class="fas fa-eye mr-2"></i>Leer Completo
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Paginación -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination-card">
                            <nav aria-label="Paginación de publicaciones">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="publicaciones.php?page=<?= $page - 1 ?><?= $categoria_filtro ? '&categoria=' . urlencode($categoria_filtro) : '' ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="publicaciones.php?page=<?= $i ?><?= $categoria_filtro ? '&categoria=' . urlencode($categoria_filtro) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="publicaciones.php?page=<?= $page + 1 ?><?= $categoria_filtro ? '&categoria=' . urlencode($categoria_filtro) : '' ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    Página <?= $page ?> de <?= $total_pages ?> 
                                    (<?= $total_publicaciones ?> publicaciones en total)
                                </small>
                            </div>
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
