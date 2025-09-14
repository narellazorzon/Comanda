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

// Debug: mostrar el rol del usuario
// echo "<!-- Rol del usuario: " . $rol . " -->";

// La eliminación se maneja a través del controlador CartaController::delete()

// Cargamos todos los ítems de la carta (incluyendo no disponibles para los filtros)
$items = CartaItem::allIncludingUnavailable();

?>

<style>
/* Estilos modernos para la Carta - Compacto */
.carta-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 12px;
  background:rgb(244, 229, 194);
  min-height: 100vh;
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}

.carta-header {
  background: linear-gradient(135deg,rgb(130, 93, 65),rgb(101, 70, 56));
  color: white;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 12px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.carta-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: white;
}

.carta-subtitle {
  font-size: 0.85rem;
  opacity: 0.9;
  margin: 0;
  color: white;
}

.admin-controls {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 10px;
  margin-bottom: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}

.categorias-nav {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.categoria-btn {
  background: white;
  color: #333;
  border: 1px solid #e0e0e0;
  padding: 6px 12px;
  border-radius: 16px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  font-size: 0.8rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.categoria-btn:hover, .categoria-btn.active {
  background: #8B4513;
  color: white;
  border-color: #8B4513;
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.menu-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
  max-height: 70vh;
  overflow-y: auto;
  padding-right: 8px;
}

/* Estilos para el scrollbar */
.menu-grid::-webkit-scrollbar {
  width: 8px;
}

.menu-grid::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.menu-grid::-webkit-scrollbar-thumb {
  background: #8B4513;
  border-radius: 4px;
}

.menu-grid::-webkit-scrollbar-thumb:hover {
  background: #A0522D;
}

.menu-item {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
  position: relative;
  display: flex;
  flex-direction: row;
  min-height: 140px;
  align-items: stretch;
}

.menu-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
  border-color: #8B4513;
}

.menu-item.unavailable {
  opacity: 0.6;
  filter: grayscale(50%);
}

.item-image {
  width: 140px;
  height: 140px;
  object-fit: cover;
  background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
  flex-shrink: 0;
  border-right: 1px solid #e0e0e0;
}

.item-image-placeholder {
  width: 140px;
  height: 140px;
  background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  color: #8B4513;
  flex-shrink: 0;
  border-right: 1px solid #e0e0e0;
}

.item-content {
  padding: 12px;
  display: flex;
  flex-direction: column;
  flex: 1;
  justify-content: space-between;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.item-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin: 0;
  flex: 1;
}

.item-category {
  background: #8B4513;
  color: white;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  margin-left: 8px;
  white-space: nowrap;
}

.item-description {
  color: #666;
  font-size: 0.85rem;
  line-height: 1.4;
  margin: 0 0 12px 0;
  flex: 1;
}

.item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 8px;
  border-top: 1px solid #f0f0f0;
}

.item-price {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.price-original {
  text-decoration: line-through;
  color: #999;
  font-size: 0.8rem;
}

.price-final, .price-normal {
  font-size: 1.2rem;
  font-weight: 700;
  color: #8B4513;
  margin: 0;
}

.discount-badge {
  background: #dc3545;
  color: white;
  padding: 1px 6px;
  border-radius: 10px;
  font-size: 0.65rem;
  font-weight: 600;
}

.availability-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  border: 1px solid;
}

.available {
  background: #d4edda;
  color: #155724;
  border-color: #c3e6cb;
}

.unavailable-badge {
  background: #f8d7da;
  color: #721c24;
  border-color: #f5c6cb;
}

.admin-actions {
  margin-top: 8px;
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: flex-start;
}

.btn-modern {
  padding: 4px 8px;
  border: 1px solid;
  border-radius: 4px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
  font-size: 0.7rem;
  display: inline-flex;
  align-items: center;
  gap: 2px;
  white-space: nowrap;
  flex-shrink: 0;
}

.btn-edit {
  background:rgb(109, 170, 234);
  color: white;
  border-color: #007bff;
}

.btn-delete {
  background:rgb(216, 102, 113);
  color: white;
  border-color: #dc3545;
}

.btn-modern:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.btn-nuevo-item {
  background: #28a745;
  color: white;
  padding: 8px 16px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  border: 1px solid #28a745;
  width: 100%;
  justify-content: center;
}

.btn-nuevo-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  background: #218838;
}

.no-items {
  text-align: center;
  color: #666;
  font-size: 1rem;
  margin-top: 30px;
  padding: 30px;
  background: white;
  border-radius: 8px;
  border: 2px dashed #e0e0e0;
}

/* Tarjetas móviles */
.mobile-cards {
  display: none;
}

.mobile-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  margin-bottom: 16px;
  padding: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.mobile-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.15);
  border-color: #8B4513;
}

