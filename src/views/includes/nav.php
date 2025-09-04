<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$rol = $_SESSION['user']['rol'] ?? '';

// Determinar la ruta base del proyecto
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url = $protocol . '://' . $host . dirname($script_name);

// Determinar la ruta base según la ubicación actual
$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_in_reportes = strpos($current_path, '/reportes/') !== false;
$base_path = $is_in_reportes ? '../' : '';
?>
<nav class="navbar">
  <div class="nav-container">
    <div class="nav-logo">
      <a href="<?= $base_url ?>/index.php?route=home" style="text-decoration: none; display: block;">
        <img src="<?= $base_url ?>/assets/img/logo.png" alt="Comanda" style="height: 60px; width: auto; max-height: 60px;">
      </a>
    </div>
    
    <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation">
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
    </button>
    
    <div class="nav-menu" id="nav-menu">
      <?php if ($rol === 'administrador'): ?>
        <a href="<?= $base_url ?>/index.php?route=mesas" class="nav-link">🪑 Mesas</a>
        <a href="<?= $base_url ?>/index.php?route=pedidos" class="nav-link">🍽️ Pedidos</a>
        <a href="<?= $base_url ?>/index.php?route=mozos" class="nav-link">👥 Mozos</a>
        <a href="<?= $base_url ?>/index.php?route=carta" class="nav-link">📋 Carta</a>
        <a href="<?= $base_url ?>/index.php?route=reportes" class="nav-link">📊 Reportes</a>
      <?php elseif ($rol === 'mozo'): ?>
        <a href="<?= $base_url ?>/index.php?route=mesas" class="nav-link">🪑 Ver Mesas</a>
        <a href="<?= $base_url ?>/index.php?route=carta" class="nav-link">📋 Ver Carta</a>
        <a href="<?= $base_url ?>/index.php?route=pedidos" class="nav-link">🍽️ Ver Pedidos</a>
        <a href="<?= $base_url ?>/index.php?route=llamados" class="nav-link">🔔 Llamados Mesa</a>
      <?php endif; ?>
      <a href="<?= $base_url ?>/index.php?route=logout" class="nav-link logout">🚪 Cerrar sesión</a>
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
