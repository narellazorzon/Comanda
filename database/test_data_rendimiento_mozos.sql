-- =====================================================
-- DATOS DE PRUEBA PARA RENDIMIENTO DE MOZOS
-- Incluye múltiples pedidos con diferentes propinas
-- Para testing del módulo de rendimiento
-- =====================================================

USE comanda;

-- Insertar más items en la carta para tener variedad (usando INSERT IGNORE para evitar duplicados)
INSERT IGNORE INTO carta (nombre, descripcion, precio, categoria, disponibilidad, imagen_url) VALUES
('Pizza Margherita', 'Pizza clásica con tomate, mozzarella y albahaca', 850.00, 'Platos Principales', 1, 'https://example.com/pizza.jpg'),
('Hamburguesa Completa', 'Con carne, lechuga, tomate, queso y papas fritas', 720.00, 'Platos Principales', 1, 'https://example.com/burger.jpg'),
('Ensalada César', 'Lechuga, pollo, queso parmesano y crutones', 580.00, 'Ensaladas', 1, 'https://example.com/caesar.jpg'),
('Milanesa Napolitana', 'Con salsa de tomate, jamón y queso', 950.00, 'Platos Principales', 1, 'https://example.com/milanesa.jpg'),
('Pasta Carbonara', 'Con panceta, huevo y queso parmesano', 780.00, 'Pastas', 1, 'https://example.com/carbonara.jpg'),
('Tiramisu', 'Postre italiano con café y mascarpone', 420.00, 'Postres', 1, 'https://example.com/tiramisu.jpg'),
('Café Cortado', 'Café expreso con leche', 180.00, 'Bebidas', 1, 'https://example.com/cortado.jpg'),
('Coca Cola', 'Bebida gaseosa 500ml', 220.00, 'Bebidas', 1, 'https://example.com/coca.jpg'),
('Agua Mineral', 'Agua sin gas 500ml', 150.00, 'Bebidas', 1, 'https://example.com/agua.jpg'),
('Flan Casero', 'Con dulce de leche', 320.00, 'Postres', 1, 'https://example.com/flan.jpg');

-- =====================================================
-- PEDIDOS Y PROPINAS DE PRUEBA - SEPTIEMBRE 2024
-- =====================================================

-- SEMANA 1 (1-7 septiembre)
-- Pedidos para Juan Pérez (ID: 2) - Mesa 1
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1250.00, 'cerrado', '2024-09-01 12:30:00', 2),
(1, 'stay', 890.00, 'cerrado', '2024-09-01 14:15:00', 2),
(1, 'stay', 1580.00, 'cerrado', '2024-09-01 19:20:00', 2),
(2, 'stay', 750.00, 'cerrado', '2024-09-02 13:10:00', 2),
(2, 'stay', 1120.00, 'cerrado', '2024-09-02 20:30:00', 2),
(1, 'stay', 680.00, 'cerrado', '2024-09-03 12:45:00', 2),
(3, 'stay', 1890.00, 'cerrado', '2024-09-03 21:00:00', 2),
(1, 'stay', 920.00, 'cerrado', '2024-09-04 13:30:00', 2),
(2, 'stay', 1340.00, 'cerrado', '2024-09-04 19:45:00', 2),
(1, 'stay', 780.00, 'cerrado', '2024-09-05 14:20:00', 2);

-- Propinas para Juan Pérez
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES
(1, 2, 125.00, '2024-09-01 12:35:00'),  -- 10%
(2, 2, 89.00, '2024-09-01 14:20:00'),   -- 10%
(3, 2, 237.00, '2024-09-01 19:25:00'),  -- 15%
(4, 2, 75.00, '2024-09-02 13:15:00'),   -- 10%
(5, 2, 168.00, '2024-09-02 20:35:00'),  -- 15%
(6, 2, 102.00, '2024-09-03 12:50:00'),  -- 15%
(7, 2, 378.00, '2024-09-03 21:05:00'),  -- 20%
(8, 2, 92.00, '2024-09-04 13:35:00'),   -- 10%
(9, 2, 201.00, '2024-09-04 19:50:00'),  -- 15%
(10, 2, 156.00, '2024-09-05 14:25:00'); -- 20%

