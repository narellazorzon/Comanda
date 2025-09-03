-- Script para insertar datos de ejemplo para probar los reportes
-- Ejecutar después de crear la base de datos

USE comanda;

-- Insertar usuarios de ejemplo
INSERT INTO usuarios (nombre, apellido, email, contrasenia, rol, estado) VALUES
('Juan', 'Pérez', 'juan@restaurante.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo', 'activo'),
('María', 'González', 'maria@restaurante.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo', 'activo'),
('Carlos', 'López', 'carlos@restaurante.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo', 'activo'),
('Ana', 'Martínez', 'ana@restaurante.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', 'activo');

-- Insertar mesas de ejemplo
INSERT INTO mesas (numero, estado, ubicacion) VALUES
(1, 'libre', 'Terraza'),
(2, 'libre', 'Interior'),
(3, 'libre', 'Terraza'),
(4, 'libre', 'Interior'),
(5, 'libre', 'Terraza'),
(6, 'libre', 'Interior');

-- Insertar items de la carta
INSERT INTO carta (nombre, descripcion, precio, categoria, disponibilidad) VALUES
-- Platos principales
('Milanesa de Pollo', 'Milanesa de pollo con papas fritas y ensalada', 12.50, 'Platos Principales', 1),
('Bife de Chorizo', 'Bife de chorizo a la parrilla con guarnición', 18.00, 'Platos Principales', 1),
('Pasta Carbonara', 'Pasta con salsa carbonara y panceta', 14.50, 'Platos Principales', 1),
('Ensalada César', 'Lechuga, crutones, parmesano y aderezo césar', 9.00, 'Ensaladas', 1),
('Pizza Margherita', 'Pizza con tomate, mozzarella y albahaca', 11.00, 'Pizzas', 1),
('Hamburguesa Clásica', 'Hamburguesa con queso, lechuga y tomate', 10.50, 'Hamburguesas', 1),

-- Bebidas
('Coca Cola', 'Gaseosa Coca Cola 500ml', 3.50, 'Bebidas', 1),
('Agua Mineral', 'Agua mineral sin gas 500ml', 2.00, 'Bebidas', 1),
('Cerveza Nacional', 'Cerveza rubia 500ml', 4.50, 'Bebidas', 1),
('Limonada', 'Limonada natural', 3.00, 'Bebidas', 1),

-- Postres
('Tiramisú', 'Postre italiano con café y mascarpone', 6.50, 'Postres', 1),
('Flan Casero', 'Flan casero con caramelo', 5.00, 'Postres', 1),
('Helado de Vainilla', 'Helado de vainilla con frutas', 4.50, 'Postres', 1);

-- Insertar pedidos de ejemplo (últimos 30 días)
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
-- Pedidos de Juan (mozo 1)
(1, 'stay', 25.50, 'pagado', DATE_SUB(NOW(), INTERVAL 1 DAY), 1),
(2, 'stay', 32.00, 'pagado', DATE_SUB(NOW(), INTERVAL 2 DAY), 1),
(3, 'stay', 18.50, 'pagado', DATE_SUB(NOW(), INTERVAL 3 DAY), 1),
(4, 'stay', 28.00, 'pagado', DATE_SUB(NOW(), INTERVAL 5 DAY), 1),
(5, 'stay', 22.50, 'pagado', DATE_SUB(NOW(), INTERVAL 7 DAY), 1),

-- Pedidos de María (mozo 2)
(2, 'stay', 30.00, 'pagado', DATE_SUB(NOW(), INTERVAL 1 DAY), 2),
(3, 'stay', 24.50, 'pagado', DATE_SUB(NOW(), INTERVAL 2 DAY), 2),
(4, 'stay', 35.00, 'pagado', DATE_SUB(NOW(), INTERVAL 4 DAY), 2),
(5, 'stay', 19.50, 'pagado', DATE_SUB(NOW(), INTERVAL 6 DAY), 2),
(6, 'stay', 27.00, 'pagado', DATE_SUB(NOW(), INTERVAL 8 DAY), 2),

-- Pedidos de Carlos (mozo 3)
(1, 'stay', 29.50, 'pagado', DATE_SUB(NOW(), INTERVAL 1 DAY), 3),
(3, 'stay', 21.00, 'pagado', DATE_SUB(NOW(), INTERVAL 3 DAY), 3),
(4, 'stay', 33.50, 'pagado', DATE_SUB(NOW(), INTERVAL 5 DAY), 3),
(6, 'stay', 26.00, 'pagado', DATE_SUB(NOW(), INTERVAL 7 DAY), 3),

-- Pedidos takeaway
(NULL, 'takeaway', 15.50, 'pagado', DATE_SUB(NOW(), INTERVAL 1 DAY), 1),
(NULL, 'takeaway', 12.00, 'pagado', DATE_SUB(NOW(), INTERVAL 2 DAY), 2),
(NULL, 'takeaway', 18.50, 'pagado', DATE_SUB(NOW(), INTERVAL 3 DAY), 3),
(NULL, 'takeaway', 22.00, 'pagado', DATE_SUB(NOW(), INTERVAL 4 DAY), 1),

-- Pedidos de hace más tiempo (para probar filtros)
(1, 'stay', 20.00, 'pagado', DATE_SUB(NOW(), INTERVAL 15 DAY), 1),
(2, 'stay', 25.50, 'pagado', DATE_SUB(NOW(), INTERVAL 20 DAY), 2),
(3, 'stay', 18.00, 'pagado', DATE_SUB(NOW(), INTERVAL 25 DAY), 3);

