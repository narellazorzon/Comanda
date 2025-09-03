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

<style>
.pedido-digital {
  max-width: 1200px;
  margin: 0 auto;
  padding: 15px;
  background: 
    radial-gradient(circle at 20% 80%, rgba(195, 174, 146, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(222, 184, 135, 0.1) 0%, transparent 50%),
    linear-gradient(135deg, #D2B48C 0%,rgba(223, 202, 174, 0.79) 25%,rgb(221, 192, 168) 50%, #D2B48C 75%, #F5DEB3 100%);
  min-height: 100vh;
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
  position: relative;
}

.pedido-digital::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: 
    repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(210, 180, 140, 0.03) 2px, rgba(210, 180, 140, 0.03) 4px);
  pointer-events: none;
}

.pedido-header {
  text-align: center;
  margin-bottom: 25px;
  color: #2F1B14;
  position: relative;
  z-index: 1;
}

.pedido-title {
  font-size: 2.2rem;
  font-weight: 700;
  margin-bottom: 8px;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
  letter-spacing: 1px;
  color:rgb(61, 37, 20);
}

.pedido-subtitle {
  font-size: 1rem;
  opacity: 0.8;
  font-weight: 400;
  color: #5D4037;
  font-style: italic;
}

.form-section {
  background: #F5DEB3;
  border: 3px solid #CD853F;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 4px 4px 8px rgba(0,0,0,0.3);
  position: relative;
}

.form-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #CD853F, #DEB887, #A0522D, #DEB887, #CD853F);
}

.form-section h3 {
  color: #2F1B14;
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 1.3rem;
  font-weight: 600;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
  color: #2F1B14;
}

.form-group select,
.form-group input {
  width: 100%;
  padding: 10px;
  border: 2px solid #CD853F;
  border-radius: 4px;
  background: white;
  font-size: 1rem;
  box-sizing: border-box;
}

.form-group select:focus,
.form-group input:focus {
  outline: none;
  border-color: #DEB887;
  box-shadow: 0 0 5px rgba(222, 184, 135, 0.5);
}

.categorias-nav {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.categoria-btn {
  background: #CD853F;
  color: #F5DEB3;
  border: 2px solid #A0522D;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  font-size: 0.85rem;
  box-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.categoria-btn:hover, .categoria-btn.active {
  background: #DEB887;
  border-color: #CD853F;
  transform: translateY(-1px);
  box-shadow: 3px 3px 6px rgba(0,0,0,0.3);
}

.carta-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 15px;
  margin-bottom: 20px;
}

.carta-item {
  background: #F5DEB3;
  border: 3px solid #CD853F;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 4px 4px 8px rgba(0,0,0,0.3);
  transition: all 0.3s ease;
  position: relative;
  display: flex;
  min-height: 120px;
  cursor: pointer;
}

.carta-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #CD853F, #DEB887, #A0522D, #DEB887, #CD853F);
}

.carta-item:hover {
  transform: translateY(-2px);
  box-shadow: 6px 6px 12px rgba(0,0,0,0.4);
  border-color: #DEB887;
}

.carta-item.selected {
  border-color: #228B22;
  background: #E6FFE6;
}

.item-image {
  width: 120px;
  height: 120px;
  object-fit: cover;
  background: linear-gradient(45deg, #F4A460, #DEB887);
  border-right: 2px solid #CD853F;
  flex-shrink: 0;
}

.item-image-placeholder {
  width: 120px;
  height: 120px;
  background: linear-gradient(45deg, #F4A460, #DEB887);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: #A0522D;
  border-right: 2px solid #CD853F;
  flex-shrink: 0;
}

.item-content {
  padding: 12px;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

.item-header {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-bottom: 8px;
}

.item-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2F1B14;
  margin: 0;
  line-height: 1.2;
}

.item-category {
  background: #CD853F;
  color: #F5DEB3;
  padding: 3px 8px;
  border-radius: 3px;
  font-size: 0.7rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: 1px solid #A0522D;
  margin-bottom: 8px;
}

.item-description {
  color: #5D4037;
  font-size: 0.8rem;
  line-height: 1.4;
  margin-bottom: 8px;
  font-style: italic;
}

.item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
}

.item-price {
  font-size: 1.3rem;
  font-weight: 700;
  color: #2F1B14;
  margin: 0;
}

.price-normal {
  font-size: 1.3rem;
  font-weight: 700;
  color: #2F1B14;
  margin: 0;
}

.pedido-items {
  background: #F5DEB3;
  border: 3px solid #CD853F;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 4px 4px 8px rgba(0,0,0,0.3);
}

.pedido-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  background: white;
  border-radius: 4px;
  margin-bottom: 10px;
  border: 1px solid #DEB887;
}

.pedido-item:last-child {
  margin-bottom: 0;
}

.item-info {
  flex: 1;
}

.item-quantity {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-right: 15px;
}

