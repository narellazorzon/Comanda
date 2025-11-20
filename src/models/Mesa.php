<?php
// src/models/Mesa.php
namespace App\Models;

use App\Config\Database;
use App\Config\Validator;
use PDO;

class Mesa {
    /**
     * Devuelve todas las mesas activas ordenadas por número con información del mozo asignado.
     */
    public static function all(): array {
        $db   = (new Database)->getConnection();
        $stmt = $db->query("
            SELECT m.*, 
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo,
                   (SELECT COUNT(*) FROM pedidos p WHERE p.id_mesa = m.id_mesa AND p.estado NOT IN ('cerrado', 'cancelado')) as pedidos_activos
            FROM mesas m
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            WHERE m.status = 1
            ORDER BY m.numero ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todas las mesas activas ordenadas con las mesas del mozo logueado primero.
     */
    public static function allForMozo(int $id_mozo): array {
        $db   = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT m.*, 
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo,
                   (SELECT COUNT(*) FROM pedidos p WHERE p.id_mesa = m.id_mesa AND p.estado NOT IN ('cerrado', 'cancelado')) as pedidos_activos,
                   CASE 
                       WHEN m.id_mozo = ? THEN 0 
                       ELSE 1 
                   END as es_mesa_asignada
            FROM mesas m
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            WHERE m.status = 1
            ORDER BY es_mesa_asignada ASC, m.numero ASC
        ");
        $stmt->execute([$id_mozo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca una mesa activa por su ID con información del mozo asignado, o devuelve null si no existe.
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
     * Busca una mesa por su número con información del mozo asignado.
     */
    public static function findByNumero(int $numero): ?array {
        $db   = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT m.*, 
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
            FROM mesas m
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            WHERE m.numero = ?
        ");
        $stmt->execute([$numero]);
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
        
        $estado = $data['estado'] ?? 'libre';
        
        $stmt = $db->prepare("INSERT INTO mesas (numero, ubicacion, estado, id_mozo) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $numero,
            $data['ubicacion'] ?? null,
            $estado,
            !empty($data['id_mozo']) ? (int) $data['id_mozo'] : null
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
        
        // Si no se proporciona estado, mantener el estado actual
        if (!isset($data['estado'])) {
            $currentMesa = self::find($id);
            $estado = $currentMesa ? $currentMesa['estado'] : 'libre';
        } else {
            $estado = $data['estado'];
        }
        
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
     * Devuelve todas las mesas inactivas ordenadas por número con información del mozo asignado.
     * Nota: Se basa en la columna estado para identificar mesas inactivas.
     */
    public static function allInactive(): array {
        $db   = (new Database)->getConnection();
        $stmt = $db->query("
            SELECT m.*, 
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo,
                   (SELECT COUNT(*) FROM pedidos p WHERE p.id_mesa = m.id_mesa AND p.estado NOT IN ('cerrado', 'cancelado')) as pedidos_activos
            FROM mesas m
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            WHERE m.status = 0
            ORDER BY m.numero ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reactiva una mesa marcándola como libre y disponible sin mozo asignado.
     */
    public static function reactivate(int $id): array {
        $db = (new Database)->getConnection();
        
        try {
            // Verificar si la mesa existe
            $checkStmt = $db->prepare("SELECT id_mesa, estado, status FROM mesas WHERE id_mesa = ?");
            $checkStmt->execute([$id]);
            $mesa = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$mesa) {
                return ['success' => false, 'message' => 'La mesa no existe'];
            }
            
            if (isset($mesa['status']) && (int)$mesa['status'] === 1) {
                return ['success' => false, 'message' => 'La mesa ya está activa'];
            }
            
            // Reactivar la mesa dejándola libre y sin mozo asignado
            $stmt = $db->prepare("UPDATE mesas SET status = 1, estado = 'libre', id_mozo = NULL WHERE id_mesa = ?");
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
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE id_mesa = ? AND estado NOT IN ('cerrado', 'cancelado')");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Marca una mesa como inactiva (quita el mozo asignado) por su ID.
     * @return array ['success' => bool, 'message' => string]
     */
    public static function delete(int $id): array {
        $db = (new Database)->getConnection();
        
        try {
            // Verificar si la mesa existe
            $checkStmt = $db->prepare("SELECT id_mesa, id_mozo, estado, status FROM mesas WHERE id_mesa = ?");
            $checkStmt->execute([$id]);
            $mesa = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$mesa) {
                return ['success' => false, 'message' => 'La mesa no existe'];
            }
            
            if (isset($mesa['status']) && (int)$mesa['status'] === 0) {
                return ['success' => false, 'message' => 'La mesa ya está inactiva'];
            }
            
            // Quitar mozo asignado (marcar como inactiva usando status)
            $stmt = $db->prepare("UPDATE mesas SET id_mozo = NULL, status = 0 WHERE id_mesa = ?");
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Mesa desactivada correctamente'];
            } else {
                return ['success' => false, 'message' => 'No se pudo desactivar la mesa'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al desactivar la mesa: ' . $e->getMessage()];
        }
    }
}
