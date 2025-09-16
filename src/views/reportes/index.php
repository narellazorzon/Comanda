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
    margin-bottom: 1.5rem;
}

.welcome-section h1 {
    color: var(--secondary);
    font-size: 2em;
    margin: 0 0 8px 0;
    font-weight: bold;
}

.welcome-section p {
    color: var(--text);
    font-size: 1em;
    margin: 0;
    opacity: 0.8;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--surface);
    padding: 1rem;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.stat-card .icon {
    font-size: 1.8em;
    margin-bottom: 8px;
    display: block;
}

.stat-card h3 {
    margin: 0 0 6px 0;
    color: var(--text);
    font-size: 0.8em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.stat-card .value {
    font-size: 1.6em;
    font-weight: bold;
    color: var(--secondary);
    margin-bottom: 4px;
}

.stat-card .subtitle {
    color: var(--text);
    font-size: 0.75em;
    opacity: 0.8;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.report-card {
    background: var(--surface);
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.report-card-header {
    padding: 1rem;
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
    font-size: 1.8em;
    margin-bottom: 8px;
    display: block;
}

.report-card-header h3 {
    margin: 0 0 6px 0;
    font-size: 1.1em;
    font-weight: 600;
}

.report-card-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.8em;
}

.report-card-body {
    padding: 1rem;
}

.report-features {
    list-style: none;
    padding: 0;
    margin: 0 0 15px 0;
}

.report-features li {
    padding: 5px 0;
    border-bottom: 1px solid var(--accent);
    color: var(--text);
    font-size: 0.8em;
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
    padding: 0.6rem 1.2rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.2s ease;
    text-align: center;
    width: 100%;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
}

.access-btn:hover {
    background-color: #8b5e46;
    text-decoration: none;
    color: var(--text-light);
}

.info-section {
    background: var(--surface);
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.info-section h2 {
    color: var(--secondary);
    margin-bottom: 0.8rem;
    font-size: 1.4em;
}

.info-section p {
    color: var(--text);
    line-height: 1.5;
    margin-bottom: 0.8rem;
    font-size: 0.9em;
}

.info-section ul {
    color: var(--text);
    line-height: 1.5;
    font-size: 0.9em;
}

.info-section ul li {
    margin-bottom: 0.4rem;
}

@media (max-width: 600px) {
    .welcome-section h1 {
        font-size: 1.6em;
    }
    
    .stats-overview {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.8rem;
    }
    
    .reports-grid {
        grid-template-columns: 1fr;
        gap: 0.8rem;
    }
    
    .stat-card {
        padding: 0.8rem;
    }
    
    .stat-card .icon {
        font-size: 1.5em;
    }
    
    .stat-card .value {
        font-size: 1.4em;
    }
    
    .report-card-header {
        padding: 0.8rem;
    }
    
    .report-card-header .icon {
        font-size: 1.5em;
    }
    
    .report-card-body {
        padding: 0.8rem;
    }
    
    .info-section {
        padding: 0.8rem;
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
            <h3>Personal Activo</h3>
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
                <h3>Rendimiento del Personal</h3>
                <p>Evaluación de productividad del personal</p>
            </div>
            <div class="report-card-body">
                <ul class="report-features">
                    <li>Ranking del personal por ventas</li>
                    <li>Análisis de productividad</li>
                    <li>Promedio de pedidos por empleado</li>
                    <li>Sistema de calificación</li>
                    <li>Métricas de rendimiento</li>
                </ul>
                <a href="<?= url('reportes/rendimiento-personal') ?>" class="access-btn">Ver Reporte</a>
            </div>
        </div>
    </div>

    <div class="info-section">
        <h2>💡 Cómo usar los reportes</h2>
        <p>Nuestro sistema de reportes te permite analizar el rendimiento de tu restaurante desde múltiples perspectivas:</p>
        
        <ul>
            <li><strong>Platos Más Vendidos:</strong> Identifica qué productos son más populares para optimizar tu menú y inventario.</li>
            <li><strong>Ventas por Categoría:</strong> Analiza qué categorías de productos generan más ingresos para tomar decisiones estratégicas.</li>
            <li><strong>Rendimiento del Personal:</strong> Evalúa la productividad de tu equipo para reconocer el buen trabajo e identificar áreas de mejora.</li>
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
