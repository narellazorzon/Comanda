<?php
// src/config/ClientSession.php

namespace App\Config;

/**
 * Gestión de sesiones para clientes
 * Asegura que las sesiones de cliente y admin no se mezclen
 */
class ClientSession {

    /**
     * Inicializa una sesión limpia para cliente
     * Preserva solo datos relevantes del cliente
     */
    public static function initClientSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Datos a preservar del cliente
        $preserve = [
            'mesa_qr' => $_SESSION['mesa_qr'] ?? null,
            'ultimo_pedido_id' => $_SESSION['ultimo_pedido_id'] ?? null,
            'ultimo_pago' => $_SESSION['ultimo_pago'] ?? null,
            'cliente_info' => $_SESSION['cliente_info'] ?? null
        ];

        // Si hay sesión de admin activa, guardarla temporalmente
        $adminSession = null;
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            $adminSession = $_SESSION['user'];
        }

        // Limpiar toda la sesión
        $_SESSION = [];

        // Restaurar solo datos del cliente
        foreach ($preserve as $key => $value) {
            if ($value !== null) {
                $_SESSION[$key] = $value;
            }
        }

        // Marcar contexto como cliente
        $_SESSION['contexto'] = 'cliente';

        // Si había sesión de admin, guardarla en una clave separada
        if ($adminSession) {
            $_SESSION['admin_backup'] = $adminSession;
        }
    }

    /**
     * Restaura la sesión de admin si existía
     */
    public static function restoreAdminSession() {
        if (isset($_SESSION['admin_backup'])) {
            $_SESSION['user'] = $_SESSION['admin_backup'];
            unset($_SESSION['admin_backup']);
            unset($_SESSION['contexto']);
        }
    }

    /**
     * Verifica si estamos en contexto de cliente
     */
    public static function isClientContext() {
        return isset($_SESSION['contexto']) && $_SESSION['contexto'] === 'cliente';
    }
}