<?php
namespace App\Models;

use PDO;

class Usuario extends BaseModel {
    public static function findByEmail(string $email): ?array {
        return self::fetchOne("SELECT * FROM usuarios WHERE email = ?", [$email]);
    }

    public static function allByRole(string $rol): array {
        return self::fetchAll("SELECT * FROM usuarios WHERE rol = ?", [$rol]);
    }

    /**
     * Obtiene todos los mozos activos para asignación a mesas.
     */
    public static function getMozosActivos(): array {
        return self::fetchAll("
            SELECT id_usuario, nombre, apellido,
                   CONCAT(nombre, ' ', apellido) as nombre_completo
            FROM usuarios
            WHERE rol = 'mozo' AND estado = 'activo'
            ORDER BY nombre, apellido
        ");
    }

    public static function find(int $id): ?array {
        return self::fetchOne("SELECT * FROM usuarios WHERE id_usuario = ?", [$id]);
    }

    public static function update(int $id, array $data): bool {
        // Preparar datos para actualización
        $updateData = [
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'estado' => $data['estado'] ?? 'activo'
        ];

        // Si viene contraseña, la hasheamos y agregamos
        if (!empty($data['contrasenia'])) {
            $updateData['contrasenia'] = password_hash($data['contrasenia'], PASSWORD_DEFAULT);
        }

        return parent::updateTable('usuarios', $updateData, 'id_usuario = :id', ['id' => $id]) > 0;
    }

    public static function create(array $data): bool {
        $insertData = [
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'contrasenia' => $data['contrasenia'],
            'rol' => $data['rol']
        ];

        return parent::insert('usuarios', $insertData) > 0;
    }
    public static function delete(int $id): bool {
        return parent::deleteFrom('usuarios', 'id_usuario = :id', ['id' => $id]) > 0;
    }

    public static function emailExists(string $email, int $excludeId = null): bool {
        $where = "email = :email";
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $where .= " AND id_usuario != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        return parent::exists('usuarios', $where, $params);
    }
}
