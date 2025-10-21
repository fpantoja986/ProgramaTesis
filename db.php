<?php
$servername = "mysql-basedatostesis.alwaysdata.net";
$username = "436163";
$password = "Felipe1704";
$database = "basedatostesis_2025";
$port = 3306;

$charset = 'utf8mb4';

$dsn = "mysql:host=$servername;port=$port;dbname=$database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "";
} catch (\PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

