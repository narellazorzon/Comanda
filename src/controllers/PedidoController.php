<?php
// src/controllers/PedidoController.php
namespace App\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\CartaItem;

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
            $idPedido = Pedido::create($_POST);
            foreach ((array)($_POST['items'] ?? []) as $itemId) {
                DetallePedido::create($idPedido, (int)$itemId);
            }
            header('Location: cme_pedidos.php');
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
     * Administradores pueden borrar cualquiera;
     * comensales (QR) sólo sus mesas, mozos no borran.
     */
    public static function delete() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $rol    = $_SESSION['user']['rol'] ?? '';
        $id     = (int)($_GET['delete'] ?? 0);
        $pedido = Pedido::find($id);
        if (! $pedido) {
            header('Location: cme_pedidos.php');
            exit;
        }
        $mesaId = $_SESSION['mesa_id'] ?? null;
        if ($rol === 'administrador'
            || ($rol !== 'administrador' && $pedido['id_mesa'] === $mesaId)
        ) {
            Pedido::delete($id);
        } else {
            header('Location: unauthorized.php');
            exit;
        }
        header('Location: cme_pedidos.php');
        exit;
    }
}
