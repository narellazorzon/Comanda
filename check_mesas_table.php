<?php
require_once 'vendor/autoload.php';
require_once 'src/config/database.php';

use App\Config\Database;

$db = (new Database)->getConnection();

echo "=== Estructura de la tabla 'mesas' ===\n\n";

$stmt = $db->query('DESCRIBE mesas');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($columns as $col) {
    echo str_pad($col['Field'], 20) . " - " . $col['Type'] . "\n";
}

echo "\n=== Verificando existencia de columna 'status' ===\n";

$hasStatus = false;
foreach($columns as $col) {
    if ($col['Field'] === 'status') {
        $hasStatus = true;
        echo "✓ La columna 'status' EXISTE\n";
        break;
    }
}

if (!$hasStatus) {
    echo "✗ La columna 'status' NO existe\n";
    echo "\n=== Columnas que podrían ser el campo de estado ===\n";
    foreach($columns as $col) {
        if (stripos($col['Field'], 'activ') !== false ||
            stripos($col['Field'], 'estado') !== false ||
            stripos($col['Field'], 'status') !== false ||
            stripos($col['Field'], 'eliminad') !== false ||
            stripos($col['Field'], 'delet') !== false) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
}

echo "\n=== Muestra de datos ===\n";
$stmt = $db->query('SELECT * FROM mesas LIMIT 3');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($rows) > 0) {
    echo "Campos disponibles: " . implode(', ', array_keys($rows[0])) . "\n";
}