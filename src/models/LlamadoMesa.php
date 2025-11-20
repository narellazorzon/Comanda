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
                   CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo,
                   u_atendido.nombre as atendido_por_nombre,
                   u_atendido.apellido as atendido_por_apellido,
                   CONCAT(u_atendido.nombre, ' ', u_atendido.apellido) as atendido_por_completo
            FROM llamados_mesa lm
            INNER JOIN mesas m ON lm.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            LEFT JOIN usuarios u_atendido ON lm.atendido_por = u_atendido.id_usuario
            ORDER BY lm.hora_solicitud DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene llamados filtrados por estado
     */
    public static function getByEstado(?string $estado = null): array {
        $db = (new Database)->getConnection();

        $sql = "
            SELECT lm.*, 
                   m.numero AS numero_mesa,
                   m.ubicacion AS ubicacion_mesa,
                   u.nombre AS mozo_nombre,
                   u.apellido AS mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) AS mozo_nombre_completo,
                   u_atendido.nombre AS atendido_por_nombre,
                   u_atendido.apellido AS atendido_por_apellido,
                   CONCAT(u_atendido.nombre, ' ', u_atendido.apellido) AS atendido_por_completo
            FROM llamados_mesa lm
            INNER JOIN mesas m ON lm.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            LEFT JOIN usuarios u_atendido ON lm.atendido_por = u_atendido.id_usuario
        ";

        if ($estado && $estado !== 'todos') {
            $sql .= " WHERE lm.estado = ?";
        }

        $sql .= " ORDER BY lm.hora_solicitud DESC";
        $stmt = $db->prepare($sql);

        if ($estado && $estado !== 'todos') {
            $stmt->execute([$estado]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateEstado(int $id_llamado, string $nuevoEstado): bool {
        try {
            $db = (new Database)->getConnection();
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            $atendidoPor = $_SESSION['user']['id_usuario'] ?? null;

            if ($nuevoEstado === 'atendido') {
                $stmt = $db->prepare("
                    UPDATE llamados_mesa 
                    SET estado = ?, hora_atencion = NOW(), atendido_por = ?
                    WHERE id_llamado = ?
                ");
                $result = $stmt->execute([$nuevoEstado, $atendidoPor, $id_llamado]);
            } else {
                $stmt = $db->prepare("
                    UPDATE llamados_mesa 
                    SET estado = ?
                    WHERE id_llamado = ?
                ");
                $result = $stmt->execute([$nuevoEstado, $id_llamado]);
            }

            return $result && $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log("Error en updateEstado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los llamados para un mozo específico con filtro de estado.
     */
    public static function getByMozo(int $id_mozo, ?string $estado = null): array {
        $db = (new Database)->getConnection();

        $sql = "
            SELECT lm.*, 
                   m.numero AS numero_mesa,
                   m.ubicacion AS ubicacion_mesa,
                   u.nombre AS mozo_nombre,
                   u.apellido AS mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) AS mozo_nombre_completo,
                   u_atendido.nombre AS atendido_por_nombre,
                   u_atendido.apellido AS atendido_por_apellido,
                   CONCAT(u_atendido.nombre, ' ', u_atendido.apellido) AS atendido_por_completo
            FROM llamados_mesa lm
            INNER JOIN mesas m ON lm.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
            LEFT JOIN usuarios u_atendido ON lm.atendido_por = u_atendido.id_usuario
            WHERE m.id_mozo = ?
        ";

        if ($estado && $estado !== 'todos') {
            $sql .= " AND lm.estado = ?";
            $stmt = $db->prepare($sql . " ORDER BY lm.hora_solicitud DESC");
            $stmt->execute([$id_mozo, $estado]);
        } else {
            $stmt = $db->prepare($sql . " ORDER BY lm.hora_solicitud DESC");
            $stmt->execute([$id_mozo]);
        }

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
        try {
            $db = (new Database)->getConnection();
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            $atendidoPor = $_SESSION['user']['id_usuario'] ?? null;

            $stmt = $db->prepare("
                UPDATE llamados_mesa 
                SET estado = 'atendido',
                    hora_atencion = NOW(),
                    atendido_por = ?
                WHERE id_llamado = ?
            ");

            $stmt->execute([$atendidoPor, $id_llamado]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log('Error en LlamadoMesa::delete: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina automáticamente llamados con más de 20 minutos.
     */
    public static function deleteOldCalls(): int {
        // Para V1 con histórico: en lugar de eliminar, marcar como 'atendido'
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("UPDATE llamados_mesa SET estado = 'atendido' WHERE TIMESTAMPDIFF(MINUTE, hora_solicitud, NOW()) > 20 AND estado = 'pendiente'");
        $stmt->execute();
        return $stmt->rowCount();
    }
}

