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
  <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<main class="login-container">
  <div class="logo">
  <img class="logo-img" src="assets/img/logo.png" alt="Logo Comanda">
  </div>
  <h2>Bienvenido</h2>

  <form method="post" action="login.php">
    <label>Email:</label>
    <input type="email" id="email" name="email" placeholder="ejemplo@correo.com" required>



    <label>Contraseña:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Ingresar</button>
  </form>

  <div class="recovery">
    ¿Has olvidado tu contraseña?<br>
    Haz <a href="#">click</a> aquí para recuperarla
  </div>
</main>
</body>
</html>
