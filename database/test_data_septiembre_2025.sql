-- =====================================================
-- DATOS DE PRUEBA PARA RENDIMIENTO DE MOZOS - SEPTIEMBRE 2025
-- Fechas actualizadas para el año correcto
-- =====================================================

USE comanda;

-- Limpiar datos de prueba anteriores de septiembre 2025
DELETE FROM propinas WHERE fecha_hora BETWEEN '2025-09-01' AND '2025-09-30';
DELETE FROM detalle_pedido WHERE id_pedido IN (
    SELECT id_pedido FROM pedidos WHERE fecha_hora BETWEEN '2025-09-01' AND '2025-09-30'
);
DELETE FROM pedidos WHERE fecha_hora BETWEEN '2025-09-01' AND '2025-09-30';

-- Insertar items adicionales en la carta (si no existen)
INSERT IGNORE INTO carta (nombre, descripcion, precio, categoria, disponibilidad) VALUES
('Pizza Margherita', 'Pizza clásica con tomate, mozzarella y albahaca', 850.00, 'Platos Principales', 1),
('Hamburguesa Completa', 'Con carne, lechuga, tomate, queso y papas fritas', 720.00, 'Platos Principales', 1),
('Ensalada César', 'Lechuga, pollo, queso parmesano y crutones', 580.00, 'Ensaladas', 1),
('Milanesa Napolitana', 'Con salsa de tomate, jamón y queso', 950.00, 'Platos Principales', 1),
('Café Cortado', 'Café expreso con leche', 180.00, 'Bebidas', 1);

