<?php
// src/models/Pedido.php
namespace App\Models;

use App\Config\Database;
use PDO;

class Pedido {
    /**
     * Devuelve todos los pedidos con información de mesa y mozo, ordenados por fecha desc.
     */
    public static function all(): array {
        $db = (new Database)->getConnection();
        return $db->query("
            SELECT p.*, 
                   m.numero as numero_mesa,
                   m.ubicacion as ubicacion_mesa,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as nombre_mozo_completo,
                   p.fecha_hora as fecha_creacion
            FROM pedidos p
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON p.id_mozo = u.id_usuario
            ORDER BY p.fecha_hora DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todos los pedidos del día actual con información de mesa y mozo.
     */
    public static function todayOnly(): array {
        $db = (new Database)->getConnection();
        return $db->query("
            SELECT p.*, 
                   m.numero as numero_mesa,
                   m.ubicacion as ubicacion_mesa,
                   u.nombre as mozo_nombre,
                   u.apellido as mozo_apellido,
                   CONCAT(u.nombre, ' ', u.apellido) as nombre_mozo_completo,
                   p.fecha_hora as fecha_creacion
            FROM pedidos p
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON p.id_mozo = u.id_usuario
            WHERE DATE(p.fecha_hora) = CURDATE()
            ORDER BY p.fecha_hora DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todos los pedidos asignados a un mozo.
     */
    public static function allByMozo(int $mozoId): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT * 
            FROM pedidos 
            WHERE id_mozo = ? 
            ORDER BY fecha_hora DESC
        ");
        $stmt->execute([$mozoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todos los pedidos de una mesa (cliente).
     */
    public static function allByMesa(int $mesaId): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT * 
            FROM pedidos 
            WHERE id_mesa = ? 
            ORDER BY fecha_hora DESC
        ");
        $stmt->execute([$mesaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un pedido por su ID.
     */
    public static function find(int $id): ?array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("SELECT * FROM pedidos WHERE id_pedido = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Crea un nuevo pedido y devuelve su ID.
     */
    public static function create(array $data): int {
        $db = (new Database)->getConnection();
        
        try {
            // Iniciar transacción
            $db->beginTransaction();
            
            // 1. Crear el pedido
            $stmt = $db->prepare("
                INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, id_mozo)
                VALUES (?,?,?,?,?)
            ");
            $stmt->execute([
                $data['id_mesa'] ?? null,
                $data['modo_consumo'] ?? 'stay',
                0.00, // Temporal, se actualizará después
                'pendiente',
                $_SESSION['user']['id_usuario'] ?? null
            ]);
            
            $pedidoId = (int)$db->lastInsertId();
            
            // 2. Guardar items del pedido y calcular total
            $total = 0.00;
            if (!empty($data['items'])) {
                $stmtItem = $db->prepare("
                    INSERT INTO detalle_pedido (id_pedido, id_item, cantidad, precio_unitario)
                    VALUES (?, ?, ?, ?)
                ");
                
                $stmtPrecio = $db->prepare("SELECT precio FROM carta WHERE id_item = ?");
                
                foreach ($data['items'] as $item) {
                    $cantidad = (int)($item['cantidad'] ?? 1);
                    $idItem = (int)($item['id_item'] ?? 0);
                    
                    if ($cantidad > 0 && $idItem > 0) {
                        // Obtener precio actual del item
                        $stmtPrecio->execute([$idItem]);
                        $precio = $stmtPrecio->fetchColumn();
                        
                        if ($precio) {
                            $subtotal = $precio * $cantidad;
                            $total += $subtotal;
                            
                            // Guardar detalle
                            $stmtItem->execute([$pedidoId, $idItem, $cantidad, $precio]);
                        }
                    }
                }
            }
            
            // 3. Actualizar el total del pedido
            $stmtUpdate = $db->prepare("UPDATE pedidos SET total = ? WHERE id_pedido = ?");
            $stmtUpdate->execute([$total, $pedidoId]);
            
            // Confirmar transacción
            $db->commit();
            
            return $pedidoId;
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Actualiza el estado de un pedido.
     */
    public static function updateEstado(int $id, string $nuevoEstado): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            UPDATE pedidos 
            SET estado = ? 
            WHERE id_pedido = ?
        ");
        return $stmt->execute([$nuevoEstado, $id]);
    }

    /**
     * Elimina un pedido por su ID.
     */
    public static function delete(int $id): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("DELETE FROM pedidos WHERE id_pedido = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Actualiza el total de un pedido.
     */
    public static function updateTotal(int $id, float $total): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            UPDATE pedidos 
            SET total = ? 
            WHERE id_pedido = ?
        ");
        return $stmt->execute([$total, $id]);
    }
}
