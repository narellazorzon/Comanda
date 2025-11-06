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
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$error = '';

// Validar rango de fechas
if ($fecha_desde && $fecha_hasta) {
    $desde_ts = strtotime($fecha_desde);
    $hasta_ts = strtotime($fecha_hasta);
    if ($hasta_ts < $desde_ts) {
        $error = '⚠️ Fechas inválidas: la fecha "Hasta" no puede ser anterior a la fecha "Desde".';
    }
}

// Obtener datos del modelo solo si no hay error
// Usamos 'todos' como período por defecto, pero las fechas tienen prioridad en getFiltroFecha
$categorias = $error ? [] : Reporte::ventasPorCategoria('todos', $fecha_desde, $fecha_hasta);
$stats = $error ? [] : Reporte::estadisticasPeriodo('todos', $fecha_desde, $fecha_hasta);
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


.filters-section {
    background: var(--surface);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: bold;
    color: var(--secondary);
}

.filter-group select,
.filter-group input[type="date"] {
    padding: 8px 12px;
    border: 1px solid var(--primary);
    border-radius: 4px;
    font-size: 14px;
    background: white;
    color: var(--text);
}

.apply-btn {
    background: var(--secondary);
    color: var(--text-light);
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(161, 134, 111, 0.3);
}

.apply-btn:hover {
    background: #8b5e46;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 134, 111, 0.4);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--surface);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid var(--accent);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(161, 134, 111, 0.15);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: var(--secondary);
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.stat-card .value {
    font-size: 2em;
    font-weight: bold;
    color: var(--secondary);
}

.categorias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.categoria-card {
    background: var(--surface);
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid var(--accent);
}

.categoria-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 134, 111, 0.15);
}

.categoria-header {
    background: linear-gradient(135deg, var(--secondary) 0%, #8b5e46 100%);
    color: var(--text-light);
    padding: 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.categoria-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}

.categoria-header h3 {
    margin: 0;
    font-size: 1.5em;
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
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
    border-bottom: 1px solid var(--accent);
}

.categoria-stat:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.stat-label {
    color: var(--secondary);
    font-weight: 500;
}

.stat-value {
    font-weight: bold;
    color: var(--text);
}

.revenue-value {
    color: var(--secondary);
    font-weight: 600;
}

.percentage-bar {
    width: 100%;
    height: 8px;
    background: var(--accent);
    border-radius: 4px;
    overflow: hidden;
    margin-top: 5px;
}

.percentage-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--secondary), #8b5e46);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: var(--text);
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

.back-btn {
    display: inline-block;
    background: var(--secondary);
    color: var(--text-light);
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.2s ease;
    margin-bottom: 1.5rem;
}

.back-btn:hover {
    background-color: #8b5e46;
    text-decoration: none;
    color: var(--text-light);
}
</style>

<div class="report-container">
    <div class="report-header">
        <h1>📊 Ventas por Categoría</h1>
        <p>Análisis de rendimiento por categoría de productos</p>
        <?php if ($fecha_desde && $fecha_hasta): ?>
            <p style="margin-top: 10px; font-size: 0.9em; opacity: 0.9;">
                📅 Período: del <?= htmlspecialchars($fecha_desde) ?> al <?= htmlspecialchars($fecha_hasta) ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Contenedor de alerta para fechas inválidas -->
    <div id="alerta-fechas" 
         style="display:<?= $error ? 'block' : 'none' ?>; 
                background:#f8d7da; 
                color:#721c24; 
                border:1px solid #f5c6cb; 
                border-radius:6px; 
                padding:10px; 
                margin-bottom:1rem;">
        <?= htmlspecialchars($error) ?>
    </div>

    <div class="filters-section">
        <div class="filter-group">
            <label for="fecha_desde">Fecha Desde:</label>
            <input type="date" name="fecha_desde" id="fecha_desde" 
                   value="<?= htmlspecialchars($fecha_desde) ?>" 
                   onchange="validarFechas()">
        </div>
        
        <div class="filter-group">
            <label for="fecha_hasta">Fecha Hasta:</label>
            <input type="date" name="fecha_hasta" id="fecha_hasta" 
                   value="<?= htmlspecialchars($fecha_hasta) ?>" 
                   onchange="validarFechas()">
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
function validarFechas() {
    const fechaDesde = document.getElementById('fecha_desde').value;
    const fechaHasta = document.getElementById('fecha_hasta').value;
    const alerta = document.getElementById('alerta-fechas');
    
    if (fechaDesde && fechaHasta && fechaHasta < fechaDesde) {
        alerta.style.display = 'block';
        alerta.innerText = '⚠️ Fechas inválidas: la fecha "Hasta" no puede ser anterior a la fecha "Desde".';
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
    
    // Limpiar parámetros de período anterior
    url.searchParams.delete('periodo');
    
    // Agregar nuevos parámetros de fecha
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
    
    window.location.href = url.toString();
}
</script>

<div style="margin-top: 2rem; text-align: center;">
    <a href="<?= url('reportes') ?>" class="back-btn">← Volver a Reportes</a>
</div>
