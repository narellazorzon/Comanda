-- Script para agregar columna status a la tabla mesas
ALTER TABLE mesas ADD COLUMN status TINYINT(1) DEFAULT 1 AFTER fecha_creacion;

-- Actualizar todas las mesas existentes como activas
UPDATE mesas SET status = 1 WHERE status IS NULL;

-- Comentario: status = 1 (activa), status = 0 (inactiva/eliminada)