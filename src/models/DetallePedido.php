<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class DetallePedido
{
    /**
     * Inserta un detalle y devuelve true si la operacion tuvo exito.
     */
    public static function create(int $pedidoId, int $itemId, int $cantidad = 1, ?float $precioUnitario = null, string $detalle = ''): bool
    {
        $db = (new Database)->getConnection();

    /**
     * Crea un nuevo detalle de pedido con parámetros individuales.
     */
    public static function createSimple(int $idPedido, int $idItem, string $detalle = ''): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            INSERT INTO detalle_pedido (id_pedido, id_item, detalle)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$idPedido, $idItem, $detalle]);
    }

    /**
     * Obtiene todos los detalles de un pedido.
     */
    public static function allByPedido(int $pedidoId): array
    {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare(
            'SELECT dp.*, c.nombre, c.descripcion
             FROM detalle_pedido dp
             JOIN carta c ON dp.id_item = c.id_item
             WHERE dp.id_pedido = ?
             ORDER BY dp.id_detalle'
        );
        $stmt->execute([$pedidoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los detalles de un pedido con información completa para la vista de pago.
     */
    public static function getByPedido(int $idPedido): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT
                dp.*,
                c.nombre,
                c.descripcion,
                c.categoria,
                dp.cantidad,
                dp.precio_unitario,
                (dp.cantidad * dp.precio_unitario) as subtotal
            FROM detalle_pedido dp
            JOIN carta c ON dp.id_item = c.id_item
            WHERE dp.id_pedido = ?
            ORDER BY dp.id_detalle
        ");
        $stmt->execute([$idPedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
