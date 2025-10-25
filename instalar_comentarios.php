<?php
// Script para instalar las tablas de comentarios
include 'db.php';

echo "<h2>Instalando sistema de comentarios...</h2>";

try {
    // Leer el archivo SQL
    $sql = file_get_contents('crear_tablas_comentarios.sql');
    
    if (!$sql) {
        throw new Exception("No se pudo leer el archivo SQL");
    }
    
    // Dividir el SQL en statements individuales
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Saltar comentarios y líneas vacías
        }
        
        try {
            $pdo->exec($statement);
            echo "<p style='color: green;'>✓ Ejecutado: " . substr($statement, 0, 50) . "...</p>";
            $success_count++;
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
            $error_count++;
        }
    }
    
    echo "<hr>";
    echo "<h3>Resumen:</h3>";
    echo "<p>✓ Comandos ejecutados exitosamente: $success_count</p>";
    echo "<p>✗ Errores: $error_count</p>";
    
    if ($error_count == 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4 style='color: #155724; margin: 0;'>¡Instalación completada!</h4>";
        echo "<p style='color: #155724; margin: 5px 0 0 0;'>El sistema de comentarios está listo para usar.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4 style='color: #721c24; margin: 0;'>Instalación con errores</h4>";
        echo "<p style='color: #721c24; margin: 5px 0 0 0;'>Algunos comandos fallaron. Revisa los errores arriba.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: #721c24; margin: 0;'>Error general</h4>";
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
