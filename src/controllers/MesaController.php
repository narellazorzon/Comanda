<?php
namespace App\Controllers;
use App\Models\Mesa;

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
    public static function delete($id) {
        session_start();
        Mesa::delete($id);
        header('Location: cme_mesas.php');
        exit;
    }
}
