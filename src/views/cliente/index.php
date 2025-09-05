<?php
// src/views/cliente/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\CartaItem;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$items = CartaItem::all();

// Determinar base url
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url = $protocol . '://' . $host . dirname($script_name);
?>

<style>
.cliente-carta { max-width: 1000px; margin: 0 auto; padding: 15px; }
.cliente-toolbar { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 10px; }
.cart-button { background: #2e7d32; color: #fff; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
.grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr)); gap: 14px; }
.card { background: #f3e2c3; border-radius: 12px; padding: 14px; box-shadow: 0 2px 6px rgba(0,0,0,.15); }
.card h3 { margin: 0 0 6px 0; }
.price { font-weight: 700; font-size: 18px; }
.add-btn { background:#007BFF; color:#fff; border:none; padding:8px 10px; border-radius:6px; cursor:pointer; }
.badge { padding: 2px 8px; border-radius: 12px; font-size: 12px; }
.available { background: #e6f4ea; color: #2e7d32; }
.cart-modal { position: fixed; inset: 0; background: rgba(0,0,0,.5); display:none; align-items:center; justify-content:center; z-index: 1000; }
.cart-panel { background:#fff; width: 95%; max-width: 580px; border-radius: 10px; padding: 20px; max-height: 90vh; overflow-y: auto; }
.cart-header { display:flex; justify-content:space-between; align-items:center; }
/* Estilos mejorados del resumen del carrito */
.cart-summary {
  background: linear-gradient(145deg, #ffffff, #f8f9fa);
  border: 1px solid #e9ecef;
  border-radius: 12px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.cart-summary-header {
  padding: 16px 20px 8px 20px;
}

.cart-summary-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 8px;
}

.cart-summary-divider {
  height: 1px;
  background: linear-gradient(to right, transparent, #dee2e6, transparent);
  margin: 8px 0;
}

.cart-items { 
  max-height: 240px; 
  overflow-y: auto; 
  padding: 0 20px;
}

.cart-item { 
  display: flex; 
  justify-content: space-between; 
  align-items: center;
  gap: 12px; 
  padding: 12px 0; 
  border-bottom: 1px solid #f1f3f4;
}

.cart-item:last-child {
  border-bottom: none;
}

.cart-item-info {
  flex: 1;
}

.cart-item-name {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 4px;
  font-size: 14px;
}

.cart-item-price {
  color: #6c757d;
  font-size: 13px;
}

.qty { 
  display: flex; 
  gap: 8px; 
  align-items: center;
  background: #f8f9fa;
  border-radius: 20px;
  padding: 4px;
}

.qty button { 
  width: 28px; 
  height: 28px;
  border: none;
  background: #fff;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: #495057;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  transition: all 0.2s ease;
}

.qty button:hover {
  background: #e9ecef;
  transform: scale(1.05);
}

.qty span {
  min-width: 24px;
  text-align: center;
  font-weight: 600;
  color: #2c3e50;
}

.cart-summary-footer {
  padding: 8px 20px 16px 20px;
}

.cart-total-amount { 
  text-align: right; 
  font-weight: 700;
  font-size: 18px;
  color: #2e7d32;
  background: linear-gradient(145deg, #e8f5e8, #f1f8f1);
  padding: 12px 16px;
  border-radius: 8px;
  border: 1px solid #c8e6c9;
}

.cart-empty {
  text-align: center;
  color: #6c757d;
  padding: 40px 20px;
  font-style: italic;
}

/* Notificación de producto agregado */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #28a745;
  color: white;
  padding: 12px 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  z-index: 2000;
  font-weight: bold;
  opacity: 0;
  transform: translateX(100px);
  transition: all 0.4s ease;
}

.toast-notification.show {
  opacity: 1;
  transform: translateX(0);
}

/* Animación del botón al agregar */
.add-btn.added {
  background: #28a745 !important;
  transform: scale(1.1);
  transition: all 0.2s ease;
}
</style>

<div class="cliente-carta">
  <div class="cliente-toolbar">
    <button id="btn-open-cart" class="cart-button" style="position:relative;">
      🛒 Ver carrito
      <span id="cart-counter" style="display:none;position:absolute;top:-8px;right:-8px;background:#dc3545;color:#fff;border-radius:50%;width:20px;height:20px;font-size:12px;font-weight:bold;display:flex;align-items:center;justify-content:center;">0</span>
    </button>
  </div>

  <div class="grid">
    <?php foreach ($items as $item): ?>
      <div class="card" data-categoria="<?= htmlspecialchars($item['categoria'] ?? 'Todas') ?>">
        <h3><?= htmlspecialchars($item['nombre']) ?></h3>
        <div class="badge available"><?= !empty($item['disponibilidad']) ? 'Disponible' : 'Agotado' ?></div>
        <p><?= htmlspecialchars($item['descripcion'] ?? '') ?></p>
        <div class="price">$<?= number_format($item['precio'], 2) ?></div>
        <button class="add-btn" data-id="<?= $item['id_item'] ?>" data-nombre="<?= htmlspecialchars($item['nombre']) ?>" data-precio="<?= number_format($item['precio'],2,'.','') ?>">➕ Agregar al carrito</button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div id="cart-modal" class="cart-modal" aria-hidden="true">
  <div class="cart-panel">
    <div class="cart-header">
      <h3>🛒 Finalizar Pedido</h3>
      <button id="btn-close-cart" style="background:none;border:none;font-size:18px;cursor:pointer;">✖</button>
    </div>
    
    <!-- Resumen del carrito -->
    <div class="cart-summary">
      <div class="cart-summary-header">
        <h4>🛒 Resumen del pedido</h4>
        <div class="cart-summary-divider"></div>
      </div>
      <div id="cart-items" class="cart-items"></div>
      <div class="cart-summary-footer">
        <div class="cart-summary-divider"></div>
        <div id="cart-total" class="cart-total-amount">Total: $0.00</div>
      </div>
    </div>
    
    <!-- Formulario de pedido -->
    <form id="checkout-form" style="display:flex;flex-direction:column;gap:12px;">
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:600;">Modalidad de consumo:</label>
        <select id="modo-consumo" name="modo_consumo" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;" required>
          <option value="">Seleccionar...</option>
          <option value="stay">🪑 Consumir en el local</option>
          <option value="takeaway">📦 Para llevar</option>
        </select>
      </div>
      
      <div id="mesa-field" style="display:none;">
        <label style="display:block;margin-bottom:4px;font-weight:600;">Número de mesa:</label>
        <select id="numero-mesa" name="numero_mesa" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
          <option value="">Seleccionar mesa...</option>
          <?php for($i = 1; $i <= 15; $i++): ?>
          <option value="<?= $i ?>">Mesa <?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:600;">Nombre completo:</label>
        <input type="text" id="nombre-completo" name="nombre_completo" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;" placeholder="Ingrese su nombre completo" required>
      </div>
      
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:600;">Email:</label>
        <input type="email" id="email" name="email" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;" placeholder="ejemplo@correo.com" required>
      </div>
      
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:600;">Forma de pago:</label>
        <select id="forma-pago" name="forma_pago" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;" required>
          <option value="">Seleccionar...</option>
          <option value="efectivo">💵 Efectivo</option>
          <option value="tarjeta">💳 Tarjeta crédito/débito</option>
        </select>
      </div>
      
      <div style="margin-top:15px;text-align:center;">
        <button type="submit" id="btn-confirmar" style="width:100%;padding:12px;border:none;border-radius:6px;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s ease;background:#a3c4f3;color:#666;" disabled>
          Confirmar Pedido
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Utilidad simple de carrito en localStorage
const CART_KEY = 'cliente_cart';
const loadCart = () => JSON.parse(localStorage.getItem(CART_KEY) || '[]');
const saveCart = (cart) => localStorage.setItem(CART_KEY, JSON.stringify(cart));

function addToCart(item){
  const cart = loadCart();
  const existing = cart.find(i => i.id === item.id);
  if(existing){ existing.qty += 1; } else { cart.push({...item, qty:1}); }
  saveCart(cart);
  renderCart();
  
  // Mostrar toast de confirmación
  showToast(`${item.nombre} agregado al carrito`);
  
  // Animación del botón
  const btn = document.querySelector(`[data-id="${item.id}"]`);
  if (btn) {
    btn.classList.add('added');
    setTimeout(() => btn.classList.remove('added'), 300);
  }
  
  // Actualizar contador
  updateCartCounter();
}

function updateQty(id, delta){
  const cart = loadCart();
  const it = cart.find(i => i.id === id);
  if(!it) return; it.qty += delta; if(it.qty <= 0){
    const idx = cart.findIndex(i => i.id === id);
    cart.splice(idx,1);
  }
  saveCart(cart); renderCart();
  updateCartCounter();
}

function renderCart(){
  const cart = loadCart();
  const list = document.getElementById('cart-items');
  const totalBox = document.getElementById('cart-total');
  
  if (cart.length === 0) {
    list.innerHTML = '<div class="cart-empty">🛒 Tu carrito está vacío<br><small>Agrega productos para continuar</small></div>';
    totalBox.textContent = 'Total: $0.00';
  } else {
    list.innerHTML = cart.map(i => `
      <div class="cart-item">
        <div class="cart-item-info">
          <div class="cart-item-name">${i.nombre}</div>
          <div class="cart-item-price">$${Number(i.precio).toFixed(2)} c/u</div>
        </div>
        <div class="qty">
          <button onclick="updateQty(${i.id},-1)">−</button>
          <span>${i.qty}</span>
          <button onclick="updateQty(${i.id},1)">+</button>
        </div>
      </div>
    `).join('');
    const total = cart.reduce((t,i)=> t + i.qty * Number(i.precio), 0);
    totalBox.textContent = `Total: $${total.toFixed(2)}`;
  }
  
  // Validar formulario después de renderizar
  setTimeout(validateForm, 100);
}

// Función para mostrar notificación toast
function showToast(message) {
  const toast = document.createElement('div');
  toast.className = 'toast-notification';
  toast.textContent = message;
  document.body.appendChild(toast);
  
  setTimeout(() => toast.classList.add('show'), 100);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => document.body.removeChild(toast), 400);
  }, 2500);
}

// Función para actualizar contador del carrito
function updateCartCounter() {
  const cart = loadCart();
  const counter = document.getElementById('cart-counter');
  const totalItems = cart.reduce((total, item) => total + item.qty, 0);
  
  if (totalItems > 0) {
    counter.textContent = totalItems;
    counter.style.display = 'flex';
  } else {
    counter.style.display = 'none';
  }
}

// Función para validar formulario y actualizar botón
function validateForm() {
  const modoConsumo = document.getElementById('modo-consumo').value;
  const nombreCompleto = document.getElementById('nombre-completo').value.trim();
  const email = document.getElementById('email').value.trim();
  const formaPago = document.getElementById('forma-pago').value;
  
  let isValid = true;
  
  // Validar campos obligatorios
  if (!modoConsumo || !nombreCompleto || !email || !formaPago) {
    isValid = false;
  }
  
  // Si es "stay", validar que tenga mesa seleccionada
  if (modoConsumo === 'stay') {
    const numeroMesa = document.getElementById('numero-mesa').value;
    if (!numeroMesa) {
      isValid = false;
    }
  }
  
  // Validar que haya items en el carrito
  const cart = loadCart();
  if (cart.length === 0) {
    isValid = false;
  }
  
  // Actualizar botón
  const btnConfirmar = document.getElementById('btn-confirmar');
  if (isValid) {
    btnConfirmar.style.background = '#007bff';
    btnConfirmar.style.color = '#fff';
    btnConfirmar.disabled = false;
  } else {
    btnConfirmar.style.background = '#a3c4f3';
    btnConfirmar.style.color = '#666';
    btnConfirmar.disabled = true;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  // Inicializar contador al cargar la página
  updateCartCounter();
  
  document.querySelectorAll('.add-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      addToCart({ id: Number(btn.dataset.id), nombre: btn.dataset.nombre, precio: btn.dataset.precio })
    });
  });
  
  const modal = document.getElementById('cart-modal');
  document.getElementById('btn-open-cart').addEventListener('click', ()=>{ 
    modal.style.display='flex'; 
    renderCart(); 
    validateForm(); // Validar al abrir
  });
  document.getElementById('btn-close-cart').addEventListener('click', ()=>{ modal.style.display='none'; });
  
  // Lógica condicional para mostrar/ocultar campo de mesa
  document.getElementById('modo-consumo').addEventListener('change', function() {
    const mesaField = document.getElementById('mesa-field');
    const numeroMesa = document.getElementById('numero-mesa');
    
    if (this.value === 'stay') {
      mesaField.style.display = 'block';
      numeroMesa.required = true;
    } else {
      mesaField.style.display = 'none';
      numeroMesa.required = false;
      numeroMesa.value = ''; // Limpiar selección
    }
    validateForm();
  });
  
  // Validar en tiempo real cuando cambien los campos
  const formFields = ['modo-consumo', 'numero-mesa', 'nombre-completo', 'email', 'forma-pago'];
  formFields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
      field.addEventListener('input', validateForm);
      field.addEventListener('change', validateForm);
    }
  });
  
  // Manejar envío del formulario
  document.getElementById('checkout-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const cart = loadCart();
    if (cart.length === 0) {
      alert('Tu carrito está vacío');
      return;
    }
    
    // Aquí se podría enviar el pedido al servidor
    // Por ahora, mostrar confirmación
    alert('¡Pedido confirmado! En breve nos comunicaremos contigo.');
    
    // Limpiar carrito y cerrar modal
    localStorage.removeItem(CART_KEY);
    modal.style.display = 'none';
    
    // Limpiar formulario
    this.reset();
    document.getElementById('mesa-field').style.display = 'none';
    updateCartCounter();
    validateForm();
  });
});
</script>


