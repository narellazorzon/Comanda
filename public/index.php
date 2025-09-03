<?php
// public/index.php - Punto de entrada principal con routing MVC
session_start();

// Cargar autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Incluir header para todas las páginas (excepto login)
$route = $_GET['route'] ?? 'home';
if ($route !== 'login') {
    include __DIR__ . '/../src/views/includes/header.php';
}

// Obtener la ruta solicitada
$route = $_GET['route'] ?? 'home';

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

    // Rutas de Carta
    case 'carta':
        requireMozoOrAdmin();
        include __DIR__ . '/../src/views/carta/index.php';
        break;

    case 'carta/create':
        requireAdmin();
        include __DIR__ . '/../src/views/carta/create.php';
        break;

    case 'carta/edit':
        requireAdmin();
        include __DIR__ . '/../src/views/carta/create.php';
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

    // Rutas de Llamados (solo para mozos)
    case 'llamados':
        requireAuth();
        if ($_SESSION['user']['rol'] !== 'mozo') {
            header('Location: index.php?route=unauthorized');
            exit;
        }
        include __DIR__ . '/../src/views/llamados/index.php';
        break;

    case 'unauthorized':
        include __DIR__ . '/../src/views/errors/unauthorized.php';
        break;

    default:
        // Si no está logueado, redirigir al login
        if (empty($_SESSION['user'])) {
            header('Location: index.php?route=login');
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