-- Pedidos para María García (ID: 3) - Mesas 4, 5, 6
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 950.00, 'cerrado', '2024-09-01 11:30:00', 3),
(5, 'stay', 1200.00, 'cerrado', '2024-09-01 13:45:00', 3),
(6, 'stay', 780.00, 'cerrado', '2024-09-01 18:30:00', 3),
(4, 'stay', 1450.00, 'cerrado', '2024-09-02 12:20:00', 3),
(5, 'stay', 890.00, 'cerrado', '2024-09-02 19:15:00', 3),
(6, 'stay', 1100.00, 'cerrado', '2024-09-03 13:00:00', 3),
(4, 'stay', 720.00, 'cerrado', '2024-09-03 20:45:00', 3),
(5, 'stay', 1380.00, 'cerrado', '2024-09-04 12:30:00', 3),
(6, 'stay', 950.00, 'cerrado', '2024-09-04 21:00:00', 3),
(4, 'stay', 1250.00, 'cerrado', '2024-09-05 13:15:00', 3),
(5, 'stay', 680.00, 'cerrado', '2024-09-05 19:30:00', 3),
(6, 'stay', 1580.00, 'cerrado', '2024-09-06 14:00:00', 3);

-- Propinas para María García (muy buenas propinas)
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES
(11, 3, 190.00, '2024-09-01 11:35:00'), -- 20%
(12, 3, 240.00, '2024-09-01 13:50:00'), -- 20%
(13, 3, 156.00, '2024-09-01 18:35:00'), -- 20%
(14, 3, 290.00, '2024-09-02 12:25:00'), -- 20%
(15, 3, 178.00, '2024-09-02 19:20:00'), -- 20%
(16, 3, 220.00, '2024-09-03 13:05:00'), -- 20%
(17, 3, 144.00, '2024-09-03 20:50:00'), -- 20%
(18, 3, 276.00, '2024-09-04 12:35:00'), -- 20%
(19, 3, 190.00, '2024-09-04 21:05:00'), -- 20%
(20, 3, 250.00, '2024-09-05 13:20:00'), -- 20%
(21, 3, 136.00, '2024-09-05 19:35:00'), -- 20%
(22, 3, 316.00, '2024-09-06 14:05:00'); -- 20%

-- Pedidos para Carlos López (ID: 4) - Mesas 7, 8
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 580.00, 'cerrado', '2024-09-01 12:00:00', 4),
(8, 'stay', 720.00, 'cerrado', '2024-09-01 15:30:00', 4),
(7, 'stay', 950.00, 'cerrado', '2024-09-02 11:45:00', 4),
(8, 'stay', 680.00, 'cerrado', '2024-09-02 16:20:00', 4),
(7, 'stay', 1200.00, 'cerrado', '2024-09-03 12:15:00', 4),
(8, 'stay', 850.00, 'cerrado', '2024-09-04 13:45:00', 4),
(7, 'stay', 920.00, 'cerrado', '2024-09-05 11:30:00', 4);

-- Propinas para Carlos López (propinas bajas)
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES
(23, 4, 29.00, '2024-09-01 12:05:00'),  -- 5%
(24, 4, 36.00, '2024-09-01 15:35:00'),  -- 5%
(25, 4, 47.50, '2024-09-02 11:50:00'),  -- 5%
(26, 4, 34.00, '2024-09-02 16:25:00'),  -- 5%
(27, 4, 60.00, '2024-09-03 12:20:00'),  -- 5%
(28, 4, 42.50, '2024-09-04 13:50:00'),  -- 5%
(29, 4, 46.00, '2024-09-05 11:35:00');  -- 5%

-- =====================================================
-- MÁS PEDIDOS PARA SEPTIEMBRE (SEMANAS 2-4)
-- =====================================================

-- SEMANA 2 (8-14 septiembre)
-- Ana Martínez (ID: 5) - Con propinas variables
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1150.00, 'cerrado', '2024-09-08 12:30:00', 5),
(2, 'stay', 890.00, 'cerrado', '2024-09-08 19:15:00', 5),
(3, 'stay', 1320.00, 'cerrado', '2024-09-09 13:45:00', 5),
(1, 'stay', 750.00, 'cerrado', '2024-09-09 20:00:00', 5),
(2, 'stay', 1580.00, 'cerrado', '2024-09-10 14:20:00', 5),
(3, 'stay', 920.00, 'cerrado', '2024-09-11 12:00:00', 5),
(1, 'stay', 1240.00, 'cerrado', '2024-09-11 21:30:00', 5),
(2, 'stay', 680.00, 'cerrado', '2024-09-12 13:15:00', 5),
(3, 'stay', 1450.00, 'cerrado', '2024-09-13 19:45:00', 5),
(1, 'stay', 850.00, 'cerrado', '2024-09-14 14:30:00', 5);

