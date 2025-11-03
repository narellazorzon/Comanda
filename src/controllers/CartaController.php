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
        // Permitir acceso a mozos y administradores para ver la carta
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!in_array(($_SESSION['user']['rol'] ?? ''), ['administrador', 'mozo'])) {
            header('Location: ' . url('unauthorized'));
            exit;
        }
        $items = CartaItem::allIncludingUnavailable();
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
                
                $imagenUrl = trim($_POST['imagen_url'] ?? '');
                
                // Validar URL de imagen si se proporciona
                if (!empty($imagenUrl)) {
                    if (!filter_var($imagenUrl, FILTER_VALIDATE_URL)) {
                        header('Location: ' . url('carta/create', ['error' => 'URL de imagen inválida']));
                        exit;
                    }
                }
                
                $data = [
                    'nombre' => trim($_POST['nombre']),
                    'descripcion' => trim($_POST['descripcion'] ?? ''),
                    'precio' => self::calcularPrecioFinal($_POST['precio'], $_POST['descuento']),
                    'categoria' => trim($_POST['categoria']),
                    'disponibilidad' => (int) ($_POST['disponibilidad'] ?? 1),
                    'imagen_url' => $imagenUrl,
                    'descuento' => (float) ($_POST['descuento'] ?? 0.00)
                ];
                
                $result = CartaItem::create($data);
                
                if ($result) {
                    // Redirección POST-REDIRECT-GET para evitar reenvío de formulario
                    // Mantener el filtro de categoría si existe
                    $categoria = $_GET['categoria'] ?? null;
                    $params = ['success' => 'Ítem creado exitosamente'];
                    if ($categoria) {
                        $params['categoria'] = $categoria;
                    }
                    header('Location: ' . url('carta', $params));
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
            // Mantener el filtro de categoría si existe
            $categoria = $_GET['categoria'] ?? null;
            $params = ['error' => 'Ítem no encontrado'];
            if ($categoria) {
                $params['categoria'] = $categoria;
            }
            header('Location: ' . url('carta', $params));
            exit;
        }

        // Preparar datos para actualización
        $imagenUrl = trim($_POST['imagen_url'] ?? '');
        
        // Validar URL de imagen si se proporciona
        if (!empty($imagenUrl)) {
            if (!filter_var($imagenUrl, FILTER_VALIDATE_URL)) {
                // Mantener el filtro de categoría si existe
                $categoria = $_GET['categoria'] ?? null;
                $params = ['error' => 'URL de imagen inválida'];
                if ($categoria) {
                    $params['categoria'] = $categoria;
                }
                header('Location: ' . url('carta', $params));
                exit;
            }
        }
        
        $data = [
            'nombre' => trim($_POST['nombre']),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => self::calcularPrecioFinal($_POST['precio'], $_POST['descuento']),
            'categoria' => trim($_POST['categoria']),
            'disponibilidad' => (int) ($_POST['disponibilidad'] ?? 1),
            'imagen_url' => $imagenUrl,
            'descuento' => (float) ($_POST['descuento'] ?? 0.00)
        ];

        if (CartaItem::update($id, $data)) {
            // Mantener el filtro de categoría si existe
            $categoria = $_GET['categoria'] ?? null;
            $params = ['success' => 'Ítem actualizado exitosamente'];
            if ($categoria) {
                $params['categoria'] = $categoria;
            }
            header('Location: ' . url('carta', $params));
        } else {
            // Mantener el filtro de categoría si existe
            $categoria = $_GET['categoria'] ?? null;
            $params = ['error' => 'Error al actualizar el ítem'];
            if ($categoria) {
                $params['categoria'] = $categoria;
            }
            header('Location: ' . url('carta', $params));
        }
        exit;
    }

    public static function delete() {
        self::authorize();
        
        // Intentar obtener el ID tanto de GET como de POST para compatibilidad
        $id = 0;
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
        } elseif (isset($_GET['delete'])) {
            $id = (int)$_GET['delete'];
        }
        
        // Debug log
        error_log("CartaController::delete() - ID recibido: " . $id);
        error_log("CartaController::delete() - POST params: " . print_r($_POST, true));
        error_log("CartaController::delete() - GET params: " . print_r($_GET, true));
        
        if ($id > 0) {
            $resultado = CartaItem::delete($id);
            error_log("CartaController::delete() - Resultado: " . ($resultado ? 'true' : 'false'));
            
            if ($resultado) {
                // Mantener el filtro de categoría si existe
                $categoria = $_GET['categoria'] ?? null;
                $params = ['success' => 'Ítem eliminado correctamente'];
                if ($categoria) {
                    $params['categoria'] = $categoria;
                }
                header('Location: ' . url('carta', $params));
            } else {
                // Mantener el filtro de categoría si existe
                $categoria = $_GET['categoria'] ?? null;
                $params = ['error' => 'Error al eliminar el ítem'];
                if ($categoria) {
                    $params['categoria'] = $categoria;
                }
                header('Location: ' . url('carta', $params));
            }
        } else {
            error_log("CartaController::delete() - ID inválido: " . $id);
            // Mantener el filtro de categoría si existe
            $categoria = $_GET['categoria'] ?? null;
            $params = ['error' => 'ID de ítem inválido'];
            if ($categoria) {
                $params['categoria'] = $categoria;
            }
            header('Location: ' . url('carta', $params));
        }
        exit;
    }

    private static function calcularPrecioFinal($precioBase, $descuento) {
        $precio = (float) $precioBase;
        $desc = isset($descuento) && $descuento !== '' ? (float) $descuento : 0.0;

        if ($desc > 0) {
            $precioFinal = $precio - ($precio * $desc / 100);
        } else {
            $precioFinal = $precio;
        }

        return round($precioFinal, 2);
    }
}
