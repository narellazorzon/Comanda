-- =====================================================
-- ESQUEMA COMPLETO UNIFICADO - SISTEMA COMANDA (V1 LIMPIA)
-- Sin módulo de inventario. Incluye datos de prueba básicos y recientes.
-- =====================================================

-- -------------------------------------------------
-- 1. Crear base de datos
-- -------------------------------------------------
DROP DATABASE IF EXISTS comanda;
CREATE DATABASE comanda
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE comanda;

-- -------------------------------------------------
-- 2. Tabla usuarios (administradores y mozos)
-- NOTA: Se implementa borrado lógico usando estado='eliminado'
-- y fecha_eliminacion para mantener integridad referencial
-- -------------------------------------------------
CREATE TABLE usuarios (
  id_usuario        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre            VARCHAR(50) NOT NULL,
  apellido          VARCHAR(50) NOT NULL,
  email             VARCHAR(100) NOT NULL UNIQUE,
  contrasenia       VARCHAR(255) NOT NULL,
  rol               ENUM('administrador','mozo') NOT NULL,
  estado            ENUM('activo','inactivo','eliminado') NOT NULL DEFAULT 'activo',
  fecha_creacion    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_eliminacion TIMESTAMP NULL DEFAULT NULL
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
  status         TINYINT(1) NOT NULL DEFAULT 1,
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
-- 5. Tabla pedidos (CON BORRADO LÓGICO)
-- NOTA: Se implementa borrado lógico usando deleted_at
-- para mantener integridad referencial y permitir auditoría
-- -------------------------------------------------
CREATE TABLE pedidos (
  id_pedido    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_mesa      INT UNSIGNED NULL,
  modo_consumo ENUM('stay','takeaway') NOT NULL,
  total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado       ENUM('pendiente','en_preparacion','servido','cuenta','cerrado','cancelado') NOT NULL DEFAULT 'pendiente',
  fecha_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_mozo      INT UNSIGNED NULL,
  forma_pago   ENUM('efectivo','tarjeta','transferencia') NULL,
  observaciones TEXT NULL,
  cliente_nombre VARCHAR(100) NULL,
  cliente_email  VARCHAR(100) NULL,
  deleted_at   TIMESTAMP NULL DEFAULT NULL,
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
  id_detalle      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pedido       INT UNSIGNED NOT NULL,
  id_item         INT UNSIGNED NOT NULL,
  cantidad        INT UNSIGNED NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(10,2) NOT NULL,
  detalle         TEXT NULL,
  FOREIGN KEY (id_pedido)
    REFERENCES pedidos(id_pedido)
      ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (id_item)
    REFERENCES carta(id_item)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 7. Tabla propinas (opcional; usada por flujo de pago)
-- -------------------------------------------------
CREATE TABLE propinas (
  id_propina     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pedido      INT UNSIGNED NOT NULL,
  id_mozo        INT UNSIGNED NULL,
  monto          DECIMAL(10,2) NOT NULL,
  fecha_hora     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_propinas_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_propinas_mozo
    FOREIGN KEY (id_mozo) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 8. Tabla llamados_mesa (para llamar mozo)
-- -------------------------------------------------
CREATE TABLE llamados_mesa (
  id_llamado     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_mesa        INT UNSIGNED NOT NULL,
  estado         ENUM('pendiente','atendido','completado') NOT NULL DEFAULT 'pendiente',
  hora_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  hora_atencion  DATETIME NULL,
  atendido_por   INT UNSIGNED NULL,
  FOREIGN KEY (id_mesa)
    REFERENCES mesas(id_mesa)
      ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (atendido_por)
    REFERENCES usuarios(id_usuario)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 9. Índices útiles
-- -------------------------------------------------
CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_pedidos_fecha ON pedidos(fecha_hora);
CREATE INDEX idx_pedidos_mesa ON pedidos(id_mesa);
CREATE INDEX idx_pedidos_mozo ON pedidos(id_mozo);
CREATE INDEX idx_pedidos_deleted_at ON pedidos(deleted_at);
CREATE INDEX idx_pedidos_activos ON pedidos(deleted_at, estado, fecha_hora);
CREATE INDEX idx_detalle_pedido_pedido ON detalle_pedido(id_pedido);
CREATE INDEX idx_propinas_fecha ON propinas(fecha_hora);
CREATE INDEX idx_mesas_estado ON mesas(estado);
CREATE INDEX idx_mesas_numero ON mesas(numero);
CREATE INDEX idx_mesas_mozo ON mesas(id_mozo);
CREATE INDEX idx_carta_disponibilidad ON carta(disponibilidad);
CREATE INDEX idx_carta_categoria ON carta(categoria);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_usuarios_estado ON usuarios(estado);
CREATE INDEX idx_usuarios_no_eliminados ON usuarios(estado, rol);
CREATE INDEX idx_llamados_estado ON llamados_mesa(estado);
CREATE INDEX idx_llamados_fecha ON llamados_mesa(hora_solicitud);

-- =====================================================
-- DATOS DE PRUEBA BÁSICOS
-- =====================================================

-- Usuarios (admin + mozos). Contraseñas: placeholder hash
INSERT INTO usuarios (nombre, apellido, email, contrasenia, rol, estado) VALUES
('Admin',  'Sistema',  'admin@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'administrador', 'activo'),
('Juan',   'Pérez',    'juan@comanda.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'mozo',         'activo'),
('María',  'García',   'maria@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'mozo',         'activo'),
('Carlos', 'López',    'carlos@comanda.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'mozo',         'activo'),
('Ana',    'Martínez', 'ana@comanda.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'mozo',         'activo'),
('Diego',  'Rodríguez','diego@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'mozo',         'activo');

-- Mesas de prueba con mozos asignados
INSERT INTO mesas (numero, ubicacion, estado, id_mozo) VALUES
(1,  'Zona Principal', 'libre',    2),
(2,  'Zona Principal', 'ocupada',  2),
(3,  'Terraza',        'libre',    3),
(4,  'Terraza',        'libre',    3),
(5,  'VIP',            'reservada',4),
(6,  'VIP',            'libre',    4),
(7,  'Barra',          'libre',    4),
(8,  'Barra',          'libre',    4),
(9,  'Jardín',         'libre',    5),
(10, 'Jardín',         'ocupada',  5),
(11, 'Salón VIP',      'libre',    6),
(12, 'Salón VIP',      'libre',    6),
(13, 'Auxiliar',       'libre',    NULL),
(14, 'Auxiliar',       'libre',    NULL),
(15, 'Auxiliar',       'libre',    NULL);

-- Carta de prueba
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

-- Pedidos y detalles de ejemplo
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, id_mozo, forma_pago, cliente_nombre) VALUES
(2, 'stay', 36.50, 'en_preparacion', 2, 'efectivo', 'Cliente 1'),
(5, 'stay', 54.00, 'pendiente',      4, 'tarjeta',  'Cliente 2'),
(NULL, 'takeaway', 21.00, 'servido', 3, 'efectivo', 'Cliente Takeaway 1');

INSERT INTO detalle_pedido (id_pedido, id_item, cantidad, precio_unitario) VALUES
(1, 1, 2, 15.50),
(1, 3, 1, 5.50),
(2, 2, 2, 17.00),
(2, 9, 1, 20.00),
(3, 4, 2, 6.00),
(3, 6, 2, 2.50);

-- Llamados de mesa de ejemplo
INSERT INTO llamados_mesa (id_mesa, estado) VALUES
(2, 'completado'),
(5, 'pendiente'),
(1, 'pendiente');

-- Ajustar estado mesas según pedidos
UPDATE mesas SET estado = 'ocupada' WHERE id_mesa = 2;
UPDATE mesas SET estado = 'reservada' WHERE id_mesa = 5;

-- =====================================================
-- DATOS DE PRUEBA SIMPLES (ÚLTIMOS 7 DÍAS)
-- Tomado de simple_test_data.sql e integrado al esquema
-- =====================================================

-- Limpiar datos recientes (por seguridad de import repetida)
DELETE FROM propinas WHERE fecha_hora >= CURDATE() - INTERVAL 7 DAY;
DELETE FROM detalle_pedido WHERE id_pedido IN (
    SELECT id_pedido FROM pedidos WHERE fecha_hora >= CURDATE() - INTERVAL 7 DAY
);
DELETE FROM pedidos WHERE fecha_hora >= CURDATE() - INTERVAL 7 DAY;

-- Pedidos recientes con propinas
-- Juan Pérez (ID: 2)
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1500.00, 'cerrado', NOW() - INTERVAL 2 HOUR, 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 150.00, NOW() - INTERVAL 2 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 800.00,  'cerrado', NOW() - INTERVAL 5 HOUR, 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 80.00, NOW() - INTERVAL 5 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1200.00, 'cerrado', NOW() - INTERVAL 1 DAY, 2);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 2, 180.00, NOW() - INTERVAL 1 DAY + INTERVAL 5 MINUTE);

-- María García (ID: 3)
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
(4, 'stay', 900.00,  'cerrado', NOW() - INTERVAL 1 DAY - INTERVAL 5 HOUR, 3);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 3, 180.00, NOW() - INTERVAL 1 DAY - INTERVAL 5 HOUR + INTERVAL 5 MINUTE);

-- Carlos López (ID: 4)
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 600.00,  'cerrado', NOW() - INTERVAL 4 HOUR, 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 30.00, NOW() - INTERVAL 4 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(8, 'stay', 800.00,  'cerrado', NOW() - INTERVAL 1 DAY - INTERVAL 3 HOUR, 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 40.00, NOW() - INTERVAL 1 DAY - INTERVAL 3 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(7, 'stay', 1000.00, 'cerrado', NOW() - INTERVAL 2 DAY, 4);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 4, 50.00, NOW() - INTERVAL 2 DAY + INTERVAL 5 MINUTE);

-- Ana Martínez (ID: 5)
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(1, 'stay', 1000.00, 'cerrado', NOW() - INTERVAL 6 HOUR, 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 150.00, NOW() - INTERVAL 6 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(2, 'stay', 1200.00, 'cerrado', NOW() - INTERVAL 1 DAY - INTERVAL 1 HOUR, 5);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 5, 180.00, NOW() - INTERVAL 1 DAY - INTERVAL 1 HOUR + INTERVAL 5 MINUTE);

-- Diego Rodríguez (ID: 6)
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(4, 'stay', 2500.00, 'cerrado', NOW() - INTERVAL 7 HOUR, 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 625.00, NOW() - INTERVAL 7 HOUR + INTERVAL 5 MINUTE);

INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, fecha_hora, id_mozo) VALUES
(5, 'stay', 3000.00, 'cerrado', NOW() - INTERVAL 2 DAY - INTERVAL 2 HOUR, 6);
SET @pedido_id = LAST_INSERT_ID();
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES (@pedido_id, 6, 750.00, NOW() - INTERVAL 2 DAY - INTERVAL 2 HOUR + INTERVAL 5 MINUTE);

-- -------------------------------------------------
-- FIN DEL SCRIPT (V1 sin inventario)
-- -------------------------------------------------
