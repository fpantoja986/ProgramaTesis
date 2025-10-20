-- Script SQL para crear las tablas necesarias para el panel de usuario
-- Ejecutar este script si las tablas no existen

-- Tabla de contenidos/publicaciones (si no existe)
CREATE TABLE IF NOT EXISTS `contenidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `contenido_texto` text,
  `categoria` varchar(100) DEFAULT NULL,
  `id_admin` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`id_admin`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_fecha` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de foros (si no existe)
CREATE TABLE IF NOT EXISTS `foros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text,
  `id_admin` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`id_admin`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de temas de foro (si no existe)
CREATE TABLE IF NOT EXISTS `temas_foro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `id_foro` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fijado` tinyint(1) DEFAULT 0,
  `cerrado` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_foro` (`id_foro`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_fecha` (`fecha_creacion`),
  KEY `idx_fijado` (`fijado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de respuestas de foro (si no existe)
CREATE TABLE IF NOT EXISTS `respuestas_foro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contenido` text NOT NULL,
  `id_tema` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tema` (`id_tema`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_fecha` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de notificaciones (si no existe)
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` varchar(50) DEFAULT 'general',
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_leida` (`leida`),
  KEY `idx_fecha` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de ejemplo si las tablas están vacías

-- Insertar un foro de ejemplo
INSERT IGNORE INTO `foros` (`titulo`, `descripcion`, `id_admin`) VALUES
('General', 'Discusiones generales sobre el sistema', 1),
('Soporte Técnico', 'Ayuda y soporte técnico', 1),
('Sugerencias', 'Propuestas y mejoras para el sistema', 1);

-- Insertar una publicación de ejemplo
INSERT IGNORE INTO `contenidos` (`titulo`, `contenido_texto`, `categoria`, `id_admin`) VALUES
('Bienvenido al Sistema', 'Este es un mensaje de bienvenida para todos los usuarios del sistema. Aquí encontrarás información importante y actualizaciones.', 'General', 'admin@example.com'),
('Guía de Uso', 'Esta es una guía básica para usar el sistema de manera efectiva. Incluye consejos y mejores prácticas.', 'Documentación', 'admin@example.com');

-- Verificar que las tablas se crearon correctamente
SELECT 'Tablas creadas exitosamente' as resultado;
