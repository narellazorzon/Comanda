<?php
// src/views/mesas/create.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Mesa;
use App\Models\Usuario;

// La autenticación ya fue verificada en el router (index.php)
// Solo necesitamos asegurarnos de que la sesión esté disponible
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mesa = null;
$error = '';
$success = '';

// Si viene ?id= para editar, cargamos los datos de la mesa
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        $mesa = Mesa::find($id);
        if (!$mesa) {
            header('Location: ' . url('mesas', ['error' => 'Mesa no encontrada']));
            exit;
        }
    }
}

// Si viene un POST, delegamos al controlador y salimos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    MesaController::create();
    exit;
}

// Cargar mozos activos para el selector
$mozos = Usuario::getMozosActivos();
?>

<h2><?= isset($mesa) ? 'Editar Mesa' : 'Crear Nueva Mesa' ?></h2>

<?php if (isset($_GET['success'])): ?>
  <div style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
    <?= htmlspecialchars($_GET['success']) ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
    <?= htmlspecialchars($_GET['error']) ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= url('mesas/create') ?>">
  <?php if (isset($mesa)): ?>
    <input type="hidden" name="id" value="<?= $mesa['id_mesa'] ?>">
  <?php endif; ?>

  <label>Número de Mesa:</label>
  <input type="number" name="numero" required value="<?= htmlspecialchars($mesa['numero'] ?? '') ?>" min="1">

  <label>Ubicación:</label>
  <input type="text" name="ubicacion" required value="<?= htmlspecialchars($mesa['ubicacion'] ?? '') ?>">

  <label>Capacidad:</label>
  <input type="number" name="capacidad" required value="<?= htmlspecialchars($mesa['capacidad'] ?? '') ?>" min="1">

  <label>Mozo Asignado:</label>
  <select name="id_mozo" required>
    <option value="">Seleccionar mozo</option>
    <?php foreach ($mozos as $mozo): ?>
      <option value="<?= $mozo['id_usuario'] ?>"
              <?= (isset($mesa) && $mesa['id_mozo'] == $mozo['id_usuario']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($mozo['nombre_completo']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <?php if (isset($mesa)): ?>
    <label>Estado:</label>
    <select name="estado" required>
      <option value="activa" <?= $mesa['estado'] === 'activa' ? 'selected' : '' ?>>Activa</option>
      <option value="inactiva" <?= $mesa['estado'] === 'inactiva' ? 'selected' : '' ?>>Inactiva</option>
    </select>
  <?php endif; ?>

  <button type="submit"><?= isset($mesa) ? 'Actualizar Mesa' : 'Crear Mesa' ?></button>
</form>

<a href="<?= url('mesas') ?>" class="button" style="background-color: rgb(83, 52, 31); margin-top: 1rem;">
  ← Volver a la lista
</a>