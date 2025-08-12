<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$rol = $_SESSION['user']['rol'] ?? '';

// Determinar la ruta base según la ubicación actual
$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_in_reportes = strpos($current_path, '/reportes/') !== false;
$base_path = $is_in_reportes ? '../' : '';
?>
<nav>
  <?php if ($rol === 'administrador'): ?>
    <a href="<?= $base_path ?>index.php">Inicio</a> |
    <a href="<?= $base_path ?>cme_mesas.php">Mesas</a> |
    <a href="<?= $base_path ?>cme_pedidos.php">Pedidos</a> |
    <a href="<?= $base_path ?>cme_mozos.php">Mozos</a> |
    <a href="<?= $base_path ?>cme_carta.php">Carta</a> |
    <a href="<?= $base_path ?>reportes/index.php">Reportes</a> |
  <?php elseif ($rol === 'mozo'): ?>
    <a href="<?= $base_path ?>estado_pedidos.php">Mis Pedidos</a> |
    <a href="<?= $base_path ?>llamados.php">Llamados Mesa</a> |
  <?php endif; ?>
  <a href="<?= $base_path ?>logout.php">Cerrar sesión</a>
</nav>
