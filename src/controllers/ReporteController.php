<?php
// src/controllers/ReporteController.php
namespace App\Controllers;

require_once __DIR__ . '/../services/ReporteService.php';

use App\Services\ReporteService;

class ReporteController {
    protected static function authorize() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            header('Location: unauthorized.php');
            exit;
        }
    }

    public static function platosMasVendidos() {
        self::authorize();
        $rs     = new ReporteService();
        $platos = $rs->platosMasVendidos();
        include __DIR__ . '/../../public/reportes/platos_mas_vendidos.php';
    }

    public static function recaudacionMensual() {
        self::authorize();
        $mes    = $_GET['mes'] ?? date('Y-m');
        $rs     = new ReporteService();
        $recaud = $rs->recaudacionMensual($mes);
        include __DIR__ . '/../../public/reportes/recaudacion_mensual.php';
    }

    public static function propinas() {
        self::authorize();
        $mes  = $_GET['mes'] ?? date('Y-m');
        $rs   = new ReporteService();
        $tips = $rs->propinas($mes);
        include __DIR__ . '/../../public/reportes/propinas.php';
    }
    
    /**
     * Acción para el reporte de rendimiento de mozos
     */
    public static function rendimientoMozos() {
        self::authorize();
        
        // Obtener parámetros con valores por defecto
        $desde = $_GET['desde'] ?? date('Y-m-01'); // Primer día del mes actual
        $hasta = $_GET['hasta'] ?? date('Y-m-d');  // Hoy
        $agrupar = $_GET['agrupar'] ?? 'ninguno';
        
        // Validar parámetro agrupar
        if (!in_array($agrupar, ['ninguno', 'dia', 'mes'])) {
            $agrupar = 'ninguno';
        }
        
        // Obtener datos del servicio
        $rs = new ReporteService();
        $resultado = $rs->getRendimientoMozos($desde, $hasta, $agrupar);
        
        // Si solicitan JSON (para API o AJAX)
        if (isset($_GET['format']) && $_GET['format'] === 'json' || 
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json');
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        // Incluir la vista
        include __DIR__ . '/../../src/views/reportes/rendimiento_mozos.php';
    }
}
