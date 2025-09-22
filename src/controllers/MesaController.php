<?php
namespace App\Controllers;

use App\Models\Mesa;

require_once __DIR__ . '/../config/helpers.php';

/**
 * Acciones administrativas para mesas que necesitan logica de servidor.
 */
class MesaController
{
    /**
     * Desactiva (soft delete) una mesa y redirige con feedback.
     */
    public static function delete(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $id = isset($_POST['id'])
            ? (int) $_POST['id']
            : (int) ($_GET['id'] ?? $_GET['delete'] ?? 0);

        if ($id <= 0) {
            header('Location: ' . url('mesas', ['error' => '1']));
            exit;
        }

        $resultado = Mesa::delete($id);
        if ($resultado['success']) {
            header('Location: ' . url('mesas', ['success' => '1']));
        } else {
            $mensaje = urlencode($resultado['message']);
            header('Location: ' . url('mesas', ['error' => '3', 'message' => $mensaje]));
        }
        exit;
    }

    /**
     * Reactiva una mesa previamente desactivada.
     */
    public static function reactivate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $id = isset($_POST['id'])
            ? (int) $_POST['id']
            : (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: ' . url('mesas', ['error' => '1']));
            exit;
        }

        $resultado = Mesa::reactivate($id);
        if ($resultado['success']) {
            header('Location: ' . url('mesas', ['success' => '2']));
        } else {
            $mensaje = urlencode($resultado['message']);
            header('Location: ' . url('mesas', ['error' => '3', 'message' => $mensaje]));
        }
        exit;
    }
}
