-- =====================================================
-- SCRIPT DE MIGRACIÓN PARA ESQUEMA ACTUALIZADO
-- =====================================================
-- 
-- Este script actualiza una base de datos existente al nuevo esquema
-- con los estados de pedidos actualizados.
-- 
-- ⚠️ IMPORTANTE: Hacer backup de la base de datos antes de ejecutar
-- =====================================================

USE comanda;

-- -------------------------------------------------
-- 1. Actualizar estados existentes de pedidos
-- -------------------------------------------------

-- Cambiar 'listo' por 'servido'
UPDATE pedidos SET estado = 'servido' WHERE estado = 'listo';

-- Cambiar 'pagado' por 'cuenta' 
UPDATE pedidos SET estado = 'cuenta' WHERE estado = 'pagado';

-- -------------------------------------------------
-- 2. Modificar la estructura de la tabla pedidos
-- -------------------------------------------------

-- Agregar columna observaciones si no existe
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS observaciones TEXT NULL AFTER id_mozo;

-- Modificar el ENUM de estados
ALTER TABLE pedidos MODIFY COLUMN estado ENUM('pendiente','en_preparacion','servido','cuenta','cerrado') NOT NULL DEFAULT 'pendiente';

-- -------------------------------------------------
-- 3. Verificar y agregar índices faltantes
-- -------------------------------------------------

-- Índices para pedidos
CREATE INDEX IF NOT EXISTS idx_pedidos_fecha ON pedidos(fecha_hora);
CREATE INDEX IF NOT EXISTS idx_pedidos_mesa ON pedidos(id_mesa);
CREATE INDEX IF NOT EXISTS idx_pedidos_mozo ON pedidos(id_mozo);

-- Índices para mesas
CREATE INDEX IF NOT EXISTS idx_mesas_estado ON mesas(estado);
CREATE INDEX IF NOT EXISTS idx_mesas_numero ON mesas(numero);

-- Índices para carta
CREATE INDEX IF NOT EXISTS idx_carta_disponibilidad ON carta(disponibilidad);
CREATE INDEX IF NOT EXISTS idx_carta_categoria ON carta(categoria);

-- Índices para usuarios
CREATE INDEX IF NOT EXISTS idx_usuarios_rol ON usuarios(rol);
CREATE INDEX IF NOT EXISTS idx_usuarios_estado ON usuarios(estado);

-- Índices para propinas
CREATE INDEX IF NOT EXISTS idx_propinas_fecha ON propinas(fecha_hora);

-- Índices para pagos
CREATE INDEX IF NOT EXISTS idx_pagos_estado ON pagos(estado_transaccion);

-- Índices para llamados
CREATE INDEX IF NOT EXISTS idx_llamados_estado ON llamados_mesa(estado);
CREATE INDEX IF NOT EXISTS idx_llamados_fecha ON llamados_mesa(hora_solicitud);

-- -------------------------------------------------
-- 4. Verificar integridad de datos
-- -------------------------------------------------

-- Verificar que no hay pedidos con estados inválidos
SELECT COUNT(*) as pedidos_con_estados_invalidos 
FROM pedidos 
WHERE estado NOT IN ('pendiente','en_preparacion','servido','cuenta','cerrado');

-- Verificar que no hay mesas con estados inválidos
SELECT COUNT(*) as mesas_con_estados_invalidos 
FROM mesas 
WHERE estado NOT IN ('libre','ocupada');

-- -------------------------------------------------
-- 5. Mensaje de confirmación
-- -------------------------------------------------

SELECT 'Migración completada exitosamente' as resultado;

-- -------------------------------------------------
-- 6. Verificar el nuevo esquema
-- -------------------------------------------------

-- Mostrar estructura actualizada de la tabla pedidos
DESCRIBE pedidos;

-- Mostrar estados disponibles
SELECT DISTINCT estado FROM pedidos ORDER BY estado;
