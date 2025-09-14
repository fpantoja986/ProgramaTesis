<?php
// POPUP TRABAJADOR DESTACADO - ARCHIVO ÚNICO
// Solo incluir este archivo en tus páginas principales

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    return;
}

// Procesar si es una petición AJAX para marcar como visto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_visto'])) {
    $popup_id = $_POST['popup_id'] ?? '';
    if (!empty($popup_id)) {
        $_SESSION['popup_trabajador_' . $popup_id] = true;
    }
    echo json_encode(['success' => true]);
    exit;
}

try {
    // Obtener trabajador destacado activo (solo usuarios verificados)
    $stmt = $pdo->prepare("
        SELECT 
            td.*,
            u.nombre_completo,
            u.foto_perfil,
            DATE_FORMAT(td.fecha_inicio, '%M %Y') as periodo_formato
        FROM trabajadores_destacados td
        INNER JOIN usuarios u ON td.id_usuario = u.id
        WHERE u.rol = 'usuario'
        AND u.verificado = 1
        AND td.mostrar_popup = 1
        AND (td.fecha_fin IS NULL OR td.fecha_fin >= CURDATE())
        AND td.fecha_inicio <= CURDATE()
        ORDER BY td.fecha_inicio DESC
        LIMIT 1
    ");
    
    $stmt->execute();
    $trabajador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no hay trabajador o ya se vio, no mostrar
    if (!$trabajador || isset($_SESSION['popup_trabajador_' . $trabajador['id']])) {
        return;
    }
    
} catch (Exception $e) {
    return; // Error silencioso
}
?>

<!-- Estilos del popup minimalista -->
<style>
.trabajador-destacado-modal .modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    position: relative;
    background: #ffffff;
    color: #333333;
    font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
}

.close-btn {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 1000;
    background: rgba(245, 245, 245, 0.9);
    border: none;
    color: #777;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    cursor: pointer;
}

.close-btn:hover {
    background: #f0f0f0;
    color: #333;
    transform: scale(1.05);
}

.modal-header-destacado {
    text-align: center;
    padding: 40px 30px 20px;
    position: relative;
    background: transparent;
    border: none;
}

.modal-title-destacado {
    font-size: 1.8rem;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #2c3e50;
    letter-spacing: -0.5px;
}

.periodo-destacado {
    font-size: 1rem;
    color: #7f8c8d;
    font-weight: 400;
    text-transform: capitalize;
}

.modal-body-destacado {
    padding: 0 40px 20px;
    text-align: center;
}

.foto-trabajador-container {
    margin-bottom: 30px;
}

.foto-trabajador {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f8f9fa;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    position: relative;
    z-index: 2;
}

.info-trabajador {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-top: 20px;
}

.nombre-trabajador {
    font-size: 1.5rem;
    font-weight: 500;
    margin-bottom: 20px;
    color: #2c3e50;
}

.mensaje-merito-container {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    text-align: left;
}

.icono-trofeo {
    background: #f8f9fa;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #e9ecef;
    color: #f39c12;
}

.icono-trofeo i {
    font-size: 18px;
}

.mensaje-merito {
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
    color: #555;
    font-weight: 400;
}

.modal-footer-destacado {
    padding: 20px 40px 30px;
    text-align: center;
    background: transparent;
    border: none;
}

.felicitaciones p {
    font-size: 1rem;
    font-weight: 500;
    margin: 0 0 20px 0;
    color: #7f8c8d;
}

.btn-continuar {
    background: #2c3e50;
    border: none;
    color: white;
    padding: 12px 32px;
    font-size: 1rem;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.2s ease;
    cursor: pointer;
    letter-spacing: 0.5px;
}

.btn-continuar:hover {
    background: #34495e;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(44, 62, 80, 0.15);
}

/* Efectos sutiles */
.foto-trabajador-container {
    position: relative;
}

