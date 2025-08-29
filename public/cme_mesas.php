<?php
// public/cme_mesas.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Mesa;

session_start();
// Mozos y administradores pueden ver esta página
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: login.php');
    exit;
}

$rol = $_SESSION['user']['rol'];

// Solo administradores pueden eliminar mesas
if (isset($_GET['delete']) && $rol === 'administrador') {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        $mesa = Mesa::find($id);
        if ($mesa && $mesa['estado'] === 'libre') {
            Mesa::delete($id);
            header('Location: cme_mesas.php?success=1');
        } else {
            header('Location: cme_mesas.php?error=1');
        }
        exit;
    }
    header('Location: cme_mesas.php');
    exit;
}

// 1) Cargamos todas las mesas
$mesas = Mesa::all();

include __DIR__ . '/includes/header.php';
?>

<h2><?= $rol === 'administrador' ? 'Gestión de Mesas' : 'Consulta de Mesas' ?></h2>

<?php if (isset($_GET['success'])): ?>
    <div class="success" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        Mesa eliminada correctamente.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="error" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        No se puede eliminar una mesa que está ocupada.
    </div>
<?php endif; ?>

<?php if ($rol === 'administrador'): ?>
  <a href="alta_mesa.php" class="button">Nueva Mesa</a>
<?php else: ?>
  <div style="background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #0c5460;">
    👁️ Vista de solo lectura - Consulta las mesas disponibles
  </div>
<?php endif; ?>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Número</th>
      <th>Ubicación</th>
      <th>Estado</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mesas as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['id_mesa']) ?></td>
        <td><?= htmlspecialchars($m['numero']) ?></td>
        <td><?= htmlspecialchars($m['ubicacion'] ?? '—') ?></td>
        <td>
          <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                       background: <?= $m['estado'] === 'libre' ? '#d4edda' : '#f8d7da' ?>; 
                       color: <?= $m['estado'] === 'libre' ? '#155724' : '#721c24' ?>;">
            <?= htmlspecialchars(ucfirst($m['estado'])) ?>
          </span>
        </td>
        <?php if ($rol === 'administrador'): ?>
          <td>
            <a href="alta_mesa.php?id=<?= $m['id_mesa'] ?>" class="btn-action">Editar</a>
            <?php if ($m['estado'] === 'libre'): ?>
              <a href="?delete=<?= $m['id_mesa'] ?>" class="btn-action" style="background: #dc3545;"
                 onclick="return confirm('¿Borrar mesa <?= $m['numero'] ?>?')">
                Borrar
              </a>
            <?php else: ?>
              <span class="btn-action" style="background: #6c757d; cursor: not-allowed; opacity: 0.6;" 
                    title="No se puede borrar una mesa ocupada">
                Borrar
              </span>
            <?php endif; ?>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>