-- Insertar detalles de pedidos
INSERT INTO detalle_pedido (id_pedido, id_item, cantidad, precio_unitario) VALUES
-- Pedido 1 (Juan)
(1, 1, 2, 12.50), -- 2 Milanesas de Pollo
(1, 7, 2, 3.50),  -- 2 Coca Colas

-- Pedido 2 (Juan)
(2, 2, 1, 18.00), -- 1 Bife de Chorizo
(2, 7, 2, 3.50),  -- 2 Coca Colas
(2, 11, 1, 6.50), -- 1 Tiramisú

-- Pedido 3 (Juan)
(3, 3, 1, 14.50), -- 1 Pasta Carbonara
(3, 8, 1, 2.00),  -- 1 Agua Mineral
(3, 12, 1, 5.00), -- 1 Flan Casero

-- Pedido 4 (Juan)
(4, 5, 2, 11.00), -- 2 Pizzas Margherita
(4, 9, 2, 4.50),  -- 2 Cervezas Nacionales

-- Pedido 5 (Juan)
(5, 6, 1, 10.50), -- 1 Hamburguesa Clásica
(5, 7, 1, 3.50),  -- 1 Coca Cola
(5, 13, 1, 4.50), -- 1 Helado de Vainilla
(5, 10, 1, 3.00), -- 1 Limonada

-- Pedido 6 (María)
(6, 1, 1, 12.50), -- 1 Milanesa de Pollo
(6, 4, 1, 9.00),  -- 1 Ensalada César
(6, 7, 2, 3.50),  -- 2 Coca Colas
(6, 11, 1, 6.50), -- 1 Tiramisú

-- Pedido 7 (María)
(7, 2, 1, 18.00), -- 1 Bife de Chorizo
(7, 8, 1, 2.00),  -- 1 Agua Mineral
(7, 12, 1, 5.00), -- 1 Flan Casero

-- Pedido 8 (María)
(8, 3, 2, 14.50), -- 2 Pastas Carbonara
(8, 9, 2, 4.50),  -- 2 Cervezas Nacionales
(8, 13, 1, 4.50), -- 1 Helado de Vainilla

-- Pedido 9 (María)
(9, 5, 1, 11.00), -- 1 Pizza Margherita
(9, 6, 1, 10.50), -- 1 Hamburguesa Clásica
(9, 7, 1, 3.50),  -- 1 Coca Cola

-- Pedido 10 (María)
(10, 4, 2, 9.00), -- 2 Ensaladas César
(10, 8, 2, 2.00), -- 2 Aguas Minerales
(10, 10, 1, 3.00), -- 1 Limonada

-- Pedido 11 (Carlos)
(11, 1, 1, 12.50), -- 1 Milanesa de Pollo
(11, 2, 1, 18.00), -- 1 Bife de Chorizo
(11, 7, 1, 3.50),  -- 1 Coca Cola

-- Pedido 12 (Carlos)
(12, 3, 1, 14.50), -- 1 Pasta Carbonara
(12, 8, 1, 2.00),  -- 1 Agua Mineral
(12, 11, 1, 6.50), -- 1 Tiramisú

-- Pedido 13 (Carlos)
(13, 5, 2, 11.00), -- 2 Pizzas Margherita
(13, 9, 2, 4.50),  -- 2 Cervezas Nacionales
(13, 12, 1, 5.00), -- 1 Flan Casero

-- Pedido 14 (Carlos)
(14, 6, 1, 10.50), -- 1 Hamburguesa Clásica
(14, 4, 1, 9.00),  -- 1 Ensalada César
(14, 7, 1, 3.50),  -- 1 Coca Cola
(14, 13, 1, 4.50), -- 1 Helado de Vainilla

-- Pedidos takeaway
(15, 1, 1, 12.50), -- 1 Milanesa de Pollo
(15, 7, 1, 3.50),  -- 1 Coca Cola

(16, 6, 1, 10.50), -- 1 Hamburguesa Clásica
(16, 8, 1, 2.00),  -- 1 Agua Mineral

(17, 3, 1, 14.50), -- 1 Pasta Carbonara
(17, 9, 1, 4.50),  -- 1 Cerveza Nacional

(18, 5, 1, 11.00), -- 1 Pizza Margherita
(18, 7, 1, 3.50),  -- 1 Coca Cola
(18, 11, 1, 6.50), -- 1 Tiramisú

-- Pedidos antiguos
(19, 1, 1, 12.50), -- 1 Milanesa de Pollo
(19, 7, 1, 3.50),  -- 1 Coca Cola
(19, 12, 1, 5.00), -- 1 Flan Casero

(20, 2, 1, 18.00), -- 1 Bife de Chorizo
(20, 9, 1, 4.50),  -- 1 Cerveza Nacional

(21, 4, 1, 9.00),  -- 1 Ensalada César
(21, 8, 1, 2.00),  -- 1 Agua Mineral
(21, 13, 1, 4.50); -- 1 Helado de Vainilla

-- Actualizar totales de pedidos (esto se haría automáticamente en la aplicación)
UPDATE pedidos SET total = (
    SELECT SUM(cantidad * precio_unitario) 
    FROM detalle_pedido 
    WHERE id_pedido = pedidos.id_pedido
);
