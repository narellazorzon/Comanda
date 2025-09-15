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

// Detectar si es edición o creación
$is_edit = isset($_GET['id']) && (int)$_GET['id'] > 0;
$pedido_id = $is_edit ? (int)$_GET['id'] : 0;
$pedido = null;
$detalles = [];

// Si es edición, cargar datos del pedido
if ($is_edit) {
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
    // Debug: verificar qué datos se obtienen
    error_log('Detalles del pedido ' . $pedido_id . ': ' . print_r($detalles, true));
}

// Cargar datos necesarios
$mesas = Mesa::all();
$items = CartaItem::all();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mesa = (int) ($_POST['id_mesa'] ?? 0);
    $modo_consumo = $_POST['modo_consumo'] ?? 'stay';
    $forma_pago = $_POST['forma_pago'] ?? null;
    $observaciones = $_POST['observaciones'] ?? '';
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_email = trim($_POST['cliente_email'] ?? '');
    $items_pedido = $_POST['items'] ?? [];
    
    // Validaciones
    if (empty($cliente_nombre)) {
        $error = 'El nombre del cliente es obligatorio';
    } elseif ($modo_consumo === 'stay' && $id_mesa <= 0) {
        $error = 'Debe seleccionar una mesa para pedidos en el local';
    } elseif (empty($items_pedido)) {
        $error = 'Debe agregar al menos un ítem al pedido';
    } else {
        try {
            $data = [
                'modo_consumo' => $modo_consumo,
                'forma_pago' => $forma_pago,
                'observaciones' => $observaciones,
                'cliente_nombre' => $cliente_nombre,
                'cliente_email' => $cliente_email,
                'items' => $items_pedido
            ];
            
            // Solo incluir mesa si es modo 'stay'
            if ($modo_consumo === 'stay') {
                $data['id_mesa'] = $id_mesa;
            }
            
            if ($is_edit) {
                // Actualizar pedido existente
                $resultado = Pedido::update($pedido_id, $data);
                if ($resultado) {
                    $success = 'Pedido actualizado correctamente';
                    // Recargar datos del pedido
                    $pedido = Pedido::find($pedido_id);
                    $detalles = Pedido::getDetalles($pedido_id);
                } else {
                    $error = 'Error al actualizar el pedido';
                }
            } else {
                // Crear nuevo pedido
                $pedidoId = Pedido::create($data);
                if ($pedidoId > 0) {
                    $success = 'Pedido creado correctamente con ID: ' . $pedidoId;
                    // Limpiar formulario
                    $_POST = [];
                } else {
                    $error = 'Error al crear el pedido';
                }
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
  background: linear-gradient(135deg,rgb(144, 104, 76),rgb(92, 64, 51));
  color: white !important;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 12px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.pedido-header * {
  color: white !important;
}

.pedido-header h1 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
  color: white !important;
}

.form-section {
  background: rgb(245, 239, 224);
  border: 2px solid rgb(187, 155, 123);
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
  background: rgb(177, 128, 80);
  color: white;
  transform: translateY(-1px);
  box-shadow: 3px 3px 6px rgba(0,0,0,0.3);
}

.modo-consumo-btn:hover {
  transform: translateY(-1px);
  box-shadow: 3px 3px 6px rgba(0,0,0,0.3);
}

.items-section {
  background: rgb(245, 239, 224);
  border: 2px solid #CD853F;
  border-radius: 6px;
  padding: 10px;
  margin-bottom: 10px;
  box-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.items-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  padding-bottom: 5px;
  border-bottom: 1px solid #CD853F;
}

.items-controls {
  display: flex;
  gap: 8px;
  align-items: center;
}

.add-item-btn {
  background: #28a745;
  color: white;
  border: none;
  padding: 7px 14px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.8rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

.add-item-btn:hover {
  background: #218838;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
}

.item-card {
  background: rgb(245, 239, 224);
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}

.item-card:hover {
  border-color: #CD853F;
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.item-detail-section {
  margin: 10px 0;
  padding: 8px 0;
  border-top: 1px solid #f0f0f0;
}

.detail-label {
  display: block;
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 5px;
  font-weight: 500;
}

.item-detail-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
  background: #fafafa;
  transition: all 0.3s ease;
}

.item-detail-input:focus {
  outline: none;
  border-color: #CD853F;
  background: white;
  box-shadow: 0 0 0 2px rgba(205, 133, 63, 0.2);
}

.item-detail-input::placeholder {
  color: #999;
  font-style: italic;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 9px;
  padding-bottom: 7px;
  border-bottom: 1px solid #e9ecef;
}

.item-name {
  font-weight: 600;
  color: #2F1B14;
  font-size: 0.9rem;
}

.item-price {
  font-weight: 600;
  color: #28a745;
  font-size: 1rem;
}

.item-controls {
  display: grid;
  grid-template-columns: 1fr 90px 90px;
  gap: 10px;
  align-items: center;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 7px;
}

.quantity-btn {
  background:rgb(71, 56, 34);
  color: white;
  border: none;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  cursor: pointer;
  font-weight: bold;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.quantity-btn:hover {
  background:rgb(104, 99, 90);
  transform: scale(1.1);
}

.quantity-input {
  width: 54px;
  text-align: center;
  padding: 5px;
  border: 2px solid #e9ecef;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
}

.quantity-input:focus {
  outline: none;
  border-color: #CD853F;
  box-shadow: 0 0 0 3px rgba(205, 133, 63, 0.2);
}

.item-total {
  font-weight: bold;
  color: #2F1B14;
  font-size: 1rem;
  text-align: right;
}

.remove-item-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 7px 10px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.8rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

.remove-item-btn:hover {
  background: #c82333;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4);
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
  background: #A0522D;
  transform: translateY(-2px);
}

.btn-secondary {
  background:rgb(131, 100, 77);
  color: white;
}

.btn-secondary:hover {
  background:rgb(93, 72, 62);
  transform: translateY(-2px);
  text-decoration: none;
  color: white;
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
    padding: 4px;
    max-height: 100vh;
    overflow-y: auto;
  }
  
  .pedido-header {
    padding: 8px;
    margin-bottom: 8px;
  }
  
  .pedido-header h1 {
    font-size: 1.1rem;
  }
  
  .form-section, .items-section {
    padding: 6px;
    margin-bottom: 6px;
  }
  
  .form-section h3 {
    font-size: 1rem;
    margin-bottom: 6px;
  }
  
  .form-group {
    margin-bottom: 8px;
  }
  
  .form-group label {
    font-size: 0.8rem;
    margin-bottom: 2px;
  }
  
  .form-group select,
  .form-group input,
  .form-group textarea {
    padding: 4px;
    font-size: 0.8rem;
  }
  
  .modo-consumo-container {
    flex-direction: row;
    gap: 6px;
    margin-bottom: 8px;
  }
  
  .modo-consumo-btn {
    padding: 8px 4px;
    font-size: 0.7rem;
  }
  
  .modo-consumo-btn div:first-child {
    font-size: 1.2rem;
    margin-bottom: 4px;
  }
  
  .modo-consumo-btn div:nth-child(2) {
    font-size: 0.8rem;
  }
  
  .modo-consumo-btn div:last-child {
    font-size: 0.7rem;
  }
  
   .items-header {
    flex-direction: column;
    gap: 8px;
     align-items: stretch;
   }
   
   .items-header h3 {
     font-size: 1rem;
     margin: 0;
   }
   
   .add-item-btn {
     padding: 8px 16px;
     font-size: 0.9rem;
     width: 100%;
   }
   
  .item-card {
    padding: 8px;
    margin-bottom: 8px;
  }
  
  .item-detail-section {
    margin: 8px 0;
    padding: 6px 0;
  }
  
  .detail-label {
    font-size: 0.8rem;
    margin-bottom: 4px;
  }
  
  .item-detail-input {
    padding: 6px 8px;
    font-size: 0.85rem;
  }
   
   .item-controls {
    grid-template-columns: 1fr;
     gap: 8px;
   }
   
   .quantity-controls {
     justify-content: center;
   }
   
   .item-total {
     text-align: center;
     font-size: 1rem;
   }
  
  .item-row input,
  .item-row select {
    padding: 3px;
    font-size: 0.8rem;
  }
  
  .remove-item-btn {
    padding: 2px 4px;
    font-size: 0.7rem;
    width: 100%;
    margin-top: 2px;
  }
  
  .total-section {
    padding: 8px;
    margin-bottom: 8px;
  }
  
  .total-section h3 {
    font-size: 1rem;
    margin-bottom: 4px;
  }
  
  .total-amount {
    font-size: 1.2rem;
  }
  
  .buttons-section {
    flex-direction: column;
    gap: 4px;
    margin-top: 8px;
  }
  
  .btn {
    padding: 6px 12px;
    font-size: 0.8rem;
    width: 100%;
  }
  
  .alert {
    padding: 6px;
    margin-bottom: 8px;
    font-size: 0.8rem;
  }
  
   /* Ocultar información del mozo en móvil para ahorrar espacio */
   #mozo-info {
     font-size: 0.7rem;
     padding: 4px;
   }
   
   /* Ocultar headers en móvil */
   .item-headers {
     display: none !important;
   }
  
  /* Hacer que el textarea sea más compacto */
  .form-group textarea {
    rows: 2;
    min-height: 50px;
  }
  
  /* Optimizar el grid de items para móvil */
  .item-row {
    display: flex;
    flex-direction: column;
    gap: 3px;
  }
  
  .item-row > * {
    width: 100%;
  }
  
  /* Hacer que los botones de modo de consumo sean más compactos */
  .modo-consumo-btn {
    min-height: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }
  
  /* Optimizar el header de items */
  .items-header {
    text-align: center;
  }
  
  /* Hacer que el total sea más prominente pero compacto */
  .total-section {
    position: sticky;
    bottom: 0;
    z-index: 10;
    margin-bottom: 0;
  }
  
  /* Botón flotante para agregar items en móvil */
  .floating-add-btn {
    position: fixed;
    bottom: 80px;
    right: 15px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #28a745;
    color: white;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 20;
    display: none;
  }
  
  .floating-add-btn:hover {
    background: #218838;
    transform: scale(1.1);
  }
  
  /* Mostrar botón flotante solo en móvil */
  @media (max-width: 768px) {
    /* Mostrar el botón de agregar en móvil */
    .add-item-btn {
      display: block;
      width: 100%;
      padding: 8px 16px;
      font-size: 0.9rem;
    }
    
    /* Estilos del modal en móvil */
    .modal {
      padding: 10px !important;
    }
    
    .modal > div {
      width: 95% !important;
      max-width: none !important;
      padding: 15px !important;
      max-height: 90vh !important;
    }
    
    .modal h3 {
      font-size: 1.1rem !important;
    }
    
    .modal input {
      padding: 12px !important;
      font-size: 1rem !important;
    }
    
    .modal #items-list {
      max-height: 60vh !important;
    }
    
    .item-option {
      padding: 12px !important;
      margin-bottom: 8px !important;
    }
    
    .item-option h4 {
      font-size: 1rem !important;
    }
    
    .item-option p {
      font-size: 0.9rem !important;
    }
  }
}

/* Estilos adicionales para pantallas muy pequeñas */
@media (max-width: 480px) {
  .pedido-edit-container {
    padding: 2px;
  }
  
  .form-section, .items-section {
    padding: 4px;
    margin-bottom: 4px;
  }
  
  .modo-consumo-container {
    gap: 4px;
  }
  
  .modo-consumo-btn {
    padding: 6px 2px;
  }
  
  .item-row {
    padding: 3px;
  }
  
  .total-section {
    padding: 6px;
  }
  
  .buttons-section {
    gap: 3px;
  }
}
</style>

<div class="pedido-edit-container">
  <!-- Header del pedido -->
  <div class="pedido-header">
    <h1><?= $is_edit ? '✏️ Editar Pedido #' . $pedido_id : '🍽️ Crear Nuevo Pedido' ?></h1>
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
      
      <!-- Modo de Consumo -->
      <div class="form-group">
        <label>Modo de Consumo *</label>
        <div class="modo-consumo-container">
          <div class="modo-consumo-btn <?= (isset($_POST['modo_consumo']) && $_POST['modo_consumo'] === 'stay') || (!isset($_POST['modo_consumo']) && (!$pedido || $pedido['modo_consumo'] === 'stay')) ? 'selected' : '' ?>" 
               onclick="selectModoConsumo('stay')">
            <div style="font-size: 1.5rem; margin-bottom: 6px;">🍽️</div>
            <div style="font-size: 0.9rem;"><strong>En el Local</strong></div>
            <div style="font-size: 0.8rem; opacity: 0.8;">Stay</div>
          </div>
          <div class="modo-consumo-btn <?= (isset($_POST['modo_consumo']) && $_POST['modo_consumo'] === 'takeaway') || ($pedido && $pedido['modo_consumo'] === 'takeaway') ? 'selected' : '' ?>" 
               onclick="selectModoConsumo('takeaway')">
            <div style="font-size: 1.5rem; margin-bottom: 6px;">🛍️</div>
            <div style="font-size: 0.9rem;"><strong>Para Llevar</strong></div>
            <div style="font-size: 0.8rem; opacity: 0.8;">Takeaway</div>
          </div>
        </div>
        <input type="hidden" name="modo_consumo" id="modo_consumo" value="<?= $_POST['modo_consumo'] ?? ($pedido['modo_consumo'] ?? 'stay') ?>" required>
      </div>

      <!-- Campo Mesa (solo para modo stay) -->
      <div class="form-group" id="mesa-group">
        <label for="id_mesa">Mesa *</label>
        <select name="id_mesa" id="id_mesa" required onchange="showMozoInfo()">
          <option value="">Seleccionar mesa</option>
          <?php foreach ($mesas as $mesa): ?>
            <option value="<?= $mesa['id_mesa'] ?>" 
                    data-mozo="<?= htmlspecialchars($mesa['mozo_nombre_completo'] ?? 'Sin asignar') ?>"
                    <?= (isset($_POST['id_mesa']) && $_POST['id_mesa'] == $mesa['id_mesa']) || ($pedido && $pedido['id_mesa'] == $mesa['id_mesa']) ? 'selected' : '' ?>>
              Mesa #<?= $mesa['numero'] ?> - <?= $mesa['ubicacion'] ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div id="mozo-info" style="margin-top: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px; display: none;">
          <strong>Mozo asignado:</strong> <span id="mozo-nombre"></span>
        </div>
    </div>

      <!-- Campos de Cliente -->
      <div class="form-group">
        <label for="cliente_nombre">Nombre del Cliente *</label>
        <input type="text" name="cliente_nombre" id="cliente_nombre" 
               value="<?= htmlspecialchars($_POST['cliente_nombre'] ?? ($pedido['cliente_nombre'] ?? '')) ?>" 
               placeholder="Ingrese el nombre del cliente" required>
      </div>

      <div class="form-group">
        <label for="cliente_email">Email del Cliente</label>
        <input type="email" name="cliente_email" id="cliente_email" 
               value="<?= htmlspecialchars($_POST['cliente_email'] ?? ($pedido['cliente_email'] ?? '')) ?>" 
               placeholder="cliente@ejemplo.com">
                </div>
                
      <div class="form-group">
        <label for="forma_pago">Forma de Pago</label>
        <select name="forma_pago" id="forma_pago">
          <option value="">Seleccionar forma de pago...</option>
          <option value="efectivo" <?= (isset($_POST['forma_pago']) && $_POST['forma_pago'] === 'efectivo') || ($pedido && $pedido['forma_pago'] === 'efectivo') ? 'selected' : '' ?>>💵 Efectivo</option>
          <option value="tarjeta" <?= (isset($_POST['forma_pago']) && $_POST['forma_pago'] === 'tarjeta') || ($pedido && $pedido['forma_pago'] === 'tarjeta') ? 'selected' : '' ?>>💳 Tarjeta</option>
          <option value="transferencia" <?= (isset($_POST['forma_pago']) && $_POST['forma_pago'] === 'transferencia') || ($pedido && $pedido['forma_pago'] === 'transferencia') ? 'selected' : '' ?>>🏦 Transferencia</option>
        </select>
      </div>
                
      <div class="form-group">
        <label for="observaciones">Observaciones</label>
        <textarea name="observaciones" id="observaciones" rows="2" 
                  placeholder="Observaciones especiales..."><?= htmlspecialchars($_POST['observaciones'] ?? ($pedido['observaciones'] ?? '')) ?></textarea>
      </div>
    </div>

    <!-- Items del pedido -->
    <div class="items-section">
      <div class="items-header">
        <h3>🍽️ Items del Pedido</h3>
        <div class="items-controls">
        <button type="button" class="add-item-btn" onclick="addItem()">
          ➕ Agregar Item
        </button>
        </div>
    </div>

      <div id="items-container">
        <!-- Los items se agregarán dinámicamente aquí -->
      </div>
    </div>

    <!-- Total -->
    <div class="total-section">
      <h3>💰 Total del Pedido</h3>
      <div class="total-amount" id="total-amount">$0.00</div>
    </div>

    <!-- Botones -->
    <div class="buttons-section">
      <a href="<?= url('pedidos') ?>" class="btn btn-secondary">
      ← Volver a Pedidos
    </a>
      <button type="submit" class="btn btn-success">
        <?= $is_edit ? '💾 Actualizar Pedido' : '💾 Crear Pedido' ?>
      </button>
    </div>
  </form>
  
</div>

<script>
let itemIndex = 0;
let items = [];

// Función para calcular precio con descuento
function calcularPrecioConDescuento(precio, descuento) {
  if (descuento > 0) {
    const descuentoCalculado = precio * (descuento / 100);
    return precio - descuentoCalculado;
  }
  return precio;
}

// Datos de items disponibles
const itemsData = {
  <?php foreach ($items as $item): ?>
    <?= $item['id_item'] ?>: {
      id: <?= $item['id_item'] ?>,
      nombre: '<?= htmlspecialchars($item['nombre']) ?>',
      precio: <?= $item['precio'] ?>,
      descuento: <?= $item['descuento'] ?? 0 ?>,
      categoria: '<?= htmlspecialchars($item['categoria']) ?>'
    },
  <?php endforeach; ?>
};


function selectModoConsumo(modo) {
  // Remover selección anterior
  document.querySelectorAll('.modo-consumo-btn').forEach(btn => {
    btn.classList.remove('selected');
  });
  
  // Seleccionar nuevo modo
  event.target.closest('.modo-consumo-btn').classList.add('selected');
  document.getElementById('modo_consumo').value = modo;
  
  // Mostrar/ocultar campo de mesa según el modo
  const mesaGroup = document.getElementById('mesa-group');
  const mesaSelect = document.getElementById('id_mesa');
  const mozoInfo = document.getElementById('mozo-info');
  
  if (mesaGroup) {
    if (modo === 'takeaway') {
      // Ocultar campo de mesa para takeaway
      mesaGroup.style.display = 'none';
      mesaSelect.required = false;
      mesaSelect.value = '';
      mozoInfo.style.display = 'none';
    } else {
      // Mostrar campo de mesa para stay
      mesaGroup.style.display = 'block';
      mesaSelect.required = true;
      // Mostrar información del mozo si hay mesa seleccionada
      showMozoInfo();
    }
  }
}

function showMozoInfo() {
  const select = document.getElementById('id_mesa');
  const mozoInfo = document.getElementById('mozo-info');
  const mozoNombre = document.getElementById('mozo-nombre');
  
  if (select.value) {
    const selectedOption = select.options[select.selectedIndex];
    const mozo = selectedOption.getAttribute('data-mozo');
    mozoNombre.textContent = mozo;
    mozoInfo.style.display = 'block';
        } else {
    mozoInfo.style.display = 'none';
  }
}

function addItem() {
  // Crear modal de selección de item
  const modal = document.createElement('div');
  modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  `;
  
  const modalContent = document.createElement('div');
  modalContent.style.cssText = `
    background: white;
    border-radius: 12px;
    padding: 20px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
  `;
  
  modalContent.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h3 style="margin: 0; color: #2F1B14;">🍽️ Seleccionar Item</h3>
      <button onclick="this.closest('.modal').remove()" style="background: #dc3545; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 1.2rem;">×</button>
    </div>
    <div style="margin-bottom: 20px;">
      <input type="text" id="item-search" placeholder="🔍 Buscar item..." style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1rem;">
    </div>
    <div id="items-list" style="max-height: 400px; overflow-y: auto;">
      ${generateItemsList()}
    </div>
  `;
  
  modal.className = 'modal';
  modal.appendChild(modalContent);
  document.body.appendChild(modal);
  
  // Funcionalidad de búsqueda
  const searchInput = modal.querySelector('#item-search');
  searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const itemsList = modal.querySelector('#items-list');
    itemsList.innerHTML = generateItemsList(searchTerm);
  });
}

