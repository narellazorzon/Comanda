<?php
require_once 'vendor/autoload.php';
require_once 'src/config/database.php';

use App\Config\Database;

try {
    $db = (new Database)->getConnection();

    echo "=== Agregando columna status a la tabla mesas ===\n\n";

    // Verificar si la columna ya existe
    $stmt = $db->query("SHOW COLUMNS FROM mesas LIKE 'status'");
    if ($stmt->rowCount() > 0) {
        echo "✓ La columna 'status' ya existe. No se requiere ninguna acción.\n";
    } else {
        // Agregar la columna status
        $db->exec("ALTER TABLE mesas ADD COLUMN status TINYINT(1) DEFAULT 1 AFTER fecha_creacion");
        echo "✓ Columna 'status' agregada exitosamente.\n";

        // Actualizar todas las mesas existentes como activas
        $db->exec("UPDATE mesas SET status = 1 WHERE status IS NULL");
        echo "✓ Todas las mesas existentes marcadas como activas.\n";
    }

    echo "\n=== Verificación ===\n";
    $stmt = $db->query("DESCRIBE mesas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($columns as $col) {
        if ($col['Field'] === 'status') {
            echo "✓ Columna 'status' confirmada: " . $col['Type'] . " " . $col['Default'] . "\n";
            break;
        }
    }

    echo "\n✅ Proceso completado exitosamente.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}