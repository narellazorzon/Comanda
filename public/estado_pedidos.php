<?php
// public/estado_pedidos.php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\Pedido;
use App\Models\Mesa;

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
    $map   = ['pendiente','en_preparacion','servido','cuenta','cerrado'];
    if (in_array($nuevo, $map, true)) {
        Pedido::updateEstado($id, $nuevo);
        
        // Si el pedido se marca como cerrado, liberar la mesa
        if ($nuevo === 'cerrado') {
            $pedido = Pedido::find($id);
            if ($pedido && isset($pedido['id_mesa']) && $pedido['id_mesa']) {
                Mesa::updateEstado($pedido['id_mesa'], 'libre');
            }
        }
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
        'en_preparacion' => 'servido',
        'servido'        => 'cuenta',
        'cuenta'         => 'cerrado',
        'cerrado'        => null
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
              <?php
              switch($p['estado']) {
                  case 'pendiente':
                      echo 'Tomar pedido';
                      break;
                  case 'en_preparacion':
                      echo 'Marcar servido';
                      break;
                  case 'servido':
                      echo 'Pedir cuenta';
                      break;
                  case 'cuenta':
                      echo 'Cerrar pedido';
                      break;
                  default:
                      echo 'Siguiente';
              }
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
