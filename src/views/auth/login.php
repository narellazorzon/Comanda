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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const passwordToggle = document.getElementById('passwordToggle');
    const buttonText = submitBtn.querySelector('.button-text');
    const buttonLoader = submitBtn.querySelector('.button-loader');
    
    // Auto-focus en el campo email al cargar la página
    emailInput.focus();
    
    // Toggle de visibilidad de contraseña
    passwordToggle.addEventListener('click', function() {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        this.querySelector('i').className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
    
    // Validación en tiempo real del email
    emailInput.addEventListener('input', function() {
        const email = this.value.trim();
        const inputWrapper = this.closest('.input-wrapper');
        
        if (email && !isValidEmail(email)) {
            inputWrapper.classList.add('error');
        } else {
            inputWrapper.classList.remove('error');
        }
    });
    
    // Limpiar estado de error al escribir en la contraseña
    passwordInput.addEventListener('input', function() {
        const inputWrapper = this.closest('.input-wrapper');
        inputWrapper.classList.remove('error');
    });
    
    // Efectos de focus en los inputs
    [emailInput, passwordInput].forEach(input => {
        input.addEventListener('focus', function() {
            const inputWrapper = this.closest('.input-wrapper');
            inputWrapper.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            const inputWrapper = this.closest('.input-wrapper');
            inputWrapper.classList.remove('focused');
        });
    });
    
    // Prevenir envío múltiple del formulario
    form.addEventListener('submit', function(e) {
        if (submitBtn.disabled) {
            e.preventDefault();
            return false;
        }
        
        // Validar campos antes del envío
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        
        if (!email || !password) {
            e.preventDefault();
            showErrorAlert('Por favor, completa todos los campos');
            return false;
        }
        
        if (!isValidEmail(email)) {
            e.preventDefault();
            const inputWrapper = emailInput.closest('.input-wrapper');
            inputWrapper.classList.add('error');
            showErrorAlert('El formato del email no es válido');
            return false;
        }
        
        // Mostrar estado de carga
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
        buttonText.style.opacity = '0';
        buttonLoader.style.opacity = '1';
        
        // Mantener el email en la URL para persistencia
        const formData = new FormData(form);
        formData.append('email_param', email);
    });
    
    // Función para validar email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Función para mostrar alertas de error
    function showErrorAlert(message) {
        // Si ya existe un mensaje de error, lo actualizamos
        let errorDiv = document.querySelector('.error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'message error-message';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle error-icon"></i><span class="message-text"></span>';
            form.parentNode.insertBefore(errorDiv, form);
        }
        errorDiv.querySelector('.message-text').textContent = message;
        errorDiv.style.display = 'flex';
    }
    
    // Auto-ocultar mensajes de error después de 5 segundos
    const errorMessage = document.querySelector('.error-message');
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.opacity = '0';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 300);
        }, 5000);
    }
    
    // Auto-ocultar mensajes de éxito después de 3 segundos
    const successMessage = document.querySelector('.success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.opacity = '0';
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 300);
        }, 3000);
    }
});
</script>
</body>
</html>
