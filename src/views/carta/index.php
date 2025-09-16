<?php
// src/views/carta/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\CartaItem;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Personal y administradores pueden ver la carta
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];


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
  background: linear-gradient(135deg, rgb(144, 104, 76), rgb(92, 64, 51));
  color: white;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 12px;
  text-align: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.carta-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: white;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.carta-subtitle {
  font-size: 0.85rem;
  opacity: 0.9;
  margin: 0;
  color: white;
  text-shadow: 0 1px 2px rgba(0,0,0,0.3);
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
  overflow-x: auto;
  padding: 0 1rem;
  scrollbar-width: thin;
  scrollbar-color: #ccc transparent;
  -webkit-overflow-scrolling: touch;
  scroll-behavior: smooth;
}

.categorias-nav::-webkit-scrollbar {
  height: 6px;
}

.categorias-nav::-webkit-scrollbar-track {
  background: transparent;
}

.categorias-nav::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 3px;
}

.categorias-nav::-webkit-scrollbar-thumb:hover {
  background: #999;
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
  flex-shrink: 0;
  white-space: nowrap;
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

/* Estilos del Modal de Confirmación */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
  animation: fadeIn 0.3s ease;
}

.modal-overlay.show {
  display: flex;
}

.modal-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  max-width: 400px;
  width: 90%;
  margin: 20px;
  overflow: hidden;
  animation: slideUp 0.3s ease;
  transform: scale(0.9);
  transition: transform 0.3s ease;
}

.modal-overlay.show .modal-container {
  transform: scale(1);
}

.modal-header {
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
  color: white;
  padding: 20px;
  text-align: center;
  position: relative;
}

.modal-icon {
  font-size: 2.5rem;
  margin-bottom: 10px;
  animation: pulse 2s infinite;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
}

.modal-body {
  padding: 25px;
  text-align: center;
}

.modal-body p {
  margin: 0 0 15px 0;
  color: #555;
  font-size: 1rem;
  line-height: 1.5;
}

.item-preview {
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 15px;
  margin: 15px 0;
}

.item-name {
  font-weight: 600;
  color: #333;
  font-size: 1.1rem;
}

.warning-text {
  color: #dc3545;
  font-size: 0.9rem;
  font-weight: 500;
  margin: 10px 0 0 0 !important;
}

.modal-footer {
  padding: 20px 25px;
  background: #f8f9fa;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-cancel {
  background: #6c757d;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn-cancel:hover {
  background: #5a6268;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-delete-confirm {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
  box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

.btn-delete-confirm:hover {
  background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
  transform: translateY(-1px);
  box-shadow: 0 6px 12px rgba(220, 53, 69, 0.4);
}

.btn-delete-confirm:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

/* Animaciones */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(0.9);
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

/* Responsive */
@media (max-width: 480px) {
  .modal-container {
    width: 95%;
    margin: 10px;
  }
  
  .modal-header {
    padding: 15px;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .modal-footer {
    padding: 15px 20px;
    flex-direction: column;
  }
  
  .btn-cancel,
  .btn-delete-confirm {
    width: 100%;
    margin: 5px 0;
  }
}

/* Estilos para notificaciones temporales */
#notification-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 2000;
  max-width: 400px;
}

.notification {
  background: white;
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 12px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-left: 4px solid;
  display: flex;
  align-items: center;
  gap: 12px;
  transform: translateX(100%);
  opacity: 0;
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  position: relative;
  overflow: hidden;
}

.notification.show {
  transform: translateX(0);
  opacity: 1;
}

.notification.success {
  border-left-color: #28a745;
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
}

.notification.error {
  border-left-color: #dc3545;
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
}

.notification-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.notification-content {
  flex: 1;
  color: #333;
  font-weight: 500;
  font-size: 0.95rem;
  line-height: 1.4;
}

.notification-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #666;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.notification-close:hover {
  background: rgba(0, 0, 0, 0.1);
  color: #333;
}

/* Efecto de progreso */
.notification::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: currentColor;
  opacity: 0.3;
  animation: progress 4s linear forwards;
}

.notification.success::after {
  background: #28a745;
}

.notification.error::after {
  background: #dc3545;
}

@keyframes progress {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

/* Responsive para notificaciones */
@media (max-width: 480px) {
  #notification-container {
    top: 10px;
    right: 10px;
    left: 10px;
    max-width: none;
  }
  
  .notification {
    padding: 12px 16px;
    font-size: 0.9rem;
  }
  
  .notification-icon {
    font-size: 1.3rem;
  }
}

