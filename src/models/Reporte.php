<?php
// src/models/Reporte.php
namespace App\Models;

use App\Config\Database;
use PDO;

class Reporte {
    /**
     * Obtiene los platos más vendidos por período
     */
    public static function platosMasVendidos(string $periodo = 'mes', int $limite = 10): array {
        $db = (new Database)->getConnection();
        
        // Construir filtro de fecha según el período
        $fecha_filtro = self::getFiltroFecha($periodo);
        
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
            WHERE p.estado IN ('pagado', 'listo')
            $fecha_filtro
            GROUP BY c.id_item, c.nombre, c.categoria, c.precio
            ORDER BY total_vendido DESC, ingresos_totales DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene estadísticas generales del período
     */
    public static function estadisticasPeriodo(string $periodo = 'mes'): array {
        $db = (new Database)->getConnection();
        
        $fecha_filtro = self::getFiltroFecha($periodo);
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT p.id_pedido) as total_pedidos,
                SUM(p.total) as ingresos_totales,
                AVG(p.total) as promedio_pedido,
                COUNT(DISTINCT p.id_mozo) as mozos_activos
            FROM pedidos p
            WHERE p.estado IN ('pagado', 'listo')
            $fecha_filtro
        ");
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Obtiene ventas por categoría
     */
    public static function ventasPorCategoria(string $periodo = 'mes'): array {
        $db = (new Database)->getConnection();
        
        $fecha_filtro = self::getFiltroFecha($periodo);
        
        $stmt = $db->prepare("
            SELECT 
                c.categoria,
                COUNT(DISTINCT p.id_pedido) as total_pedidos,
                SUM(dp.cantidad) as total_vendido,
                SUM(dp.cantidad * dp.precio_unitario) as ingresos_totales
            FROM detalle_pedido dp
            JOIN carta c ON dp.id_item = c.id_item
            JOIN pedidos p ON dp.id_pedido = p.id_pedido
            WHERE p.estado IN ('pagado', 'listo')
            $fecha_filtro
            GROUP BY c.categoria
            ORDER BY ingresos_totales DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene ventas por día del período
     */
    public static function ventasPorDia(string $periodo = 'mes'): array {
        $db = (new Database)->getConnection();
        
        $fecha_filtro = self::getFiltroFecha($periodo);
        
        $stmt = $db->prepare("
            SELECT 
                DATE(p.fecha_hora) as fecha,
                COUNT(DISTINCT p.id_pedido) as total_pedidos,
                SUM(p.total) as ingresos_dia
            FROM pedidos p
            WHERE p.estado IN ('pagado', 'listo')
            $fecha_filtro
            GROUP BY DATE(p.fecha_hora)
            ORDER BY fecha DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el rendimiento de los mozos
     */
    public static function rendimientoMozos(string $periodo = 'mes'): array {
        $db = (new Database)->getConnection();
        
        $fecha_filtro = self::getFiltroFecha($periodo);
        
        $stmt = $db->prepare("
            SELECT 
                u.nombre,
                u.apellido,
                COUNT(DISTINCT p.id_pedido) as total_pedidos,
                SUM(p.total) as ingresos_generados,
                AVG(p.total) as promedio_pedido
            FROM pedidos p
            JOIN usuarios u ON p.id_mozo = u.id_usuario
            WHERE p.estado IN ('pagado', 'listo')
            AND p.id_mozo IS NOT NULL
            $fecha_filtro
            GROUP BY u.id_usuario, u.nombre, u.apellido
            ORDER BY ingresos_generados DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Construye el filtro de fecha SQL según el período
     */
    private static function getFiltroFecha(string $periodo): string {
        switch ($periodo) {
            case 'semana':
                return "AND p.fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'mes':
                return "AND p.fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'año':
                return "AND p.fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "AND p.fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        }
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
            default:
                return 'Último Mes';
        }
    }
}
