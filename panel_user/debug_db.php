<?php
// Archivo de prueba para verificar la conexión y estructura de la base de datos
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

echo "<h2>Diagnóstico de Base de Datos</h2>";

try {
    // Verificar conexión
    echo "<p>✅ Conexión a la base de datos: OK</p>";
    
    // Listar todas las tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tablas disponibles:</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Verificar tabla usuarios
    if (in_array('usuarios', $tables)) {
        echo "<p>✅ Tabla 'usuarios' existe</p>";
        $stmt = $pdo->query("DESCRIBE usuarios");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas de usuarios: " . implode(', ', $columns) . "</p>";
    } else {
        echo "<p>❌ Tabla 'usuarios' NO existe</p>";
    }
    
    // Verificar tabla contenidos
    if (in_array('contenidos', $tables)) {
        echo "<p>✅ Tabla 'contenidos' existe</p>";
        $stmt = $pdo->query("DESCRIBE contenidos");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas de contenidos: " . implode(', ', $columns) . "</p>";
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM contenidos");
        $count = $stmt->fetchColumn();
        echo "<p>Total de registros en contenidos: $count</p>";
    } else {
        echo "<p>❌ Tabla 'contenidos' NO existe</p>";
    }
    
    // Verificar tabla foros
    if (in_array('foros', $tables)) {
        echo "<p>✅ Tabla 'foros' existe</p>";
    } else {
        echo "<p>❌ Tabla 'foros' NO existe</p>";
    }
    
    // Verificar tabla temas_foro
    if (in_array('temas_foro', $tables)) {
        echo "<p>✅ Tabla 'temas_foro' existe</p>";
    } else {
        echo "<p>❌ Tabla 'temas_foro' NO existe</p>";
    }
    
    // Verificar tabla respuestas_foro
    if (in_array('respuestas_foro', $tables)) {
        echo "<p>✅ Tabla 'respuestas_foro' existe</p>";
    } else {
        echo "<p>❌ Tabla 'respuestas_foro' NO existe</p>";
    }
    
    // Verificar tabla notificaciones
    if (in_array('notificaciones', $tables)) {
        echo "<p>✅ Tabla 'notificaciones' existe</p>";
    } else {
        echo "<p>❌ Tabla 'notificaciones' NO existe</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Error de base de datos: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='publicaciones.php'>← Volver a Publicaciones</a></p>";
?>
