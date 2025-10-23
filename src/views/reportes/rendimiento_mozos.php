<?php 
// La vista recibe $resultado desde el controlador con:
// - params: ['desde', 'hasta', 'agrupar']  
// - kpis: array con los datos

$params = $resultado['params'] ?? [];
$kpis = $resultado['kpis'] ?? [];
$desde = $params['desde'] ?? date('Y-m-01');
$hasta = $params['hasta'] ?? date('Y-m-d');
$agrupar = $params['agrupar'] ?? 'ninguno';

// Calcular totales generales
$totalPedidos = array_sum(array_column($kpis, 'pedidos'));
$totalPropinas = array_sum(array_column($kpis, 'propina_total'));
$totalVendido = array_sum(array_column($kpis, 'total_vendido'));
$promedioGeneral = $totalPedidos > 0 ? $totalPropinas / $totalPedidos : 0;
?>

<style>
.report-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.report-header {
    background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
    text-align: center;
}

.report-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
}

.report-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.filters-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: bold;
    color: #495057;
}

.filter-group select {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.apply-btn {
    background:var(--secondary);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s;
}

.apply-btn:hover {
    background-color: #8b5e46;
    transform: translateY(-2px);
}

.clear-btn {
    background: var(--secondary);
    color: var(--text-light);
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s;
    text-decoration: none;
    display: inline-block;
}

.clear-btn:hover {
    background-color: #8b5e46;
    text-decoration: none;
    color: var(--text-light);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-card .value {
    font-size: 2em;
    font-weight: bold;
    color: #495057;
}

.mozos-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mozos-table table {
    width: 100%;
    border-collapse: collapse;
}

.mozos-table th {
    background: #495057;
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.mozos-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
}

.mozos-table tr:hover {
    background: #f8f9fa;
}

.mozo-name {
    font-weight: 600;
    color: #495057;
}

.performance-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.9em;
    font-weight: 600;
    text-align: center;
}

.performance-excellent {
    background: #d4edda;
    color: #155724;
}

.performance-good {
    background: #d1ecf1;
    color: #0c5460;
}

.performance-average {
    background: #fff3cd;
    color: #856404;
}

.performance-poor {
    background: #f8d7da;
    color: #721c24;
}

.revenue {
    color: #28a745;
    font-weight: 600;
}

.rank-badge {
    background: #fd7e14;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.9em;
    font-weight: 600;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #6c757d;
    font-style: italic;
}

@media (max-width: 768px) {
    .filters-section {
        flex-direction: column;
        align-items: stretch;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .mozos-table {
        overflow-x: auto;
    }

    /* Responsive para tabla de ranking */
    .table-responsive {
        margin: 0 -15px;
        padding: 0 15px;
    }

    .mozos-table table {
        font-size: 0.875rem;
    }

    .mozos-table th,
    .mozos-table td {
        padding: 8px 10px;
        white-space: nowrap;
    }

    .mozos-table th:first-child,
    .mozos-table td:first-child {
        padding-left: 15px;
    }

    .mozos-table th:last-child,
    .mozos-table td:last-child {
        padding-right: 15px;
    }

    /* Ajustar columnas para móviles */
    .mozos-table th:nth-child(4),
    .mozos-table td:nth-child(4) { /* Total Vendido */
        min-width: 100px;
    }

    .mozos-table th:nth-child(5),
    .mozos-table td:nth-child(5) { /* Total Propinas */
        min-width: 100px;
    }

    .mozos-table th:nth-child(6),
    .mozos-table td:nth-child(6) { /* Propina Promedio */
        min-width: 100px;
    }

    .mozos-table th:nth-child(8),
    .mozos-table td:nth-child(8) { /* Rendimiento */
        min-width: 120px;
    }

    .rank-badge {
        font-size: 0.75rem;
        padding: 2px 6px;
    }

    .mozo-name {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .progress {
        height: 16px;
    }

    .progress-bar {
        font-size: 0.7rem;
        line-height: 16px;
    }
}

@media (max-width: 576px) {
    .mozos-table {
        font-size: 0.8rem;
    }

    .mozos-table th,
    .mozos-table td {
        padding: 6px 8px;
    }

    /* Ocultar columnas menos importantes en móviles muy pequeños */
    .mozos-table th:nth-child(6),
    .mozos-table td:nth-child(6) { /* Propina Promedio */
        display: none;
    }

    .mozos-table th:nth-child(8),
    .mozos-table td:nth-child(8) { /* Rendimiento */
        display: none;
    }

    .table-responsive {
        overflow-x: scroll;
        -webkit-overflow-scrolling: touch;
    }

    /* Mejorar gráficos en móviles */
    .card-body {
        padding: 0.75rem;
    }

    canvas {
        max-height: 250px !important;
    }

    /* Ajustar botones en móviles */
    .export-btn {
        font-size: 0.8rem;
        padding: 6px 12px;
        float: none;
        width: 100%;
        margin-top: 10px;
    }

    /* Títulos más pequeños */
    .card-header h5 {
        font-size: 1rem;
    }

    /* Estadísticas más compactas */
    .stat-card {
        padding: 1rem;
    }

    .stat-card .value {
        font-size: 1.4em;
    }

    /* Asegurar visibilidad del ranking */
    .ranking-card {
        margin: 0 -15px;
        border-radius: 0;
        border-left: none !important;
        border-right: none !important;
    }

    .ranking-table-container {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        margin: 0 !important;
        background: #fff;
    }

    .ranking-table-container::-webkit-scrollbar {
        height: 6px;
    }

    .ranking-table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .ranking-table-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .ranking-table-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .mozos-table {
        min-width: 600px !important;
        margin-bottom: 0 !important;
    }

    /* Ajustar tarjetas en móvil */
    .card {
        border-radius: 0.375rem !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }

    /* Forzar visibilidad de la tabla */
    .table-responsive {
        display: block !important;
        width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }

    /* Asegurar que el contenedor no colapse */
    .card-body.p-0 {
        padding: 0 !important;
    }

    /* Mensaje de scroll en móvil */
    .ranking-card::after {
        content: "→ Desliza para ver más";
        display: block;
        text-align: center;
        color: #6c757d;
        font-size: 0.75rem;
        padding: 8px;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }
}

.back-btn {
    display: inline-block;
    background: var(--secondary);
    color: var(--text-light);
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.2s ease;
}

.back-btn:hover {
    background-color: #8b5e46;
    text-decoration: none;
    color: var(--text-light);
}

.export-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s;
    float: right;
}

.export-btn:hover {
    background: #218838;
    transform: translateY(-2px);
}
</style>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h2 mb-0">📊 Rendimiento del Personal</h1>
                <button class="export-btn" onclick="exportarCSV()">
                    📥 Exportar CSV
                </button>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <input type="hidden" name="route" value="reportes/rendimiento-personal">
                        
                        <div class="col-md-4">
                            <label for="desde" class="form-label">Desde</label>
                            <input type="text" class="form-control" id="desde" name="desde"
                                   value="<?= date('d/m/Y', strtotime($desde)) ?>"
                                   placeholder="DD/MM/YYYY"
                                   pattern="\d{2}/\d{2}/\d{4}">
                            <input type="hidden" id="desde_hidden" name="desde"
                                   value="<?= htmlspecialchars($desde) ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="hasta" class="form-label">Hasta</label>
                            <input type="text" class="form-control" id="hasta" name="hasta"
                                   value="<?= date('d/m/Y', strtotime($hasta)) ?>"
                                   placeholder="DD/MM/YYYY"
                                   pattern="\d{2}/\d{2}/\d{4}">
                            <input type="hidden" id="hasta_hidden" name="hasta"
                                   value="<?= htmlspecialchars($hasta) ?>">
                        </div>
                        
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="apply-btn me-2">
                                🔍 Filtrar
                            </button>
                            <a href="?route=reportes/rendimiento-personal" class="clear-btn">
                                🔄 Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tarjetas de estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3>Total Pedidos</h3>
                        <div class="value"><?= number_format($totalPedidos) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3>Total Propinas</h3>
                        <div class="value text-success">$<?= number_format($totalPropinas, 2) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3>Total Vendido</h3>
                        <div class="value">$<?= number_format($totalVendido, 2) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3>Propina Promedio</h3>
                        <div class="value text-info">$<?= number_format($promedioGeneral, 2) ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($kpis)): ?>
                <div class="alert alert-warning" role="alert">
                    No se encontraron datos para el período seleccionado.
                </div>
            <?php else: ?>
                
                <?php if ($agrupar === 'ninguno'): ?>
                    <!-- Vista de Ranking -->
                    <div class="card mb-4 ranking-card">
                        <div class="card-header">
                            <h5 class="mb-0">🏆 Ranking de Rendimiento</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="ranking-table-container">
                                <table class="table table-hover mozos-table">
                                    <thead>
                                        <tr>
                                            <th width="60">#</th>
                                            <th>Mozo</th>
                                            <th class="text-center">Pedidos</th>
                                            <th class="text-end">Total Vendido</th>
                                            <th class="text-end">Total Propinas</th>
                                            <th class="text-end d-none d-md-table-cell">Propina Promedio</th>
                                            <th class="text-center">Tasa Propina</th>
                                            <th width="150" class="d-none d-lg-table-cell">Rendimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($kpis as $kpi): ?>
                                            <?php
                                            $rankClass = 'rank-badge';
                                            if ($kpi['ranking'] == 1) $rankClass .= ' bg-warning text-dark';
                                            elseif ($kpi['ranking'] == 2) $rankClass .= ' bg-secondary';
                                            elseif ($kpi['ranking'] == 3) $rankClass .= ' bg-danger';
                                            
                                            // Calcular porcentaje de rendimiento basado en tasa de propina
                                            $maxTasa = max(array_column($kpis, 'tasa_propina'));
                                            $performancePercent = $maxTasa > 0 ? ($kpi['tasa_propina'] / $maxTasa * 100) : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="<?= $rankClass ?>">
                                                        <?= $kpi['ranking'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong class="mozo-name"><?= htmlspecialchars($kpi['mozo']) ?></strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= $kpi['pedidos'] ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <strong>$<?= number_format($kpi['total_vendido'], 2) ?></strong>
                                                </td>
                                                <td class="text-end text-success">
                                                    <strong>$<?= number_format($kpi['propina_total'], 2) ?></strong>
                                                </td>
                                                <td class="text-end d-none d-md-table-cell">
                                                    $<?= number_format($kpi['propina_promedio_por_pedido'], 2) ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">
                                                        <?= number_format($kpi['tasa_propina'] * 100, 2) ?>%
                                                    </span>
                                                </td>
                                                <td class="d-none d-lg-table-cell">
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-success" role="progressbar"
                                                             style="width: <?= $performancePercent ?>%"
                                                             aria-valuenow="<?= $performancePercent ?>"
                                                             aria-valuemin="0" aria-valuemax="100">
                                                            <?= round($performancePercent) ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gráficos para vista de ranking -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">📈 Propinas por Mozo</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartPropinas" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">📊 Pedidos por Mozo</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartPedidos" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Vista Agrupada (por día o mes) -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                📅 Rendimiento por <?= $agrupar === 'dia' ? 'Día' : 'Mes' ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mozo</th>
                                            <th>Período</th>
                                            <th class="text-center">Pedidos</th>
                                            <th class="text-end">Total Vendido</th>
                                            <th class="text-end">Total Propinas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $currentMozo = '';
                                        foreach ($kpis as $kpi): 
                                            $showMozo = $currentMozo !== $kpi['mozo'];
                                            $currentMozo = $kpi['mozo'];
                                            ?>
                                            <tr <?= $showMozo ? 'class="border-top border-2"' : '' ?>>
                                                <td>
                                                    <?php if ($showMozo): ?>
                                                        <strong><?= htmlspecialchars($kpi['mozo']) ?></strong>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php
                                                        if ($agrupar === 'dia') {
                                                            echo date('d/m/Y', strtotime($kpi['periodo']));
                                                        } else {
                                                            echo date('M Y', strtotime($kpi['periodo']));
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= $kpi['pedidos'] ?></span>
                                                </td>
                                                <td class="text-end">
                                                    $<?= number_format($kpi['total_vendido'], 2) ?>
                                                </td>
                                                <td class="text-end text-success">
                                                    <strong>$<?= number_format($kpi['propina_total'], 2) ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
            <!-- Botón volver -->
            <div class="mt-4 text-center">
                <a href="?route=reportes" class="back-btn">
                    ← Volver a Reportes
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Scripts necesarios -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script>
<?php if (!empty($kpis) && $agrupar === 'ninguno'): ?>
    // Datos para gráficos de ranking
    const mozos = <?= json_encode(array_column($kpis, 'mozo')) ?>;
    const propinas = <?= json_encode(array_column($kpis, 'propina_total')) ?>;
    const pedidos = <?= json_encode(array_column($kpis, 'pedidos')) ?>;
    
    // Gráfico de propinas
    new Chart(document.getElementById('chartPropinas'), {
        type: 'bar',
        data: {
            labels: mozos.slice(0, 10), // Top 10
            datasets: [{
                label: 'Propinas ($)',
                data: propinas.slice(0, 10),
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de pedidos
    new Chart(document.getElementById('chartPedidos'), {
        type: 'bar',
        data: {
            labels: mozos.slice(0, 10), // Top 10
            datasets: [{
                label: 'Pedidos',
                data: pedidos.slice(0, 10),
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
<?php endif; ?>

// Función para exportar a CSV
function exportarCSV() {
    // Crear CSV en cliente
    let csv = 'Mozo,Pedidos,Total Vendido,Total Propinas,Propina Promedio,Tasa Propina\n';
    
    <?php if (!empty($kpis) && $agrupar === 'ninguno'): ?>
        <?php foreach ($kpis as $kpi): ?>
            csv += '<?= addslashes($kpi['mozo']) ?>,';
            csv += '<?= $kpi['pedidos'] ?>,';
            csv += '<?= $kpi['total_vendido'] ?>,';
            csv += '<?= $kpi['propina_total'] ?>,';
            csv += '<?= $kpi['propina_promedio_por_pedido'] ?>,';
            csv += '<?= number_format($kpi['tasa_propina'] * 100, 2) ?>%\n';
        <?php endforeach; ?>
    <?php endif; ?>
    
    // Descargar archivo
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'rendimiento_mozos_<?= date('Y-m-d') ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Función para convertir fecha de DD/MM/YYYY a YYYY-MM-DD
function convertDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('/');
    if (parts.length !== 3) return dateStr;
    return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
}

// Función para convertir fecha de YYYY-MM-DD a DD/MM/YYYY
function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Validar y convertir fechas al enviar el formulario
const form = document.querySelector('form');
if (form) {
    form.addEventListener('submit', function(e) {
    const desdeInput = document.getElementById('desde');
    const hastaInput = document.getElementById('hasta');
    const desdeHidden = document.getElementById('desde_hidden');
    const hastaHidden = document.getElementById('hasta_hidden');

    // Validar formato DD/MM/YYYY
    const dateRegex = /^\d{2}\/\d{2}\/\d{4}$/;

    if (!dateRegex.test(desdeInput.value)) {
        e.preventDefault();
        alert('El formato de la fecha "Desde" debe ser DD/MM/YYYY');
        desdeInput.focus();
        return;
    }

    if (!dateRegex.test(hastaInput.value)) {
        e.preventDefault();
        alert('El formato de la fecha "Hasta" debe ser DD/MM/YYYY');
        hastaInput.focus();
        return;
    }

    // Convertir fechas al formato YYYY-MM-DD para el formulario
    desdeHidden.value = convertDate(desdeInput.value);
    hastaHidden.value = convertDate(hastaInput.value);
});
}

// Formatear fecha al salir del campo
const desdeInput = document.getElementById('desde');
const hastaInput = document.getElementById('hasta');

if (desdeInput) {
    desdeInput.addEventListener('blur', function() {
        const value = this.value;
        if (value && /^\d{8}$/.test(value.replace(/\D/g, ''))) {
            // Auto-formatear si el usuario ingresa ddmmyyyy
            const clean = value.replace(/\D/g, '');
            if (clean.length === 8) {
                this.value = clean.substring(0, 2) + '/' + clean.substring(2, 4) + '/' + clean.substring(4, 8);
            }
        }
    });
}

if (hastaInput) {
    hastaInput.addEventListener('blur', function() {
    const value = this.value;
    if (value && /^\d{8}$/.test(value.replace(/\D/g, ''))) {
        // Auto-formatear si el usuario ingresa ddmmyyyy
        const clean = value.replace(/\D/g, '');
        if (clean.length === 8) {
            this.value = clean.substring(0, 2) + '/' + clean.substring(2, 4) + '/' + clean.substring(4, 8);
        }
    }
});
}

// Configuración en español para Flatpickr
const spanishLocale = {
    weekdays: {
        shorthand: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
        longhand: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
    },
    months: {
        shorthand: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        longhand: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
    },
    daysInMonth: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
    firstDayOfWeek: 1,
    ordinal: function(nth) {
        const s = nth % 100;
        if (s > 3 && s < 21) return 'º';
        switch (s % 10) {
            case 1: return 'º';
            case 2: return 'º';
            case 3: return 'º';
            default: return 'º';
        }
    },
    rangeSeparator: ' a ',
    weekAbbreviation: 'Sem',
    scrollTitle: 'Desplázate para incrementar',
    toggleTitle: 'Haz clic para alternar',
    amPM: ['AM', 'PM'],
    yearAriaLabel: 'Año',
    monthAriaLabel: 'Mes',
    hourAriaLabel: 'Hora',
    minuteAriaLabel: 'Minuto',
    time_24hr: true
};

// Inicializar Flatpickr para los campos de fecha
flatpickr("#desde", {
    locale: spanishLocale,
    dateFormat: "d/m/Y",
    altInput: true,
    altFormat: "d \\de F \\de Y",
    maxDate: new Date(),
    onChange: function(selectedDates, dateStr, instance) {
        // Actualizar el campo hidden cuando cambia la fecha
        document.getElementById('desde_hidden').value =
            selectedDates[0] ? selectedDates[0].toISOString().split('T')[0] : '';
    }
});

flatpickr("#hasta", {
    locale: spanishLocale,
    dateFormat: "d/m/Y",
    altInput: true,
    altFormat: "d \\de F \\de Y",
    maxDate: new Date(),
    onChange: function(selectedDates, dateStr, instance) {
        // Actualizar el campo hidden cuando cambia la fecha
        document.getElementById('hasta_hidden').value =
            selectedDates[0] ? selectedDates[0].toISOString().split('T')[0] : '';
    }
});

// Sincronizar valores iniciales
document.addEventListener('DOMContentLoaded', function() {
    const desdeInput = document.getElementById('desde');
    const hastaInput = document.getElementById('hasta');
    const desdeHidden = document.getElementById('desde_hidden');
    const hastaHidden = document.getElementById('hasta_hidden');

    // Establecer valores iniciales en los campos hidden
    desdeHidden.value = '<?= htmlspecialchars($desde) ?>';
    hastaHidden.value = '<?= htmlspecialchars($hasta) ?>';
});
</script>