.btn-modern:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.btn-nuevo-item {
  background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #1e7e34 100%);
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
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
  border: 1px solid #28a745;
  width: 100%;
  justify-content: center;
}

.btn-nuevo-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  background: linear-gradient(135deg, #1e7e34 0%, #28a745 50%, #20c997 100%);
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
    font-size: 1.1rem;
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
    padding: 0 1rem 6px 1rem;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
    flex-wrap: nowrap;
    max-width: 100%;
    position: relative;
  }
  
  .categorias-nav::before,
  .categorias-nav::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 20px;
    pointer-events: none;
    z-index: 1;
  }
  
  .categorias-nav::before {
    left: 0;
    background: linear-gradient(to right, rgba(247, 241, 225, 1), rgba(247, 241, 225, 0));
  }
  
  .categorias-nav::after {
    right: 0;
    background: linear-gradient(to left, rgba(247, 241, 225, 1), rgba(247, 241, 225, 0));
  }
  
  .categoria-btn {
    white-space: nowrap;
    flex-shrink: 0;
    font-size: 0.65rem;
    padding: 4px 8px;
    min-width: fit-content;
  }
  
  .admin-controls {
    padding: 8px;
  }
  
  .btn-nuevo-item {
    padding: 8px 16px;
    font-size: 0.65rem;
  }
  
  /* Mejorar tarjetas móviles */
  .mobile-card {
    margin-bottom: 16px;
    padding: 16px;
    transform: scale(0.9);
    transform-origin: top center;
  }
  
  .card-title strong {
    font-size: 0.9rem;
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
  
  /* Estilos para imágenes en tarjetas móviles */
  .card-image {
    margin: 8px 0;
    text-align: center;
  }
  
  .mobile-item-image {
    width: 100%;
    max-width: 200px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  
  .mobile-image-placeholder {
    width: 100%;
    max-width: 200px;
    height: 120px;
    background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #999;
    margin: 0 auto;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
    <p class="carta-subtitle">Deliciosos platos </p>
  </div>

  <!-- Sistema de notificaciones temporales -->
  <div id="notification-container"></div>

  <?php if ($rol === 'administrador'): ?>
    <div class="admin-controls">
      <a href="<?= url('carta/create') ?>" class="btn-nuevo-item">
        ➕ Nuevo Ítem
      </a>
    </div>
  <?php endif; ?>

  <?php 
  // Definir orden específico de categorías
  $ordenCategorias = [
    'Entradas',
    'Platos principales', 
    'Carnes',
    'Pastas',
    'Pizzas',
    'Ensaladas',
    'Postres',
    'Bebidas'
  ];
  
  // Mapeo de variaciones de nombres de categorías
  $mapeoCategorias = [
    'entradas' => 'Entradas',
    'platos principales' => 'Platos principales',
    'platos principales' => 'Platos principales',
    'Platos Principales' => 'Platos principales',
    'carnes' => 'Carnes',
    'pastas' => 'Pastas',
    'pizzas' => 'Pizzas',
    'ensaladas' => 'Ensaladas',
    'postres' => 'Postres',
    'bebidas' => 'Bebidas'
  ];
  
  // Agrupar items por categoría
  $categorias = [];
  $categoriasUnicas = [];
  
  foreach ($items as $item) {
      $categoriaOriginal = $item['categoria'] ?? 'Sin categoría';
      
      // Normalizar el nombre de la categoría usando el mapeo
      $categoria = $mapeoCategorias[strtolower($categoriaOriginal)] ?? $categoriaOriginal;
      
      // Agregar a la lista de categorías únicas (usando el nombre normalizado)
      if (!in_array($categoria, $categoriasUnicas)) {
          $categoriasUnicas[] = $categoria;
      }
      
      // Agrupar items por categoría (usando el nombre normalizado)
      if (!isset($categorias[$categoria])) {
          $categorias[$categoria] = [];
      }
      $categorias[$categoria][] = $item;
  }
  
  // Ordenar categorías según el orden específico definido
  $categoriasOrdenadas = [];
  foreach ($ordenCategorias as $categoriaOrden) {
      if (in_array($categoriaOrden, $categoriasUnicas)) {
          $categoriasOrdenadas[] = $categoriaOrden;
      }
  }
  
  // Agregar categorías que no están en el orden específico al final
  foreach ($categoriasUnicas as $categoria) {
      if (!in_array($categoria, $categoriasOrdenadas)) {
          $categoriasOrdenadas[] = $categoria;
      }
  }
  
  ?>

  <div class="categorias-nav">
    <button class="categoria-btn active" data-categoria="todas" onclick="filtrarCategoria('todas')">Todas</button>
    <button class="categoria-btn" data-categoria="descuentos" onclick="filtrarCategoria('descuentos')">🏷️ Descuentos</button>
    <?php foreach ($categoriasOrdenadas as $categoria): ?>
      <button class="categoria-btn" data-categoria="<?= htmlspecialchars($categoria) ?>" onclick="filtrarCategoria('<?= htmlspecialchars($categoria) ?>')">
        <?= htmlspecialchars($categoria) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="menu-grid" id="menu-grid">
      <?php 
    // Mostrar items agrupados por categoría en el orden específico
    foreach ($categoriasOrdenadas as $categoriaOrden): 
      if (isset($categorias[$categoriaOrden])): 
        foreach ($categorias[$categoriaOrden] as $item): 
      $precioFinal = $item['precio'];
      if (!empty($item['descuento']) && $item['descuento'] > 0) {
          $descuentoCalculado = $item['precio'] * ($item['descuento'] / 100);
          $precioFinal = $item['precio'] - $descuentoCalculado;
      }
      
      // Normalizar el nombre de la categoría para el data-categoria
      $categoriaOriginal = $item['categoria'] ?? 'Sin categoría';
      $categoriaNormalizada = $mapeoCategorias[strtolower($categoriaOriginal)] ?? $categoriaOriginal;
      ?>
      <div class="menu-item <?= !$item['disponibilidad'] ? 'unavailable' : '' ?>" 
           data-categoria="<?= htmlspecialchars($categoriaNormalizada) ?>"
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
    <?php 
        endforeach; // Cerrar foreach de items de la categoría
      endif; // Cerrar if de categoría existe
    endforeach; // Cerrar foreach de categorías ordenadas
    ?>
  </div>

  <!-- Tarjetas móviles (solo visibles en móvil) -->
  <div class="mobile-cards" id="mobile-cards">
    <?php if (empty($items)): ?>
      <div class="no-items">
        <p>No hay items en la carta.</p>
      </div>
    <?php else: ?>
        <?php 
      // Mostrar items agrupados por categoría en el orden específico (móvil)
      foreach ($categoriasOrdenadas as $categoriaOrden): 
        if (isset($categorias[$categoriaOrden])): 
          foreach ($categorias[$categoriaOrden] as $item): 
            $precioFinal = $item['precio'];
            if (!empty($item['descuento']) && $item['descuento'] > 0) {
                $descuentoCalculado = $item['precio'] * ($item['descuento'] / 100);
                $precioFinal = $item['precio'] - $descuentoCalculado;
            }
            
            // Normalizar el nombre de la categoría para el data-categoria
            $categoriaOriginal = $item['categoria'] ?? 'Sin categoría';
            $categoriaNormalizada = $mapeoCategorias[strtolower($categoriaOriginal)] ?? $categoriaOriginal;
      ?>
        <div class="mobile-card <?= !$item['disponibilidad'] ? 'unavailable' : '' ?>" 
             data-categoria="<?= htmlspecialchars($categoriaNormalizada) ?>"
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
          
          <!-- Imagen del item -->
          <div class="card-image">
            <?php if (!empty($item['imagen_url'])): ?>
              <img src="<?= htmlspecialchars($item['imagen_url']) ?>" 
                   alt="<?= htmlspecialchars($item['nombre']) ?>"
                   class="mobile-item-image"
                   onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
              <div class="mobile-image-placeholder" style="display: none;">
                🍽️
              </div>
            <?php else: ?>
              <div class="mobile-image-placeholder">
                🍽️
              </div>
            <?php endif; ?>
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
      <?php 
          endforeach; // Cerrar foreach de items de la categoría
        endif; // Cerrar if de categoría existe
      endforeach; // Cerrar foreach de categorías ordenadas
      ?>
    <?php endif; ?>
  </div>

  <?php if (empty($items)): ?>
    <div class="no-items">
      <p>No hay items en la carta.</p>
    </div>
  <?php endif; ?>

  <!-- Modal de confirmación de eliminación -->
  <div id="deleteModal" class="modal-overlay">
    <div class="modal-container">
      <div class="modal-header">
        <div class="modal-icon">⚠️</div>
        <h3>Confirmar Eliminación</h3>
      </div>
      <div class="modal-body">
        <p>¿Estás seguro de que quieres eliminar este ítem?</p>
        <div class="item-preview">
          <span class="item-name" id="itemNameToDelete"></span>
        </div>
        <p class="warning-text">Esta acción no se puede deshacer.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeDeleteModal()">
          Cancelar
        </button>
        <button type="button" class="btn-delete-confirm" onclick="confirmDelete()">
          🗑️ Eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Función para aplicar filtro sin modificar URL (para inicialización)
function aplicarFiltroCategoria(categoria) {
  // Actualizar botones activos
  const allButtons = document.querySelectorAll('.categoria-btn');
  allButtons.forEach(btn => {
    btn.classList.remove('active');
  });
  
  // Encontrar y activar el botón correcto
  const targetButton = document.querySelector(`[data-categoria="${categoria}"]`);
  if (targetButton) {
    targetButton.classList.add('active');
  }
  
  // Filtrar items en desktop
  const menuItems = document.querySelectorAll('.menu-item');
  menuItems.forEach(item => {
    const itemCategoria = item.getAttribute('data-categoria');
    let shouldShow = false;
    
    if (categoria === 'todas') {
      shouldShow = true;
    } else if (categoria === 'descuentos') {
      // Mostrar solo items con descuento
      const discountBadge = item.querySelector('.discount-badge');
      shouldShow = discountBadge !== null;
    } else {
      shouldShow = itemCategoria === categoria;
    }
    
    if (shouldShow) {
      item.style.display = 'flex';
    } else {
      item.style.display = 'none';
    }
  });
  
  // Filtrar tarjetas móviles
  const mobileCards = document.querySelectorAll('.mobile-card');
  mobileCards.forEach(card => {
    const cardCategoria = card.getAttribute('data-categoria');
    let shouldShow = false;
    
    if (categoria === 'todas') {
      shouldShow = true;
    } else if (categoria === 'descuentos') {
      // Mostrar solo items con descuento
      const discountBadge = card.querySelector('.discount-badge');
      shouldShow = discountBadge !== null;
    } else {
      shouldShow = cardCategoria === categoria;
    }
    
    if (shouldShow) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

// Función para aplicar filtro cuando el usuario hace clic
function aplicarFiltroCategoriaConEvento(categoria, event) {
  
  // Actualizar botones activos
  document.querySelectorAll('.categoria-btn').forEach(btn => {
    btn.classList.remove('active');
  });
  
  // Activar el botón que se hizo clic
  if (event && event.target) {
  event.target.classList.add('active');
  } else {
    // Fallback si no hay evento
    const targetButton = document.querySelector(`[data-categoria="${categoria}"]`);
    if (targetButton) {
      targetButton.classList.add('active');
    }
  }
  
  // Filtrar items en desktop
  const menuItems = document.querySelectorAll('.menu-item');
  menuItems.forEach(item => {
    const itemCategoria = item.getAttribute('data-categoria');
    let shouldShow = false;
    
    if (categoria === 'todas') {
      shouldShow = true;
    } else if (categoria === 'descuentos') {
      // Mostrar solo items con descuento
      const discountBadge = item.querySelector('.discount-badge');
      shouldShow = discountBadge !== null;
    } else {
      shouldShow = itemCategoria === categoria;
    }
    
    if (shouldShow) {
      item.style.display = 'flex';
    } else {
      item.style.display = 'none';
    }
  });
  
  // Filtrar tarjetas móviles
  const mobileCards = document.querySelectorAll('.mobile-card');
  mobileCards.forEach(card => {
    const cardCategoria = card.getAttribute('data-categoria');
    let shouldShow = false;
    
    if (categoria === 'todas') {
      shouldShow = true;
    } else if (categoria === 'descuentos') {
      // Mostrar solo items con descuento
      const discountBadge = card.querySelector('.discount-badge');
      shouldShow = discountBadge !== null;
    } else {
      shouldShow = cardCategoria === categoria;
    }
    
    if (shouldShow) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

// Filtros de categoría (cuando el usuario hace clic)
function filtrarCategoria(categoria) {
  
  // Aplicar el filtro visual con evento
  aplicarFiltroCategoriaConEvento(categoria, event);
  
  // Guardar el filtro en la URL sin recargar la página
  const url = new URL(window.location);
  if (categoria === 'todas') {
    url.searchParams.delete('categoria');
  } else {
    url.searchParams.set('categoria', categoria);
  }
  window.history.replaceState({}, '', url);
}

// Variables globales para el modal
let currentItemId = null;
let currentItemName = null;

// Función para mostrar el modal de confirmación
function confirmarBorradoCarta(itemId, itemName) {
  currentItemId = itemId;
  currentItemName = itemName;
  
  // Actualizar el nombre del ítem en el modal
  document.getElementById('itemNameToDelete').textContent = itemName;
  
  // Mostrar el modal
  const modal = document.getElementById('deleteModal');
  modal.classList.add('show');
  
  // Agregar efecto de blur al body
  document.body.style.overflow = 'hidden';
}

// Función para cerrar el modal
function closeDeleteModal() {
  const modal = document.getElementById('deleteModal');
  modal.classList.remove('show');
  
  // Restaurar scroll del body
  document.body.style.overflow = 'auto';
  
  // Limpiar variables
  currentItemId = null;
  currentItemName = null;
}

// Función para confirmar la eliminación
function confirmDelete() {
  if (currentItemId && currentItemName) {
    // Crear formulario para enviar la solicitud de eliminación
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('carta/delete') ?>';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = currentItemId;
    
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();
  }
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener('click', function(event) {
  const modal = document.getElementById('deleteModal');
  if (event.target === modal) {
    closeDeleteModal();
  }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeDeleteModal();
  }
});

// Sistema de notificaciones temporales
function showNotification(message, type = 'success', duration = 4000) {
  const container = document.getElementById('notification-container');
  
  // Crear elemento de notificación
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  
  // Icono según el tipo y mensaje
  let icon = '✅';
  if (type === 'error') {
    // Si es un mensaje de eliminación, usar icono de basura
    if (message.toLowerCase().includes('eliminado') || message.toLowerCase().includes('borrado')) {
      icon = '🗑️';
    } else {
      icon = '❌';
    }
  }
  
  notification.innerHTML = `
    <span class="notification-icon">${icon}</span>
    <span class="notification-content">${message}</span>
    <button class="notification-close" onclick="closeNotification(this)">×</button>
  `;
  
  // Agregar al contenedor
  container.appendChild(notification);
  
  // Mostrar con animación
  setTimeout(() => {
    notification.classList.add('show');
  }, 100);
  
  // Auto-eliminar después del tiempo especificado
  setTimeout(() => {
    closeNotification(notification.querySelector('.notification-close'));
  }, duration);
}

function closeNotification(closeButton) {
  const notification = closeButton.closest('.notification');
  if (notification) {
    notification.classList.remove('show');
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 400);
  }
}

// Inicializar todo en un solo DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
  // Verificar si estamos en móvil o desktop
  const isMobile = window.innerWidth <= 768;
  
  // Obtener parámetros de la URL
  const urlParams = new URLSearchParams(window.location.search);
  const categoriaFiltro = urlParams.get('categoria') || 'todas';
  
  // Manejar notificaciones PRIMERO
  if (urlParams.has('success')) {
    const message = urlParams.get('success');
    
    // Determinar el tipo de notificación basado en el mensaje
    let notificationType = 'success';
    let duration = 5000;
    
    // Si el mensaje es de eliminación, usar estilo de error (rojo)
    if (message.toLowerCase().includes('eliminado') || message.toLowerCase().includes('borrado')) {
      notificationType = 'error';
      duration = 6000;
    }
    
    showNotification(message, notificationType, duration);
  }
  
  if (urlParams.has('error')) {
    const message = urlParams.get('error');
    showNotification(message, 'error', 6000);
  }
  
  // Limpiar la URL de notificaciones pero mantener el filtro
  if (urlParams.has('success') || urlParams.has('error')) {
    const newUrl = window.location.pathname + window.location.search.replace(/[?&]success=[^&]*/, '').replace(/[?&]error=[^&]*/, '');
    const cleanUrl = newUrl.endsWith('?') ? newUrl.slice(0, -1) : newUrl;
    window.history.replaceState({}, '', cleanUrl);
  }
  
  // Aplicar filtro inicial desde la URL SIN modificar la URL
  // Pequeño delay para asegurar que todos los elementos estén cargados
  setTimeout(() => {
    aplicarFiltroCategoria(categoriaFiltro);
  }, 100);
  
  // Manejar redimensionamiento de ventana
  window.addEventListener('resize', function() {
    const newIsMobile = window.innerWidth <= 768;
    if (newIsMobile !== isMobile) {
      // Mantener el filtro actual al redimensionar
      const currentCategoria = urlParams.get('categoria') || 'todas';
      aplicarFiltroCategoria(currentCategoria);
    }
  });
});
</script>