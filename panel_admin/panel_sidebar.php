<?php
// panel_sidebar.php
// Sidebar lateral común para el panel de administración con estilos incluidos
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
        margin: 12px auto; /* Center horizontally */
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
    }

    .sidebar .icon-btn.active,
    .sidebar .icon-btn:hover {
        background: #f3f0ff;
        color: #4f46e5;
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
        min-width: 160px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        z-index: 200;
        padding: 10px 0;
        margin-left: 0;
        display: none;
    }

    .dropdown-menu-custom.show {
        display: block;
    }

    .arrow-icon {
        margin-left: 5px;
        font-size: 0.75rem;
        transition: transform 0.3s ease;
    }

    .arrow-icon.rotated {
        transform: rotate(180deg);
    }

    .dropdown-menu-custom a {
        display: block;
        padding: 10px 16px;
        color: #333;
        text-decoration: none;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
    }

    .dropdown-menu-custom a:last-child {
        border-bottom: none;
    }

    .dropdown-menu-custom a:hover {
        background: #f3f0ff;
        color: #4f46e5;
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
        }
        .sidebar .icon-btn,
        .sidebar .logout-btn {
            margin: 0 10px;
        }
        .main-content,
        .container {
            margin-left: 0;
            margin-top: 70px;
            width: 100%;
        }
        .dropdown-menu-custom {
            left: 0;
            top: 60px;
            margin-left: 0;
        }
    }
</style>

<div class="sidebar">
    <a href="admin_dashboard.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>" title="Inicio">
        <i class="fas fa-home"></i>
    </a>
    <button type="button" class="icon-btn" id="listarBtn" title="Listar Usuarios/Admins" aria-expanded="false" aria-controls="listarDropdown">
        <i class="fas fa-users"></i>
        <i class="fas fa-chevron-down arrow-icon"></i>
    </button>
    <div class="dropdown-menu-custom" id="listarDropdown" role="region" aria-hidden="true">
        <a href="users.php"><i class="fas fa-user"></i> Usuarios</a>
        <a href="admins.php"><i class="fas fa-user-shield"></i> Administradores</a>
    </div>
    <a href="mis_publicaciones.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'mis_publicaciones.php' ? 'active' : '' ?>" title="Mis Publicaciones">
        <i class="fas fa-file-alt"></i>
    </a>
    <a href="subir_contenido.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'subir_contenido.php' ? 'active' : '' ?>" title="Subir Contenido">
        <i class="fas fa-upload"></i>
    </a>
    <a href="ajustes.php" class="icon-btn <?= basename($_SERVER['PHP_SELF']) === 'ajustes.php' ? 'active' : '' ?>" title="Ajustes">
        <i class="fas fa-cog"></i>
    </a>
    <form action="../logout.php" method="POST" style="width:100%;">
        <button type="submit" class="icon-btn logout-btn" title="Cerrar sesión">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </form>
</div>

<script>
    // Dropdown para listar usuarios/admins
    const listarBtn = document.getElementById('listarBtn');
    const listarDropdown = document.getElementById('listarDropdown');
    const arrowIcon = listarBtn.querySelector('.arrow-icon');
    listarBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        const isShown = listarDropdown.classList.toggle('show');
        listarBtn.setAttribute('aria-expanded', isShown);
        listarDropdown.setAttribute('aria-hidden', !isShown);
        arrowIcon.classList.toggle('rotated', isShown);
    });
    // Cierra el dropdown si se hace click fuera
    document.addEventListener('click', function(e) {
        if (!listarDropdown.contains(e.target) && e.target !== listarBtn) {
            listarDropdown.classList.remove('show');
            listarBtn.setAttribute('aria-expanded', false);
            listarDropdown.setAttribute('aria-hidden', true);
            arrowIcon.classList.remove('rotated');
        }
    });
</script>
