<?php
// src/views/pedidos/edit.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Pedido;
use App\Models\Mesa;
use App\Models\CartaItem;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Personal y administradores pueden editar pedidos
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];
$error = '';
$success = '';

// Obtener ID del pedido a editar
$pedido_id = (int) ($_GET['id'] ?? 0);

if ($pedido_id <= 0) {
    header('Location: ' . url('pedidos', ['error' => 'ID de pedido inválido']));
    exit;
}

// Cargar datos del pedido
$pedido = Pedido::find($pedido_id);
if (!$pedido) {
    header('Location: ' . url('pedidos', ['error' => 'Pedido no encontrado']));
    exit;
}

// Verificar que el pedido no esté cerrado
if ($pedido['estado'] === 'cerrado') {
    header('Location: ' . url('pedidos', ['error' => 'No se puede editar un pedido cerrado']));
    exit;
}

// Cargar detalles del pedido
$detalles = Pedido::getDetalles($pedido_id);

// Cargar datos necesarios para el formulario
$mesas = Mesa::all();
$items = CartaItem::all();

// Si estamos en modo POST, validar la mesa directamente aquí
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $modo_consumo === 'stay') {
    $id_mesa = (int) ($_POST['id_mesa'] ?? 0);

    if ($id_mesa > 0) {
        // Verificación directa de la mesa
        $db = new \App\Config\Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT estado FROM mesas WHERE id_mesa = ?");
        $stmt->execute([$id_mesa]);
        $mesaEstado = $stmt->fetchColumn();

        $stmtActual = $conn->prepare("SELECT id_mesa FROM pedidos WHERE id_pedido = ?");
        $stmtActual->execute([$pedido_id]);
        $mesaActual = $stmtActual->fetchColumn();

        error_log("VALIDACIÓN DIRECTA - Mesa $id_mesa tiene estado: " . $mesaEstado);
        error_log("VALIDACIÓN DIRECTA - Mesa actual del pedido: " . $mesaActual);

        if ($mesaEstado !== 'libre' && $id_mesa != $mesaActual) {
            die(json_encode([
                'success' => false,
                'message' => "ERROR: La mesa #$id_mesa no está disponible (Estado: $mesaEstado). Solo se permite cambiar a mesas libres."
            ]));
        }
    }
}

