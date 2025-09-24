<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Usuario {
    public static function findByEmail(string $email): ?array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function allByRole(string $rol): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE rol = ?");
        $stmt->execute([$rol]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los mozos activos para asignación a mesas.
     */
    public static function getMozosActivos(): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT id_usuario, nombre, apellido, 
                   CONCAT(nombre, ' ', apellido) as nombre_completo
            FROM usuarios 
            WHERE rol = 'mozo' AND estado = 'activo'
            ORDER BY nombre, apellido
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los usuarios por rol (para filtros).
     */
    public static function findByRole(string $rol): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT id_usuario, nombre, apellido, email, estado,
                   CONCAT(nombre, ' ', apellido) as nombre_completo
            FROM usuarios 
            WHERE rol = ?
            ORDER BY nombre, apellido
        ");
        $stmt->execute([$rol]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function update(int $id, array $data): bool {
        $db = (new Database)->getConnection();
        
        // Si viene contraseña, la hasheamos
        if (isset($data['contrasenia']) && !empty($data['contrasenia'])) {
            $stmt = $db->prepare("
                UPDATE usuarios 
                SET nombre = ?, apellido = ?, email = ?, contrasenia = ?, estado = ?
                WHERE id_usuario = ?
            ");
            return $stmt->execute([
                $data['nombre'],
                $data['apellido'],
                $data['email'],
                password_hash($data['contrasenia'], PASSWORD_DEFAULT),
                $data['estado'] ?? 'activo',
                $id
            ]);
        } else {
            // Actualizar sin cambiar contraseña
            $stmt = $db->prepare("
                UPDATE usuarios 
                SET nombre = ?, apellido = ?, email = ?, estado = ?
                WHERE id_usuario = ?
            ");
            return $stmt->execute([
                $data['nombre'],
                $data['apellido'],
                $data['email'],
                $data['estado'] ?? 'activo',
                $id
            ]);
        }
    }

    public static function create(array $data): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            INSERT INTO usuarios (nombre, apellido, email, contrasenia, rol)
            VALUES (?,?,?,?,?)
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['email'],
            $data['contrasenia'],
            $data['rol']
        ]);
    }
    public static function delete(int $id): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        return $stmt->execute([$id]);
    }

    public static function emailExists(string $email, int $excludeId = null): bool {
        $db = (new Database)->getConnection();
        $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
        $params = [$email];
        
        if ($excludeId !== null) {
            $sql .= " AND id_usuario != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
