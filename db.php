<?php
$host = '127.0.0.1';
$db   = 'tesis';       // Verifica que el nombre de tu base de datos sea correcto
$user = 'root';
$pass = '170403';      // Verifica que esta sea tu contraseña real de MySQL
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error de conexión: " . $e->getMessage());  // Aquí se muestra el error exacto
}
