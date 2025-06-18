<?php
// public/alta_carta.php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\CartaItem;

session_start();
// 1) Sólo administradores pueden entrar
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

// 2) Si viene id por GET, estamos en modo edición
$id   = isset($_GET['id']) ? (int) $_GET['id'] : null;
$item = $id ? CartaItem::find($id) : null;

// 3) POST: creamos o actualizamos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre'         => $_POST['nombre'] ?? '',
        'descripcion'    => $_POST['descripcion'] ?? '',
        'precio'         => $_POST['precio'] ?? 0,
        'categoria'      => $_POST['categoria'] ?? null,
        'disponibilidad' => $_POST['disponibilidad'] ?? 1,
        'imagen_url'     => $_POST['imagen_url'] ?? null,
    ];

    if ($id) {
        CartaItem::update($id, $data);
    } else {
        CartaItem::create($data);
    }

    header('Location: cme_carta.php');
    exit;
}

// 4) Mostrar formulario
include __DIR__ . '/includes/header.php';
?>

<h2><?= $id ? 'Editar Ítem' : 'Nuevo Ítem' ?></h2>
<form method="post">
  <label>
    Nombre:
    <input type="text" name="nombre" required
           value="<?= htmlspecialchars($item['nombre'] ?? '') ?>">
  </label>

  <label>
    Descripción:
    <textarea name="descripcion"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea>
  </label>

  <label>
    Precio:
    <input type="number" name="precio" step="0.01" required
           value="<?= htmlspecialchars($item['precio'] ?? '') ?>">
  </label>

  <label>
    Categoría:
    <input type="text" name="categoria"
           value="<?= htmlspecialchars($item['categoria'] ?? '') ?>">
  </label>

  <label>
    Disponible:
    <select name="disponibilidad">
      <option value="1" <?= (isset($item['disponibilidad']) && $item['disponibilidad']==1) ? 'selected' : '' ?>>Sí</option>
      <option value="0" <?= (isset($item['disponibilidad']) && $item['disponibilidad']==0) ? 'selected' : '' ?>>No</option>
    </select>
  </label>

  <label>
    URL de imagen:
    <input type="text" name="imagen_url"
           value="<?= htmlspecialchars($item['imagen_url'] ?? '') ?>">
  </label>

  <button type="submit"><?= $id ? 'Actualizar' : 'Crear' ?></button>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