.mobile-card.unavailable {
  opacity: 0.6;
  filter: grayscale(50%);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.card-title {
  flex: 1;
}

.card-title strong {
  display: block;
  font-size: 1rem;
  color: #333;
  margin-bottom: 3px;
  font-weight: 600;
}

.card-category {
  font-size: 0.75rem;
  color: #8B4513;
  background: #f8f9fa;
  padding: 2px 6px;
  border-radius: 10px;
  display: inline-block;
}

.card-status {
  margin-left: 6px;
}

.card-content {
  margin-bottom: 8px;
}

.card-description {
  font-size: 0.8rem;
  color: #666;
  line-height: 1.3;
  margin-bottom: 6px;
}

.card-price {
  font-size: 1.1rem;
  font-weight: 700;
  color: #8B4513;
  margin-bottom: 6px;
}

.card-actions {
  display: flex;
  gap: 6px;
  margin-top: 8px;
}

.card-actions .btn-modern {
  flex: 1;
  justify-content: center;
  font-size: 0.7rem;
  padding: 5px 10px;
}

/* Responsive */
@media (max-width: 768px) {
  .carta-container {
    padding: 8px;
  }
  
  .carta-header {
    padding: 10px;
    margin-bottom: 10px;
  }
  
  .carta-title {
    font-size: 1.3rem;
  }
  
  /* Ocultar vista desktop en móvil */
  .menu-grid {
    display: none;
  }
  
  /* Mostrar vista móvil */
  .mobile-cards {
    display: block;
  }
  
  .categorias-nav {
    justify-content: flex-start;
    overflow-x: auto;
    padding-bottom: 6px;
  }
  
  .categoria-btn {
    white-space: nowrap;
    flex-shrink: 0;
    font-size: 0.75rem;
    padding: 5px 10px;
  }
  
  .admin-controls {
    padding: 8px;
  }
  
  .btn-nuevo-item {
    padding: 8px 16px;
    font-size: 0.85rem;
  }
  
  /* Mejorar tarjetas móviles */
  .mobile-card {
    margin-bottom: 16px;
    padding: 16px;
  }
  
  .card-title strong {
    font-size: 1.1rem;
    line-height: 1.2;
  }
  
  .card-category {
    font-size: 0.7rem;
    margin-top: 4px;
  }
  
  .card-description {
    font-size: 0.85rem;
    line-height: 1.4;
    margin-bottom: 8px;
  }
  
  .card-price {
    font-size: 1.2rem;
    margin-bottom: 8px;
  }
  
  .card-actions {
    margin-top: 12px;
  }
  
  .card-actions .btn-modern {
    font-size: 0.75rem;
    padding: 6px 12px;
  }
}

@media (min-width: 769px) {
  .mobile-cards {
    display: none;
  }
}
</style>

<div class="carta-container">
  <div class="carta-header">
    <h1 class="carta-title">🍽️ Carta Digital</h1>
    <p class="carta-subtitle">Deliciosos platos preparados con amor</p>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 6px; padding: 0.75rem; margin: 1rem 0; font-weight: 500; font-size: 0.85rem;">
      ✅ <?= htmlspecialchars($_GET['success']) ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 6px; padding: 0.75rem; margin: 1rem 0; font-weight: 500; font-size: 0.85rem;">
      ⚠️ <?= htmlspecialchars($_GET['error']) ?>
    </div>
  <?php endif; ?>

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
  $categoriasUnicas = [];
  
  foreach ($items as $item) {
      $categoria = $item['categoria'] ?? 'Sin categoría';
      
      // Agregar a la lista de categorías únicas
      if (!in_array($categoria, $categoriasUnicas)) {
          $categoriasUnicas[] = $categoria;
      }
      
      // Agrupar items por categoría
      if (!isset($categorias[$categoria])) {
          $categorias[$categoria] = [];
      }
      $categorias[$categoria][] = $item;
  }
  
  // Ordenar categorías alfabéticamente
  sort($categoriasUnicas);
  
  // Debug: mostrar categorías encontradas
  // echo "<!-- Categorías encontradas: " . implode(', ', $categoriasUnicas) . " -->";
  ?>

  <div class="categorias-nav">
    <button class="categoria-btn active" onclick="filtrarCategoria('todas')">Todas</button>
    <?php foreach ($categoriasUnicas as $categoria): ?>
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
           data-categoria="<?= htmlspecialchars($item['categoria'] ?? 'Sin categoría') ?>"
           data-item-id="<?= $item['id_item'] ?>">
        
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
              <a href="#" 
                 class="btn-modern btn-delete"
                 onclick="confirmarBorradoCarta(<?= $item['id_item'] ?>, '<?= htmlspecialchars($item['nombre']) ?>')">
                🗑️ Borrar
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Tarjetas móviles (solo visibles en móvil) -->
  <div class="mobile-cards" id="mobile-cards">
    <?php if (empty($items)): ?>
      <div class="no-items">
        <p>No hay items en la carta.</p>
      </div>
    <?php else: ?>
      <?php foreach ($items as $item): ?>
        <?php 
        $precioFinal = $item['precio'];
        if (!empty($item['descuento']) && $item['descuento'] > 0) {
            $descuentoCalculado = $item['precio'] * ($item['descuento'] / 100);
            $precioFinal = $item['precio'] - $descuentoCalculado;
        }
        ?>
        <div class="mobile-card <?= !$item['disponibilidad'] ? 'unavailable' : '' ?>" 
             data-categoria="<?= htmlspecialchars($item['categoria'] ?? 'Sin categoría') ?>"
             data-item-id="<?= $item['id_item'] ?>">
          
          <div class="card-header">
            <div class="card-title">
              <strong><?= htmlspecialchars($item['nombre']) ?></strong>
              <span class="card-category"><?= htmlspecialchars($item['categoria'] ?? 'Sin categoría') ?></span>
            </div>
            <div class="card-status">
              <div class="availability-badge <?= $item['disponibilidad'] ? 'available' : 'unavailable-badge' ?>">
                <?= $item['disponibilidad'] ? '✅' : '❌' ?>
              </div>
            </div>
          </div>
          
          <div class="card-content">
            <p class="card-description">
              <?= htmlspecialchars($item['descripcion'] ?? 'Delicioso plato preparado con ingredientes frescos.') ?>
            </p>
            
            <div class="card-price">
              <?php if (!empty($item['descuento']) && $item['descuento'] > 0): ?>
                <span class="price-original">$<?= number_format($item['precio'], 2) ?></span>
                <span class="price-final">$<?= number_format($precioFinal, 2) ?></span>
                <span class="discount-badge">-<?= number_format($item['descuento'], 0) ?>% OFF</span>
              <?php else: ?>
                <span class="price-normal">$<?= number_format($item['precio'], 2) ?></span>
              <?php endif; ?>
            </div>
          </div>
          
          <?php if ($rol === 'administrador'): ?>
            <div class="card-actions">
              <a href="<?= url('carta/edit', ['id' => $item['id_item']]) ?>" class="btn-modern btn-edit">
                ✏️ Editar
              </a>
              <a href="#" 
                 class="btn-modern btn-delete"
                 onclick="confirmarBorradoCarta(<?= $item['id_item'] ?>, '<?= htmlspecialchars($item['nombre']) ?>')">
                🗑️ Borrar
              </a>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (empty($items)): ?>
    <div class="no-items">
      <p>No hay items en la carta.</p>
    </div>
  <?php endif; ?>
</div>

<script>
// Filtros de categoría
function filtrarCategoria(categoria) {
  console.log('Filtrando por categoría:', categoria);
  
  // Actualizar botones activos
  document.querySelectorAll('.categoria-btn').forEach(btn => {
    btn.classList.remove('active');
  });
  event.target.classList.add('active');
  
  // Filtrar items en desktop
  const menuItems = document.querySelectorAll('.menu-item');
  console.log('Filtrando', menuItems.length, 'items de desktop');
  menuItems.forEach(item => {
    const itemCategoria = item.getAttribute('data-categoria');
    if (categoria === 'todas' || itemCategoria === categoria) {
      item.style.display = 'flex';
    } else {
      item.style.display = 'none';
    }
  });
  
  // Filtrar tarjetas móviles
  const mobileCards = document.querySelectorAll('.mobile-card');
  console.log('Filtrando', mobileCards.length, 'tarjetas móviles');
  mobileCards.forEach(card => {
    const cardCategoria = card.getAttribute('data-categoria');
    if (categoria === 'todas' || cardCategoria === categoria) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

// Función para confirmar borrado de items de carta
function confirmarBorradoCarta(itemId, itemName) {
  if (confirm(`¿Estás seguro de que quieres eliminar el item "${itemName}"?`)) {
    // Crear formulario para enviar la solicitud de eliminación
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('carta/delete') ?>';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = itemId;
    
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();
  }
}

// Inicializar filtros
document.addEventListener('DOMContentLoaded', function() {
  console.log('Inicializando filtros de carta');
  
  // Verificar si estamos en móvil o desktop
  const isMobile = window.innerWidth <= 768;
  console.log('Es móvil:', isMobile);
  
  // Aplicar filtro inicial
  filtrarCategoria('todas');
  
  // Manejar redimensionamiento de ventana
  window.addEventListener('resize', function() {
    const newIsMobile = window.innerWidth <= 768;
    if (newIsMobile !== isMobile) {
      console.log('Cambio de vista detectado, reaplicando filtros');
      filtrarCategoria('todas');
    }
  });
});
</script>