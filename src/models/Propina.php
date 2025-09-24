<?php
// src/models/Propina.php
namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

class Propina {
    /**
     * Crea una nueva propina asociada a un pedido y mozo
     */
    public static function create(array $data): int {
        $db = (new Database)->getConnection();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora)
                VALUES (:id_pedido, :id_mozo, :monto, NOW())
            ");
            
            $stmt->execute([
                ':id_pedido' => $data['id_pedido'],
                ':id_mozo' => $data['id_mozo'] ?? null,
                ':monto' => $data['monto']
            ]);
            
            return (int)$db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error al registrar propina: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene la propina de un pedido específico
     */
    public static function findByPedido(int $idPedido): ?array {
        $db = (new Database)->getConnection();
        
        $stmt = $db->prepare("
            SELECT p.*, 
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido
            FROM propinas p
            LEFT JOIN usuarios u ON p.id_mozo = u.id_usuario
            WHERE p.id_pedido = ?
        ");
        
        $stmt->execute([$idPedido]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
    
    /**
     * Obtiene todas las propinas de un mozo en un período
     */
    public static function getByMozo(int $idMozo, ?string $fechaInicio = null, ?string $fechaFin = null): array {
        $db = (new Database)->getConnection();
        
        $sql = "
            SELECT p.*, 
                   ped.id_mesa,
                   m.numero as numero_mesa
            FROM propinas p
            JOIN pedidos ped ON p.id_pedido = ped.id_pedido
            LEFT JOIN mesas m ON ped.id_mesa = m.id_mesa
            WHERE p.id_mozo = ?
        ";
        
        $params = [$idMozo];
        
        if ($fechaInicio && $fechaFin) {
            $sql .= " AND DATE(p.fecha_hora) BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }
        
        $sql .= " ORDER BY p.fecha_hora DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcula estadísticas de propinas por mozo
     */
    public static function getEstadisticasByMozo(int $idMozo, ?string $mes = null): array {
        $db = (new Database)->getConnection();
        
        $sql = "
            SELECT 
                COUNT(*) as total_propinas,
                SUM(monto) as monto_total,
                AVG(monto) as promedio_propina,
                MAX(monto) as propina_maxima,
                MIN(monto) as propina_minima
            FROM propinas
            WHERE id_mozo = ?
        ";
        
        $params = [$idMozo];
        
        if ($mes) {
            $sql .= " AND DATE_FORMAT(fecha_hora, '%Y-%m') = ?";
            $params[] = $mes;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Actualiza el monto de una propina existente
     */
    public static function update(int $idPropina, float $nuevoMonto): bool {
        $db = (new Database)->getConnection();
        
        $stmt = $db->prepare("
            UPDATE propinas 
            SET monto = ?
            WHERE id_propina = ?
        ");
        
        return $stmt->execute([$nuevoMonto, $idPropina]);
    }
    
    /**
     * Elimina una propina (solo para casos especiales)
     */
    public static function delete(int $idPropina): bool {
        $db = (new Database)->getConnection();
        
        $stmt = $db->prepare("DELETE FROM propinas WHERE id_propina = ?");
        return $stmt->execute([$idPropina]);
    }
}