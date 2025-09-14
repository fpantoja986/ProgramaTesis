/**
 * SISTEMA DE REPORTES PARA FOROS
 * Incluir este archivo en las páginas donde se muestran respuestas de foros
 */

// HTML del modal de reporte
const modalReporteHTML = `
<div class="modal fade" id="modalReporteRespuesta" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-flag mr-2"></i>Reportar Respuesta Inapropiada
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formReporteRespuesta">
                <div class="modal-body">
                    <input type="hidden" id="respuesta_id_reporte" name="respuesta_id">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Antes de reportar:</strong> Los reportes son revisados por nuestro equipo de moderación. 
                        Usa esta función responsablemente y solo para contenido que realmente viole las normas.
                    </div>
                    
                    <div class="form-group">
                        <label for="motivo_reporte">¿Por qué reportas esta respuesta? *</label>
                        <select class="form-control" id="motivo_reporte" name="motivo" required>
                            <option value="">Selecciona un motivo...</option>
                            <option value="spam">Spam o contenido repetitivo</option>
                            <option value="contenido_ofensivo">Lenguaje ofensivo o inapropiado</option>
                            <option value="acoso">Acoso o intimidación</option>
                            <option value="informacion_falsa">Información falsa o engañosa</option>
                            <option value="contenido_inapropiado">Contenido no relacionado al tema</option>
                            <option value="otro">Otro motivo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="comentario_reporte">Detalles adicionales (opcional):</label>
                        <textarea class="form-control" id="comentario_reporte" name="comentario" rows="3"
                                  placeholder="Proporciona más información sobre el problema que observaste..."></textarea>
                        <small class="form-text text-muted">
                            Ayúdanos a entender mejor la situación para tomar la acción apropiada.
                        </small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Importante:</strong> Los reportes falsos o malintencionados pueden resultar 
                        en advertencias para tu cuenta.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane mr-1"></i>Enviar Reporte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
`;

// Estilos CSS para los botones de reporte
const estilosReporte = `
<style>
.btn-reportar {
    font-size: 0.8rem;
    padding: 4px 8px;
    border: none;
    background: none;
    color: #6c757d;
    transition: all 0.3s ease;
    border-radius: 4px;
}

.btn-reportar:hover {
    color: #dc3545;
    background-color: rgba(220, 53, 69, 0.1);
    text-decoration: none;
}

.btn-reportar i {
    font-size: 0.75rem;
}

.respuesta-reportada {
    opacity: 0.7;
    border-left: 4px solid #ffc107;
    background-color: rgba(255, 193, 7, 0.05);
}

.respuesta-reportada::before {
    content: "⚠️ Contenido reportado - En revisión";
    display: block;
    padding: 5px 10px;
    background-color: #fff3cd;
    color: #856404;
    font-size: 0.8rem;
    font-weight: bold;
    border-bottom: 1px solid #ffeaa7;
}

@media (max-width: 768px) {
    .btn-reportar {
        font-size: 0.7rem;
        padding: 2px 6px;
    }
    
    .btn-reportar .btn-text {
        display: none;
    }
}
</style>
`;

// Inicializar el sistema cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalReporteHTML);
    
    // Agregar estilos al head
    document.head.insertAdjacentHTML('beforeend', estilosReporte);
    
    // Agregar botones de reporte a las respuestas
    agregarBotonesReporte();
    
    // Configurar el formulario de reporte
    configurarFormularioReporte();
});

/**
 * Agrega botones de reporte a todas las respuestas
 */
function agregarBotonesReporte() {
    // Buscar todas las respuestas (ajusta los selectores según tu HTML)
    const selectoresRespuesta = [
        '.respuesta-item',
        '.forum-response', 
        '.respuesta',
        '.comment-item',
        '[data-respuesta-id]'
    ];
    
    let respuestas = [];
    selectoresRespuesta.forEach(selector => {
        respuestas = respuestas.concat(Array.from(document.querySelectorAll(selector)));
    });
    
    respuestas.forEach(respuesta => {
        if (respuesta.querySelector('.btn-reportar')) {
            return; // Ya tiene botón de reporte
        }
        
        // Obtener ID de la respuesta
        const respuestaId = respuesta.dataset.respuestaId || 
                           respuesta.dataset.id || 
                           respuesta.getAttribute('data-id') ||
                           respuesta.id?.replace('respuesta-', '');
        
        if (!respuestaId) {
            console.warn('No se pudo obtener ID de respuesta para:', respuesta);
            return;
        }
        
        // Crear botón de reporte
        const botonReporte = document.createElement('button');
        botonReporte.type = 'button';
        botonReporte.className = 'btn btn-link btn-sm btn-reportar';
        botonReporte.title = 'Reportar contenido inapropiado';
        botonReporte.innerHTML = '<i class="fas fa-flag"></i> <span class="btn-text">Reportar</span>';
        
        // Evento click
        botonReporte.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            abrirModalReporte(respuestaId);
        });
        
        // Buscar donde insertar el botón
        const contenedorAcciones = buscarContenedorAcciones(respuesta);
        if (contenedorAcciones) {
            contenedorAcciones.appendChild(botonReporte);
        } else {
            // Si no encuentra contenedor de acciones, lo agrega al final
            respuesta.appendChild(botonReporte);
        }
    });
}

