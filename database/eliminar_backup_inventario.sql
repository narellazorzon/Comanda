-- Script para eliminar la tabla de backup de inventario
-- Ejecutar si ya se creó la tabla inventario_backup

-- Eliminar tabla de backup
DROP TABLE IF EXISTS inventario_backup;

-- Verificar que se eliminó
SHOW TABLES LIKE 'inventario%';
