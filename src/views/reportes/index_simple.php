<?php
session_start();

// Verificar autenticación y permisos
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
    echo "Acceso denegado - Solo administradores pueden ver esta página";
    exit;
}

echo "Acceso a reportes - OK<br>";
echo "Usuario: " . $_SESSION['user']['nombre'] . "<br>";
echo "Rol: " . $_SESSION['user']['rol'] . "<br>";
echo "<a href='platos_mas_vendidos.php'>Ver Platos Más Vendidos</a><br>";
echo "<a href='ventas_por_categoria.php'>Ver Ventas por Categoría</a><br>";
echo "<a href='rendimiento_mozos.php'>Ver Rendimiento de Mozos</a><br>";
echo "<a href='../index.php'>Volver al inicio</a>";
?>
