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
        // Debugging temporal
        error_log("Mesa::create - Data recibida: " . print_r($data, true));
        
        // Validar que los datos requeridos estén presentes
        if (!isset($data['numero']) || $data['numero'] === '' || $data['numero'] === null) {
            error_log("Mesa::create - Error: numero no está presente o está vacío");
            throw new \InvalidArgumentException('El número de mesa es obligatorio');
        }
        
        // Validar que el número sea un entero positivo
        $numero = (int) $data['numero'];
        if ($numero <= 0) {
            error_log("Mesa::create - Error: numero <= 0: " . $numero);
            throw new \InvalidArgumentException('El número de mesa debe ser mayor a 0');
        }
        
        $db   = (new Database)->getConnection();
        
        // Verificar si ya existe una mesa con ese número
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM mesas WHERE numero = ?");
        $checkStmt->execute([$numero]);
        if ($checkStmt->fetchColumn() > 0) {
            error_log("Mesa::create - Error: mesa duplicada con numero: " . $numero);
            throw new \InvalidArgumentException('Ya existe una mesa con el número ' . $numero);
        }
        
        $stmt = $db->prepare("INSERT INTO mesas (numero, ubicacion) VALUES (?, ?)");
        return $stmt->execute([
            $numero,
            $data['ubicacion'] ?? null
        ]);
    }

    /**
     * Actualiza número, ubicación y estado de la mesa.
     */
    public static function update(int $id, array $data): bool {
        // Validar que los datos requeridos estén presentes
        if (!isset($data['numero']) || $data['numero'] === '' || $data['numero'] === null) {
            throw new \InvalidArgumentException('El número de mesa es obligatorio');
        }
        
        // Validar que el número sea un entero positivo
        $numero = (int) $data['numero'];
        if ($numero <= 0) {
            throw new \InvalidArgumentException('El número de mesa debe ser mayor a 0');
        }
        
        $db   = (new Database)->getConnection();
        
        // Verificar si ya existe otra mesa con ese número (excluyendo la mesa actual)
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM mesas WHERE numero = ? AND id_mesa != ?");
        $checkStmt->execute([$numero, $id]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new \InvalidArgumentException('Ya existe otra mesa con el número ' . $numero);
        }
        
        $estado = $data['estado'] ?? 'libre';
        
        $stmt = $db->prepare("
            UPDATE mesas
            SET numero   = ?,
                ubicacion= ?,
                estado   = ?
            WHERE id_mesa = ?
        ");
        return $stmt->execute([
            $numero,
            $data['ubicacion'] ?? null,
            $estado,
            $id
        ]);
    }

    /**
     * Actualiza solo el estado de una mesa.
     */
    public static function updateEstado(int $id, string $estado): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("UPDATE mesas SET estado = ? WHERE id_mesa = ?");
        return $stmt->execute([$estado, $id]);
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
