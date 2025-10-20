<?php
// Versión minimalista para identificar el problema
session_start();

// Verificar sesión básica
if (!isset($_SESSION['user_id'])) {
    echo "Error: Sesión no válida";
    exit;
}

echo "Sesión OK<br>";
echo "Usuario ID: " . ($_SESSION['user_id'] ?? 'No definido') . "<br>";
echo "Nombre: " . ($_SESSION['nombre_completo'] ?? 'No definido') . "<br>";
echo "Rol: " . ($_SESSION['rol'] ?? 'No definido') . "<br>";
echo "Fecha: " . date('Y-m-d H:i:s') . "<br>";

// Intentar incluir db.php
try {
    include '../db.php';
    echo "Base de datos: OK<br>";
} catch (Exception $e) {
    echo "Error base de datos: " . $e->getMessage() . "<br>";
}

echo "<br><a href='../user_dashboard.php'>Volver al Dashboard</a>";
?>
