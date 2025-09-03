<?php
// src/models/Mesa.php
namespace App\Models;

use App\Config\Database;
use App\Config\Validator;
use PDO;

class Mesa {
    /**
     * Devuelve todas las mesas ordenadas por número con información del mozo asignado.
     */
    public static function all(): array {
        $db   = (new Database)->getConnection();
        $stmt = $db->query("
            SELECT m.*, 
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
            FROM mesas m
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            ORDER BY m.numero ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca una mesa por su ID con información del mozo asignado, o devuelve null si no existe.
     */
    public static function find(int $id): ?array {
        $db   = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT m.*, 
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
            FROM mesas m
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            WHERE m.id_mesa = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Crea una nueva mesa usando validaciones robustas.
     */
    public static function create(array $data): bool {
        // Validar datos usando la nueva clase Validator
        $validation = Validator::validateMultiple($data, [
            'numero' => [
                'type' => 'positive_integer',
                'options' => ['min' => 1, 'max' => 999]
            ],
            'ubicacion' => [
                'type' => 'string', 
                'options' => ['min_length' => 0, 'max_length' => 100]
            ]
        ]);
        
        if (!$validation['valid']) {
            $errors = implode(', ', $validation['errors']);
            throw new \InvalidArgumentException('Datos inválidos: ' . $errors);
        }
        
        $validated_data = $validation['data'];
        $numero = $validated_data['numero'];
        $ubicacion = $validated_data['ubicacion'] ?: null;
        
        $db = (new Database)->getConnection();
        
        // Verificar si ya existe una mesa con ese número
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM mesas WHERE numero = ?");
        $checkStmt->execute([$numero]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new \InvalidArgumentException('Ya existe una mesa con el número ' . $numero);
        }
        
        // Validar id_mozo si se proporciona
        $id_mozo = null;
        if (!empty($data['id_mozo'])) {
            $mozo_validation = Validator::validateInput($data['id_mozo'], 'positive_integer');
            if (!$mozo_validation['valid']) {
                throw new \InvalidArgumentException('ID de mozo inválido');
            }
            
            // Verificar que el mozo existe y está activo
            $mozoStmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = ? AND rol = 'mozo' AND estado = 'activo'");
            $mozoStmt->execute([$mozo_validation['value']]);
            if ($mozoStmt->fetchColumn() == 0) {
                throw new \InvalidArgumentException('El mozo especificado no existe o no está activo');
            }
            $id_mozo = $mozo_validation['value'];
        }
        
        $stmt = $db->prepare("INSERT INTO mesas (numero, ubicacion, id_mozo) VALUES (?, ?, ?)");
        return $stmt->execute([$numero, $ubicacion, $id_mozo]);
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
                estado   = ?,
                id_mozo  = ?
            WHERE id_mesa = ?
        ");
        return $stmt->execute([
            $numero,
            $data['ubicacion'] ?? null,
            $estado,
            !empty($data['id_mozo']) ? (int) $data['id_mozo'] : null,
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
     * Asigna o cambia el mozo de una mesa.
     */
    public static function asignarMozo(int $id_mesa, ?int $id_mozo): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("UPDATE mesas SET id_mozo = ? WHERE id_mesa = ?");
        return $stmt->execute([$id_mozo, $id_mesa]);
    }

    /**
     * Obtiene todas las mesas asignadas a un mozo específico.
     */
    public static function getMesasByMozo(int $id_mozo): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT m.*, 
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
            FROM mesas m
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            WHERE m.id_mozo = ?
            ORDER BY m.numero ASC
        ");
        $stmt->execute([$id_mozo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta cuántas mesas tiene asignadas un mozo.
     */
    public static function countMesasByMozo(int $id_mozo): int {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM mesas WHERE id_mozo = ?");
        $stmt->execute([$id_mozo]);
        return (int) $stmt->fetchColumn();
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
