-- =====================================================
-- DATOS DE PRUEBA PARA RENDIMIENTO DE MOZOS - VERSIÓN CORREGIDA
-- Incluye múltiples pedidos con diferentes propinas
-- Para testing del módulo de rendimiento
-- =====================================================

USE comanda;

-- Primero, limpiar datos de prueba anteriores si existen
DELETE FROM propinas WHERE fecha_hora BETWEEN '2024-09-01' AND '2024-09-30';
DELETE FROM detalle_pedido WHERE id_pedido IN (
    SELECT id_pedido FROM pedidos WHERE fecha_hora BETWEEN '2024-09-01' AND '2024-09-30'
);
DELETE FROM pedidos WHERE fecha_hora BETWEEN '2024-09-01' AND '2024-09-30';

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
-- PEDIDOS SEPTIEMBRE 2024 - JUAN PÉREZ (ID: 2)
-- =====================================================

-- Semana 1
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1250.00, 'cerrado', '2024-09-01 12:30:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 125.00, '2024-09-01 12:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 890.00, 'cerrado', '2024-09-01 14:15:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 89.00, '2024-09-01 14:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1580.00, 'cerrado', '2024-09-01 19:20:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 237.00, '2024-09-01 19:25:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 750.00, 'cerrado', '2024-09-02 13:10:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 75.00, '2024-09-02 13:15:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1120.00, 'cerrado', '2024-09-02 20:30:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 168.00, '2024-09-02 20:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 680.00, 'cerrado', '2024-09-03 12:45:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 102.00, '2024-09-03 12:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(3, 'stay', 1890.00, 'cerrado', '2024-09-03 21:00:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 378.00, '2024-09-03 21:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 920.00, 'cerrado', '2024-09-04 13:30:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 92.00, '2024-09-04 13:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1340.00, 'cerrado', '2024-09-04 19:45:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 201.00, '2024-09-04 19:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 780.00, 'cerrado', '2024-09-05 14:20:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 156.00, '2024-09-05 14:25:00');

-- =====================================================
-- PEDIDOS SEPTIEMBRE 2024 - MARÍA GARCÍA (ID: 3)
-- =====================================================

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 950.00, 'cerrado', '2024-09-01 11:30:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 190.00, '2024-09-01 11:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 1200.00, 'cerrado', '2024-09-01 13:45:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 240.00, '2024-09-01 13:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 780.00, 'cerrado', '2024-09-01 18:30:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 156.00, '2024-09-01 18:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1450.00, 'cerrado', '2024-09-02 12:20:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 290.00, '2024-09-02 12:25:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 890.00, 'cerrado', '2024-09-02 19:15:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 178.00, '2024-09-02 19:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 1100.00, 'cerrado', '2024-09-03 13:00:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 220.00, '2024-09-03 13:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 720.00, 'cerrado', '2024-09-03 20:45:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 144.00, '2024-09-03 20:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 1380.00, 'cerrado', '2024-09-04 12:30:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 276.00, '2024-09-04 12:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 950.00, 'cerrado', '2024-09-04 21:00:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 190.00, '2024-09-04 21:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1250.00, 'cerrado', '2024-09-05 13:15:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 250.00, '2024-09-05 13:20:00');

-- =====================================================
-- PEDIDOS SEPTIEMBRE 2024 - CARLOS LÓPEZ (ID: 4)
-- =====================================================

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 580.00, 'cerrado', '2024-09-01 12:00:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 29.00, '2024-09-01 12:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 720.00, 'cerrado', '2024-09-01 15:30:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 36.00, '2024-09-01 15:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 950.00, 'cerrado', '2024-09-02 11:45:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 47.50, '2024-09-02 11:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 680.00, 'cerrado', '2024-09-02 16:20:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 34.00, '2024-09-02 16:25:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 1200.00, 'cerrado', '2024-09-03 12:15:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 60.00, '2024-09-03 12:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 850.00, 'cerrado', '2024-09-04 13:45:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 42.50, '2024-09-04 13:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 920.00, 'cerrado', '2024-09-05 11:30:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 46.00, '2024-09-05 11:35:00');

-- =====================================================
-- PEDIDOS SEPTIEMBRE 2024 - ANA MARTÍNEZ (ID: 5)
-- =====================================================

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1150.00, 'cerrado', '2024-09-08 12:30:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 115.00, '2024-09-08 12:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 890.00, 'cerrado', '2024-09-08 19:15:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 133.50, '2024-09-08 19:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(3, 'stay', 1320.00, 'cerrado', '2024-09-09 13:45:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 132.00, '2024-09-09 13:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 750.00, 'cerrado', '2024-09-09 20:00:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 112.50, '2024-09-09 20:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1580.00, 'cerrado', '2024-09-10 14:20:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 158.00, '2024-09-10 14:25:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(3, 'stay', 920.00, 'cerrado', '2024-09-11 12:00:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 138.00, '2024-09-11 12:05:00');

-- =====================================================
-- PEDIDOS SEPTIEMBRE 2024 - DIEGO RODRÍGUEZ (ID: 6)
-- =====================================================

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1800.00, 'cerrado', '2024-09-08 20:00:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 450.00, '2024-09-08 20:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 2200.00, 'cerrado', '2024-09-10 21:15:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 550.00, '2024-09-10 21:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 1650.00, 'cerrado', '2024-09-12 19:30:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 412.50, '2024-09-12 19:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1950.00, 'cerrado', '2024-09-14 20:45:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 487.50, '2024-09-14 20:50:00');

-- =====================================================
-- MÁS PEDIDOS PARA COMPLETAR EL MES (SEMANAS 3-4)
-- =====================================================

-- Juan Pérez - Semana 3
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 980.00, 'cerrado', '2024-09-15 12:45:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 98.00, '2024-09-15 12:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1150.00, 'cerrado', '2024-09-15 19:30:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 172.50, '2024-09-15 19:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(3, 'stay', 750.00, 'cerrado', '2024-09-16 13:20:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 112.50, '2024-09-16 13:25:00');

-- María García - Semana 3
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1100.00, 'cerrado', '2024-09-15 11:30:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 220.00, '2024-09-15 11:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 950.00, 'cerrado', '2024-09-15 18:45:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 190.00, '2024-09-15 18:50:00');

-- Carlos López - Semana 3
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 750.00, 'cerrado', '2024-09-23 12:00:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 37.50, '2024-09-23 12:05:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 1100.00, 'cerrado', '2024-09-25 14:30:00', 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 55.00, '2024-09-25 14:35:00');

-- Ana Martínez - Semana 4
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1250.00, 'cerrado', '2024-09-23 13:45:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 187.50, '2024-09-23 13:50:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 980.00, 'cerrado', '2024-09-25 19:20:00', 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 147.00, '2024-09-25 19:25:00');

-- Diego Rodríguez - Semana 4
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 2100.00, 'cerrado', '2024-09-22 20:30:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 525.00, '2024-09-22 20:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 1950.00, 'cerrado', '2024-09-27 21:15:00', 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 487.50, '2024-09-27 21:20:00');

-- =====================================================
-- ALGUNOS PEDIDOS ADICIONALES PARA COMPLETAR DATOS
-- =====================================================

-- Juan Pérez - pedidos adicionales
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1200.00, 'cerrado', '2024-09-22 12:30:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 180.00, '2024-09-22 12:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 950.00, 'cerrado', '2024-09-24 19:15:00', 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 142.50, '2024-09-24 19:20:00');

