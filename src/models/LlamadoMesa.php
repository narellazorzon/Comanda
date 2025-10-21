<?php
// src/models/LlamadoMesa.php
namespace App\Models;

use App\Config\Database;
use PDO;

class LlamadoMesa {
    public static function all(): array {
        $db = (new Database)->getConnection();
        return $db->query("
            SELECT lm.*, 
                   m.numero as numero_mesa,
                   m.ubicacion as ubicacion_mesa,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
            FROM llamados_mesa lm
            INNER JOIN mesas m ON lm.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            ORDER BY lm.hora_solicitud DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateEstado(int $id, string $nuevoEstado): bool {
        $db = (new Database)->getConnection();
        if ($nuevoEstado === 'completado') {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $atendidoPor = $_SESSION['user']['id_usuario'] ?? null;
            $stmt = $db->prepare("UPDATE llamados_mesa SET estado = ?, hora_atencion = NOW(), atendido_por = ? WHERE id_llamado = ?");
            return $stmt->execute([$nuevoEstado, $atendidoPor, $id]);
        }
        $stmt = $db->prepare("UPDATE llamados_mesa SET estado = ? WHERE id_llamado = ?");
        return $stmt->execute([$nuevoEstado, $id]);
    }

    /**
     * Obtiene todos los llamados pendientes para un mozo específico.
     */
    public static function getByMozo(int $id_mozo): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT lm.*, 
                   m.numero as numero_mesa,
                   m.ubicacion as ubicacion_mesa,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
            FROM llamados_mesa lm
            INNER JOIN mesas m ON lm.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            WHERE m.id_mozo = ? AND lm.estado = 'pendiente'
            ORDER BY lm.hora_solicitud DESC
        ");
        $stmt->execute([$id_mozo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo llamado de mesa.
     */
    public static function create(int $id_mesa): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            INSERT INTO llamados_mesa (id_mesa) 
            VALUES (?)
        ");
        return $stmt->execute([$id_mesa]);
    }

    /**
     * Obtiene todos los llamados pendientes.
     */
    public static function getAllPending(): array {
        $db = (new Database)->getConnection();
        return $db->query("
            SELECT lm.*, 
                   m.numero as numero_mesa,
                   m.ubicacion as ubicacion_mesa,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo
            FROM llamados_mesa lm
            INNER JOIN mesas m ON lm.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            WHERE lm.estado = 'pendiente'
            ORDER BY lm.hora_solicitud DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina un llamado de mesa.
     */
    public static function delete(int $id_llamado): bool {
        // V1: conservar histórico marcando como 'completado' en lugar de borrar
        try {
            $db = (new Database)->getConnection();
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $atendidoPor = $_SESSION['user']['id_usuario'] ?? null;
            $stmt = $db->prepare("UPDATE llamados_mesa SET estado = 'completado', hora_atencion = NOW(), atendido_por = ? WHERE id_llamado = ?");
            $result = $stmt->execute([$atendidoPor, $id_llamado]);
            $rows_affected = $stmt->rowCount();
            error_log("LlamadoMesa::delete (soft-complete) - ID: $id_llamado, Result: " . ($result ? 'true' : 'false') . ", Rows affected: $rows_affected");
            return $result && $rows_affected > 0;
        } catch (\Exception $e) {
            error_log("LlamadoMesa::delete - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina automáticamente llamados con más de 20 minutos.
     */
    public static function deleteOldCalls(): int {
        // Para V1 con histórico: en lugar de eliminar, marcar como 'completado'
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("UPDATE llamados_mesa SET estado = 'completado' WHERE TIMESTAMPDIFF(MINUTE, hora_solicitud, NOW()) > 20 AND estado = 'pendiente'");
        $stmt->execute();
        return $stmt->rowCount();
    }
}

