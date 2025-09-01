<?php
// Detectar nivel de carpeta para rutas dinámicas
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$basePath = '';

if ($currentDir === 'publicaciones' || $currentDir === 'foros') {
    $basePath = '../';
}
?>
<style>
    .sidebar {
        width: 70px;
        background: #fff;
        min-height: 100vh;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px 0;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 100;
    }

    .sidebar .icon-btn {
        width: 48px;
        height: 48px;
        margin: 12px auto;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: none;
        border: none;
        transition: background 0.2s;
        font-size: 1.5rem;
        color: #6c63ff;
        position: relative;
        cursor: pointer;
        text-decoration: none;
    }

    .sidebar .icon-btn.active,
    .sidebar .icon-btn:hover {
        background: #f3f0ff;
        color: #4f46e5;
        text-decoration: none;
    }

    .sidebar .logout-btn {
        margin-top: auto;
        color: #e53e3e;
    }

    .dropdown-menu-custom {
        max-height: none;
        overflow: visible;
        transition: none;
        position: absolute;
        top: 0;
        left: 100%;
        min-width: 200px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        z-index: 200;
        padding: 10px 0;
        margin-left: 8px;
        display: none;
    }

    .dropdown-menu-custom.show {
        display: block;
    }

    .arrow-icon {
        position: absolute;
        top: 2px;
        right: 2px;
        font-size: 0.6rem;
        transition: transform 0.3s ease;
    }

    .arrow-icon.rotated {
        transform: rotate(180deg);
    }

    .dropdown-menu-custom a {
        display: block;
        padding: 12px 16px;
        color: #333;
        text-decoration: none;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
        font-size: 0.9rem;
    }

    .dropdown-menu-custom a:last-child {
        border-bottom: none;
    }

    .dropdown-menu-custom a:hover {
        background: #f3f0ff;
        color: #4f46e5;
        text-decoration: none;
    }

    .dropdown-menu-custom a i {
        margin-right: 8px;
        width: 14px;
        text-align: center;
    }

    /* Dropdown específico para foros */
    .foros-dropdown {
        min-width: 180px;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 100vw;
            height: 60px;
            flex-direction: row;
            position: fixed;
            bottom: 0;
            top: auto;
            left: 0;
            right: 0;
            min-height: unset;
            overflow-x: auto;
            padding: 8px 16px;
        }

        .sidebar .icon-btn,
        .sidebar .logout-btn {
            margin: 0 8px;
            flex-shrink: 0;
        }

        .main-content,
        .container,
        .admin-container {
            margin-left: 0;
            margin-bottom: 80px;
            width: 100%;
        }

        .dropdown-menu-custom {
            left: 0;
            top: -200px;
            margin-left: 0;
        }
    }
</style>

<div class="sidebar">
    <a href="<?= $basePath ?>admin_dashboard.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>" title="Dashboard">
        <i class="fas fa-home"></i>
    </a>

    <!-- Dropdown Usuarios/Admins -->
    <button type="button" class="icon-btn" id="listarBtn" title="Usuarios y Administradores" aria-expanded="false" aria-controls="listarDropdown">
        <i class="fas fa-users"></i>
        <i class="fas fa-chevron-down arrow-icon"></i>
    </button>
    <div class="dropdown-menu-custom" id="listarDropdown" role="region" aria-hidden="true">
        <a href="<?= $basePath ?>users.php"><i class="fas fa-user"></i> Usuarios</a>
        <a href="<?= $basePath ?>admins.php"><i class="fas fa-user-shield"></i> Administradores</a>
    </div>

    <!-- Dropdown Publicaciones -->
    <button type="button" class="icon-btn" id="publicacionesBtn" title="Gestión de Publicaciones" aria-expanded="false" aria-controls="publicacionesDropdown">
        <i class="fas fa-file-alt"></i>
        <i class="fas fa-chevron-down arrow-icon"></i>
    </button>
    <div class="dropdown-menu-custom" id="publicacionesDropdown" role="region" aria-hidden="true">
        <a href="<?= $basePath ?>publicaciones/mis_publicaciones.php"><i class="fas fa-list"></i> Mis Publicaciones</a>
        <a href="<?= $basePath ?>publicaciones/subir_contenido.php"><i class="fas fa-upload"></i> Subir Contenido</a>
    </div>

    <a href="<?= $basePath ?>foros/gestionar_foros.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'gestionar_foros.php' ? 'active' : '' ?>" title="Gestionar Foros">
        <i class="fas fa-chevron-down arrow-icon"></i>
    </a>

    

    <a href="<?= $basePath ?>ajustes.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'ajustes.php' ? 'active' : '' ?>" title="Configuración">
        <i class="fas fa-cog"></i>
    </a>

    <!-- Logout -->
    <form action="<?= $basePath ?>../logout.php" method="POST" style="width:100%; display: flex; justify-content: center;">
        <button type="submit" class="icon-btn logout-btn" title="Cerrar sesión">
            <i class="fas fa-sign-out-alt"></i>
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
        } else if (['users.php', 'admins.php'].includes(currentFile)) {
            document.getElementById('listarBtn').classList.add('active');
        }
    });
</script>