<?php
include '../db.php'; // o 'db.php' según la ubicación

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre_completo'];
    $email = $_POST['email'];
    $rol = $_POST['rol'];
    $genero = $_POST['genero'];
    $verificado = isset($_POST['verificado']) ? 1 : 0;

    $sql = "UPDATE usuarios SET nombre_completo = ?, email = ?, rol = ?, genero = ?, verificado = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $email, $rol, $genero, $verificado, $id]);

    echo "ok";
}
?>
