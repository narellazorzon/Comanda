-- Script para agregar el campo status a la tabla mesas
-- Este script implementa soft delete para las mesas

USE comanda;

-- Agregar el campo status a la tabla mesas
ALTER TABLE mesas 
ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 
COMMENT '1 = activa, 0 = inactiva (soft delete)';

-- Actualizar todas las mesas existentes para que estén activas
UPDATE mesas SET status = 1 WHERE status IS NULL;

-- Crear índice para mejorar el rendimiento de consultas por status
CREATE INDEX idx_mesas_status ON mesas(status);

-- Verificar que el campo se agregó correctamente
DESCRIBE mesas;
