<?php
namespace App\Services;

use App\Config\Database;
use PDO;

class ReporteService {
    public function platosMasVendidos(int $limit = 10): array {
        $db = (new Database)->getConnection();
        $sql = "
          SELECT c.nombre, SUM(dp.cantidad) AS cantidad
          FROM detalle_pedido dp
          JOIN carta c ON dp.id_item = c.id_item
          GROUP BY dp.id_item
          ORDER BY cantidad DESC
          LIMIT ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recaudacionMensual(string $mes): array {
        $db = (new Database)->getConnection();
        $sql = "
          SELECT SUM(total) AS total
          FROM pedidos
          WHERE estado = 'pagado'
            AND DATE_FORMAT(fecha_hora, '%Y-%m') = ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$mes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function propinas(string $mes): array {
        $db = (new Database)->getConnection();
        $sql = "
          SELECT u.nombre, u.apellido, SUM(pr.monto) AS monto
          FROM propinas pr
          JOIN usuarios u ON pr.id_mozo = u.id_usuario
          WHERE DATE_FORMAT(pr.fecha_hora, '%Y-%m') = ?
          GROUP BY pr.id_mozo
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$mes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