function generateItemsList(searchTerm = '') {
  let html = '';
  const filteredItems = Object.values(itemsData).filter(item => 
    item.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
    item.categoria.toLowerCase().includes(searchTerm.toLowerCase())
  );
  
  // Agrupar por categoría
  const groupedItems = {};
  filteredItems.forEach(item => {
    if (!groupedItems[item.categoria]) {
      groupedItems[item.categoria] = [];
    }
    groupedItems[item.categoria].push(item);
  });
  
  Object.keys(groupedItems).sort().forEach(categoria => {
    html += `<div style="margin-bottom: 15px;">
      <h4 style="color: #CD853F; margin: 0 0 10px 0; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">${categoria}</h4>`;
    
    groupedItems[categoria].forEach(item => {
      const precioConDescuento = calcularPrecioConDescuento(item.precio, item.descuento);
      const precioHtml = item.descuento > 0 ? 
        `<div style="font-weight: bold; color: #28a745; font-size: 1.1rem;">
           <span style="text-decoration: line-through; color: #999; font-size: 0.9em;">$${item.precio.toFixed(2)}</span>
           <span style="color: #e74c3c;">$${precioConDescuento.toFixed(2)}</span>
           <span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7em; margin-left: 5px;">-${item.descuento}%</span>
         </div>` :
        `<div style="font-weight: bold; color: #28a745; font-size: 1.1rem;">$${item.precio.toFixed(2)}</div>`;
      
      html += `
        <div onclick="selectItem(${item.id})" class="item-option" style="
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 12px;
          margin-bottom: 8px;
          background: #f8f9fa;
          border: 2px solid #e9ecef;
          border-radius: 8px;
          cursor: pointer;
          transition: all 0.3s ease;
          min-height: 50px;
        " onmouseover="this.style.borderColor='#CD853F'; this.style.backgroundColor='#fff3cd'" onmouseout="this.style.borderColor='#e9ecef'; this.style.backgroundColor='#f8f9fa'">
          <div>
            <div style="font-weight: 600; color: #2F1B14; margin-bottom: 4px;">${item.nombre}</div>
          </div>
          ${precioHtml}
        </div>
      `;
    });
    
    html += '</div>';
  });
  
  return html;
}

