<?php
// src/controllers/ClienteController.php
namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Propina;
use App\Models\Mesa;
use App\Models\DetallePedido;
use App\Models\CartaItem;
use App\Config\ClientSession;
use Exception;

require_once __DIR__ . '/../config/helpers.php';

class ClienteController {
    
    /**
     * Muestra la vista principal del cliente (menú)
     */
    public static function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Asegurar contexto de cliente
        if (!ClientSession::isClientContext()) {
            ClientSession::initClientSession();
        }
        include __DIR__ . '/../views/cliente/index.php';
    }
    
    /**
     * Muestra la vista de proceso de pago con opción de propinas
     */
    public static function pago() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Asegurar contexto de cliente
        if (!ClientSession::isClientContext()) {
            ClientSession::initClientSession();
        }
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

        header('Content-Type: application/json');
        
        try {
            $pedidoId = $_POST['pedido_id'] ?? null;
            $propinaMonto = floatval($_POST['propina_monto'] ?? 0);
            $mozoId = $_POST['mozo_id'] ?? null;
            $metodoPago = $_POST['metodo_pago'] ?? null;
            $mesa = $_POST['mesa'] ?? null;

            // Debug log
            error_log("Payment processing data: " . json_encode([
                'pedido_id' => $pedidoId,
                'propina_monto' => $propinaMonto,
                'mozo_id' => $mozoId,
                'metodo_pago' => $metodoPago,
                'mesa' => $mesa
            ]));
            
            if (!$pedidoId) {
                throw new Exception('ID de pedido no válido');
            }
            
            // Obtener el pedido
            $pedido = Pedido::find($pedidoId);
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

            // Asegurar contexto de cliente y limpiar cualquier sesión de admin
            if (isset($_SESSION['user'])) {
                unset($_SESSION['user']);
            }
            $_SESSION['contexto'] = 'cliente';
            
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

        // Asegurar contexto de cliente y limpiar cualquier sesión de admin
        if (!ClientSession::isClientContext()) {
            ClientSession::initClientSession();
        }
        // Eliminar cualquier sesión de admin que pueda existir
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
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
            // Obtener datos JSON del cuerpo de la petición
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                throw new Exception('Datos inválidos');
            }
            
            $modoConsumo = $data['modo_consumo'] ?? 'stay';
            $idMesa = $data['id_mesa'] ?? null;
            $nombreCliente = $data['cliente_nombre'] ?? '';
            $emailCliente = $data['cliente_email'] ?? '';
            $formaPago = $data['forma_pago'] ?? '';
            $observaciones = $data['observaciones'] ?? '';
            $items = $data['items'] ?? [];
            
            if (empty($items)) {
                throw new Exception('El carrito está vacío');
            }
            
            if (empty($nombreCliente)) {
                throw new Exception('El nombre del cliente es obligatorio');
            }
            
            if (empty($emailCliente)) {
                throw new Exception('El email del cliente es obligatorio');
            }
            
            // Buscar mozo si hay mesa asignada
            $idMozo = null;
            if ($idMesa) {
                $mesa = Mesa::find($idMesa);
                if ($mesa) {
                    $idMozo = $mesa['id_mozo'];
                }
            }
            
            // Crear el pedido
            $pedidoData = [
                'id_mesa' => $idMesa,
                'modo_consumo' => $modoConsumo,
                'id_mozo' => $idMozo,
                'cliente_nombre' => $nombreCliente,
                'cliente_email' => $emailCliente,
                'forma_pago' => $formaPago,
                'observaciones' => $observaciones,
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
            
            // Enviar respuesta JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => $pedidoId
            ]);
            exit;
            
        } catch (Exception $e) {
            // Enviar error como JSON
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Muestra el menú QR con mesa predefinida
     */
    public static function menuQR() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Asegurar contexto de cliente
        if (!ClientSession::isClientContext()) {
            ClientSession::initClientSession();
        }

        $mesa = $_GET['mesa'] ?? null;
        if ($mesa) {
            $_SESSION['mesa_qr'] = $mesa;
        }

        include __DIR__ . '/../views/cliente/index.php';
    }
}