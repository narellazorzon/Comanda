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
.cart-modal { position: fixed; inset: 0; background: rgba(0,0,0,.5); display:none; align-items:center; justify-content:center; }
.cart-panel { background:#fff; width: 95%; max-width: 520px; border-radius: 10px; padding: 14px; }
.cart-header { display:flex; justify-content:space-between; align-items:center; }
.cart-items { max-height: 320px; overflow:auto; margin-top: 10px; }
.cart-item { display:flex; justify-content:space-between; gap:8px; padding:8px 0; border-bottom:1px solid #eee; }
.qty { display:flex; gap:6px; align-items:center; }
.qty button { width:26px; height:26px; }
.total { text-align:right; margin-top:10px; font-weight:700; }
</style>

<div class="cliente-carta">
  <div class="cliente-toolbar">
    <button id="btn-open-cart" class="cart-button">🛒 Ver carrito</button>
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
      <h3>Tu carrito</h3>
      <button id="btn-close-cart">✖</button>
    </div>
    <div id="cart-items" class="cart-items"></div>
    <div id="cart-total" class="total">Total: $0.00</div>
    <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:10px;">
      <a href="<?= $base_url ?>/index.php?route=login" class="cart-button" style="background:#6c757d;">Iniciar sesión para continuar</a>
    </div>
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
}

function updateQty(id, delta){
  const cart = loadCart();
  const it = cart.find(i => i.id === id);
  if(!it) return; it.qty += delta; if(it.qty <= 0){
    const idx = cart.findIndex(i => i.id === id);
    cart.splice(idx,1);
  }
  saveCart(cart); renderCart();
}

function renderCart(){
  const cart = loadCart();
  const list = document.getElementById('cart-items');
  const totalBox = document.getElementById('cart-total');
  list.innerHTML = cart.map(i => `
    <div class="cart-item">
      <div>
        <div><strong>${i.nombre}</strong></div>
        <div>$${Number(i.precio).toFixed(2)}</div>
      </div>
      <div class="qty">
        <button onclick="updateQty(${i.id},-1)">-</button>
        <span>${i.qty}</span>
        <button onclick="updateQty(${i.id},1)">+</button>
      </div>
    </div>
  `).join('');
  const total = cart.reduce((t,i)=> t + i.qty * Number(i.precio), 0);
  totalBox.textContent = `Total: $${total.toFixed(2)}`;
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.add-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      addToCart({ id: Number(btn.dataset.id), nombre: btn.dataset.nombre, precio: btn.dataset.precio })
    });
  });
  const modal = document.getElementById('cart-modal');
  document.getElementById('btn-open-cart').addEventListener('click', ()=>{ modal.style.display='flex'; renderCart(); });
  document.getElementById('btn-close-cart').addEventListener('click', ()=>{ modal.style.display='none'; });
});
</script>