/**
 * Busca el contenedor de acciones donde insertar el botón
 */
function buscarContenedorAcciones(respuesta) {
    const selectoresAcciones = [
        '.acciones-respuesta',
        '.response-actions', 
        '.respuesta-footer',
        '.comment-actions',
        '.post-actions',
        '.actions'
    ];
    
    for (const selector of selectoresAcciones) {
        const contenedor = respuesta.querySelector(selector);
        if (contenedor) {
            return contenedor;
        }
    }
    
    return null;
}

/**
 * Abre el modal de reporte para una respuesta específica
 */
function abrirModalReporte(respuestaId) {
    document.getElementById('respuesta_id_reporte').value = respuestaId;
    $('#modalReporteRespuesta').modal('show');
}

/**
 * Configura el formulario de reporte
 */
function configurarFormularioReporte() {
    const form = document.getElementById('formReporteRespuesta');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Mostrar loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Enviando...';
        
        // Determinar la ruta correcta para el procesador
        let rutaProcesador = 'panel_admin/foros/moderacion/procesar_reporte.php';
        
        // Si ya estamos en el panel admin, ajustar ruta
        if (window.location.pathname.includes('panel_admin')) {
            rutaProcesador = 'foros/moderacion/procesar_reporte.php';
        }
        
        fetch(rutaProcesador, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            $('#modalReporteRespuesta').modal('hide');
            
            if (data.success) {
                // Mostrar mensaje de éxito
                mostrarMensaje('success', '¡Reporte enviado!', data.message || 'Gracias por tu reporte. Nuestro equipo lo revisará pronto.');
                
                // Marcar la respuesta como reportada visualmente
                marcarRespuestaReportada(formData.get('respuesta_id'));
                
                // Reset form
                form.reset();
            } else {
                mostrarMensaje('error', 'Error', data.error || 'Error al enviar el reporte');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            $('#modalReporteRespuesta').modal('hide');
            mostrarMensaje('error', 'Error de conexión', 'No se pudo enviar el reporte. Inténtalo de nuevo.');
        })
        .finally(() => {
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i>Enviar Reporte';
        });
    });
}

/**
 * Marca visualmente una respuesta como reportada
 */
function marcarRespuestaReportada(respuestaId) {
    const selectoresRespuesta = [
        `[data-respuesta-id="${respuestaId}"]`,
        `[data-id="${respuestaId}"]`,
        `#respuesta-${respuestaId}`
    ];
    
    for (const selector of selectoresRespuesta) {
        const respuesta = document.querySelector(selector);
        if (respuesta) {
            respuesta.classList.add('respuesta-reportada');
            
            // Deshabilitar el botón de reporte
            const botonReporte = respuesta.querySelector('.btn-reportar');
            if (botonReporte) {
                botonReporte.disabled = true;
                botonReporte.innerHTML = '<i class="fas fa-check"></i> Reportado';
                botonReporte.classList.add('text-success');
            }
            break;
        }
    }
}

/**
 * Muestra mensajes al usuario
 */
function mostrarMensaje(tipo, titulo, mensaje) {
    // Usar SweetAlert2 si está disponible
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: titulo,
            text: mensaje,
            icon: tipo === 'success' ? 'success' : 'error',
            timer: tipo === 'success' ? 3000 : 5000,
            showConfirmButton: tipo !== 'success'
        });
    } 
    // Fallback a alert básico
    else {
        alert(titulo + ': ' + mensaje);
    }
}

/**
 * Función pública para agregar botón de reporte a una respuesta específica
 */
window.agregarBotonReporte = function(respuestaId, contenedor) {
    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'btn btn-link btn-sm btn-reportar';
    boton.innerHTML = '<i class="fas fa-flag"></i> Reportar';
    boton.onclick = () => abrirModalReporte(respuestaId);
    
    if (typeof contenedor === 'string') {
        document.querySelector(contenedor).appendChild(boton);
    } else {
        contenedor.appendChild(boton);
    }
};

// Exportar funciones para uso externo
window.SistemaReportes = {
    agregarBotones: agregarBotonesReporte,
    abrirModal: abrirModalReporte,
    agregarBoton: window.agregarBotonReporte
};