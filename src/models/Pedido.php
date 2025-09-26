<?php
// src/models/Pedido.php
namespace App\Models;

use App\Database\QueryBuilder;
use App\Config\Database;
use PDO;
use Exception;

class Pedido extends BaseModel {
    /**
     * Devuelve todos los pedidos con información de mesa y mozo, ordenados por fecha desc.
     */
    public static function all(): array {
        $sql = QueryBuilder::pedidosWithMesaAndMozo();
        return self::fetchAll($sql);
    }

    /**
     * Devuelve todos los pedidos del día actual con información de mesa y mozo.
     */
    public static function todayOnly(): array {
        $sql = QueryBuilder::pedidosWithMesaAndMozo('DATE(p.fecha_hora) = CURDATE()');
        return self::fetchAll($sql);
    }

    /**
     * Devuelve todos los pedidos asignados a un mozo.
     */
    public static function allByMozo(int $mozoId): array {
<<<<<<< HEAD
        $sql = QueryBuilder::pedidosWithMesaAndMozo('p.id_mozo = :mozoId');
        return self::fetchAll($sql, ['mozoId' => $mozoId]);
=======
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT *
            FROM pedidos
            WHERE id_mozo = ?
            ORDER BY fecha_hora DESC
        ");
        $stmt->execute([$mozoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
>>>>>>> develop1
    }

    /**
     * Devuelve los pedidos del día actual para las mesas asignadas a un mozo.
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
            AND m.id_mozo = ?
            ORDER BY p.fecha_hora DESC
        ");
        $stmt->execute([$mozoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve todos los pedidos de una mesa (cliente).
     */
    public static function allByMesa(int $mesaId): array {
        $sql = QueryBuilder::pedidosWithMesaAndMozo('p.id_mesa = :mesaId');
        return self::fetchAll($sql, ['mesaId' => $mesaId]);
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
                $data['id_mozo'] ?? (isset($_SESSION['user']['id_usuario']) ? $_SESSION['user']['id_usuario'] : null),
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
        return parent::updateTable('pedidos', ['estado' => $nuevoEstado], 'id_pedido = :id', ['id' => $id]) > 0;
    }

    /**
     * Elimina un pedido por su ID y libera la mesa si no tiene más pedidos activos.
     */
    public static function delete(int $id): bool {
<<<<<<< HEAD
        return parent::deleteFrom('pedidos', 'id_pedido = :id', ['id' => $id]) > 0;
=======
        $db = (new Database)->getConnection();
        
        try {
            $db->beginTransaction();
            
            // 1. Obtener información del pedido antes de eliminarlo
            $stmtPedido = $db->prepare("SELECT id_mesa, modo_consumo FROM pedidos WHERE id_pedido = ?");
            $stmtPedido->execute([$id]);
            $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);
            
            if (!$pedido) {
                $db->rollback();
                return false;
            }
            
            // 2. Eliminar el pedido
            $stmt = $db->prepare("DELETE FROM pedidos WHERE id_pedido = ?");
            $resultado = $stmt->execute([$id]);
            
            if (!$resultado) {
                $db->rollback();
                return false;
            }
            
            // 3. Verificar si la mesa tiene más pedidos activos antes de liberarla
            if ($pedido['id_mesa'] && $pedido['modo_consumo'] === 'stay') {
                // Contar pedidos activos restantes en la mesa
                $stmtCount = $db->prepare("
                    SELECT COUNT(*) 
                    FROM pedidos 
                    WHERE id_mesa = ? 
                    AND estado NOT IN ('cerrado', 'cancelado')
                    AND modo_consumo = 'stay'
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
>>>>>>> develop1
    }

    /**
     * Actualiza el total de un pedido.
     */
    public static function updateTotal(int $id, float $total): bool {
        return parent::updateTable('pedidos', ['total' => $total], 'id_pedido = :id', ['id' => $id]) > 0;
    }

    /**
     * Obtiene un pedido por su ID con información completa.
     */
    public static function find(int $id): ?array {
        $sql = QueryBuilder::pedidosWithMesaAndMozo('p.id_pedido = :id');
        return self::fetchOne($sql, ['id' => $id]);
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

            // 2. Actualizar datos básicos del pedido
            $stmt = $db->prepare("
                UPDATE pedidos
                SET id_mesa = ?, modo_consumo = ?, forma_pago = ?, observaciones = ?
            ");
            $stmt->execute([
                $data['id_mesa'] ?? null,
                $data['modo_consumo'] ?? 'stay',
                $data['forma_pago'] ?? null,
                $data['observaciones'] ?? null,
                $id
            ]);

            // 2. Eliminar detalles existentes
            $stmt = $db->prepare("DELETE FROM detalle_pedido WHERE id_pedido = ?");
            $stmt->execute([$id]);

            // 3. Insertar nuevos detalles y calcular total
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

<<<<<<< HEAD
            // 4. Actualizar el total del pedido
            $stmtUpdate = $db->prepare("UPDATE pedidos SET total = ? WHERE id_pedido = ?");
            $stmtUpdate->execute([$total, $id]);

            $db->commit();
            return true;

        } catch (Exception $e) {
=======
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

            // 4. Actualizar el total del pedido
            $stmtUpdate = $db->prepare("UPDATE pedidos SET total = ? WHERE id_pedido = ?");
            $stmtUpdate->execute([$total, $id]);

            $db->commit();
            return true;

        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Obtiene un pedido con todos sus detalles incluyendo mesa y mozo.
     */
    public static function findWithDetails(int $id): ?array {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT p.*,
                   m.numero as numero_mesa,
                   m.ubicacion as ubicacion_mesa,
                   mz.id_usuario as id_mozo,
                   CONCAT(mz.nombre, ' ', mz.apellido) as nombre_mozo_completo
            FROM pedidos p
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN usuarios mz ON p.id_mozo = mz.id_usuario
            WHERE p.id_pedido = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Calcula el total del pedido con propina.
     */
    public static function calcularTotalConPropina(int $idPedido, float $porcentajePropina): array {
        $pedido = self::findWithDetails($idPedido);

        if (!$pedido) {
            return [
                'subtotal' => 0,
                'propina' => 0,
                'total' => 0
            ];
        }

        $subtotal = (float)$pedido['total'];
        $propina = $subtotal * ($porcentajePropina / 100);
        $total = $subtotal + $propina;

        return [
            'subtotal' => $subtotal,
            'propina' => round($propina, 2),
            'total' => round($total, 2)
        ];
    }
}
