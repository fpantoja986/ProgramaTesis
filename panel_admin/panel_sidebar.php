<?php
// Detectar nivel de carpeta para rutas dinámicas
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$basePath = '';

if ($currentDir === 'publicaciones' || $currentDir === 'foros' || $currentDir === 'trabajador_resaltado' || $currentDir === 'moderacion' || $currentDir === 'comentarios') {
    $basePath = '../';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>dark-mode.css">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --primary-light: #a29bfe;
            --primary-dark: #5649c9;
            --text-color: #2d3436;
            --text-light: #636e72;
            --background: #ffffff;
            --hover-bg: #f8f7ff;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --border-radius: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .sidebar {
            width: 90px;
            background: var(--background);
            min-height: 100vh;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 28px 0;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            transition: var(--transition);
        }

        .sidebar:hover {
            width: 260px;
            align-items: flex-start;
            padding-left: 20px;
        }

        .sidebar:hover .icon-text {
            display: inline-block;
            opacity: 1;
        }

        .sidebar:hover .icon-btn {
            width: 220px;
            justify-content: flex-start;
            padding: 0 16px;
        }

        .sidebar:hover .arrow-icon {
            display: block;
        }

        .sidebar .logo {
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
        }

        .sidebar .logo i {
            font-size: 28px;
        }

        .sidebar .logo-text {
            display: none;
            margin-left: 12px;
            font-size: 18px;
        }

        .sidebar:hover .logo {
            justify-content: flex-start;
            padding-left: 16px;
        }

        .sidebar:hover .logo-text {
            display: inline-block;
        }

        .sidebar .icon-btn {
            width: 50px;
            height: 50px;
            margin: 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: none;
            border: none;
            transition: var(--transition);
            font-size: 1.4rem;
            color: var(--text-light);
            position: relative;
            cursor: pointer;
            text-decoration: none;
        }

        .sidebar .icon-btn:hover {
            background: var(--hover-bg);
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .sidebar .icon-btn.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
        }

        .sidebar .icon-text {
            display: none;
            margin-left: 16px;
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.3s ease;
            white-space: nowrap;
        }

        .sidebar .logout-btn {
            margin-top: auto;
            color: #ff7675;
        }

        .sidebar .logout-btn:hover {
            color: #d63031;
            background: rgba(255, 118, 117, 0.1);
        }

        .dropdown-menu-custom {
            max-height: none;
            overflow: visible;
            transition: none;
            position: absolute;
            top: 0;
            left: 100%;
            min-width: 220px;
            background: var(--background);
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            z-index: 200;
            padding: 12px 0;
            margin-left: 8px;
            display: none;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .dropdown-menu-custom.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .arrow-icon {
            position: absolute;
            top: 50%;
            right: 15px;
            font-size: 0.8rem;
            transition: transform 0.3s ease;
            transform: translateY(-50%);
            display: none;
        }

        .arrow-icon.rotated {
            transform: translateY(-50%) rotate(180deg);
        }

        .dropdown-menu-custom a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.95rem;
            font-weight: 400;
        }

        .dropdown-menu-custom a:last-child {
            border-bottom: none;
        }

        .dropdown-menu-custom a:hover {
            background: var(--hover-bg);
            color: var(--primary-color);
            padding-left: 24px;
        }

        .dropdown-menu-custom a i {
            margin-right: 12px;
            width: 18px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Dropdown específico para foros */
        .foros-dropdown {
            min-width: 200px;
        }

        .divider {
            height: 1px;
            background: rgba(0, 0, 0, 0.08);
            margin: 8px 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100vw;
                height: 70px;
                flex-direction: row;
                position: fixed;
                bottom: 0;
                top: auto;
                left: 0;
                right: 0;
                min-height: unset;
                overflow-x: auto;
                padding: 0 16px;
                align-items: center;
                justify-content: space-around;
            }

            .sidebar:hover {
                width: 100vw;
                padding: 0 16px;
                align-items: center;
            }

            .sidebar .logo,
            .sidebar .icon-text,
            .sidebar:hover .logo-text,
            .sidebar:hover .icon-text,
            .sidebar .arrow-icon {
                display: none !important;
            }

            .sidebar .icon-btn,
            .sidebar .logout-btn {
                margin: 0;
                flex-shrink: 0;
                width: 50px;
                height: 50px;
            }

            .sidebar:hover .icon-btn {
                width: 50px;
                justify-content: center;
                padding: 0;
            }

            .dropdown-menu-custom {
                left: 0;
                top: auto;
                bottom: 100%;
                margin-left: 0;
                margin-bottom: 10px;
            }

            .main-content,
            .container,
            .admin-container {
                margin-left: 0;
                margin-bottom: 90px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-tachometer-alt"></i>
            <span class="logo-text">Panel Admin</span>
        </div>
        
        <a href="<?= $basePath ?>admin_dashboard.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>" title="Dashboard">
            <i class="fas fa-home"></i>
            <span class="icon-text">Dashboard</span>
        </a>

        <!-- Dropdown Usuarios/Admins -->
        <button type="button" class="icon-btn" id="listarBtn" title="Usuarios y Administradores" aria-expanded="false" aria-controls="listarDropdown">
            <i class="fas fa-users"></i>
            <span class="icon-text">Usuarios & Admin</span>
            <i class="fas fa-chevron-down arrow-icon"></i>
        </button>
        <div class="dropdown-menu-custom" id="listarDropdown" role="region" aria-hidden="true">
            <a href="<?= $basePath ?>users.php"><i class="fas fa-user"></i> Usuarios</a>
            <a href="<?= $basePath ?>admins.php"><i class="fas fa-user-shield"></i> Administradores</a>
        </div>

        <!-- Dropdown Publicaciones -->
        <button type="button" class="icon-btn" id="publicacionesBtn" title="Gestión de Publicaciones" aria-expanded="false" aria-controls="publicacionesDropdown">
            <i class="fas fa-file-alt"></i>
            <span class="icon-text">Publicaciones</span>
            <i class="fas fa-chevron-down arrow-icon"></i>
        </button>
        <div class="dropdown-menu-custom" id="publicacionesDropdown" role="region" aria-hidden="true">
            <a href="<?= $basePath ?>publicaciones/mis_publicaciones.php"><i class="fas fa-list"></i> Mis Publicaciones</a>
            <a href="<?= $basePath ?>publicaciones/subir_contenido.php"><i class="fas fa-upload"></i> Subir Contenido</a>
        </div>

        <!-- Dropdown Foros -->
        <button type="button" class="icon-btn" id="forosBtn" title="Gestión de Foros" aria-expanded="false" aria-controls="forosDropdown">
            <i class="fas fa-comments"></i>
            <span class="icon-text">Foros</span>
            <i class="fas fa-chevron-down arrow-icon"></i>
        </button>
        <div class="dropdown-menu-custom foros-dropdown" id="forosDropdown" role="region" aria-hidden="true">
            <a href="<?= $basePath ?>foros/gestionar_foros.php"><i class="fas fa-cog"></i> Gestionar Foros</a>
            <a href="<?= $basePath ?>foros/moderacion_foros.php"><i class="fas fa-shield-alt"></i> Moderación</a>
            <a href="<?= $basePath ?>foros/gestionar_reportes.php"><i class="fas fa-flag"></i> Gestionar Reportes</a>
        </div>

        <!-- Gestión de Comentarios -->
        <a href="<?= $basePath ?>comentarios/gestionar_comentarios.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'gestionar_comentarios.php' ? 'active' : '' ?>" title="Gestión de Comentarios">
            <i class="fas fa-comment-dots"></i>
            <span class="icon-text">Comentarios</span>
        </a>

        <!-- Trabajadores Destacados -->
        <a href="<?= $basePath ?>trabajador_resaltado/gestionar_trabajadores_destacados.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'gestionar_trabajadores_destacados.php' ? 'active' : '' ?>" title="Trabajadores Destacados">
            <i class="fas fa-star"></i>
            <span class="icon-text">Destacados del Mes</span>
        </a>

        <a href="<?= $basePath ?>ajustes.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'ajustes.php' ? 'active' : '' ?>" title="Configuración">
            <i class="fas fa-cog"></i>
            <span class="icon-text">Configuración</span>
        </a>

        <a href="https://docs.google.com/forms/d/e/1FAIpQLSdf5oa2DdVaegvDQJ-Yqiil4RnwBqla4RwY5iuRopIZJzKaWw/viewform?usp=publish-editor" target="_blank" class="icon-btn" title="Calificar Sistema">
            <i class="fas fa-star"></i>
            <span class="icon-text">Calificar Sistema</span>
        </a>

        <!-- Logout -->
        <form action="<?= $basePath ?>../logout.php" method="POST" style="width:100%; display: flex; justify-content: center;">
            <button type="submit" class="icon-btn logout-btn" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
                <span class="icon-text">Cerrar Sesión</span>
            </button>
        </form>
    </div>

    <script>
        // Función genérica para manejar dropdowns
        function setupDropdown(btnId, dropdownId) {
            const btn = document.getElementById(btnId);
            const dropdown = document.getElementById(dropdownId);
            const arrowIcon = btn.querySelector('.arrow-icon');

            btn.addEventListener('click', function(e) {
                e.stopPropagation();

                // Cerrar otros dropdowns
                document.querySelectorAll('.dropdown-menu-custom.show').forEach(dd => {
                    if (dd !== dropdown) {
                        dd.classList.remove('show');
                        const otherBtn = document.querySelector(`[aria-controls="${dd.id}"]`);
                        const otherArrow = otherBtn.querySelector('.arrow-icon');
                        otherBtn.setAttribute('aria-expanded', false);
                        dd.setAttribute('aria-hidden', true);
                        otherArrow.classList.remove('rotated');
                    }
                });

                // Toggle del dropdown actual
                const isShown = dropdown.classList.toggle('show');
                btn.setAttribute('aria-expanded', isShown);
                dropdown.setAttribute('aria-hidden', !isShown);
                arrowIcon.classList.toggle('rotated', isShown);
            });
        }

        // Configurar todos los dropdowns
        setupDropdown('listarBtn', 'listarDropdown');
        setupDropdown('publicacionesBtn', 'publicacionesDropdown');
        setupDropdown('forosBtn', 'forosDropdown');

        // Cerrar dropdowns al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.sidebar')) {
                document.querySelectorAll('.dropdown-menu-custom.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                    const btn = document.querySelector(`[aria-controls="${dropdown.id}"]`);
                    const arrowIcon = btn.querySelector('.arrow-icon');
                    btn.setAttribute('aria-expanded', false);
                    dropdown.setAttribute('aria-hidden', true);
                    arrowIcon.classList.remove('rotated');
                });
            }
        });

        // Marcar como activo basado en la URL actual
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const currentFile = currentPath.split('/').pop();

            // Marcar secciones activas
            if (currentPath.includes('/publicaciones/')) {
                document.getElementById('publicacionesBtn').classList.add('active');
            } else if (currentPath.includes('/foros/')) {
                document.getElementById('forosBtn').classList.add('active');
            } else if (currentPath.includes('/trabajador_resaltado/')) {
                // El enlace directo ya tiene la clase active por PHP
            } else if (['users.php', 'admins.php'].includes(currentFile)) {
                document.getElementById('listarBtn').classList.add('active');
            }
        });
    </script>
    <script src="<?= $basePath ?>dark-mode.js"></script>
</body>
</html>