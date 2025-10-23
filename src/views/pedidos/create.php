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
// Personal y administradores pueden crear pedidos
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
    } elseif (empty($items_pedido) && !$is_edit) {
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
  padding: 20px;
  min-height: 100vh;
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
  background: linear-gradient(135deg,rgb(250, 241, 219) 0%, #f5f2e8 100%);
  border: none;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 4px 20px rgba(144, 104, 76, 0.1);
  transition: all 0.3s ease;
}

.form-section:hover {
  box-shadow: 0 8px 30px rgba(144, 104, 76, 0.15);
  transform: translateY(-2px);
}

.form-section h3 {
  color: #2c3e50;
  margin-top: 0;
  margin-bottom: 16px;
  font-size: 1.2rem;
  font-weight: 700;
  border-bottom: none;
  padding-bottom: 0;
  position: relative;
}

.form-section h3::after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 0;
  width: 40px;
  height: 3px;
  background: linear-gradient(90deg, #90684C, #5C4033);
  border-radius: 2px;
}

.form-group {
  margin-bottom: 10px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-family: "Segoe UI", Tahoma, sans-serif;
  font-weight: 600;
  color: #34495e;
  font-size: 0.95rem;
  letter-spacing: 0.3px;
}

.form-group select,
.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e8ecf0;
  border-radius: 8px;
  background: #ffffff;
  font-family: "Segoe UI", Tahoma, sans-serif;
  font-size: 0.95rem;
  box-sizing: border-box;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

/* Estilos para el dropdown personalizado de mesa */
.custom-select {
  position: relative;
  width: 100%;
}

.custom-select-trigger {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e8ecf0;
  border-radius: 8px;
  background: #ffffff;
  font-family: "Segoe UI", Tahoma, sans-serif;
  font-size: 0.95rem;
  box-sizing: border-box;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.custom-select-trigger:focus {
  outline: none;
  border-color: #90684C;
  box-shadow: 0 0 0 3px rgba(144, 104, 76, 0.1);
  transform: translateY(-1px);
}

.custom-select-arrow {
  transition: transform 0.3s ease;
}

.custom-select.open .custom-select-arrow {
  transform: rotate(180deg);
}

.custom-select-options {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #ffffff;
  border: 2px solid #e8ecf0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  display: none;
}

.custom-select.open .custom-select-options {
  display: block;
}

.custom-select-option {
  padding: 12px 16px;
  cursor: pointer;
  transition: background-color 0.2s ease;
  border-bottom: 1px solid #f1f3f4;
  font-family: "Segoe UI", Tahoma, sans-serif;
  font-size: 0.95rem;
}

.custom-select-option:last-child {
  border-bottom: none;
}

.custom-select-option:hover {
  background-color: #f8f9fa;
}

.custom-select-option.selected {
  background-color: #90684C;
  color: white;
}

/* Scroll personalizado para las opciones */
.custom-select-options::-webkit-scrollbar {
  width: 6px;
}

.custom-select-options::-webkit-scrollbar-track {
  background: #f1f3f4;
  border-radius: 3px;
}

.custom-select-options::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #90684C, #5C4033);
  border-radius: 3px;
}

.custom-select-options::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #5C4033, #90684C);
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #90684C;
  box-shadow: 0 0 0 3px rgba(144, 104, 76, 0.1);
  transform: translateY(-1px);
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
  background: #ffffff;
  border: 2px solid #e8ecf0;
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.modo-consumo-btn.selected {
  background: linear-gradient(135deg, #90684C, #5C4033);
  color: white;
  border-color: #5C4033;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(144, 104, 76, 0.3);
}

.modo-consumo-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  border-color: #90684C;
}

.items-section {
  background: linear-gradient(135deg, rgb(250, 241, 219) 0%, #f5f2e8 100%);
  border: none;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 4px 20px rgba(144, 104, 76, 0.1);
  transition: all 0.3s ease;
}

.items-section:hover {
  box-shadow: 0 8px 30px rgba(144, 104, 76, 0.15);
  transform: translateY(-2px);
}

.items-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
}

.items-controls {
  display: flex;
  gap: 8px;
  align-items: center;
}

.add-item-btn {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
}

.add-item-btn:hover {
  background: linear-gradient(135deg, #229954, #27ae60);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
}

.item-card {
  background: linear-gradient(135deg, rgb(250, 241, 219) 0%, #f5f2e8 100%);
  border: none;
  border-radius: 10px;
  padding: 12px;
  margin-bottom: 12px;
  box-shadow: 0 4px 15px rgba(144, 104, 76, 0.15), 0 2px 6px rgba(144, 104, 76, 0.1);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.item-card:hover {
  box-shadow: 0 8px 25px rgba(144, 104, 76, 0.25), 0 4px 12px rgba(144, 104, 76, 0.15);
  transform: translateY(-3px);
  background: linear-gradient(135deg, #fff3cd 0%, #f0f2f5 100%);
}

.item-detail-section {
  margin: 8px 0;
  padding: 8px;
  border: none;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(144, 104, 76, 0.08);
}

.detail-label {
  display: block;
  font-size: 0.8rem;
  color: #5C4033;
  margin-bottom: 4px;
  font-weight: 600;
  font-family: "Segoe UI", Tahoma, sans-serif;
}

.item-detail-input {
  width: 100%;
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  background: rgba(255, 255, 255, 0.9);
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(144, 104, 76, 0.12), inset 0 1px 3px rgba(144, 104, 76, 0.1);
  font-family: "Segoe UI", Tahoma, sans-serif;
}

.item-detail-input:focus {
  outline: none;
  background: white;
  box-shadow: 0 4px 12px rgba(144, 104, 76, 0.2), inset 0 1px 3px rgba(144, 104, 76, 0.15), 0 0 0 3px rgba(144, 104, 76, 0.15);
  transform: translateY(-1px);
}

.item-detail-input::placeholder {
  color: #999;
  font-style: italic;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
  padding-bottom: 4px;
  border-bottom: none;
  box-shadow: 0 1px 3px rgba(144, 104, 76, 0.1);
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
  grid-template-columns: 1fr 80px 80px;
  gap: 8px;
  align-items: center;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 4px;
}

.quantity-btn {
  background: linear-gradient(135deg, #34495e, #2c3e50);
  color: white;
  border: none;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  cursor: pointer;
  font-weight: bold;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 6px rgba(52, 73, 94, 0.3);
}

.quantity-btn:hover {
  background: linear-gradient(135deg, #2c3e50, #34495e);
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(52, 73, 94, 0.4);
}

.quantity-input {
  width: 50px;
  text-align: center;
  padding: 6px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 2px 8px rgba(144, 104, 76, 0.12), inset 0 1px 3px rgba(144, 104, 76, 0.1);
  transition: all 0.3s ease;
  font-family: "Segoe UI", Tahoma, sans-serif;
}

.quantity-input:focus {
  outline: none;
  box-shadow: 0 4px 12px rgba(144, 104, 76, 0.2), inset 0 1px 3px rgba(144, 104, 76, 0.15), 0 0 0 3px rgba(144, 104, 76, 0.15);
  transform: translateY(-1px);
  background: white;
}

.item-total {
  font-weight: bold;
  color: #2F1B14;
  font-size: 1rem;
  text-align: right;
}

.remove-item-btn {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.8rem;
  transition: all 0.3s ease;
  box-shadow: 0 3px 8px rgba(231, 76, 60, 0.3);
}

.remove-item-btn:hover {
  background: linear-gradient(135deg, #c0392b, #a93226);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(231, 76, 60, 0.4);
}

.total-section {
  background: linear-gradient(135deg, #90684C, #5C4033);
  color: white;
  padding: 20px;
  border-radius: 12px;
  text-align: center;
  margin-bottom: 20px;
  box-shadow: 0 8px 25px rgba(144, 104, 76, 0.3);
  transition: all 0.3s ease;
}

.total-section:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(144, 104, 76, 0.4);
}

.total-section h3 {
  margin: 0 0 6px 0;
  font-size: 1.2rem;
}

.total-amount {
  font-size: 1.8rem;
  font-weight: bold;
  color: #ecf0f1;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.buttons-section {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-top: 12px;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.btn-primary {
  background: linear-gradient(135deg, #90684C, #5C4033);
  color: white;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #5C4033, #90684C);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(144, 104, 76, 0.3);
}

.btn-secondary {
  background: linear-gradient(135deg, #90684C, #5C4033);
  color: white;
  border: none;
}

.btn-secondary:hover {
  background: linear-gradient(135deg, #5C4033, #90684C);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(144, 104, 76, 0.3);
  text-decoration: none;
  color: white;
}

.btn-success {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
  color: white;
}

.btn-success:hover {
  background: linear-gradient(135deg, #229954, #27ae60);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(39, 174, 96, 0.3);
}

.alert {
  padding: 16px;
  margin-bottom: 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border: none;
}

.alert-success {
  background: linear-gradient(135deg, #d4edda, #c3e6cb);
  color: #155724;
  border: none;
}

.alert-error {
  background: linear-gradient(135deg, #f8d7da, #f5c6cb);
  color: #721c24;
  border: none;
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
    font-family: "Segoe UI", Tahoma, sans-serif;
    font-size: 0.8rem;
    margin-bottom: 2px;
  }
  
  .form-group select,
  .form-group input,
  .form-group textarea {
    padding: 8px 12px;
    font-family: "Segoe UI", Tahoma, sans-serif;
    font-size: 0.9rem;
    width: 100%;
    box-sizing: border-box;
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
      padding: 5px !important;
      align-items: flex-start !important;
      padding-top: 60px !important;
    }
    
    .modal > div {
      width: 98% !important;
      max-width: none !important;
      padding: 20px 15px !important;
      max-height: 85vh !important;
      margin: 0 auto !important;
      position: relative !important;
    }
    
    .modal h3 {
      font-size: 1.1rem !important;
      margin-bottom: 15px !important;
    }
    
    /* Asegurar que el botón cerrar sea accesible */
    .modal button[onclick*="closest"] {
      position: absolute !important;
      top: 15px !important;
      right: 15px !important;
      z-index: 1001 !important;
      width: 40px !important;
      height: 40px !important;
      font-size: 1.4rem !important;
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

/* Estilos para barras de scroll personalizadas */
#items-list::-webkit-scrollbar {
  width: 8px;
}

#items-list::-webkit-scrollbar-track {
  background: #f3e2b8;
  border-radius: 4px;
}

#items-list::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #90684C, #5C4033);
  border-radius: 4px;
  border: 1px solid #5C4033;
}

#items-list::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #5C4033, #90684C);
  box-shadow: 0 2px 4px rgba(144, 104, 76, 0.3);
}

/* Estilos para scroll del modal principal */
.modal > div::-webkit-scrollbar {
  width: 8px;
}

.modal > div::-webkit-scrollbar-track {
  background: #f3e2b8;
  border-radius: 4px;
}

.modal > div::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #90684C, #5C4033);
  border-radius: 4px;
  border: 1px solid #5C4033;
}

.modal > div::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #5C4033, #90684C);
  box-shadow: 0 2px 4px rgba(144, 104, 76, 0.3);
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
  
  /* Modal en pantallas muy pequeñas */
  .modal {
    padding-top: 50px !important;
  }
  
  .modal > div {
    width: 100% !important;
    padding: 15px 10px !important;
    max-height: 80vh !important;
  }
  
  .modal button[onclick*="closest"] {
    width: 35px !important;
    height: 35px !important;
    font-size: 1.2rem !important;
    top: 10px !important;
    right: 10px !important;
  }
  
  /* Campos del cliente específicos para móvil */
  .form-group input[type="text"],
  .form-group input[type="email"],
  .form-group textarea {
    padding: 10px 12px;
    font-family: "Segoe UI", Tahoma, sans-serif;
    font-size: 0.9rem;
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }
  
  .form-group label {
    font-family: "Segoe UI", Tahoma, sans-serif;
    font-size: 0.85rem;
    margin-bottom: 4px;
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
        <div class="custom-select" id="mesa-select">
          <div class="custom-select-trigger" tabindex="0">
            <span id="mesa-selected-text">Seleccionar mesa</span>
            <span class="custom-select-arrow">▼</span>
          </div>
          <div class="custom-select-options">
            <div class="custom-select-option" data-value="" data-mozo="">Seleccionar mesa</div>
            <?php foreach ($mesas as $mesa): ?>
              <div class="custom-select-option" 
                   data-value="<?= $mesa['id_mesa'] ?>" 
                   data-mozo="<?= htmlspecialchars($mesa['mozo_nombre_completo'] ?? 'Sin asignar') ?>"
                   <?= (isset($_POST['id_mesa']) && $_POST['id_mesa'] == $mesa['id_mesa']) || ($pedido && $pedido['id_mesa'] == $mesa['id_mesa']) ? 'class="selected"' : '' ?>>
                Mesa #<?= $mesa['numero'] ?> - <?= $mesa['ubicacion'] ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <input type="hidden" name="id_mesa" id="id_mesa" value="<?= isset($_POST['id_mesa']) ? $_POST['id_mesa'] : ($pedido['id_mesa'] ?? '') ?>">
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
        <label for="observaciones">Observaciones Especiales</label>
        <textarea name="observaciones" id="observaciones" rows="2" 
                  placeholder="Ingrese observaciones especiales para el pedido..."><?= htmlspecialchars($_POST['observaciones'] ?? ($pedido['observaciones'] ?? '')) ?></textarea>
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
        <?php if ($is_edit && !empty($detalles)): ?>
          <?php foreach ($detalles as $index => $detalle): ?>
            <div class="item-card" data-index="<?= $index ?>">
              <div class="item-header">
                <div class="item-name"><?= htmlspecialchars($detalle['item_nombre']) ?></div>
                <div class="item-price">$<?= number_format($detalle['precio_unitario'], 2) ?></div>
              </div>
              <div class="item-detail-section">
                <label for="detalle_<?= $index ?>" class="detail-label">Detalle/Observaciones:</label>
                <input type="text" 
                       id="detalle_<?= $index ?>" 
                       class="item-detail-input" 
                       value="<?= htmlspecialchars($detalle['detalle']) ?>"
                       placeholder="Ej: sin sal, sin condimentos..."
                       onchange="updateItemDetail(<?= $index ?>, this.value)"
                       maxlength="100">
              </div>
              <div class="item-controls">
                <div class="quantity-controls">
                  <button type="button" class="quantity-btn" onclick="changeQuantity(<?= $index ?>, -1)">-</button>
                  <input type="number" class="quantity-input" value="<?= $detalle['cantidad'] ?>" min="1" max="99" 
                         onchange="updateQuantity(<?= $index ?>, this.value)">
                  <button type="button" class="quantity-btn" onclick="changeQuantity(<?= $index ?>, 1)">+</button>
                </div>
                <div class="item-total">$<?= number_format($detalle['subtotal'], 2) ?></div>
                <button type="button" class="remove-item-btn" onclick="removeItem(<?= $index ?>)">
                  🗑️ Eliminar
                </button>
              </div>
              <input type="hidden" name="items[<?= $index ?>][id_item]" value="<?= $detalle['id_item'] ?>">
              <input type="hidden" name="items[<?= $index ?>][cantidad]" value="<?= $detalle['cantidad'] ?>">
              <input type="hidden" name="items[<?= $index ?>][precio_unitario]" value="<?= $detalle['precio_unitario'] ?>">
              <input type="hidden" name="items[<?= $index ?>][detalle]" value="<?= htmlspecialchars($detalle['detalle']) ?>">
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Total -->
    <div class="total-section">
      <h3>💰 Total del Pedido</h3>
      <div class="total-amount" id="total-amount">$0.00</div>
    </div>

    <!-- Botones -->
    <div class="buttons-section">
      <button type="submit" class="btn btn-success">
        <?= $is_edit ? '💾 Actualizar Pedido' : '💾 Crear Pedido' ?>
      </button>
      <a href="<?= url('pedidos') ?>" class="btn btn-secondary">
      ← Volver a Pedidos
    </a>
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

// Inicializar dropdown personalizado de mesa
document.addEventListener('DOMContentLoaded', function() {
  const customSelect = document.getElementById('mesa-select');
  const trigger = customSelect.querySelector('.custom-select-trigger');
  const options = customSelect.querySelectorAll('.custom-select-option');
  const hiddenInput = document.getElementById('id_mesa');
  const selectedText = document.getElementById('mesa-selected-text');
  const mozoInfo = document.getElementById('mozo-info');
  const mozoNombre = document.getElementById('mozo-nombre');

  // Abrir/cerrar dropdown
  trigger.addEventListener('click', function() {
    customSelect.classList.toggle('open');
  });

  // Cerrar dropdown al hacer clic fuera
  document.addEventListener('click', function(e) {
    if (!customSelect.contains(e.target)) {
      customSelect.classList.remove('open');
    }
  });

  // Seleccionar opción
  options.forEach(option => {
    option.addEventListener('click', function() {
      const value = this.getAttribute('data-value');
      const text = this.textContent;
      const mozo = this.getAttribute('data-mozo');
      
      // Actualizar selección
      hiddenInput.value = value;
      selectedText.textContent = text;
      
      // Remover selección anterior
      options.forEach(opt => opt.classList.remove('selected'));
      this.classList.add('selected');
      
      // Mostrar/ocultar info del mozo
      if (value) {
        mozoNombre.textContent = mozo;
        mozoInfo.style.display = 'block';
      } else {
        mozoInfo.style.display = 'none';
      }
      
      // Cerrar dropdown
      customSelect.classList.remove('open');
    });
  });

  // Navegación con teclado
  trigger.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      customSelect.classList.toggle('open');
    }
  });
});

function showMozoInfo() {
  // Esta función ya no es necesaria con el dropdown personalizado
  // Se maneja automáticamente en el event listener
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
    background: linear-gradient(135deg, #f7f1e1 0%, #f3e2b8 50%, #eee0be 100%);
    border-radius: 12px;
    padding: 25px;
    max-width: 600px;
    width: 95%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 15px 35px rgba(144, 104, 76, 0.3), 0 0 0 1px rgba(144, 104, 76, 0.1);
  `;
  
  modalContent.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; box-shadow: 0 2px 0 rgba(144, 104, 76, 0.3);">
      <h3 style="margin: 0; color: #2F1B14; font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 1.4rem; font-weight: 700; position: relative;">
        🍽️ Seleccionar Item
        <div style="position: absolute; bottom: -8px; left: 0; width: 40px; height: 3px; background: linear-gradient(90deg, #90684C, #5C4033); border-radius: 2px;"></div>
      </h3>
      <button onclick="this.closest('.modal').remove()" style="background: linear-gradient(135deg, #90684C, #5C4033); color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; box-shadow: 0 4px 12px rgba(144, 104, 76, 0.3); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 15px rgba(144, 104, 76, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(144, 104, 76, 0.3)'">×</button>
    </div>
    <div style="margin-bottom: 25px;">
      <label style="display: block; margin-bottom: 8px; font-family: 'Segoe UI', Tahoma, sans-serif; font-weight: 600; color: #34495e; font-size: 0.95rem; letter-spacing: 0.3px;">🔍 Buscar Item</label>
      <input type="text" id="item-search" placeholder="Ingrese el nombre del item a buscar..." style="width: 100%; padding: 12px 16px; border: 1px solid transparent; border-radius: 8px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 0.95rem; box-sizing: border-box; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(144, 104, 76, 0.15), 0 0 0 1px rgba(144, 104, 76, 0.1);" onfocus="this.style.boxShadow='0 6px 20px rgba(144, 104, 76, 0.25), 0 0 0 2px rgba(144, 104, 76, 0.2)'; this.style.transform='translateY(-1px)'" onblur="this.style.boxShadow='0 4px 12px rgba(144, 104, 76, 0.15), 0 0 0 1px rgba(144, 104, 76, 0.1)'; this.style.transform='translateY(0)'">
    </div>
    <div id="items-list" style="max-height: 400px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #90684C #f3e2b8;">
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
      <h4 style="color: #90684C; margin: 0 0 10px 0; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">${categoria}</h4>`;
    
    groupedItems[categoria].forEach(item => {
      const precioConDescuento = calcularPrecioConDescuento(item.precio, item.descuento);
      const precioHtml = item.descuento > 0 ? 
        `<div style="text-align: right;">
           <div style="font-family: 'Segoe UI', Tahoma, sans-serif; font-weight: bold; color: #28a745; font-size: 1.1rem; margin-bottom: 2px;">
             <span style="text-decoration: line-through; color: #999; font-size: 0.9em;">$${item.precio.toFixed(2)}</span>
             <span style="color: #e74c3c;">$${precioConDescuento.toFixed(2)}</span>
           </div>
           <span style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.75em; font-weight: 600; box-shadow: 0 2px 4px rgba(231, 76, 60, 0.3);">-${item.descuento}%</span>
         </div>` :
        `<div style="text-align: right;">
           <div style="font-family: 'Segoe UI', Tahoma, sans-serif; font-weight: bold; color: #28a745; font-size: 1.1rem;">$${item.precio.toFixed(2)}</div>
         </div>`;
      
      html += `
        <div onclick="selectItem(${item.id})" class="item-option" style="
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 15px 18px;
          margin-bottom: 12px;
          background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
          border: 1px solid transparent;
          border-radius: 10px;
          cursor: pointer;
          transition: all 0.3s ease;
          min-height: 60px;
          box-shadow: 0 4px 12px rgba(144, 104, 76, 0.15), 0 0 0 1px rgba(144, 104, 76, 0.1);
        " onmouseover="this.style.backgroundColor='linear-gradient(135deg, #fff3cd 0%, #f0f2f5 100%)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(144, 104, 76, 0.25), 0 0 0 2px rgba(144, 104, 76, 0.2)'" onmouseout="this.style.backgroundColor='linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(144, 104, 76, 0.15), 0 0 0 1px rgba(144, 104, 76, 0.1)'">
          <div>
            <div style="font-family: 'Segoe UI', Tahoma, sans-serif; font-weight: 600; color: #2F1B14; margin-bottom: 4px; font-size: 1rem;">${item.nombre}</div>
            <div style="font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 0.85rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">${item.categoria}</div>
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
    
  // Inicializar array de items si estamos en modo edición
  <?php if ($is_edit && !empty($detalles)): ?>
    // Limpiar array existente
    items = [];
    
    // Cargar items existentes en el array JavaScript
    <?php foreach ($detalles as $index => $detalle): ?>
      items.push({
        index: <?= $index ?>,
        id: <?= $detalle['id_item'] ?>,
        nombre: '<?= addslashes($detalle['item_nombre']) ?>',
        precio: <?= $detalle['precio_unitario'] ?>,
        cantidad: <?= $detalle['cantidad'] ?>,
        detalle: '<?= addslashes($detalle['detalle']) ?>',
        descuento: 0,
        precioOriginal: <?= $detalle['precio_unitario'] ?>
      });
    <?php endforeach; ?>
    
    // Actualizar itemIndex para evitar conflictos
    itemIndex = <?= count($detalles) ?>;
    
    console.log('Items cargados desde PHP:', items);
    updateTotal();
  <?php endif; ?>

  // Inicializar visibilidad del campo de mesa según el modo seleccionado
  const modoConsumo = document.getElementById('modo_consumo').value;
  if (modoConsumo) {
    selectModoConsumo(modoConsumo);
  } else {
    // Por defecto, modo 'stay' está seleccionado
    selectModoConsumo('stay');
  }
  
});
</script>
