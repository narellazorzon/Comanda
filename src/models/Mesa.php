<?php
// src/models/Mesa.php
namespace App\Models;

use App\Database\QueryBuilder;
use PDO;
use PDOException;

class Mesa extends BaseModel {
    /**
     * Devuelve todas las mesas activas ordenadas por número con información del mozo asignado.
     */
    public static function all(): array {
        $sql = QueryBuilder::mesasWithMozo('m.status = 1');
        return self::fetchAll($sql);
    }

    /**
     * Busca una mesa activa por su ID con información del mozo asignado, o devuelve null si no existe.
     */
    public static function find(int $id): ?array {
        $sql = QueryBuilder::mesasWithMozo('m.id_mesa = :id AND m.status = 1');
        return self::fetchOne($sql, ['id' => $id]);
    }
    
    /**
     * Busca una mesa por su número con información del mozo asignado.
     */
    public static function findByNumero(int $numero): ?array {
        $sql = QueryBuilder::mesasWithMozo('m.numero = :numero');
        return self::fetchOne($sql, ['numero' => $numero]);
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
        

        // Verificar si ya existe una mesa con ese número
        if (self::exists('mesas', 'numero = :numero', ['numero' => $numero])) {
            error_log("Mesa::create - Error: mesa duplicada con numero: " . $numero);
            throw new \InvalidArgumentException('Ya existe una mesa con el número ' . $numero);
        }

        $insertData = [
            'numero' => $numero,
            'ubicacion' => $data['ubicacion'] ?? null,
            'estado' => $data['estado'] ?? 'libre',
            'id_mozo' => !empty($data['id_mozo']) ? (int) $data['id_mozo'] : null
        ];

        // Agregar status solo si la columna existe
        $insertData['status'] = 1;

        return self::insert('mesas', $insertData) > 0;
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
        

        // Verificar si ya existe otra mesa con ese número (excluyendo la mesa actual)
        if (self::exists('mesas', 'numero = :numero AND id_mesa != :id', ['numero' => $numero, 'id' => $id])) {
            throw new \InvalidArgumentException('Ya existe otra mesa con el número ' . $numero);
        }
        
        // Si no se proporciona estado, mantener el estado actual
        if (!isset($data['estado'])) {
            $currentMesa = self::find($id);
            $estado = $currentMesa ? $currentMesa['estado'] : 'libre';
        } else {
            $estado = $data['estado'];
        }
        
        $updateData = [
            'numero' => $numero,
            'ubicacion' => $data['ubicacion'] ?? null,
            'estado' => $estado,
            'id_mozo' => !empty($data['id_mozo']) ? (int) $data['id_mozo'] : null
        ];

        return self::updateTable('mesas', $updateData, 'id_mesa = :id', ['id' => $id]) > 0;
    }

    /**
     * Actualiza solo el estado de una mesa.
     */
    public static function updateEstado(int $id, string $estado): bool {
        return self::updateTable('mesas', ['estado' => $estado], 'id_mesa = :id', ['id' => $id]) > 0;
    }

    /**
     * Asigna o cambia el mozo de una mesa.
     */
    public static function asignarMozo(int $id_mesa, ?int $id_mozo): bool {
        return self::updateTable('mesas', ['id_mozo' => $id_mozo], 'id_mesa = :id', ['id' => $id_mesa]) > 0;
    }

    /**
     * Obtiene todas las mesas asignadas a un mozo específico.
     */
    public static function getMesasByMozo(int $id_mozo): array {
        $sql = QueryBuilder::mesasWithMozo('m.id_mozo = :id_mozo');
        return self::fetchAll($sql, ['id_mozo' => $id_mozo]);
    }

    /**
     * Cuenta cuántas mesas tiene asignadas un mozo.
     */
    public static function countMesasByMozo(int $id_mozo): int {
        return self::count('mesas', 'id_mozo = :id_mozo', ['id_mozo' => $id_mozo]);
    }

    /**
     * Devuelve todas las mesas inactivas ordenadas por número con información del mozo asignado.
     */
    public static function allInactive(): array {
        $sql = QueryBuilder::mesasWithMozo('m.status = 0');
        return self::fetchAll($sql);
    }

    /**
     * Reactiva una mesa (cambia status de 0 a 1).
     */
    public static function reactivate(int $id): array {
        
        try {
            // Verificar si la mesa existe
            $mesa = self::fetchOne("SELECT id_mesa, estado, status FROM mesas WHERE id_mesa = :id", ['id' => $id]);

            if (!$mesa) {
                return ['success' => false, 'message' => 'La mesa no existe'];
            }

            if ($mesa['estado'] != 'reservada') {
                return ['success' => false, 'message' => 'La mesa ya está activa'];
            }

            // Reactivar la mesa
            $stmt = $db->prepare("UPDATE mesas SET estado = 'libre' WHERE id_mesa = ?");
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Mesa reactivada correctamente'];
            } else {
                return ['success' => false, 'message' => 'No se pudo reactivar la mesa'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al reactivar la mesa: ' . $e->getMessage()];
        }
    }

    /**
     * Verifica si una mesa tiene pedidos activos.
     */
    public static function tienePedidosActivos(int $id): bool {
        return self::exists('pedidos', "id_mesa = :id AND estado NOT IN ('cerrado', 'cancelado')", ['id' => $id]);
    }

    /**
     * Marca una mesa como inactiva (soft delete) por su ID.
     * @return array ['success' => bool, 'message' => string]
     */
    public static function delete(int $id): array {
        
        try {
            // Verificar si la mesa existe
            $mesa = self::fetchOne("SELECT id_mesa, estado, status FROM mesas WHERE id_mesa = :id", ['id' => $id]);

            if (!$mesa) {
                return ['success' => false, 'message' => 'La mesa no existe'];
            }

            if ($mesa['estado'] == 'reservada') {
                return ['success' => false, 'message' => 'La mesa ya está inactiva'];
            }

            // Marcar como inactiva (soft delete usando status)
            $rowsAffected = self::updateTable('mesas', ['status' => 0], 'id_mesa = :id', ['id' => $id]);

            if ($rowsAffected > 0) {
                return ['success' => true, 'message' => 'Mesa desactivada correctamente'];
            } else {
                return ['success' => false, 'message' => 'No se pudo desactivar la mesa'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al desactivar la mesa: ' . $e->getMessage()];
        }
    }
}
