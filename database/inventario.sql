-- =====================================================
-- SISTEMA BÁSICO DE INVENTARIOS - SISTEMA COMANDA
-- Tabla para controlar stock de items de carta
-- =====================================================

USE comanda;

-- =====================================================
-- Tabla de inventario para items de carta
-- =====================================================
CREATE TABLE inventario (
    id_inventario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_item INT UNSIGNED NOT NULL,
    cantidad_disponible INT NOT NULL DEFAULT 0,
    cantidad_minima INT NOT NULL DEFAULT 5,
    unidad_medida ENUM('unidad','porcion','kg','litro','gramo') NOT NULL DEFAULT 'unidad',
    costo_unitario DECIMAL(10,2) NULL,
    fecha_ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estado ENUM('disponible','agotado','discontinuado') NOT NULL DEFAULT 'disponible',
    notas TEXT NULL,
    
    CONSTRAINT fk_inventario_item 
        FOREIGN KEY (id_item) REFERENCES carta(id_item) 
        ON UPDATE CASCADE ON DELETE CASCADE,
        
    INDEX idx_inventario_item (id_item),
    INDEX idx_inventario_estado (estado),
    INDEX idx_inventario_stock_bajo (cantidad_disponible)
) ENGINE=InnoDB;

-- =====================================================
-- Tabla de movimientos de inventario (historial)
-- =====================================================
CREATE TABLE inventario_movimientos (
    id_movimiento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_item INT UNSIGNED NOT NULL,
    tipo_movimiento ENUM('entrada','salida','ajuste','venta') NOT NULL,
    cantidad INT NOT NULL,
    cantidad_anterior INT NOT NULL,
    cantidad_nueva INT NOT NULL,
    motivo VARCHAR(200) NULL,
    id_pedido INT UNSIGNED NULL, -- Para ventas
    id_usuario INT UNSIGNED NULL, -- Quien hizo el movimiento
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_movimiento_item 
        FOREIGN KEY (id_item) REFERENCES carta(id_item) 
        ON UPDATE CASCADE ON DELETE CASCADE,
        
    CONSTRAINT fk_movimiento_pedido 
        FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) 
        ON UPDATE CASCADE ON DELETE SET NULL,
        
    CONSTRAINT fk_movimiento_usuario 
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) 
        ON UPDATE CASCADE ON DELETE SET NULL,
        
    INDEX idx_movimiento_item (id_item),
    INDEX idx_movimiento_fecha (fecha_movimiento),
    INDEX idx_movimiento_tipo (tipo_movimiento)
) ENGINE=InnoDB;

-- =====================================================
-- Trigger: Descontar stock automáticamente en ventas
-- =====================================================
DELIMITER $$
CREATE TRIGGER tr_pedido_item_descontar_stock
    AFTER INSERT ON pedido_items
    FOR EACH ROW
BEGIN
    DECLARE stock_actual INT DEFAULT 0;
    DECLARE stock_nuevo INT DEFAULT 0;
    
    -- Obtener stock actual
    SELECT cantidad_disponible INTO stock_actual
    FROM inventario 
    WHERE id_item = NEW.id_item;
    
    -- Solo descontar si existe registro de inventario
    IF stock_actual IS NOT NULL THEN
        -- Calcular nuevo stock
        SET stock_nuevo = stock_actual - NEW.cantidad;
        
        -- Actualizar inventario
        UPDATE inventario 
        SET cantidad_disponible = stock_nuevo,
            estado = CASE 
                WHEN stock_nuevo <= 0 THEN 'agotado'
                WHEN stock_nuevo <= cantidad_minima THEN 'disponible'
                ELSE 'disponible'
            END
        WHERE id_item = NEW.id_item;
        
        -- Registrar movimiento
        INSERT INTO inventario_movimientos (
            id_item, tipo_movimiento, cantidad, 
            cantidad_anterior, cantidad_nueva, 
            motivo, id_pedido
        ) VALUES (
            NEW.id_item, 'venta', NEW.cantidad,
            stock_actual, stock_nuevo,
            'Venta automática', 
            (SELECT id_pedido FROM pedido_items pi WHERE pi.id_item = NEW.id_item AND pi.cantidad = NEW.cantidad LIMIT 1)
        );
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- Vista: Items con stock bajo
-- =====================================================
CREATE VIEW vista_stock_bajo AS
SELECT 
    c.nombre as item_nombre,
    c.categoria,
    i.cantidad_disponible,
    i.cantidad_minima,
    i.unidad_medida,
    i.estado,
    i.fecha_ultima_actualizacion
FROM inventario i
JOIN carta c ON i.id_item = c.id_item
WHERE i.cantidad_disponible <= i.cantidad_minima
   OR i.estado = 'agotado'
ORDER BY i.cantidad_disponible ASC;

