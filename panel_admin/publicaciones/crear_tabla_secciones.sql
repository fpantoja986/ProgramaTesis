-- Script para crear la tabla de secciones y modificar la tabla contenidos
-- Ejecutar este script en la base de datos

-- 1. Crear tabla de secciones
CREATE TABLE IF NOT EXISTS secciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    icono VARCHAR(50) DEFAULT 'fas fa-folder',
    visible TINYINT(1) DEFAULT 1,
    orden INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Agregar campo seccion_id a la tabla contenidos
ALTER TABLE contenidos 
ADD COLUMN seccion_id INT DEFAULT NULL,
ADD COLUMN orden_seccion INT DEFAULT 0,
ADD FOREIGN KEY (seccion_id) REFERENCES secciones(id) ON DELETE SET NULL;

-- 3. Crear índices para mejor rendimiento
CREATE INDEX idx_contenidos_seccion ON contenidos(seccion_id);
CREATE INDEX idx_contenidos_orden ON contenidos(orden_seccion);
CREATE INDEX idx_secciones_visible ON secciones(visible);
CREATE INDEX idx_secciones_orden ON secciones(orden);

-- 4. Insertar secciones por defecto
INSERT INTO secciones (nombre, descripcion, color, icono, orden) VALUES
('General', 'Contenidos generales sin categoría específica', '#6c757d', 'fas fa-folder', 1),
('Noticias', 'Noticias y actualizaciones importantes', '#28a745', 'fas fa-newspaper', 2),
('Tutoriales', 'Guías y tutoriales paso a paso', '#17a2b8', 'fas fa-book', 3),
('Recursos', 'Documentos y recursos descargables', '#ffc107', 'fas fa-download', 4),
('Eventos', 'Información sobre eventos y actividades', '#dc3545', 'fas fa-calendar-alt', 5);