function selectItem(itemId) {
  const item = itemsData[itemId];
  
  if (item) {
    // Verificar si el item ya está en el pedido
    const existingItem = items.find(i => i.id === itemId);
    if (existingItem) {
      // Incrementar cantidad
      existingItem.cantidad += 1;
      updateItemCard(existingItem);
    } else {
      // Agregar nuevo item
      const precioConDescuento = calcularPrecioConDescuento(item.precio, item.descuento);
      const newItem = {
        id: item.id,
        nombre: item.nombre,
        precio: precioConDescuento,
        precioOriginal: item.precio,
        descuento: item.descuento,
        cantidad: 1,
        detalle: '', // Campo para observaciones del item
        index: itemIndex++
      };
      items.push(newItem);
      createItemCard(newItem);
    }
    
    updateTotal();
    
    // Cerrar modal
    document.querySelector('.modal').remove();
  }
}

function createItemCard(item) {
  const container = document.getElementById('items-container');
  const card = document.createElement('div');
  card.className = 'item-card';
  card.setAttribute('data-index', item.index);
  
  // Generar HTML del precio (con descuento si aplica)
  let precioHtml = '';
  if (item.descuento > 0) {
    precioHtml = `
      <div class="item-price">
        <span class="precio-original" style="text-decoration: line-through; color: #999; font-size: 0.9em;">$${item.precioOriginal.toFixed(2)}</span>
        <span class="precio-descuento" style="color: #e74c3c; font-weight: bold;">$${item.precio.toFixed(2)}</span>
        <span class="descuento-badge" style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7em; margin-left: 5px;">-${item.descuento}%</span>
      </div>
    `;
  } else {
    precioHtml = `<div class="item-price">$${item.precio.toFixed(2)}</div>`;
  }

  card.innerHTML = `
    <div class="item-header">
      <div class="item-name">${item.nombre}</div>
      ${precioHtml}
    </div>
    <div class="item-detail-section">
      <label for="detalle_${item.index}" class="detail-label">Detalle/Observaciones:</label>
      <input type="text" 
             id="detalle_${item.index}" 
             class="item-detail-input" 
             placeholder="Ej: sin sal, bien cocido, etc." 
             value="${item.detalle || ''}"
             onchange="updateItemDetail(${item.index}, this.value)"
             maxlength="100">
    </div>
    <div class="item-controls">
      <div class="quantity-controls">
        <button type="button" class="quantity-btn" onclick="changeQuantity(${item.index}, -1)">-</button>
        <input type="number" class="quantity-input" value="${item.cantidad}" min="1" max="99" 
               onchange="updateQuantity(${item.index}, this.value)">
        <button type="button" class="quantity-btn" onclick="changeQuantity(${item.index}, 1)">+</button>
      </div>
      <div class="item-total">$${(item.precio * item.cantidad).toFixed(2)}</div>
      <button type="button" class="remove-item-btn" onclick="removeItem(${item.index})">
        🗑️ Eliminar
      </button>
    </div>
    <input type="hidden" name="items[${item.index}][id_item]" value="${item.id}">
    <input type="hidden" name="items[${item.index}][cantidad]" value="${item.cantidad}">
    <input type="hidden" name="items[${item.index}][precio_unitario]" value="${item.precio}">
    <input type="hidden" name="items[${item.index}][detalle]" value="${item.detalle || ''}">
  `;
  
  container.appendChild(card);
}

