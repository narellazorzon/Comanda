<?php
// public/index.php - Punto de entrada principal con routing MVC
session_start();

// Cargar autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Incluir header para todas las páginas (excepto login y rutas de API)
$route = $_GET['route'] ?? 'cliente';
$apiRoutes = ['cliente-pedido', 'llamar-mozo', 'pedidos/info', 'pedidos/update-estado'];
if ($route !== 'login' && !in_array($route, $apiRoutes)) {
    include __DIR__ . '/../src/views/includes/header.php';
}

// Obtener la ruta solicitada
$route = $_GET['route'] ?? 'cliente';

// Función para redirigir al login si no está autenticado
function requireAuth() {
    if (empty($_SESSION['user'])) {
        // Si es una petición AJAX, devolver JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No está autenticado. Por favor, inicie sesión.']);
            exit;
        }
        // Si no es AJAX, redirigir normalmente
        header('Location: index.php?route=login');
        exit;
    }
}

// Función para verificar permisos de administrador
function requireAdmin() {
    requireAuth();
    if ($_SESSION['user']['rol'] !== 'administrador') {
        header('Location: index.php?route=unauthorized');
        exit;
    }
}

// Función para verificar permisos del personal o administrador
function requireMozoOrAdmin() {
    requireAuth();
    if (!in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
        // Si es una petición AJAX, devolver JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para acceder a esta información']);
            exit;
        }
        // Si no es AJAX, redirigir normalmente
        header('Location: index.php?route=unauthorized');
        exit;
    }
}

