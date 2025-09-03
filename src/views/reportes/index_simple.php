<?php
require_once __DIR__ . '/../../config/helpers.php';

// La sesión ya está iniciada desde public/index.php
// Verificar autenticación y permisos
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
    header('Location: ' . url('unauthorized'));
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reportes - Sistema Comanda</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        .info { background: #e6ffe6; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .links { margin: 1rem 0; }
        .links a { display: block; margin: 0.5rem 0; padding: 0.5rem; background: #f0f0f0; text-decoration: none; border-radius: 4px; }
        .links a:hover { background: #e0e0e0; }
    </style>
</head>
<body>
    <h1>📊 Sistema de Reportes</h1>
    
    <div class="info">
        <strong>Acceso a reportes - ✅ OK</strong><br>
        Usuario: <?= htmlspecialchars($_SESSION['user']['nombre']) ?><br>
        Rol: <?= htmlspecialchars($_SESSION['user']['rol']) ?>
    </div>
    
    <div class="links">
        <a href="<?= url('reportes/platos-mas-vendidos') ?>">🍽️ Ver Platos Más Vendidos</a>
        <a href="<?= url('reportes/ventas-categoria') ?>">📊 Ver Ventas por Categoría</a>
        <a href="<?= url('reportes/rendimiento-mozos') ?>">👥 Ver Rendimiento de Mozos</a>
        <a href="<?= url('reportes/propina') ?>">💰 Ver Propinas</a>
        <a href="<?= url('reportes/recaudacion') ?>">💵 Ver Recaudación</a>
        <a href="<?= url('home') ?>">🏠 Volver al inicio</a>
    </div>
</body>
</html>
