<?php
// public/estado_pedidos.php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\Pedido;

session_start();
$rol = $_SESSION['user']['rol'] ?? '';
if (! in_array($rol, ['mozo','administrador'], true)) {
    header('Location: login.php');
    exit;
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pedido'], $_POST['estado'])) {
    $id    = (int)($_POST['id_pedido']);
    $nuevo = $_POST['estado'];
    $map   = ['pendiente','en_preparacion','listo'];
    if (in_array($nuevo, $map, true)) {
        Pedido::updateEstado($id, $nuevo);
    }
    header('Location: estado_pedidos.php');
    exit;
}

// Cargar todos los pedidos
$pedidos = Pedido::all();

include __DIR__ . '/includes/header.php';
?>

<h2>Estado de Pedidos</h2>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Mesa</th>
      <th>Hora</th>
      <th>Estado</th>
      <th>Siguiente</th>
      <th>Acción</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($pedidos as $p):
      $next = [
        'pendiente'      => 'en_preparacion',
        'en_preparacion' => 'listo',
        'listo'          => null
      ][$p['estado']] ?? null;
    ?>
    <tr>
      <td><?= htmlspecialchars($p['id_pedido']) ?></td>
      <td><?= htmlspecialchars($p['id_mesa'] ?? '—') ?></td>
      <td><?= date('d/m/Y H:i:s', strtotime($p['fecha_hora'])) ?></td>
      <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$p['estado']))) ?></td>
      <td>
        <?= $next
            ? htmlspecialchars(ucfirst(str_replace('_',' ',$next)))
            : '&mdash;'
        ?>
      </td>
      <td class="action-cell no-card">
        <?php if ($next): ?>
          <form method="post" class="action-form">
            <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
            <input type="hidden" name="estado"      value="<?= $next ?>">
            <button type="submit" class="btn-action">
              <?= $p['estado'] === 'pendiente'
                  ? 'Tomar pedido'
                  : 'Marcar listo'
              ?>
            </button>
          </form>
        <?php else: ?>
          &mdash;
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>
