<?php
namespace App\Database;

/**
 * Constructor de consultas SQL comunes
 * Centraliza queries repetitivas del proyecto
 */
class QueryBuilder {
    /**
     * Query base para pedidos con información de mesa y mozo
     */
    public static function pedidosWithMesaAndMozo(string $whereClause = '', string $orderBy = 'p.fecha_hora DESC'): string {
        $sql = "
            SELECT p.*,
                   m.numero as numero_mesa,
                   m.ubicacion as ubicacion_mesa,
                   m.estado as estado_mesa,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as nombre_mozo_completo
            FROM pedidos p
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON p.id_mozo = u.id_usuario
        ";

        if ($whereClause) {
            $sql .= " WHERE $whereClause";
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        return $sql;
    }

    /**
     * Query base para mesas con información del mozo
     */
    public static function mesasWithMozo(string $whereClause = '', string $orderBy = 'm.numero ASC'): string {
        $sql = "
            SELECT m.*,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo,
                   (SELECT COUNT(*) FROM pedidos p WHERE p.id_mesa = m.id_mesa AND p.estado NOT IN ('cerrado', 'cancelado')) as pedidos_activos
            FROM mesas m
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
        ";

        if ($whereClause) {
            $sql .= " WHERE $whereClause";
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        return $sql;
    }

    /**
     * Query para detalles de pedido con información del item
     */
    public static function detallesPedidoWithItem(string $whereClause = ''): string {
        $sql = "
            SELECT dp.*,
                   c.nombre as nombre_item,
                   c.descripcion as descripcion_item,
                   c.precio as precio_unitario,
                   c.categoria,
                   c.tiempo_preparacion,
                   (dp.cantidad * dp.precio) as subtotal
            FROM detalle_pedido dp
            JOIN carta c ON dp.id_item = c.id_item
        ";

        if ($whereClause) {
            $sql .= " WHERE $whereClause";
        }

        return $sql;
    }

    /**
     * Query para llamados de mesa con información completa
     */
    public static function llamadosWithMesaAndMozo(string $whereClause = '', string $orderBy = 'l.hora DESC'): string {
        $sql = "
            SELECT l.*,
                   m.numero as numero_mesa,
                   m.ubicacion as ubicacion_mesa,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
            FROM llamados_mesa l
            JOIN mesas m ON l.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
        ";

        if ($whereClause) {
            $sql .= " WHERE $whereClause";
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        return $sql;
    }

    /**
     * Query para propinas con información de pedido y mozo
     */
    public static function propinasWithPedidoAndMozo(string $whereClause = ''): string {
        $sql = "
            SELECT pr.*,
                   p.total as total_pedido,
                   p.id_mesa,
                   m.numero as numero_mesa,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
            FROM propinas pr
            JOIN pedidos p ON pr.id_pedido = p.id_pedido
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON pr.id_mozo = u.id_usuario
        ";

        if ($whereClause) {
            $sql .= " WHERE $whereClause";
        }

        return $sql;
    }

    /**
     * Query para ventas por categoría
     */
    public static function ventasPorCategoria(string $dateFrom = '', string $dateTo = ''): string {
        $sql = "
            SELECT c.categoria,
                   COUNT(DISTINCT dp.id_pedido) as cantidad_pedidos,
                   SUM(dp.cantidad) as cantidad_items,
                   SUM(dp.cantidad * dp.precio) as total_vendido,
                   AVG(dp.precio) as precio_promedio
            FROM detalle_pedido dp
            JOIN carta c ON dp.id_item = c.id_item
            JOIN pedidos p ON dp.id_pedido = p.id_pedido
        ";

        $where = ["p.estado IN ('servido', 'cuenta', 'cerrado')"];

        if ($dateFrom && $dateTo) {
            $where[] = "p.fecha_hora BETWEEN '$dateFrom' AND '$dateTo'";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " GROUP BY c.categoria ORDER BY total_vendido DESC";

        return $sql;
    }

    /**
     * Query para items más vendidos
     */
    public static function itemsMasVendidos(int $limit = 10, string $dateFrom = '', string $dateTo = ''): string {
        $sql = "
            SELECT c.nombre,
                   c.categoria,
                   c.precio,
                   SUM(dp.cantidad) as cantidad_vendida,
                   COUNT(DISTINCT dp.id_pedido) as veces_pedido,
                   SUM(dp.cantidad * dp.precio) as total_recaudado
            FROM detalle_pedido dp
            JOIN carta c ON dp.id_item = c.id_item
            JOIN pedidos p ON dp.id_pedido = p.id_pedido
        ";

        $where = ["p.estado IN ('servido', 'cuenta', 'cerrado')"];

        if ($dateFrom && $dateTo) {
            $where[] = "p.fecha_hora BETWEEN '$dateFrom' AND '$dateTo'";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " GROUP BY c.id_item ORDER BY cantidad_vendida DESC LIMIT $limit";

        return $sql;
    }

    /**
     * Query para rendimiento de mozos
     */
    public static function rendimientoMozos(string $dateFrom, string $dateTo, string $groupBy = ''): string {
        if ($groupBy === 'dia') {
            return self::rendimientoMozosPorDia($dateFrom, $dateTo);
        } elseif ($groupBy === 'mes') {
            return self::rendimientoMozosPorMes($dateFrom, $dateTo);
        }

        // Sin agrupación (ranking)
        return "
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
    }

    /**
     * Query para rendimiento de mozos por día
     */
    private static function rendimientoMozosPorDia(string $dateFrom, string $dateTo): string {
        return "
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
    }

    /**
     * Query para rendimiento de mozos por mes
     */
    private static function rendimientoMozosPorMes(string $dateFrom, string $dateTo): string {
        return "
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
}