<?php
namespace App\Controllers;
use App\Models\Mesa;

// Incluir helpers
require_once __DIR__ . '/../config/helpers.php';

class MesaController {
    public static function index() {
        session_start();
        $mesas = Mesa::all();
        include __DIR__ . '/../../public/cme_mesas.php';
    }
    public static function create() {
        session_start();
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            Mesa::create($_POST);
            header('Location: cme_mesas.php');
            exit;
        }
        include __DIR__ . '/../../public/alta_mesa.php';
    }
    public static function edit($id) {
        session_start();
        $mesa = Mesa::find($id);
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            Mesa::update($id, $_POST);
            header('Location: cme_mesas.php');
            exit;
        }
        include __DIR__ . '/../../public/alta_mesa.php';
    }
    public static function delete() {
        // session_start() ya fue llamado en index.php
        
        $id = 0;
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
        } elseif (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
        }
        
        if ($id > 0) {
            $resultado = Mesa::delete($id);
            if ($resultado['success']) {
                header('Location: ' . url('mesas', ['success' => '1']));
            } else {
                // Usar el mensaje específico del modelo
                $mensaje = urlencode($resultado['message']);
                header('Location: ' . url('mesas', ['error' => '3', 'message' => $mensaje]));
            }
        } else {
            header('Location: ' . url('mesas', ['error' => '1']));
        }
        exit;
    }

    public static function reactivate() {
        // session_start() ya fue llamado en index.php
        
        $id = 0;
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
        } elseif (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
        }
        
        if ($id > 0) {
            $resultado = Mesa::reactivate($id);
            if ($resultado['success']) {
                header('Location: ' . url('mesas', ['success' => '2']));
            } else {
                // Usar el mensaje específico del modelo
                $mensaje = urlencode($resultado['message']);
                header('Location: ' . url('mesas', ['error' => '3', 'message' => $mensaje]));
            }
        } else {
            header('Location: ' . url('mesas', ['error' => '1']));
        }
        exit;
    }
}