// Sistema de routing
switch ($route) {
    case 'login':
        // Si ya está logueado, redirigir al home
        if (!empty($_SESSION['user'])) {
            header('Location: index.php?route=home');
            exit;
        }
        include __DIR__ . '/../src/views/auth/login.php';
        break;

    case 'logout':
        require_once __DIR__ . '/../src/controllers/AuthController.php';
        \App\Controllers\AuthController::logout();
        break;

    case 'home':
        requireAuth();
        include __DIR__ . '/../src/views/home/index.php';
        break;

    case 'cliente':
        include __DIR__ . '/../src/views/cliente/index.php';
        break;

    // Rutas de Mesas
    case 'mesas':
        requireMozoOrAdmin();
        include __DIR__ . '/../src/views/mesas/index.php';
        break;

    case 'mesas/create':
        requireAdmin();
        include __DIR__ . '/../src/views/mesas/create.php';
        break;

    case 'mesas/edit':
        requireAdmin();
        include __DIR__ . '/../src/views/mesas/create.php';
        break;

    case 'mesas/cambiar-mozo':
        requireAdmin();
        include __DIR__ . '/../src/views/mesas/cambiar_mozo.php';
        break;

    case 'mesas/delete':
        requireAdmin();
        require_once __DIR__ . '/../src/controllers/MesaController.php';
        \App\Controllers\MesaController::delete();
        break;

    case 'mesas/reactivate':
        requireAdmin();
        require_once __DIR__ . '/../src/controllers/MesaController.php';
        \App\Controllers\MesaController::reactivate();
        break;

    // Rutas del Personal
    case 'mozos':
        requireAdmin();
        include __DIR__ . '/../src/views/mozos/index.php';
        break;

    case 'mozos/create':
        requireAdmin();
        include __DIR__ . '/../src/views/mozos/create.php';
        break;

    case 'mozos/edit':
        requireAdmin();
        include __DIR__ . '/../src/views/mozos/create.php';
        break;

    case 'mozos/confirmar-inactivacion':
        requireAdmin();
        include __DIR__ . '/../src/views/mozos/confirmar_inactivacion.php';
        break;

    case 'mozos/confirmar-eliminacion':
        requireAdmin();
        include __DIR__ . '/../src/views/mozos/confirmar_eliminacion.php';
        break;

    case 'mozos/procesar-inactivacion':
        requireAdmin();
        require_once __DIR__ . '/../src/controllers/MozoController.php';
        \App\Controllers\MozoController::procesarInactivacion();
        break;

    case 'mozos/delete':
        requireAdmin();
        require_once __DIR__ . '/../src/controllers/MozoController.php';
        \App\Controllers\MozoController::delete();
        break;

    case 'mozos/procesar-eliminacion':
        requireAdmin();
        require_once __DIR__ . '/../src/controllers/MozoController.php';
        \App\Controllers\MozoController::procesarEliminacion();
        break;

    // Rutas de Carta
    case 'carta':
        requireMozoOrAdmin();
        \App\Controllers\CartaController::index();
        break;

    case 'carta/create':
        requireAdmin();
        \App\Controllers\CartaController::create();
        break;

    case 'carta/edit':
        requireAdmin();
        \App\Controllers\CartaController::edit();
        break;

    case 'carta/delete':
        requireAdmin();
        \App\Controllers\CartaController::delete();
        break;

    // Rutas de Pedidos
    case 'pedidos':
        requireMozoOrAdmin();
        include __DIR__ . '/../src/views/pedidos/index.php';
        break;

    case 'pedidos/create':
        requireMozoOrAdmin();
        include __DIR__ . '/../src/views/pedidos/create.php';
        break;

    case 'pedidos/edit':
        requireMozoOrAdmin();
        include __DIR__ . '/../src/views/pedidos/create.php';
        break;

    case 'pedidos/delete':
        requireAdmin();
        \App\Controllers\PedidoController::delete();
        break;

    case 'pedidos/update-estado':
        requireMozoOrAdmin();
        \App\Controllers\PedidoController::updateEstado();
        break;

    case 'pedidos/info':
        requireMozoOrAdmin();
        \App\Controllers\PedidoController::info();
        break;

    case 'test-pedidos':
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Ruta de prueba funcionando']);
        exit;

    // Rutas de Reportes
    case 'reportes':
        requireAdmin();
        include __DIR__ . '/../src/views/reportes/index.php';
        break;

    case 'reportes/platos-mas-vendidos':
        requireAdmin();
        include __DIR__ . '/../src/views/reportes/platos_mas_vendidos.php';
        break;

    case 'reportes/propina':
        requireAdmin();
        include __DIR__ . '/../src/views/reportes/propina.php';
        break;

    case 'reportes/recaudacion':
        requireAdmin();
        include __DIR__ . '/../src/views/reportes/recaudacion_mensual.php';
        break;

    case 'reportes/ventas-categoria':
        requireAdmin();
        include __DIR__ . '/../src/views/reportes/ventas_por_categoria.php';
        break;

    case 'reportes/rendimiento-personal':
        requireAdmin();
        include __DIR__ . '/../src/views/reportes/rendimiento_mozos.php';
        break;

    // Rutas de Llamados (personal y administradores)
    case 'llamados':
        requireMozoOrAdmin();
        include __DIR__ . '/../src/views/llamados/index.php';
        break;

    // Ruta para llamar personal desde cliente
    case 'llamar-mozo':
        require_once __DIR__ . '/../src/controllers/MozoController.php';
        \App\Controllers\MozoController::llamarMozo();
        break;

    // Ruta para crear pedido desde cliente
    case 'cliente-pedido':
        require_once __DIR__ . '/../src/controllers/ClienteController.php';
        \App\Controllers\ClienteController::crearPedido();
        break;

    // Ruta del generador de QRs offline (solo administrador)
    case 'admin/qr-offline':
        requireAdmin();
        include __DIR__ . '/../src/views/admin/generador_qr_offline.php';
        break;

    case 'unauthorized':
        include __DIR__ . '/../src/views/errors/unauthorized.php';
        break;

    default:
        // Si no está logueado, mostrar la vista pública del cliente
        if (empty($_SESSION['user'])) {
            header('Location: index.php?route=cliente');
            exit;
        }
        // Si está logueado, mostrar el home
        header('Location: index.php?route=home');
        exit;
}

// Incluir footer para todas las páginas (excepto login y rutas de API)
if ($route !== 'login' && !in_array($route, $apiRoutes)) {
    include __DIR__ . '/../src/views/includes/footer.php';
}
?>