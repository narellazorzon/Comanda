<?php
// src/views/auth/login.php
require_once __DIR__ . '/../../../vendor/autoload.php';
use App\Controllers\AuthController;
use App\Config\CsrfToken;

// Determinar la ruta base del proyecto
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url = $protocol . '://' . $host . dirname($script_name);

// Si es POST, procesamos el login y salimos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthController::login();
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión — Comanda</title>
  <!-- Incluimos tu CSS -->
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/login.css?v=<?= time() ?>">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome para iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <!-- Fondo con gradiente animado -->
  <div class="background-animation">
    <div class="gradient-orb orb-1"></div>
    <div class="gradient-orb orb-2"></div>
    <div class="gradient-orb orb-3"></div>
  </div>

  <main class="login-container">
    <!-- Header con logo y título -->
    <div class="login-header">
      <div class="logo-container">
        <img class="logo-img" src="<?= $base_url ?>/assets/img/logo.png?v=<?= time() ?>" alt="Logo Comanda">
        <div class="logo-glow"></div>
      </div>
      <h1 class="welcome-title">Bienvenido</h1>
      <p class="welcome-subtitle">Inicia sesión para acceder al sistema</p>
    </div>

    <!-- Mensajes de estado -->
    <?php if (isset($_GET['error'])): ?>
      <div class="message error-message">
        <i class="fas fa-exclamation-triangle error-icon"></i>
        <span class="message-text"><?= htmlspecialchars($_GET['error']) ?></span>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
      <div class="message success-message">
        <i class="fas fa-check-circle success-icon"></i>
        <span class="message-text"><?= htmlspecialchars($_GET['success']) ?></span>
      </div>
    <?php endif; ?>

    <!-- Formulario de login -->
    <form method="post" action="<?= $base_url ?>/index.php?route=login" id="loginForm" class="login-form">
      <?= CsrfToken::field() ?>
      
      <div class="input-group">
        <label for="email" class="input-label">
          <i class="fas fa-envelope"></i>
          Email
        </label>
        <div class="input-wrapper">
          <input type="email" id="email" name="email" placeholder="tu@email.com" 
                 value="<?= htmlspecialchars($_GET['email'] ?? '') ?>" required>
          <div class="input-border"></div>
        </div>
      </div>

      <div class="input-group">
        <label for="password" class="input-label">
          <i class="fas fa-lock"></i>
          Contraseña
        </label>
        <div class="input-wrapper">
          <input type="password" id="password" name="password" placeholder="••••••••" required>
          <button type="button" class="password-toggle" id="passwordToggle">
            <i class="fas fa-eye"></i>
          </button>
          <div class="input-border"></div>
        </div>
      </div>

      <button type="submit" id="submitBtn" class="login-button">
        <span class="button-text">Ingresar</span>
        <div class="button-loader">
          <div class="spinner"></div>
        </div>
      </button>
    </form>

    <!-- Enlaces adicionales -->
    <div class="login-footer">
      <a href="#" class="recovery-link">
        <i class="fas fa-key"></i>
        ¿Olvidaste tu contraseña?
      </a>
    </div>
  </main>

<script src="/public/assets/js/login-form-manager.js"></script>
</body>
</html>
