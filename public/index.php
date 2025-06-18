<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/includes/header.php';
?>
<h1>Bienvenido, <?= htmlspecialchars($_SESSION['user']['nombre']) ?></h1>
<nav>
  <ul>
    <li><a href="cme_mesas.php">Mesas</a></li>
    <li><a href="cme_pedidos.php">Pedidos</a></li>
    <li><a href="cme_mozos.php">Mozos</a></li>
    <li><a href="logout.php">Cerrar sesión</a></li>
  </ul>
</nav>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
