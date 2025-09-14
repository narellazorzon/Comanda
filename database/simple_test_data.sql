-- =====================================================
-- DATOS DE PRUEBA SIMPLES CON FECHAS ACTUALES
-- Para testing inmediato del módulo
-- =====================================================

USE comanda;

-- Limpiar datos de prueba anteriores
DELETE FROM propinas WHERE fecha_hora >= CURDATE() - INTERVAL 7 DAY;
DELETE FROM detalle_pedido WHERE id_pedido IN (
    SELECT id_pedido FROM pedidos WHERE fecha_hora >= CURDATE() - INTERVAL 7 DAY
);
DELETE FROM pedidos WHERE fecha_hora >= CURDATE() - INTERVAL 7 DAY;

-- =====================================================
-- PEDIDOS DE LOS ÚLTIMOS 7 DÍAS (FECHAS ACTUALES)
-- =====================================================

-- Juan Pérez (ID: 2) - HOY
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1500.00, 'cerrado', NOW() - INTERVAL 2 HOUR, 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 150.00, NOW() - INTERVAL 2 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 800.00, 'cerrado', NOW() - INTERVAL 5 HOUR, 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 80.00, NOW() - INTERVAL 5 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1200.00, 'cerrado', NOW() - INTERVAL 1 DAY, 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 180.00, NOW() - INTERVAL 1 DAY + INTERVAL 5 MINUTE);

-- María García (ID: 3) - AYER Y HOY  
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 2000.00, 'cerrado', NOW() - INTERVAL 1 HOUR, 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 400.00, NOW() - INTERVAL 1 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 1800.00, 'cerrado', NOW() - INTERVAL 3 HOUR, 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 360.00, NOW() - INTERVAL 3 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 1500.00, 'cerrado', NOW() - INTERVAL 1 DAY - INTERVAL 2 HOUR, 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 300.00, NOW() - INTERVAL 1 DAY - INTERVAL 2 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 900.00, 'cerrado', NOW() - INTERVAL 1 DAY - INTERVAL 5 HOUR, 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 180.00, NOW() - INTERVAL 1 DAY - INTERVAL 5 HOUR + INTERVAL 5 MINUTE);

-- Carlos López (ID: 4) - PROPINAS BAJAS
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 600.00, 'cerrado', NOW() - INTERVAL 4 HOUR, 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 30.00, NOW() - INTERVAL 4 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 800.00, 'cerrado', NOW() - INTERVAL 1 DAY - INTERVAL 3 HOUR, 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 40.00, NOW() - INTERVAL 1 DAY - INTERVAL 3 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 1000.00, 'cerrado', NOW() - INTERVAL 2 DAY, 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 50.00, NOW() - INTERVAL 2 DAY + INTERVAL 5 MINUTE);

-- Ana Martínez (ID: 5) - PROPINAS MEDIAS
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1000.00, 'cerrado', NOW() - INTERVAL 6 HOUR, 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 150.00, NOW() - INTERVAL 6 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1200.00, 'cerrado', NOW() - INTERVAL 1 DAY - INTERVAL 1 HOUR, 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 180.00, NOW() - INTERVAL 1 DAY - INTERVAL 1 HOUR + INTERVAL 5 MINUTE);

-- Diego Rodríguez (ID: 6) - PROPINAS PREMIUM
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 2500.00, 'cerrado', NOW() - INTERVAL 7 HOUR, 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 625.00, NOW() - INTERVAL 7 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 3000.00, 'cerrado', NOW() - INTERVAL 2 DAY - INTERVAL 2 HOUR, 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 750.00, NOW() - INTERVAL 2 DAY - INTERVAL 2 HOUR + INTERVAL 5 MINUTE);

-- =====================================================
-- CONSULTA PARA VERIFICAR LOS DATOS INSERTADOS
-- =====================================================

SELECT 'DATOS INSERTADOS CORRECTAMENTE - ÚLTIMOS 7 DÍAS' as mensaje;

SELECT 
    u.nombre,
    u.apellido,
    COUNT(DISTINCT p.id_pedido) as pedidos,
    SUM(p.total) as total_vendido,
    SUM(pr.monto) as total_propinas,
    ROUND(SUM(pr.monto) / SUM(p.total) * 100, 2) as tasa_propina_porcentaje
FROM usuarios u
LEFT JOIN pedidos p ON u.id_usuario = p.id_mozo AND p.fecha_hora >= CURDATE() - INTERVAL 7 DAY
LEFT JOIN propinas pr ON p.id_pedido = pr.id_pedido
WHERE u.rol = 'mozo' AND u.estado = 'activo'
GROUP BY u.id_usuario, u.nombre, u.apellido
ORDER BY total_propinas DESC;