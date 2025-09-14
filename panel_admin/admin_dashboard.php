<?php
session_start();

require '../db.php';
// Evitar caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// Distribución por género
$stmt = $pdo->query("SELECT genero, COUNT(*) as total FROM usuarios GROUP BY genero");
$generos = [];
$generosTotales = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $generos[] = ucfirst($row['genero']);
    $generosTotales[] = (int) $row['total'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="stylesadmin.css">
</head>
<?php include 'trabajador_resaltado/popup_trabajador_destacado.php'; ?>
<body>
    <?php include 'panel_sidebar.php'; ?>

    <!-- Contenido principal solo con gráfica de género -->
    <div class="main-content">
        <div class="container">
            <h2 class="mb-4 text-primary">Panel de Administración</h2>
            <div class="row justify-content-center">
                <div class="col-md-6 mb-4">
                    <div class="bg-white rounded shadow-sm p-4">
                        <h5 class="mb-3 text-center">Distribución por Género</h5>
                        <canvas id="generoChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        // Gráfica de géneros
        const generoChart = document.getElementById('generoChart').getContext('2d');
        new Chart(generoChart, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($generos); ?>,
                datasets: [{
                    data: <?php echo json_encode($generosTotales); ?>,
                    backgroundColor: [
                        '#6c63ff', '#34d399', '#fbbf24', '#f87171', '#60a5fa'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>

</html>