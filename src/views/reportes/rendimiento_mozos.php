<?php 
// La vista recibe $resultado desde el controlador con:
// - params: ['desde', 'hasta', 'agrupar']  
// - kpis: array con los datos

$params = $resultado['params'] ?? [];
$kpis = $resultado['kpis'] ?? [];
$desde = $params['desde'] ?? date('Y-m-01');
$hasta = $params['hasta'] ?? date('Y-m-d');
$agrupar = $params['agrupar'] ?? 'ninguno';

// Obtener fechas del GET para los filtros (usando los mismos nombres que platos más vendidos)
$fechaDesde = !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : $desde;
$fechaHasta = !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : $hasta;

// Validar fechas
$fechasInvalidas = false;
if (!empty($fechaDesde) && !empty($fechaHasta)) {
    if ($fechaHasta < $fechaDesde) {
        $fechasInvalidas = true;
    }
}

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
    background: linear-gradient(135deg, var(--secondary) 0%, #8b5e46 100%);
    color: var(--text-light);
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(161, 134, 111, 0.3);
    position: relative;
    overflow: hidden;
}

.report-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}

.report-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    color: rgb(238, 224, 191);
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
}

.report-header p {
    margin: 0;
    opacity: 0.95;
    font-size: 1.1em;
    position: relative;
    z-index: 1;
}

