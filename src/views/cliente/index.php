<?php
// src/views/cliente/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\CartaItem;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$items = CartaItem::all();

// Organizar items por categoría
$itemsPorCategoria = [];
foreach ($items as $item) {
    $categoria = $item['categoria'] ?? 'Otros';
    if (!isset($itemsPorCategoria[$categoria])) {
        $itemsPorCategoria[$categoria] = [];
    }
    $itemsPorCategoria[$categoria][] = $item;
}

// Orden preferido de categorías
$ordenCategorias = ['Entradas', 'Platos Principales', 'Carnes', 'Aves', 'Pescados', 'Pastas', 'Pizzas', 'Hamburguesas', 'Ensaladas', 'Postres', 'Bebidas'];
$categoriasOrdenadas = [];

// Primero agregar las categorías en el orden preferido
foreach ($ordenCategorias as $cat) {
    if (isset($itemsPorCategoria[$cat])) {
        $categoriasOrdenadas[$cat] = $itemsPorCategoria[$cat];
        unset($itemsPorCategoria[$cat]);
    }
}

// Luego agregar las categorías restantes
foreach ($itemsPorCategoria as $cat => $items) {
    $categoriasOrdenadas[$cat] = $items;
}

// Determinar base url
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url = $protocol . '://' . $host . dirname($script_name);

