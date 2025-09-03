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

<style>
.carta-digital {
  max-width: 1000px;
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

.carta-digital::before {
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

.carta-header {
  text-align: center;
  margin-bottom: 25px;
  color: #2F1B14;
  position: relative;
  z-index: 1;
}

.carta-title {
  font-size: 2.2rem;
  font-weight: 700;
  margin-bottom: 8px;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
  letter-spacing: 1px;
  color:rgb(61, 37, 20);
}

.carta-subtitle {
  font-size: 1rem;
  opacity: 0.8;
  font-weight: 400;
  color: #5D4037;
  font-style: italic;
}

.admin-controls {
  background: rgba(139, 69, 19, 0.15);
  border: 2px solid #8B4513;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 20px;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.categorias-nav {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.categoria-btn {
  background:rgb(216, 199, 181);
  color: #F5DEB3;
  border: 2px solidrgb(109, 82, 69);
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
  border-color:rgb(229, 208, 188);
  transform: translateY(-1px);
  box-shadow: 3px 3px 6px rgba(0,0,0,0.3);
}

.menu-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 15px;
  margin-bottom: 30px;
}

.menu-item {
  background: #F5DEB3;
  border: 3px solidrgb(117, 104, 92);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 4px 4px 8px rgba(0,0,0,0.3);
  transition: all 0.3s ease;
  position: relative;
  display: flex;
  min-height: 120px;
}

.menu-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg,rgb(215, 204, 159), #DEB887,rgb(236, 222, 186), #DEB887,rgb(229, 205, 181));
}

.menu-item:hover {
  transform: translateY(-2px);
  box-shadow: 6px 6px 12px rgba(0,0,0,0.4);
  border-color: #DEB887;
}

.menu-item.unavailable {
  opacity: 0.7;
  filter: grayscale(30%);
  border-color: #696969;
}

