-- =====================================================
-- ESQUEMA COMPLETO UNIFICADO - SISTEMA COMANDA
-- Incluye todas las mejoras y datos de prueba
-- Versión: Actualizada con todas las funcionalidades
-- =====================================================

-- -------------------------------------------------
-- 1. Borramos la base si existiera y la creamos
-- -------------------------------------------------
DROP DATABASE IF EXISTS comanda;
CREATE DATABASE comanda
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE comanda;

-- -------------------------------------------------
-- 2. Tabla usuarios (administradores y mozos)
-- -------------------------------------------------
CREATE TABLE usuarios (
  id_usuario     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(50) NOT NULL,
  apellido       VARCHAR(50) NOT NULL,
  email          VARCHAR(100) NOT NULL UNIQUE,
  contrasenia    VARCHAR(255) NOT NULL,
  rol            ENUM('administrador','mozo') NOT NULL,
  estado         ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 3. Tabla mesas (CON MOZO ASIGNADO)
-- -------------------------------------------------
CREATE TABLE mesas (
  id_mesa        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero         INT NOT NULL UNIQUE,
  estado         ENUM('libre','ocupada','reservada') NOT NULL DEFAULT 'libre',
  ubicacion      VARCHAR(100) NULL,
  id_mozo        INT UNSIGNED NULL,
  status         TINYINT(1) NOT NULL DEFAULT 1, -- 1=activa, 0=inactiva
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mesas_mozo
    FOREIGN KEY (id_mozo) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 4. Tabla carta (items del menú)
-- -------------------------------------------------
CREATE TABLE carta (
  id_item        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(100) NOT NULL UNIQUE,
  descripcion    TEXT,
  precio         DECIMAL(10,2) NOT NULL,
  categoria      VARCHAR(50) NULL,
  disponibilidad TINYINT(1) NOT NULL DEFAULT 1,
  imagen_url     VARCHAR(255) NULL,
  descuento      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 5. Tabla pedidos (ESTADOS ACTUALIZADOS)
-- -------------------------------------------------
CREATE TABLE pedidos (
  id_pedido    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_mesa      INT UNSIGNED NULL,
  modo_consumo ENUM('stay','takeaway') NOT NULL,
  total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado       ENUM('pendiente','en_preparacion','servido','cerrado','cancelado') NOT NULL DEFAULT 'pendiente',
  fecha_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_mozo      INT UNSIGNED NULL,
  forma_pago   ENUM('efectivo','tarjeta','transferencia') NULL,
  observaciones TEXT NULL,
  cliente_nombre VARCHAR(100) NULL,
  cliente_email VARCHAR(100) NULL,
  FOREIGN KEY (id_mesa)
    REFERENCES mesas(id_mesa)
      ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (id_mozo)
    REFERENCES usuarios(id_usuario)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 6. Tabla detalle_pedido
-- -------------------------------------------------
CREATE TABLE detalle_pedido (
  id_detalle     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pedido      INT UNSIGNED NOT NULL,
  id_item        INT UNSIGNED NOT NULL,
  cantidad       INT UNSIGNED NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(10,2) NOT NULL,
  detalle        TEXT NULL,
  FOREIGN KEY (id_pedido)
    REFERENCES pedidos(id_pedido)
      ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (id_item)
    REFERENCES carta(id_item)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 7. Tabla llamado_mesa
-- -------------------------------------------------
CREATE TABLE llamado_mesa (
  id_llamado    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_mesa       INT UNSIGNED NOT NULL,
  timestamp     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atendido      TINYINT(1) NOT NULL DEFAULT 0,
  id_mozo       INT UNSIGNED NULL,
  FOREIGN KEY (id_mesa)
    REFERENCES mesas(id_mesa)
      ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (id_mozo)
    REFERENCES usuarios(id_usuario)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 8. Tabla inventario (versión simplificada)
-- -------------------------------------------------
CREATE TABLE inventario (
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

-- -------------------------------------------------
-- 9. Tabla inventario_movimientos
-- -------------------------------------------------
CREATE TABLE inventario_movimientos (
    id_movimiento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_item INT UNSIGNED NOT NULL,
    tipo_movimiento ENUM('entrada','salida','ajuste','venta') NOT NULL,
    cantidad INT NOT NULL,
    cantidad_anterior INT NOT NULL,
    cantidad_nueva INT NOT NULL,
    motivo VARCHAR(200) NULL,
    id_pedido INT UNSIGNED NULL,
    id_usuario INT UNSIGNED NULL,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_inv_mov_item
        FOREIGN KEY (id_item) REFERENCES carta(id_item)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_inv_mov_pedido
        FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_inv_mov_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- ÍNDICES ADICIONALES
-- -------------------------------------------------
CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_pedidos_fecha ON pedidos(fecha_hora);
CREATE INDEX idx_pedidos_mesa ON pedidos(id_mesa);
CREATE INDEX idx_pedidos_mozo ON pedidos(id_mozo);
CREATE INDEX idx_detalle_pedido_pedido ON detalle_pedido(id_pedido);
CREATE INDEX idx_llamado_mesa_mesa ON llamado_mesa(id_mesa);
CREATE INDEX idx_llamado_mesa_atendido ON llamado_mesa(atendido);

-- -------------------------------------------------
-- VIEWS ÚTILES
-- -------------------------------------------------

-- Vista para ver mesas con estado de mozo
CREATE VIEW vista_mesas_con_mozo AS
SELECT
    m.*,
    u.nombre as mozo_nombre,
    u.apellido as mozo_apellido,
    CONCAT(u.nombre, ' ', u.apellido) as mozo_nombre_completo,
    (SELECT COUNT(*) FROM pedidos p WHERE p.id_mesa = m.id_mesa AND p.estado NOT IN ('cerrado', 'cancelado')) as pedidos_activos
FROM mesas m
LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
WHERE m.status = 1;

-- Vista para stock bajo
CREATE VIEW vista_stock_bajo AS
SELECT
    i.*,
    c.nombre as item_nombre,
    c.categoria as item_categoria,
    c.precio as item_precio
FROM inventario i
JOIN carta c ON i.id_item = c.id_item
WHERE i.cantidad_disponible <= i.cantidad_minima OR i.estado = 'agotado'
ORDER BY i.cantidad_disponible ASC;

-- Vista para resumen por categorías
CREATE VIEW vista_inventario_categoria AS
SELECT
    c.categoria,
    COUNT(DISTINCT i.id_item) as total_items,
    SUM(i.cantidad_disponible) as total_stock,
    COUNT(CASE WHEN i.estado = 'disponible' THEN 1 END) as items_disponibles,
    COUNT(CASE WHEN i.estado = 'agotado' THEN 1 END) as items_agotados,
    AVG(i.cantidad_disponible) as promedio_stock
FROM inventario i
JOIN carta c ON i.id_item = c.id_item
GROUP BY c.categoria
ORDER BY c.categoria;

-- -------------------------------------------------
-- TRIGGERS
-- -------------------------------------------------

-- Trigger para actualizar stock automáticamente al vender
DELIMITER //
CREATE TRIGGER tr_detalle_pedido_descontar_stock
AFTER INSERT ON detalle_pedido
FOR EACH ROW
BEGIN
    DECLARE stock_actual INT;
    DECLARE nuevo_stock INT;

    -- Obtener stock actual
    SELECT cantidad_disponible INTO stock_actual
    FROM inventario
    WHERE id_item = NEW.id_item;

    -- Calcular nuevo stock
    SET nuevo_stock = stock_actual - NEW.cantidad;

    -- Actualizar inventario
    UPDATE inventario
    SET cantidad_disponible = nuevo_stock,
        estado = IF(nuevo_stock <= 0, 'agotado', 'disponible')
    WHERE id_item = NEW.id_item;

    -- Registrar movimiento
    INSERT INTO inventario_movimientos (
        id_item, tipo_movimiento, cantidad,
        cantidad_anterior, cantidad_nueva,
        motivo, id_pedido
    ) VALUES (
        NEW.id_item, 'venta', NEW.cantidad,
        stock_actual, nuevo_stock,
        'Venta automatica', NEW.id_pedido
    );
END//
DELIMITER ;

-- -------------------------------------------------
-- STORED PROCEDURES
-- -------------------------------------------------

-- Procedimiento para actualizar stock manualmente
DELIMITER //
CREATE PROCEDURE sp_actualizar_stock(
    IN p_id_item INT,
    IN p_cantidad INT,
    IN p_tipo_movimiento ENUM('entrada','salida','ajuste'),
    IN p_motivo VARCHAR(200),
    IN p_id_usuario INT
)
BEGIN
    DECLARE stock_actual INT;
    DECLARE nuevo_stock INT;

    -- Obtener stock actual
    SELECT cantidad_disponible INTO stock_actual
    FROM inventario
    WHERE id_item = p_id_item;

    -- Calcular nuevo stock según tipo
    IF p_tipo_movimiento = 'entrada' THEN
        SET nuevo_stock = stock_actual + p_cantidad;
    ELSEIF p_tipo_movimiento = 'salida' THEN
        SET nuevo_stock = stock_actual - p_cantidad;
    ELSE -- ajuste
        SET nuevo_stock = p_cantidad;
    END IF;

    -- Actualizar inventario
    UPDATE inventario
    SET cantidad_disponible = nuevo_stock,
        estado = IF(nuevo_stock <= 0, 'agotado', 'disponible')
    WHERE id_item = p_id_item;

    -- Registrar movimiento
    INSERT INTO inventario_movimientos (
        id_item, tipo_movimiento, cantidad,
        cantidad_anterior, cantidad_nueva,
        motivo, id_usuario
    ) VALUES (
        p_id_item, p_tipo_movimiento, p_cantidad,
        stock_actual, nuevo_stock,
        p_motivo, p_id_usuario
    );

    SELECT 'Stock actualizado correctamente' as mensaje;
END//
DELIMITER ;

-- -------------------------------------------------
-- DATOS DE PRUEBA
-- -------------------------------------------------

-- Usuarios de prueba
INSERT INTO usuarios (nombre, apellido, email, contrasenia, rol) VALUES
('Admin', 'Sistema', 'admin@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'administrador'),
('Juan', 'Pérez', 'juan@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'mozo'),
('María', 'García', 'maria@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'mozo'),
('Carlos', 'López', 'carlos@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'mozo');

-- Mesas de prueba con mozos asignados
INSERT INTO mesas (numero, ubicacion, id_mozo, estado) VALUES
(1, 'Zona Principal', 2, 'libre'),
(2, 'Zona Principal', 2, 'ocupada'),
(3, 'Terraza', 3, 'libre'),
(4, 'Terraza', 3, 'libre'),
(5, 'VIP', 4, 'reservada'),
(6, 'VIP', 4, 'libre');

-- Items del menú de prueba
INSERT INTO carta (nombre, descripcion, precio, categoria, disponibilidad) VALUES
('Hamburguesa Clásica', 'Carne 200g con lechuga, tomate y queso', 15.50, 'Hamburguesas', 1),
('Hamburguesa BBQ', 'Carne 200g con salsa BBQ y cebolla caramelizada', 17.00, 'Hamburguesas', 1),
('Papas Fritas', 'Porción individual de papas crujientes', 5.50, 'Acompañamientos', 1),
('Aros de Cebolla', 'Porción de aros de cebolla rebozados', 6.00, 'Acompañamientos', 1),
('Coca Cola 500ml', 'Bebida gaseosa cola', 3.50, 'Bebidas', 1),
('Agua Mineral 500ml', 'Agua natural sin gas', 2.50, 'Bebidas', 1),
('Heineken 330ml', 'Cerveza lager botella', 5.00, 'Bebidas Alcohólicas', 1),
('Ensalada César', 'Lechuga, pollo, crutones y aderezo césar', 12.00, 'Ensaladas', 1),
('Pizza Margarita', 'Pizza mediana con tomate y mozzarella', 18.00, 'Pizzas', 1),
('Pizza Napolitana', 'Pizza mediana con tomate, mozzarella y albahaca', 20.00, 'Pizzas', 1);

-- Inventario inicial
INSERT INTO inventario (id_item, cantidad_disponible, cantidad_minima) VALUES
(1, 50, 20), -- Hamburguesa Clásica
(2, 30, 15), -- Hamburguesa BBQ
(3, 100, 50), -- Papas Fritas
(4, 80, 40), -- Aros de Cebolla
(5, 60, 30), -- Coca Cola
(6, 50, 25), -- Agua Mineral
(7, 40, 20), -- Heineken
(8, 25, 10), -- Ensalada César
(9, 20, 10), -- Pizza Margarita
(10, 15, 10); -- Pizza Napolitana

-- Pedidos de prueba
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, id_mozo, forma_pago, cliente_nombre) VALUES
(2, 'stay', 36.50, 'en_preparacion', 2, 'efectivo', 'Cliente 1'),
(5, 'stay', 54.00, 'pendiente', 4, 'tarjeta', 'Cliente 2'),
(NULL, 'takeaway', 21.00, 'servido', 3, 'efectivo', 'Cliente Takeaway 1');

-- Detalles de pedidos
INSERT INTO detalle_pedido (id_pedido, id_item, cantidad, precio_unitario) VALUES
-- Pedido 1 (Mesa 2)
(1, 1, 2, 15.50),
(1, 3, 1, 5.50),
-- Pedido 2 (Mesa 5)
(2, 2, 2, 17.00),
(2, 9, 1, 20.00),
-- Pedido 3 (Takeaway)
(3, 4, 2, 6.00),
(3, 6, 2, 2.50);

-- Llamados de mesa de prueba
INSERT INTO llamado_mesa (id_mesa, atendido, id_mozo) VALUES
(2, 1, 2),
(5, 0, NULL),
(1, 0, NULL);

-- Movimientos de inventario de prueba
INSERT INTO inventario_movimientos (id_item, tipo_movimiento, cantidad, cantidad_anterior, cantidad_nueva, motivo, id_pedido) VALUES
(1, 'venta', 2, 52, 50, 'Venta automatica', 1),
(2, 'venta', 2, 32, 30, 'Venta automatica', 2),
(3, 'venta', 1, 101, 100, 'Venta automatica', 1),
(4, 'venta', 2, 82, 80, 'Venta automatica', 3),
(6, 'venta', 2, 52, 50, 'Venta automatica', 3),
(9, 'venta', 1, 21, 20, 'Venta automatica', 2);

-- Actualizar estado de algunas mesas según los pedidos
UPDATE mesas SET estado = 'ocupada' WHERE id_mesa = 2;
UPDATE mesas SET estado = 'reservada' WHERE id_mesa = 5;

-- -------------------------------------------------
-- FIN DEL SCRIPT
-- -------------------------------------------------

-- Nota: Las contraseñas están hasheadas con "secret"
-- admin@comanda.com / secret
-- juan@comanda.com / secret
-- maria@comanda.com / secret
-- carlos@comanda.com / secret