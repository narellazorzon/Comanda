<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class DetallePedido {
    public static function create(int $idPedido, int $idItem): bool {
        $db = (new Database)->getConnection();
        // Obtener precio unitario
        $precio = $db
            ->prepare("SELECT precio FROM carta WHERE id_item = ?")
            ->execute([$idItem]);
        $row = $db
            ->query("SELECT precio FROM carta WHERE id_item = $idItem")
            ->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            INSERT INTO detalle_pedido (id_pedido, id_item, cantidad, precio_unitario)
            VALUES (?,?,1,?)
        ");
        return $stmt->execute([
            $idPedido,
            $idItem,
            $row['precio']
        ]);
    }
}
