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
<nav class="navbar">
  <div class="nav-container">
    <div class="nav-logo">
      <span>🍽️ Comanda</span>
    </div>
    
    <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation">
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
    </button>
    
    <div class="nav-menu" id="nav-menu">
      <?php if ($rol === 'administrador'): ?>
        <a href="<?= $base_path ?>index.php" class="nav-link">🏠 Inicio</a>
        <a href="<?= $base_path ?>cme_mesas.php" class="nav-link">🪑 Mesas</a>
        <a href="<?= $base_path ?>cme_pedidos.php" class="nav-link">🍽️ Pedidos</a>
        <a href="<?= $base_path ?>cme_mozos.php" class="nav-link">👥 Mozos</a>
        <a href="<?= $base_path ?>cme_carta.php" class="nav-link">📋 Carta</a>
        <a href="<?= $base_path ?>reportes/index.php" class="nav-link">📊 Reportes</a>
      <?php elseif ($rol === 'mozo'): ?>
        <a href="<?= $base_path ?>estado_pedidos.php" class="nav-link">🍽️ Mis Pedidos</a>
        <a href="<?= $base_path ?>llamados.php" class="nav-link">🔔 Llamados Mesa</a>
      <?php endif; ?>
      <a href="<?= $base_path ?>logout.php" class="nav-link logout">🚪 Cerrar sesión</a>
    </div>
  </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    
    navToggle.addEventListener('click', function() {
        navMenu.classList.toggle('active');
        navToggle.classList.toggle('active');
    });
    
    // Cerrar menú al hacer clic en un enlace (móvil)
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            navToggle.classList.remove('active');
        });
    });
    
    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
            navMenu.classList.remove('active');
            navToggle.classList.remove('active');
        }
    });
});
</script>
