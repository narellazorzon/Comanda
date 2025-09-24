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

        if ($precioUnitario === null) {
            $stmtPrecio = $db->prepare('SELECT precio FROM carta WHERE id_item = ?');
            $stmtPrecio->execute([$itemId]);
            $precioUnitario = (float) $stmtPrecio->fetchColumn();
        }

        if ($precioUnitario <= 0) {
            return false;
        }

        $stmt = $db->prepare(
            'INSERT INTO detalle_pedido (id_pedido, id_item, cantidad, precio_unitario, detalle) VALUES (?, ?, ?, ?, ?)'
        );

        return $stmt->execute([
            $pedidoId,
            $itemId,
            max(1, $cantidad),
            $precioUnitario,
            $detalle,
        ]);
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
     * Alias amigable.
     */
    public static function getByPedido(int $pedidoId): array
    {
        return self::allByPedido($pedidoId);
    }
}
