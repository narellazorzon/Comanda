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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id_mesa = null;
        $modo_consumo = null;

        // Caso 1: QR de mesa física
        if (!empty($_GET['qr']) || !empty($_GET['mesa'])) {
            $valorMesa = (int) ($_GET['qr'] ?? $_GET['mesa']);

            // Intentar buscar por ID
            $mesa = \App\Models\Mesa::find($valorMesa);

            // Intentar buscar por número si no se encuentra
            if (!$mesa && method_exists('\App\Models\Mesa', 'findByNumero')) {
                $mesa = \App\Models\Mesa::findByNumero($valorMesa);
            }

            // Validar tipo de retorno y asignar correctamente
            if ($mesa) {
                if (is_array($mesa)) {
                    $id_mesa = $mesa['id_mesa'] ?? null;
                } elseif (is_object($mesa)) {
                    $id_mesa = $mesa->id_mesa ?? null;
                }

                if ($id_mesa !== null) {
                    $modo_consumo = 'stay';
                    error_log("[QR DETECTADO] Mesa ID: {$id_mesa}, modo_consumo=stay");
                } else {
                    error_log("[QR ERROR] Mesa detectada pero sin id válido (valor={$valorMesa})");
                }
            } else {
                error_log("[QR ERROR] Mesa no encontrada (valor={$valorMesa})");
            }
        }
        // Caso 2: QR de Take Away
        elseif (!empty($_GET['takeaway'])) {
            $id_mesa = 0;
            $modo_consumo = 'takeaway';
            error_log("[TAKEAWAY DETECTADO] modo_consumo=takeaway");
        }

        // Guardar contexto
        $_SESSION['mesa_qr'] = $id_mesa;
        $_SESSION['modo_consumo_qr'] = $modo_consumo;

        include __DIR__ . '/../views/cliente/index.php';
    }
    
    /**
     * Obtiene los platos más vendidos por categoría
     */
    public static function getPlatosMasVendidos() {
        try {
            $database = new \App\Config\Database();
            $db = $database->getConnection();
            
            // Consulta para obtener los platos más vendidos por categoría
            $sql = "
                SELECT 
                    c.id_item,
                    c.nombre,
                    c.categoria,
                    SUM(dp.cantidad) as total_vendido
                FROM carta c
                INNER JOIN detalle_pedido dp ON c.id_item = dp.id_item
                INNER JOIN pedidos p ON dp.id_pedido = p.id_pedido
                WHERE p.estado = 'cerrado'
                GROUP BY c.id_item, c.nombre, c.categoria
                ORDER BY c.categoria, total_vendido DESC
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Organizar por categoría y tomar el más vendido de cada una
            $masVendidosPorCategoria = [];
            foreach ($resultados as $item) {
                $categoria = $item['categoria'] ?? 'Otros';
                if (!isset($masVendidosPorCategoria[$categoria])) {
                    $masVendidosPorCategoria[$categoria] = $item['id_item'];
                }
            }
            
            return $masVendidosPorCategoria;
            
        } catch (Exception $e) {
            error_log("Error obteniendo platos más vendidos: " . $e->getMessage());
            return [];
        }
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

        header('Content-Type: application/json');
        
        try {
            // Aceptar tanto FormData (application/x-www-form-urlencoded | multipart/form-data)
            // como JSON (application/json) y claves camelCase o snake_case
            $inputData = [];
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $raw = file_get_contents('php://input');
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $inputData = $json;
                }
            }

            $pedidoId = $_POST['pedido_id']
                ?? ($inputData['pedido_id'] ?? ($inputData['pedidoId'] ?? null));
            $propinaMonto = floatval(
                $_POST['propina_monto']
                ?? ($inputData['propina_monto'] ?? ($inputData['propinaMonto'] ?? 0))
            );
            $mozoId = $_POST['mozo_id'] ?? ($inputData['mozo_id'] ?? ($inputData['mozoId'] ?? null));
            $metodoPago = $_POST['metodo_pago'] ?? ($inputData['metodo_pago'] ?? ($inputData['metodoPago'] ?? null));
            $mesa = $_POST['mesa'] ?? ($inputData['mesa'] ?? ($inputData['mesaNumero'] ?? null));

            // Normalizar forma de pago
            if ($metodoPago) {
                $metodoPago = strtolower(trim($metodoPago));
                $validos = ['efectivo','tarjeta','transferencia'];
                if (!in_array($metodoPago, $validos, true)) {
                    $metodoPago = null; // ignorar valores no válidos
                }
            }

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
            
            // Registrar la propina si hay monto (tolerante si tabla no existe)
            if ($propinaMonto > 0 && $mozoId) {
                try {
                    Propina::create([
                        'id_pedido' => $pedidoId,
                        'id_mozo' => $mozoId,
                        'monto' => $propinaMonto
                    ]);
                } catch (\Exception $pe) {
                    $msg = $pe->getMessage();
                    // Si la tabla de propinas no existe en el esquema, continuar sin fallar
                    if (
                        stripos($msg, 'Base table or view not found') !== false ||
                        stripos($msg, "42S02") !== false ||
                        stripos($msg, "propina") !== false && stripos($msg, "exist") !== false
                    ) {
                        error_log('[WARN] Propina no registrada: ' . $msg);
                    } else {
                        throw $pe; // otros errores de BD deben propagarse
                    }
                }
            }
            
            // Persistir forma de pago en el pedido si se informó
            if ($metodoPago) {
                \App\Models\Pedido::setFormaPago((int)$pedidoId, $metodoPago);
            }
            
            // No cambiamos el estado aquí.
            // El pedido se crea como 'pendiente' y el mozo lo moverá a 'en_preparacion' al confirmar el pago.
            
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
            // Obtener datos JSON del cuerpo de la petición
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                throw new Exception('Datos inválidos');
            }
            
            // Priorizar el contexto de QR si existe
            $modoConsumo = $_SESSION['modo_consumo_qr'] ?? ($data['modo_consumo'] ?? 'stay');
            $idMesa = $_SESSION['mesa_qr'] ?? ($data['id_mesa'] ?? null);
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
