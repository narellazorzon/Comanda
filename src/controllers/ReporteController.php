<?php
// src/controllers/ReporteController.php
namespace App\Controllers;

use App\Config\Database;

class ReporteController {

    /**
     * Muestra el reporte de rendimiento de mozos
     */
    public static function rendimientoMozos() {
        // Obtener parámetros del formulario
        $desde = self::convertirFecha($_GET['desde'] ?? date('Y-m-01'));
        $hasta = self::convertirFecha($_GET['hasta'] ?? date('Y-m-d'));
        $agrupar = $_GET['agrupar'] ?? 'ninguno';

        // Validar fechas
        if ($desde > $hasta) {
            $temp = $desde;
            $desde = $hasta;
            $hasta = $temp;
        }

        $kpis = [];

        if ($agrupar === 'ninguno') {
            // Vista de ranking - obtener datos directamente con propinas
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("
                SELECT
                    u.nombre,
                    u.apellido,
                    COUNT(DISTINCT p.id_pedido) as pedidos,
                    SUM(p.total) as total_vendido,
                    COALESCE(SUM(pr.monto), 0) as propina_total
                FROM pedidos p
                JOIN usuarios u ON p.id_mozo = u.id_usuario
                LEFT JOIN propinas pr ON p.id_pedido = pr.id_pedido
                WHERE p.estado IN ('servido', 'cerrado')
                AND p.fecha_hora BETWEEN ? AND ?
                GROUP BY u.id_usuario, u.nombre, u.apellido
                ORDER BY total_vendido DESC
            ");
            $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);

            $ranking = 1;
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $kpis[] = [
                    'mozo' => $row['nombre'] . ' ' . $row['apellido'],
                    'pedidos' => $row['pedidos'],
                    'total_vendido' => $row['total_vendido'],
                    'propina_total' => $row['propina_total'],
                    'propina_promedio_por_pedido' => $row['pedidos'] > 0 ? $row['propina_total'] / $row['pedidos'] : 0,
                    'tasa_propina' => $row['total_vendido'] > 0 ? $row['propina_total'] / $row['total_vendido'] : 0,
                    'ranking' => $ranking++
                ];
            }
        } else {
            // Vista agrupada - implementar lógica para agrupar por día o mes
            $kpis = self::obtenerDatosAgrupados($desde, $hasta, $agrupar);
        }

        // Preparar resultado para la vista
        $resultado = [
            'params' => [
                'desde' => $desde,
                'hasta' => $hasta,
                'agrupar' => $agrupar
            ],
            'kpis' => $kpis
        ];

        // Pasar los datos a la vista
        return $resultado;
    }

    /**
     * Determina el período según las fechas seleccionadas
     */
    private static function determinarPeriodo($desde, $hasta) {
        $dias = (strtotime($hasta) - strtotime($desde)) / (60 * 60 * 24);

        if ($dias <= 7) {
            return 'semana';
        } elseif ($dias <= 31) {
            return 'mes';
        } else {
            return 'año';
        }
    }

    /**
     * Obtiene datos agrupados por día o mes
     */
    private static function obtenerDatosAgrupados($desde, $hasta, $agrupar) {
        $db = (new Database())->getConnection();
        $kpis = [];

        if ($agrupar === 'dia') {
            $stmt = $db->prepare("
                SELECT
                    u.nombre,
                    u.apellido,
                    DATE(p.fecha_hora) as periodo,
                    COUNT(DISTINCT p.id_pedido) as pedidos,
                    SUM(p.total) as total_vendido,
                    COALESCE(SUM(pr.monto), 0) as propina_total
                FROM pedidos p
                JOIN usuarios u ON p.id_mozo = u.id_usuario
                LEFT JOIN propinas pr ON p.id_pedido = pr.id_pedido
                WHERE p.estado IN ('servido', 'cerrado')
                AND p.fecha_hora BETWEEN ? AND ?
                GROUP BY u.id_usuario, u.nombre, u.apellido, DATE(p.fecha_hora)
                ORDER BY u.nombre, u.apellido, periodo
            ");
            $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $kpis[] = [
                    'mozo' => $row['nombre'] . ' ' . $row['apellido'],
                    'periodo' => $row['periodo'],
                    'pedidos' => $row['pedidos'],
                    'total_vendido' => $row['total_vendido'],
                    'propina_total' => $row['propina_total'],
                    'propina_promedio_por_pedido' => $row['pedidos'] > 0 ? $row['propina_total'] / $row['pedidos'] : 0,
                    'tasa_propina' => $row['total_vendido'] > 0 ? $row['propina_total'] / $row['total_vendido'] : 0,
                    'ranking' => 0
                ];
            }
        } elseif ($agrupar === 'mes') {
            $stmt = $db->prepare("
                SELECT
                    u.nombre,
                    u.apellido,
                    DATE_FORMAT(p.fecha_hora, '%Y-%m-01') as periodo,
                    COUNT(DISTINCT p.id_pedido) as pedidos,
                    SUM(p.total) as total_vendido,
                    COALESCE(SUM(pr.monto), 0) as propina_total
                FROM pedidos p
                JOIN usuarios u ON p.id_mozo = u.id_usuario
                LEFT JOIN propinas pr ON p.id_pedido = pr.id_pedido
                WHERE p.estado IN ('servido', 'cerrado')
                AND p.fecha_hora BETWEEN ? AND ?
                GROUP BY u.id_usuario, u.nombre, u.apellido, DATE_FORMAT(p.fecha_hora, '%Y-%m-01')
                ORDER BY u.nombre, u.apellido, periodo
            ");
            $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $kpis[] = [
                    'mozo' => $row['nombre'] . ' ' . $row['apellido'],
                    'periodo' => $row['periodo'],
                    'pedidos' => $row['pedidos'],
                    'total_vendido' => $row['total_vendido'],
                    'propina_total' => $row['propina_total'],
                    'propina_promedio_por_pedido' => $row['pedidos'] > 0 ? $row['propina_total'] / $row['pedidos'] : 0,
                    'tasa_propina' => $row['total_vendido'] > 0 ? $row['propina_total'] / $row['total_vendido'] : 0,
                    'ranking' => 0
                ];
            }
        }

        return $kpis;
    }

    /**
     * Convierte fecha de DD/MM/YYYY a YYYY-MM-DD
     */
    private static function convertirFecha($fecha) {
        // Si ya está en formato YYYY-MM-DD, devolverla
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }

        // Convertir de DD/MM/YYYY a YYYY-MM-DD
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
            $partes = explode('/', $fecha);
            return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
        }

        // Formato inválido, devolver fecha actual
        return date('Y-m-d');
    }
}