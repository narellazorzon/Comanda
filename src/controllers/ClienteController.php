<?php
// src/controllers/ClienteController.php
namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Propina;
use App\Models\Mesa;
use App\Models\DetallePedido;
use App\Models\CartaItem;
use Exception;

require_once __DIR__ . '/../config/helpers.php';

class ClienteController {
    
    /**
     * Muestra la vista principal del cliente (menú)
     */
    public static function index() {
        include __DIR__ . '/../views/cliente/index.php';
    }
    
    /**
     * Muestra la vista de proceso de pago con opción de propinas
     */
    public static function pago() {
        include __DIR__ . '/../views/cliente/pago.php';
    }
    
    /**
     * Procesa el pago del pedido incluyendo la propina
     */
    public static function procesarPago() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('cliente'));
            exit;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        try {
            $pedidoId = $_POST['pedido_id'] ?? null;
            $propinaMonto = floatval($_POST['propina_monto'] ?? 0);
            $mozoId = $_POST['mozo_id'] ?? null;
            $metodoPago = $_POST['metodo_pago'] ?? null;
            
            if (!$pedidoId) {
                throw new Exception('ID de pedido no válido');
            }
            
            // Obtener el pedido
            $pedido = Pedido::findWithDetails($pedidoId);
            if (!$pedido) {
                throw new Exception('Pedido no encontrado');
            }
            
            // Si el pedido no tiene mozo asignado, buscar el mozo de la mesa
            if (!$mozoId && $pedido['id_mesa']) {
                $mesa = Mesa::find($pedido['id_mesa']);
                if ($mesa) {
                    $mozoId = $mesa['id_mozo'];
                }
            }
            
            // Registrar la propina si hay monto
            if ($propinaMonto > 0 && $mozoId) {
                Propina::create([
                    'id_pedido' => $pedidoId,
                    'id_mozo' => $mozoId,
                    'monto' => $propinaMonto
                ]);
            }
            
            // Actualizar estado del pedido a "cuenta"
            Pedido::updateEstado($pedidoId, 'cuenta');
            
            // Guardar información en sesión para confirmación
            $_SESSION['ultimo_pago'] = [
                'pedido_id' => $pedidoId,
                'propina' => $propinaMonto,
                'metodo_pago' => $metodoPago,
                'total' => $pedido['total'] + $propinaMonto,
                'timestamp' => time()
            ];
            
            // Respuesta exitosa
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Pago procesado exitosamente',
                'pedido_id' => $pedidoId
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Muestra la confirmación del pago
     */
    public static function confirmacion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $pedidoId = $_GET['pedido'] ?? $_SESSION['ultimo_pago']['pedido_id'] ?? null;
        
        if (!$pedidoId) {
            header('Location: ' . url('cliente'));
            exit;
        }
        
        include __DIR__ . '/../views/cliente/confirmacion.php';
    }
    
    /**
     * Crea un nuevo pedido desde el carrito del cliente
     */
    public static function crearPedido() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('cliente'));
            exit;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        try {
            // Obtener datos del formulario
            $modoConsumo = $_POST['modo_consumo'] ?? 'stay';
            $numeroMesa = $_POST['numero_mesa'] ?? null;
            $nombreCliente = $_POST['nombre_completo'] ?? '';
            $emailCliente = $_POST['email'] ?? '';
            $items = json_decode($_POST['items'] ?? '[]', true);
            
            if (empty($items)) {
                throw new Exception('El carrito está vacío');
            }
            
            // Buscar mesa si es consumo en el local
            $idMesa = null;
            $idMozo = null;
            
            if ($modoConsumo === 'stay' && $numeroMesa) {
                $mesa = Mesa::findByNumero($numeroMesa);
                if ($mesa) {
                    $idMesa = $mesa['id_mesa'];
                    $idMozo = $mesa['id_mozo'];
                }
            }
            
            // Crear el pedido
            $pedidoData = [
                'id_mesa' => $idMesa,
                'modo_consumo' => $modoConsumo,
                'id_mozo' => $idMozo,
                'items' => $items
            ];
            
            $pedidoId = Pedido::create($pedidoData);
            
            if (!$pedidoId) {
                throw new Exception('Error al crear el pedido');
            }
            
            // Guardar información del cliente en sesión
            $_SESSION['cliente_info'] = [
                'nombre' => $nombreCliente,
                'email' => $emailCliente
            ];
            
            $_SESSION['ultimo_pedido_id'] = $pedidoId;
            
            // Enviar respuesta JSON con la URL de redirección
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'redirect_url' => url('cliente/pago', ['pedido' => $pedidoId]),
                'pedido_id' => $pedidoId
            ]);
            exit;
            
        } catch (Exception $e) {
            // Enviar error como JSON también
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Muestra el menú QR con mesa predefinida
     */
    public static function menuQR() {
        $mesa = $_GET['mesa'] ?? null;
        
        if ($mesa) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['mesa_qr'] = $mesa;
        }
        
        include __DIR__ . '/../views/cliente/index.php';
    }
}