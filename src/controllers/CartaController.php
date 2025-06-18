<?php
// src/controllers/CartaController.php
namespace App\Controllers;

use App\Models\CartaItem;

class CartaController {
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
        $items = CartaItem::all();
        include __DIR__ . '/../../public/cme_carta.php';
    }

    public static function create() {
        self::authorize();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CartaItem::create($_POST);
            header('Location: cme_carta.php');
            exit;
        }
        include __DIR__ . '/../../public/alta_carta.php';
    }

    public static function edit() {
        self::authorize();
        $id   = (int)($_GET['id'] ?? 0);
        $item = CartaItem::find($id);
        if (!$item) {
            header('Location: cme_carta.php');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CartaItem::update($id, $_POST);
            header('Location: cme_carta.php');
            exit;
        }
        include __DIR__ . '/../../public/alta_carta.php';
    }

    public static function delete() {
        self::authorize();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            CartaItem::delete($id);
        }
        header('Location: cme_carta.php');
        exit;
    }
}
