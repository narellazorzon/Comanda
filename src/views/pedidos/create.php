<?php
// src/views/pedidos/create.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Pedido;
use App\Models\Mesa;
use App\Models\CartaItem;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Mozos y administradores pueden crear pedidos
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];
$error = '';
$success = '';

// Cargar datos necesarios
$mesas = Mesa::all();
$items = CartaItem::all();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mesa = (int) ($_POST['id_mesa'] ?? 0);
    $items_pedido = $_POST['items'] ?? [];
    
    if ($id_mesa <= 0) {
        $error = 'Debe seleccionar una mesa';
    } elseif (empty($items_pedido)) {
        $error = 'Debe agregar al menos un ítem al pedido';
    } else {
        try {
            $data = [
                'id_mesa' => $id_mesa,
                'id_mozo' => $_SESSION['user']['id_usuario'],
                'estado' => 'pendiente',
                'items' => $items_pedido
            ];
            
            if (Pedido::create($data)) {
                $success = 'Pedido creado correctamente';
                // Limpiar formulario
                $_POST = [];
            } else {
                $error = 'Error al crear el pedido';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

?>

<h2>Nuevo Pedido</h2>

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

<form method="post" id="pedidoForm">
    <label>Mesa:</label>
    <select name="id_mesa" required>
        <option value="">Seleccionar mesa</option>
        <?php foreach ($mesas as $mesa): ?>
            <option value="<?= $mesa['id_mesa'] ?>" 
                    <?= ($_POST['id_mesa'] ?? '') == $mesa['id_mesa'] ? 'selected' : '' ?>>
                Mesa <?= $mesa['numero'] ?>
                <?php if ($mesa['ubicacion']): ?>
                    - <?= $mesa['ubicacion'] ?>
                <?php endif; ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div id="items-container">
        <h3>Items del Pedido</h3>
        <div class="item-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: end;">
            <div style="flex: 2;">
                <label>Ítem:</label>
                <select name="items[0][id_item]" required>
                    <option value="">Seleccionar ítem</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?= $item['id_item'] ?>">
                            <?= htmlspecialchars($item['nombre']) ?> - $<?= number_format($item['precio'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex: 1;">
                <label>Cantidad:</label>
                <input type="number" name="items[0][cantidad]" min="1" value="1" required>
            </div>
            <div style="flex: 1;">
                <label>Precio:</label>
                <input type="number" name="items[0][precio]" step="0.01" min="0" required>
            </div>
            <div>
                <button type="button" onclick="removeItem(this)" class="button" style="background: #dc3545;">Eliminar</button>
            </div>
        </div>
    </div>

    <button type="button" onclick="addItem()" class="button" style="background: #28a745; margin-bottom: 1rem;">
        + Agregar Ítem
    </button>

    <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
        <h3>Resumen del Pedido</h3>
        <p><strong>Total: $<span id="total">0.00</span></strong></p>
    </div>

    <button type="submit" style="margin-top: 1rem;">
        Crear Pedido
    </button>
    
    <a href="<?= url('pedidos') ?>" class="button" style="margin-left: 10px;">Volver</a>
</form>

<script>
let itemIndex = 1;

function addItem() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.style.cssText = 'display: flex; gap: 1rem; margin-bottom: 1rem; align-items: end;';
    
    newRow.innerHTML = `
        <div style="flex: 2;">
            <label>Ítem:</label>
            <select name="items[${itemIndex}][id_item]" required>
                <option value="">Seleccionar ítem</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?= $item['id_item'] ?>">
                        <?= htmlspecialchars($item['nombre']) ?> - $<?= number_format($item['precio'], 2) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex: 1;">
            <label>Cantidad:</label>
            <input type="number" name="items[${itemIndex}][cantidad]" min="1" value="1" required>
        </div>
        <div style="flex: 1;">
            <label>Precio:</label>
            <input type="number" name="items[${itemIndex}][precio]" step="0.01" min="0" required>
        </div>
        <div>
            <button type="button" onclick="removeItem(this)" class="button" style="background: #dc3545;">Eliminar</button>
        </div>
    `;
    
    container.appendChild(newRow);
    itemIndex++;
    updateTotal();
}

function removeItem(button) {
    button.closest('.item-row').remove();
    updateTotal();
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const cantidad = parseFloat(row.querySelector('input[name*="[cantidad]"]').value) || 0;
        const precio = parseFloat(row.querySelector('input[name*="[precio]"]').value) || 0;
        total += cantidad * precio;
    });
    document.getElementById('total').textContent = total.toFixed(2);
}

// Actualizar precios automáticamente
document.addEventListener('change', function(e) {
    if (e.target.name && e.target.name.includes('[id_item]')) {
        const select = e.target;
        const option = select.options[select.selectedIndex];
        if (option.value) {
            const precio = option.textContent.split('$')[1];
            const row = select.closest('.item-row');
            const precioInput = row.querySelector('input[name*="[precio]"]');
            precioInput.value = precio;
            updateTotal();
        }
    }
    
    if (e.target.name && (e.target.name.includes('[cantidad]') || e.target.name.includes('[precio]'))) {
        updateTotal();
    }
});

// Calcular total inicial
updateTotal();
</script>


