<?php
// src/views/mozos/create.php
require_once __DIR__ . '/../../../vendor/autoload.php';
use App\Controllers\MozoController;
use App\Models\Usuario;

// Solo iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Solo administradores pueden acceder
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ../../public/index.php?route=login');
    exit;
}

// Si viene un POST, delegamos al controlador y salimos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    MozoController::create();
    exit;
}

// Si viene ?id= para editar, cargamos los datos del mozo
$mozo = null;
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        $mozo = Usuario::find($id);
        if (!$mozo || $mozo['rol'] !== 'mozo') {
            header('Location: ../../public/index.php?route=mozos&error=' . urlencode('Mozo no encontrado'));
            exit;
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>

<h2><?= isset($mozo) ? 'Modificar Mozo' : 'Alta de Mozo' ?></h2>

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

<form method="post" action="../../public/index.php?route=mozos/create">
  <?php if (isset($mozo)): ?>
    <input type="hidden" name="id" value="<?= $mozo['id_usuario'] ?>">
  <?php endif; ?>
  
  <label>Nombre:</label>
  <input type="text" name="nombre" required value="<?= htmlspecialchars($mozo['nombre'] ?? '') ?>">

  <label>Apellido:</label>
  <input type="text" name="apellido" required value="<?= htmlspecialchars($mozo['apellido'] ?? '') ?>">

  <label>Email:</label>
  <input type="email" name="email" required value="<?= htmlspecialchars($mozo['email'] ?? '') ?>">

  <?php if (isset($mozo)): ?>
    <label>Estado:</label>
    <select name="estado" required>
      <option value="activo" <?= ($mozo['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
      <option value="inactivo" <?= ($mozo['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
    </select>

    <label>Nueva Contraseña (opcional):</label>
    <input type="password" name="contrasenia" placeholder="Dejar vacío para mantener la actual">
    <small style="color: #666; display: block; margin-top: 0.25rem;">
      Solo completa este campo si deseas cambiar la contraseña
    </small>
  <?php else: ?>
    <label>Contraseña:</label>
    <input type="password" name="contrasenia" required>
  <?php endif; ?>

  <button type="submit"><?= isset($mozo) ? 'Guardar cambios' : 'Crear Mozo' ?></button>
</form>

<a href="../../public/index.php?route=mozos" class="button" style="background-color: #6c757d; margin-top: 1rem;">
  ← Volver a la lista
</a>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
