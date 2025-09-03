<?php
// src/controllers/CartaController.php
namespace App\Controllers;

use App\Models\CartaItem;

// Incluir helpers después del namespace
require_once __DIR__ . '/../config/helpers.php';

class CartaController {
    protected static function authorize() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            header('Location: ' . url('unauthorized'));
            exit;
        }
    }

    public static function index() {
        self::authorize();
        $items = CartaItem::all();
        include __DIR__ . '/../views/carta/index.php';
    }

    public static function create() {
        self::authorize();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            
            if ($id > 0) {
                // Es una actualización
                self::update($id);
            } else {
                // Es una creación nueva
                // Validar datos requeridos
                if (empty($_POST['nombre']) || empty($_POST['categoria']) || empty($_POST['precio'])) {
                    header('Location: ' . url('carta/create', ['error' => 'Faltan campos requeridos']));
                    exit;
                }
                
                $data = [
                    'nombre' => trim($_POST['nombre']),
                    'descripcion' => trim($_POST['descripcion'] ?? ''),
                    'precio' => (float) $_POST['precio'],
                    'categoria' => trim($_POST['categoria']),
                    'disponibilidad' => (int) ($_POST['disponibilidad'] ?? 1),
                    'imagen_url' => trim($_POST['imagen_url'] ?? ''),
                    'descuento' => (float) ($_POST['descuento'] ?? 0.00)
                ];
                
                $result = CartaItem::create($data);
                
                if ($result) {
                    // Redirección POST-REDIRECT-GET para evitar reenvío de formulario
                    header('Location: ' . url('carta', ['success' => 'Ítem creado exitosamente']));
                } else {
                    header('Location: ' . url('carta/create', ['error' => 'Error al crear el ítem']));
                }
                exit;
            }
        }
        include __DIR__ . '/../views/carta/create.php';
    }

    public static function edit() {
        self::authorize();
        $id = (int)($_GET['id'] ?? 0);
        $item = CartaItem::find($id);
        if (!$item) {
            header('Location: ' . url('carta', ['error' => 'Ítem no encontrado']));
            exit;
        }
        include __DIR__ . '/../views/carta/create.php';
    }

    public static function update(int $id) {
        self::authorize();
        
        $item = CartaItem::find($id);
        if (!$item) {
            header('Location: ' . url('carta', ['error' => 'Ítem no encontrado']));
            exit;
        }

        // Preparar datos para actualización
        $data = [
            'nombre' => trim($_POST['nombre']),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => (float) $_POST['precio'],
            'categoria' => trim($_POST['categoria']),
            'disponibilidad' => (int) ($_POST['disponibilidad'] ?? 1),
            'imagen_url' => trim($_POST['imagen_url'] ?? ''),
            'descuento' => (float) ($_POST['descuento'] ?? 0.00)
        ];

        if (CartaItem::update($id, $data)) {
            header('Location: ' . url('carta', ['success' => 'Ítem actualizado exitosamente']));
        } else {
            header('Location: ' . url('carta', ['error' => 'Error al actualizar el ítem']));
        }
        exit;
    }

    public static function delete() {
        self::authorize();
        $id = (int)($_GET['delete'] ?? 0);
        if ($id > 0) {
            CartaItem::delete($id);
        }
        header('Location: ' . url('carta'));
        exit;
    }
}
