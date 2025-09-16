<?php
// Script de debugging para el módulo de Rendimiento del Personal
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/services/ReporteService.php';

use App\Config\Database;
use App\Services\ReporteService;

echo "<h1>DEBUG - Rendimiento del Personal</h1>";

$db = (new Database)->getConnection();

echo "<h2>1. Verificar conexión a BD</h2>";
echo "✅ Conexión exitosa<br>";

echo "<h2>2. Verificar pedidos en la base de datos</h2>";
$stmt = $db->prepare("
    SELECT 
        p.id_pedido,
        p.id_mozo,
        u.nombre,
        u.apellido,
        p.total,
        p.estado,
        p.fecha_hora
    FROM pedidos p
    JOIN usuarios u ON p.id_mozo = u.id_usuario
    WHERE p.fecha_hora >= CURDATE() - INTERVAL 30 DAY
    ORDER BY p.fecha_hora DESC
    LIMIT 20
");
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Mozo</th><th>Total</th><th>Estado</th><th>Fecha</th></tr>";
foreach($pedidos as $p) {
    echo "<tr>";
    echo "<td>{$p['id_pedido']}</td>";
    echo "<td>{$p['nombre']} {$p['apellido']}</td>";
    echo "<td>\${$p['total']}</td>";
    echo "<td>{$p['estado']}</td>";
    echo "<td>{$p['fecha_hora']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>3. Verificar propinas</h2>";
$stmt = $db->prepare("
    SELECT 
        pr.id_propina,
        pr.id_pedido,
        pr.id_mozo,
        u.nombre,
        u.apellido,
        pr.monto,
        pr.fecha_hora
    FROM propinas pr
    JOIN usuarios u ON pr.id_mozo = u.id_usuario
    WHERE pr.fecha_hora >= CURDATE() - INTERVAL 30 DAY
    ORDER BY pr.fecha_hora DESC
    LIMIT 20
");
$stmt->execute();
$propinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Pedido ID</th><th>Mozo</th><th>Monto</th><th>Fecha</th></tr>";
foreach($propinas as $pr) {
    echo "<tr>";
    echo "<td>{$pr['id_propina']}</td>";
    echo "<td>{$pr['id_pedido']}</td>";
    echo "<td>{$pr['nombre']} {$pr['apellido']}</td>";
    echo "<td>\${$pr['monto']}</td>";
    echo "<td>{$pr['fecha_hora']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>4. Probar el servicio ReporteService</h2>";

$rs = new ReporteService();

// Probar con fechas de los últimos 30 días
$desde = date('Y-m-d', strtotime('-30 days'));
$hasta = date('Y-m-d');

echo "<p><strong>Probando con fechas:</strong> Desde {$desde} hasta {$hasta}</p>";

try {
    $resultado = $rs->getRendimientoMozos($desde, $hasta, 'ninguno');
    
    echo "<h3>Resultado del servicio:</h3>";
    echo "<pre>";
    print_r($resultado);
    echo "</pre>";
    
    if (empty($resultado['kpis'])) {
        echo "<p style='color: red;'>❌ No hay datos en el resultado</p>";
        
        // Probar la consulta SQL directamente
        echo "<h3>Probando consulta SQL directamente:</h3>";
        
        $sql = "
            SELECT 
                u.id_usuario AS mozo_id,
                CONCAT(u.nombre, ' ', u.apellido) AS mozo,
                COUNT(DISTINCT pe.id_pedido) AS pedidos,
                COALESCE(SUM(pr.monto), 0) AS propina_total,
                COALESCE(SUM(pe.total), 0) AS total_vendido
            FROM usuarios u
            LEFT JOIN pedidos pe ON u.id_usuario = pe.id_mozo 
                AND pe.fecha_hora BETWEEN :desde AND :hasta
                AND pe.estado IN ('servido', 'cuenta', 'cerrado')
            LEFT JOIN propinas pr ON pr.id_pedido = pe.id_pedido
            WHERE u.rol = 'mozo' AND u.estado = 'activo'
            GROUP BY u.id_usuario, mozo
            ORDER BY propina_total DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':desde' => $desde . ' 00:00:00',
            ':hasta' => $hasta . ' 23:59:59'
        ]);
        
        $directResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<pre>";
        print_r($directResult);
        echo "</pre>";
        
    } else {
        echo "<p style='color: green;'>✅ Datos encontrados!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Verificar estados de pedidos</h2>";
$stmt = $db->prepare("
    SELECT estado, COUNT(*) as cantidad
    FROM pedidos 
    WHERE fecha_hora >= CURDATE() - INTERVAL 30 DAY
    GROUP BY estado
");
$stmt->execute();
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Estado</th><th>Cantidad</th></tr>";
foreach($estados as $est) {
    echo "<tr><td>{$est['estado']}</td><td>{$est['cantidad']}</td></tr>";
}
echo "</table>";

echo "<h2>6. Fechas por defecto del controlador</h2>";
$fechaDesde = date('Y-m-01'); // Primer día del mes actual
$fechaHasta = date('Y-m-d');  // Hoy

echo "<p>Fecha desde por defecto: <strong>{$fechaDesde}</strong></p>";
echo "<p>Fecha hasta por defecto: <strong>{$fechaHasta}</strong></p>";

?>