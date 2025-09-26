<?php
// src/controllers/InventarioController.php
namespace App\Controllers;

use App\Models\Inventario;
use App\Config\CsrfToken;
use App\Config\Validator;

class InventarioController 
{
    /**
     * Muestra el dashboard del inventario
     */
    public static function index(): void 
    {
        // Verificar autenticación y permisos de administrador
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
            header('Location: index.php?route=unauthorized');
            exit;
        }
        
        try {
            $estadisticas = Inventario::getEstadisticas();
            $stock_bajo = Inventario::getStockBajo();
            $resumen_categorias = Inventario::getResumenCategorias();
            
            // Cargar vista del dashboard
            include __DIR__ . '/../views/inventario/dashboard.php';
            
        } catch (\Exception $e) {
            error_log("Error en inventario dashboard: " . $e->getMessage());
            header('Location: index.php?route=home&error=' . urlencode('Error al cargar inventario'));
            exit;
        }
    }
    
    /**
     * Muestra la lista completa del inventario
     */
    public static function lista(): void 
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
            header('Location: index.php?route=unauthorized');
            exit;
        }
        
        try {
            $inventario = Inventario::all();
            
            // Cargar vista de lista
            include __DIR__ . '/../views/inventario/lista.php';
            
        } catch (\Exception $e) {
            error_log("Error en inventario lista: " . $e->getMessage());
            header('Location: index.php?route=home&error=' . urlencode('Error al cargar inventario'));
            exit;
        }
    }
    
    /**
     * Actualiza el stock de un item
     */
    public static function actualizarStock(): void 
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
            header('Location: index.php?route=unauthorized');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=inventario');
            exit;
        }
        
        try {
            // Verificar token CSRF
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!CsrfToken::validate($csrf_token)) {
                throw new \Exception('Token de seguridad inválido');
            }
            
            $id_item = $_POST['id_item'] ?? '';
            $nueva_cantidad = $_POST['nueva_cantidad'] ?? '';
            $motivo = $_POST['motivo'] ?? '';
            $id_usuario = $_SESSION['user']['id_usuario'];
            
            $resultado = Inventario::actualizarStock(
                (int) $id_item,
                (int) $nueva_cantidad, 
                $motivo,
                $id_usuario
            );
            
            if ($resultado) {
                header('Location: index.php?route=inventario/lista&success=' . urlencode('Stock actualizado correctamente'));
            } else {
                header('Location: index.php?route=inventario/lista&error=' . urlencode('Error al actualizar stock'));
            }
            
        } catch (\Exception $e) {
            error_log("Error actualizando stock: " . $e->getMessage());
            header('Location: index.php?route=inventario/lista&error=' . urlencode($e->getMessage()));
        }
        
        exit;
    }
    
    /**
     * Muestra movimientos de inventario
     */
    public static function movimientos(): void 
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
            header('Location: index.php?route=unauthorized');
            exit;
        }
        
        try {
            $id_item = $_GET['id_item'] ?? null;
            $limit = (int) ($_GET['limit'] ?? 50);
            
            $movimientos = Inventario::getMovimientosRecientes($limit, $id_item);
            
            // Cargar vista de movimientos
            include __DIR__ . '/../views/inventario/movimientos.php';
            
        } catch (\Exception $e) {
            error_log("Error en movimientos inventario: " . $e->getMessage());
            header('Location: index.php?route=inventario&error=' . urlencode('Error al cargar movimientos'));
            exit;
        }
    }
    
    /**
     * API endpoint para verificar disponibilidad (para AJAX)
     */
    public static function verificarDisponibilidad(): void 
    {
        header('Content-Type: application/json');
        
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($_SESSION['user'])) {
            echo json_encode(['error' => 'No autenticado']);
            exit;
        }
        
        try {
            $id_item = (int) ($_GET['id_item'] ?? 0);
            $cantidad = (int) ($_GET['cantidad'] ?? 1);
            
            if ($id_item <= 0) {
                echo json_encode(['error' => 'ID de item inválido']);
                exit;
            }
            
            $disponible = Inventario::verificarDisponibilidad($id_item, $cantidad);
            $inventario = Inventario::findByItem($id_item);
            
            echo json_encode([
                'disponible' => $disponible,
                'stock_actual' => $inventario['cantidad_disponible'] ?? 0,
                'estado' => $inventario['estado'] ?? 'disponible'
            ]);
            
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        exit;
    }
}