function updateItemDetail(itemIndex, detalle) {
  const item = items.find(i => i.index === itemIndex);
  if (item) {
    item.detalle = detalle;
    // Actualizar el input hidden
    const card = document.querySelector(`[data-index="${itemIndex}"]`);
    if (card) {
      const hiddenDetalle = card.querySelector('input[name*="[detalle]"]');
      if (hiddenDetalle) hiddenDetalle.value = detalle;
    }
  }
}

function updateItemCard(item) {
  const card = document.querySelector(`[data-index="${item.index}"]`);
  if (card) {
    const quantityInput = card.querySelector('.quantity-input');
    const totalElement = card.querySelector('.item-total');
    const hiddenQuantity = card.querySelector('input[name*="[cantidad]"]');
    const hiddenDetalle = card.querySelector('input[name*="[detalle]"]');
    
    quantityInput.value = item.cantidad;
    totalElement.textContent = `$${(item.precio * item.cantidad).toFixed(2)}`;
    hiddenQuantity.value = item.cantidad;
    if (hiddenDetalle) hiddenDetalle.value = item.detalle || '';
    
    // Actualizar el precio si hay descuento
    if (item.descuento > 0) {
      const precioElement = card.querySelector('.item-price');
      if (precioElement) {
        precioElement.innerHTML = `
          <span class="precio-original" style="text-decoration: line-through; color: #999; font-size: 0.9em;">$${item.precioOriginal.toFixed(2)}</span>
          <span class="precio-descuento" style="color: #e74c3c; font-weight: bold;">$${item.precio.toFixed(2)}</span>
          <span class="descuento-badge" style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7em; margin-left: 5px;">-${item.descuento}%</span>
        `;
      }
    }
  }
}

