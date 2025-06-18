<?php
// public/cme_mozos.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\MozoController;
use App\Models\Usuario;

// 1) Si llega ?delete=ID, borramos y redirigimos
if (isset($_GET['delete'])) {
    MozoController::delete();
}

// 2) Cargamos todos los mozos
$mozos = Usuario::allByRole('mozo');

// 3) Incluimos header (con tu style.css)
require_once __DIR__ . '/includes/header.php';
?>

<h2>Gestión de Mozos</h2>
<a class="button" href="alta_mozo.php">Nuevo Mozo</a>

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
          <a href="alta_mozo.php?id=<?= $m['id_usuario'] ?>">Editar</a> |
          <a href="cme_mozos.php?delete=<?= $m['id_usuario'] ?>"
             onclick="return confirm('¿Borrar mozo?')">Borrar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>

<?php
// 4) Incluimos footer
require_once __DIR__ . '/includes/footer.php';
?>
