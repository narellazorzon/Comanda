<?php
namespace App\Controllers;

use App\Models\Pedido;

require_once __DIR__ . '/../config/helpers.php';

/**
 * Endpoints administrativos y AJAX para pedidos.
 */
class PedidoController
{
    /**
     * Cambia el estado de un pedido y devuelve JSON para la UI.
     */
    public static function updateEstado(): void
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');

        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $rol = $_SESSION['user']['rol'] ?? '';
            if (!in_array($rol, ['administrador', 'mozo'], true)) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }

            $pedidoId = (int) ($_POST['id_pedido'] ?? 0);
            $nuevoEstado = $_POST['estado'] ?? '';
            $estadosValidos = ['pendiente', 'en_preparacion', 'servido', 'cerrado'];

            if ($pedidoId <= 0 || !in_array($nuevoEstado, $estadosValidos, true)) {
                echo json_encode(['success' => false, 'message' => 'Datos invalidos']);
                exit;
            }

            $pedido = Pedido::find($pedidoId);
            if (!$pedido) {
                echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
                exit;
            }

            if ($pedido['estado'] === 'cerrado') {
                echo json_encode(['success' => false, 'message' => 'No se puede modificar un pedido cerrado']);
                exit;
            }

            if ($pedido['estado'] === $nuevoEstado) {
                echo json_encode(['success' => false, 'message' => 'El pedido ya tiene ese estado']);
                exit;
            }

            $resultado = Pedido::updateEstado($pedidoId, $nuevoEstado);
            echo json_encode([
                'success' => (bool) $resultado,
                'message' => $resultado ? 'Estado actualizado correctamente' : 'Error al actualizar el estado',
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Elimina (soft-delete) un pedido. Solo administradores.
     */
    public static function delete(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            header('Location: ' . url('unauthorized'));
            exit;
        }

        $pedidoId = (int) ($_GET['delete'] ?? $_GET['id'] ?? 0);
        if ($pedidoId <= 0) {
            header('Location: ' . url('pedidos', ['error' => 'ID de pedido invalido']));
            exit;
        }

        $pedido = Pedido::find($pedidoId);
        if ($pedido && $pedido['estado'] === 'cerrado') {
            header('Location: ' . url('pedidos', ['error' => 'No se puede eliminar un pedido cerrado']));
            exit;
        }

        $resultado = Pedido::delete($pedidoId);
        header('Location: ' . url('pedidos', [
            $resultado ? 'success' : 'error' => $resultado
                ? 'Pedido eliminado correctamente'
                : 'Error al eliminar el pedido'
        ]));
        exit;
    }

    /**
     * Devuelve informacion completa de un pedido (JSON).
     */
    public static function info(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permiso denegado']);
            exit;
        }

        $pedidoId = (int) ($_GET['id'] ?? 0);
        if ($pedidoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            exit;
        }

        $pedido = Pedido::find($pedidoId);
        if (!$pedido) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
            exit;
        }

        $detalles = Pedido::getDetalles($pedidoId);
        $items = [];
        foreach ($detalles as $detalle) {
            $items[] = [
                'id_item' => $detalle['id_item'],
                'nombre' => $detalle['item_nombre'] ?? $detalle['nombre'] ?? 'Item no disponible',
                'categoria' => $detalle['item_categoria'] ?? $detalle['categoria'] ?? 'Sin categoria',
                'precio' => $detalle['precio_actual'] ?? $detalle['precio'] ?? $detalle['precio_unitario'] ?? 0,
                'cantidad' => $detalle['cantidad'] ?? 1,
                'detalle' => $detalle['detalle'] ?? '',
            ];
        }

        echo json_encode([
            'success' => true,
            'pedido' => [
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
                'items' => $items,
            ],
        ]);
        exit;
    }
}