function changeQuantity(index, change) {
  const item = items.find(i => i.index === index);
  if (item) {
    const newQuantity = Math.max(1, Math.min(99, item.cantidad + change));
    item.cantidad = newQuantity;
    updateItemCard(item);
    updateTotal();
  }
}

function updateQuantity(index, value) {
  const item = items.find(i => i.index === index);
  if (item) {
    const newQuantity = Math.max(1, Math.min(99, parseInt(value) || 1));
    item.cantidad = newQuantity;
    updateItemCard(item);
    updateTotal();
  }
}

function removeItem(index) {
  const itemIndex = items.findIndex(i => i.index === index);
  if (itemIndex !== -1) {
    items.splice(itemIndex, 1);
    const card = document.querySelector(`[data-index="${index}"]`);
    if (card) {
      card.remove();
    }
    updateTotal();
  }
}

function updateTotal() {
  let total = 0;
  
  items.forEach(item => {
    total += item.precio * item.cantidad;
  });
  
  const totalElement = document.getElementById('total-amount');
  if (totalElement) {
    totalElement.textContent = `$${total.toFixed(2)}`;
  }
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    updateTotal();
    
  // Inicializar visibilidad del campo de mesa según el modo seleccionado
  const modoConsumo = document.getElementById('modo_consumo').value;
  if (modoConsumo) {
    selectModoConsumo(modoConsumo);
  } else {
    // Por defecto, modo 'stay' está seleccionado
    selectModoConsumo('stay');
  }
  
  // Si es edición, cargar items existentes
  <?php if ($is_edit && !empty($detalles)): ?>
    loadExistingItems();
  <?php endif; ?>
});

