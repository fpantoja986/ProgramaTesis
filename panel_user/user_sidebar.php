<?php
// La sesión ya está iniciada en el archivo principal
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['nombre_completo'] ?? 'Usuario';
$user_email = $_SESSION['email'] ?? '';

// Detectar nivel de carpeta para rutas dinámicas
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$basePath = '';

if ($currentDir === 'foros') {
    $basePath = '../';
}

// Obtener foto de perfil del usuario
$stmt = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$foto_perfil = $user_data['foto_perfil'] ?? null;
?>

<div class="sidebar bg-white border-right" style="min-height: 100vh; padding-top: 1rem;">
    <!-- Header del Usuario -->
    <div class="text-center mb-4 px-3">
        <div class="user-profile mb-3">
            <img src="<?= !empty($foto_perfil) ? 'data:image/jpeg;base64,' . $foto_perfil : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) ?>" 
                 class="rounded-circle border border-primary" 
                 alt="Foto de perfil" 
                 style="width: 80px; height: 80px; object-fit: cover;">
        </div>
        <h5 class="text-primary mb-1"><?= htmlspecialchars($user_name) ?></h5>
        <small class="text-muted"><?= htmlspecialchars($user_email) ?></small>
    </div>

    <!-- Navegación Principal -->
    <nav class="nav flex-column px-3">
        <a href="<?= $basePath ?>user_dashboard.php" 
           class="nav-link <?= $current_page === 'user_dashboard.php' ? 'active bg-primary text-white' : 'text-dark' ?> mb-2 rounded">
            <i class="fas fa-tachometer-alt mr-2"></i>
            Dashboard
        </a>
        
        <a href="<?= $basePath ?>publicaciones.php" 
           class="nav-link <?= $current_page === 'publicaciones.php' ? 'active bg-primary text-white' : 'text-dark' ?> mb-2 rounded">
            <i class="fas fa-newspaper mr-2"></i>
            Publicaciones
        </a>
        
        <a href="<?= $basePath ?>foros/lista_foros.php" 
           class="nav-link <?= strpos($current_page, 'foros') !== false ? 'active bg-primary text-white' : 'text-dark' ?> mb-2 rounded">
            <i class="fas fa-comments mr-2"></i>
            Foros
        </a>
        
        <a href="<?= $basePath ?>mis_temas.php" 
           class="nav-link <?= $current_page === 'mis_temas.php' ? 'active bg-primary text-white' : 'text-dark' ?> mb-2 rounded">
            <i class="fas fa-user-edit mr-2"></i>
            Mis Temas
        </a>
        
        <a href="<?= $basePath ?>notificaciones.php" 
           class="nav-link <?= $current_page === 'notificaciones.php' ? 'active bg-primary text-white' : 'text-dark' ?> mb-2 rounded">
            <i class="fas fa-bell mr-2"></i>
            Notificaciones
            <?php
            // Contar notificaciones no leídas
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leida = 0");
            $stmt->execute([$user_id]);
            $notificaciones_count = $stmt->fetchColumn();
            if ($notificaciones_count > 0):
            ?>
                <span class="badge badge-danger ml-2"><?= $notificaciones_count ?></span>
            <?php endif; ?>
        </a>
        
        <a href="<?= $basePath ?>actividad.php" 
           class="nav-link <?= $current_page === 'actividad.php' ? 'active bg-primary text-white' : 'text-dark' ?> mb-2 rounded">
            <i class="fas fa-history mr-2"></i>
            Mi Actividad
        </a>
        
        <hr class="my-3">
        
        <a href="<?= $basePath ?>ajustes_usuario.php" 
           class="nav-link <?= $current_page === 'ajustes_usuario.php' ? 'active bg-primary text-white' : 'text-dark' ?> mb-2 rounded">
            <i class="fas fa-cog mr-2"></i>
            Ajustes
        </a>
        
        <a href="<?= $basePath ?>../logout.php" class="nav-link text-danger mb-2 rounded">
            <i class="fas fa-sign-out-alt mr-2"></i>
            Cerrar Sesión
        </a>
    </nav>

    <!-- Información adicional -->
    <div class="mt-auto px-3 pb-3">
        <div class="card border-0 bg-light">
            <div class="card-body p-3">
                <h6 class="card-title text-primary mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Información
                </h6>
                <small class="text-muted">
                    <i class="fas fa-calendar mr-1"></i>
                    Última conexión: <?= date('d M Y') ?>
                </small>
                <br>
                <small class="text-muted">
                    <i class="fas fa-user-tag mr-1"></i>
                    Rol: Usuario
                </small>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar {
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}

.nav-link {
    transition: all 0.3s ease;
    border-radius: 8px !important;
}

.nav-link:hover {
    background-color: #f8f9fa !important;
    transform: translateX(5px);
}

.nav-link.active {
    box-shadow: 0 2px 10px rgba(0,123,255,0.3);
}

.user-profile img {
    transition: transform 0.3s ease;
}

.user-profile img:hover {
    transform: scale(1.05);
}

.badge {
    font-size: 0.7rem;
    padding: 0.25em 0.5em;
}
</style>
