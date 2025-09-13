<?php
// src/views/carta/create.php
require_once __DIR__ . '/../../config/helpers.php';

$item = null;
$error = '';
$success = '';

// Si viene id por GET, estamos en modo edición
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $item = \App\Models\CartaItem::find($id);
    if (!$item) {
        header('Location: ' . url('carta', ['error' => 'Ítem no encontrado']));
        exit;
    }
}

// Mostrar mensajes de éxito/error
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>

<h2><?= isset($item) ? 'Editar Ítem' : 'Nuevo Ítem' ?></h2>

<?php if ($success): ?>
  <div style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= url('carta/create') ?>" autocomplete="off">
  <?php if (isset($item)): ?>
    <input type="hidden" name="id" value="<?= $item['id_item'] ?>">
  <?php endif; ?>
  
  <?php 
  // Solo usar datos del POST si hay error de validación (no para creación exitosa)
  $usePostData = isset($_GET['error']) && !isset($item);
  ?>
  
  <label>Categoría:</label>
  <select name="categoria" required>
    <option value="">Seleccionar categoría</option>
    <?php 
    // Obtener categorías dinámicamente desde la base de datos
    $items = \App\Models\CartaItem::allIncludingUnavailable();
    $categoriasUnicas = [];
    
    foreach ($items as $itemData) {
        $categoria = $itemData['categoria'] ?? 'Sin categoría';
        if (!in_array($categoria, $categoriasUnicas)) {
            $categoriasUnicas[] = $categoria;
        }
    }
    
    // Agregar categorías predefinidas si no existen en la BD
    $categoriasPredefinidas = ['Entradas', 'Platos Principales', 'Postres', 'Bebidas', 'Acompañamientos', 'Carnes', 'Ensaladas', 'Pastas', 'Pizzas'];
    foreach ($categoriasPredefinidas as $categoria) {
        if (!in_array($categoria, $categoriasUnicas)) {
            $categoriasUnicas[] = $categoria;
        }
    }
    
    // Ordenar alfabéticamente
    sort($categoriasUnicas);
    
    $categoriaSeleccionada = $item['categoria'] ?? ($usePostData ? $_POST['categoria'] : '') ?? '';
    foreach ($categoriasUnicas as $categoria): 
    ?>
      <option value="<?= $categoria ?>" <?= $categoriaSeleccionada === $categoria ? 'selected' : '' ?>>
        <?= $categoria ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label>Nombre del Ítem:</label>
  <input type="text" name="nombre" required 
         value="<?= htmlspecialchars($item['nombre'] ?? ($usePostData ? $_POST['nombre'] : '') ?? '') ?>"
         placeholder="Ej: Hamburguesa Clásica" autocomplete="off">

  <label>Descripción:</label>
  <textarea name="descripcion" placeholder="Descripción detallada del ítem..." autocomplete="off" 
            style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; line-height: 1.5; font-weight: 400; letter-spacing: 0.3px;"><?= htmlspecialchars($item['descripcion'] ?? ($usePostData ? $_POST['descripcion'] : '') ?? '') ?></textarea>

  <label>Precio Base ($):</label>
  <input type="number" name="precio" id="precio" step="0.01" min="0.01" required 
         value="<?= htmlspecialchars($item['precio'] ?? ($usePostData ? $_POST['precio'] : '') ?? '') ?>" autocomplete="off">

  <label>Descuento (%):</label>
  <input type="number" name="descuento" id="descuento" step="0.01" min="0" max="100" 
         value="<?= htmlspecialchars($item['descuento'] ?? ($usePostData ? $_POST['descuento'] : '') ?? '0') ?>" autocomplete="off"
         placeholder="0">

  <label>Precio Final ($):</label>
  <input type="text" id="precio_final" readonly 
         value="<?= htmlspecialchars($item['precio'] ?? ($usePostData ? $_POST['precio'] : '') ?? '0') ?>" 
         style="background: #f8f9fa; color: #495057; font-weight: bold;">

  <label>Disponible:</label>
  <select name="disponibilidad">
    <?php $disponibilidad = $item['disponibilidad'] ?? ($usePostData ? $_POST['disponibilidad'] : 1) ?? 1; ?>
    <option value="1" <?= $disponibilidad == 1 ? 'selected' : '' ?>>Sí</option>
    <option value="0" <?= $disponibilidad == 0 ? 'selected' : '' ?>>No</option>
  </select>

  <label>URL de Imagen (opcional):</label>
  <input type="url" name="imagen_url" 
         value="<?= htmlspecialchars($item['imagen_url'] ?? ($usePostData ? $_POST['imagen_url'] : '') ?? '') ?>"
         placeholder="https://ejemplo.com/imagen.jpg" autocomplete="off">

  <button type="submit">
    <?= isset($item) ? 'Actualizar Ítem' : 'Crear Ítem' ?>
  </button>
  
  <a href="<?= url('carta') ?>" class="button" style="margin-left: 10px;">← Volver a la lista</a>
</form>

<script>
function calcularPrecioFinal() {
    const precio = parseFloat(document.getElementById('precio').value) || 0;
    const descuento = parseFloat(document.getElementById('descuento').value) || 0;
    
    if (descuento > 0) {
        const descuentoCalculado = precio * (descuento / 100);
        const precioFinal = precio - descuentoCalculado;
        document.getElementById('precio_final').value = precioFinal.toFixed(2);
    } else {
        document.getElementById('precio_final').value = precio.toFixed(2);
    }
}

// Event listeners para calcular automáticamente
document.getElementById('precio').addEventListener('input', calcularPrecioFinal);
document.getElementById('descuento').addEventListener('input', calcularPrecioFinal);

// Calcular precio inicial al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    calcularPrecioFinal();
});
</script>