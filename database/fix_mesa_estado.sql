-- Script para agregar el estado 'reservada' a la tabla mesas
USE comanda;

-- Modificar la columna estado para incluir 'reservada'
ALTER TABLE mesas 
MODIFY COLUMN estado ENUM('libre','ocupada','reservada') NOT NULL DEFAULT 'libre';

-- Verificar que el cambio se aplicó correctamente
DESCRIBE mesas;
