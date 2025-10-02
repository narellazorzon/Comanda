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

// Cargar datos necesarios - solo mesas activas
$mesas = Mesa::getActive();
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
  width: 100%;
  margin: 0 auto;
  padding: 8px;
  background: #f8f9fa;
  min-height: auto;
  box-sizing: border-box;
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
  margin-bottom: 16px;
  position: relative;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  color: #2F1B14;
  font-size: 0.95rem;
  letter-spacing: 0.3px;
  transition: color 0.2s ease;
}

.form-group select,
.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #E0E0E0;
  border-radius: 8px;
  background: #FAFAFA;
  font-size: 0.95rem;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  color: #333;
  box-sizing: border-box;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.form-group select:hover,
.form-group input:hover,
.form-group textarea:hover {
  border-color: #CD853F;
  background: #FFFFFF;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #DEB887;
  background: #FFFFFF;
  box-shadow: 0 0 0 3px rgba(222, 184, 135, 0.2), 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-1px);
}

.form-group select:valid,
.form-group input:valid,
.form-group textarea:valid {
  border-color: #4CAF50;
}

.form-group select:invalid:not(:placeholder-shown),
.form-group input:invalid:not(:placeholder-shown),
.form-group textarea:invalid:not(:placeholder-shown) {
  border-color: #F44336;
}

/* Estilos específicos para diferentes tipos de campos */
.form-group input[type="text"],
.form-group input[type="email"] {
  background-image: linear-gradient(45deg, transparent 50%, #CD853F 50%);
  background-position: right 12px center;
  background-repeat: no-repeat;
  background-size: 0;
  transition: background-size 0.2s ease;
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus {
  background-size: 4px 4px;
}

.form-group select {
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23CD853F' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 16px;
  padding-right: 40px;
  appearance: none;
  cursor: pointer;
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
  font-family: Arial, sans-serif;
  line-height: 1.5;
}

/* Placeholder styling */
.form-group input::placeholder,
.form-group textarea::placeholder {
  color: #999;
  font-style: italic;
  transition: color 0.2s ease;
}

.form-group input:focus::placeholder,
.form-group textarea:focus::placeholder {
  color: #BBB;
}

/* Estilo específico para el campo de observaciones con Arial */
#observaciones {
  font-family: Arial, sans-serif;
  font-size: 0.9rem;
  line-height: 1.4;
}

/* Estilos específicos para el desplegable de mesas */
#id_mesa {
  width: 100% !important;
  max-width: 100%;
  background: linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%);
  border: 2px solid #E0E0E0;
  border-radius: 10px;
  padding: 14px 50px 14px 16px;
  font-size: 1rem;
  font-weight: 500;
  color: #2F1B14;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  position: relative;
  box-sizing: border-box;
  display: block;
}

#id_mesa:hover {
  border-color: #CD853F;
  background: #FFFFFF;
  box-shadow: 0 6px 20px rgba(0,0,0,0.12);
  transform: translateY(-1px);
}

#id_mesa:focus {
  outline: none;
  border-color: #DEB887;
  background: #FFFFFF;
  box-shadow: 0 0 0 4px rgba(222, 184, 135, 0.2), 0 8px 25px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}

#id_mesa option {
  padding: 12px 16px;
  font-size: 0.95rem;
  color: #333;
  background: #FFFFFF;
  border: none;
  transition: background-color 0.2s ease;
}

#id_mesa option:hover {
  background: #F8F9FA;
}

#id_mesa option:checked {
  background: #DEB887;
  color: #2F1B14;
  font-weight: 600;
}

/* Información del mozo mejorada */
#mozo-info {
  margin-top: 12px;
  padding: 16px;
  background: linear-gradient(135deg, #E8F5E8 0%, #F0F8F0 100%);
  border: 2px solid #4CAF50;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.15);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

#mozo-info::before {
  content: '👨‍💼';
  position: absolute;
  top: 12px;
  right: 16px;
  font-size: 1.5rem;
  opacity: 0.7;
}

#mozo-info h4 {
  margin: 0 0 8px 0;
  color: #2E7D32;
  font-size: 1.1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

#mozo-info p {
  margin: 0;
  color: #388E3C;
  font-size: 0.95rem;
  font-weight: 500;
}

#mozo-info .mozo-status {
  display: inline-block;
  padding: 4px 12px;
  background: #4CAF50;
  color: white;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-top: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Asegurar ancho uniforme para todos los campos del formulario */
#cliente_nombre,
#cliente_email,
#forma_pago,
#observaciones,
#id_mesa {
  width: 100% !important;
  max-width: 100%;
  box-sizing: border-box;
  display: block;
}

