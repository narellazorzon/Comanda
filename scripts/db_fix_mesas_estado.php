<?php
// scripts/db_fix_mesas_estado.php
// One-off data correction: align legacy inactive mesas to use estado = 'inactiva'

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    $db = (new Database)->getConnection();

    // 1) Marcar como inactivas (status = 0) las mesas con id_mozo NULL y estado vacío o NULL
    $legacyCandidates = (int) $db->query(
        "SELECT COUNT(*) FROM mesas WHERE id_mozo IS NULL AND (estado IS NULL OR estado = '')"
    )->fetchColumn();

    echo "Mesas legado con estado vacío y sin mozo: {$legacyCandidates}\n";

    if ($legacyCandidates > 0) {
        $updatedStatus = $db->exec(
            "UPDATE mesas SET status = 0 WHERE id_mozo IS NULL AND (estado IS NULL OR estado = '')"
        );
        echo "Actualizadas a status=0 (inactivas): {$updatedStatus}\n";
    }

    // 2) Normalizar estado vacío a 'libre' para mantener valores válidos del ENUM
    $empties = (int) $db->query("SELECT COUNT(*) FROM mesas WHERE estado = ''")->fetchColumn();
    echo "Mesas con estado vacío: {$empties}\n";
    if ($empties > 0) {
        $normalized = $db->exec("UPDATE mesas SET estado = 'libre' WHERE estado = ''");
        echo "Normalizadas a estado='libre': {$normalized}\n";
    }

    // 3) Mostrar conteos finales
    $inactiveByStatus = (int) $db->query("SELECT COUNT(*) FROM mesas WHERE status = 0")->fetchColumn();
    $activeByStatus   = (int) $db->query("SELECT COUNT(*) FROM mesas WHERE status = 1")->fetchColumn();
    echo "Totales -> activas(status=1): {$activeByStatus}, inactivas(status=0): {$inactiveByStatus}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
