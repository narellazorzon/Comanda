<?php
// Archivo de prueba para verificar el flujo de edición
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\Usuario;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Test de Edición de Mozos</h1>";

// 1. Verificar que existe al menos un mozo
$mozos = Usuario::allByRole('mozo');
echo "<h2>1. Mozos en el sistema:</h2>";
if (empty($mozos)) {
    echo "<p style='color: red;'>❌ No hay mozos en el sistema</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Estado</th><th>Test Edición</th></tr>";
    foreach ($mozos as $m) {
        echo "<tr>";
        echo "<td>{$m['id_usuario']}</td>";
        echo "<td>{$m['nombre']} {$m['apellido']}</td>";
        echo "<td>{$m['email']}</td>";
        echo "<td>{$m['estado']}</td>";
        echo "<td><a href='alta_mozo.php?id={$m['id_usuario']}' target='_blank'>Probar Edición</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Simular carga de un mozo específico
if (isset($_GET['test_id'])) {
    $id = (int) $_GET['test_id'];
    echo "<h2>2. Test de carga del mozo ID: $id</h2>";
    
    try {
        $mozo = Usuario::find($id);
        if ($mozo) {
            if ($mozo['rol'] === 'mozo') {
                echo "<p style='color: green;'>✅ Mozo cargado correctamente:</p>";
                echo "<pre>" . print_r($mozo, true) . "</pre>";
            } else {
                echo "<p style='color: orange;'>⚠️ Usuario encontrado pero no es mozo. Rol: {$mozo['rol']}</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Mozo no encontrado</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al cargar mozo: " . $e->getMessage() . "</p>";
    }
}

// 3. Enlaces de prueba
echo "<h2>3. Enlaces de prueba:</h2>";
if (!empty($mozos)) {
    $primerMozo = $mozos[0];
    echo "<a href='?test_id={$primerMozo['id_usuario']}'>Test cargar mozo ID {$primerMozo['id_usuario']}</a><br>";
    echo "<a href='alta_mozo.php?id={$primerMozo['id_usuario']}' target='_blank'>Ir a edición real</a><br>";
}
echo "<a href='cme_mozos.php'>Volver a gestión de mozos</a>";
?>