.quantity-input {
  width: 60px;
  padding: 5px;
  border: 1px solid #CD853F;
  border-radius: 3px;
  text-align: center;
}

.btn-remove {
  background: #dc3545;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 3px;
  cursor: pointer;
  font-size: 0.8rem;
}

.btn-remove:hover {
  background: #c82333;
}

.resumen-section {
  background: #F5DEB3;
  border: 3px solid #228B22;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 4px 4px 8px rgba(0,0,0,0.3);
}

.resumen-section h3 {
  color: #2F1B14;
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 1.3rem;
  font-weight: 600;
}

.total-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #228B22;
  text-align: center;
  padding: 10px;
  background: white;
  border-radius: 4px;
  border: 2px solid #32CD32;
}

.btn-submit {
  background: #228B22;
  color: white;
  padding: 15px 30px;
  border: 2px solid #32CD32;
  border-radius: 4px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 3px 3px 6px rgba(0,0,0,0.3);
  width: 100%;
  margin-bottom: 10px;
}

.btn-submit:hover {
  background: #32CD32;
  transform: translateY(-2px);
  box-shadow: 4px 4px 8px rgba(0,0,0,0.4);
}

.btn-back {
  background: #6c757d;
  color: white;
  padding: 10px 20px;
  border: 2px solid #5a6268;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 600;
  display: inline-block;
  transition: all 0.3s ease;
  text-align: center;
  width: 100%;
}

.btn-back:hover {
  background: #5a6268;
  transform: translateY(-1px);
}

.alert {
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 20px;
  font-weight: 500;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
  .carta-grid {
    grid-template-columns: 1fr;
  }
  
  .pedido-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .item-quantity {
    margin-right: 0;
    align-self: flex-end;
  }
}
</style>

<div class="pedido-digital">
  <div class="pedido-header">
    <h1 class="pedido-title">🍽️ Nuevo Pedido</h1>
    <p class="pedido-subtitle">Selecciona la mesa y agrega los items del menú</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success">
      ✅ <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <form method="post" id="pedidoForm">
    <!-- Selección de Mesa -->
    <div class="form-section">
      <h3>📍 Seleccionar Mesa</h3>
      <div class="form-group">
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
      </div>
    </div>

    <!-- Selección de Items -->
    <div class="form-section">
      <h3>🍽️ Seleccionar Items del Menú</h3>
      
      <?php 
      // Agrupar items por categoría
      $categorias = [];
      foreach ($items as $item) {
          if ($item['disponibilidad']) {
              $categoria = $item['categoria'] ?? 'Sin categoría';
              if (!isset($categorias[$categoria])) {
                  $categorias[$categoria] = [];
              }
              $categorias[$categoria][] = $item;
          }
      }
      ?>

      <div class="categorias-nav">
        <button class="categoria-btn active" onclick="filtrarCategoria('todas')">Todas</button>
        <?php foreach (array_keys($categorias) as $categoria): ?>
          <button class="categoria-btn" onclick="filtrarCategoria('<?= htmlspecialchars($categoria) ?>')">
            <?= htmlspecialchars($categoria) ?>
          </button>
        <?php endforeach; ?>
      </div>
      
      <div class="carta-grid" id="carta-grid">
        <?php foreach ($items as $item): ?>
          <?php if ($item['disponibilidad']): ?>
            <div class="carta-item" 
                 data-item-id="<?= $item['id_item'] ?>" 
                 data-precio="<?= $item['precio'] ?>"
                 data-categoria="<?= htmlspecialchars($item['categoria'] ?? 'Sin categoría') ?>">
              
              <?php if (!empty($item['imagen_url'])): ?>
                <img src="<?= htmlspecialchars($item['imagen_url']) ?>" 
                     alt="<?= htmlspecialchars($item['nombre']) ?>"
                     class="item-image"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="item-image-placeholder" style="display: none;">
                  🍽️
                </div>
              <?php else: ?>
                <div class="item-image-placeholder">
                  🍽️
                </div>
              <?php endif; ?>
              
              <div class="item-content">
                <div class="item-header">
                  <h3 class="item-name"><?= htmlspecialchars($item['nombre']) ?></h3>
                  <span class="item-category"><?= htmlspecialchars($item['categoria'] ?? 'Sin categoría') ?></span>
                </div>
                
                <p class="item-description">
                  <?= htmlspecialchars($item['descripcion'] ?? 'Delicioso plato preparado con ingredientes frescos.') ?>
                </p>
                
                <div class="item-footer">
                  <div class="item-price">
                    <p class="price-normal">$<?= number_format($item['precio'], 2) ?></p>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Items del Pedido -->
    <div class="pedido-items">
      <h3>📋 Items del Pedido</h3>
      <div id="pedido-items-list">
        <p style="text-align: center; color: #6c757d; font-style: italic;">
          Selecciona items del menú para agregarlos al pedido
        </p>
      </div>
    </div>

    <!-- Resumen -->
    <div class="resumen-section">
      <h3>💰 Resumen del Pedido</h3>
      <div class="total-amount">
        Total: $<span id="total">0.00</span>
      </div>
    </div>

    <button type="submit" class="btn-submit">
      ✅ Crear Pedido
    </button>
    
    <a href="<?= url('pedidos') ?>" class="btn-back">
      ← Volver a Pedidos
    </a>
  </form>
