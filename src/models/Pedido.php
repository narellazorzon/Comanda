<?php
// src/models/Pedido.php
namespace App\Models;

use App\Config\Database;
use PDO;

class Pedido {
    /**
     * Devuelve todos los pedidos con información de mesa y mozo, ordenados por fecha desc.
     */
    public static function all(): array {
        $db = (new Database)->getConnection();
        return $db->query("
            SELECT p.*, 
                   m.numero as numero_mesa,
                   m.ubicacion as ubicacion_mesa,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as nombre_mozo_completo,
                   p.fecha_hora as fecha_creacion
            FROM pedidos p
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON p.id_mozo = u.id_usuario
            ORDER BY p.fecha_hora DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todos los pedidos asignados a un mozo.
     */
    public static function allByMozo(int $mozoId): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT * 
            FROM pedidos 
            WHERE id_mozo = ? 
            ORDER BY fecha_hora DESC
        ");
        $stmt->execute([$mozoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todos los pedidos de una mesa (cliente).
     */
    public static function allByMesa(int $mesaId): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT * 
            FROM pedidos 
            WHERE id_mesa = ? 
            ORDER BY fecha_hora DESC
        ");
        $stmt->execute([$mesaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un pedido por su ID.
     */
    public static function find(int $id): ?array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT * FROM pedidos WHERE id_pedido = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Crea un nuevo pedido y devuelve su ID.
     */
    public static function create(array $data): int {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, id_mozo)
            VALUES (?,?,?,?,?)
        ");
        $stmt->execute([
            $data['id_mesa'] ?? null,
            $data['modo_consumo'],
            0.00,
            'pendiente',
            $_SESSION['user']['id_usuario'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Actualiza el estado de un pedido.
     */
    public static function updateEstado(int $id, string $nuevoEstado): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            UPDATE pedidos 
            SET estado = ? 
            WHERE id_pedido = ?
        ");
        return $stmt->execute([$nuevoEstado, $id]);
    }

    /**
     * Elimina un pedido por su ID.
     */
    public static function delete(int $id): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("DELETE FROM pedidos WHERE id_pedido = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Actualiza el total de un pedido.
     */
    public static function updateTotal(int $id, float $total): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            UPDATE pedidos 
            SET total = ? 
            WHERE id_pedido = ?
        ");
        return $stmt->execute([$total, $id]);
    }
}
