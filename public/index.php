<?php
// public/index.php - Punto de entrada principal con routing MVC
session_start();

// Cargar autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Incluir header para todas las páginas (excepto login)
$route = $_GET['route'] ?? 'cliente';
if ($route !== 'login') {
    include __DIR__ . '/../src/views/includes/header.php';
}

// Obtener la ruta solicitada
$route = $_GET['route'] ?? 'cliente';

// Función para redirigir al login si no está autenticado
function requireAuth() {
    if (empty($_SESSION['user'])) {
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

// Función para verificar permisos de mozo o administrador
function requireMozoOrAdmin() {
    requireAuth();
    if (!in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
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

    // Rutas de Mozos
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

    case 'mozos/procesar-inactivacion':
        requireAdmin();
        require_once __DIR__ . '/../src/controllers/MozoController.php';
        \App\Controllers\MozoController::procesarInactivacion();
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

    case 'reportes/rendimiento-mozos':
        requireAdmin();
        include __DIR__ . '/../src/views/reportes/rendimiento_mozos.php';
        break;

    case 'reportes/propina':
        requireAdmin();
        include __DIR__ . '/../src/views/reportes/propina.php';
        break;

    case 'reportes/recaudacion':
        requireAdmin();
        include __DIR__ . '/../src/views/reportes/recaudacion_mensual.php';
        break;

    // Rutas de Llamados (mozos y administradores)
    case 'llamados':
        requireMozoOrAdmin();
        include __DIR__ . '/../src/views/llamados/index.php';
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

// Incluir footer para todas las páginas (excepto login)
if ($route !== 'login') {
    include __DIR__ . '/../src/views/includes/footer.php';
}
?>