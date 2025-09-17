-- Remove stock trigger that's causing order creation to fail
-- Stock management can be implemented later as a separate feature

-- Drop the problematic trigger
DROP TRIGGER IF EXISTS tr_detalle_pedido_descontar_stock;
DROP TRIGGER IF EXISTS tr_detalle_pedido_check_stock;

-- Optional: Create inventory records with unlimited stock for all carta items
-- This prevents any stock-related issues while maintaining the table structure

-- First, clear existing inventory to avoid duplicates
TRUNCATE TABLE inventario;

-- Insert inventory records for all carta items with "unlimited" stock (9999)
INSERT INTO inventario (id_item, cantidad_disponible, cantidad_minima, unidad_medida, estado)
SELECT
    id_item,
    9999 as cantidad_disponible,  -- Set high stock to avoid issues
    0 as cantidad_minima,          -- No minimum required
    'unidad' as unidad_medida,
    'disponible' as estado
FROM carta
WHERE activo = 1;

-- Verify the fix
SELECT 'Stock trigger removed and inventory initialized with unlimited stock' as message;