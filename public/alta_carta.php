<?php
// public/alta_carta.php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\CartaItem;

session_start();
// Solo administradores pueden acceder
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

$item = null;
$error = '';
$success = '';

// Si viene id por GET, estamos en modo edición
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $item = CartaItem::find($id);
    if (!$item) {
        header('Location: cme_carta.php');
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float) ($_POST['precio'] ?? 0);
    $categoria = trim($_POST['categoria'] ?? '');
    $disponibilidad = (int) ($_POST['disponibilidad'] ?? 1);
    $imagen_url = trim($_POST['imagen_url'] ?? '');

    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre del ítem es obligatorio';
    } elseif ($precio <= 0) {
        $error = 'El precio debe ser mayor a 0';
    } elseif (empty($categoria)) {
        $error = 'La categoría es obligatoria';
    } else {
        try {
            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'categoria' => $categoria,
                'disponibilidad' => $disponibilidad,
                'imagen_url' => $imagen_url ?: null,
            ];

            if (isset($item)) {
                // Modificar ítem existente
                if (CartaItem::update($item['id_item'], $data)) {
                    $success = 'Ítem modificado correctamente';
                    $item = CartaItem::find($item['id_item']); // Recargar datos
                } else {
                    $error = 'Error al modificar el ítem';
                }
            } else {
                // Crear nuevo ítem
                if (CartaItem::create($data)) {
                    $success = 'Ítem creado correctamente';
                    // Limpiar formulario
                    $_POST = [];
                } else {
                    $error = 'Error al crear el ítem';
                }
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<h2><?= isset($item) ? 'Editar Ítem' : 'Nuevo Ítem' ?></h2>

<?php if ($error): ?>
    <div class="error" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<form method="post">
    <label>Nombre del Ítem:</label>
    <input type="text" name="nombre" required 
           value="<?= htmlspecialchars($item['nombre'] ?? $_POST['nombre'] ?? '') ?>"
           placeholder="Ej: Hamburguesa Clásica">

    <label>Descripción:</label>
    <textarea name="descripcion" placeholder="Descripción detallada del ítem..."><?= htmlspecialchars($item['descripcion'] ?? $_POST['descripcion'] ?? '') ?></textarea>

    <label>Precio ($):</label>
    <input type="number" name="precio" step="0.01" min="0.01" required 
           value="<?= htmlspecialchars($item['precio'] ?? $_POST['precio'] ?? '') ?>">

    <label>Categoría:</label>
    <select name="categoria" required>
        <option value="">Seleccionar categoría</option>
        <option value="Entradas" <?= ($item['categoria'] ?? $_POST['categoria'] ?? '') == 'Entradas' ? 'selected' : '' ?>>Entradas</option>
        <option value="Platos Principales" <?= ($item['categoria'] ?? $_POST['categoria'] ?? '') == 'Platos Principales' ? 'selected' : '' ?>>Platos Principales</option>
        <option value="Postres" <?= ($item['categoria'] ?? $_POST['categoria'] ?? '') == 'Postres' ? 'selected' : '' ?>>Postres</option>
        <option value="Bebidas" <?= ($item['categoria'] ?? $_POST['categoria'] ?? '') == 'Bebidas' ? 'selected' : '' ?>>Bebidas</option>
        <option value="Acompañamientos" <?= ($item['categoria'] ?? $_POST['categoria'] ?? '') == 'Acompañamientos' ? 'selected' : '' ?>>Acompañamientos</option>
    </select>

    <label>Disponible:</label>
    <select name="disponibilidad">
        <option value="1" <?= ($item['disponibilidad'] ?? $_POST['disponibilidad'] ?? 1) == 1 ? 'selected' : '' ?>>Sí</option>
        <option value="0" <?= ($item['disponibilidad'] ?? $_POST['disponibilidad'] ?? 1) == 0 ? 'selected' : '' ?>>No</option>
    </select>

    <label>URL de Imagen (opcional):</label>
    <input type="url" name="imagen_url" 
           value="<?= htmlspecialchars($item['imagen_url'] ?? $_POST['imagen_url'] ?? '') ?>"
           placeholder="https://ejemplo.com/imagen.jpg">

    <button type="submit">
        <?= isset($item) ? 'Actualizar Ítem' : 'Crear Ítem' ?>
    </button>
    
    <a href="cme_carta.php" class="button" style="margin-left: 10px;">Volver</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
