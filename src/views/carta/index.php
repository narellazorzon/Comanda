<?php
// src/views/carta/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\CartaItem;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Mozos y administradores pueden ver la carta
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];

// Solo administradores pueden eliminar items
if (isset($_GET['delete']) && $rol === 'administrador') {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        CartaItem::delete($id);
    }
    header('Location: ' . url('carta'));
    exit;
}

// 3) Cargamos todos los ítems de la carta
$items = CartaItem::all();

?>

<h2><?= $rol === 'administrador' ? 'Gestión de Carta' : 'Consulta de Carta' ?></h2>

<?php if ($rol === 'administrador'): ?>
  <a href="<?= url('carta/create') ?>" class="button">Nuevo Ítem</a>
<?php else: ?>
  <div style="background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #0c5460;">
    📋 Vista de solo lectura - Consulta los items del menú y precios
  </div>
<?php endif; ?>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Descripción</th>
      <th>Precio</th>
      <th>Categoría</th>
      <th>Disponible</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['id_item']) ?></td>
        <td><strong><?= htmlspecialchars($item['nombre']) ?></strong></td>
        <td style="max-width: 200px; font-size: 0.9em; color: #666;">
          <?= htmlspecialchars($item['descripcion'] ?? '—') ?>
        </td>
        <td><strong>$<?= number_format($item['precio'], 2) ?></strong></td>
        <td>
          <span style="background: #e9ecef; padding: 2px 6px; border-radius: 10px; font-size: 0.8em;">
            <?= htmlspecialchars($item['categoria'] ?? 'Sin categoría') ?>
          </span>
        </td>
        <td>
          <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                       background: <?= $item['disponibilidad'] ? '#d4edda' : '#f8d7da' ?>; 
                       color: <?= $item['disponibilidad'] ? '#155724' : '#721c24' ?>;">
            <?= $item['disponibilidad'] ? '✅ Disponible' : '❌ No disponible' ?>
          </span>
        </td>
        <?php if ($rol === 'administrador'): ?>
          <td>
            <a href="<?= url('carta/edit') ?>&id=<?= $item['id_item'] ?>" class="btn-action">Editar</a>
            <a href="?delete=<?= $item['id_item'] ?>" class="btn-action" style="background: #dc3545;"
               onclick="return confirm('¿Borrar ítem &quot;<?= htmlspecialchars($item['nombre']) ?>&quot;?')">
              Borrar
            </a>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>


