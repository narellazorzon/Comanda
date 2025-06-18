<?php
// src/models/LlamadoMesa.php
namespace App\Models;

use App\Config\Database;
use PDO;

class LlamadoMesa {
    public static function all(): array {
        $db = (new Database)->getConnection();
        return $db->query("
            SELECT * 
            FROM llamados_mesa 
            ORDER BY hora_solicitud DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateEstado(int $id, string $nuevoEstado): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            UPDATE llamados_mesa 
            SET estado = ? 
            WHERE id_llamado = ?
        ");
        return $stmt->execute([$nuevoEstado, $id]);
    }

    // si necesitas, podrías añadir allPending(), find(), etc.
}
