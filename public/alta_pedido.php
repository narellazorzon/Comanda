<?php
// public/alta_pedido.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\CartaItem;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\DetallePedido;

session_start();
// Solo mozos y administradores pueden crear pedidos
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Cargar datos necesarios
$mesas = Mesa::all();
$carta = CartaItem::all();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modo_consumo = $_POST['modo_consumo'] ?? 'stay';
    $id_mesa = $modo_consumo === 'stay' ? (int) ($_POST['id_mesa'] ?? 0) : null;
    $items = $_POST['items'] ?? [];
    $cantidades = $_POST['cantidades'] ?? [];
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Validaciones
    if ($modo_consumo === 'stay' && empty($id_mesa)) {
        $error = 'Debe seleccionar una mesa para pedidos en el local';
    } elseif (empty($items)) {
        $error = 'Debe seleccionar al menos un ítem';
    } else {
        try {
            // Crear el pedido
            $pedidoData = [
                'id_mesa' => $id_mesa,
                'modo_consumo' => $modo_consumo,
                'observaciones' => $observaciones
            ];
            
            $id_pedido = Pedido::create($pedidoData);
            
            if ($id_pedido) {
                // Agregar items al pedido
                $total = 0;
                foreach ($items as $index => $id_item) {
                    $cantidad = (int) ($cantidades[$index] ?? 1);
                    if ($cantidad > 0) {
                        $item = CartaItem::find($id_item);
                        if ($item && $item['disponibilidad']) {
                            DetallePedido::create([
                                'id_pedido' => $id_pedido,
                                'id_item' => $id_item,
                                'cantidad' => $cantidad,
                                'precio_unitario' => $item['precio']
                            ]);
                            $total += $item['precio'] * $cantidad;
                        }
                    }
                }
                
                // Actualizar total del pedido
                Pedido::updateTotal($id_pedido, $total);
                
                // Si es pedido en mesa, marcar mesa como ocupada
                if ($id_mesa) {
                    Mesa::update($id_mesa, ['estado' => 'ocupada']);
                }
                
                $success = "Pedido #$id_pedido creado correctamente";
                $_POST = []; // Limpiar formulario
            } else {
                $error = 'Error al crear el pedido';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
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

<form method="post" action="alta_pedido.php">
    <label>Modo de Consumo:</label>
    <select name="modo_consumo" id="modo_consumo" onchange="toggleMesa()">
        <option value="stay" <?= ($_POST['modo_consumo'] ?? 'stay') == 'stay' ? 'selected' : '' ?>>En Mesa</option>
        <option value="takeaway" <?= ($_POST['modo_consumo'] ?? '') == 'takeaway' ? 'selected' : '' ?>>Para Llevar</option>
    </select>

    <div id="mesa_section">
        <label>Mesa:</label>
        <select name="id_mesa" required>
            <option value="">Seleccionar mesa</option>
            <?php foreach ($mesas as $mesa): ?>
                <?php if ($mesa['estado'] === 'libre'): ?>
                    <option value="<?= $mesa['id_mesa'] ?>" 
                            <?= ($_POST['id_mesa'] ?? '') == $mesa['id_mesa'] ? 'selected' : '' ?>>
                        Mesa <?= $mesa['numero'] ?> - <?= $mesa['ubicacion'] ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>

    <label>Items del Menú:</label>
    <?php if (count($carta) === 0): ?>
        <p style="color: #666; font-style: italic;">No hay ítems disponibles en la carta.</p>
    <?php else: ?>
        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
            <?php foreach ($carta as $index => $item): ?>
                <?php if ($item['disponibilidad']): ?>
                    <div style="display: flex; align-items: center; margin-bottom: 10px; padding: 8px; border-bottom: 1px solid #eee;">
                        <input type="checkbox" name="items[]" value="<?= $item['id_item'] ?>" 
                               id="item_<?= $item['id_item'] ?>" 
                               onchange="toggleCantidad(<?= $item['id_item'] ?>)"
                               style="margin-right: 10px;">
                        
                        <div style="flex: 1;">
                            <label for="item_<?= $item['id_item'] ?>" style="font-weight: bold; cursor: pointer;">
                                <?= htmlspecialchars($item['nombre']) ?>
                            </label>
                            <div style="color: #666; font-size: 0.9em;">
                                <?= htmlspecialchars($item['descripcion']) ?>
                            </div>
                            <div style="color: var(--secondary); font-weight: bold;">
                                $<?= number_format($item['precio'], 2) ?>
                            </div>
                        </div>
                        
                        <input type="number" name="cantidades[]" value="1" min="1" max="10" 
                               id="cantidad_<?= $item['id_item'] ?>" 
                               style="width: 60px; margin-left: 10px;"
                               disabled>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <label>Observaciones:</label>
    <textarea name="observaciones" placeholder="Especificaciones especiales, alergias, etc..."><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>

    <button type="submit">Crear Pedido</button>
    <a href="cme_pedidos.php" class="button" style="margin-left: 10px;">Volver</a>
</form>

<script>
function toggleMesa() {
    const modo = document.getElementById('modo_consumo').value;
    const mesaSection = document.getElementById('mesa_section');
    const mesaSelect = mesaSection.querySelector('select[name="id_mesa"]');
    
    if (modo === 'takeaway') {
        mesaSection.style.display = 'none';
        mesaSelect.removeAttribute('required');
    } else {
        mesaSection.style.display = 'block';
        mesaSelect.setAttribute('required', 'required');
    }
}

function toggleCantidad(itemId) {
    const checkbox = document.getElementById('item_' + itemId);
    const cantidad = document.getElementById('cantidad_' + itemId);
    
    if (checkbox.checked) {
        cantidad.disabled = false;
        cantidad.focus();
    } else {
        cantidad.disabled = true;
        cantidad.value = 1;
    }
}

// Inicializar estado
document.addEventListener('DOMContentLoaded', function() {
    toggleMesa();
    
    // Habilitar cantidades para items ya seleccionados
    <?php if (isset($_POST['items'])): ?>
        <?php foreach ($_POST['items'] as $itemId): ?>
            toggleCantidad(<?= $itemId ?>);
        <?php endforeach; ?>
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