// Iconos para cada categoría
$iconosCategorias = [
    'Entradas' ,
    'Platos Principales' => '🍽️',
    'Carnes' => '🥩',
    'Aves' => '🍗',
    'Pescados' => '🐟',
    'Pastas' => '🍝',
    'Pizzas' => '🍕',
    'Hamburguesas' => '🍔',
    'Ensaladas' => '🥗',
    'Postres' => '🍰',
    'Bebidas' => '🥤',
    'Otros' => '🍴'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - Sistema Comanda</title>
    
    <style>
    /* Variables de diseño */
    :root {
        --color-fondo: #f7f1e1;
        --color-superficie: #ffffff;
        --color-primario: #a1866f;
        --color-secundario: #8b5e46;
        --color-acento: #007BFF;
        --color-exito: #28a745;
        --color-texto: #3f3f3f;
        --color-texto-suave: #6c757d;
        --sombra-suave: 0 2px 8px rgba(0,0,0,0.08);
        --sombra-media: 0 4px 12px rgba(0,0,0,0.12);
        --sombra-fuerte: 0 8px 24px rgba(0,0,0,0.15);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f7f1e1 0%, #f0e8d8 100%);
        color: rgb(83, 52, 31);
        min-height: 100vh;
    }

    /* --- Caja título --- */
    .menu-header {
        background: linear-gradient(135deg,rgb(83, 52, 31),rgb(137, 107, 75));
        color: #fff;
        text-align: center;
        padding: 1rem;
        border-radius: 10px;
        margin: 0 1rem 1rem;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .menu-header h2 {
        font-size: 1.2rem;
        margin-bottom: 0.25rem;
        color: rgb(247, 241, 226);
    }
    
    .menu-header p {
        font-size: 0.9rem;
        opacity: 0.9;
        color: rgb(247, 241, 226);
    }

    /* --- Caja filtros scrollables --- */
    .menu-filters {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding: 0.5rem 1rem;
        margin: 0 1rem 1rem;
        scrollbar-width: none;       /* Firefox */
    }
    
    .menu-filters::-webkit-scrollbar {
        display: none;              /* Chrome/Safari */
    }
    
    .menu-filters button {
        flex: 0 0 auto;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        border: 1px solid #b08d67;
        background: #fff;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .menu-filters button.active,
    .menu-filters button:hover {
        background: #b08d67;
        color: #fff;
    }

    .categoria-nav-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
        padding-bottom: 1rem; /* Espacio generoso para la barra de scroll */
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
        scrollbar-color: var(--color-primario) #f0f0f0;
        scrollbar-gutter: stable;
    }

    .categoria-nav-content::-webkit-scrollbar {
        height: 6px;
        margin-top: 8px; /* Separación clara del contenido */
    }

    .categoria-nav-content::-webkit-scrollbar-track {
        background: #f0f0f0;
        border-radius: 3px;
        margin: 0 1rem;
        margin-top: 4px;
    }

    .categoria-nav-content::-webkit-scrollbar-thumb {
        background: var(--color-primario);
        border-radius: 3px;
        opacity: 0.8;
    }

    .categoria-nav-content::-webkit-scrollbar-thumb:hover {
        background: var(--color-secundario);
        opacity: 1;
    }

    .categoria-btn {
        background: var(--color-superficie);
        border: 2px solid rgb(83, 52, 31);
        color: var(--color-primario);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        white-space: nowrap;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .categoria-btn:hover {
        background: var(--color-primario);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--sombra-suave);
    }

    .categoria-btn.active {
        background: rgb(83, 52, 31);
        color: white;
    }

    /* Contenedor principal */
    .menu-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem 2rem;
    }

    /* Secciones de categorías */
    .categoria-seccion {
        margin-bottom: 3rem;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .categoria-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 3px solid rgb(83, 52, 31);
    }

    .categoria-icono {
        font-size: 2rem;
    }

    .categoria-titulo {
        font-size: 1.75rem;
        font-weight: 700;
        color: rgb(83, 52, 31);
    }


    /* Grid de productos - Diseño mixto responsive */
    .productos-grid {
        display: grid;
        grid-template-columns: 1fr;   /* mobile */
        gap: 1rem;
    }
    
    @media (min-width: 768px) {
        .productos-grid {
            grid-template-columns: repeat(2, 1fr); /* tablet */
        }
    }
    
    @media (min-width: 1024px) {
        .productos-grid {
            grid-template-columns: repeat(3, 1fr); /* desktop */
        }
    }

    /* Tarjeta de producto */
    .producto-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--sombra-suave);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .producto-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--sombra-fuerte);
    }

    .producto-header {
        padding: 1rem 1rem 0.5rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    }

    .producto-nombre {
        font-size: 1rem;
        font-weight: 700;
        color: var(--color-texto);
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }

    .producto-badges {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    
    /* Estilos para imagen del producto */
    .producto-imagen {
        padding: 0 1.25rem;
        margin-bottom: 0.75rem;
        text-align: center;
    }
    
    .producto-img {
        width: 100%;
        height: 200px; /* Imagen grande para móvil 1 columna */
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    @media (min-width: 768px) {
        .producto-img {
            height: 100px; /* Imagen mediana para tablet 2 columnas */
        }
    }
    
    @media (min-width: 1024px) {
        .producto-img {
            height: 80px; /* Imagen pequeña para desktop 3 columnas */
        }
    }
    
    .producto-img:hover {
        transform: scale(1.02);
    }
    
    .producto-imagen-placeholder {
        width: 100%;
        height: 200px; /* Placeholder grande para móvil 1 columna */
        background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #999;
        margin: 0 auto;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    @media (min-width: 768px) {
        .producto-imagen-placeholder {
            height: 100px; /* Placeholder mediano para tablet 2 columnas */
            font-size: 2rem;
        }
    }
    
    @media (min-width: 1024px) {
        .producto-imagen-placeholder {
            height: 80px; /* Placeholder pequeño para desktop 3 columnas */
            font-size: 1.5rem;
        }
    }

    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-disponible {
        background: #e8f5e8;
        color: var(--color-exito);
    }

    .badge-agotado {
        background: #ffeaea;
        color: #dc3545;
    }

    .producto-descripcion {
        color: var(--color-texto-suave);
        font-size: 0.875rem;
        line-height: 1.4;
        padding: 0 1.25rem;
        flex-grow: 1;
        display: -webkit-box;
        -webkit-line-clamp: 2; /* Mostrar máximo 2 líneas */
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    @media (min-width: 768px) {
        .producto-descripcion {
            -webkit-line-clamp: 3; /* 3 líneas en tablet */
        }
    }
    
    @media (min-width: 1024px) {
        .producto-descripcion {
            -webkit-line-clamp: 2; /* 2 líneas en desktop */
        }
    }

    .producto-footer {
        padding: 0.75rem 1rem;
        background: #fafafa;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #e9ecef;
    }

    .producto-precio {
        font-size: 1.5rem; /* Precio grande para móvil */
        font-weight: 700;
        color: var(--color-secundario);
    }
    
    @media (min-width: 768px) {
        .producto-precio {
            font-size: 1.3rem; /* Precio mediano para tablet */
        }
    }
    
    @media (min-width: 1024px) {
        .producto-precio {
            font-size: 1.2rem; /* Precio pequeño para desktop */
        }
    }

    /* Estilos para precios con descuento */
    .precio-container {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .precio-original {
        font-size: 1rem;
        color: #999;
        text-decoration: line-through;
        font-weight: 500;
    }

    .precio-descuento {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--color-exito);
    }

    .descuento-badge {
        background: #ff4444;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 0.25rem;
    }

    @media (min-width: 768px) {
        .precio-descuento {
            font-size: 1.3rem;
        }
        .precio-original {
            font-size: 0.9rem;
        }
    }

    @media (min-width: 1024px) {
        .precio-descuento {
            font-size: 1.2rem;
        }
        .precio-original {
            font-size: 0.85rem;
        }
    }

    .add-btn {
        background: linear-gradient(135deg, #55aacc 0%, #4a9bc4 50%, #3d8bb8 100%);
        color: white;
        border: none;
        padding: 0.6rem 1rem; /* Botón grande para móvil */
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.9rem;
        box-shadow: 0 3px 8px rgba(85, 170, 204, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    @media (min-width: 768px) {
        .add-btn {
            padding: 0.5rem 0.8rem; /* Botón mediano para tablet */
            font-size: 0.85rem;
        }
    }
    
    @media (min-width: 1024px) {
        .add-btn {
            padding: 0.4rem 0.7rem; /* Botón pequeño para desktop */
            font-size: 0.8rem;
        }
    }

    .add-btn:hover {
        background: linear-gradient(135deg, #6bb6ff 0%, #5aa5e8 50%, #4d94d6 100%);
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 6px 15px rgba(85, 170, 204, 0.4);
    }

    .add-btn:active {
        transform: translateY(0) scale(0.98);
        box-shadow: 0 2px 5px rgba(85, 170, 204, 0.3);
    }

    /* Efecto de brillo al pasar el mouse */
    .add-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s ease;
    }

    .add-btn:hover::before {
        left: 100%;
    }

    .add-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .add-btn.added {
        background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%);
        animation: pulse 0.3s ease;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    /* Botón flotante del carrito */
    .cart-button-float {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: rgb(137, 107, 75);
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
        cursor: pointer;
        box-shadow: var(--sombra-fuerte);
        transition: all 0.3s ease;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cart-button-float:hover {
        transform: scale(1.1);
        background: rgb(137, 107, 75);
    }

    /* Botón de volver arriba (desktop) */
    .back-to-top {
        position: fixed;
        bottom: 5rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        background: rgb(137, 107, 75);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 1.2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 150;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .back-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .back-to-top:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.3);
    }

    .cart-counter {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        font-size: 0.75rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Modal para seleccionar mesa */
    .mesa-modal {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        backdrop-filter: blur(4px);
    }

    .mesa-panel {
        background: white;
        width: 95%;
        max-width: 500px;
        border-radius: 16px;
        padding: 1.5rem;
        max-height: 90vh;
        overflow-y: auto;
        animation: slideUp 0.3s ease;
    }

    .mesa-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
    }

    .mesa-header h3 {
        font-size: 1.5rem;
        color: var(--color-secundario);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .mesa-content p {
        margin-bottom: 1.5rem;
        color: var(--color-texto);
        font-size: 1rem;
    }

    .mesa-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 0.75rem;
        margin-bottom: 2rem;
    }

    .mesa-btn {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        color: var(--color-texto);
        padding: 0.75rem 0.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .mesa-btn:hover {
        background: var(--color-primario);
        border-color: var(--color-primario);
        color: white;
        transform: translateY(-2px);
    }

    .mesa-btn.selected {
        background: var(--color-acento);
        border-color: var(--color-acento);
        color: white;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .mesa-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn-aceptar, .btn-cancelar {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        font-size: 1rem;
    }

    .btn-aceptar {
        background: var(--color-acento);
        color: white;
    }

    .btn-aceptar:hover:not(:disabled) {
        background: #0056b3;
        transform: translateY(-2px);
    }

    .btn-aceptar:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .btn-cancelar {
        background: #6c757d;
        color: white;
    }

    .btn-cancelar:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    /* Modal del carrito mejorado */
    .cart-modal {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        backdrop-filter: blur(4px);
    }

    .cart-panel {
        background: white;
        width: 95%;
        max-width: 600px;
        border-radius: 16px;
        padding: 1.5rem;
        max-height: 90vh;
        overflow-y: auto;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
    }

    .cart-header h3 {
        font-size: 1.5rem;
        color: var(--color-secundario);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--color-texto-suave);
        transition: color 0.3s ease;
    }

    .btn-close:hover {
        color: var(--color-texto);
    }

    /* Resumen del carrito mejorado */
    .cart-summary {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border: 1px solid #e9ecef;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .cart-summary-header {
        background: var(--color-primario);
        color: white;
        padding: 1rem;
    }

    .cart-summary-header h4 {
        margin: 0;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .cart-items {
        max-height: 280px;
        overflow-y: auto;
        padding: 1rem;
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        margin-bottom: 0.75rem;
        background: white;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .cart-item:hover {
        box-shadow: var(--sombra-suave);
    }

    .cart-item-info {
        flex: 1;
    }

    .cart-item-name {
        font-weight: 600;
        color: var(--color-texto);
        margin-bottom: 0.25rem;
    }

    .cart-item-price {
        color: var(--color-texto-suave);
        font-size: 0.875rem;
    }

    .qty {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        background: #f8f9fa;
        border-radius: 24px;
        padding: 0.25rem;
    }

    .qty button {
        width: 32px;
        height: 32px;
        border: none;
        background: white;
        border-radius: 50%;
        cursor: pointer;
        font-weight: bold;
        color: var(--color-primario);
        box-shadow: var(--sombra-suave);
        transition: all 0.2s ease;
    }

    .qty button:hover {
        background: var(--color-primario);
        color: white;
        transform: scale(1.1);
    }

    .qty span {
        min-width: 32px;
        text-align: center;
        font-weight: 600;
    }

    .cart-summary-footer {
        padding: 1rem;
        background: #f8f9fa;
        border-top: 2px solid #e9ecef;
    }

    .cart-total-amount {
        text-align: right;
        font-weight: 700;
        font-size: 1.5rem;
        color: var(--color-exito);
    }

    .cart-empty {
        text-align: center;
        padding: 3rem;
        color: var(--color-texto-suave);
    }

    .cart-empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* Formulario del pedido */
    .checkout-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-group label {
        font-weight: 600;
        color: var(--color-texto);
        font-size: 0.9rem;
    }

    .form-group input,
    .form-group select {
        padding: 0.75rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--color-primario);
        box-shadow: 0 0 0 3px rgba(161, 134, 111, 0.1);
    }

    .btn-confirmar {
        background: var(--color-acento);
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 1rem;
    }

    .btn-confirmar:hover:not(:disabled) {
        background: #0056b3;
        transform: translateY(-2px);
        box-shadow: var(--sombra-media);
    }

    .btn-confirmar:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    /* Notificación toast mejorada */
    .toast-notification {
        position: fixed;
        top: 2rem;
        right: 2rem;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: var(--sombra-fuerte);
        z-index: 3000;
        font-weight: 600;
        opacity: 0;
        transform: translateX(100px);
        transition: all 0.4s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        max-width: 400px;
        word-wrap: break-word;
    }

    .toast-notification.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast-success {
        background: var(--color-exito);
    }

    .toast-warning {
        background: #ff9800;
        color: white;
    }

    .toast-error {
        background: #f44336;
    }

    /* Responsive */
    @media (max-width: 768px) {
        /* Ajustes generales */
        body {
            font-size: 14px;
            margin: 0;
            padding: 0;
            width: 100%;
            overflow-x: hidden;
        }
        
        * {
            box-sizing: border-box;
        }

        /* Header móvil optimizado */
        .menu-header {
            padding: 1rem 0;
            transform: scale(1);
            transform-origin: top center;
            width: 100%;
            margin: 0;
        }

        .menu-header-content {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }

        .menu-title {
            font-size: 1.3rem;
            margin-bottom: 0.25rem;
        }

        .menu-subtitle {
            font-size: 0.75rem;
        }

        /* Filtros móvil */
        .menu-filters {
            padding: 0.5rem 1rem;
            margin: 0 1rem 1rem;
            gap: 0.4rem;
        }
        
        .menu-filters button {
            padding: 0.35rem 0.7rem;
            font-size: 0.8rem;
        }

        /* Contenedor principal */
        .menu-container {
            padding: 1rem 0.75rem;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }

        /* Secciones de categoría */
        .categoria-seccion {
            margin-bottom: 2rem;
        }

        .categoria-header {
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .categoria-titulo {
            font-size: 1.1rem;
            margin: 0;
        }


        /* Grid responsive ya definido arriba con media queries */

        /* Estilos específicos para móvil - solo ajustes menores */
        .producto-card {
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .producto-header {
            padding: 1rem 1rem 0.75rem;
        }

        .producto-nombre {
            font-size: 1.1rem;
        }

        .producto-footer {
            flex-direction: column;
            gap: 0.75rem;
            align-items: stretch;
        }

        .add-btn {
            width: 100%;
        }

        /* Botón flotante del carrito */
        .cart-button-float {
            bottom: 1rem;
            right: 1rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .cart-button-float .cart-count {
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
        }

        /* Modal del carrito móvil */
        .cart-modal {
            width: 100%;
            height: 100%;
            max-height: 100vh;
            border-radius: 0;
        }

        .cart-content {
            padding: 1rem;
        }

        .cart-header h3 {
            font-size: 1.2rem;
        }

        .cart-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .cart-item-name {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .cart-item-price {
            font-size: 0.8rem;
            color: var(--color-texto-suave);
        }

        .qty button {
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
        }

        .qty span {
            font-size: 0.9rem;
            min-width: 30px;
        }

        .cart-summary {
            padding: 1rem;
            margin-top: 1rem;
        }

        .cart-summary-header h4 {
            font-size: 0.9rem;
        }

        .total-box {
            font-size: 1.1rem;
            padding: 0.75rem;
        }

        .cart-actions {
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            font-size: 0.9rem;
        }

        /* Botón de llamar mozo móvil */
        .llamar-mozo-btn {
            position: fixed;
            bottom: 5rem;
            right: 1rem;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            z-index: 150;
        }

        /* Toast notifications móvil */
        .toast {
            bottom: 1rem;
            left: 1rem;
            right: 1rem;
            width: auto;
            font-size: 0.85rem;
        }

        /* Mejoras de usabilidad táctil */
        .categoria-btn,
        .add-btn,
        .btn,
        .cart-button-float,
        .llamar-mozo-btn {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        /* Animaciones suaves para móvil */
        .producto-card {
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
        }

        /* Mejoras de accesibilidad */
        .add-btn:focus,
        .categoria-btn:focus {
            outline: 2px solid var(--color-primario);
            outline-offset: 2px;
        }

        /* Optimización de scroll */
        .menu-container {
            -webkit-overflow-scrolling: touch;
        }

        /* Mejoras visuales para pantallas pequeñas */
        .producto-card {
            margin-bottom: 0.5rem;
        }

        .categoria-seccion:last-child {
            margin-bottom: 6rem; /* Espacio para botones flotantes */
        }


        /* Botón de volver arriba */
        .back-to-top {
            position: fixed;
            bottom: 7rem;
            right: 1rem;
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--color-primario) 0%, rgb(137, 107, 75) 100%);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 150;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.3);
        }
    }
    </style>
</head>
<body>
    <!-- Header del menú -->
    <div class="menu-header">
        <h2>🍽️ Nuestro Menú</h2>
        <p>Explora nuestra deliciosa selección de platos y bebidas</p>
    </div>

    <!-- Navegación de categorías -->
    <div class="menu-filters">
        <button class="active" onclick="mostrarTodas()">🍴 Todas</button>
        <?php foreach ($categoriasOrdenadas as $categoria => $items): ?>
            <button onclick="filtrarCategoria('<?= htmlspecialchars($categoria) ?>')">
                <?= $iconosCategorias[$categoria] ?? '🍴' ?> <?= htmlspecialchars($categoria) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Contenedor principal del menú -->
    <div class="menu-container">
        <?php foreach ($categoriasOrdenadas as $categoria => $itemsCategoria): ?>
            <section class="categoria-seccion" data-categoria="<?= htmlspecialchars($categoria) ?>">
                <div class="categoria-header">
                    <span class="categoria-icono"><?= $iconosCategorias[$categoria] ?? '🍴' ?></span>
                    <h2 class="categoria-titulo"><?= htmlspecialchars($categoria) ?></h2>
                </div>
                
                <div class="productos-grid">
                    <?php foreach ($itemsCategoria as $item): ?>
                        <div class="producto-card">
                            <div class="producto-header">
                                <h3 class="producto-nombre"><?= htmlspecialchars($item['nombre']) ?></h3>
                                <div class="producto-badges">
                                    <?php if (!empty($item['disponibilidad'])): ?>
                                        <span class="badge badge-disponible">✓ Disponible</span>
                                    <?php else: ?>
                                        <span class="badge badge-agotado">✗ Agotado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Imagen del producto -->
                            <div class="producto-imagen">
                                <?php if (!empty($item['imagen_url'])): ?>
                                    <img src="<?= htmlspecialchars($item['imagen_url']) ?>" 
                                         alt="<?= htmlspecialchars($item['nombre']) ?>"
                                         class="producto-img"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="producto-imagen-placeholder" style="display: none;">
                                        🍽️
                                    </div>
                                <?php else: ?>
                                    <div class="producto-imagen-placeholder">
                                        🍽️
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($item['descripcion'])): ?>
                                <p class="producto-descripcion" title="<?= htmlspecialchars($item['descripcion']) ?>"><?= htmlspecialchars($item['descripcion']) ?></p>
                            <?php endif; ?>
                            
                            <div class="producto-footer">
                                <?php 
                                $precioOriginal = $item['precio'];
                                $descuento = $item['descuento'] ?? 0;
                                $precioConDescuento = $precioOriginal - $descuento;
                                $tieneDescuento = $descuento > 0;
                                ?>
                                
                                <?php if ($tieneDescuento): ?>
                                    <div class="precio-container">
                                        <span class="precio-original">$<?= number_format($precioOriginal, 2) ?></span>
                                        <span class="precio-descuento">$<?= number_format($precioConDescuento, 2) ?></span>
                                        <span class="descuento-badge">-<?= number_format(($descuento / $precioOriginal) * 100, 0) ?>%</span>
                                    </div>
                                <?php else: ?>
                                    <span class="producto-precio">$<?= number_format($precioOriginal, 2) ?></span>
                                <?php endif; ?>
                                
                                <?php if (!empty($item['disponibilidad'])): ?>
                                    <button class="add-btn" 
                                            data-id="<?= $item['id_item'] ?>" 
                                            data-nombre="<?= htmlspecialchars($item['nombre']) ?>" 
                                            data-precio="<?= number_format($tieneDescuento ? $precioConDescuento : $precioOriginal, 2, '.', '') ?>">
                                        <span>➕</span>
                                        <span>Agregar</span>
                                    </button>
                                <?php else: ?>
                                    <button class="add-btn" disabled>
                                        <span>✗</span>
                                        <span>Agotado</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <!-- Botón flotante del carrito -->
    <button id="btn-open-cart" class="cart-button-float">
        🛒
        <span id="cart-counter" class="cart-counter" style="display: none;">0</span>
    </button>

    <!-- Botón de volver arriba -->
    <button id="back-to-top" class="back-to-top" title="Volver arriba">
        ↑
    </button>

    <!-- Modal para seleccionar mesa -->
    <div id="mesa-modal" class="mesa-modal" aria-hidden="true">
        <div class="mesa-panel">
            <div class="mesa-header">
                <h3>🔔 Llamar Mozo</h3>
                <button id="btn-close-mesa" class="btn-close">✕</button>
            </div>
            <div class="mesa-content">
                <p>Selecciona tu mesa para llamar al mozo:</p>
                <div class="mesa-grid">
                    <?php for($i = 1; $i <= 15; $i++): ?>
                    <button class="mesa-btn" data-mesa="<?= $i ?>">Mesa <?= $i ?></button>
                    <?php endfor; ?>
                </div>
                <div class="mesa-actions">
                    <button id="btn-aceptar-mesa" class="btn-aceptar" disabled>Aceptar</button>
                    <button id="btn-cancelar-mesa" class="btn-cancelar">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal del carrito -->
    <div id="cart-modal" class="cart-modal" aria-hidden="true">
        <div class="cart-panel">
            <div class="cart-header">
                <h3>🛒 Tu Pedido</h3>
                <button id="btn-close-cart" class="btn-close">✕</button>
            </div>
            
            <!-- Resumen del carrito -->
            <div class="cart-summary">
                <div class="cart-summary-header">
                    <h4>📋 Resumen del pedido</h4>
                </div>
                <div id="cart-items" class="cart-items"></div>
                <div class="cart-summary-footer">
                    <div id="cart-total" class="cart-total-amount">Total: $0.00</div>
                </div>
            </div>
            
            <!-- Formulario de pedido -->
            <form id="checkout-form" class="checkout-form">
                <div class="form-group">
                    <label>Modalidad de consumo:</label>
                    <select id="modo-consumo" name="modo_consumo" required>
                        <option value="">Seleccionar...</option>
                        <option value="stay">🪑 Consumir en el local</option>
                        <option value="takeaway">📦 Para llevar</option>
                    </select>
                </div>
                
                <div id="mesa-field-container">
                    <!-- Campo mesa desde QR (visible cuando viene por QR) -->
                    <div id="mesa-qr-field" class="form-group" style="display:none;">
                        <label>Mesa asignada:</label>
                        <div id="mesa-qr-info" style="background:#e8f5e8;border:2px solid #c8e6c9;padding:1rem;border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
                            <span id="mesa-qr-text">✅ Mesa X (desde QR)</span>
                            <button type="button" id="btn-cambiar-mesa" style="background:none;border:1px solid #28a745;color:#28a745;padding:0.25rem 0.5rem;border-radius:4px;cursor:pointer;font-size:0.875rem;">
                                Cambiar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Campo mesa manual -->
                    <div id="mesa-manual-field" class="form-group" style="display:none;">
                        <label>Número de mesa:</label>
                        <select id="numero-mesa" name="numero_mesa">
                            <option value="">Seleccionar mesa...</option>
                            <?php for($i = 1; $i <= 15; $i++): ?>
                            <option value="<?= $i ?>">Mesa <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Nombre completo:</label>
                    <input type="text" id="nombre-completo" name="nombre_completo" placeholder="Ingrese su nombre completo" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="email" name="email" placeholder="ejemplo@correo.com" required>
                </div>
                
                <div class="form-group">
                    <label>Forma de pago:</label>
                    <select id="forma-pago" name="forma_pago" required>
                        <option value="">Seleccionar...</option>
                        <option value="efectivo">💵 Efectivo</option>
                        <option value="tarjeta">💳 Tarjeta crédito/débito</option>
                    </select>
                </div>
                
                <button type="submit" id="btn-confirmar" class="btn-confirmar" disabled>
                    Confirmar Pedido
                </button>
            </form>
        </div>
    </div>

    <script>
    // Utilidad simple de carrito en localStorage
    const CART_KEY = 'cliente_cart';
    const loadCart = () => JSON.parse(localStorage.getItem(CART_KEY) || '[]');
    const saveCart = (cart) => localStorage.setItem(CART_KEY, JSON.stringify(cart));

    // Variables globales para QR
    let mesaFromQR = null;
    let isQRMode = false;

    // Función para filtrar por categoría
    function filtrarCategoria(categoria) {
        const secciones = document.querySelectorAll('.categoria-seccion');
        const botones = document.querySelectorAll('.menu-filters button');
        
        // Actualizar botones activos
        botones.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.includes(categoria)) {
                btn.classList.add('active');
                // Scroll automático al botón activo
                if (typeof scrollToCategory === 'function') {
                    scrollToCategory(btn);
                }
            }
        });
        
        // Mostrar solo la categoría seleccionada
        secciones.forEach(seccion => {
            if (seccion.dataset.categoria === categoria) {
                seccion.style.display = 'block';
                // Scroll suave a la sección
                setTimeout(() => {
                    seccion.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            } else {
                seccion.style.display = 'none';
            }
        });
    }

    function mostrarTodas() {
        const secciones = document.querySelectorAll('.categoria-seccion');
        const botones = document.querySelectorAll('.menu-filters button');
        
        // Actualizar botones activos
        botones.forEach(btn => btn.classList.remove('active'));
        botones[0].classList.add('active');
        // Scroll automático al botón "Todas"
        if (typeof scrollToCategory === 'function') {
            scrollToCategory(botones[0]);
        }
        
        // Mostrar todas las secciones
        secciones.forEach(seccion => {
            seccion.style.display = 'block';
        });
        
        // Scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Función para detectar si viene desde QR
    function detectQRMode() {
        const urlParams = new URLSearchParams(window.location.search);
        const mesaParam = urlParams.get('mesa') || urlParams.get('qr');
        
        console.log('DetectQRMode - URL:', window.location.href);
        console.log('DetectQRMode - mesaParam:', mesaParam);
        
        if (mesaParam && !isNaN(mesaParam)) {
            mesaFromQR = parseInt(mesaParam);
            isQRMode = true;
            console.log('DetectQRMode - Modo QR activado, mesa:', mesaFromQR);
            setupQRMode();
        } else {
            console.log('DetectQRMode - Modo manual activado');
            setupManualMode();
        }
        
        // Mostrar botón llamar mozo siempre (tanto en modo QR como manual)
        const btnLlamarMozo = document.getElementById('nav-llamar-mozo');
        if (btnLlamarMozo) {
            btnLlamarMozo.style.display = 'flex';
        }
    }

    // Configurar modo QR
    function setupQRMode() {
        const qrField = document.getElementById('mesa-qr-field');
        const manualField = document.getElementById('mesa-manual-field');
        const qrText = document.getElementById('mesa-qr-text');
        
        qrField.style.display = 'block';
        manualField.style.display = 'none';
        qrText.textContent = `✅ Mesa ${mesaFromQR} (desde QR)`;
        
        document.getElementById('numero-mesa').value = mesaFromQR;
    }

    // Configurar modo manual
    function setupManualMode() {
        const qrField = document.getElementById('mesa-qr-field');
        const manualField = document.getElementById('mesa-manual-field');
        
        qrField.style.display = 'none';
        manualField.style.display = 'block';
    }

    // Cambiar a modo manual desde QR
    function cambiarMesaFromQR() {
        isQRMode = false;
        setupManualMode();
        document.getElementById('numero-mesa').value = '';
        validateFormQR();
    }

    // Función para llamar al mozo
    async function llamarMozo(numeroMesa = null) {
        let mesaParaLlamar = numeroMesa || mesaFromQR;
        
        console.log('LlamarMozo - numeroMesa:', numeroMesa);
        console.log('LlamarMozo - mesaFromQR:', mesaFromQR);
        console.log('LlamarMozo - mesaParaLlamar:', mesaParaLlamar);
        
        if (!mesaParaLlamar) {
            // Mostrar popup para seleccionar mesa
            console.log('LlamarMozo - Mostrando popup porque no hay mesa');
            mostrarPopupMesa();
            return;
        }
        
        console.log('LlamarMozo - Llamando directamente al mozo de la mesa:', mesaParaLlamar);

        const btnLlamarMozo = document.getElementById('nav-llamar-mozo');
        const originalText = btnLlamarMozo.innerHTML;
        
        // Cambiar estado del botón
        btnLlamarMozo.classList.add('llamando');
        btnLlamarMozo.innerHTML = '⏳ Llamando...';
        btnLlamarMozo.disabled = true;

        try {
            console.log('Enviando petición a:', '<?= $base_url ?>/index.php?route=llamar-mozo');
            console.log('Datos a enviar:', { numero_mesa: mesaParaLlamar });
            
            const response = await fetch('<?= $base_url ?>/index.php?route=llamar-mozo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    numero_mesa: mesaParaLlamar
                })
            });
            
            console.log('Respuesta recibida:', response);
            console.log('Status:', response.status);

            const result = await response.json();
            console.log('Resultado del servidor:', result);

            if (result.success) {
                showToast('✅ ' + result.message, 'success');
                
                // Deshabilitar el botón por 3 minutos (180 segundos)
                setTimeout(() => {
                    btnLlamarMozo.classList.remove('llamando');
                    btnLlamarMozo.innerHTML = originalText;
                    btnLlamarMozo.disabled = false;
                }, 180000); // 3 minutos
            } else {
                // Manejar diferentes tipos de mensajes
                if (result.type === 'warning') {
                    showToast('⚠️ ' + result.message, 'warning');
                    // Para advertencias, no lanzar error, solo restaurar botón
                    btnLlamarMozo.classList.remove('llamando');
                    btnLlamarMozo.innerHTML = originalText;
                    btnLlamarMozo.disabled = false;
                    return; // Salir de la función sin lanzar error
                } else {
                    showToast('❌ ' + result.message, 'error');
                    throw new Error(result.message || 'Error al enviar el llamado');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            
            // Solo mostrar error técnico en consola, no al usuario
            if (error.message.includes('Unexpected token')) {
                showToast('❌ Error de conexión. Por favor, intente nuevamente.', 'error');
            } else {
                showToast('❌ Error al llamar al mozo: ' + error.message, 'error');
            }
            
            // Restaurar botón
            btnLlamarMozo.classList.remove('llamando');
            btnLlamarMozo.innerHTML = originalText;
            btnLlamarMozo.disabled = false;
        }
    }

    // Función para mostrar popup de selección de mesa
    function mostrarPopupMesa() {
        const modal = document.getElementById('mesa-modal');
        modal.style.display = 'flex';
        
        // Limpiar selección anterior
        document.querySelectorAll('.mesa-btn').forEach(btn => {
            btn.classList.remove('selected');
        });
        document.getElementById('btn-aceptar-mesa').disabled = true;
    }

    // Función para cerrar popup de mesa
    function cerrarPopupMesa() {
        const modal = document.getElementById('mesa-modal');
        modal.style.display = 'none';
    }

    function addToCart(item) {
        const cart = loadCart();
        const existing = cart.find(i => i.id === item.id);
        if (existing) { 
            existing.qty += 1; 
        } else { 
            cart.push({...item, qty: 1}); 
        }
        saveCart(cart);
        renderCart();
        
        // Mostrar toast de confirmación
        showToast(`✅ ${item.nombre} agregado al carrito`);
        
        // Animación del botón
        const btn = document.querySelector(`[data-id="${item.id}"]`);
        if (btn) {
            btn.classList.add('added');
            setTimeout(() => btn.classList.remove('added'), 300);
        }
        
        updateCartCounter();
    }

    function updateQty(id, delta) {
        const cart = loadCart();
        const it = cart.find(i => i.id === id);
        if (!it) return; 
        it.qty += delta; 
        if (it.qty <= 0) {
            const idx = cart.findIndex(i => i.id === id);
            cart.splice(idx, 1);
        }
        saveCart(cart); 
        renderCart();
        updateCartCounter();
    }

    function renderCart() {
        const cart = loadCart();
        const list = document.getElementById('cart-items');
        const totalBox = document.getElementById('cart-total');
        
        if (cart.length === 0) {
            list.innerHTML = `
                <div class="cart-empty">
                    <div class="cart-empty-icon">🛒</div>
                    <div>Tu carrito está vacío</div>
                    <small style="color: #999;">Agrega productos para continuar</small>
                </div>
            `;
            totalBox.textContent = 'Total: $0.00';
        } else {
            list.innerHTML = cart.map(i => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${i.nombre}</div>
                        <div class="cart-item-price">$${Number(i.precio).toFixed(2)} c/u</div>
                    </div>
                    <div class="qty">
                        <button onclick="updateQty(${i.id}, -1)">−</button>
                        <span>${i.qty}</span>
                        <button onclick="updateQty(${i.id}, 1)">+</button>
                    </div>
                </div>
            `).join('');
            const total = cart.reduce((t, i) => t + i.qty * Number(i.precio), 0);
            totalBox.textContent = `Total: $${total.toFixed(2)}`;
        }
        
        setTimeout(validateFormQR, 100);
    }

    // Función para mostrar notificación toast
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        
        // Agregar clase según el tipo
        if (type === 'warning') {
            toast.classList.add('toast-warning');
        } else if (type === 'error') {
            toast.classList.add('toast-error');
        } else {
            toast.classList.add('toast-success');
        }
        
        toast.innerHTML = message;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Duración diferente según el tipo
        const duration = type === 'warning' ? 4000 : 2500;
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 400);
        }, duration);
    }

    // Función para actualizar contador del carrito
    function updateCartCounter() {
        const cart = loadCart();
        const counter = document.getElementById('cart-counter');
        const totalItems = cart.reduce((total, item) => total + item.qty, 0);
        
        if (totalItems > 0) {
            counter.textContent = totalItems;
            counter.style.display = 'flex';
        } else {
            counter.style.display = 'none';
        }
    }

    // Validación del formulario para modo QR
    function validateFormQR() {
        const modoConsumo = document.getElementById('modo-consumo').value;
        const nombreCompleto = document.getElementById('nombre-completo').value.trim();
        const email = document.getElementById('email').value.trim();
        const formaPago = document.getElementById('forma-pago').value;
        
        console.log('=== VALIDANDO FORMULARIO ===');
        console.log('Modo consumo:', modoConsumo);
        console.log('Nombre completo:', nombreCompleto);
        console.log('Email:', email);
        console.log('Forma pago:', formaPago);
        
        let isValid = true;
        
        if (!modoConsumo || !nombreCompleto || !email || !formaPago) {
            console.log('❌ Faltan campos obligatorios');
            isValid = false;
        }
        
        if (modoConsumo === 'stay') {
            let mesaValida = false;
            
            if (isQRMode && mesaFromQR) {
                console.log('✅ Mesa desde QR:', mesaFromQR);
                mesaValida = true;
            } else {
                const numeroMesa = document.getElementById('numero-mesa').value;
                console.log('Número mesa manual:', numeroMesa);
                mesaValida = !!numeroMesa;
            }
            
            if (!mesaValida) {
                console.log('❌ Mesa no válida');
                isValid = false;
            }
        }
        
        const cart = loadCart();
        console.log('Carrito:', cart);
        if (cart.length === 0) {
            console.log('❌ Carrito vacío');
            isValid = false;
        }
        
        const btnConfirmar = document.getElementById('btn-confirmar');
        btnConfirmar.disabled = !isValid;
        console.log('Botón habilitado:', !isValid ? 'NO' : 'SÍ');
    }

    // Inicialización
    document.addEventListener('DOMContentLoaded', () => {
        detectQRMode();
        updateCartCounter();
        
        // Inicializar filtros
        const secciones = document.querySelectorAll('.categoria-seccion');
        
        // Asegurar que todas las secciones estén visibles inicialmente
        secciones.forEach(seccion => {
            seccion.style.display = 'block';
        });
        
        // Botón de volver arriba
        const backToTopBtn = document.getElementById('back-to-top');
        
        // Mostrar/ocultar botón según scroll
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });
        
        // Scroll suave al hacer clic
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Scroll automático de categorías al seleccionar
        function scrollToCategory(button) {
            const container = document.querySelector('.menu-filters');
            if (container) {
                button.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }
        }
        
        // Agregar productos al carrito
        document.querySelectorAll('.add-btn:not(:disabled)').forEach(btn => {
            btn.addEventListener('click', () => {
                addToCart({ 
                    id: Number(btn.dataset.id), 
                    nombre: btn.dataset.nombre, 
                    precio: btn.dataset.precio 
                });
            });
        });
        
        // Modal del carrito
        const modal = document.getElementById('cart-modal');
        document.getElementById('btn-open-cart').addEventListener('click', () => { 
            modal.style.display = 'flex'; 
            renderCart(); 
            validateFormQR();
        });
        document.getElementById('btn-close-cart').addEventListener('click', () => { 
            modal.style.display = 'none'; 
        });
        
        // Botón cambiar mesa QR
        const btnCambiarMesa = document.getElementById('btn-cambiar-mesa');
        if (btnCambiarMesa) {
            btnCambiarMesa.addEventListener('click', cambiarMesaFromQR);
        }
        
        // Botón llamar mozo en el nav
        const btnLlamarMozo = document.getElementById('nav-llamar-mozo');
        if (btnLlamarMozo) {
            btnLlamarMozo.addEventListener('click', () => llamarMozo());
        }
        
        // Modal de selección de mesa
        const mesaModal = document.getElementById('mesa-modal');
        const btnCloseMesa = document.getElementById('btn-close-mesa');
        const btnCancelarMesa = document.getElementById('btn-cancelar-mesa');
        const btnAceptarMesa = document.getElementById('btn-aceptar-mesa');
        
        if (btnCloseMesa) {
            btnCloseMesa.addEventListener('click', cerrarPopupMesa);
        }
        
        if (btnCancelarMesa) {
            btnCancelarMesa.addEventListener('click', cerrarPopupMesa);
        }
        
        if (btnAceptarMesa) {
            btnAceptarMesa.addEventListener('click', () => {
                const mesaSeleccionada = document.querySelector('.mesa-btn.selected');
                if (mesaSeleccionada) {
                    const numeroMesa = parseInt(mesaSeleccionada.dataset.mesa);
                    cerrarPopupMesa();
                    llamarMozo(numeroMesa);
                }
            });
        }
        
        // Selección de mesa en el popup
        document.querySelectorAll('.mesa-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Remover selección anterior
                document.querySelectorAll('.mesa-btn').forEach(b => b.classList.remove('selected'));
                // Seleccionar la mesa actual
                btn.classList.add('selected');
                // Habilitar botón aceptar
                document.getElementById('btn-aceptar-mesa').disabled = false;
            });
        });
        
        // Cerrar modal al hacer clic fuera
        if (mesaModal) {
            mesaModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarPopupMesa();
                }
            });
        }
        
        // Lógica de campos de mesa
        document.getElementById('modo-consumo').addEventListener('change', function() {
            if (this.value === 'stay') {
                if (!isQRMode) {
                    document.getElementById('mesa-manual-field').style.display = 'block';
                    document.getElementById('numero-mesa').required = true;
                }
            } else {
                document.getElementById('mesa-qr-field').style.display = 'none';
                document.getElementById('mesa-manual-field').style.display = 'none';
                document.getElementById('numero-mesa').required = false;
                document.getElementById('numero-mesa').value = '';
            }
            validateFormQR();
        });
        
        // Validación en tiempo real
        const formFields = ['modo-consumo', 'numero-mesa', 'nombre-completo', 'email', 'forma-pago'];
        formFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', validateFormQR);
                field.addEventListener('change', validateFormQR);
            }
        });
        
        // Envío del formulario
        document.getElementById('checkout-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('=== FORMULARIO ENVIADO ===');
            
            const cart = loadCart();
            console.log('Carrito:', cart);
            if (cart.length === 0) {
                alert('Tu carrito está vacío');
                return;
            }
            
            let mesaFinal = null;
            const modoConsumo = document.getElementById('modo-consumo').value;
            
            if (modoConsumo === 'stay') {
                if (isQRMode && mesaFromQR) {
                    mesaFinal = mesaFromQR;
                } else {
                    mesaFinal = document.getElementById('numero-mesa').value;
                }
            }
            
            console.log('Mesa final para pedido:', mesaFinal);
            console.log('Modo consumo:', modoConsumo);
            
            // Preparar datos del pedido
            const pedidoData = {
                cliente_nombre: document.getElementById('nombre-completo').value,
                cliente_email: document.getElementById('email').value,
                modo_consumo: modoConsumo,
                forma_pago: document.getElementById('forma-pago').value,
                observaciones: '', // No hay campo de observaciones en el formulario del cliente
                items: cart.map(item => ({
                    id_item: item.id,
                    cantidad: item.cantidad,
                    precio_unitario: item.precio,
                    detalle: item.detalle || ''
                }))
            };
            
            console.log('Datos del pedido:', pedidoData);
            
            // Solo incluir mesa si es modo 'stay'
            if (modoConsumo === 'stay' && mesaFinal) {
                pedidoData.id_mesa = parseInt(mesaFinal);
            }
            
            try {
                // Enviar pedido al servidor
                const response = await fetch('<?= $base_url ?>/index.php?route=cliente-pedido', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(pedidoData)
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    // Mostrar mensaje de éxito
                    showToast(`✅ ¡Pedido confirmado! ID: ${result.pedido_id}. En breve nos comunicaremos contigo.`);
                    
                    // Limpiar carrito y cerrar modal
                    localStorage.removeItem(CART_KEY);
                    modal.style.display = 'none';
                    this.reset();
                    
                    if (isQRMode) {
                        setupQRMode();
                    } else {
                        document.getElementById('mesa-manual-field').style.display = 'none';
                    }
                    
                    updateCartCounter();
                    validateFormQR();
                } else {
                    // Mostrar error
                    showToast(`❌ Error: ${result.error || 'No se pudo crear el pedido'}`, 'error');
                }
            } catch (error) {
                console.error('Error al enviar pedido:', error);
                showToast('❌ Error de conexión. Intenta nuevamente.', 'error');
            }
        });
        
        // Cerrar modal al hacer clic fuera
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>