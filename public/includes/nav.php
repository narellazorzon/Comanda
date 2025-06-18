<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$rol = $_SESSION['user']['rol'] ?? '';
?>
<nav>
  <?php if ($rol === 'administrador'): ?>
    <a href="index.php">Inicio</a> |
    <a href="cme_mesas.php">Mesas</a> |
    <a href="cme_pedidos.php">Pedidos</a> |
    <a href="cme_mozos.php">Mozos</a> |
    <a href="cme_carta.php">Carta</a> |
    <a href="reportes/platos_mas_vendidos.php">Reportes</a> |
  <?php elseif ($rol === 'mozo'): ?>
    <a href="estado_pedidos.php">Mis Pedidos</a> |
    <a href="llamados.php">Llamados Mesa</a> |
  <?php endif; ?>
  <a href="logout.php">Cerrar sesión</a>
</nav>
