<?php
// Instalación simple de comentarios
include 'db.php';

echo "<h2>Instalando tabla de comentarios...</h2>";

try {
    // Verificar si la tabla ya existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios_publicaciones'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4 style='color: #0c5460; margin: 0;'>Tabla ya existe</h4>";
        echo "<p style='color: #0c5460; margin: 5px 0 0 0;'>La tabla 'comentarios_publicaciones' ya existe en la base de datos.</p>";
        echo "</div>";
    } else {
        // Crear tabla básica de comentarios
        $sql = "
        CREATE TABLE comentarios_publicaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_publicacion INT NOT NULL,
            id_usuario INT NOT NULL,
            comentario TEXT NOT NULL,
            id_comentario_padre INT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            activo TINYINT(1) DEFAULT 1,
            moderado TINYINT(1) DEFAULT 1,
            id_moderador INT NULL,
            fecha_moderacion TIMESTAMP NULL,
            motivo_rechazo TEXT NULL,
            reportes JSON NULL,
            total_reportes INT DEFAULT 0,
            likes JSON NULL,
            total_likes INT DEFAULT 0,
            FOREIGN KEY (id_publicacion) REFERENCES contenidos(id) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (id_comentario_padre) REFERENCES comentarios_publicaciones(id) ON DELETE CASCADE,
            FOREIGN KEY (id_moderador) REFERENCES usuarios(id) ON DELETE SET NULL,
            INDEX idx_publicacion (id_publicacion),
            INDEX idx_usuario (id_usuario),
            INDEX idx_padre (id_comentario_padre),
            INDEX idx_fecha (fecha_creacion),
            INDEX idx_moderado (moderado),
            INDEX idx_total_reportes (total_reportes),
            INDEX idx_total_likes (total_likes),
            INDEX idx_activo (activo)
        )";
        
        $pdo->exec($sql);
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4 style='color: #155724; margin: 0;'>¡Tabla creada exitosamente!</h4>";
        echo "<p style='color: #155724; margin: 5px 0 0 0;'>El sistema de comentarios está listo para usar.</p>";
        echo "</div>";
    }
    
    // Verificar que la tabla se creó
    $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios_publicaciones'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Tabla 'comentarios_publicaciones' existe</p>";
        
        // Mostrar estructura
        $stmt = $pdo->query("DESCRIBE comentarios_publicaciones");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Estructura de la tabla:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li><strong>" . $column['Field'] . "</strong> - " . $column['Type'] . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: #721c24; margin: 0;'>Error</h4>";
    echo "<p style='color: #721c24; margin: 5px 0 0 0;'>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background-color: #f8f9fa;
}
h2 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
</style>
