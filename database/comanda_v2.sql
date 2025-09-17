-- =====================================================
-- Sistema de Gestión de Comandas - Base de Datos v2.0
-- =====================================================
-- Autor: Sistema Comanda
-- Fecha: 2025
-- Descripción: Script completo para crear la base de datos
--              con estructura y datos de prueba
-- =====================================================

-- Crear base de datos
DROP DATABASE IF EXISTS comanda;
CREATE DATABASE comanda CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE comanda;

-- =====================================================
-- ESTRUCTURA DE TABLAS
-- =====================================================

-- Tabla de usuarios (mozos y administradores)
CREATE TABLE usuarios (
    id_usuario INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'mozo') NOT NULL DEFAULT 'mozo',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de mesas
CREATE TABLE mesas (
    id_mesa INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    numero INT NOT NULL UNIQUE,
    ubicacion VARCHAR(100),
    capacidad INT NOT NULL DEFAULT 4,
    estado ENUM('disponible', 'ocupada', 'reservada') NOT NULL DEFAULT 'disponible',
    activa TINYINT(1) NOT NULL DEFAULT 1,
    id_mozo INT(10) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id_mesa),
    INDEX idx_estado (estado),
    INDEX idx_mozo (id_mozo),
    INDEX idx_activa (activa),
    FOREIGN KEY (id_mozo) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de categorías de carta
CREATE TABLE carta (
    id_item INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    categoria VARCHAR(50),
    disponibilidad TINYINT(1) NOT NULL DEFAULT 1,
    imagen_url VARCHAR(255) DEFAULT '/Comanda/public/assets/images/placeholder.svg',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_item),
    INDEX idx_categoria (categoria),
    INDEX idx_disponibilidad (disponibilidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de pedidos
CREATE TABLE pedidos (
    id_pedido INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_mesa INT(10) UNSIGNED DEFAULT NULL,
    modo_consumo ENUM('stay', 'takeaway') NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('pendiente', 'en_preparacion', 'servido', 'cuenta', 'cerrado') NOT NULL DEFAULT 'pendiente',
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_mozo INT(10) UNSIGNED DEFAULT NULL,
    observaciones TEXT,
    forma_pago VARCHAR(50),
    cliente_nombre VARCHAR(100),
    cliente_email VARCHAR(100),
    PRIMARY KEY (id_pedido),
    INDEX idx_mesa (id_mesa),
    INDEX idx_mozo (id_mozo),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_hora),
    FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa) ON DELETE SET NULL,
    FOREIGN KEY (id_mozo) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de detalles de pedido
