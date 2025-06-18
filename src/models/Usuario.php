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
}