// Depuración: verificar estados de las mesas
if (empty($mesas)) {
    $error = 'No hay mesas disponibles en el sistema';
} else {
    // Verificar si hay al menos una mesa libre
    $mesasLibres = array_filter($mesas, function($mesa) {
        return $mesa['estado'] === 'libre';
    });
    if (empty($mesasLibres) && $pedido['id_mesa']) {
        // Si no hay mesas libres pero el pedido tiene mesa, mostrar advertencia
        $mesaActual = Mesa::find($pedido['id_mesa']);
        if ($mesaActual && $mesaActual['estado'] !== 'libre') {
            $error = 'Advertencia: No hay mesas libres disponibles. Solo puede mantener la mesa actual.';
        }
    }
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mesa = (int) ($_POST['id_mesa'] ?? 0);
    $modo_consumo = $_POST['modo_consumo'] ?? 'stay';
    $forma_pago = $_POST['forma_pago'] ?? null;
    $observaciones = $_POST['observaciones'] ?? '';
    $items_pedido = $_POST['items'] ?? [];

    // Log para depuración
    error_log("EDIT PEDIDO - POST data: " . print_r($_POST, true));
    error_log("EDIT PEDIDO - Pedido actual: " . print_r($pedido, true));

    if ($modo_consumo === 'stay' && $id_mesa <= 0) {
        $error = 'Debe seleccionar una mesa para pedidos en el local';
    } elseif ($modo_consumo === 'stay' && $id_mesa > 0) {
        // Verificar si la mesa está disponible
        $mesa = Mesa::find($id_mesa);
        error_log("EDIT PEDIDO - ID mesa actual: " . $pedido['id_mesa']);
        error_log("EDIT PEDIDO - ID mesa nueva: " . $id_mesa);
        error_log("EDIT PEDIDO - Mesa seleccionada ($id_mesa): " . print_r($mesa, true));

        if (!$mesa) {
            $error = 'La mesa seleccionada no existe';
        } elseif ($mesa['estado'] !== 'libre' && $id_mesa != $pedido['id_mesa']) {
            // Permitir mantener la misma mesa actual aunque no esté libre
            $error = 'La mesa seleccionada no está disponible. Estado actual: ' . ucfirst($mesa['estado']);
            error_log("EDIT PEDIDO - Error: Mesa no disponible. Estado: " . $mesa['estado']);
            error_log("EDIT PEDIDO - Condición: estado=" . $mesa['estado'] . ", es_mesa_nueva=" . ($id_mesa != $pedido['id_mesa'] ? 'SI' : 'NO'));
        }
    }

    if (empty($error)) {
        try {
            $data = [
                'modo_consumo' => $modo_consumo,
                'forma_pago' => $forma_pago,
                'observaciones' => $observaciones,
                'items' => $items_pedido
            ];
            
            // Solo incluir mesa si es modo 'stay'
            if ($modo_consumo === 'stay') {
                $data['id_mesa'] = $id_mesa;
            }
            
            if (Pedido::update($pedido_id, $data)) {
                $success = 'Pedido actualizado correctamente';
                // Recargar datos actualizados
                $pedido = Pedido::find($pedido_id);
                $detalles = Pedido::getDetalles($pedido_id);
            } else {
                $error = 'Error al actualizar el pedido';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

?>

<style>
.pedido-edit-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 8px;
  background: #f8f9fa;
  min-height: auto;
}

.pedido-header {
  background: linear-gradient(135deg,rgb(129, 92, 66),rgb(97, 68, 55));
  color: white !important;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 12px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.pedido-header h1 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
  color: white !important;
}

.pedido-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 8px;
  margin-top: 8px;
}

.info-item {
  background: rgba(255,255,255,0.1);
  padding: 6px;
  border-radius: 4px;
}

.info-label {
  font-size: 0.8rem;
  opacity: 0.8;
  margin-bottom: 3px;
}

.info-value {
  font-size: 0.9rem;
  font-weight: 600;
}

.form-section {
  background: white;
  border: 2px solid #CD853F;
  border-radius: 6px;
  padding: 12px;
  margin-bottom: 12px;
  box-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.form-section h3 {
  color: #2F1B14;
  margin-top: 0;
  margin-bottom: 8px;
  font-size: 1.1rem;
  font-weight: 600;
  border-bottom: 1px solid #CD853F;
  padding-bottom: 6px;
}

.form-group {
  margin-bottom: 10px;
}

.form-group label {
  display: block;
  margin-bottom: 3px;
  font-weight: 600;
  color: #2F1B14;
  font-size: 0.9rem;
}

.form-group select,
.form-group input,
.form-group textarea {
  width: 100%;
  padding: 6px;
  border: 1px solid #CD853F;
  border-radius: 3px;
  background: white;
  font-size: 0.9rem;
  box-sizing: border-box;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #DEB887;
  box-shadow: 0 0 3px rgba(222, 184, 135, 0.5);
}

.modo-consumo-container {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-bottom: 12px;
}

.modo-consumo-btn {
  flex: 1;
  max-width: 150px;
  background: #F5DEB3;
  border: 2px solid #CD853F;
  border-radius: 8px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
  box-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.modo-consumo-btn.selected {
  background: #CD853F;
  color: white;
  transform: translateY(-1px);
  box-shadow: 3px 3px 6px rgba(0,0,0,0.3);
}

.modo-consumo-btn:hover {
  transform: translateY(-1px);
  box-shadow: 3px 3px 6px rgba(0,0,0,0.3);
}

.items-section {
  background: white;
  border: 2px solid #CD853F;
  border-radius: 6px;
  padding: 12px;
  margin-bottom: 12px;
  box-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.items-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 6px;
  border-bottom: 1px solid #CD853F;
}

.add-item-btn {
  background: #28a745;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
  transition: background 0.3s ease;
}

.add-item-btn:hover {
  background: #218838;
}

.item-row {
  display: grid;
  grid-template-columns: 1fr 80px 80px 80px 40px;
  gap: 6px;
  align-items: center;
  padding: 6px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-bottom: 6px;
  background: #f8f9fa;
}

.item-row input,
.item-row select {
  padding: 4px;
  border: 1px solid #ddd;
  border-radius: 3px;
  font-size: 0.85rem;
}

.remove-item-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 3px 6px;
  border-radius: 3px;
  cursor: pointer;
  font-size: 0.8rem;
}

.remove-item-btn:hover {
  background: #c82333;
}

.total-section {
  background: #2F1B14;
  color: white;
  padding: 12px;
  border-radius: 6px;
  text-align: center;
  margin-bottom: 12px;
}

.total-section h3 {
  margin: 0 0 6px 0;
  font-size: 1.2rem;
}

.total-amount {
  font-size: 1.5rem;
  font-weight: bold;
  color: #DEB887;
}

.buttons-section {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-top: 12px;
}

.btn {
  padding: 8px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #CD853F;
  color: white;
}

.btn-primary:hover {
  background:rgb(171, 123, 101);
  transform: translateY(-2px);
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
  transform: translateY(-2px);
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover {
  background: #218838;
  transform: translateY(-2px);
}

.alert {
  padding: 8px;
  margin-bottom: 12px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.9rem;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
  .pedido-edit-container {
    padding: 5px;
  }
  
  .pedido-info {
    display: block;
    grid-template-columns: none;
    gap: 0;
    margin-top: 16px;
    width: 100%;
  }
  
  .info-item {
    background: rgba(255,255,255,0.2);
    padding: 12px;
    margin-bottom: 8px;
    min-height: auto;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    display: block;
    width: 100%;
  }
  
  .info-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
    display: block;
  }
  
  .info-value {
    font-size: 1rem;
    color: white;
    font-weight: 500;
    display: block;
  }
  
  .pedido-header {
    padding: 16px;
    margin-bottom: 16px;
  }
  
  .pedido-header h1 {
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: white;
  }
  
  .pedido-header {
    background: linear-gradient(135deg,rgb(129, 92, 66),rgb(97, 68, 55));
    color: white;
  }
  
  .modo-consumo-container {
    flex-direction: column;
    gap: 8px;
  }
  
  .item-row {
    grid-template-columns: 1fr;
    gap: 4px;
  }
  
  .buttons-section {
    flex-direction: column;
    gap: 6px;
  }
  
  .form-section, .items-section {
    padding: 8px;
    margin-bottom: 8px;
  }
}
</style>

<div class="pedido-edit-container">
  <!-- Header del pedido -->
  <div class="pedido-header">
    <h1>✏️ Editar Pedido #<?= $pedido['id_pedido'] ?></h1>
    <div class="pedido-info">
      <div class="info-item">
        <div class="info-label">Estado Actual</div>
        <div class="info-value"><?= ucfirst($pedido['estado']) ?></div>
      </div>
      <?php if ($pedido['modo_consumo'] === 'stay'): ?>
      <div class="info-item">
        <div class="info-label">Mesa</div>
        <div class="info-value">#<?= $pedido['numero_mesa'] ?> - <?= $pedido['ubicacion_mesa'] ?></div>
      </div>
      <?php endif; ?>
      <div class="info-item">
        <div class="info-label">Mozo</div>
        <div class="info-value"><?= $pedido['nombre_mozo_completo'] ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Fecha</div>
        <div class="info-value"><?= date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Total Actual</div>
        <div class="info-value">$<?= number_format($pedido['total'], 2) ?></div>
      </div>
    </div>
  </div>

  <!-- Mensajes de error/success -->
  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" id="pedidoForm">
    <!-- Información básica del pedido -->
    <div class="form-section">
      <h3>📋 Información del Pedido</h3>
      
      <div class="form-group">
        <label for="id_mesa">Mesa *</label>
        <div style="background: #e7f3ff; padding: 8px; border-radius: 4px; margin-bottom: 8px; font-size: 0.85rem; border-left: 4px solid #0066cc;">
          ℹ️ Solo se muestran las mesas <strong>libres</strong> disponibles para seleccionar
        </div>
        <select name="id_mesa" id="id_mesa" required onchange="showMozoInfo()">
          <option value="">Seleccionar mesa</option>
          <?php 
          // Filtrar mesas: solo mostrar las libres
          $mesasDisponibles = array_filter($mesas, function($mesa) {
              return $mesa['estado'] === 'libre';
          });
          
          foreach ($mesasDisponibles as $mesa): 
            $esMesaActual = $mesa['id_mesa'] == $pedido['id_mesa'];
            $esLibre = $mesa['estado'] === 'libre';
          ?>
            <option value="<?= $mesa['id_mesa'] ?>"
                    data-mozo="<?= htmlspecialchars($mesa['mozo_nombre_completo'] ?? 'Sin asignar') ?>"
                    data-estado="<?= $mesa['estado'] ?>"
                    <?= $esMesaActual ? 'selected' : '' ?>>
              Mesa #<?= $mesa['numero'] ?> - <?= $mesa['ubicacion'] ?>
              <?php if ($esMesaActual): ?>
                (Actual)
              <?php endif; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div id="mesa-info" style="margin-top: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px; display: none;">
          <strong>Estado:</strong> <span id="mesa-estado"></span>
        </div>
        <div id="mozo-info" style="margin-top: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px; display: none;">
          <strong>Mozo asignado:</strong> <span id="mozo-nombre"></span>
        </div>
      </div>

      <div class="form-group">
        <label>Modo de Consumo *</label>
        <div class="modo-consumo-container">
          <div class="modo-consumo-btn <?= $pedido['modo_consumo'] === 'stay' ? 'selected' : '' ?>"
               onclick="selectModoConsumo('stay', this)">
            <div style="font-size: 1.5rem; margin-bottom: 6px;">🍽️</div>
            <div style="font-size: 0.9rem;"><strong>En el Local</strong></div>
            <div style="font-size: 0.8rem; opacity: 0.8;">Stay</div>
          </div>
          <div class="modo-consumo-btn <?= $pedido['modo_consumo'] === 'takeaway' ? 'selected' : '' ?>"
               onclick="selectModoConsumo('takeaway', this)">
            <div style="font-size: 1.5rem; margin-bottom: 6px;">🛍️</div>
            <div style="font-size: 0.9rem;"><strong>Para Llevar</strong></div>
            <div style="font-size: 0.8rem; opacity: 0.8;">Takeaway</div>
          </div>
        </div>
        <input type="hidden" name="modo_consumo" id="modo_consumo" value="<?= $pedido['modo_consumo'] ?>" required>
      </div>

      <div class="form-group">
        <label for="forma_pago">Forma de Pago</label>
        <select name="forma_pago" id="forma_pago">
          <option value="">Seleccionar forma de pago...</option>
          <option value="efectivo" <?= ($pedido['forma_pago'] ?? '') === 'efectivo' ? 'selected' : '' ?>>💵 Efectivo</option>
          <option value="tarjeta" <?= ($pedido['forma_pago'] ?? '') === 'tarjeta' ? 'selected' : '' ?>>💳 Tarjeta</option>
          <option value="transferencia" <?= ($pedido['forma_pago'] ?? '') === 'transferencia' ? 'selected' : '' ?>>🏦 Transferencia</option>
        </select>
      </div>

      <div class="form-group">
        <label for="observaciones">Observaciones</label>
        <textarea name="observaciones" id="observaciones" rows="3" 
                  placeholder="Observaciones especiales del pedido..."><?= htmlspecialchars($pedido['observaciones'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Items del pedido -->
    <div class="items-section">
      <div class="items-header">
        <h3>🍽️ Items del Pedido</h3>
        <button type="button" class="add-item-btn" onclick="addItem()">
          ➕ Agregar Item
        </button>
      </div>

      <div id="items-container">
        <?php foreach ($detalles as $index => $detalle): ?>
          <div class="item-row" data-index="<?= $index ?>">
            <select name="items[<?= $index ?>][id_item]" required onchange="updateItemPrice(<?= $index ?>)">
              <option value="">Seleccionar item</option>
              <?php foreach ($items as $item): ?>
                <option value="<?= $item['id_item'] ?>" 
                        data-precio="<?= $item['precio'] ?>"
                        <?= $item['id_item'] == $detalle['id_item'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($item['nombre']) ?> - $<?= number_format($item['precio'], 2) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="items[<?= $index ?>][cantidad]" 
                   value="<?= $detalle['cantidad'] ?>" min="1" max="99" 
                   onchange="updateTotal()" required>
            <input type="number" name="items[<?= $index ?>][precio_unitario]" 
                   value="<?= $detalle['precio_actual'] ?>" step="0.01" 
                   readonly style="background: #f8f9fa;">
            <input type="text" value="$<?= number_format($detalle['cantidad'] * $detalle['precio_actual'], 2) ?>" 
                   readonly style="background: #f8f9fa; text-align: right;">
            <button type="button" class="remove-item-btn" onclick="removeItem(<?= $index ?>)">
              ❌
            </button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Total -->
    <div class="total-section">
      <h3>💰 Total del Pedido</h3>
      <div class="total-amount" id="total-amount">$<?= number_format($pedido['total'], 2) ?></div>
    </div>

    <!-- Botones -->
    <div class="buttons-section">
      <a href="<?= url('pedidos') ?>" class="btn btn-secondary">
        ← Volver a Pedidos
      </a>
      <button type="button" class="btn btn-primary" onclick="addItem()">
        ➕ Agregar Item
      </button>
      <button type="submit" class="btn btn-success">
        💾 Guardar Cambios
      </button>
    </div>
  </form>
</div>

<script>
let itemIndex = <?= count($detalles) ?>;

function selectModoConsumo(modo, element) {
  // Remover selección anterior
  document.querySelectorAll('.modo-consumo-btn').forEach(btn => {
    btn.classList.remove('selected');
  });

  // Seleccionar nuevo modo
  if (element) {
    element.closest('.modo-consumo-btn').classList.add('selected');
  }
  document.getElementById('modo_consumo').value = modo;
  
  // Mostrar/ocultar campo de mesa según el modo
  const mesaSelect = document.getElementById('id_mesa');
  const mesaGroup = mesaSelect ? mesaSelect.closest('.form-group') : null;
  
  if (mesaGroup) {
    if (modo === 'takeaway') {
      mesaGroup.style.display = 'none';
      mesaSelect.required = false;
      mesaSelect.value = '';
      // Ocultar información del mozo
      document.getElementById('mozo-info').style.display = 'none';
    } else {
      mesaGroup.style.display = 'block';
      mesaSelect.required = true;
      // Mostrar información del mozo si hay mesa seleccionada
      showMozoInfo();
    }
  }
}

function showMozoInfo() {
  const select = document.getElementById('id_mesa');
  const mesaInfo = document.getElementById('mesa-info');
  const mesaEstado = document.getElementById('mesa-estado');
  const mozoInfo = document.getElementById('mozo-info');
  const mozoNombre = document.getElementById('mozo-nombre');

  if (select.value) {
    const selectedOption = select.options[select.selectedIndex];
    const estado = selectedOption.getAttribute('data-estado');
    const mozo = selectedOption.getAttribute('data-mozo');

    // Mostrar estado de la mesa
    mesaEstado.textContent = estado;
    mesaInfo.style.display = 'block';

    // Mostrar información del mozo
    mozoNombre.textContent = mozo;
    mozoInfo.style.display = 'block';
  } else {
    mesaInfo.style.display = 'none';
    mozoInfo.style.display = 'none';
  }
}

function addItem() {
  const container = document.getElementById('items-container');
  const newRow = document.createElement('div');
  newRow.className = 'item-row';
  newRow.setAttribute('data-index', itemIndex);
  
  // Crear el select con las opciones
  const select = document.createElement('select');
  select.name = `items[${itemIndex}][id_item]`;
  select.required = true;
  select.onchange = () => updateItemPrice(itemIndex);
  
  // Agregar opción por defecto
  const defaultOption = document.createElement('option');
  defaultOption.value = '';
  defaultOption.textContent = 'Seleccionar item';
  select.appendChild(defaultOption);
  
  // Agregar opciones de items desde PHP
  <?php foreach ($items as $item): ?>
    const option<?= $item['id_item'] ?> = document.createElement('option');
    option<?= $item['id_item'] ?>.value = '<?= $item['id_item'] ?>';
    option<?= $item['id_item'] ?>.dataset.precio = '<?= $item['precio'] ?>';
    option<?= $item['id_item'] ?>.textContent = '<?= htmlspecialchars($item['nombre']) ?> - $<?= number_format($item['precio'], 2) ?>';
    select.appendChild(option<?= $item['id_item'] ?>);
  <?php endforeach; ?>
  
  // Crear inputs
  const cantidadInput = document.createElement('input');
  cantidadInput.type = 'number';
  cantidadInput.name = `items[${itemIndex}][cantidad]`;
  cantidadInput.value = '1';
  cantidadInput.min = '1';
  cantidadInput.max = '99';
  cantidadInput.onchange = updateTotal;
  cantidadInput.required = true;
  
  const precioInput = document.createElement('input');
  precioInput.type = 'number';
  precioInput.name = `items[${itemIndex}][precio_unitario]`;
  precioInput.value = '0';
  precioInput.step = '0.01';
  precioInput.readOnly = true;
  precioInput.style.background = '#f8f9fa';
  
  const subtotalInput = document.createElement('input');
  subtotalInput.type = 'text';
  subtotalInput.value = '$0.00';
  subtotalInput.readOnly = true;
  subtotalInput.style.background = '#f8f9fa';
  subtotalInput.style.textAlign = 'right';
  
  const removeBtn = document.createElement('button');
  removeBtn.type = 'button';
  removeBtn.className = 'remove-item-btn';
  removeBtn.textContent = '❌';
  removeBtn.onclick = () => removeItem(itemIndex);
  
  // Agregar elementos al row
  newRow.appendChild(select);
  newRow.appendChild(cantidadInput);
  newRow.appendChild(precioInput);
  newRow.appendChild(subtotalInput);
  newRow.appendChild(removeBtn);
  
  container.appendChild(newRow);
  itemIndex++;
}

function removeItem(index) {
  const row = document.querySelector(`[data-index="${index}"]`);
  if (row) {
    row.remove();
    updateTotal();
  }
}

function updateItemPrice(index) {
  const row = document.querySelector(`[data-index="${index}"]`);
  const select = row.querySelector('select[name*="[id_item]"]');
  const precioInput = row.querySelector('input[name*="[precio_unitario]"]');
  const cantidadInput = row.querySelector('input[name*="[cantidad]"]');
  
  const selectedOption = select.options[select.selectedIndex];
  if (selectedOption.value) {
    const precio = parseFloat(selectedOption.dataset.precio);
    precioInput.value = precio.toFixed(2);
    updateTotal();
  } else {
    precioInput.value = '0';
    updateTotal();
  }
}

function updateTotal() {
  let total = 0;
  console.log('Calculando total en formulario de editar...');
  
  const itemRows = document.querySelectorAll('.item-row');
  console.log('Encontradas', itemRows.length, 'filas de items');
  
  itemRows.forEach((row, index) => {
    const cantidadInput = row.querySelector('input[name*="[cantidad]"]');
    const precioInput = row.querySelector('input[name*="[precio_unitario]"]');
    
    console.log(`Procesando fila ${index}:`, {
      cantidadInput: cantidadInput,
      precioInput: precioInput,
      cantidadValue: cantidadInput ? cantidadInput.value : 'N/A',
      precioValue: precioInput ? precioInput.value : 'N/A'
    });
    
    if (cantidadInput && precioInput) {
      const cantidad = parseFloat(cantidadInput.value) || 0;
      const precio = parseFloat(precioInput.value) || 0;
      const subtotal = cantidad * precio;
      
      console.log(`Item ${index}: cantidad=${cantidad}, precio=${precio}, subtotal=${subtotal}`);
      
      // Actualizar el campo de subtotal visual
      const subtotalInput = row.querySelector('input[readonly]');
      if (subtotalInput) {
        subtotalInput.value = '$' + subtotal.toFixed(2);
        console.log(`Subtotal actualizado: ${subtotalInput.value}`);
      }
      
      total += subtotal;
    }
  });
  
  console.log('Total calculado:', total);
  const totalElement = document.getElementById('total-amount');
  if (totalElement) {
    totalElement.textContent = '$' + total.toFixed(2);
    console.log('Total actualizado en UI:', totalElement.textContent);
  } else {
    console.error('No se encontró el elemento total-amount');
  }
}

// Inicializar total al cargar la página
document.addEventListener('DOMContentLoaded', function() {
  console.log('Inicializando formulario de editar...');
  updateTotal();
  
  // Inicializar visibilidad del campo de mesa según el modo actual del pedido
  const modoConsumo = '<?= $pedido['modo_consumo'] ?>';
  if (modoConsumo) {
    selectModoConsumo(modoConsumo);
  }
  
  // Mostrar información del mozo si hay mesa seleccionada
  showMozoInfo();
});
</script>