-- =====================================================
-- PEDIDOS SEPTIEMBRE 2025 - JUAN PÉREZ (ID: 2)
-- Propinas variables 10-15%
-- =====================================================

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1250.00, 'cerrado', '2025-09-01 12:30:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 125.00, '2025-09-01 12:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 890.00, 'cerrado', '2025-09-02 14:15:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 133.50, '2025-09-02 14:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1580.00, 'cerrado', '2025-09-03 19:20:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 237.00, '2025-09-03 19:25:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 750.00, 'cerrado', '2025-09-04 13:10:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 112.50, '2025-09-04 13:15:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1120.00, 'cerrado', '2025-09-05 20:30:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 168.00, '2025-09-05 20:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(3, 'stay', 920.00, 'cerrado', '2025-09-06 13:30:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 138.00, '2025-09-06 13:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1340.00, 'cerrado', '2025-09-07 19:45:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 134.00, '2025-09-07 19:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 680.00, 'cerrado', '2025-09-08 12:45:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 102.00, '2025-09-08 12:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(3, 'stay', 1890.00, 'cerrado', '2025-09-09 21:00:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 283.50, '2025-09-09 21:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 780.00, 'cerrado', '2025-09-10 14:20:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 117.00, '2025-09-10 14:25:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1200.00, 'cerrado', '2025-09-11 13:15:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 180.00, '2025-09-11 13:20:00');

-- =====================================================
-- PEDIDOS SEPTIEMBRE 2025 - MARÍA GARCÍA (ID: 3)
-- LA MEJOR - Propinas consistentes del 20%
-- =====================================================

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 950.00, 'cerrado', '2025-09-01 11:30:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 190.00, '2025-09-01 11:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 1200.00, 'cerrado', '2025-09-01 18:45:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 240.00, '2025-09-01 18:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 780.00, 'cerrado', '2025-09-02 13:30:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 156.00, '2025-09-02 13:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1450.00, 'cerrado', '2025-09-03 12:20:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 290.00, '2025-09-03 12:25:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 890.00, 'cerrado', '2025-09-04 19:15:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 178.00, '2025-09-04 19:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 1100.00, 'cerrado', '2025-09-05 14:00:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 220.00, '2025-09-05 14:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 720.00, 'cerrado', '2025-09-06 20:45:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 144.00, '2025-09-06 20:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 1380.00, 'cerrado', '2025-09-07 12:30:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 276.00, '2025-09-07 12:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 950.00, 'cerrado', '2025-09-08 21:00:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 190.00, '2025-09-08 21:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1250.00, 'cerrado', '2025-09-09 13:15:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 250.00, '2025-09-09 13:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 1580.00, 'cerrado', '2025-09-10 19:30:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 316.00, '2025-09-10 19:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 1150.00, 'cerrado', '2025-09-11 15:00:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 230.00, '2025-09-11 15:05:00');

-- =====================================================
-- PEDIDOS SEPTIEMBRE 2025 - CARLOS LÓPEZ (ID: 4)
-- Propinas bajas del 5%
-- =====================================================

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 580.00, 'cerrado', '2025-09-01 12:00:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 29.00, '2025-09-01 12:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 720.00, 'cerrado', '2025-09-02 15:30:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 36.00, '2025-09-02 15:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 950.00, 'cerrado', '2025-09-03 11:45:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 47.50, '2025-09-03 11:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 680.00, 'cerrado', '2025-09-04 16:20:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 34.00, '2025-09-04 16:25:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 1200.00, 'cerrado', '2025-09-05 12:15:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 60.00, '2025-09-05 12:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 850.00, 'cerrado', '2025-09-06 13:45:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 42.50, '2025-09-06 13:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 920.00, 'cerrado', '2025-09-07 11:30:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 46.00, '2025-09-07 11:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 750.00, 'cerrado', '2025-09-08 14:00:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 37.50, '2025-09-08 14:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 1100.00, 'cerrado', '2025-09-09 16:30:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 55.00, '2025-09-09 16:35:00');

-- =====================================================
-- PEDIDOS SEPTIEMBRE 2025 - ANA MARTÍNEZ (ID: 5)
-- Propinas medianas 10-15%
-- =====================================================

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1150.00, 'cerrado', '2025-09-01 12:30:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 115.00, '2025-09-01 12:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 890.00, 'cerrado', '2025-09-02 19:15:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 133.50, '2025-09-02 19:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(3, 'stay', 1320.00, 'cerrado', '2025-09-03 13:45:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 132.00, '2025-09-03 13:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 750.00, 'cerrado', '2025-09-04 20:00:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 112.50, '2025-09-04 20:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1580.00, 'cerrado', '2025-09-05 14:20:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 158.00, '2025-09-05 14:25:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(3, 'stay', 920.00, 'cerrado', '2025-09-06 12:00:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 138.00, '2025-09-06 12:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1240.00, 'cerrado', '2025-09-07 21:30:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 186.00, '2025-09-07 21:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 680.00, 'cerrado', '2025-09-08 13:15:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 68.00, '2025-09-08 13:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(3, 'stay', 1450.00, 'cerrado', '2025-09-09 19:45:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 217.50, '2025-09-09 19:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 850.00, 'cerrado', '2025-09-10 14:30:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 85.00, '2025-09-10 14:35:00');

-- =====================================================
-- PEDIDOS SEPTIEMBRE 2025 - DIEGO RODRÍGUEZ (ID: 6)
-- PREMIUM - Pocos pedidos pero propinas del 25%
-- =====================================================

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1800.00, 'cerrado', '2025-09-01 20:00:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 450.00, '2025-09-01 20:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 2200.00, 'cerrado', '2025-09-03 21:15:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 550.00, '2025-09-03 21:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 1650.00, 'cerrado', '2025-09-05 19:30:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 412.50, '2025-09-05 19:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1950.00, 'cerrado', '2025-09-07 20:45:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 487.50, '2025-09-07 20:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 2100.00, 'cerrado', '2025-09-09 20:30:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 525.00, '2025-09-09 20:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 2350.00, 'cerrado', '2025-09-11 21:15:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 587.50, '2025-09-11 21:20:00');

-- =====================================================
-- VERIFICAR DATOS INSERTADOS
-- =====================================================

SELECT 'DATOS DE PRUEBA INSERTADOS CORRECTAMENTE - SEPTIEMBRE 2025' as mensaje;

SELECT 
    CONCAT(u.nombre, ' ', u.apellido) as mozo,
    COUNT(DISTINCT p.id_pedido) as pedidos,
    ROUND(SUM(p.total), 2) as total_vendido,
    ROUND(SUM(pr.monto), 2) as total_propinas,
    ROUND(SUM(pr.monto) / SUM(p.total) * 100, 2) as tasa_propina_porcentaje
FROM usuarios u
LEFT JOIN pedidos p ON u.id_usuario = p.id_mozo 
    AND p.fecha_hora BETWEEN '2025-09-01' AND '2025-09-30'
    AND p.estado = 'cerrado'
LEFT JOIN propinas pr ON p.id_pedido = pr.id_pedido
WHERE u.rol = 'mozo' AND u.estado = 'activo'
GROUP BY u.id_usuario, mozo
HAVING pedidos > 0
ORDER BY tasa_propina_porcentaje DESC;

-- =====================================================
-- RESUMEN DE DATOS CREADOS
-- =====================================================
/*
ESTADÍSTICAS ESPERADAS (septiembre 2025):

1. Diego Rodríguez - PREMIUM (6 pedidos)
   - Total vendido: ~$12,050
   - Total propinas: ~$3,012
   - Tasa de propina: 25%

2. María García - LA MEJOR (12 pedidos)
   - Total vendido: ~$13,450  
   - Total propinas: ~$2,690
   - Tasa de propina: 20%

3. Juan Pérez - MEDIO (11 pedidos)
   - Total vendido: ~$12,410
   - Total propinas: ~$1,650
   - Tasa de propina: ~13%

4. Ana Martínez - MEDIO-BAJO (10 pedidos)
   - Total vendido: ~$10,835
   - Total propinas: ~$1,345
   - Tasa de propina: ~12%

5. Carlos López - NECESITA MEJORAR (9 pedidos)
   - Total vendido: ~$7,750
   - Total propinas: ~$387
   - Tasa de propina: 5%

Total: 48 pedidos con propinas balanceadas para septiembre 2025
*/