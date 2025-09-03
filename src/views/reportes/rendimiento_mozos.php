<?php 
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

// La sesión ya está iniciada desde public/index.php
// Verificar autenticación y permisos
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
    header('Location: ' . url('unauthorized'));
    exit;
}

use App\Models\Reporte;

// Parámetros de filtro
$periodo = $_GET['periodo'] ?? 'mes';

// Validar período
$periodos_validos = ['semana', 'mes', 'año'];
if (!in_array($periodo, $periodos_validos)) {
    $periodo = 'mes';
}

// Obtener datos usando el modelo Reporte
$mozos = Reporte::rendimientoMozos($periodo);
$stats = Reporte::estadisticasPeriodo($periodo);
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
    background: #fd7e14;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.apply-btn:hover {
    background: #e8690b;
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
}
</style>

<div class="report-container">
    <div class="report-header">
        <h1>👥 Rendimiento de Mozos</h1>
        <p>Análisis de productividad y ventas por mozo</p>
    </div>

    <div class="filters-section">
        <div class="filter-group">
            <label for="periodo">Período:</label>
            <select name="periodo" id="periodo" onchange="updateFilters()">
                <option value="semana" <?= $periodo === 'semana' ? 'selected' : '' ?>>Última Semana</option>
                <option value="mes" <?= $periodo === 'mes' ? 'selected' : '' ?>>Último Mes</option>
                <option value="año" <?= $periodo === 'año' ? 'selected' : '' ?>>Último Año</option>
            </select>
        </div>
        
        <button class="apply-btn" onclick="applyFilters()">Aplicar Filtros</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total de Pedidos</h3>
            <div class="value"><?= number_format($stats['total_pedidos'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <h3>Ingresos Totales</h3>
            <div class="value">$<?= number_format($stats['ingresos_totales'] ?? 0, 2) ?></div>
        </div>
        <div class="stat-card">
            <h3>Promedio por Pedido</h3>
            <div class="value">$<?= number_format($stats['promedio_pedido'] ?? 0, 2) ?></div>
        </div>
        <div class="stat-card">
            <h3>Mozos Activos</h3>
            <div class="value"><?= count($mozos) ?></div>
        </div>
    </div>

    <div class="mozos-table">
        <?php if (empty($mozos)): ?>
            <div class="no-data">
                <h3>No hay datos disponibles</h3>
                <p>No se encontraron datos de rendimiento para el período seleccionado.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mozo</th>
                        <th>Total Pedidos</th>
                        <th>Ingresos Generados</th>
                        <th>Promedio por Pedido</th>
                        <th>Rendimiento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $max_ingresos = max(array_column($mozos, 'ingresos_generados'));
                    $max_pedidos = max(array_column($mozos, 'total_pedidos'));
                    ?>
                    <?php foreach ($mozos as $index => $mozo): ?>
                        <?php 
                        $rendimiento_ingresos = $max_ingresos > 0 ? ($mozo['ingresos_generados'] / $max_ingresos) * 100 : 0;
                        $rendimiento_pedidos = $max_pedidos > 0 ? ($mozo['total_pedidos'] / $max_pedidos) * 100 : 0;
                        $rendimiento_promedio = ($rendimiento_ingresos + $rendimiento_pedidos) / 2;
                        
                        $performance_class = '';
                        $performance_text = '';
                        if ($rendimiento_promedio >= 90) {
                            $performance_class = 'performance-excellent';
                            $performance_text = 'Excelente';
                        } elseif ($rendimiento_promedio >= 75) {
                            $performance_class = 'performance-good';
                            $performance_text = 'Bueno';
                        } elseif ($rendimiento_promedio >= 50) {
                            $performance_class = 'performance-average';
                            $performance_text = 'Promedio';
                        } else {
                            $performance_class = 'performance-poor';
                            $performance_text = 'Necesita Mejora';
                        }
                        ?>
                        <tr>
                            <td>
                                <span class="rank-badge"><?= $index + 1 ?></span>
                            </td>
                            <td>
                                <div class="mozo-name"><?= htmlspecialchars($mozo['nombre'] . ' ' . $mozo['apellido']) ?></div>
                            </td>
                            <td><?= number_format($mozo['total_pedidos']) ?></td>
                            <td class="revenue">$<?= number_format($mozo['ingresos_generados'], 2) ?></td>
                            <td>$<?= number_format($mozo['promedio_pedido'], 2) ?></td>
                            <td>
                                <span class="performance-badge <?= $performance_class ?>">
                                    <?= $performance_text ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function updateFilters() {
    const periodo = document.getElementById('periodo').value;
    
    const url = new URL(window.location);
    url.searchParams.set('periodo', periodo);
    
    window.location.href = url.toString();
}

function applyFilters() {
    updateFilters();
}
</script>

<div style="margin-top: 2rem; text-align: center;">
    <a href="<?= url('reportes') ?>" class="access-btn">← Volver a Reportes</a>
</div>
