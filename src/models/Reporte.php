<?php
// src/models/Reporte.php
namespace App\Models;

use App\Config\Database;
use PDO;

class Reporte {
    /**
     * Obtiene los platos más vendidos por período
     */
    public static function platosMasVendidos(string $periodo = 'mes', int $limite = 10, ?string $fechaDesde = null, ?string $fechaHasta = null): array {
        $db = (new Database)->getConnection();
        
        // Construir filtro de fecha según el período o fechas personalizadas
        $fecha_filtro = self::getFiltroFecha($periodo, $fechaDesde, $fechaHasta);
        
        $stmt = $db->prepare("
            SELECT 
                c.id_item,
                c.nombre,
                c.categoria,
                c.precio,
                SUM(dp.cantidad) as total_vendido,
                COUNT(DISTINCT p.id_pedido) as total_pedidos,
                SUM(dp.cantidad * dp.precio_unitario) as ingresos_totales
            FROM detalle_pedido dp
            JOIN carta c ON dp.id_item = c.id_item
            JOIN pedidos p ON dp.id_pedido = p.id_pedido
            WHERE p.estado IN ('servido', 'cerrado', 'cuenta')
            $fecha_filtro
            GROUP BY c.id_item, c.nombre, c.categoria, c.precio
            ORDER BY total_vendido DESC, ingresos_totales DESC
            LIMIT :limite
        ");
        
        // Preparar parámetros
        $params = ['limite' => $limite];
        
        // Si se usan fechas personalizadas, agregar los parámetros
        if ($fechaDesde && $fechaHasta && self::validarFormatoFecha($fechaDesde) && self::validarFormatoFecha($fechaHasta) && $fechaDesde <= $fechaHasta) {
            $params['fechaDesde'] = $fechaDesde;
            $params['fechaHasta'] = $fechaHasta;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene estadísticas generales del período
     */
    public static function estadisticasPeriodo(string $periodo = 'mes', ?string $fechaDesde = null, ?string $fechaHasta = null): array {
        $db = (new Database)->getConnection();
        
        $fecha_filtro = self::getFiltroFecha($periodo, $fechaDesde, $fechaHasta);
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT p.id_pedido) as total_pedidos,
                SUM(p.total) as ingresos_totales,
                AVG(p.total) as promedio_pedido,
                COUNT(DISTINCT p.id_mozo) as mozos_activos
            FROM pedidos p
            WHERE p.estado IN ('servido', 'cerrado', 'cuenta')
            $fecha_filtro
        ");
        
        // Preparar parámetros
        $params = [];
        
        // Si se usan fechas personalizadas, agregar los parámetros
        if ($fechaDesde && $fechaHasta && self::validarFormatoFecha($fechaDesde) && self::validarFormatoFecha($fechaHasta) && $fechaDesde <= $fechaHasta) {
            $params['fechaDesde'] = $fechaDesde;
            $params['fechaHasta'] = $fechaHasta;
        }
        
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Obtiene ventas por categoría
     */
    public static function ventasPorCategoria(string $periodo = 'mes', ?string $fechaDesde = null, ?string $fechaHasta = null): array {
        $db = (new Database)->getConnection();
        
        $fecha_filtro = self::getFiltroFecha($periodo, $fechaDesde, $fechaHasta);
        
        $stmt = $db->prepare("
            SELECT 
                c.categoria,
                COUNT(DISTINCT p.id_pedido) as total_pedidos,
                SUM(dp.cantidad) as total_vendido,
                SUM(dp.cantidad * dp.precio_unitario) as ingresos_totales
            FROM detalle_pedido dp
            JOIN carta c ON dp.id_item = c.id_item
            JOIN pedidos p ON dp.id_pedido = p.id_pedido
            WHERE p.estado IN ('servido', 'cerrado', 'cuenta')
            $fecha_filtro
            GROUP BY c.categoria
            ORDER BY ingresos_totales DESC
        ");
        
        // Preparar parámetros
        $params = [];
        
        // Si se usan fechas personalizadas, agregar los parámetros
        if ($fechaDesde && $fechaHasta && self::validarFormatoFecha($fechaDesde) && self::validarFormatoFecha($fechaHasta) && $fechaDesde <= $fechaHasta) {
            $params['fechaDesde'] = $fechaDesde;
            $params['fechaHasta'] = $fechaHasta;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene ventas por día del período
     */
    public static function ventasPorDia(string $periodo = 'mes', ?string $fechaDesde = null, ?string $fechaHasta = null): array {
        $db = (new Database)->getConnection();
        
        $fecha_filtro = self::getFiltroFecha($periodo, $fechaDesde, $fechaHasta);
        
        $stmt = $db->prepare("
            SELECT 
                DATE(p.fecha_hora) as fecha,
                COUNT(DISTINCT p.id_pedido) as total_pedidos,
                SUM(p.total) as ingresos_dia
            FROM pedidos p
            WHERE p.estado IN ('servido', 'cerrado', 'cuenta')
            $fecha_filtro
            GROUP BY DATE(p.fecha_hora)
            ORDER BY fecha DESC
        ");
        
        // Preparar parámetros
        $params = [];
        
        // Si se usan fechas personalizadas, agregar los parámetros
        if ($fechaDesde && $fechaHasta && self::validarFormatoFecha($fechaDesde) && self::validarFormatoFecha($fechaHasta) && $fechaDesde <= $fechaHasta) {
            $params['fechaDesde'] = $fechaDesde;
            $params['fechaHasta'] = $fechaHasta;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el rendimiento de los mozos
     */
    public static function rendimientoMozos(string $periodo = 'mes', ?string $fechaDesde = null, ?string $fechaHasta = null): array {
        $db = (new Database)->getConnection();

        $fecha_filtro = self::getFiltroFecha($periodo, $fechaDesde, $fechaHasta);

        $stmt = $db->prepare("
            SELECT
                u.nombre,
                u.apellido,
                COUNT(DISTINCT p.id_pedido) as total_pedidos,
                SUM(p.total) as ingresos_generados,
                AVG(p.total) as promedio_pedido
            FROM pedidos p
            JOIN usuarios u ON p.id_mozo = u.id_usuario
            WHERE p.estado IN ('servido', 'cerrado', 'cuenta')
            AND p.id_mozo IS NOT NULL
            $fecha_filtro
            GROUP BY u.id_usuario, u.nombre, u.apellido
            ORDER BY ingresos_generados DESC
        ");

        // Preparar parámetros
        $params = [];
        
        // Si se usan fechas personalizadas, agregar los parámetros
        if ($fechaDesde && $fechaHasta && self::validarFormatoFecha($fechaDesde) && self::validarFormatoFecha($fechaHasta) && $fechaDesde <= $fechaHasta) {
            $params['fechaDesde'] = $fechaDesde;
            $params['fechaHasta'] = $fechaHasta;
        }

        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las propinas por mozo en un período específico
     */
    public static function propinasPorMozo(string $mes = null): array {
        $db = (new Database)->getConnection();

        $sql = "
            SELECT
                u.id_usuario,
                u.nombre,
                u.apellido,
                SUM(pr.monto) as monto,
                COUNT(DISTINCT pr.id_pedido) as pedidos,
                AVG(pr.monto) as promedio
            FROM propinas pr
            JOIN usuarios u ON pr.id_mozo = u.id_usuario
            JOIN pedidos p ON pr.id_pedido = p.id_pedido
            WHERE pr.id_mozo IS NOT NULL
        ";

        $params = [];

        if ($mes) {
            $sql .= " AND DATE_FORMAT(pr.fecha_hora, '%Y-%m') = ?";
            $params[] = $mes;
        }

        $sql .= " GROUP BY u.id_usuario, u.nombre, u.apellido ORDER BY monto DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Construye el filtro de fecha SQL según el período o fechas personalizadas
     */
    private static function getFiltroFecha(string $periodo, ?string $fechaDesde = null, ?string $fechaHasta = null): string {
        // Si se proporcionan fechas personalizadas, validarlas y usarlas
        if ($fechaDesde && $fechaHasta) {
            // Validar formato de fecha (YYYY-MM-DD)
            if (self::validarFormatoFecha($fechaDesde) && self::validarFormatoFecha($fechaHasta)) {
                // Validar que fechaDesde <= fechaHasta
                if ($fechaDesde <= $fechaHasta) {
                    return "AND DATE(p.fecha_hora) BETWEEN :fechaDesde AND :fechaHasta";
                }
            }
            // Si las fechas no son válidas, usar período 'todos'
            $periodo = 'todos';
        }
        
        // Usar lógica de períodos existente
        switch ($periodo) {
            case 'semana':
                return "AND p.fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'mes':
                return "AND p.fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'año':
                return "AND p.fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            case 'todos':
                return ""; // Sin filtro de fecha para mostrar todos los datos
            default:
                return "AND p.fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        }
    }
    
    /**
     * Valida que una fecha tenga el formato YYYY-MM-DD
     */
    private static function validarFormatoFecha(string $fecha): bool {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
    
    /**
     * Obtiene el período actual en formato legible
     */
    public static function getPeriodoLegible(string $periodo): string {
        switch ($periodo) {
            case 'semana':
                return 'Última Semana';
            case 'mes':
                return 'Último Mes';
            case 'año':
                return 'Último Año';
            case 'todos':
                return 'Todos los Períodos';
            default:
                return 'Todos los Períodos';
        }
    }
    
    /**
     * Método de diagnóstico para verificar datos disponibles
     */
    public static function diagnosticar(): array {
        $db = (new Database)->getConnection();
        
        $diagnostico = [];
        
        // Contar pedidos totales
        $stmt = $db->query("SELECT COUNT(*) as total FROM pedidos");
        $diagnostico['pedidos_totales'] = $stmt->fetchColumn();
        
        // Contar pedidos por estado
        $stmt = $db->query("
            SELECT estado, COUNT(*) as cantidad 
            FROM pedidos 
            GROUP BY estado 
            ORDER BY cantidad DESC
        ");
        $diagnostico['pedidos_por_estado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Verificar si hay detalles de pedido
        $stmt = $db->query("SELECT COUNT(*) as total FROM detalle_pedido");
        $diagnostico['detalles_pedido'] = $stmt->fetchColumn();
        
        // Pedidos con datos útiles para reportes
        $stmt = $db->query("
            SELECT COUNT(*) as total 
            FROM pedidos 
            WHERE estado IN ('servido', 'cuenta', 'cerrado') 
            AND total > 0
        ");
        $diagnostico['pedidos_reporteables'] = $stmt->fetchColumn();
        
        return $diagnostico;
    }
}