-- =====================================================
-- Vista: Resumen de inventario por categoría
-- =====================================================
CREATE VIEW vista_inventario_categoria AS
SELECT 
    c.categoria,
    COUNT(*) as total_items,
    SUM(CASE WHEN i.estado = 'disponible' THEN 1 ELSE 0 END) as items_disponibles,
    SUM(CASE WHEN i.estado = 'agotado' THEN 1 ELSE 0 END) as items_agotados,
    SUM(CASE WHEN i.cantidad_disponible <= i.cantidad_minima THEN 1 ELSE 0 END) as items_stock_bajo,
    AVG(i.cantidad_disponible) as promedio_stock
FROM inventario i
JOIN carta c ON i.id_item = c.id_item
GROUP BY c.categoria
ORDER BY c.categoria;

-- =====================================================
-- Datos iniciales básicos para inventario
-- =====================================================

-- Insertar inventario inicial para todos los items de carta existentes
INSERT INTO inventario (id_item, cantidad_disponible, cantidad_minima, unidad_medida, costo_unitario, estado)
SELECT 
    id_item,
    CASE 
        WHEN categoria = 'Entrada' THEN 25
        WHEN categoria = 'Plato Principal' THEN 15
        WHEN categoria = 'Postre' THEN 20
        WHEN categoria = 'Bebida' THEN 50
        ELSE 10
    END as cantidad_inicial,
    CASE 
        WHEN categoria = 'Entrada' THEN 5
        WHEN categoria = 'Plato Principal' THEN 3
        WHEN categoria = 'Postre' THEN 5
        WHEN categoria = 'Bebida' THEN 10
        ELSE 2
    END as minimo,
    CASE 
        WHEN categoria = 'Bebida' THEN 'litro'
        WHEN categoria = 'Postre' THEN 'porcion'
        ELSE 'unidad'
    END as unidad,
    precio * 0.4 as costo_estimado, -- 40% del precio de venta
    'disponible'
FROM carta
WHERE NOT EXISTS (SELECT 1 FROM inventario WHERE inventario.id_item = carta.id_item);

-- =====================================================
-- Procedimiento: Actualizar stock manualmente
-- =====================================================
DELIMITER $$
CREATE PROCEDURE sp_actualizar_stock(
    IN p_id_item INT,
    IN p_nueva_cantidad INT,
    IN p_motivo VARCHAR(200),
    IN p_id_usuario INT
)
BEGIN
    DECLARE cantidad_anterior INT DEFAULT 0;
    DECLARE cantidad_minima_item INT DEFAULT 0;
    
    -- Obtener cantidad actual y mínima
    SELECT cantidad_disponible, cantidad_minima
    INTO cantidad_anterior, cantidad_minima_item
    FROM inventario 
    WHERE id_item = p_id_item;
    
    -- Actualizar inventario
    UPDATE inventario 
    SET cantidad_disponible = p_nueva_cantidad,
        estado = CASE 
            WHEN p_nueva_cantidad <= 0 THEN 'agotado'
            WHEN p_nueva_cantidad <= cantidad_minima_item THEN 'disponible'
            ELSE 'disponible'
        END
    WHERE id_item = p_id_item;
    
    -- Registrar movimiento
    INSERT INTO inventario_movimientos (
        id_item, tipo_movimiento, cantidad,
        cantidad_anterior, cantidad_nueva,
        motivo, id_usuario
    ) VALUES (
        p_id_item, 
        CASE 
            WHEN p_nueva_cantidad > cantidad_anterior THEN 'entrada'
            WHEN p_nueva_cantidad < cantidad_anterior THEN 'salida'
            ELSE 'ajuste'
        END,
        ABS(p_nueva_cantidad - cantidad_anterior),
        cantidad_anterior, 
        p_nueva_cantidad,
        p_motivo, 
        p_id_usuario
    );
END$$
DELIMITER ;

-- =====================================================
-- CONSULTAS DE EJEMPLO PARA PROBAR EL SISTEMA
-- =====================================================
/*
-- Ver todos los items con su inventario
SELECT c.nombre, c.categoria, i.cantidad_disponible, i.cantidad_minima, i.estado
FROM carta c
LEFT JOIN inventario i ON c.id_item = i.id_item
ORDER BY c.categoria, c.nombre;

-- Ver items con stock bajo
SELECT * FROM vista_stock_bajo;

-- Ver resumen por categoría  
SELECT * FROM vista_inventario_categoria;

-- Ver movimientos recientes
SELECT c.nombre, m.tipo_movimiento, m.cantidad, m.motivo, m.fecha_movimiento
FROM inventario_movimientos m
JOIN carta c ON m.id_item = c.id_item
ORDER BY m.fecha_movimiento DESC
LIMIT 20;

-- Actualizar stock de un item
CALL sp_actualizar_stock(1, 50, 'Reposición semanal', 1);
*/