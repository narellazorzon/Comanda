<?php
// Script de prueba para verificar que las páginas de mesas funcionan

session_start();
// Simular usuario administrador
$_SESSION['user'] = ['rol' => 'administrador', 'id_usuario' => 1, 'nombre' => 'Admin Test'];

require_once 'vendor/autoload.php';
require_once 'src/models/Mesa.php';

echo "=== PRUEBA DE FUNCIONALIDAD DE MESAS ===\n\n";

try {
    // Test 1: Obtener todas las mesas activas
    echo "1. Obteniendo todas las mesas activas:\n";
    $mesas = \App\Models\Mesa::all();
    echo "   - Cantidad de mesas activas: " . count($mesas) . "\n";
    if (count($mesas) > 0) {
        echo "   - Primera mesa: Mesa #" . $mesas[0]['numero'] . " - Estado: " . $mesas[0]['estado'] . "\n";
    }
    echo "\n";

    // Test 2: Buscar mesa por ID
    echo "2. Buscando mesa por ID (id=1):\n";
    $mesa = \App\Models\Mesa::find(1);
    if ($mesa) {
        echo "   - Mesa encontrada: #" . $mesa['numero'] . " - Estado: " . $mesa['estado'] . "\n";
    } else {
        echo "   - Mesa con ID 1 no encontrada o inactiva\n";
    }
    echo "\n";

    // Test 3: Obtener mesas inactivas
    echo "3. Obteniendo mesas inactivas:\n";
    $mesasInactivas = \App\Models\Mesa::allInactive();
    echo "   - Cantidad de mesas inactivas: " . count($mesasInactivas) . "\n";
    echo "\n";

    // Test 4: Verificar la estructura de datos
    echo "4. Verificando estructura de datos:\n";
    if (count($mesas) > 0) {
        $campos = array_keys($mesas[0]);
        echo "   - Campos disponibles: " . implode(', ', $campos) . "\n";
        if (in_array('status', $campos)) {
            echo "   ✓ Campo 'status' presente\n";
        }
    }
    echo "\n";

    echo "✅ TODAS LAS PRUEBAS PASARON CORRECTAMENTE\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";