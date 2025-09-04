<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class CartaItem {
    // ya tenías all()
    public static function all(): array {
        $db = (new Database)->getConnection();
        return $db->query("SELECT * FROM carta WHERE disponibilidad = 1")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function allIncludingUnavailable(): array {
        $db = (new Database)->getConnection();
        return $db->query("SELECT * FROM carta")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT * FROM carta WHERE id_item = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            INSERT INTO carta (nombre, descripcion, precio, categoria, disponibilidad, imagen_url)
            VALUES (?,?,?,?,?,?)
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'],
            $data['precio'],
            $data['categoria'] ?? null,
            $data['disponibilidad'] ?? 1,
            $data['imagen_url'] ?? null
        ]);
    }

    public static function update(int $id, array $data): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            UPDATE carta
            SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, disponibilidad = ?, imagen_url = ?
            WHERE id_item = ?
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'],
            $data['precio'],
            $data['categoria'] ?? null,
            $data['disponibilidad'] ?? 1,
            $data['imagen_url'] ?? null,
            $id
        ]);
    }

    public static function canDelete(int $id): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM detalle_pedido WHERE id_item = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }

    public static function delete(int $id): bool {
        $db = (new Database)->getConnection();
        
        error_log("CartaItem::delete() - ID: " . $id);
        
        // Verificar si el item está siendo usado en pedidos
        $canDelete = self::canDelete($id);
        error_log("CartaItem::delete() - Can delete: " . ($canDelete ? 'true' : 'false'));
        
        if (!$canDelete) {
            // Si está siendo usado, marcar como no disponible en lugar de eliminar
            error_log("CartaItem::delete() - Marcando como no disponible");
            $updateStmt = $db->prepare("UPDATE carta SET disponibilidad = 0 WHERE id_item = ?");
            $result = $updateStmt->execute([$id]);
            error_log("CartaItem::delete() - Update result: " . ($result ? 'true' : 'false'));
            return $result;
        } else {
            // Si no está siendo usado, eliminar completamente
            error_log("CartaItem::delete() - Eliminando completamente");
            $deleteStmt = $db->prepare("DELETE FROM carta WHERE id_item = ?");
            $result = $deleteStmt->execute([$id]);
            error_log("CartaItem::delete() - Delete result: " . ($result ? 'true' : 'false'));
            return $result;
        }
    }
}
