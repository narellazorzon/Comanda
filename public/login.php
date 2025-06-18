<?php
// public/login.php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Controllers\AuthController;

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
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <main style="max-width: 400px; margin: 3rem auto; padding: 1rem;">
    <h2>Iniciar sesión</h2>
    <?php if (!empty($_GET['error'])): ?>
      <p class="error" style="color:red;"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>
    <form method="post" action="login.php">
      <label>Email:</label>
      <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

      <label>Contraseña:</label>
      <input type="password" name="password" required>

      <button type="submit">Entrar</button>
    </form>
  </main>
</body>
</html>
