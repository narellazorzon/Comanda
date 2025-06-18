<?php
// public/cme_carta.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\CartaItem;

session_start();
// 1) Solo administradores pueden acceder
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

// 2) Si viene ?delete=ID, borramos el ítem y recargamos
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        CartaItem::delete($id);
    }
    header('Location: cme_carta.php');
    exit;
}

// 3) Cargamos todos los ítems de la carta
$items = CartaItem::all();

// 4) Incluir cabecera
include __DIR__ . '/includes/header.php';
?>

<h2>Gestión de Carta</h2>
<a href="alta_carta.php" class="button">Nuevo Ítem</a>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Precio</th>
      <th>Disponible</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['id_item']) ?></td>
        <td><?= htmlspecialchars($item['nombre']) ?></td>
        <td>$<?= number_format($item['precio'], 2) ?></td>
        <td><?= $item['disponibilidad'] ? 'Sí' : 'No' ?></td>
        <td>
          <a href="alta_carta.php?id=<?= $item['id_item'] ?>">Editar</a> |
          <a href="?delete=<?= $item['id_item'] ?>"
             onclick="return confirm('¿Borrar ítem “<?= htmlspecialchars($item['nombre']) ?>”?')">
            Borrar
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>
