<?php
// src/controllers/ReporteController.php
namespace App\Controllers;

use App\Config\Database;

class ReporteController {

    /**
     * Muestra el reporte de rendimiento de mozos
     */
    public static function rendimientoMozos() {
        // Obtener parámetros del formulario (usando los mismos nombres que platos más vendidos)
        $fechaDesde = !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
        $fechaHasta = !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
        $agrupar = $_GET['agrupar'] ?? 'ninguno';
        
        // Usar las fechas directamente (el input type="date" siempre envía YYYY-MM-DD)
        // Solo validar que no estén vacías
        $desde = !empty($fechaDesde) ? $fechaDesde : date('Y-m-01');
        $hasta = !empty($fechaHasta) ? $fechaHasta : date('Y-m-d');

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
                INNER JOIN usuarios u ON p.id_mozo = u.id_usuario
                LEFT JOIN propinas pr ON p.id_pedido = pr.id_pedido
                WHERE p.estado IN ('servido', 'cerrado')
                AND p.fecha_hora BETWEEN ? AND ?
                AND p.deleted_at IS NULL
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
                INNER JOIN usuarios u ON p.id_mozo = u.id_usuario
                LEFT JOIN propinas pr ON p.id_pedido = pr.id_pedido
                WHERE p.estado IN ('servido', 'cerrado')
                AND p.fecha_hora BETWEEN ? AND ?
                AND p.deleted_at IS NULL
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
                INNER JOIN usuarios u ON p.id_mozo = u.id_usuario
                LEFT JOIN propinas pr ON p.id_pedido = pr.id_pedido
                WHERE p.estado IN ('servido', 'cerrado')
                AND p.fecha_hora BETWEEN ? AND ?
                AND p.deleted_at IS NULL
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

    /**
     * Valida que una fecha tenga el formato YYYY-MM-DD
     */
    private static function validarFecha($fecha) {
        if (empty($fecha)) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    /**
     * Exporta el reporte de rendimiento de mozos a CSV
     */
    public static function exportarRendimientoMozosCSV() {
        // Obtener parámetros del formulario
        $fechaDesde = !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
        $fechaHasta = !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
        
        // Validar fechas
        $desde = !empty($fechaDesde) ? $fechaDesde : date('Y-m-01');
        $hasta = !empty($fechaHasta) ? $fechaHasta : date('Y-m-d');

        if ($desde > $hasta) {
            $temp = $desde;
            $desde = $hasta;
            $hasta = $temp;
        }

        // Obtener datos (solo vista de ranking)
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("
            SELECT
                u.nombre,
                u.apellido,
                COUNT(DISTINCT p.id_pedido) as pedidos,
                SUM(p.total) as total_vendido,
                COALESCE(SUM(pr.monto), 0) as propina_total
            FROM pedidos p
            INNER JOIN usuarios u ON p.id_mozo = u.id_usuario
            LEFT JOIN propinas pr ON p.id_pedido = pr.id_pedido
            WHERE p.estado IN ('servido', 'cerrado')
            AND p.fecha_hora BETWEEN ? AND ?
            AND p.deleted_at IS NULL
            GROUP BY u.id_usuario, u.nombre, u.apellido
            ORDER BY total_vendido DESC
        ");
        $stmt->execute([$desde . ' 00:00:00', $hasta . ' 23:59:59']);

        // Limpiar cualquier output previo
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Configurar headers para descarga CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rendimiento_mozos_' . date('Y-m-d') . '.csv"');
        
        // BOM UTF-8 para Excel (debe ser lo primero que se envía)
        echo "\xEF\xBB\xBF";

        // Abrir output stream
        $output = fopen('php://output', 'w');

        // Encabezados
        fputcsv($output, [
            'Mozo',
            'Pedidos',
            'Total Vendido',
            'Total Propinas',
            'Propina Promedio',
            'Tasa Propina'
        ], ';');

        // Filas de datos
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $mozo = $row['nombre'] . ' ' . $row['apellido'];
            $pedidos = $row['pedidos'];
            $totalVendido = $row['total_vendido'];
            $totalPropinas = $row['propina_total'];
            $propinaPromedio = $row['pedidos'] > 0 ? $row['propina_total'] / $row['pedidos'] : 0;
            $tasaPropina = $row['total_vendido'] > 0 ? $row['propina_total'] / $row['total_vendido'] : 0;

            fputcsv($output, [
                $mozo,
                $pedidos,
                number_format($totalVendido, 2, ',', ''),      // Formato argentino: coma como decimal
                number_format($totalPropinas, 2, ',', ''),
                number_format($propinaPromedio, 2, ',', ''),
                number_format($tasaPropina * 100, 2, ',', '') . '%'
            ], ';');
        }

        fclose($output);
        exit;
    }
}