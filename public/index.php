<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/includes/header.php';
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
            <a href="cme_mesas.php" class="button">Ver Mesas</a>
            <a href="alta_mesa.php" class="button" style="background: var(--accent); color: var(--text);">Nueva Mesa</a>
        </div>
    </div>

    <!-- Módulo 2: Gestión de Carta -->
    <div style="background: var(--surface); padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <h3 style="color: var(--secondary); margin-bottom: 1rem;">📋 Gestión de Carta</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Gestiona los items del menú, precios y disponibilidad.
        </p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="cme_carta.php" class="button">Ver Carta</a>
            <a href="alta_carta.php" class="button" style="background: var(--accent); color: var(--text);">Nuevo Ítem</a>
        </div>
    </div>

    <!-- Módulo 3: Gestión de Pedidos -->
    <div style="background: var(--surface); padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <h3 style="color: var(--secondary); margin-bottom: 1rem;">🍽️ Gestión de Pedidos</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Crea y gestiona pedidos, controla estados y totales.
        </p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="cme_pedidos.php" class="button">Ver Pedidos</a>
            <a href="alta_pedido.php" class="button" style="background: var(--accent); color: var(--text);">Nuevo Pedido</a>
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
            <a href="cme_mozos.php" class="button">Ver Mozos</a>
            <a href="alta_mozo.php" class="button" style="background: var(--accent); color: var(--text);">Nuevo Mozo</a>
        </div>
    </div>

    <div style="background: var(--surface); padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <h3 style="color: var(--secondary); margin-bottom: 1rem;">📊 Reportes</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Genera reportes de ventas y análisis.
        </p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="reportes/platos_mas_vendidos.php" class="button">Platos Populares</a>
            <a href="reportes/recaudacion_mensual.php" class="button">Recaudación</a>
            <a href="reportes/propina.php" class="button">Propinas</a>
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
            <a href="llamados.php" class="button">Ver Llamados</a>
        </div>
    </div>
    <?php endif; ?>

</div>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
