<?php
// src/controllers/PedidoController.php
namespace App\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\CartaItem;

// Incluir helpers después del namespace
require_once __DIR__ . '/../config/helpers.php';

class PedidoController {
    /**
     * Muestra todos los pedidos tanto para administradores como para el personal.
     */
    public static function index() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $rol = $_SESSION['user']['rol'] ?? '';
        if (! in_array($rol, ['administrador','mozo'], true)) {
            header('Location: login.php');
            exit;
        }

        // Carga todos los pedidos
        $pedidos = Pedido::all();
        include __DIR__ . '/../../public/estado_pedidos.php';
    }

    /**
     * Crea un nuevo pedido con los ítems seleccionados.
     */
    public static function create() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Preparar datos para el pedido
            $pedidoData = [
                'id_mesa' => $_POST['id_mesa'] ?? null,
                'modo_consumo' => $_POST['modo_consumo'] ?? 'stay'
            ];
            
            $idPedido = Pedido::create($pedidoData);
            
            // Crear detalles del pedido
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (isset($item['id_item']) && isset($item['cantidad'])) {
                        $detalle = $item['detalle'] ?? '';
                        for ($i = 0; $i < (int)$item['cantidad']; $i++) {
                            DetallePedido::create($idPedido, (int)$item['id_item'], $detalle);
                        }
                    }
                }
            }
            
            header('Location: ' . url('pedidos', ['success' => 'Pedido creado correctamente']));
            exit;
        }
        $carta = CartaItem::all();
        include __DIR__ . '/../../public/alta_pedido.php';
    }

    /**
     * Avanza el estado de un pedido (pendiente → en_preparacion → listo).
     */
    public static function updateEstado() {
        // Asegurar que no hay salida antes del JSON
        ob_clean();
        
        // Log para debug
        error_log('=== PEDIDO UPDATE ESTADO ===');
        error_log('Method: ' . $_SERVER['REQUEST_METHOD']);
        error_log('POST data: ' . print_r($_POST, true));
        
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            
            $rol = $_SESSION['user']['rol'] ?? '';
            error_log('User role: ' . $rol);
            
            if (! in_array($rol, ['administrador','mozo'], true)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            $id    = (int)($_POST['id_pedido'] ?? 0);
            $nuevo = $_POST['estado'] ?? '';
            $map   = ['pendiente','en_preparacion','servido','cuenta','cerrado'];
            
            error_log('ID: ' . $id . ', Estado: ' . $nuevo);
            
            if ($id > 0 && in_array($nuevo, $map, true)) {
                // Verificar que el pedido no esté cerrado
                $pedido = Pedido::find($id);
                error_log('Pedido encontrado: ' . print_r($pedido, true));
                
                if ($pedido && $pedido['estado'] === 'cerrado') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'No se puede cambiar el estado de un pedido cerrado']);
                    exit;
                }
                
                // Verificar si el estado actual es el mismo que el nuevo estado
                if ($pedido && $pedido['estado'] === $nuevo) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'El pedido ya tiene ese estado']);
                    exit;
                }
                
                $resultado = Pedido::updateEstado($id, $nuevo);
                error_log('Resultado update: ' . ($resultado ? 'true' : 'false'));
                
                if ($resultado) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Datos inválidos - ID: ' . $id . ', Estado: ' . $nuevo]);
            }
        } catch (Exception $e) {
            error_log('Error en updateEstado: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Elimina (cancela) un pedido.
     * Solo administradores pueden eliminar pedidos.
     */
    public static function delete() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $rol = $_SESSION['user']['rol'] ?? '';
        if ($rol !== 'administrador') {
            header('Location: ' . url('unauthorized'));
            exit;
        }
        
        $id = (int)($_GET['delete'] ?? 0);
        if ($id > 0) {
            // Verificar que el pedido no esté cerrado antes de eliminar
            $pedido = Pedido::find($id);
            if ($pedido && $pedido['estado'] === 'cerrado') {
                header('Location: ' . url('pedidos', ['error' => 'No se puede eliminar un pedido cerrado']));
                exit;
            }
            
            $resultado = Pedido::delete($id);
            if ($resultado) {
                header('Location: ' . url('pedidos', ['success' => 'Pedido eliminado correctamente']));
            } else {
                header('Location: ' . url('pedidos', ['error' => 'Error al eliminar el pedido']));
            }
        } else {
            header('Location: ' . url('pedidos', ['error' => 'ID de pedido inválido']));
        }
        exit;
    }

    /**
     * Crea un pedido desde la página del cliente (pública).
     * No requiere autenticación.
     */
    public static function createFromClient() {
        // Log para debug
        error_log('=== PEDIDO DESDE CLIENTE ===');
        error_log('Método: ' . $_SERVER['REQUEST_METHOD']);
        error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'No definido'));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }

        try {
            // Obtener datos del JSON
            $rawInput = file_get_contents('php://input');
            error_log('Datos recibidos: ' . $rawInput);
            
            $input = json_decode($rawInput, true);
            error_log('Datos decodificados: ' . print_r($input, true));
            
            if (!$input) {
                error_log('Error: Datos inválidos o JSON malformado');
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                return;
            }

            // Validar datos requeridos
            if (empty($input['cliente_nombre'])) {
                http_response_code(400);
                echo json_encode(['error' => 'El nombre del cliente es obligatorio']);
                return;
            }

            if (empty($input['items']) || !is_array($input['items'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Debe agregar al menos un ítem al pedido']);
                return;
            }

            // Preparar datos del pedido
            $data = [
                'modo_consumo' => $input['modo_consumo'] ?? 'stay',
                'forma_pago' => $input['forma_pago'] ?? null,
                'observaciones' => $input['observaciones'] ?? '',
                'cliente_nombre' => trim($input['cliente_nombre']),
                'cliente_email' => trim($input['cliente_email'] ?? ''),
                'items' => $input['items']
            ];

            // Solo incluir mesa si es modo 'stay'
            if ($data['modo_consumo'] === 'stay' && !empty($input['id_mesa'])) {
                $data['id_mesa'] = (int)$input['id_mesa'];
            }

            // Crear el pedido
            $pedidoId = Pedido::create($data);
            
            if ($pedidoId > 0) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Pedido creado correctamente',
                    'pedido_id' => $pedidoId
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear el pedido']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtiene la información completa de un pedido incluyendo sus detalles
     */
    public static function info() {
        // Forzar salida JSON desde el inicio
        header('Content-Type: application/json; charset=utf-8');
        
        // Asegurar que la sesión esté iniciada
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Verificar permisos
        if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para acceder a esta información. Por favor, inicie sesión nuevamente.']);
            exit;
        }

        try {
            $pedidoId = $_GET['id'] ?? null;
            
            if (!$pedidoId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID del pedido requerido']);
                exit;
            }

            // Obtener información del pedido
            $pedido = Pedido::find($pedidoId);
            
            if (!$pedido) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
                exit;
            }

            // Obtener detalles del pedido
            $detalles = Pedido::getDetalles($pedidoId);
            
            // Formatear los detalles para incluir información completa de cada item
            $items = [];
            foreach ($detalles as $detalle) {
                $items[] = [
                    'id_item' => $detalle['id_item'],
                    'nombre' => $detalle['item_nombre'] ?? $detalle['nombre'] ?? 'Item no encontrado',
                    'categoria' => $detalle['item_categoria'] ?? $detalle['categoria'] ?? 'Sin categoría',
                    'precio' => $detalle['precio_actual'] ?? $detalle['precio'] ?? $detalle['precio_unitario'] ?? 0,
                    'cantidad' => $detalle['cantidad'],
                    'detalle' => $detalle['detalle'] ?? ''
                ];
            }

            // Preparar respuesta
            $pedidoInfo = [
                'id_pedido' => $pedido['id_pedido'],
                'estado' => $pedido['estado'],
                'modo_consumo' => $pedido['modo_consumo'],
                'forma_pago' => $pedido['forma_pago'],
                'observaciones' => $pedido['observaciones'],
                'cliente_nombre' => $pedido['cliente_nombre'],
                'cliente_email' => $pedido['cliente_email'],
                'total' => $pedido['total'],
                'fecha_creacion' => $pedido['fecha_creacion'],
                'numero_mesa' => $pedido['numero_mesa'],
                'nombre_mozo_completo' => $pedido['nombre_mozo_completo'],
                'items' => $items
            ];

            echo json_encode([
                'success' => true,
                'pedido' => $pedidoInfo
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
        }
        exit;
    }
}
