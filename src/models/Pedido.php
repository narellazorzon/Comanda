<?php
// src/models/Pedido.php
namespace App\Models;

use App\Config\Database;
use PDO;

class Pedido {
    /**
     * Devuelve todos los pedidos con información de mesa y mozo, ordenados por fecha desc.
     * Solo incluye pedidos no eliminados lógicamente.
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
            WHERE p.deleted_at IS NULL
            ORDER BY p.fecha_hora DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todos los pedidos del día actual con información de mesa y mozo.
     * Solo incluye pedidos no eliminados lógicamente.
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
            AND p.deleted_at IS NULL
            ORDER BY p.fecha_hora DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todos los pedidos asignados a un mozo.
     * Solo incluye pedidos no eliminados lógicamente.
     */
    public static function allByMozo(int $mozoId): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT *
            FROM pedidos
            WHERE id_mozo = ?
            AND deleted_at IS NULL
            ORDER BY fecha_hora DESC
        ");
        $stmt->execute([$mozoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve los pedidos del día actual para las mesas asignadas a un mozo.
     * Solo incluye pedidos no eliminados lógicamente.
     */
    public static function todayByMesoAssigned(int $mozoId): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
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
            AND (p.id_mozo = ? OR m.id_mozo = ?)
            AND p.deleted_at IS NULL
            ORDER BY p.fecha_hora DESC
        ");
        $stmt->execute([$mozoId, $mozoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todos los pedidos de una mesa (cliente).
     * Solo incluye pedidos no eliminados lógicamente.
     */
    public static function allByMesa(int $mesaId): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT * 
            FROM pedidos 
            WHERE id_mesa = ? 
            AND deleted_at IS NULL
            ORDER BY fecha_hora DESC
        ");
        $stmt->execute([$mesaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo pedido y devuelve su ID.
     */
    public static function create(array $data): int {
        $db = (new Database)->getConnection();
        
        try {
            // Log para depuración
            error_log("Pedido::create called with data: " . json_encode($data));

            // Iniciar transacción
            $db->beginTransaction();
            
            // 1. Crear el pedido
            $stmt = $db->prepare("
                INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, id_mozo, forma_pago, observaciones, cliente_nombre, cliente_email)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");

            // Asegurarnos de que el estado se establezca correctamente
            $estado = 'pendiente';
            $params = [
                $data['id_mesa'] ?? null,
                $data['modo_consumo'] ?? 'stay',
                0.00, // Temporal, se actualizará después
                $estado,
                $data['id_mozo'] ?? null,
                $data['forma_pago'] ?? null,
                $data['observaciones'] ?? null,
                $data['cliente_nombre'] ?? null,
                $data['cliente_email'] ?? null
            ];

            error_log("Creating pedido with estado: '$estado' (hex: " . bin2hex($estado) . ")");
            $stmt->execute($params);

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
            
            // 4. Actualizar estado de la mesa a ocupada si se asignó una mesa
            if (!empty($data['id_mesa'])) {
                $stmtMesa = $db->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id_mesa = ?");
                $stmtMesa->execute([$data['id_mesa']]);
            }
            
            // Confirmar transacción
            $db->commit();
            
            return $pedidoId;
            
        } catch (\Exception $e) {
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
     * Elimina lógicamente un pedido por su ID y libera la mesa si no tiene más pedidos activos.
     * Implementa borrado lógico marcando deleted_at con timestamp actual.
     */
    public static function delete(int $id): bool {
        $db = (new Database)->getConnection();
        
        try {
            $db->beginTransaction();
            
            // 1. Obtener información del pedido antes de eliminarlo
            $stmtPedido = $db->prepare("SELECT id_mesa, modo_consumo FROM pedidos WHERE id_pedido = ? AND deleted_at IS NULL");
            $stmtPedido->execute([$id]);
            $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);
            
            if (!$pedido) {
                $db->rollback();
                return false;
            }
            
            // 2. Marcar como eliminado lógicamente
            $stmt = $db->prepare("UPDATE pedidos SET deleted_at = NOW() WHERE id_pedido = ?");
            $resultado = $stmt->execute([$id]);
            
            if (!$resultado) {
                $db->rollback();
                return false;
            }
            
            // 3. Verificar si la mesa tiene más pedidos activos antes de liberarla
            if ($pedido['id_mesa'] && $pedido['modo_consumo'] === 'stay') {
                // Contar pedidos activos restantes en la mesa (no eliminados)
                $stmtCount = $db->prepare("
                    SELECT COUNT(*) 
                    FROM pedidos 
                    WHERE id_mesa = ? 
                    AND estado NOT IN ('cerrado', 'cancelado')
                    AND modo_consumo = 'stay'
                    AND deleted_at IS NULL
                ");
                $stmtCount->execute([$pedido['id_mesa']]);
                $pedidosRestantes = $stmtCount->fetchColumn();
                
                // Solo liberar la mesa si no hay más pedidos activos
                if ($pedidosRestantes == 0) {
                    $stmtMesa = $db->prepare("UPDATE mesas SET estado = 'libre' WHERE id_mesa = ?");
                    $stmtMesa->execute([$pedido['id_mesa']]);
                }
            }
            
            $db->commit();
            return true;
            
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
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

    /**
     * Actualiza solo la forma de pago de un pedido sin tocar otros campos.
     */
    public static function setFormaPago(int $id, ?string $formaPago): bool {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("UPDATE pedidos SET forma_pago = ? WHERE id_pedido = ?");
        return $stmt->execute([$formaPago, $id]);
    }

    /**
     * Obtiene un pedido por su ID con información completa.
     * Solo incluye pedidos no eliminados lógicamente.
     */
    public static function find(int $id): ?array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
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
            WHERE p.id_pedido = ?
            AND p.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene los detalles de un pedido con información de los items.
     */
    public static function getDetalles(int $id): array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT dp.*, 
                   c.nombre as item_nombre,
                   c.descripcion as item_descripcion,
                   c.categoria as item_categoria,
                   c.precio as precio_actual,
                   (dp.cantidad * dp.precio_unitario) as subtotal,
                   '' as detalle
            FROM detalle_pedido dp
            JOIN carta c ON dp.id_item = c.id_item
            WHERE dp.id_pedido = ?
            ORDER BY dp.id_detalle
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Actualiza un pedido existente con nuevos datos.
     */
    public static function update(int $id, array $data): bool {
        $db = (new Database)->getConnection();
        
        try {
            $db->beginTransaction();
            
            // 1. Obtener mesa actual del pedido (siempre necesaria para liberar)
            $stmtActual = $db->prepare("SELECT id_mesa FROM pedidos WHERE id_pedido = ?");
            $stmtActual->execute([$id]);
            $mesaActual = $stmtActual->fetchColumn();
            
            // 2. Validar que la mesa esté disponible si se está cambiando
            if (isset($data['id_mesa']) && $data['id_mesa']) {
                // Verificar estado actual de la mesa
                $stmtMesa = $db->prepare("SELECT estado, id_mozo FROM mesas WHERE id_mesa = ?");
                $stmtMesa->execute([$data['id_mesa']]);
                $mesaData = $stmtMesa->fetch(PDO::FETCH_ASSOC);

                // Si la mesa no es libre y es diferente a la actual, lanzar error
                if ($mesaData && $mesaData['estado'] !== 'libre' && $data['id_mesa'] != $mesaActual) {
                    throw new \Exception("No se puede cambiar a la mesa seleccionada. Estado actual: " . $mesaData['estado']);
                }

                $idMozo = $mesaData ? $mesaData['id_mozo'] : null;
            }

            // 3. Actualizar datos básicos del pedido incluyendo el mozo
            $stmt = $db->prepare("
                UPDATE pedidos
                SET id_mesa = ?, id_mozo = ?, modo_consumo = ?, forma_pago = ?, observaciones = ?
                WHERE id_pedido = ?
            ");
            $stmt->execute([
                $data['id_mesa'] ?? null,
                $idMozo,
                $data['modo_consumo'] ?? 'stay',
                $data['forma_pago'] ?? null,
                $data['observaciones'] ?? null,
                $id
            ]);
            
            // 4. Eliminar detalles existentes solo si se envían nuevos items
            if (!empty($data['items'])) {
                $stmt = $db->prepare("DELETE FROM detalle_pedido WHERE id_pedido = ?");
                $stmt->execute([$id]);
            }

            // 5. Insertar nuevos detalles y calcular total
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
                            $stmtItem->execute([$id, $idItem, $cantidad, $precio]);
                        }
                    }
                }
            } else {
                // Si no se envían items, mantener el total actual
                $stmtTotal = $db->prepare("SELECT total FROM pedidos WHERE id_pedido = ?");
                $stmtTotal->execute([$id]);
                $total = $stmtTotal->fetchColumn();
            }

            // 6. Actualizar el total del pedido
            $stmtUpdate = $db->prepare("UPDATE pedidos SET total = ? WHERE id_pedido = ?");
            $stmtUpdate->execute([$total, $id]);
            
            // 7. Actualizar estado de la mesa si se cambió a una nueva mesa
            // Los triggers de la base de datos no manejan cambios de mesa en edición, solo cambios de estado
            if (isset($data['id_mesa']) && $data['id_mesa'] && $data['id_mesa'] != $mesaActual) {
                // Cambiar estado de la mesa anterior a libre si existía
                if ($mesaActual) {
                    $stmtMesaAnterior = $db->prepare("UPDATE mesas SET estado = 'libre' WHERE id_mesa = ?");
                    $stmtMesaAnterior->execute([$mesaActual]);
                }
                
                // Cambiar estado de la nueva mesa a ocupada
                $stmtMesaNueva = $db->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id_mesa = ?");
                $stmtMesaNueva->execute([$data['id_mesa']]);
            }
            
            $db->commit();
            return true;
            
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
}