CREATE TABLE detalle_pedido (
    id_detalle INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pedido INT(10) UNSIGNED NOT NULL,
    id_item INT(10) UNSIGNED NOT NULL,
    cantidad INT(10) UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    detalle TEXT,
    PRIMARY KEY (id_detalle),
    INDEX idx_pedido (id_pedido),
    INDEX idx_item (id_item),
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_item) REFERENCES carta(id_item) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de propinas
CREATE TABLE propinas (
    id_propina INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pedido INT(10) UNSIGNED NOT NULL,
    id_mozo INT(10) UNSIGNED DEFAULT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_propina),
    INDEX idx_pedido (id_pedido),
    INDEX idx_mozo (id_mozo),
    INDEX idx_fecha (fecha_hora),
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_mozo) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de llamados a mesa
CREATE TABLE llamados_mesa (
    id_llamado INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_mesa INT(10) UNSIGNED NOT NULL,
    tipo ENUM('mozo', 'cuenta', 'ayuda') NOT NULL DEFAULT 'mozo',
    estado ENUM('pendiente', 'atendido') NOT NULL DEFAULT 'pendiente',
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atendido_por INT(10) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id_llamado),
    INDEX idx_mesa (id_mesa),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_hora),
    FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa) ON DELETE CASCADE,
    FOREIGN KEY (atendido_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de inventario
CREATE TABLE inventario (
    id_producto INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(50),
    unidad_medida VARCHAR(20) NOT NULL,
    stock_actual DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    precio_unitario DECIMAL(10,2),
    proveedor VARCHAR(100),
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_producto),
    INDEX idx_categoria (categoria),
    INDEX idx_stock (stock_actual)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de movimientos de inventario
CREATE TABLE inventario_movimientos (
    id_movimiento INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_producto INT(10) UNSIGNED NOT NULL,
    tipo_movimiento ENUM('entrada', 'salida', 'ajuste') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(255),
    id_usuario INT(10) UNSIGNED,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_movimiento),
    INDEX idx_producto (id_producto),
    INDEX idx_fecha (fecha_hora),
    FOREIGN KEY (id_producto) REFERENCES inventario(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de pagos (para registrar métodos de pago detallados)
CREATE TABLE pagos (
    id_pago INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pedido INT(10) UNSIGNED NOT NULL,
    metodo VARCHAR(50) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    referencia VARCHAR(100),
    PRIMARY KEY (id_pago),
    INDEX idx_pedido (id_pedido),
    INDEX idx_fecha (fecha_hora),
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista de inventario por categoría
CREATE VIEW vista_inventario_categoria AS
SELECT
    categoria,
    COUNT(*) as total_productos,
    SUM(stock_actual * precio_unitario) as valor_total,
    SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) as productos_bajo_minimo
FROM inventario
GROUP BY categoria;

-- Vista de productos con stock bajo
CREATE VIEW vista_stock_bajo AS
SELECT
    id_producto,
    nombre,
    categoria,
    stock_actual,
    stock_minimo,
    (stock_minimo - stock_actual) as cantidad_faltante
FROM inventario
WHERE stock_actual <= stock_minimo
ORDER BY cantidad_faltante DESC;

-- =====================================================
-- DATOS DE PRUEBA
-- =====================================================

-- Insertar usuarios de prueba
INSERT INTO usuarios (nombre, apellido, email, password, rol, activo) VALUES
-- Password: admin123 (hash bcrypt)
('Admin', 'Sistema', 'admin@comanda.com', '$2y$10$YGhI4R8KpJBQZ7YH5FMtNuLXx3P9hFZoQhPEeHJLVONQcY1cOPQPe', 'administrador', 1),
-- Password: mozo123
('Juan', 'Pérez', 'juan@comanda.com', '$2y$10$kQmE5VlGqPqGwjRgJxzFY.aJBP8ND4GxHINsVLZqzKWxSY0BwBKDi', 'mozo', 1),
('María', 'González', 'maria@comanda.com', '$2y$10$kQmE5VlGqPqGwjRgJxzFY.aJBP8ND4GxHINsVLZqzKWxSY0BwBKDi', 'mozo', 1),
('Carlos', 'Rodríguez', 'carlos@comanda.com', '$2y$10$kQmE5VlGqPqGwjRgJxzFY.aJBP8ND4GxHINsVLZqzKWxSY0BwBKDi', 'mozo', 1),
('Ana', 'Martínez', 'ana@comanda.com', '$2y$10$kQmE5VlGqPqGwjRgJxzFY.aJBP8ND4GxHINsVLZqzKWxSY0BwBKDi', 'mozo', 0),
('Pedro', 'López', 'pedro@comanda.com', '$2y$10$kQmE5VlGqPqGwjRgJxzFY.aJBP8ND4GxHINsVLZqzKWxSY0BwBKDi', 'mozo', 1);

-- Insertar mesas de prueba
INSERT INTO mesas (numero, ubicacion, capacidad, estado, id_mozo) VALUES
(1, 'Salón Principal', 4, 'disponible', 2),
(2, 'Salón Principal', 4, 'ocupada', 2),
(3, 'Salón Principal', 2, 'disponible', 2),
(4, 'Salón Principal', 6, 'disponible', 3),
(5, 'Terraza', 4, 'disponible', 3),
(6, 'Terraza', 4, 'ocupada', 3),
(7, 'Terraza', 2, 'disponible', 4),
(8, 'Terraza', 8, 'reservada', 4),
(9, 'Jardín', 6, 'disponible', 6),
(10, 'Jardín', 4, 'disponible', 6),
(11, 'Salón VIP', 8, 'disponible', 2),
(12, 'Salón VIP', 10, 'disponible', 3);

-- Insertar items en la carta
INSERT INTO carta (nombre, descripcion, precio, categoria, disponibilidad) VALUES
-- Entradas
('Tabla de Fiambres', 'Selección de jamón crudo, salame, queso, aceitunas', 850.00, 'Entradas', 1),
('Empanadas de Carne', 'Media docena de empanadas caseras de carne cortada a cuchillo', 720.00, 'Entradas', 1),
('Empanadas de Pollo', 'Media docena de empanadas de pollo', 720.00, 'Entradas', 1),
('Provoleta', 'Queso provolone grillado con orégano y tomate', 680.00, 'Entradas', 1),
('Rabas', 'Aros de calamar rebozados con salsa alioli', 920.00, 'Entradas', 1),
('Papas Bravas', 'Papas fritas con salsa picante y alioli', 580.00, 'Entradas', 1),

-- Platos Principales
('Milanesa Clásica', 'Milanesa de ternera con papas fritas', 1250.00, 'Platos Principales', 1),
('Milanesa Napolitana', 'Milanesa con jamón, queso y salsa de tomate', 1450.00, 'Platos Principales', 1),
('Bife de Chorizo', 'Bife de chorizo de 350g con guarnición', 1850.00, 'Platos Principales', 1),
('Pollo Grillado', 'Pechuga de pollo grillada con ensalada mixta', 1180.00, 'Platos Principales', 1),
('Salmon Rosado', 'Filet de salmón con puré de papas y vegetales', 2200.00, 'Platos Principales', 1),
('Ravioles de Ricota', 'Ravioles caseros con salsa a elección', 1150.00, 'Platos Principales', 1),
('Ñoquis de Papa', 'Ñoquis caseros con salsa a elección', 980.00, 'Platos Principales', 1),
('Hamburguesa Completa', 'Hamburguesa con queso, lechuga, tomate, cebolla y papas', 1100.00, 'Platos Principales', 1),
('Pizza Margherita', 'Pizza con mozzarella, tomate y albahaca', 950.00, 'Platos Principales', 1),
('Pizza Especial', 'Pizza con mozzarella, jamón, morrones y aceitunas', 1150.00, 'Platos Principales', 1),

-- Pastas
('Spaghetti Bolognesa', 'Spaghetti con salsa bolognesa casera', 980.00, 'Pastas', 1),
('Fettuccine Alfredo', 'Fettuccine con salsa de crema y queso parmesano', 1050.00, 'Pastas', 1),
('Lasagna', 'Lasagna casera de carne con bechamel', 1250.00, 'Pastas', 1),
('Pasta Carbonara', 'Pasta con panceta, huevo y queso parmesano', 1100.00, 'Pastas', 1),
('Canelones', 'Canelones de verdura y ricota', 1080.00, 'Pastas', 1),

-- Ensaladas
('Ensalada Caesar', 'Lechuga romana, crutones, parmesano y aderezo caesar', 780.00, 'Ensaladas', 1),
('Ensalada Mixta', 'Lechuga, tomate, zanahoria, huevo', 620.00, 'Ensaladas', 1),
('Ensalada Caprese', 'Tomate, mozzarella, albahaca y aceite de oliva', 850.00, 'Ensaladas', 1),
('Ensalada de Rúcula', 'Rúcula, parmesano, tomates cherry', 750.00, 'Ensaladas', 1),

-- Postres
('Flan Casero', 'Flan con dulce de leche y crema', 480.00, 'Postres', 1),
('Helado', 'Tres bochas a elección', 520.00, 'Postres', 1),
('Tiramisú', 'Postre italiano con café y mascarpone', 650.00, 'Postres', 1),
('Brownie', 'Brownie de chocolate con helado', 580.00, 'Postres', 1),
('Cheesecake', 'Tarta de queso con frutos rojos', 620.00, 'Postres', 1),
('Panqueques con Dulce', 'Dos panqueques con dulce de leche', 550.00, 'Postres', 1),

-- Bebidas
('Agua Mineral', 'Botella 500ml', 280.00, 'Bebidas', 1),
('Gaseosa Línea Coca', 'Coca Cola, Sprite, Fanta 500ml', 380.00, 'Bebidas', 1),
('Jugo Natural', 'Naranja o Limonada natural', 420.00, 'Bebidas', 1),
('Cerveza Tirada', 'Chopp 500cc', 480.00, 'Bebidas', 1),
('Copa de Vino', 'Malbec o Cabernet', 520.00, 'Bebidas', 1),

-- Cafetería
('Café Espresso', 'Café espresso italiano', 320.00, 'Cafetería', 1),
('Café Cortado', 'Café con un toque de leche', 340.00, 'Cafetería', 1),
('Café con Leche', 'Café con leche espumada', 380.00, 'Cafetería', 1),
('Capuccino', 'Café con leche espumada y cacao', 420.00, 'Cafetería', 1),
('Té', 'Variedad de té en hebras', 320.00, 'Cafetería', 1),
('Submarino', 'Leche caliente con chocolate', 450.00, 'Cafetería', 1);

-- Insertar pedidos de ejemplo
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, id_mozo, cliente_nombre, cliente_email, forma_pago) VALUES
(2, 'stay', 3250.00, 'servido', 2, 'Roberto Silva', 'roberto@email.com', 'tarjeta'),
(6, 'stay', 2780.00, 'en_preparacion', 3, 'Laura Méndez', 'laura@email.com', 'efectivo'),
(null, 'takeaway', 1850.00, 'cerrado', 4, 'Diego Torres', 'diego@email.com', 'efectivo'),
(1, 'stay', 4560.00, 'cuenta', 2, 'Carla Ruiz', 'carla@email.com', 'tarjeta');

-- Insertar detalles de pedidos
INSERT INTO detalle_pedido (id_pedido, id_item, cantidad, precio_unitario, detalle) VALUES
-- Pedido 1
(1, 1, 1, 850.00, ''),
(1, 9, 2, 1850.00, 'Término medio'),
(1, 37, 2, 280.00, ''),
-- Pedido 2
(2, 4, 1, 680.00, ''),
(2, 14, 2, 1100.00, 'Sin cebolla'),
(2, 38, 2, 380.00, ''),
-- Pedido 3
(3, 7, 1, 1250.00, ''),
(3, 27, 1, 480.00, 'Con extra dulce de leche'),
-- Pedido 4
(4, 2, 2, 720.00, ''),
(4, 8, 1, 1450.00, ''),
(4, 11, 1, 2200.00, 'Término 3/4'),
(4, 39, 3, 420.00, '');

-- Insertar propinas de ejemplo
INSERT INTO propinas (id_pedido, id_mozo, monto) VALUES
(3, 4, 185.00),
(4, 2, 450.00);

-- Insertar items de inventario de ejemplo
INSERT INTO inventario (nombre, categoria, unidad_medida, stock_actual, stock_minimo, precio_unitario, proveedor) VALUES
('Harina', 'Insumos Básicos', 'kg', 45.5, 20.0, 150.00, 'Distribuidora Central'),
('Aceite', 'Insumos Básicos', 'litros', 28.0, 15.0, 850.00, 'Distribuidora Central'),
('Carne Vacuna', 'Carnes', 'kg', 35.0, 25.0, 2800.00, 'Frigorífico Norte'),
('Pollo', 'Carnes', 'kg', 22.0, 15.0, 890.00, 'Frigorífico Norte'),
('Tomate', 'Verduras', 'kg', 18.0, 10.0, 280.00, 'Verdulería Orgánica'),
('Lechuga', 'Verduras', 'kg', 12.0, 8.0, 320.00, 'Verdulería Orgánica'),
('Queso Mozzarella', 'Lácteos', 'kg', 15.0, 10.0, 1850.00, 'Lácteos del Sur'),
('Cerveza', 'Bebidas', 'litros', 85.0, 50.0, 480.00, 'Cervecería Artesanal'),
('Vino Malbec', 'Bebidas', 'botellas', 24.0, 12.0, 1200.00, 'Bodega Premium'),
('Café', 'Cafetería', 'kg', 8.5, 5.0, 3200.00, 'Importadora Café');

-- Insertar movimientos de inventario de ejemplo
INSERT INTO inventario_movimientos (id_producto, tipo_movimiento, cantidad, motivo, id_usuario) VALUES
(1, 'entrada', 50.0, 'Compra mensual', 1),
(3, 'salida', 15.0, 'Uso en cocina', 2),
(5, 'entrada', 30.0, 'Reposición semanal', 1),
(8, 'salida', 25.0, 'Consumo del día', 3),
(10, 'ajuste', -2.0, 'Ajuste de inventario', 1);

-- =====================================================
-- INFORMACIÓN DE ACCESO
-- =====================================================
-- Usuario administrador:
--   Email: admin@comanda.com
--   Password: admin123
--
-- Usuarios mozo:
--   Email: juan@comanda.com / maria@comanda.com / carlos@comanda.com
--   Password: mozo123
-- =====================================================