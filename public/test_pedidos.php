<?php
// public/test_pedidos.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Pedido;
use App\Models\Mesa;
use App\Models\Usuario;

session_start();

// Simular un usuario administrador para la prueba
$_SESSION['user'] = [
    'id_usuario' => 1,
    'rol' => 'administrador',
    'nombre' => 'Admin'
];

echo "<h1>🧪 Prueba del Modelo Pedido</h1>";

try {
    echo "<h2>1. Probando Pedido::all()</h2>";
    $pedidos = Pedido::all();
    echo "<p>✅ Pedido::all() funcionó correctamente</p>";
    echo "<p>Total de pedidos: " . count($pedidos) . "</p>";
    
    if (!empty($pedidos)) {
        echo "<h3>Primer pedido:</h3>";
        echo "<pre>" . print_r($pedidos[0], true) . "</pre>";
    }
    
    echo "<h2>2. Probando Pedido::find()</h2>";
    if (!empty($pedidos)) {
        $primerPedido = Pedido::find($pedidos[0]['id_pedido']);
        echo "<p>✅ Pedido::find() funcionó correctamente</p>";
        echo "<pre>" . print_r($primerPedido, true) . "</pre>";
    } else {
        echo "<p>⚠️ No hay pedidos para probar find()</p>";
    }
    
    echo "<h2>3. Probando Mesa::all()</h2>";
    $mesas = Mesa::all();
    echo "<p>✅ Mesa::all() funcionó correctamente</p>";
    echo "<p>Total de mesas: " . count($mesas) . "</p>";
    
    echo "<h2>4. Probando Usuario::allByRole()</h2>";
    $mozos = Usuario::allByRole('mozo');
    echo "<p>✅ Usuario::allByRole() funcionó correctamente</p>";
    echo "<p>Total de mozos: " . count($mozos) . "</p>";
    
    echo "<h2>5. Probando acceso a arrays</h2>";
    if (!empty($pedidos)) {
        $pedido = $pedidos[0];
        echo "<p>✅ Acceso a id_pedido: " . ($pedido['id_pedido'] ?? 'N/A') . "</p>";
        echo "<p>✅ Acceso a id_mesa: " . (isset($pedido['id_mesa']) ? $pedido['id_mesa'] : 'N/A') . "</p>";
        echo "<p>✅ Acceso a id_mozo: " . (isset($pedido['id_mozo']) ? $pedido['id_mozo'] : 'N/A') . "</p>";
        echo "<p>✅ Acceso a estado: " . ($pedido['estado'] ?? 'N/A') . "</p>";
        echo "<p>✅ Acceso a total: " . ($pedido['total'] ?? 'N/A') . "</p>";
    }
    
    echo "<h2>✅ Todas las pruebas pasaron correctamente</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error encontrado:</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
