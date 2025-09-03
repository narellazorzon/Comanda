<?php
// src/views/home/index.php
require_once __DIR__ . '/../includes/header.php';

// Determinar la ruta base del proyecto
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url = $protocol . '://' . $host . dirname($script_name);
?>
<h1>Bienvenido, <?= htmlspecialchars($_SESSION['user']['nombre']) ?></h1>
<p style="color: #666; margin-bottom: 2rem;">
    Sistema de Gestión de Restaurante - Panel de Control
</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
    
    <!-- Módulo 1: Gestión de Mesas -->
    <div style="background: var(--surface); padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <h3 style="color: var(--secondary); margin-bottom: 1rem;">🏠 Gestión de Mesas</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Administra las mesas del restaurante, su ubicación y estado.
        </p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= $base_url ?>/index.php?route=mesas" class="button">Ver Mesas</a>
            <?php if ($_SESSION['user']['rol'] === 'administrador'): ?>
                <a href="<?= $base_url ?>/index.php?route=mesas/create" class="button" style="background: var(--accent); color: var(--text);">Nueva Mesa</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Módulo 2: Gestión de Carta -->
    <div style="background: var(--surface); padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <h3 style="color: var(--secondary); margin-bottom: 1rem;">📋 Gestión de Carta</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Gestiona los items del menú, precios y disponibilidad.
        </p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= $base_url ?>/index.php?route=carta" class="button">Ver Carta</a>
            <?php if ($_SESSION['user']['rol'] === 'administrador'): ?>
                <a href="<?= $base_url ?>/index.php?route=carta/create" class="button" style="background: var(--accent); color: var(--text);">Nuevo Ítem</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Módulo 3: Gestión de Pedidos -->
    <div style="background: var(--surface); padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <h3 style="color: var(--secondary); margin-bottom: 1rem;">🍽️ Gestión de Pedidos</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Crea y gestiona pedidos, controla estados y totales.
        </p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= $base_url ?>/index.php?route=pedidos" class="button">Ver Pedidos</a>
            <a href="<?= $base_url ?>/index.php?route=pedidos/create" class="button" style="background: var(--accent); color: var(--text);">Nuevo Pedido</a>
        </div>
    </div>

    <!-- Módulos Adicionales -->
    <?php if ($_SESSION['user']['rol'] === 'administrador'): ?>
    <div style="background: var(--surface); padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <h3 style="color: var(--secondary); margin-bottom: 1rem;">👥 Gestión de Personal</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Administra mozos y usuarios del sistema.
        </p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= $base_url ?>/index.php?route=mozos" class="button">Ver Mozos</a>
            <a href="<?= $base_url ?>/index.php?route=mozos/create" class="button" style="background: var(--accent); color: var(--text);">Nuevo Mozo</a>
        </div>
    </div>

    <div style="background: var(--surface); padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <h3 style="color: var(--secondary); margin-bottom: 1rem;">📊 Reportes</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Genera reportes de ventas y análisis.
        </p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= $base_url ?>/index.php?route=reportes/platos-mas-vendidos" class="button">Platos Populares</a>
            <a href="<?= $base_url ?>/index.php?route=reportes/recaudacion" class="button">Recaudación</a>
            <a href="<?= $base_url ?>/index.php?route=reportes/propina" class="button">Propinas</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Funciones de Mozo -->
    <?php if ($_SESSION['user']['rol'] === 'mozo'): ?>
    <div style="background: var(--surface); padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <h3 style="color: var(--secondary); margin-bottom: 1rem;">🔔 Llamados de Mesa</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Gestiona las solicitudes de atención de las mesas.
        </p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= $base_url ?>/index.php?route=llamados" class="button">Ver Llamados</a>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
