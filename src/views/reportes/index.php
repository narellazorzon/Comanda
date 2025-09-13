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

// Obtener estadísticas generales del mes actual
$stats = Reporte::estadisticasPeriodo('mes');

// Diagnóstico para debuggear problemas
$diagnostico = Reporte::diagnosticar();
?>

<style>
/* Usar las mismas variables de color del dashboard principal */
/* Variables CSS removidas - usar las del style.css global para mantener consistencia */

body {
    background-color: var(--background);
    font-family: "Segoe UI", Tahoma, sans-serif;
    margin: 0;
    padding: 0;
    color: var(--text);
}

/* Estilos de nav removidos - ahora usa el nav estándar del header */

main {
    max-width: 960px;
    margin: 1.5rem auto;
    padding: 0 1rem;
}

.welcome-section {
    margin-bottom: 2rem;
}

.welcome-section h1 {
    color: var(--secondary);
    font-size: 2.5em;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.welcome-section p {
    color: var(--text);
    font-size: 1.1em;
    margin: 0;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-card .icon {
    font-size: 2.5em;
    margin-bottom: 15px;
    display: block;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: var(--text);
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.stat-card .value {
    font-size: 2.2em;
    font-weight: bold;
    color: var(--secondary);
    margin-bottom: 5px;
}

.stat-card .subtitle {
    color: var(--text);
    font-size: 0.9em;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.report-card {
    background: var(--surface);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.report-card-header {
    padding: 1.5rem;
    color: var(--text-light);
    text-align: center;
}

.report-card-header.platos {
    background: var(--secondary);
}

.report-card-header.categorias {
    background: var(--primary);
}

.report-card-header.mozos {
    background: var(--secondary);
}

.report-card-header .icon {
    font-size: 2.5em;
    margin-bottom: 15px;
    display: block;
}

.report-card-header h3 {
    margin: 0 0 10px 0;
    font-size: 1.4em;
    font-weight: 600;
}

.report-card-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95em;
}

.report-card-body {
    padding: 1.5rem;
}

.report-features {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
}

.report-features li {
    padding: 8px 0;
    border-bottom: 1px solid var(--accent);
    color: var(--text);
    font-size: 0.9em;
}

.report-features li:last-child {
    border-bottom: none;
}

.report-features li:before {
    content: "✓";
    color: var(--secondary);
    font-weight: bold;
    margin-right: 10px;
}

.access-btn {
    display: inline-block;
    background: var(--secondary);
    color: var(--text-light);
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.2s ease;
    text-align: center;
    width: 100%;
    border: none;
    cursor: pointer;
    font-size: 1rem;
}

.access-btn:hover {
    background-color: #8b5e46;
    text-decoration: none;
    color: var(--text-light);
}

.info-section {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.info-section h2 {
    color: var(--secondary);
    margin-bottom: 1rem;
    font-size: 1.8em;
}

.info-section p {
    color: var(--text);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.info-section ul {
    color: var(--text);
    line-height: 1.6;
}

.info-section ul li {
    margin-bottom: 0.5rem;
}

@media (max-width: 600px) {
    .welcome-section h1 {
        font-size: 2em;
    }
    
    .stats-overview {
        grid-template-columns: 1fr;
    }
    
    .reports-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .report-card-header {
        padding: 1rem;
    }
    
    .report-card-body {
        padding: 1rem;
    }
}
</style>

<!-- Contenido dentro del main que ya viene del header -->
    <div class="welcome-section">
        <h1>Bienvenido, <?= htmlspecialchars($_SESSION['user']['nombre']) ?></h1>
        <p>Sistema de Reportes - Análisis de Ventas y Rendimiento</p>
    </div>

    <div class="stats-overview">
        <div class="stat-card">
            <span class="icon">📋</span>
            <h3>Total de Pedidos</h3>
            <div class="value"><?= number_format($stats['total_pedidos'] ?? 0) ?></div>
            <div class="subtitle">Este mes</div>
        </div>
        <div class="stat-card">
            <span class="icon">💰</span>
            <h3>Ingresos Totales</h3>
            <div class="value">$<?= number_format($stats['ingresos_totales'] ?? 0, 2) ?></div>
            <div class="subtitle">Este mes</div>
        </div>
        <div class="stat-card">
            <span class="icon">📈</span>
            <h3>Promedio por Pedido</h3>
            <div class="value">$<?= number_format($stats['promedio_pedido'] ?? 0, 2) ?></div>
            <div class="subtitle">Este mes</div>
        </div>
        <div class="stat-card">
            <span class="icon">👥</span>
            <h3>Mozos Activos</h3>
            <div class="value"><?= number_format($stats['mozos_activos'] ?? 0) ?></div>
            <div class="subtitle">Este mes</div>
        </div>
    </div>

    <div class="reports-grid">
        <div class="report-card">
            <div class="report-card-header platos">
                <span class="icon">🍽️</span>
                <h3>Platos Más Vendidos</h3>
                <p>Análisis detallado de los productos más populares</p>
            </div>
            <div class="report-card-body">
                <ul class="report-features">
                    <li>Ranking de platos por cantidad vendida</li>
                    <li>Análisis por período (semana, mes, año)</li>
                    <li>Ingresos generados por cada plato</li>
                    <li>Estadísticas de pedidos</li>
                    <li>Filtros personalizables</li>
                </ul>
                <a href="<?= url('reportes/platos-mas-vendidos') ?>" class="access-btn">Ver Reporte</a>
            </div>
        </div>

        <div class="report-card">
            <div class="report-card-header categorias">
                <span class="icon">📊</span>
                <h3>Ventas por Categoría</h3>
                <p>Rendimiento de cada categoría de productos</p>
            </div>
            <div class="report-card-body">
                <ul class="report-features">
                    <li>Análisis por categoría de productos</li>
                    <li>Porcentajes de participación</li>
                    <li>Comparación de rendimiento</li>
                    <li>Gráficos visuales de distribución</li>
                    <li>Métricas de rentabilidad</li>
                </ul>
                <a href="<?= url('reportes/ventas-categoria') ?>" class="access-btn">Ver Reporte</a>
            </div>
        </div>

        <div class="report-card">
            <div class="report-card-header mozos">
                <span class="icon">👥</span>
                <h3>Rendimiento de Mozos</h3>
                <p>Evaluación de productividad del personal</p>
            </div>
            <div class="report-card-body">
                <ul class="report-features">
                    <li>Ranking de mozos por ventas</li>
                    <li>Análisis de productividad</li>
                    <li>Promedio de pedidos por mozo</li>
                    <li>Sistema de calificación</li>
                    <li>Métricas de rendimiento</li>
                </ul>
                <a href="<?= url('reportes/rendimiento-mozos') ?>" class="access-btn">Ver Reporte</a>
            </div>
        </div>
    </div>

    <div class="info-section">
        <h2>💡 Cómo usar los reportes</h2>
        <p>Nuestro sistema de reportes te permite analizar el rendimiento de tu restaurante desde múltiples perspectivas:</p>
        
        <ul>
            <li><strong>Platos Más Vendidos:</strong> Identifica qué productos son más populares para optimizar tu menú y inventario.</li>
            <li><strong>Ventas por Categoría:</strong> Analiza qué categorías de productos generan más ingresos para tomar decisiones estratégicas.</li>
            <li><strong>Rendimiento de Mozos:</strong> Evalúa la productividad de tu equipo para reconocer el buen trabajo e identificar áreas de mejora.</li>
        </ul>
        
        <p><strong>Consejo:</strong> Revisa estos reportes regularmente (semanal o mensualmente) para identificar tendencias y tomar decisiones informadas sobre tu negocio.</p>
    </div>

    <!-- Información de diagnóstico -->
    <?php if ($diagnostico['pedidos_reporteables'] == 0): ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <h3 style="color: #856404; margin-top: 0;">⚠️ Información importante sobre reportes</h3>
            <p style="color: #856404; margin-bottom: 10px;">
                Los reportes necesitan pedidos completados para generar estadísticas. Estado actual de datos:
            </p>
            <ul style="color: #856404;">
                <li><strong>Pedidos totales:</strong> <?= $diagnostico['pedidos_totales'] ?></li>
                <li><strong>Detalles de pedido:</strong> <?= $diagnostico['detalles_pedido'] ?></li>
                <li><strong>Pedidos completados (reporteables):</strong> <?= $diagnostico['pedidos_reporteables'] ?></li>
            </ul>
            <?php if (!empty($diagnostico['pedidos_por_estado'])): ?>
                <p style="color: #856404;"><strong>Pedidos por estado:</strong></p>
                <ul style="color: #856404;">
                    <?php foreach ($diagnostico['pedidos_por_estado'] as $estado): ?>
                        <li><?= ucfirst($estado['estado']) ?>: <?= $estado['cantidad'] ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p style="color: #856404;">
                <strong>Para ver reportes con datos:</strong> Crea pedidos y cambia su estado a "Pagado" o "Cerrado".
            </p>
        </div>
    <?php endif; ?>