-- María García - pedidos adicionales
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 1350.00, 'cerrado', '2024-09-22 13:15:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 270.00, '2024-09-22 13:20:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 890.00, 'cerrado', '2024-09-24 18:30:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 178.00, '2024-09-24 18:35:00');

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(6, 'stay', 1580.00, 'cerrado', '2024-09-26 21:00:00', 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 316.00, '2024-09-26 21:05:00');

-- =====================================================
-- RESUMEN DE DATOS INSERTADOS
-- =====================================================
/*
MOZOS Y SUS ESTADÍSTICAS ESPERADAS (septiembre 2024):

1. María García (ID: 3) - LA MEJOR
   - ~15 pedidos
   - Propinas consistentes del 20%
   - Total vendido: ~$16,000
   - Total propinas: ~$3,200
   - Tasa de propina: 20%

2. Diego Rodríguez (ID: 6) - PREMIUM SERVICE  
   - 6 pedidos de alto valor
   - Propinas del 25%
   - Total vendido: ~$11,650
   - Total propinas: ~$2,912
   - Tasa de propina: 25%

3. Juan Pérez (ID: 2) - RENDIMIENTO MEDIO
   - ~16 pedidos
   - Propinas variables 10-20%
   - Total vendido: ~$16,500
   - Total propinas: ~$2,200
   - Tasa de propina: ~13%

4. Ana Martínez (ID: 5) - RENDIMIENTO MEDIO-BAJO
   - 8 pedidos
   - Propinas del 10-15%
   - Total vendido: ~$8,800
   - Total propinas: ~$1,183
   - Tasa de propina: ~13%

5. Carlos López (ID: 4) - NECESITA MEJORAR
   - 9 pedidos
   - Propinas consistentemente bajas (5%)
   - Total vendido: ~$7,750
   - Total propinas: ~$387
   - Tasa de propina: 5%

Total: ~40 pedidos con propinas reales y balanceadas
*/