.foto-trabajador-container::after {
    content: "";
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 60%;
    height: 8px;
    background: radial-gradient(ellipse at center, rgba(0,0,0,0.1) 0%, transparent 70%);
    border-radius: 50%;
    z-index: 1;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-title-destacado { font-size: 1.5rem; }
    .nombre-trabajador { font-size: 1.3rem; }
    .modal-body-destacado { padding: 0 20px 15px; }
    .modal-footer-destacado { padding: 15px 20px 25px; }
    .foto-trabajador { width: 120px; height: 120px; }
    .mensaje-merito-container { flex-direction: column; text-align: center; }
    .icono-trofeo { align-self: center; }
}

/* Animación de entrada del modal */
.modal.fade .modal-dialog {
    transform: translateY(20px) scale(0.95);
    opacity: 0;
    transition: all 0.3s ease-out;
}

.modal.show .modal-dialog {
    transform: translateY(0) scale(1);
    opacity: 1;
}

/* Línea decorativa superior */
.modal-header-destacado::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3498db, #2c3e50);
    border-radius: 2px 2px 0 0;
}
</style>

<!-- Modal Trabajador Destacado del Mes -->
<div class="modal fade" id="modalTrabajadorDestacado" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
        <div class="modal-content trabajador-destacado-modal">
            <button type="button" class="close-btn" onclick="cerrarPopup()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="modal-header-destacado">
                <h2 class="modal-title-destacado">
                    Trabajador Destacado
                </h2>
                <p class="periodo-destacado"><?= htmlspecialchars($trabajador['periodo_formato']) ?></p>
            </div>

            <div class="modal-body-destacado">
                <div class="foto-trabajador-container">
                    <img src="<?= $trabajador['foto_perfil'] ? 'uploads/perfiles/' . htmlspecialchars($trabajador['foto_perfil']) : 'assets/img/default-avatar.png' ?>" 
                         alt="<?= htmlspecialchars($trabajador['nombre_completo']) ?>" 
                         class="foto-trabajador">
                </div>

                <div class="info-trabajador">
                    <h3 class="nombre-trabajador"><?= htmlspecialchars($trabajador['nombre_completo']) ?></h3>
                    
                    <div class="mensaje-merito-container">
                        <div class="icono-trofeo">
                            <i class="fas fa-award"></i>
                        </div>
                        <p class="mensaje-merito"><?= nl2br(htmlspecialchars($trabajador['mensaje_merito'])) ?></p>
                    </div>
                </div>
            </div>

            <div class="modal-footer-destacado">
                <div class="felicitaciones">
                    <p>Felicitaciones por tu excelente trabajo</p>
                </div>
                <button type="button" class="btn-continuar" onclick="cerrarPopup()">
                    Continuar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
const trabajadorId = <?= $trabajador['id'] ?>;

// Mostrar modal al cargar
document.addEventListener('DOMContentLoaded', function() {
    // Pequeño retraso para mejor experiencia de usuario
    setTimeout(function() {
        // Si hay jQuery y Bootstrap, usar modal de Bootstrap
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#modalTrabajadorDestacado').modal('show');
        } else {
            // Mostrar con CSS puro
            document.getElementById('modalTrabajadorDestacado').style.display = 'block';
            document.getElementById('modalTrabajadorDestacado').classList.add('show');
            document.body.classList.add('modal-open');
        }
    }, 500);
});

// Función para cerrar popup
function cerrarPopup() {
    // Marcar como visto en sesión
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'marcar_visto=1&popup_id=' + trabajadorId
    }).then(() => {
        // Cerrar modal
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#modalTrabajadorDestacado').modal('hide');
        } else {
            document.getElementById('modalTrabajadorDestacado').style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    }).catch(() => {
        // Cerrar aunque falle la petición
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#modalTrabajadorDestacado').modal('hide');
        } else {
            document.getElementById('modalTrabajadorDestacado').style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    });
}

// Cerrar con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarPopup();
    }
});
</script>

<?php
// Marcar como visto inmediatamente para evitar que se muestre de nuevo
$_SESSION['popup_trabajador_' . $trabajador['id']] = true;
?>