.filtros {
    background: var(--surface);
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.filtros-fechas {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    flex: 1;
    min-width: 0;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 600;
    color: var(--secondary);
}

.filter-group select, .filter-group input {
    padding: 0.5rem;
    border: 1px solid var(--primary);
    border-radius: 4px;
    font-size: 1rem;
}

.apply-btn,
.btn-aplicar {
    background: var(--secondary);
    color: var(--text-light);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.2s ease;
    height: fit-content;
    white-space: nowrap;
    margin-left: 2.5rem;
}

.apply-btn:hover,
.btn-aplicar:hover {
    background-color: #8b5e46;
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
    color: var(--secondary);
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-card .value {
    font-size: 2em;
    font-weight: bold;
    color: var(--secondary);
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
    background: var(--secondary);
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.mozos-table td {
    padding: 12px 15px;
    border-bottom: 1px solid rgba(144, 104, 76, 0.2);
}

.mozos-table tr:hover {
    background: rgba(144, 104, 76, 0.05);
}

.mozo-name {
    font-weight: 600;
    color: var(--secondary);
}

.card.mb-4.ranking-card .card-header,
.ranking-card .card-header {
    background: var(--background) !important;
    background-color: var(--background) !important;
    color: var(--secondary) !important;
    border-bottom: 1px solid rgba(144, 104, 76, 0.2) !important;
}

.card.mb-4.ranking-card .card-header h5,
.ranking-card .card-header h5,
.ranking-card .card-header h5.mb-0 {
    color: var(--secondary) !important;
    font-size: 1.5rem !important;
    font-weight: 600 !important;
}

/* Títulos de gráficos - aumento del 40% */
.row .col-md-6 .card .card-header h5 {
    font-size: 1.4rem !important;
    font-weight: 600 !important;
    color: var(--secondary) !important;
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
    color: var(--secondary);
    font-style: italic;
}

@media (max-width: 768px) {
    .filtros {
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
    .btn-exportar {
        font-size: 0.9rem;
        padding: 0.6rem 1.2rem;
        width: 100%;
        margin-top: 0.5rem;
    }
    
    .filtros {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filtros-fechas {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
    }
    
    .filtros-fechas .filter-group,
    .filtros-fechas .apply-btn {
        width: 100%;
    }
    
    .filtros-fechas .apply-btn {
        margin-left: 0;
    }
    
    .btn-exportar {
        width: 100%;
        margin-top: 0.5rem;
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
        background: rgba(144, 104, 76, 0.1);
    }

    .ranking-table-container::-webkit-scrollbar-thumb {
        background: var(--secondary);
        border-radius: 3px;
    }

    .ranking-table-container::-webkit-scrollbar-thumb:hover {
        background: #8b5e46;
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
        color: var(--secondary);
        font-size: 0.75rem;
        padding: 8px;
        background: rgba(144, 104, 76, 0.1);
        border-top: 1px solid rgba(144, 104, 76, 0.3);
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

.btn-exportar {
    background: #28a745;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: background-color 0.2s ease;
    height: fit-content;
    white-space: nowrap;
    flex-shrink: 0;
}

.btn-exportar:hover {
    background: #218838;
}
</style>


<div class="report-container">
    <div class="report-header">
        <h1>👥 Rendimiento del Personal</h1>
        <p>Análisis de rendimiento y productividad del equipo</p>
        <?php if ($fechaDesde && $fechaHasta): ?>
            <p style="margin-top: 10px; font-size: 0.9em; opacity: 0.9;">
                📅 Período: del <?= htmlspecialchars($fechaDesde) ?> al <?= htmlspecialchars($fechaHasta) ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Contenedor de alerta para fechas inválidas -->
    <div id="alerta-fechas" 
                 style="display:<?= $fechasInvalidas ? 'block' : 'none' ?>; 
                        background:#f8d7da; 
                        color:#721c24; 
                        border:1px solid #f5c6cb; 
                        border-radius:6px; 
                        padding:10px; 
                        margin-bottom:1rem;">
                ❌ Fechas inválidas: la fecha hasta no puede ser anterior a la fecha desde.
    </div>

    <!-- Filtros -->
    <div class="filtros">
                <div class="filtros-fechas">
                    <div class="filter-group">
                        <label for="fecha_desde">Fecha Desde:</label>
                        <input type="date" name="fecha_desde" id="fecha_desde" 
                               value="<?= htmlspecialchars($fechaDesde ?? '') ?>" 
                               onchange="validarFechas()">
                    </div>
                    
                    <div class="filter-group">
                        <label for="fecha_hasta">Fecha Hasta:</label>
                        <input type="date" name="fecha_hasta" id="fecha_hasta" 
                               value="<?= htmlspecialchars($fechaHasta ?? '') ?>" 
                               onchange="validarFechas()">
                    </div>
                    
                    <button class="apply-btn" onclick="applyFilters()">Aplicar Filtros</button>
                </div>
                
                <button class="btn-exportar" onclick="exportarCSV()">
                    📥 Exportar CSV
                </button>
    </div>
    
    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Pedidos</h3>
            <div class="value"><?= number_format($totalPedidos) ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Propinas</h3>
            <div class="value text-success">$<?= number_format($totalPropinas, 2) ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Vendido</h3>
            <div class="value">$<?= number_format($totalVendido, 2) ?></div>
        </div>
        <div class="stat-card">
            <h3>Propina Promedio</h3>
            <div class="value text-info">$<?= number_format($promedioGeneral, 2) ?></div>
        </div>
    </div>
            
            <?php if (empty($kpis)): ?>
                <div class="alert alert-warning" role="alert" style="background-color: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px 0;">
                    <h4 style="margin-top: 0; color: #856404;">⚠️ No se encontraron datos para el período seleccionado</h4>
                    <div style="background: white; padding: 15px; border-radius: 5px; margin-top: 15px;">
                        <strong style="color: #856404;">🔍 Debug de fechas:</strong><br><br>
                        <strong>URL completa:</strong> <code style="background: #f0f0f0; padding: 2px 5px;"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') ?></code><br><br>
                        <strong>Parámetros GET recibidos:</strong><br>
                        - fecha_desde: <code style="background: #f0f0f0; padding: 2px 5px;"><?= htmlspecialchars($_GET['fecha_desde'] ?? 'NO ENVIADO') ?></code><br>
                        - fecha_hasta: <code style="background: #f0f0f0; padding: 2px 5px;"><?= htmlspecialchars($_GET['fecha_hasta'] ?? 'NO ENVIADO') ?></code><br>
                        - route: <code style="background: #f0f0f0; padding: 2px 5px;"><?= htmlspecialchars($_GET['route'] ?? 'NO ENVIADO') ?></code><br><br>
                        <strong>Fechas procesadas en la vista:</strong><br>
                        - fechaDesde: <code style="background: #f0f0f0; padding: 2px 5px;"><?= htmlspecialchars($fechaDesde ?? 'N/A') ?></code><br>
                        - fechaHasta: <code style="background: #f0f0f0; padding: 2px 5px;"><?= htmlspecialchars($fechaHasta ?? 'N/A') ?></code><br><br>
                        <strong>Fechas procesadas en el controlador (desde params):</strong><br>
                        - desde: <code style="background: #f0f0f0; padding: 2px 5px;"><?= htmlspecialchars($desde ?? 'N/A') ?></code><br>
                        - hasta: <code style="background: #f0f0f0; padding: 2px 5px;"><?= htmlspecialchars($hasta ?? 'N/A') ?></code><br><br>
                        <strong>Total de KPIs encontrados:</strong> <code style="background: #f0f0f0; padding: 2px 5px;"><?= count($kpis) ?></code>
                    </div>
                </div>
            <?php else: ?>
                
                <?php if ($agrupar === 'ninguno'): ?>
                    <!-- Vista de Ranking -->
                    <div class="card mb-4 ranking-card">
                        <div class="card-header" style="background: var(--background) !important; background-color: var(--background) !important; color: var(--secondary) !important;">
                            <h5 class="mb-0" style="color: var(--secondary) !important; font-size: 1.5rem !important; font-weight: 600 !important;">🏆 Ranking de Rendimiento</h5>
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
                                    <h5 class="mb-0" style="font-size: 1.4rem !important; font-weight: 600 !important; color: var(--secondary) !important;">📈 Propinas por Mozo</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartPropinas" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0" style="font-size: 1.4rem !important; font-weight: 600 !important; color: var(--secondary) !important;">📊 Pedidos por Mozo</h5>
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

// Validación y aplicación de filtros (igual que en platos más vendidos)
function validarFechas() {
    const fechaDesde = document.getElementById('fecha_desde').value;
    const fechaHasta = document.getElementById('fecha_hasta').value;
    const alerta = document.getElementById('alerta-fechas');
    
    if (fechaDesde && fechaHasta && fechaHasta < fechaDesde) {
        alerta.style.display = 'block';
        alerta.innerText = '❌ Fechas inválidas: la fecha hasta no puede ser anterior a la fecha desde.';
        return false;
    } else {
        alerta.style.display = 'none';
        return true;
    }
}

function applyFilters() {
    // Validar fechas antes de proceder
    if (!validarFechas()) {
        return false;
    }
    
    const fechaDesde = document.getElementById('fecha_desde').value;
    const fechaHasta = document.getElementById('fecha_hasta').value;
    
    const url = new URL(window.location);
    
    // Agregar nuevos parámetros
    if (fechaDesde) {
        url.searchParams.set('fecha_desde', fechaDesde);
    } else {
        url.searchParams.delete('fecha_desde');
    }
    
    if (fechaHasta) {
        url.searchParams.set('fecha_hasta', fechaHasta);
    } else {
        url.searchParams.delete('fecha_hasta');
    }
    
    // Mantener el parámetro route
    url.searchParams.set('route', 'reportes/rendimiento-personal');
    
    window.location.href = url.toString();
}
</script>
