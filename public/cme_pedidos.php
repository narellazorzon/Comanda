<?php
// public/cme_pedidos.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\PedidoController;
use App\Models\Pedido;

// 1) Si llega ?delete=ID, delegamos en el controlador
if (isset($_GET['delete'])) {
    PedidoController::delete();
    exit;
}

// 2) Arrancamos la sesión y cargamos pedidos
session_start();
$rol    = $_SESSION['user']['rol'] ?? '';
$mesaId = $_SESSION['mesa_id'] ?? null;

$pedidos = $rol === 'administrador'
    ? Pedido::all()
    : ($mesaId ? Pedido::allByMesa($mesaId) : []);

require_once __DIR__ . '/includes/header.php';
?>

<h2>Gestión de Pedidos</h2>
<?php if ($rol === 'administrador'): ?>
  <a class="button" href="alta_pedido.php">Nuevo Pedido</a>
<?php endif; ?>

<table>
  <tr>
    <th>ID</th>
    <th>Mesa</th>
    <th>Mozo</th>
    <th>Estado</th>
    <th>Total</th>
    <th>Acciones</th>
  </tr>
  <?php foreach ($pedidos as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p['id_pedido']) ?></td>
      <td><?= htmlspecialchars($p['id_mesa'] ?? '') ?></td>
      <td><?= htmlspecialchars($p['id_mozo'] ?? '') ?></td>
      <td><?= htmlspecialchars($p['estado']) ?></td>
      <td>$<?= number_format($p['total'], 2) ?></td>
      <td>
        <?php if ($rol === 'administrador'): ?>
          <a href="?delete=<?= $p['id_pedido'] ?>"
             onclick="return confirm('¿Borrar pedido?')">Borrar</a>
        <?php elseif ($p['id_mesa'] === $mesaId && $p['estado'] === 'pendiente'): ?>
          <a href="?delete=<?= $p['id_pedido'] ?>"
             onclick="return confirm('¿Cancelar tu pedido?')">Cancelar</a>
        <?php else: ?>
          —
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
