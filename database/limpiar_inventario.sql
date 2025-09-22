-- Script para limpiar campos no utilizados de la tabla inventario
-- Ejecutar después de hacer backup de la base de datos

-- PASO 1: Eliminar tabla de backup si existe
DROP TABLE IF EXISTS inventario_backup;

-- PASO 2: Eliminar columnas no utilizadas
ALTER TABLE inventario
DROP COLUMN unidad_medida,
DROP COLUMN costo_unitario,
DROP COLUMN notas,
DROP COLUMN fecha_ultima_actualizacion;

-- PASO 3: Eliminar índices que ya no son necesarios
DROP INDEX IF EXISTS idx_inventario_stock_bajo ON inventario;

-- PASO 4: Actualizar el modelo PHP para que coincida con la nueva estructura
-- (Esto se hará manualmente en el archivo Inventario.php)

-- PASO 5: Eliminar vistas que usen las columnas eliminadas
DROP VIEW IF EXISTS vista_inventario_categoria;
DROP VIEW IF EXISTS vista_stock_bajo;

-- PASO 6: Verificar que la tabla quede con solo los campos esenciales:
-- - id_inventario (PK)
-- - id_item (FK)
-- - cantidad_disponible
-- - cantidad_minima  
-- - estado

-- PASO 7: Mostrar estructura final
DESCRIBE inventario;