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
                        for ($i = 0; $i < (int)$item['cantidad']; $i++) {
                            DetallePedido::create($idPedido, (int)$item['id_item']);
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
}
