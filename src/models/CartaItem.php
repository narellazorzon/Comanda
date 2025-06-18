<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class CartaItem {
    // ya tenías all()
    public static function all(): array {
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
            isset($data['disponibilidad']) ? 1 : 0,
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
            isset($data['disponibilidad']) ? 1 : 0,
            $data['imagen_url'] ?? null,
            $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("DELETE FROM carta WHERE id_item = ?");
        return $stmt->execute([$id]);
    }
}
