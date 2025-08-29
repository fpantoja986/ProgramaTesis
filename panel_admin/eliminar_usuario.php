<?php
include '../db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);

    echo "ok"; // respuesta simple para JS
} else {
    http_response_code(400);
    echo "ID no proporcionado.";
}
