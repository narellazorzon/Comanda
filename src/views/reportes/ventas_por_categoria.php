<?php 
session_start();

// Verificar autenticación y permisos
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
    header('Location: ../../public/unauthorized.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Reporte;

// Parámetros de filtro
$periodo = $_GET['periodo'] ?? 'mes';

// Validar período
$periodos_validos = ['semana', 'mes', 'año'];
if (!in_array($periodo, $periodos_validos)) {
    $periodo = 'mes';
}

// Obtener datos usando el modelo Reporte
$categorias = Reporte::ventasPorCategoria($periodo);
$stats = Reporte::estadisticasPeriodo($periodo);
?>

<style>
.report-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.report-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.apply-btn:hover {
    background: #218838;
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

.categorias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.categoria-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s;
}

.categoria-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.categoria-header {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    color: white;
    padding: 20px;
    text-align: center;
}

.categoria-header h3 {
    margin: 0;
    font-size: 1.5em;
}

.categoria-body {
    padding: 20px;
}

.categoria-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.categoria-stat:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.stat-label {
    color: #6c757d;
    font-weight: 500;
}

.stat-value {
    font-weight: bold;
    color: #495057;
}

.revenue-value {
    color: #28a745;
}

.percentage-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 5px;
}

.percentage-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    border-radius: 4px;
    transition: width 0.3s ease;
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
    
    .categorias-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="report-container">
    <div class="report-header">
        <h1>📊 Ventas por Categoría</h1>
        <p>Análisis de rendimiento por categoría de productos</p>
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
            <h3>Categorías Activas</h3>
            <div class="value"><?= count($categorias) ?></div>
        </div>
    </div>

    <div class="categorias-grid">
        <?php if (empty($categorias)): ?>
            <div class="no-data">
                <h3>No hay datos disponibles</h3>
                <p>No se encontraron ventas por categoría para el período seleccionado.</p>
            </div>
        <?php else: ?>
            <?php 
            $total_ingresos = array_sum(array_column($categorias, 'ingresos_totales'));
            ?>
            <?php foreach ($categorias as $categoria): ?>
                <?php 
                $porcentaje = $total_ingresos > 0 ? ($categoria['ingresos_totales'] / $total_ingresos) * 100 : 0;
                ?>
                <div class="categoria-card">
                    <div class="categoria-header">
                        <h3><?= htmlspecialchars($categoria['categoria'] ?? 'Sin categoría') ?></h3>
                    </div>
                    <div class="categoria-body">
                        <div class="categoria-stat">
                            <span class="stat-label">Total Vendido:</span>
                            <span class="stat-value"><?= number_format($categoria['total_vendido']) ?> unidades</span>
                        </div>
                        <div class="categoria-stat">
                            <span class="stat-label">Pedidos:</span>
                            <span class="stat-value"><?= number_format($categoria['total_pedidos']) ?></span>
                        </div>
                        <div class="categoria-stat">
                            <span class="stat-label">Ingresos:</span>
                            <span class="stat-value revenue-value">$<?= number_format($categoria['ingresos_totales'], 2) ?></span>
                        </div>
                        <div class="categoria-stat">
                            <span class="stat-label">Porcentaje del total:</span>
                            <span class="stat-value"><?= number_format($porcentaje, 1) ?>%</span>
                        </div>
                        <div class="percentage-bar">
                            <div class="percentage-fill" style="width: <?= $porcentaje ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
