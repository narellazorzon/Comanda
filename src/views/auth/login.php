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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión — Comanda</title>
  <!-- Incluimos tu CSS -->
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/login.css?v=<?= time() ?>">
</head>
<body>
<main class="login-container">
  <div class="logo">
  <img class="logo-img" src="<?= $base_url ?>/assets/img/logo.png" alt="Logo Comanda">
  </div>
  <h2>Bienvenido</h2>

  <?php if (isset($_GET['error'])): ?>
    <div class="error-message">
      <span class="error-icon">⚠️</span>
      <?= htmlspecialchars($_GET['error']) ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['success'])): ?>
    <div class="success-message">
      <span class="success-icon">✅</span>
      <?= htmlspecialchars($_GET['success']) ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= $base_url ?>/index.php?route=login" id="loginForm">
    <?= CsrfToken::field() ?>
    <label>Email:</label>
    <input type="email" id="email" name="email" placeholder="ejemplo@correo.com" 
           value="<?= htmlspecialchars($_GET['email'] ?? '') ?>" required>

    <label>Contraseña:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" id="submitBtn">Ingresar</button>
  </form>

  <div class="recovery">
    ¿Has olvidado tu contraseña?<br>
    Haz <a href="#">click</a> aquí para recuperarla
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    
    // Auto-focus en el campo email al cargar la página
    emailInput.focus();
    
    // Validación en tiempo real del email
    emailInput.addEventListener('input', function() {
        const email = this.value.trim();
        if (email && !isValidEmail(email)) {
            this.classList.add('error');
        } else {
            this.classList.remove('error');
        }
    });
    
    // Limpiar estado de error al escribir en la contraseña
    passwordInput.addEventListener('input', function() {
        this.classList.remove('error');
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
            emailInput.classList.add('error');
            showErrorAlert('El formato del email no es válido');
            return false;
        }
        
        // Deshabilitar botón para prevenir doble envío
        submitBtn.disabled = true;
        submitBtn.textContent = 'Ingresando...';
        
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
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = '<span class="error-icon">⚠️</span><span class="error-text"></span>';
            form.parentNode.insertBefore(errorDiv, form);
        }
        errorDiv.querySelector('.error-text').textContent = message;
    }
    
    // Auto-ocultar mensajes de error después de 5 segundos
    const errorMessage = document.querySelector('.error-message');
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.opacity = '0';
            setTimeout(() => {
                errorMessage.remove();
            }, 300);
        }, 5000);
    }
});
</script>
</body>
</html>