-- Propinas para Ana Martínez (propinas medianas)
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES
(30, 5, 115.00, '2024-09-08 12:35:00'), -- 10%
(31, 5, 133.50, '2024-09-08 19:20:00'), -- 15%
(32, 5, 132.00, '2024-09-09 13:50:00'), -- 10%
(33, 5, 112.50, '2024-09-09 20:05:00'), -- 15%
(34, 5, 158.00, '2024-09-10 14:25:00'), -- 10%
(35, 5, 138.00, '2024-09-11 12:05:00'), -- 15%
(36, 5, 186.00, '2024-09-11 21:35:00'), -- 15%
(37, 5, 68.00, '2024-09-12 13:20:00'),  -- 10%
(38, 5, 217.50, '2024-09-13 19:50:00'), -- 15%
(39, 5, 85.00, '2024-09-14 14:35:00');  -- 10%

-- Diego Rodríguez (ID: 6) - Pocos pedidos pero buenas propinas
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1800.00, 'cerrado', '2024-09-08 20:00:00', 6),
(5, 'stay', 2200.00, 'cerrado', '2024-09-10 21:15:00', 6),
(6, 'stay', 1650.00, 'cerrado', '2024-09-12 19:30:00', 6),
(4, 'stay', 1950.00, 'cerrado', '2024-09-14 20:45:00', 6);

-- Propinas excelentes para Diego
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES
(40, 6, 450.00, '2024-09-08 20:05:00'), -- 25%
(41, 6, 550.00, '2024-09-10 21:20:00'), -- 25%
(42, 6, 412.50, '2024-09-12 19:35:00'), -- 25%
(43, 6, 487.50, '2024-09-14 20:50:00'); -- 25%

-- =====================================================
-- SEMANA 3 (15-21 septiembre)
-- =====================================================

-- Más pedidos para Juan Pérez
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 980.00, 'cerrado', '2024-09-15 12:45:00', 2),
(2, 'stay', 1150.00, 'cerrado', '2024-09-15 19:30:00', 2),
(3, 'stay', 750.00, 'cerrado', '2024-09-16 13:20:00', 2),
(1, 'stay', 1340.00, 'cerrado', '2024-09-16 20:15:00', 2),
(2, 'stay', 890.00, 'cerrado', '2024-09-17 14:00:00', 2),
(3, 'stay', 1580.00, 'cerrado', '2024-09-18 21:30:00', 2),
(1, 'stay', 720.00, 'cerrado', '2024-09-19 12:15:00', 2),
(2, 'stay', 1250.00, 'cerrado', '2024-09-20 19:45:00', 2);

INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES
(44, 2, 98.00, '2024-09-15 12:50:00'),   -- 10%
(45, 2, 172.50, '2024-09-15 19:35:00'),  -- 15%
(46, 2, 112.50, '2024-09-16 13:25:00'),  -- 15%
(47, 2, 134.00, '2024-09-16 20:20:00'),  -- 10%
(48, 2, 133.50, '2024-09-17 14:05:00'),  -- 15%
(49, 2, 237.00, '2024-09-18 21:35:00'),  -- 15%
(50, 2, 108.00, '2024-09-19 12:20:00'),  -- 15%
(51, 2, 187.50, '2024-09-20 19:50:00');  -- 15%

-- Más pedidos para María García
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1100.00, 'cerrado', '2024-09-15 11:30:00', 3),
(5, 'stay', 950.00, 'cerrado', '2024-09-15 18:45:00', 3),
(6, 'stay', 1380.00, 'cerrado', '2024-09-16 12:15:00', 3),
(4, 'stay', 780.00, 'cerrado', '2024-09-16 20:30:00', 3),
(5, 'stay', 1250.00, 'cerrado', '2024-09-17 13:45:00', 3),
(6, 'stay', 890.00, 'cerrado', '2024-09-18 19:15:00', 3),
(4, 'stay', 1450.00, 'cerrado', '2024-09-19 14:20:00', 3),
(5, 'stay', 1080.00, 'cerrado', '2024-09-20 20:00:00', 3);

INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES
(52, 3, 220.00, '2024-09-15 11:35:00'), -- 20%
(53, 3, 190.00, '2024-09-15 18:50:00'), -- 20%
(54, 3, 276.00, '2024-09-16 12:20:00'), -- 20%
(55, 3, 156.00, '2024-09-16 20:35:00'), -- 20%
(56, 3, 250.00, '2024-09-17 13:50:00'), -- 20%
(57, 3, 178.00, '2024-09-18 19:20:00'), -- 20%
(58, 3, 290.00, '2024-09-19 14:25:00'), -- 20%
(59, 3, 216.00, '2024-09-20 20:05:00'); -- 20%

-- =====================================================
-- SEMANA 4 (22-28 septiembre) - Últimos datos
-- =====================================================

