-- Script para limpiar campos no utilizados de la tabla inventario
-- Ejecutar después de hacer backup de la base de datos

-- Paso 1: Eliminar columnas no utilizadas
ALTER TABLE inventario
DROP COLUMN unidad_medida,
DROP COLUMN costo_unitario,
DROP COLUMN notas;

-- Paso 2: Eliminar índices que ya no son necesarios
DROP INDEX idx_inventario_stock_bajo ON inventario;

-- Paso 3: Crear nueva tabla de inventario simplificada
CREATE TABLE inventario_nueva (
    id_inventario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_item INT UNSIGNED NOT NULL,
    cantidad_disponible INT NOT NULL DEFAULT 0,
    cantidad_minima INT NOT NULL DEFAULT 5,
    estado ENUM('disponible','agotado','discontinuado') NOT NULL DEFAULT 'disponible',

    CONSTRAINT fk_inventario_item
        FOREIGN KEY (id_item) REFERENCES carta(id_item)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX idx_inventario_item (id_item),
    INDEX idx_inventario_estado (estado)
) ENGINE=InnoDB;

-- Paso 4: Migrar datos
INSERT INTO inventario_nueva (id_inventario, id_item, cantidad_disponible, cantidad_minima, estado)
SELECT id_inventario, id_item, cantidad_disponible, cantidad_minima, estado
FROM inventario;

-- Paso 5: Reemplazar tabla antigua
DROP TABLE inventario;
RENAME TABLE inventario_nueva TO inventario;

-- Actualizar vistas que usen las columnas eliminadas
DROP VIEW IF EXISTS vista_inventario_categoria;