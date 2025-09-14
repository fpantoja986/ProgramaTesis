<?php
$host = 'bd-sistemas-2025-umariana-2e29.i.aivencloud.com';
$port = '26021';
$db   = 'tesis';       // Tu base de datos en Aiven
$user = 'avnadmin';    // Usuario de Aiven
$pass = 'AVNS_D2DYzMWTRMuypFx0WsJ';  // Reemplaza con la contraseña real que te dio Aiven
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "";
} catch (\PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

