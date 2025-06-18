<?php
// src/controllers/MozoController.php
namespace App\Controllers;

use App\Models\Usuario;

class MozoController {
    protected static function authorize() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            header('Location: unauthorized.php');
            exit;
        }
    }

    public static function index() {
        self::authorize();
        $mozos = Usuario::allByRole('mozo');
        include __DIR__ . '/../../public/cme_mozos.php';
    }

    public static function create() {
        self::authorize();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST['rol'] = 'mozo';
            $_POST['contrasenia'] = password_hash($_POST['contrasenia'], PASSWORD_DEFAULT);
            Usuario::create($_POST);
            header('Location: cme_mozos.php');
            exit;
        }
        include __DIR__ . '/../../public/alta_mozo.php';
    }

    public static function delete() {
        self::authorize();
        $id = (int) ($_GET['delete'] ?? 0);
        if ($id > 0) {
            Usuario::delete($id);
        }
        header('Location: cme_mozos.php');
        exit;
    }
}
