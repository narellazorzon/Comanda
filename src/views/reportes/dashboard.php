<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard de Reportes</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?route=home">Inicio</a></li>
        <li class="breadcrumb-item active">Reportes</li>
    </ol>

    <!-- Métricas principales -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-white-50 small">Ventas del Día</div>
                            <div class="h3 mb-0">$<?= number_format($metrics['ventas_dia'], 2, ',', '.') ?></div>
                        </div>
                        <div class="fa-2x text-white-50">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="?route=reportes/ventas-por-periodo&periodo=hoy">Ver detalles</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-white-50 small">Pedidos del Día</div>
                            <div class="h3 mb-0"><?= $metrics['pedidos_dia'] ?></div>
                        </div>
                        <div class="fa-2x text-white-50">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="index.php?route=pedidos">Ver pedidos</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-white-50 small">Pedidos Pendientes</div>
                            <div class="h3 mb-0"><?= $metrics['pedidos_pendientes'] ?></div>
                        </div>
                        <div class="fa-2x text-white-50">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="index.php?route=pedidos&estado=pendiente">Ver pedidos</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-white-50 small">Mesas Ocupadas</div>
                            <div class="h3 mb-0"><?= $metrics['mesas_ocupadas'] ?></div>
                        </div>
                        <div class="fa-2x text-white-50">
                            <i class="fas fa-chair"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="index.php?route=mesas">Ver mesas</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Ventas Últimas 24 Horas
                </div>
                <div class="card-body">
                    <canvas id="ventas24h" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Ticket Promedio
                </div>
                <div class="card-body text-center">
                    <div class="h1 mb-0">$<?= number_format($metrics['ticket_promedio'], 2, ',', '.') ?></div>
                    <div class="text-muted small">Por pedido hoy</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Ventas Últimos 7 Días
                </div>
                <div class="card-body">
                    <canvas id="ventas7d" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-star me-1"></i>
                    Productos Más Vendidos
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($topProducts as $index => $product): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($product['nombre']) ?></div>
                                    <small class="text-muted"><?= (int)$product['total_vendido'] ?> unidades</small>
                                </div>
                                <span class="badge bg-primary rounded-pill">$<?= number_format($product['total_ventas'], 2, ',', '.') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas por mozo -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Rendimiento por Mozo
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Mozo</th>
                            <th>Pedidos</th>
                            <th>Total Ventas</th>
                            <th>Ticket Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mozoMetrics as $mozo): ?>
                            <tr>
                                <td><?= htmlspecialchars($mozo['nombre_mozo']) ?></td>
                                <td><?= (int)$mozo['total_pedidos'] ?></td>
                                <td>$<?= number_format($mozo['total_ventas'], 2, ',', '.') ?></td>
                                <td>$<?= number_format($mozo['ticket_promedio'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Configuración de gráficos
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de ventas últimas 24 horas
    const ctx24h = document.getElementById('ventas24h').getContext('2d');
    const ventas24hChart = new Chart(ctx24h, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_map(function($i) {
                return date('H:00', strtotime("-$i hours"));
            }, range(23, -1, -1))) ?>,
            datasets: [{
                label: 'Ventas',
                data: <?= json_encode($chartData['ventas_24h']) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });

    // Gráfico de ventas últimos 7 días
    const ctx7d = document.getElementById('ventas7d').getContext('2d');
    const ventas7dChart = new Chart(ctx7d, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(function($i) {
                return date('D', strtotime("-$i days"));
            }, range(6, -1, -1))) ?>,
            datasets: [{
                label: 'Ventas',
                data: <?= json_encode($chartData['ventas_7d']) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });

    // Auto-refresh cada 30 segundos
    setInterval(() => {
        location.reload();
    }, 30000);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>