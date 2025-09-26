-- =====================================================
-- TRIGGERS PARA CONSISTENCIA DE DATOS - SISTEMA COMANDA
-- Mantiene la consistencia automática entre mesas y pedidos
-- =====================================================

USE comanda;

-- =====================================================
-- 1. TRIGGER: Actualizar estado de mesa al crear pedido
-- =====================================================
DELIMITER $$
CREATE TRIGGER tr_pedido_insert_update_mesa
    AFTER INSERT ON pedidos
    FOR EACH ROW
BEGIN
    -- Solo actualizar si el pedido es tipo "stay" (para mesa)
    IF NEW.modalidad = 'stay' AND NEW.id_mesa IS NOT NULL THEN
        -- Actualizar mesa a ocupada cuando se crea un pedido
        UPDATE mesas 
        SET estado = 'ocupada' 
        WHERE id_mesa = NEW.id_mesa;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- 2. TRIGGER: Actualizar estado de mesa al cerrar pedido
-- =====================================================
DELIMITER $$
CREATE TRIGGER tr_pedido_update_mesa_estado
    AFTER UPDATE ON pedidos
    FOR EACH ROW
BEGIN
    -- Solo procesar cambios de estado para pedidos tipo "stay"
    IF NEW.modalidad = 'stay' AND NEW.id_mesa IS NOT NULL THEN
        -- Si el pedido se cierra, verificar si quedan pedidos activos en la mesa
        IF NEW.estado = 'cerrado' AND OLD.estado != 'cerrado' THEN
            -- Contar pedidos activos en la mesa (no cerrados)
            SET @pedidos_activos = (
                SELECT COUNT(*)
                FROM pedidos
                WHERE id_mesa = NEW.id_mesa 
                AND estado != 'cerrado'
                AND modalidad = 'stay'
            );
            
            -- Si no hay pedidos activos, liberar la mesa
            IF @pedidos_activos = 0 THEN
                UPDATE mesas 
                SET estado = 'libre' 
                WHERE id_mesa = NEW.id_mesa;
            END IF;
        END IF;
        
        -- Si el pedido se reactiva (de cerrado a otro estado), ocupar la mesa
        IF OLD.estado = 'cerrado' AND NEW.estado != 'cerrado' THEN
            UPDATE mesas 
            SET estado = 'ocupada' 
            WHERE id_mesa = NEW.id_mesa;
        END IF;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- 3. TRIGGER: Prevenir eliminación de mesa ocupada
-- =====================================================
DELIMITER $$
CREATE TRIGGER tr_mesa_delete_check
    BEFORE DELETE ON mesas
    FOR EACH ROW
BEGIN
    -- Verificar si hay pedidos activos en la mesa
    SET @pedidos_activos = (
        SELECT COUNT(*)
        FROM pedidos
        WHERE id_mesa = OLD.id_mesa 
        AND estado != 'cerrado'
        AND modalidad = 'stay'
    );
    
    -- Si hay pedidos activos, prevenir eliminación
    IF @pedidos_activos > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'No se puede eliminar una mesa con pedidos activos';
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- 4. TRIGGER: Prevenir inactivación de mozo con mesas ocupadas
-- =====================================================
DELIMITER $$
CREATE TRIGGER tr_usuario_update_check_mesas
    BEFORE UPDATE ON usuarios
    FOR EACH ROW
BEGIN
    -- Solo verificar cuando se inactiva un mozo
    IF OLD.estado = 'activo' AND NEW.estado = 'inactivo' AND NEW.rol = 'mozo' THEN
        -- Verificar si tiene mesas con pedidos activos
        SET @mesas_ocupadas = (
            SELECT COUNT(*)
            FROM mesas m
            JOIN pedidos p ON m.id_mesa = p.id_mesa
            WHERE m.id_mozo = OLD.id_usuario 
            AND p.estado != 'cerrado'
            AND p.modalidad = 'stay'
        );
        
        -- Si tiene mesas ocupadas, prevenir inactivación
        IF @mesas_ocupadas > 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'No se puede inactivar un mozo que tiene mesas ocupadas con pedidos activos';
        END IF;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- 5. TRIGGER: Log de cambios críticos (auditoría básica)
-- =====================================================

-- Primero crear tabla de auditoría
CREATE TABLE auditoria_mesas (
    id_auditoria INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT UNSIGNED NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    estado_anterior ENUM('libre','ocupada') NULL,
    estado_nuevo ENUM('libre','ocupada') NULL,
    id_mozo_anterior INT UNSIGNED NULL,
    id_mozo_nuevo INT UNSIGNED NULL,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_sistema VARCHAR(100) DEFAULT USER()
) ENGINE=InnoDB;

-- Trigger para auditar cambios en mesas
DELIMITER $$
CREATE TRIGGER tr_mesa_audit_update
    AFTER UPDATE ON mesas
    FOR EACH ROW
BEGIN
    -- Solo registrar si hubo cambios significativos
    IF OLD.estado != NEW.estado OR OLD.id_mozo != NEW.id_mozo THEN
        INSERT INTO auditoria_mesas (
            id_mesa, accion, estado_anterior, estado_nuevo,
            id_mozo_anterior, id_mozo_nuevo
        ) VALUES (
            NEW.id_mesa, 'UPDATE', OLD.estado, NEW.estado,
            OLD.id_mozo, NEW.id_mozo
        );
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- VERIFICAR QUE LOS TRIGGERS SE CREARON CORRECTAMENTE
-- =====================================================
-- Uncomment para verificar:
-- SHOW TRIGGERS LIKE '%pedido%';
-- SHOW TRIGGERS LIKE '%mesa%';
-- SHOW TRIGGERS LIKE '%usuario%';

-- =====================================================
-- COMENTARIOS DE USO:
-- =====================================================
/*
FUNCIONALIDADES IMPLEMENTADAS:

1. CONSISTENCIA AUTOMÁTICA:
   - Mesa se marca como 'ocupada' al crear pedido tipo 'stay'
   - Mesa se libera automáticamente cuando no hay pedidos activos
   
2. INTEGRIDAD DE DATOS:
   - No se puede eliminar mesa con pedidos activos
   - No se puede inactivar mozo con mesas ocupadas
   
3. AUDITORÍA:
   - Se registran todos los cambios de estado de mesas
   - Trazabilidad de cambios de asignación de mozos
   
4. PREVENCIÓN DE ERRORES:
   - Bloquea operaciones que dejarían datos inconsistentes
   - Mensajes de error descriptivos para debug

PARA PROBAR:
1. Crear pedido en mesa libre -> Mesa debe pasar a ocupada
2. Cerrar último pedido de mesa -> Mesa debe pasar a libre  
3. Intentar eliminar mesa ocupada -> Debe fallar con error
4. Intentar inactivar mozo con mesas ocupadas -> Debe fallar
5. Revisar tabla auditoria_mesas para ver historial de cambios
*/