.item-image {
  width: 120px;
  height: 120px;
  object-fit: cover;
  background: linear-gradient(45deg,rgb(213, 180, 151), #DEB887);
  border-right: 2px solidrgb(185, 161, 136);
  flex-shrink: 0;
}

.item-image-placeholder {
  width: 120px;
  height: 120px;
  background: linear-gradient(45deg,rgb(225, 195, 168), #DEB887);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color:rgb(161, 130, 115);
  border-right: 2px solidrgb(215, 182, 149);
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
  background:rgb(214, 176, 138);
  color: #F5DEB3;
  padding: 3px 8px;
  border-radius: 3px;
  font-size: 0.7rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: 1px solidrgb(234, 192, 172);
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
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.price-original {
  text-decoration: line-through;
  color:rgb(115, 100, 89);
  font-size: 0.75rem;
}

.price-final {
  font-size: 1.3rem;
  font-weight: 700;
  color: #8B4513;
  margin: 0;
}

.price-normal {
  font-size: 1.3rem;
  font-weight: 700;
  color: #2F1B14;
  margin: 0;
}

.discount-badge {
  background: #228B22;
  color: white;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 0.7rem;
  font-weight: 600;
  margin-top: 3px;
  border: 1px solid #32CD32;
}

.availability-badge {
  padding: 4px 8px;
  border-radius: 3px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: 1px solid;
}

.available {
  background: #228B22;
  color: white;
  border-color: #32CD32;
}

.unavailable-badge {
  background: #8B0000;
  color: white;
  border-color: #DC143C;
}

.admin-actions {
  margin-top: 8px;
  display: flex;
  gap: 6px;
}

.btn-modern {
  padding: 6px 12px;
  border: 2px solid;
  border-radius: 3px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
  font-size: 0.8rem;
}

.btn-edit {
  background: #4682B4;
  color: white;
  border-color: #5F9EA0;
}

.btn-delete {
  background: #8B0000;
  color: white;
  border-color: #DC143C;
}

.btn-modern:hover {
  transform: translateY(-1px);
  box-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.btn-nuevo-item {
  background: #228B22;
  color: white;
  padding: 12px 30px;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  display: inline-block;
  transition: all 0.3s ease;
  box-shadow: 3px 3px 6px rgba(0,0,0,0.3);
  border: 2px solid #32CD32;
  width: 100%;
  text-align: center;
  box-sizing: border-box;
}

.btn-nuevo-item:hover {
  transform: translateY(-2px);
  box-shadow: 4px 4px 8px rgba(0,0,0,0.4);
  background: #32CD32;
}

.no-items {
  text-align: center;
  color: #2F1B14;
  font-size: 1.1rem;
  margin-top: 40px;
}

@media (max-width: 768px) {
  .carta-title {
    font-size: 1.8rem;
  }
  
  .menu-grid {
    grid-template-columns: 1fr;
  }
  
  .categorias-nav {
    justify-content: center;
  }
  
  .carta-digital {
    padding: 10px;
  }
  
  .btn-nuevo-item {
    padding: 10px 20px;
    font-size: 0.85rem;
  }
  
  .admin-controls {
    padding: 10px;
  }
}
</style>

<div class="carta-digital">
  <div class="carta-header">
    <h1 class="carta-title">🍽️ Carta Digital</h1>
    <p class="carta-subtitle">Deliciosos platos preparados con amor</p>
  </div>

  <?php if ($rol === 'administrador'): ?>
    <div class="admin-controls">
      <a href="<?= url('carta/create') ?>" class="btn-nuevo-item">
        ➕ Nuevo Ítem
      </a>
    </div>
  <?php else: ?>
    <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.2); text-align: center; color: white;">
      📋 Vista de solo lectura - Consulta los items del menú y precios
    </div>
  <?php endif; ?>

  <?php 
  // Agrupar items por categoría
  $categorias = [];
  foreach ($items as $item) {
      $categoria = $item['categoria'] ?? 'Sin categoría';
      if (!isset($categorias[$categoria])) {
          $categorias[$categoria] = [];
      }
      $categorias[$categoria][] = $item;
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

  <div class="menu-grid" id="menu-grid">
    <?php foreach ($items as $item): ?>
      <?php 
      $precioFinal = $item['precio'];
      if (!empty($item['descuento']) && $item['descuento'] > 0) {
          $descuentoCalculado = $item['precio'] * ($item['descuento'] / 100);
          $precioFinal = $item['precio'] - $descuentoCalculado;
      }
      ?>
      <div class="menu-item <?= !$item['disponibilidad'] ? 'unavailable' : '' ?>" 
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
              <?php if (!empty($item['descuento']) && $item['descuento'] > 0): ?>
                <span class="price-original">$<?= number_format($item['precio'], 2) ?></span>
                <p class="price-final">$<?= number_format($precioFinal, 2) ?></p>
                <span class="discount-badge">-<?= number_format($item['descuento'], 0) ?>% OFF</span>
              <?php else: ?>
                <p class="price-normal">$<?= number_format($item['precio'], 2) ?></p>
              <?php endif; ?>
            </div>
            
            <div class="availability-badge <?= $item['disponibilidad'] ? 'available' : 'unavailable-badge' ?>">
              <?= $item['disponibilidad'] ? '✅ Disponible' : '❌ Agotado' ?>
            </div>
          </div>
          
          <?php if ($rol === 'administrador'): ?>
            <div class="admin-actions">
              <a href="<?= url('carta/edit', ['id' => $item['id_item']]) ?>" class="btn-modern btn-edit">
                ✏️ Editar
              </a>
              <a href="<?= url('carta', ['delete' => $item['id_item']]) ?>" 
                 class="btn-modern btn-delete"
                 onclick="return confirm('¿Borrar ítem &quot;<?= htmlspecialchars($item['nombre']) ?>&quot;?')">
                🗑️ Borrar
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (empty($items)): ?>
    <div class="no-items">
      <h3>🍽️ No hay items en la carta</h3>
      <p>Agrega algunos platos deliciosos para comenzar</p>
    </div>
  <?php endif; ?>
</div>

<script>
function filtrarCategoria(categoria) {
  const items = document.querySelectorAll('.menu-item');
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

// Animación de entrada
document.addEventListener('DOMContentLoaded', function() {
  const items = document.querySelectorAll('.menu-item');
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


