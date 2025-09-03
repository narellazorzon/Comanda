<?php
// src/models/Inventario.php
namespace App\Models;

use App\Config\Database;
use App\Config\Validator;
use PDO;

class Inventario 
{
    /**
     * Obtiene todo el inventario con información de items
     * @return array Lista completa de inventario
     */
    public static function all(): array 
    {
        $db = (new Database)->getConnection();
        $stmt = $db->query("
            SELECT 
                i.*, 
                c.nombre as item_nombre,
                c.categoria,
                c.precio
            FROM inventario i
            JOIN carta c ON i.id_item = c.id_item
            ORDER BY c.categoria, c.nombre ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene inventario de un item específico
     * @param int $id_item ID del item
     * @return array|null Datos del inventario o null si no existe
     */
    public static function findByItem(int $id_item): ?array 
    {
        $db = (new Database)->getConnection();
        $stmt = $db->prepare("
            SELECT 
                i.*, 
                c.nombre as item_nombre,
                c.categoria,
                c.precio
            FROM inventario i
            JOIN carta c ON i.id_item = c.id_item
            WHERE i.id_item = ?
        ");
        $stmt->execute([$id_item]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Obtiene items con stock bajo
     * @return array Items que necesitan reposición
     */
    public static function getStockBajo(): array 
    {
        $db = (new Database)->getConnection();
        $stmt = $db->query("SELECT * FROM vista_stock_bajo");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene resumen por categorías
     * @return array Estadísticas por categoría
     */
    public static function getResumenCategorias(): array 
    {
        $db = (new Database)->getConnection();
        $stmt = $db->query("SELECT * FROM vista_inventario_categoria");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Actualiza stock de un item
     * @param int $id_item ID del item
     * @param int $nueva_cantidad Nueva cantidad
     * @param string $motivo Motivo del cambio
     * @param int $id_usuario ID del usuario que hace el cambio
     * @return bool Éxito de la operación
     */
    public static function actualizarStock(int $id_item, int $nueva_cantidad, string $motivo, int $id_usuario): bool 
    {
        // Validar entrada
        $validation = Validator::validateMultiple([
            'id_item' => $id_item,
            'nueva_cantidad' => $nueva_cantidad,
            'motivo' => $motivo,
            'id_usuario' => $id_usuario
        ], [
            'id_item' => ['type' => 'positive_integer'],
            'nueva_cantidad' => ['type' => 'positive_integer', 'options' => ['min' => 0, 'max' => 9999]],
            'motivo' => ['type' => 'string', 'options' => ['min_length' => 1, 'max_length' => 200]],
            'id_usuario' => ['type' => 'positive_integer']
        ]);
        
        if (!$validation['valid']) {
            throw new \InvalidArgumentException('Datos inválidos: ' . implode(', ', $validation['errors']));
        }
        
        $db = (new Database)->getConnection();
        
        try {
            // Usar stored procedure para actualizar
            $stmt = $db->prepare("CALL sp_actualizar_stock(?, ?, ?, ?)");
            $result = $stmt->execute([
                $validation['data']['id_item'],
                $validation['data']['nueva_cantidad'],
                $validation['data']['motivo'],
                $validation['data']['id_usuario']
            ]);
            
            return $result;
            
        } catch (\PDOException $e) {
            throw new \Exception('Error al actualizar stock: ' . $e->getMessage());
        }
    }
    
    /**
     * Crea registro de inventario para un nuevo item
     * @param array $data Datos del inventario
     * @return bool Éxito de la operación
     */
    public static function create(array $data): bool 
    {
        // Validar datos
        $validation = Validator::validateMultiple($data, [
            'id_item' => ['type' => 'positive_integer'],
            'cantidad_disponible' => ['type' => 'positive_integer', 'options' => ['min' => 0, 'max' => 9999]],
            'cantidad_minima' => ['type' => 'positive_integer', 'options' => ['min' => 0, 'max' => 999]],
            'unidad_medida' => ['type' => 'enum', 'options' => ['values' => ['unidad', 'porcion', 'kg', 'litro', 'gramo']]],
            'costo_unitario' => ['type' => 'price', 'options' => ['decimals' => 2]]
        ]);
        
        if (!$validation['valid']) {
            throw new \InvalidArgumentException('Datos inválidos: ' . implode(', ', $validation['errors']));
        }
        
        $validated_data = $validation['data'];
        
        $db = (new Database)->getConnection();
        
        // Verificar que no exista ya el inventario para este item
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM inventario WHERE id_item = ?");
        $checkStmt->execute([$validated_data['id_item']]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new \InvalidArgumentException('Ya existe inventario para este item');
        }
        
        // Verificar que el item existe
        $itemStmt = $db->prepare("SELECT COUNT(*) FROM carta WHERE id_item = ?");
        $itemStmt->execute([$validated_data['id_item']]);
        if ($itemStmt->fetchColumn() == 0) {
            throw new \InvalidArgumentException('El item especificado no existe en la carta');
        }
        
        $stmt = $db->prepare("
            INSERT INTO inventario (
                id_item, cantidad_disponible, cantidad_minima, 
                unidad_medida, costo_unitario, estado, notas
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $estado = $validated_data['cantidad_disponible'] <= 0 ? 'agotado' : 'disponible';
        
        return $stmt->execute([
            $validated_data['id_item'],
            $validated_data['cantidad_disponible'],
            $validated_data['cantidad_minima'],
            $validated_data['unidad_medida'],
            $validated_data['costo_unitario'],
            $estado,
            $data['notas'] ?? null
        ]);
    }
    
    /**
     * Obtiene movimientos de inventario recientes
     * @param int $limit Número de movimientos a mostrar
     * @param int $id_item Filtrar por item específico (opcional)
     * @return array Lista de movimientos
     */
    public static function getMovimientosRecientes(int $limit = 50, int $id_item = null): array 
    {
        $db = (new Database)->getConnection();
        
        $sql = "
            SELECT 
                m.*,
                c.nombre as item_nombre,
                c.categoria,
                u.nombre as usuario_nombre,
                u.apellido as usuario_apellido
            FROM inventario_movimientos m
            JOIN carta c ON m.id_item = c.id_item
            LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario
        ";
        
        if ($id_item) {
            $sql .= " WHERE m.id_item = ?";
        }
        
        $sql .= " ORDER BY m.fecha_movimiento DESC LIMIT ?";
        
        $stmt = $db->prepare($sql);
        
        if ($id_item) {
            $stmt->execute([$id_item, $limit]);
        } else {
            $stmt->execute([$limit]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verifica disponibilidad de un item para pedidos
     * @param int $id_item ID del item
     * @param int $cantidad_solicitada Cantidad que se quiere pedir
     * @return bool True si hay stock suficiente
     */
    public static function verificarDisponibilidad(int $id_item, int $cantidad_solicitada): bool 
    {
        $inventario = self::findByItem($id_item);
        
        if (!$inventario) {
            // Si no hay registro de inventario, asumir disponible
            return true;
        }
        
        if ($inventario['estado'] === 'agotado' || $inventario['estado'] === 'discontinuado') {
            return false;
        }
        
        return $inventario['cantidad_disponible'] >= $cantidad_solicitada;
    }
    
    /**
     * Obtiene estadísticas generales del inventario
     * @return array Estadísticas completas
     */
    public static function getEstadisticas(): array 
    {
        $db = (new Database)->getConnection();
        
        // Total de items
        $stmt = $db->query("SELECT COUNT(*) as total FROM inventario");
        $total_items = $stmt->fetchColumn();
        
        // Items agotados
        $stmt = $db->query("SELECT COUNT(*) FROM inventario WHERE estado = 'agotado'");
        $items_agotados = $stmt->fetchColumn();
        
        // Items con stock bajo
        $stmt = $db->query("SELECT COUNT(*) FROM inventario WHERE cantidad_disponible <= cantidad_minima AND estado != 'agotado'");
        $items_stock_bajo = $stmt->fetchColumn();
        
        // Valor total del inventario
        $stmt = $db->query("SELECT SUM(cantidad_disponible * COALESCE(costo_unitario, 0)) as valor_total FROM inventario");
        $valor_total = $stmt->fetchColumn() ?: 0;
        
        return [
            'total_items' => $total_items,
            'items_disponibles' => $total_items - $items_agotados,
            'items_agotados' => $items_agotados,
            'items_stock_bajo' => $items_stock_bajo,
            'valor_total_inventario' => $valor_total,
            'porcentaje_disponibilidad' => $total_items > 0 ? round((($total_items - $items_agotados) / $total_items) * 100, 1) : 0
        ];
    }
}