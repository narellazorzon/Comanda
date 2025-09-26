<?php
// src/controllers/ReportesController.php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Pedido;
use PDO;

/**
 * Controlador para la gestión de reportes y estadísticas
 *
 * Este controlador maneja toda la lógica relacionada con la generación
 * de reportes, métricas y análisis del sistema.
 */
class ReportesController
{
    /**
     * Muestra el dashboard principal con métricas en tiempo real
     */
    public function dashboard()
    {
        // Verificar permisos
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
            header('Location: index.php?route=login');
            exit;
        }

        // Obtener métricas del día
        $metrics = $this->getDailyMetrics();

        // Obtener datos para gráficos
        $chartData = $this->getChartData();

        // Obtener productos más vendidos
        $topProducts = $this->getTopProducts();

        // Obtener métricas por mozo
        $mozoMetrics = $this->getMozoMetrics();

        // Cargar la vista del dashboard
        require __DIR__ . '/../views/reportes/dashboard.php';
    }

    /**
     * Genera reporte de ventas por período
     */
    public function ventasPorPeriodo()
    {
        // Verificar permisos
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
            header('Location: index.php?route=login');
            exit;
        }

        $periodo = $_GET['periodo'] ?? 'hoy';
        $formato = $_GET['formato'] ?? 'html';

        $data = $this->getVentasPorPeriodo($periodo);

        if ($formato === 'json') {
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        }

        // Cargar vista del reporte
        require __DIR__ . '/../views/reportes/ventas-por-periodo.php';
    }

    /**
     * API para obtener métricas en tiempo real (para app móvil)
     */
    public function apiMetrics()
    {
        header('Content-Type: application/json');

        try {
            $metrics = [
                'pedidos_pendientes' => $this->countPedidosByStatus('pendiente'),
                'pedidos_en_preparacion' => $this->countPedidosByStatus('en_preparacion'),
                'mesas_ocupadas' => $this->countMesasByStatus('ocupada'),
                'ventas_del_dia' => $this->getVentasDelDia(),
                'timestamp' => date('Y-m-d H:i:s')
            ];

            echo json_encode(['success' => true, 'data' => $metrics]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * API para obtener pedidos asignados a un mozo (para app móvil)
     */
    public function apiMozoPedidos()
    {
        header('Content-Type: application/json');

        if (!isset($_GET['id_mozo'])) {
            echo json_encode(['success' => false, 'error' => 'ID de mozo requerido']);
            exit;
        }

        try {
            $idMozo = (int)$_GET['id_mozo'];
            $pedidos = $this->getPedidosByMozo($idMozo);

            echo json_encode(['success' => true, 'data' => $pedidos]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Actualiza el estado de un pedido (para app móvil)
     */
    public function apiUpdatePedidoStatus()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['id_pedido']) || !isset($input['nuevo_estado'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        try {
            $idPedido = (int)$input['id_pedido'];
            $nuevoEstado = $input['nuevo_estado'];

            // Verificar si el pedido existe y pertenece al mozo
            if (!$this->verifyPedidoMozo($idPedido, $input['id_mozo'] ?? 0)) {
                echo json_encode(['success' => false, 'error' => 'No autorizado']);
                exit;
            }

            // Actualizar estado
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
            $result = $stmt->execute([$nuevoEstado, $idPedido]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al actualizar']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene métricas del día actual
     */
    private function getDailyMetrics()
    {
        $db = (new Database())->getConnection();
        $today = date('Y-m-d');

        $metrics = [];

        // Total de ventas del día
        $stmt = $db->prepare("SELECT SUM(total) as total_ventas FROM pedidos
                             WHERE DATE(fecha_hora) = ? AND estado NOT IN ('cancelado')");
        $stmt->execute([$today]);
        $metrics['ventas_dia'] = (float)$stmt->fetchColumn();

        // Número de pedidos del día
        $stmt = $db->prepare("SELECT COUNT(*) as total_pedidos FROM pedidos
                             WHERE DATE(fecha_hora) = ?");
        $stmt->execute([$today]);
        $metrics['pedidos_dia'] = (int)$stmt->fetchColumn();

        // Pedidos pendientes
        $stmt = $db->prepare("SELECT COUNT(*) FROM pedidos
                             WHERE estado = 'pendiente'");
        $stmt->execute();
        $metrics['pedidos_pendientes'] = (int)$stmt->fetchColumn();

        // Mesas ocupadas
        $stmt = $db->prepare("SELECT COUNT(*) FROM mesas WHERE estado = 'ocupada'");
        $stmt->execute();
        $metrics['mesas_ocupadas'] = (int)$stmt->fetchColumn();

        // Ticket promedio
        if ($metrics['pedidos_dia'] > 0) {
            $metrics['ticket_promedio'] = $metrics['ventas_dia'] / $metrics['pedidos_dia'];
        } else {
            $metrics['ticket_promedio'] = 0;
        }

        return $metrics;
    }

    /**
     * Obtiene datos para los gráficos del dashboard
     */
    private function getChartData()
    {
        $db = (new Database())->getConnection();

        // Ventas últimas 24 horas
        $ventasHoras = [];
        for ($i = 23; $i >= 0; $i--) {
            $hora = date('Y-m-d H:00:00', strtotime("-$i hours"));
            $stmt = $db->prepare("SELECT SUM(total) as ventas FROM pedidos
                                 WHERE fecha_hora >= ? AND fecha_hora < ?
                                 AND estado NOT IN ('cancelado')");
            $stmt->execute([$hora, date('Y-m-d H:00:00', strtotime("-$i hours +1 hour"))]);
            $ventasHoras[] = (float)$stmt->fetchColumn();
        }

        // Ventas últimos 7 días
        $ventasDias = [];
        for ($i = 6; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime("-$i days"));
            $stmt = $db->prepare("SELECT SUM(total) as ventas FROM pedidos
                                 WHERE DATE(fecha_hora) = ? AND estado NOT IN ('cancelado')");
            $stmt->execute([$dia]);
            $ventasDias[] = (float)$stmt->fetchColumn();
        }

        return [
            'ventas_24h' => $ventasHoras,
            'ventas_7d' => $ventasDias
        ];
    }

    /**
     * Obtiene los productos más vendidos
     */
    private function getTopProducts()
    {
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("
            SELECT c.nombre, SUM(dp.cantidad) as total_vendido, SUM(dp.subtotal) as total_ventas
            FROM detalle_pedido dp
            JOIN carta c ON dp.id_item = c.id_item
            JOIN pedidos p ON dp.id_pedido = p.id_pedido
            WHERE DATE(p.fecha_hora) = CURDATE() AND p.estado NOT IN ('cancelado')
            GROUP BY c.id_item
            ORDER BY total_vendido DESC
            LIMIT 10
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene métricas por mozo
     */
    private function getMozoMetrics()
    {
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("
            SELECT
                u.id_usuario,
                CONCAT(u.nombre, ' ', u.apellido) as nombre_mozo,
                COUNT(p.id_pedido) as total_pedidos,
                SUM(p.total) as total_ventas,
                AVG(p.total) as ticket_promedio
            FROM pedidos p
            JOIN usuarios u ON p.id_mozo = u.id_usuario
            WHERE DATE(p.fecha_hora) = CURDATE() AND p.estado NOT IN ('cancelado')
            GROUP BY u.id_usuario
            ORDER BY total_ventas DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas por período específico
     */
    private function getVentasPorPeriodo($periodo)
    {
        $db = (new Database())->getConnection();

        switch ($periodo) {
            case 'hoy':
                $where = "DATE(fecha_hora) = CURDATE()";
                break;
            case 'ayer':
                $where = "DATE(fecha_hora) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'semana':
                $where = "WEEK(fecha_hora) = WEEK(CURDATE()) AND YEAR(fecha_hora) = YEAR(CURDATE())";
                break;
            case 'mes':
                $where = "MONTH(fecha_hora) = MONTH(CURDATE()) AND YEAR(fecha_hora) = YEAR(CURDATE())";
                break;
            default:
                $where = "DATE(fecha_hora) = CURDATE()";
        }

        $stmt = $db->prepare("
            SELECT
                DATE(fecha_hora) as fecha,
                COUNT(*) as total_pedidos,
                SUM(total) as total_ventas,
                AVG(total) as ticket_promedio
            FROM pedidos
            WHERE $where AND estado NOT IN ('cancelado')
            GROUP BY DATE(fecha_hora)
            ORDER BY fecha ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta pedidos por estado
     */
    private function countPedidosByStatus($status)
    {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = ?");
        $stmt->execute([$status]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Cuenta mesas por estado
     */
    private function countMesasByStatus($status)
    {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM mesas WHERE estado = ?");
        $stmt->execute([$status]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene ventas del día
     */
    private function getVentasDelDia()
    {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT SUM(total) FROM pedidos
                             WHERE DATE(fecha_hora) = CURDATE() AND estado NOT IN ('cancelado')");
        $result = $stmt->fetchColumn();
        return (float)$result;
    }

    /**
     * Obtiene pedidos asignados a un mozo
     */
    private function getPedidosByMozo($idMozo)
    {
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("
            SELECT p.*, m.numero as numero_mesa,
                   GROUP_CONCAT(c.nombre, ' (', dp.cantidad, ')') as items
            FROM pedidos p
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
            LEFT JOIN carta c ON dp.id_item = c.id_item
            WHERE p.id_mozo = ? AND p.estado NOT IN ('cerrado', 'cancelado')
            GROUP BY p.id_pedido
            ORDER BY p.fecha_hora ASC
        ");
        $stmt->execute([$idMozo]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un pedido pertenece a un mozo
     */
    private function verifyPedidoMozo($idPedido, $idMozo)
    {
        // Si es administrador, permitir acceso
        if (isset($_SESSION['usuario']) && $_SESSION['usuario']['rol'] === 'administrador') {
            return true;
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT id_mozo FROM pedidos WHERE id_pedido = ?");
        $stmt->execute([$idPedido]);
        $pedidoMozo = $stmt->fetchColumn();

        return $pedidoMozo == $idMozo;
    }
}