/* Campo de búsqueda en el modal */
#item-search {
  width: 100% !important;
  max-width: 100%;
  box-sizing: border-box;
  display: block;
}

/* Campos de detalle de items */
.item-detail-input {
  width: 100% !important;
  max-width: 100%;
  box-sizing: border-box;
  display: block;
}

/* Asegurar que todos los form-groups tengan el mismo ancho */
.form-group {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

/* Asegurar ancho uniforme para todas las secciones del formulario */
.form-section,
.items-section {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

/* Asegurar que todos los elementos de entrada tengan el mismo ancho base */
input[type="text"],
input[type="email"],
input[type="number"],
select,
textarea {
  width: 100% !important;
  max-width: 100%;
  box-sizing: border-box;
  display: block;
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
  background:rgb(68, 40, 12);
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
  background:rgb(84, 55, 42);
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
        <label for="id_mesa">🍽️ Seleccionar Mesa *</label>
        <select name="id_mesa" id="id_mesa" required onchange="showMozoInfo()">
          <option value="">Elige una mesa disponible...</option>
          <?php foreach ($mesas as $mesa): ?>
            <option value="<?= $mesa['id_mesa'] ?>" 
                    data-mozo="<?= htmlspecialchars($mesa['mozo_nombre_completo'] ?? 'Sin asignar') ?>"
                    data-estado="<?= $mesa['estado'] ?>"
                    data-ubicacion="<?= htmlspecialchars($mesa['ubicacion']) ?>"
                    <?= (isset($_POST['id_mesa']) && $_POST['id_mesa'] == $mesa['id_mesa']) || ($pedido && $pedido['id_mesa'] == $mesa['id_mesa']) ? 'selected' : '' ?>>
              🪑 Mesa #<?= $mesa['numero'] ?> - <?= $mesa['ubicacion'] ?> 
              <?php if ($mesa['estado'] === 'libre'): ?>
                ✅ Libre
              <?php elseif ($mesa['estado'] === 'ocupada'): ?>
                🟡 Ocupada
              <?php else: ?>
                ⚪ <?= ucfirst($mesa['estado']) ?>
              <?php endif; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div id="mozo-info" style="display: none;">
          <h4> Mozo Asignado</h4>
          <p id="mozo-nombre"></p>
          <span class="mozo-status">Asignado</span>
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
      <h3>Total del Pedido</h3>
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
    const estado = selectedOption.getAttribute('data-estado');
    const ubicacion = selectedOption.getAttribute('data-ubicacion');
    
    // Crear contenido más rico
    mozoNombre.innerHTML = `
      <strong>${mozo}</strong><br>
      <small>📍 ${ubicacion}</small>
    `;
    
    // Agregar animación de entrada
    mozoInfo.style.display = 'block';
    mozoInfo.style.opacity = '0';
    mozoInfo.style.transform = 'translateY(-10px)';
    
    setTimeout(() => {
      mozoInfo.style.transition = 'all 0.3s ease';
      mozoInfo.style.opacity = '1';
      mozoInfo.style.transform = 'translateY(0)';
    }, 50);
    
  } else {
    mozoInfo.style.display = 'none';
  }
}

function addItem() {
  // Crear modal de selección de item con diseño moderno
  const modal = document.createElement('div');
  modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.5) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease-out;
  `;
  
  const modalContent = document.createElement('div');
  modalContent.style.cssText = `
    background: linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%);
    border-radius: 20px;
    padding: 0;
    max-width: 600px;
    width: 95%;
    max-height: 85vh;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.2);
    animation: slideInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
  `;
  
  modalContent.innerHTML = `
    <div style="background: linear-gradient(135deg,rgb(103, 81, 59) 0%,rgb(83, 52, 31)100%); padding: 20px; color: white; position: relative; overflow: hidden;">
      <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
      <div style="position: absolute; bottom: -30px; left: -30px; width: 60px; height: 60px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
      <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
        <div>
          <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 10px;">
            🍽️ Seleccionar Item
          </h3>
          <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 0.9rem;">Elige los productos para tu pedido</p>
        </div>
        <button onclick="this.closest('.modal').remove()" style="
          background: rgba(255,255,255,0.2);
          color: white;
          border: 2px solid rgba(255,255,255,0.3);
          width: 40px;
          height: 40px;
          border-radius: 50%;
          cursor: pointer;
          font-size: 1.3rem;
          font-weight: bold;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.3s ease;
          backdrop-filter: blur(10px);
        " onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='scale(1)'">×</button>
      </div>
    </div>
    
    <div style="padding: 25px;">
      <div style="position: relative; margin-bottom: 25px;">
        <input type="text" id="item-search" placeholder="🔍 Buscar por nombre o categoría..." style="
          width: 100%;
          padding: 15px 20px 15px 50px;
          border: 2px solid #E0E0E0;
          border-radius: 15px;
          font-size: 1rem;
          background: #FAFAFA;
          transition: all 0.3s ease;
          box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        " onfocus="this.style.borderColor='#CD853F'; this.style.background='white'; this.style.boxShadow='0 8px 25px rgba(205, 133, 63, 0.15)'" onblur="this.style.borderColor='#E0E0E0'; this.style.background='#FAFAFA'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.05)'">
        <div style="position: absolute; left: 18px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; color: #999;">🔍</div>
      </div>
      
      <div id="items-list" style="max-height: 450px; overflow-y: auto; padding-right: 5px;">
        ${generateItemsList()}
      </div>
    </div>
    
    <style>
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      @keyframes slideInUp {
        from { 
          opacity: 0;
          transform: translateY(50px) scale(0.9);
        }
        to { 
          opacity: 1;
          transform: translateY(0) scale(1);
        }
      }
      #items-list::-webkit-scrollbar {
        width: 6px;
      }
      #items-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
      }
      #items-list::-webkit-scrollbar-thumb {
        background:rgb(113, 88, 64);
        border-radius: 3px;
      }
      #items-list::-webkit-scrollbar-thumb:hover {
        background:rgb(86, 69, 51);
      }
    </style>
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
  
  // Cerrar modal al hacer clic fuera
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      modal.remove();
    }
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
    html += `<div style="margin-bottom: 25px;">
      <div style="
        background: linear-gradient(135deg,rgb(140, 106, 72) 0%,rgb(103, 83, 52) 100%);
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        margin-bottom: 15px;
        position: relative;
        overflow: hidden;
      ">
        <div style="position: absolute; top: -10px; right: -10px; width: 30px; height: 30px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
        <h4 style="margin: 0; font-size: 1rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; position: relative; z-index: 1;">${categoria}</h4>
      </div>`;
    
    groupedItems[categoria].forEach(item => {
      const precioConDescuento = calcularPrecioConDescuento(item.precio, item.descuento);
      const precioHtml = item.descuento > 0 ? 
        `<div style="text-align: right;">
           <div style="font-weight: 700; color: #e74c3c; font-size: 1.2rem; margin-bottom: 2px;">$${precioConDescuento.toFixed(2)}</div>
           <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
             <span style="text-decoration: line-through; color: #999; font-size: 0.85rem;">$${item.precio.toFixed(2)}</span>
             <span style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">-${item.descuento}%</span>
           </div>
         </div>` :
        `<div style="text-align: right;">
           <div style="font-weight: 700; color: #28a745; font-size: 1.2rem;">$${item.precio.toFixed(2)}</div>
         </div>`;
      
      html += `
        <div onclick="selectItem(${item.id})" class="item-option" style="
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 18px 20px;
          margin-bottom: 12px;
          background: linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%);
          border: 2px solid #E0E0E0;
          border-radius: 15px;
          cursor: pointer;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          min-height: 70px;
          box-shadow: 0 4px 12px rgba(0,0,0,0.05);
          position: relative;
          overflow: hidden;
        " onmouseover="
          this.style.borderColor='#CD853F';
          this.style.backgroundColor='#FFFFFF';
          this.style.transform='translateY(-2px)';
          this.style.boxShadow='0 8px 25px rgba(205, 133, 63, 0.15)';
        " onmouseout="
          this.style.borderColor='#E0E0E0';
          this.style.backgroundColor='linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%)';
          this.style.transform='translateY(0)';
          this.style.boxShadow='0 4px 12px rgba(0,0,0,0.05)';
        ">
          <div style="flex: 1; padding-right: 15px;">
            <div style="font-weight: 700; color: #2F1B14; margin-bottom: 6px; font-size: 1.1rem; line-height: 1.3;">${item.nombre}</div>
            ${item.descripcion ? `<div style="color: #666; font-size: 0.9rem; line-height: 1.4; margin-top: 4px;">${item.descripcion}</div>` : ''}
          </div>
          <div style="flex-shrink: 0;">
            ${precioHtml}
          </div>
          <div style="
            position: absolute;
            top: 15px;
            right: 15px;
            width: 8px;
            height: 8px;
            background: #CD853F;
            border-radius: 50%;
            opacity: 0;
            transition: all 0.3s ease;
          "></div>
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
