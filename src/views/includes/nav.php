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
      <?php
      // Si está en modo QR (sin rol de usuario), hacer refresh a la carta
      $modo_qr = $_SESSION['modo_consumo_qr'] ?? null;
      $id_mesa_qr = $_SESSION['mesa_qr'] ?? null;
      $is_qr_mode = empty($rol) && ($modo_qr !== null || $id_mesa_qr !== null);
      
      if ($is_qr_mode) {
          // En modo QR, recargar la página actual manteniendo los parámetros
          $logo_href = "javascript:void(0);";
          $logo_onclick = "window.location.reload();";
      } else {
          // Si no está en modo QR, redirigir a home como antes
          $logo_href = $base_url . "/index.php?route=home";
          $logo_onclick = "";
      }
      ?>
      <a href="<?= $logo_href ?>" <?= !empty($logo_onclick) ? 'onclick="' . $logo_onclick . ' return false;"' : '' ?> style="text-decoration: none; display: block;">
        <img src="<?= $base_url ?>/assets/img/logo.png" alt="Comanda" style="height: 60px; width: auto; max-height: 60px;">
      </a>
    </div>
    
    <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation">
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
    </button>
    
    <div class="nav-menu" id="nav-menu">
      <?php if (empty($rol)): ?>
        <button id="nav-llamar-mozo" class="nav-link btn-llamar-mozo" style="display:none;">🔔 Llamar Mozo</button>
        <button id="nav-cart-button" class="nav-link" style="background:none;border:none;cursor:pointer;">🛒 Carrito</button>
      <?php elseif ($rol === 'administrador'): ?>
        <a href="<?= $base_url ?>/index.php?route=mesas" class="nav-link">🪑 Mesas</a>
        <a href="<?= $base_url ?>/index.php?route=pedidos" class="nav-link">🍽️ Pedidos</a>
        <a href="<?= $base_url ?>/index.php?route=mozos" class="nav-link">👥 Personal</a>
        <a href="<?= $base_url ?>/index.php?route=carta" class="nav-link">📋 Carta</a>
        <a href="<?= $base_url ?>/index.php?route=reportes" class="nav-link">📊 Reportes</a>
        <a href="<?= $base_url ?>/index.php?route=admin/qr-offline" class="nav-link">📱 QR Mesas</a>
      <?php elseif ($rol === 'mozo'): ?>
        <a href="<?= $base_url ?>/index.php?route=mesas" class="nav-link">🪑 Ver Mesas</a>
        <a href="<?= $base_url ?>/index.php?route=carta" class="nav-link">📋 Ver Carta</a>
        <a href="<?= $base_url ?>/index.php?route=pedidos" class="nav-link">🍽️ Ver Pedidos</a>
        <a href="<?= $base_url ?>/index.php?route=llamados" class="nav-link">🔔 Llamados Mesa</a>
      <?php endif; ?>
      <?php if ($rol): ?>
        <a href="<?= $base_url ?>/index.php?route=logout" class="nav-link logout">🚪 Cerrar sesión</a>
      <?php else: ?>
        <?php
        // Ocultar "Iniciar sesión" cuando se está en la vista de cliente/carta
        $current_route = $_GET['route'] ?? 'cliente';
        $is_cliente_view = ($current_route === 'cliente' || strpos($_SERVER['PHP_SELF'] ?? '', 'cliente') !== false);
        if (!$is_cliente_view):
        ?>
        <a href="<?= $base_url ?>/index.php?route=login" class="nav-link">🔐 Iniciar sesión</a>
        <?php endif; ?>
      <?php endif; ?>
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

    // Botón de carrito en el header para visitantes
    const navCartBtn = document.getElementById('nav-cart-button');
    if (navCartBtn) {
        navCartBtn.addEventListener('click', function() {
            const modal = document.getElementById('cart-modal');
            if (modal) {
                modal.style.display = 'flex';
                const evt = new Event('renderCart');
                document.dispatchEvent(evt);
            } else {
                window.location.href = '<?= $base_url ?>/index.php?route=cliente';
            }
        });
    }
});
</script>
