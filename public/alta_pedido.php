<?php
// public/alta_pedido.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\PedidoController;
use App\Models\CartaItem;

// Si es POST, dejamos que el controlador procese y redirija
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    PedidoController::create();
    exit;
}

// Para GET cargamos el listado de ítems
$carta = CartaItem::all();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Nuevo Pedido</h2>

<form method="post" action="alta_pedido.php">
  <label>Modo de consumo:</label>
  <select name="modo_consumo">
    <option value="stay">En mesa</option>
    <option value="takeaway">Para llevar</option>
  </select>

  <label>Items:</label>
  <?php if (count($carta) === 0): ?>
    <p>No hay ítems disponibles.</p>
  <?php else: ?>
    <?php foreach ($carta as $item): ?>
      <div>
        <input type="checkbox" name="items[]" value="<?= $item['id_item'] ?>">
        <?= htmlspecialchars($item['nombre']) ?> ($<?= $item['precio'] ?>)
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <label>Observaciones:</label>
  <textarea name="observaciones"></textarea>

  <button type="submit">Crear Pedido</button>
</form>

<?php
require_once __DIR__ . '/includes/footer.php';
