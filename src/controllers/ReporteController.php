<?php
// src/controllers/ReporteController.php
namespace App\Controllers;

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
}
