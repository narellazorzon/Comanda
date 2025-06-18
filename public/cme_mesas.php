<?php
// public/cme_mesas.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Mesa;

session_start();
// Solo administradores pueden ver esta página
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

// Si viene ?delete=ID, borramos esa mesa y redirigimos
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        Mesa::delete($id);
    }
    header('Location: cme_mesas.php');
    exit;
}

// 1) Cargamos todas las mesas
$mesas = Mesa::all();

include __DIR__ . '/includes/header.php';
?>

<h2>Gestión de Mesas</h2>
<a href="alta_mesa.php" class="button">Nueva Mesa</a>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Número</th>
      <th>Ubicación</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mesas as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['id_mesa']) ?></td>
        <td><?= htmlspecialchars($m['numero']) ?></td>
        <td><?= htmlspecialchars($m['ubicacion'] ?? '—') ?></td>
        <td><?= htmlspecialchars(ucfirst($m['estado'])) ?></td>
        <td>
          <a href="alta_mesa.php?id=<?= $m['id_mesa'] ?>">Editar</a> |
          <a href="?delete=<?= $m['id_mesa'] ?>"
             onclick="return confirm('¿Borrar mesa <?= $m['numero'] ?>?')">
            Borrar
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>
