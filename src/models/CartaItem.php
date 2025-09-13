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
        
        try {
            $db->beginTransaction();
            
            // Primero eliminar todas las referencias en detalle_pedido
            error_log("CartaItem::delete() - Eliminando referencias en detalle_pedido");
            $deleteDetalleStmt = $db->prepare("DELETE FROM detalle_pedido WHERE id_item = ?");
            $detalleResult = $deleteDetalleStmt->execute([$id]);
            error_log("CartaItem::delete() - Detalle delete result: " . ($detalleResult ? 'true' : 'false'));
            
            // Luego eliminar el item de la carta
            error_log("CartaItem::delete() - Eliminando item de carta");
            $deleteCartaStmt = $db->prepare("DELETE FROM carta WHERE id_item = ?");
            $cartaResult = $deleteCartaStmt->execute([$id]);
            error_log("CartaItem::delete() - Carta delete result: " . ($cartaResult ? 'true' : 'false'));
            
            if ($cartaResult) {
                $db->commit();
                error_log("CartaItem::delete() - Transacción completada exitosamente");
                return true;
            } else {
                $db->rollBack();
                error_log("CartaItem::delete() - Error al eliminar item, rollback ejecutado");
                return false;
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("CartaItem::delete() - Error en transacción: " . $e->getMessage());
            return false;
        }
    }
}
