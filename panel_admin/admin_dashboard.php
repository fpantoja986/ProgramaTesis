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

// Obtener filtros de fecha
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy

// 1. Distribución por género
$stmt = $pdo->query("SELECT genero, COUNT(*) as total FROM usuarios GROUP BY genero");
$generos = [];
$generosTotales = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $generos[] = ucfirst($row['genero']);
    $generosTotales[] = (int) $row['total'];
}

// 2. Ranking de foros más debatidos
$stmt = $pdo->prepare("
    SELECT 
        f.titulo as foro_titulo,
        COUNT(t.id) as total_temas,
        COUNT(rf.id) as total_respuestas,
        COUNT(DISTINCT rf.id_usuario) as usuarios_participantes
    FROM foros f
    LEFT JOIN temas_foro t ON f.id = t.id_foro
    LEFT JOIN respuestas_foro rf ON t.id = rf.id_tema
    WHERE f.fecha_creacion BETWEEN ? AND ?
    GROUP BY f.id, f.titulo
    ORDER BY total_respuestas DESC
    LIMIT 10
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$foros_ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Usuarios más activos
$stmt = $pdo->prepare("
    SELECT 
        u.nombre_completo,
        u.genero,
        COUNT(rf.id) as total_respuestas,
        COUNT(DISTINCT t.id) as temas_creados,
        MAX(rf.fecha_creacion) as ultima_actividad
    FROM usuarios u
    LEFT JOIN respuestas_foro rf ON u.id = rf.id_usuario
    LEFT JOIN temas_foro t ON u.id = t.id_usuario
    WHERE rf.fecha_creacion BETWEEN ? AND ? OR t.fecha_creacion BETWEEN ? AND ?
    GROUP BY u.id, u.nombre_completo, u.genero
    HAVING total_respuestas > 0 OR temas_creados > 0
    ORDER BY (total_respuestas + temas_creados) DESC
    LIMIT 10
");
$stmt->execute([$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
$usuarios_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Estadísticas generales
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT u.id) as total_usuarios,
        COUNT(DISTINCT f.id) as total_foros,
        COUNT(DISTINCT t.id) as total_temas,
        COUNT(rf.id) as total_respuestas
    FROM usuarios u
    LEFT JOIN foros f ON 1=1
    LEFT JOIN temas_foro t ON f.id = t.id_foro
    LEFT JOIN respuestas_foro rf ON t.id = rf.id_tema
    WHERE rf.fecha_creacion BETWEEN ? AND ? OR t.fecha_creacion BETWEEN ? AND ?
");
$stmt->execute([$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
$stats_generales = $stmt->fetch(PDO::FETCH_ASSOC);

// 5. Tendencia temporal (últimos 12 meses)
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(rf.fecha_creacion, '%Y-%m') as mes,
        COUNT(rf.id) as respuestas,
        COUNT(DISTINCT rf.id_usuario) as usuarios_activos
    FROM respuestas_foro rf
    WHERE rf.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(rf.fecha_creacion, '%Y-%m')
    ORDER BY mes ASC
");
$stmt->execute();
$tendencia_temporal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Participación por género en el período
$stmt = $pdo->prepare("
    SELECT 
        u.genero,
        COUNT(rf.id) as respuestas,
        COUNT(DISTINCT rf.id_usuario) as usuarios_unicos
    FROM usuarios u
    INNER JOIN respuestas_foro rf ON u.id = rf.id_usuario
    WHERE rf.fecha_creacion BETWEEN ? AND ?
    GROUP BY u.genero
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$participacion_genero = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="dark-mode.css">
</head>
<?php include 'trabajador_resaltado/popup_trabajador_destacado.php'; ?>
<body>
    <?php include 'panel_sidebar.php'; ?>

    <!-- Contenido principal con estadísticas completas -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header con filtros -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="mb-0 text-primary">
                            <i class="fas fa-chart-line mr-2"></i>Estadísticas de Participación
                        </h2>
                        <div class="d-flex gap-2">
                            <form method="GET" class="d-flex gap-2">
                                <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" class="form-control form-control-sm">
                                <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" class="form-control form-control-sm">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-filter mr-1"></i>Filtrar
                                </button>
                            </form>
                            <button class="btn btn-success btn-sm" onclick="exportarReporte()">
                                <i class="fas fa-download mr-1"></i>Exportar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas generales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= $stats_generales['total_usuarios'] ?></h4>
                                    <p class="mb-0">Usuarios Totales</p>
                                </div>
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= $stats_generales['total_foros'] ?></h4>
                                    <p class="mb-0">Foros Activos</p>
                                </div>
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= $stats_generales['total_temas'] ?></h4>
                                    <p class="mb-0">Temas Creados</p>
                                </div>
                                <i class="fas fa-file-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= $stats_generales['total_respuestas'] ?></h4>
                                    <p class="mb-0">Respuestas</p>
                                </div>
                                <i class="fas fa-reply fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos principales -->
            <div class="row mb-4">
                <!-- Participación por género -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie mr-2"></i>Participación por Género
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="generoChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tendencia temporal -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line mr-2"></i>Evolución de Participación
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="tendenciaChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rankings -->
            <div class="row mb-4">
                <!-- Foros más debatidos -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-trophy mr-2"></i>Foros Más Debatidos
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($foros_ranking)): ?>
                                <p class="text-muted">No hay datos para el período seleccionado</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Foro</th>
                                                <th>Respuestas</th>
                                                <th>Participantes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($foros_ranking as $index => $foro): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-<?= $index < 3 ? 'warning' : 'secondary' ?>">
                                                            <?= $index + 1 ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($foro['foro_titulo']) ?></td>
                                                    <td>
                                                        <span class="badge badge-primary"><?= $foro['total_respuestas'] ?></span>
                                                    </td>
                                                    <td><?= $foro['usuarios_participantes'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Usuarios más activos -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-star mr-2"></i>Usuarios Más Activos
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($usuarios_activos)): ?>
                                <p class="text-muted">No hay datos para el período seleccionado</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Usuario</th>
                                                <th>Respuestas</th>
                                                <th>Temas</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($usuarios_activos as $index => $usuario): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-<?= $index < 3 ? 'warning' : 'secondary' ?>">
                                                            <?= $index + 1 ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-<?= $usuario['genero'] === 'masculino' ? 'mars' : 'venus' ?> mr-2 text-<?= $usuario['genero'] === 'masculino' ? 'primary' : 'pink' ?>"></i>
                                                            <?= htmlspecialchars($usuario['nombre_completo']) ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info"><?= $usuario['total_respuestas'] ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-success"><?= $usuario['temas_creados'] ?></span>
                                                    </td>
                                                    <td>
                                                        <strong><?= $usuario['total_respuestas'] + $usuario['temas_creados'] ?></strong>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="dark-mode.js"></script>
    <script>
        // Datos para los gráficos
        const generosData = <?php echo json_encode($generos); ?>;
        const generosTotalesData = <?php echo json_encode($generosTotales); ?>;
        const tendenciaData = <?php echo json_encode($tendencia_temporal); ?>;
        const participacionGeneroData = <?php echo json_encode($participacion_genero); ?>;

        // Función para obtener colores según el modo
        function getChartColors() {
            const isDark = document.body.classList.contains('dark-mode');
            return {
                background: isDark ? [
                    '#6c63ff', '#34d399', '#fbbf24', '#f87171', '#60a5fa'
                ] : [
                    '#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc'
                ],
                border: isDark ? '#404040' : '#fff',
                text: isDark ? '#ffffff' : '#666666',
                grid: isDark ? '#404040' : '#e0e0e0'
            };
        }

        // Gráfica de géneros (participación en el período)
        const generoChart = document.getElementById('generoChart').getContext('2d');
        const generoChartInstance = new Chart(generoChart, {
            type: 'doughnut',
            data: {
                labels: participacionGeneroData.map(item => item.genero.charAt(0).toUpperCase() + item.genero.slice(1)),
                datasets: [{
                    data: participacionGeneroData.map(item => item.respuestas),
                    backgroundColor: getChartColors().background,
                    borderWidth: 2,
                    borderColor: getChartColors().border
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                color: getChartColors().text,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            color: getChartColors().text
                        }
                    },
                    tooltip: {
                        backgroundColor: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#fff',
                        titleColor: getChartColors().text,
                        bodyColor: getChartColors().text,
                        borderColor: getChartColors().grid,
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} respuestas (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Gráfica de tendencia temporal
        const tendenciaChart = document.getElementById('tendenciaChart').getContext('2d');
        const tendenciaChartInstance = new Chart(tendenciaChart, {
            type: 'line',
            data: {
                labels: tendenciaData.map(item => {
                    const [year, month] = item.mes.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Respuestas',
                    data: tendenciaData.map(item => item.respuestas),
                    borderColor: getChartColors().background[0],
                    backgroundColor: getChartColors().background[0] + '20',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Usuarios Activos',
                    data: tendenciaData.map(item => item.usuarios_activos),
                    borderColor: getChartColors().background[1],
                    backgroundColor: getChartColors().background[1] + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                color: getChartColors().text,
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            color: getChartColors().text
                        }
                    },
                    tooltip: {
                        backgroundColor: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#fff',
                        titleColor: getChartColors().text,
                        bodyColor: getChartColors().text,
                        borderColor: getChartColors().grid,
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: getChartColors().grid
                        },
                        ticks: {
                            color: getChartColors().text
                        }
                    },
                    x: {
                        grid: {
                            color: getChartColors().grid
                        },
                        ticks: {
                            color: getChartColors().text
                        }
                    }
                }
            }
        });

        // Función para exportar reporte
        function exportarReporte() {
            const fechaInicio = '<?= $fecha_inicio ?>';
            const fechaFin = '<?= $fecha_fin ?>';
            
            // Crear PDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Título
            doc.setFontSize(20);
            doc.text('Reporte de Estadísticas de Participación', 20, 20);
            
            // Período
            doc.setFontSize(12);
            doc.text(`Período: ${fechaInicio} a ${fechaFin}`, 20, 30);
            
            // Estadísticas generales
            doc.setFontSize(16);
            doc.text('Estadísticas Generales', 20, 50);
            
            doc.setFontSize(10);
            doc.text(`• Usuarios Totales: <?= $stats_generales['total_usuarios'] ?>`, 20, 60);
            doc.text(`• Foros Activos: <?= $stats_generales['total_foros'] ?>`, 20, 65);
            doc.text(`• Temas Creados: <?= $stats_generales['total_temas'] ?>`, 20, 70);
            doc.text(`• Respuestas: <?= $stats_generales['total_respuestas'] ?>`, 20, 75);
            
            // Foros más debatidos
            doc.setFontSize(16);
            doc.text('Foros Más Debatidos', 20, 90);
            
            let yPos = 100;
            <?php foreach (array_slice($foros_ranking, 0, 5) as $index => $foro): ?>
                doc.text(`${<?= $index + 1 ?>}. <?= addslashes($foro['foro_titulo']) ?> (<?= $foro['total_respuestas'] ?> respuestas)`, 20, yPos);
                yPos += 5;
            <?php endforeach; ?>
            
            // Usuarios más activos
            doc.setFontSize(16);
            doc.text('Usuarios Más Activos', 20, yPos + 10);
            yPos += 20;
            
            <?php foreach (array_slice($usuarios_activos, 0, 5) as $index => $usuario): ?>
                doc.text(`${<?= $index + 1 ?>}. <?= addslashes($usuario['nombre_completo']) ?> (<?= $usuario['total_respuestas'] + $usuario['temas_creados'] ?> actividades)`, 20, yPos);
                yPos += 5;
            <?php endforeach; ?>
            
            // Guardar PDF
            doc.save(`estadisticas_participacion_${fechaInicio}_${fechaFin}.pdf`);
        }

        // Función para exportar a Excel
        function exportarExcel() {
            const fechaInicio = '<?= $fecha_inicio ?>';
            const fechaFin = '<?= $fecha_fin ?>';
            
            // Crear workbook
            const wb = XLSX.utils.book_new();
            
            // Hoja de estadísticas generales
            const statsData = [
                ['Métrica', 'Valor'],
                ['Usuarios Totales', <?= $stats_generales['total_usuarios'] ?>],
                ['Foros Activos', <?= $stats_generales['total_foros'] ?>],
                ['Temas Creados', <?= $stats_generales['total_temas'] ?>],
                ['Respuestas', <?= $stats_generales['total_respuestas'] ?>]
            ];
            const statsSheet = XLSX.utils.aoa_to_sheet(statsData);
            XLSX.utils.book_append_sheet(wb, statsSheet, 'Estadísticas Generales');
            
            // Hoja de foros más debatidos
            const forosData = [
                ['Posición', 'Foro', 'Respuestas', 'Participantes']
            ].concat(<?php echo json_encode($foros_ranking); ?>.map((foro, index) => [
                index + 1,
                foro.foro_titulo,
                foro.total_respuestas,
                foro.usuarios_participantes
            ]));
            const forosSheet = XLSX.utils.aoa_to_sheet(forosData);
            XLSX.utils.book_append_sheet(wb, forosSheet, 'Foros Más Debatidos');
            
            // Hoja de usuarios más activos
            const usuariosData = [
                ['Posición', 'Usuario', 'Respuestas', 'Temas', 'Total']
            ].concat(<?php echo json_encode($usuarios_activos); ?>.map((usuario, index) => [
                index + 1,
                usuario.nombre_completo,
                usuario.total_respuestas,
                usuario.temas_creados,
                usuario.total_respuestas + usuario.temas_creados
            ]));
            const usuariosSheet = XLSX.utils.aoa_to_sheet(usuariosData);
            XLSX.utils.book_append_sheet(wb, usuariosSheet, 'Usuarios Más Activos');
            
            // Guardar Excel
            XLSX.writeFile(wb, `estadisticas_participacion_${fechaInicio}_${fechaFin}.xlsx`);
        }

        // Función para actualizar gráficos cuando cambie el modo oscuro
        function updateChartsForDarkMode() {
            if (typeof generoChartInstance !== 'undefined') {
                generoChartInstance.options.plugins.legend.labels.color = getChartColors().text;
                generoChartInstance.options.color = getChartColors().text;
                generoChartInstance.options.plugins.tooltip.backgroundColor = document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#fff';
                generoChartInstance.options.plugins.tooltip.titleColor = getChartColors().text;
                generoChartInstance.options.plugins.tooltip.bodyColor = getChartColors().text;
                generoChartInstance.options.plugins.tooltip.borderColor = getChartColors().grid;
                generoChartInstance.data.datasets[0].backgroundColor = getChartColors().background;
                generoChartInstance.data.datasets[0].borderColor = getChartColors().border;
                generoChartInstance.update('none');
            }

            if (typeof tendenciaChartInstance !== 'undefined') {
                tendenciaChartInstance.options.color = getChartColors().text;
                tendenciaChartInstance.options.plugins.legend.labels.color = getChartColors().text;
                tendenciaChartInstance.options.plugins.tooltip.backgroundColor = document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#fff';
                tendenciaChartInstance.options.plugins.tooltip.titleColor = getChartColors().text;
                tendenciaChartInstance.options.plugins.tooltip.bodyColor = getChartColors().text;
                tendenciaChartInstance.options.plugins.tooltip.borderColor = getChartColors().grid;
                tendenciaChartInstance.options.scales.y.grid.color = getChartColors().grid;
                tendenciaChartInstance.options.scales.x.grid.color = getChartColors().grid;
                tendenciaChartInstance.options.scales.y.ticks.color = getChartColors().text;
                tendenciaChartInstance.options.scales.x.ticks.color = getChartColors().text;
                tendenciaChartInstance.data.datasets[0].borderColor = getChartColors().background[0];
                tendenciaChartInstance.data.datasets[0].backgroundColor = getChartColors().background[0] + '20';
                tendenciaChartInstance.data.datasets[1].borderColor = getChartColors().background[1];
                tendenciaChartInstance.data.datasets[1].backgroundColor = getChartColors().background[1] + '20';
                tendenciaChartInstance.update('none');
            }
        }

        // Escuchar cambios en el modo oscuro
        $(document).ready(function() {
            $('.btn-success').after(`
                <button class="btn btn-info btn-sm ml-2" onclick="exportarExcel()">
                    <i class="fas fa-file-excel mr-1"></i>Excel
                </button>
            `);

            // Observar cambios en la clase dark-mode
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        setTimeout(updateChartsForDarkMode, 100);
                    }
                });
            });

            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    </script>
</body>

</html>