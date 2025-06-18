<?php
// public/alta_mozo.php

require_once __DIR__ . '/../vendor/autoload.php';
use App\Controllers\MozoController;

// Si viene un POST, delegamos al controlador y salimos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    MozoController::create();
    exit;
}

// Si viniera un GET con ?id= para editar podrías hacer lo mismo con MozoController::edit()
// ...

// Ahora mostramos la vista
require_once __DIR__ . '/includes/header.php';
?>

<h2><?= isset($mozo) ? 'Modificar Mozo' : 'Alta de Mozo' ?></h2>

<form method="post" action="alta_mozo.php">
  <label>Nombre:</label>
  <input type="text" name="nombre" required value="<?= $mozo['nombre'] ?? '' ?>">

  <label>Apellido:</label>
  <input type="text" name="apellido" required value="<?= $mozo['apellido'] ?? '' ?>">

  <label>Email:</label>
  <input type="email" name="email" required value="<?= $mozo['email'] ?? '' ?>">

  <?php if (!isset($mozo)): ?>
    <label>Contraseña:</label>
    <input type="password" name="contrasenia" required>
  <?php endif; ?>

  <button type="submit"><?= isset($mozo) ? 'Guardar cambios' : 'Crear Mozo' ?></button>
</form>

<?php
require_once __DIR__ . '/includes/footer.php';
