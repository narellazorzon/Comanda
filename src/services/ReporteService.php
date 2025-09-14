<?php
namespace App\Services;

require_once __DIR__ . '/../config/database.php';

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
    
    /**
     * Obtiene el rendimiento de mozos con KPIs monetarios y de pedidos
     */
    public function getRendimientoMozos(string $desde, string $hasta, string $agrupar = 'ninguno'): array {
        $db = (new Database)->getConnection();
        
        // Validar fechas
        $desde = date('Y-m-d', strtotime($desde));
        $hasta = date('Y-m-d 23:59:59', strtotime($hasta));
        
        if ($agrupar === 'ninguno') {
            // Consulta principal con todos los KPIs - incluir todos los mozos activos
            $sql = "
                SELECT 
                    u.id_usuario AS mozo_id,
                    CONCAT(u.nombre, ' ', u.apellido) AS mozo,
                    COUNT(DISTINCT pe.id_pedido) AS pedidos,
                    COALESCE(SUM(pr.monto), 0) AS propina_total,
                    COALESCE(SUM(pe.total), 0) AS total_vendido,
                    ROUND(COALESCE(SUM(pr.monto), 0) / NULLIF(COUNT(DISTINCT pe.id_pedido), 0), 2) AS propina_promedio_por_pedido,
                    ROUND(COALESCE(SUM(pr.monto), 0) / NULLIF(COALESCE(SUM(pe.total), 0), 0), 4) AS tasa_propina
                FROM usuarios u
                LEFT JOIN pedidos pe ON u.id_usuario = pe.id_mozo 
                    AND pe.fecha_hora BETWEEN :desde AND :hasta
                    AND pe.estado IN ('servido', 'cuenta', 'cerrado')
                LEFT JOIN propinas pr ON pr.id_pedido = pe.id_pedido
                WHERE u.rol = 'mozo' AND u.estado = 'activo'
                GROUP BY u.id_usuario, mozo
                ORDER BY tasa_propina DESC, propina_total DESC
            ";
        } elseif ($agrupar === 'dia') {
            $sql = "
                SELECT 
                    u.id_usuario AS mozo_id,
                    CONCAT(u.nombre, ' ', u.apellido) AS mozo,
                    DATE(pe.fecha_hora) AS periodo,
                    COUNT(DISTINCT pe.id_pedido) AS pedidos,
                    COALESCE(SUM(pr.monto), 0) AS propina_total,
                    COALESCE(SUM(pe.total), 0) AS total_vendido
                FROM usuarios u
                LEFT JOIN pedidos pe ON u.id_usuario = pe.id_mozo 
                    AND pe.fecha_hora BETWEEN :desde AND :hasta
                    AND pe.estado IN ('servido', 'cuenta', 'cerrado')
                LEFT JOIN propinas pr ON pr.id_pedido = pe.id_pedido
                WHERE u.rol = 'mozo' AND u.estado = 'activo'
                  AND pe.id_pedido IS NOT NULL
                GROUP BY u.id_usuario, mozo, periodo
                ORDER BY periodo, mozo
            ";
        } else { // mes
            $sql = "
                SELECT 
                    u.id_usuario AS mozo_id,
                    CONCAT(u.nombre, ' ', u.apellido) AS mozo,
                    DATE_FORMAT(pe.fecha_hora, '%Y-%m-01') AS periodo,
                    COUNT(DISTINCT pe.id_pedido) AS pedidos,
                    COALESCE(SUM(pr.monto), 0) AS propina_total,
                    COALESCE(SUM(pe.total), 0) AS total_vendido
                FROM usuarios u
                LEFT JOIN pedidos pe ON u.id_usuario = pe.id_mozo 
                    AND pe.fecha_hora BETWEEN :desde AND :hasta
                    AND pe.estado IN ('servido', 'cuenta', 'cerrado')
                LEFT JOIN propinas pr ON pr.id_pedido = pe.id_pedido
                WHERE u.rol = 'mozo' AND u.estado = 'activo'
                  AND pe.id_pedido IS NOT NULL
                GROUP BY u.id_usuario, mozo, periodo
                ORDER BY periodo, mozo
            ";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':desde' => $desde,
            ':hasta' => $hasta
        ]);
        
        $kpis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si es agrupación ninguna, agregar ranking
        if ($agrupar === 'ninguno') {
            $ranking = 1;
            foreach ($kpis as &$kpi) {
                $kpi['ranking'] = $ranking++;
                // Convertir a float para operaciones
                $kpi['propina_total'] = floatval($kpi['propina_total']);
                $kpi['total_vendido'] = floatval($kpi['total_vendido']);
                $kpi['propina_promedio_por_pedido'] = floatval($kpi['propina_promedio_por_pedido']);
                $kpi['tasa_propina'] = floatval($kpi['tasa_propina']);
                $kpi['pedidos'] = intval($kpi['pedidos']);
            }
        }
        
        return [
            'params' => [
                'desde' => $desde,
                'hasta' => substr($hasta, 0, 10), // Quitar hora para display
                'agrupar' => $agrupar
            ],
            'kpis' => $kpis
        ];
    }
}
