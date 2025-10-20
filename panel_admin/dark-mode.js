/**
 * Sistema de Modo Oscuro
 * Maneja el toggle y persistencia del modo oscuro
 */

class DarkModeManager {
    constructor() {
        this.isDarkMode = this.getStoredMode();
        this.init();
    }

    init() {
        this.createToggleButton();
        this.applyMode();
        this.bindEvents();
    }

    createToggleButton() {
        // Crear botón toggle si no existe
        if (!document.querySelector('.dark-mode-toggle')) {
            const toggleButton = document.createElement('div');
            toggleButton.className = 'dark-mode-toggle';
            toggleButton.innerHTML = '<i class="fas fa-moon"></i>';
            toggleButton.title = 'Cambiar modo oscuro';
            document.body.appendChild(toggleButton);
        }
    }

    bindEvents() {
        const toggleButton = document.querySelector('.dark-mode-toggle');
        if (toggleButton) {
            toggleButton.addEventListener('click', () => this.toggle());
        }

        // Escuchar cambios en el sistema
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener((e) => {
                if (!this.hasStoredPreference()) {
                    this.isDarkMode = e.matches;
                    this.applyMode();
                }
            });
        }
    }

    toggle() {
        this.isDarkMode = !this.isDarkMode;
        this.applyMode();
        this.storeMode();
        this.animateToggle();
    }

    applyMode() {
        const body = document.body;
        const toggleButton = document.querySelector('.dark-mode-toggle');
        
        if (this.isDarkMode) {
            body.classList.add('dark-mode');
            if (toggleButton) {
                toggleButton.innerHTML = '<i class="fas fa-sun"></i>';
                toggleButton.title = 'Cambiar a modo claro';
            }
        } else {
            body.classList.remove('dark-mode');
            if (toggleButton) {
                toggleButton.innerHTML = '<i class="fas fa-moon"></i>';
                toggleButton.title = 'Cambiar a modo oscuro';
            }
        }

        // Actualizar gráficos si existen
        this.updateCharts();
    }

    updateCharts() {
        // Actualizar colores de gráficos Chart.js
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = this.isDarkMode ? '#ffffff' : '#666666';
            Chart.defaults.borderColor = this.isDarkMode ? '#404040' : '#e0e0e0';
            Chart.defaults.backgroundColor = this.isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            
            // Re-renderizar gráficos existentes
            Chart.helpers.each(Chart.instances, (chart) => {
                chart.update('none');
            });
        }
    }

    animateToggle() {
        const toggleButton = document.querySelector('.dark-mode-toggle');
        if (toggleButton) {
            toggleButton.classList.add('animating');
            setTimeout(() => {
                toggleButton.classList.remove('animating');
            }, 500);
        }
    }

    storeMode() {
        try {
            localStorage.setItem('darkMode', this.isDarkMode.toString());
        } catch (e) {
            console.warn('No se pudo guardar la preferencia del modo oscuro');
        }
    }

    getStoredMode() {
        try {
            const stored = localStorage.getItem('darkMode');
            if (stored !== null) {
                return stored === 'true';
            }
        } catch (e) {
            console.warn('No se pudo leer la preferencia del modo oscuro');
        }

        // Fallback al modo del sistema
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return true;
        }

        return false;
    }

    hasStoredPreference() {
        try {
            return localStorage.getItem('darkMode') !== null;
        } catch (e) {
            return false;
        }
    }

    // Método público para cambiar el modo programáticamente
    setMode(isDark) {
        this.isDarkMode = isDark;
        this.applyMode();
        this.storeMode();
    }

    // Método público para obtener el estado actual
    getMode() {
        return this.isDarkMode;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.darkModeManager = new DarkModeManager();
});

// Función global para compatibilidad
function toggleDarkMode() {
    if (window.darkModeManager) {
        window.darkModeManager.toggle();
    }
}

// Función para cambiar el modo desde otros scripts
function setDarkMode(isDark) {
    if (window.darkModeManager) {
        window.darkModeManager.setMode(isDark);
    }
}

// Función para obtener el estado actual
function isDarkMode() {
    return window.darkModeManager ? window.darkModeManager.getMode() : false;
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DarkModeManager;
}