// Función para cargar items existentes en modo edición
function loadExistingItems() {
  console.log('=== FUNCIÓN loadExistingItems LLAMADA ===');
  <?php if ($is_edit && !empty($detalles)): ?>
    const existingItems = <?= json_encode($detalles) ?>;
    console.log('=== DEBUGGING CARGA DE ITEMS ===');
    console.log('is_edit:', <?= $is_edit ? 'true' : 'false' ?>);
    console.log('detalles vacío:', <?= empty($detalles) ? 'true' : 'false' ?>);
    console.log('Cantidad de detalles:', <?= count($detalles) ?>);
    console.log('Cargando items existentes:', existingItems);
    
    existingItems.forEach((detalle, index) => {
      const item = {
        index: items.length,
        id: detalle.id_item,
        nombre: detalle.item_nombre, // Corregido: usar item_nombre en lugar de nombre
        precio: parseFloat(detalle.precio_unitario),
        cantidad: parseInt(detalle.cantidad),
        detalle: detalle.detalle || '',
        descuento: 0,
        precioOriginal: parseFloat(detalle.precio_unitario)
      };
      
      console.log('Agregando item:', item);
      items.push(item);
      createItemCard(item);
    });
    
    updateTotal();
    console.log('Items cargados:', items);
  <?php else: ?>
    console.log('No hay items para cargar');
  <?php endif; ?>
}
</script>