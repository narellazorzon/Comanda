<?php
// src/models/Mesa.php
namespace App\Models;

use App\Config\Database;
use PDO;

class Mesa {
    /**
     * Devuelve todas las mesas ordenadas por número.
     */
    public static function all(): array {
        $db   = (new Database)->getConnection();
        $stmt = $db->query("SELECT * FROM mesas ORDER BY numero ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca una mesa por su ID, o devuelve null si no existe.
     */
    public static function find(int $id): ?array {
        $db   = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT * FROM mesas WHERE id_mesa = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Crea una nueva mesa. El estado por defecto (libre) lo asigna MySQL.
     */
    public static function create(array $data): bool {
        $db   = (new Database)->getConnection();
        $stmt = $db->prepare("INSERT INTO mesas (numero, ubicacion) VALUES (?, ?)");
        return $stmt->execute([
            $data['numero'],
            $data['ubicacion'] ?? null
        ]);
    }

    /**
     * Actualiza número, ubicación y estado de la mesa.
     */
    public static function update(int $id, array $data): bool {
        $db   = (new Database)->getConnection();
        $stmt = $db->prepare("
            UPDATE mesas
            SET numero   = ?,
                ubicacion= ?,
                estado   = ?
            WHERE id_mesa = ?
        ");
        return $stmt->execute([
            $data['numero'],
            $data['ubicacion'] ?? null,
            $data['estado']     ?? 'libre',
            $id
        ]);
    }

    /**
     * Borra una mesa por su ID.
     */
    public static function delete(int $id): bool {
        $db   = (new Database)->getConnection();
        $stmt = $db->prepare("DELETE FROM mesas WHERE id_mesa = ?");
        return $stmt->execute([$id]);
    }
}
