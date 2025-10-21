<?php
// Archivo de diagnóstico para el sistema de foros
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../../login.php");
    exit();
}

include '../../db.php';

echo "<h2>Diagnóstico del Sistema de Foros</h2>";

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
    
    // Verificar tabla foros
    if (in_array('foros', $tables)) {
        echo "<p>✅ Tabla 'foros' existe</p>";
        $stmt = $pdo->query("DESCRIBE foros");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas de foros: " . implode(', ', $columns) . "</p>";
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM foros");
        $count = $stmt->fetchColumn();
        echo "<p>Total de registros en foros: $count</p>";
        
        // Mostrar foros existentes
        if ($count > 0) {
            $stmt = $pdo->query("SELECT id, titulo, descripcion FROM foros LIMIT 5");
            $foros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h4>Foros existentes:</h4><ul>";
            foreach ($foros as $foro) {
                echo "<li>ID: {$foro['id']} - {$foro['titulo']}</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p>❌ Tabla 'foros' NO existe</p>";
    }
    
    // Verificar tabla temas_foro
    if (in_array('temas_foro', $tables)) {
        echo "<p>✅ Tabla 'temas_foro' existe</p>";
        $stmt = $pdo->query("DESCRIBE temas_foro");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas de temas_foro: " . implode(', ', $columns) . "</p>";
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM temas_foro");
        $count = $stmt->fetchColumn();
        echo "<p>Total de registros en temas_foro: $count</p>";
    } else {
        echo "<p>❌ Tabla 'temas_foro' NO existe</p>";
    }
    
    // Verificar tabla respuestas_foro
    if (in_array('respuestas_foro', $tables)) {
        echo "<p>✅ Tabla 'respuestas_foro' existe</p>";
        $stmt = $pdo->query("DESCRIBE respuestas_foro");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas de respuestas_foro: " . implode(', ', $columns) . "</p>";
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM respuestas_foro");
        $count = $stmt->fetchColumn();
        echo "<p>Total de registros en respuestas_foro: $count</p>";
    } else {
        echo "<p>❌ Tabla 'respuestas_foro' NO existe</p>";
    }
    
    // Verificar tabla usuarios
    if (in_array('usuarios', $tables)) {
        echo "<p>✅ Tabla 'usuarios' existe</p>";
        $stmt = $pdo->query("DESCRIBE usuarios");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columnas de usuarios: " . implode(', ', $columns) . "</p>";
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $count = $stmt->fetchColumn();
        echo "<p>Total de registros en usuarios: $count</p>";
    } else {
        echo "<p>❌ Tabla 'usuarios' NO existe</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Error de base de datos: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='lista_foros.php'>← Volver a Lista de Foros</a></p>";
echo "<p><a href='../debug_db.php'>← Ver Diagnóstico General</a></p>";
?>
