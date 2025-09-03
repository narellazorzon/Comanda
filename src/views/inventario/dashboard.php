<?php
// src/views/inventario/dashboard.php
if (!isset($estadisticas) || !isset($stock_bajo) || !isset($resumen_categorias)) {
    header('Location: index.php?route=inventario');
    exit;
}
?>

<style>
.inventario-dashboard {
    padding: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.stat-card.warning {
    border-left: 4px solid #ffc107;
}

.stat-card.danger {
    border-left: 4px solid #dc3545;
}

.stat-card.success {
    border-left: 4px solid #28a745;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.alerts-section {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.alert-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--accent);
}

.alert-item:last-child {
    border-bottom: none;
}

.alert-item.critico {
    background: rgba(220, 53, 69, 0.1);
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.categoria-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.categoria-card {
    background: var(--surface);
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.btn-primary {
    background: var(--secondary);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
}

.btn-primary:hover {
    background: #8b5e46;
    color: white;
    text-decoration: none;
}
</style>

<div class="inventario-dashboard">
    <div class="page-header">
        <h1>📦 Dashboard de Inventario</h1>
        <p>Control y gestión del stock de productos</p>
    </div>

    <!-- Estadísticas principales -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-value"><?= $estadisticas['items_disponibles'] ?></div>
            <div class="stat-label">Items Disponibles</div>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-value"><?= $estadisticas['items_agotados'] ?></div>
            <div class="stat-label">Items Agotados</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-value"><?= $estadisticas['items_stock_bajo'] ?></div>
            <div class="stat-label">Stock Bajo</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">$<?= number_format($estadisticas['valor_total_inventario'], 2) ?></div>
            <div class="stat-label">Valor Total</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= $estadisticas['porcentaje_disponibilidad'] ?>%</div>
            <div class="stat-label">Disponibilidad</div>
        </div>
    </div>

    <!-- Alertas de stock bajo -->
    <?php if (!empty($stock_bajo)): ?>
    <div class="alerts-section">
        <h3>⚠️ Alertas de Stock Bajo</h3>
        
        <?php foreach ($stock_bajo as $item): ?>
        <div class="alert-item <?= $item['estado'] === 'agotado' ? 'critico' : '' ?>">
            <div>
                <strong><?= htmlspecialchars($item['item_nombre']) ?></strong>
                <small>(<?= htmlspecialchars($item['categoria']) ?>)</small>
                <?php if ($item['estado'] === 'agotado'): ?>
                    <span style="color: #dc3545; font-weight: bold;">- AGOTADO</span>
                <?php endif; ?>
            </div>
            <div>
                <span style="color: #dc3545; font-weight: bold;">
                    <?= $item['cantidad_disponible'] ?> <?= $item['unidad_medida'] ?>
                </span>
                <small>(mín: <?= $item['cantidad_minima'] ?>)</small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Resumen por categorías -->
    <div class="alerts-section">
        <h3>📊 Resumen por Categorías</h3>
        
        <div class="categoria-summary">
            <?php foreach ($resumen_categorias as $categoria): ?>
            <div class="categoria-card">
                <h4><?= htmlspecialchars($categoria['categoria']) ?></h4>
                <div>
                    <strong><?= $categoria['items_disponibles'] ?></strong> de <strong><?= $categoria['total_items'] ?></strong> disponibles
                </div>
                <?php if ($categoria['items_agotados'] > 0): ?>
                <div style="color: #dc3545;">
                    🚫 <?= $categoria['items_agotados'] ?> agotados
                </div>
                <?php endif; ?>
                <?php if ($categoria['items_stock_bajo'] > 0): ?>
                <div style="color: #ffc107;">
                    ⚠️ <?= $categoria['items_stock_bajo'] ?> con stock bajo
                </div>
                <?php endif; ?>
                <div style="color: #6c757d; font-size: 0.9rem;">
                    Stock promedio: <?= number_format($categoria['promedio_stock'], 1) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="alerts-section">
        <h3>💡 Sistema de Inventarios Básico</h3>
        <div style="color: var(--text); line-height: 1.6;">
            <p><strong>Funcionalidades implementadas:</strong></p>
            <ul>
                <li>✅ <strong>Control automático:</strong> Stock se descuenta automáticamente con cada venta</li>
                <li>✅ <strong>Alertas inteligentes:</strong> Notificaciones cuando stock está por debajo del mínimo</li>
                <li>✅ <strong>Historial completo:</strong> Seguimiento de todos los movimientos de inventario</li>
                <li>✅ <strong>Validaciones:</strong> Controles de seguridad para prevenir errores</li>
            </ul>
            
            <p><strong>Para activar el sistema:</strong></p>
            <ol>
                <li>Ejecutar <code>database/inventario.sql</code> para crear las tablas</li>
                <li>Revisar y actualizar stock inicial según necesidades</li>
                <li>Configurar niveles mínimos apropiados para cada item</li>
                <li>Monitorear alertas regularmente</li>
            </ol>
        </div>
    </div>
</div>