-- Pedidos finales para todos los mozos
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
-- Juan Pérez
(1, 'stay', 1200.00, 'cerrado', '2024-09-22 12:30:00', 2),
(2, 'stay', 950.00, 'cerrado', '2024-09-24 19:15:00', 2),
(3, 'stay', 1450.00, 'cerrado', '2024-09-26 20:45:00', 2),

-- María García  
(4, 'stay', 1350.00, 'cerrado', '2024-09-22 13:15:00', 3),
(5, 'stay', 890.00, 'cerrado', '2024-09-24 18:30:00', 3),
(6, 'stay', 1580.00, 'cerrado', '2024-09-26 21:00:00', 3),

-- Carlos López
(7, 'stay', 750.00, 'cerrado', '2024-09-23 12:00:00', 4),
(8, 'stay', 1100.00, 'cerrado', '2024-09-25 14:30:00', 4),

-- Ana Martínez
(1, 'stay', 1250.00, 'cerrado', '2024-09-23 13:45:00', 5),
(2, 'stay', 980.00, 'cerrado', '2024-09-25 19:20:00', 5),

-- Diego Rodríguez
(4, 'stay', 2100.00, 'cerrado', '2024-09-22 20:30:00', 6),
(5, 'stay', 1950.00, 'cerrado', '2024-09-27 21:15:00', 6);

-- Propinas finales
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES
-- Juan Pérez (propinas normales)
(60, 2, 180.00, '2024-09-22 12:35:00'), -- 15%
(61, 2, 142.50, '2024-09-24 19:20:00'), -- 15%
(62, 2, 217.50, '2024-09-26 20:50:00'), -- 15%

-- María García (propinas excelentes)
(63, 3, 270.00, '2024-09-22 13:20:00'), -- 20%
(64, 3, 178.00, '2024-09-24 18:35:00'), -- 20%
(65, 3, 316.00, '2024-09-26 21:05:00'), -- 20%

-- Carlos López (propinas bajas)
(66, 4, 37.50, '2024-09-23 12:05:00'),  -- 5%
(67, 4, 55.00, '2024-09-25 14:35:00'),  -- 5%

-- Ana Martínez (propinas medianas)
(68, 5, 187.50, '2024-09-23 13:50:00'), -- 15%
(69, 5, 147.00, '2024-09-25 19:25:00'), -- 15%

-- Diego Rodríguez (propinas premium)
(70, 6, 525.00, '2024-09-22 20:35:00'), -- 25%
(71, 6, 487.50, '2024-09-27 21:20:00'); -- 25%

-- =====================================================
-- DETALLES DE PEDIDOS (algunos ejemplos)
-- =====================================================

-- Detalles para algunos pedidos representativos (usando IDs existentes)
INSERT INTO detalle_pedido (id_pedido, id_item, cantidad, precio_unitario) VALUES
-- Pedido 1 (Juan Pérez) - usando items existentes
(1, 2, 2, 11.00),      -- Empanadas (ID conocido)
(1, 3, 1, 11000.00),   -- Brownie con helado (ID conocido)

-- Pedido 11 (María García) - usando items disponibles
(11, 2, 3, 11.00),     -- Empanadas
(11, 3, 1, 11000.00),  -- Brownie

-- Pedido 23 (Carlos López) - items básicos
(23, 2, 4, 11.00),     -- Empanadas

-- Pedido 40 (Diego Rodríguez) - pedido grande
(40, 2, 5, 11.00),     -- Empanadas
(40, 3, 2, 11000.00);  -- Brownies

-- =====================================================
-- RESUMEN DE DATOS INSERTADOS
-- =====================================================
/*
MOZOS Y SUS ESTADÍSTICAS ESPERADAS (septiembre 2024):

1. María García (ID: 3)
   - Mayor cantidad de pedidos
   - Propinas consistentes del 20%
   - Mejor tasa de propina

2. Diego Rodríguez (ID: 6)  
   - Menos pedidos pero de mayor valor
   - Propinas del 25%
   - Segundo en tasa de propina

3. Juan Pérez (ID: 2)
   - Cantidad media de pedidos
   - Propinas variables 10-20%
   - Tasa de propina media

4. Ana Martínez (ID: 5)
   - Cantidad media de pedidos  
   - Propinas del 10-15%
   - Tasa de propina media-baja

5. Carlos López (ID: 4)
   - Menos pedidos
   - Propinas consistentemente bajas (5%)
   - Peor tasa de propina

Total aproximado:
- 71 pedidos
- 67 propinas registradas
- Datos distribuidos en septiembre 2024
*/