<?php
namespace App\Controllers;

use App\Models\LlamadoMesa;

class LlamadoController {
    
    /**
     * Atiende un llamado cambiando su estado a 'atendido'
     */
    public static function atender(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
            header('Location: index.php?route=unauthorized');
            exit;
        }

        $id_llamado = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id_llamado <= 0) {
            header('Location: index.php?route=llamados&error=1');
            exit;
        }

        // Cambiar estado a 'atendido' en lugar de eliminar
        if (LlamadoMesa::updateEstado($id_llamado, 'atendido')) {
            header('Location: index.php?route=llamados&estado=pendiente&atendido=1');
        } else {
            header('Location: index.php?route=llamados&estado=pendiente&error=2');
        }
        exit;
    }
}