</div>

<script>
let pedidoItems = [];
let itemIndex = 0;

// Agregar item al pedido
function addItemToPedido(itemId, nombre, precio) {
    // Verificar si el item ya existe
    const existingItem = pedidoItems.find(item => item.id_item === itemId);
    
    if (existingItem) {
        // Si ya existe, aumentar la cantidad
        existingItem.cantidad += 1;
    } else {
        // Si no existe, agregarlo
        pedidoItems.push({
            id_item: itemId,
            nombre: nombre,
            precio: parseFloat(precio),
            cantidad: 1
        });
    }
    
    updatePedidoDisplay();
    updateTotal();
}

// Remover item del pedido
function removeItemFromPedido(itemId) {
    pedidoItems = pedidoItems.filter(item => item.id_item !== itemId);
    updatePedidoDisplay();
    updateTotal();
}

// Actualizar cantidad de un item
function updateItemQuantity(itemId, cantidad) {
    const item = pedidoItems.find(item => item.id_item === itemId);
    if (item) {
        if (cantidad <= 0) {
            removeItemFromPedido(itemId);
        } else {
            item.cantidad = parseInt(cantidad);
            updatePedidoDisplay();
            updateTotal();
        }
    }
}

// Actualizar la visualización de items del pedido
function updatePedidoDisplay() {
    const container = document.getElementById('pedido-items-list');
    
    if (pedidoItems.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #6c757d; font-style: italic;">Selecciona items del menú para agregarlos al pedido</p>';
        return;
    }
    
    let html = '';
    pedidoItems.forEach(item => {
        html += `
            <div class="pedido-item">
                <div class="item-info">
                    <strong>${item.nombre}</strong><br>
                    <small>$${item.precio.toFixed(2)} c/u</small>
                </div>
                <div class="item-quantity">
                    <label>Cantidad:</label>
                    <input type="number" 
                           class="quantity-input" 
                           value="${item.cantidad}" 
                           min="1" 
                           onchange="updateItemQuantity('${item.id_item}', this.value)">
                    <button type="button" 
                            class="btn-remove" 
                            onclick="removeItemFromPedido('${item.id_item}')">
                        🗑️
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Actualizar total
function updateTotal() {
    let total = 0;
    pedidoItems.forEach(item => {
        total += item.precio * item.cantidad;
    });
    document.getElementById('total').textContent = total.toFixed(2);
}

// Función para filtrar por categoría
function filtrarCategoria(categoria) {
  const items = document.querySelectorAll('.carta-item');
  const botones = document.querySelectorAll('.categoria-btn');
  
  // Actualizar botones activos
  botones.forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
  
  // Filtrar items
  items.forEach(item => {
    if (categoria === 'todas' || item.dataset.categoria === categoria) {
      item.style.display = 'flex';
      item.style.opacity = '1';
      item.style.transform = 'translateY(0)';
    } else {
      item.style.opacity = '0';
      item.style.transform = 'translateY(20px)';
      setTimeout(() => {
        item.style.display = 'none';
      }, 300);
    }
  });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Agregar event listeners a los items de la carta
    document.querySelectorAll('.carta-item').forEach(item => {
        item.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const nombre = this.querySelector('.item-name').textContent;
            const precio = this.dataset.precio;
            
            addItemToPedido(itemId, nombre, precio);
            
            // Efecto visual de selección
            this.classList.add('selected');
            setTimeout(() => {
                this.classList.remove('selected');
            }, 500);
        });
    });
    
    // Actualizar formulario antes de enviar
    document.getElementById('pedidoForm').addEventListener('submit', function(e) {
        if (pedidoItems.length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un ítem al pedido');
            return;
        }
        
        // Crear inputs hidden para los items
        pedidoItems.forEach((item, index) => {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = `items[${index}][id_item]`;
            idInput.value = item.id_item;
            
            const cantidadInput = document.createElement('input');
            cantidadInput.type = 'hidden';
            cantidadInput.name = `items[${index}][cantidad]`;
            cantidadInput.value = item.cantidad;
            
            const precioInput = document.createElement('input');
            precioInput.type = 'hidden';
            precioInput.name = `items[${index}][precio]`;
            precioInput.value = item.precio;
            
            this.appendChild(idInput);
            this.appendChild(cantidadInput);
            this.appendChild(precioInput);
        });
    });
    
    updateTotal();
    
    // Animación de entrada para los items
    const items = document.querySelectorAll('.carta-item');
    items.forEach((item, index) => {
        item.style.display = 'flex';
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>


