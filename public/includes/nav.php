<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$rol = $_SESSION['user']['rol'] ?? '';
?>
<nav>
  <button id="nav-toggle" aria-label="Menú">☰</button>
  <ul id="nav-menu">
  <?php if ($rol === 'administrador'): ?>
    <li><a href="index.php">Inicio</a></li>
    <li><a href="cme_mesas.php">Mesas</a></li>
    <li><a href="cme_pedidos.php">Pedidos</a></li>
    <li><a href="cme_mozos.php">Mozos</a></li>
    <li><a href="cme_carta.php">Carta</a></li>
    <li><a href="reportes/platos_mas_vendidos.php">Reportes</a></li>
  <?php elseif ($rol === 'mozo'): ?>
    <li><a href="estado_pedidos.php">Mis Pedidos</a></li>
    <li><a href="llamados.php">Llamados Mesa</a></li>
  <?php endif; ?>
    <li><a href="logout.php">Cerrar sesión</a></li>
  </ul>
</nav>
