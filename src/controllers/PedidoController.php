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
     * Muestra todos los pedidos tanto para administradores como para mozos.
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
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $rol = $_SESSION['user']['rol'] ?? '';
        if (! in_array($rol, ['administrador','mozo'], true)) {
            header('Location: login.php');
            exit;
        }
        $id    = (int)($_POST['id_pedido'] ?? 0);
        $nuevo = $_POST['estado'] ?? '';
        $map   = ['pendiente','en_preparacion','listo'];
        if ($id > 0 && in_array($nuevo, $map, true)) {
            Pedido::updateEstado($id, $nuevo);
        }
        header('Location: estado_pedidos.php');
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
}
