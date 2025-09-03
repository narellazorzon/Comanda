<?php
// src/views/mozos/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Controllers\MozoController;
use App\Models\Usuario;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Solo administradores pueden acceder
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . url('login'));
    exit;
}

// 1) Si llega ?delete=ID, borramos y redirigimos
if (isset($_GET['delete'])) {
    MozoController::delete();
}

// 2) Cargamos todos los mozos
$mozos = Usuario::allByRole('mozo');

?>

<h2>Gestión de Mozos</h2>

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

<a class="button" href="<?= url('mozos/create') ?>">Nuevo Mozo</a>

<table>
  <tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Email</th>
    <th>Estado</th>
    <th>Acciones</th>
  </tr>
  <?php if (empty($mozos)): ?>
    <tr>
      <td colspan="5">No hay mozos registrados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($mozos as $m): ?>
      <tr>
        <td><?= $m['id_usuario'] ?></td>
        <td><?= htmlspecialchars($m['nombre'].' '.$m['apellido']) ?></td>
        <td><?= htmlspecialchars($m['email']) ?></td>
        <td><?= $m['estado'] ?></td>
        <td>
          <a href="<?= url('mozos/edit') ?>&id=<?= $m['id_usuario'] ?>">Editar</a> |
          <a href="<?= url('mozos') ?>&delete=<?= $m['id_usuario'] ?>"
             onclick="return confirm('¿Borrar mozo?')">Borrar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>


