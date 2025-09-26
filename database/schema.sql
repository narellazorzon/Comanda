-- =====================================================
-- ESQUEMA COMPLETO UNIFICADO - SISTEMA COMANDA
-- Incluye todas las mejoras y datos de prueba
-- Versión: Final con asignación de mozos a mesas
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
  estado         ENUM('libre','ocupada') NOT NULL DEFAULT 'libre',
  ubicacion      VARCHAR(100) NULL,
  id_mozo        INT UNSIGNED NULL,
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
  estado       ENUM('pendiente','en_preparacion','servido','cuenta','cerrado') NOT NULL DEFAULT 'pendiente',
  fecha_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_mozo      INT UNSIGNED NULL,
  observaciones TEXT NULL,
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
  FOREIGN KEY (id_pedido) 
    REFERENCES pedidos(id_pedido)
      ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (id_item) 
    REFERENCES carta(id_item)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 7. Tabla llamados_mesa
-- -------------------------------------------------
CREATE TABLE llamados_mesa (
  id_llamado     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_mesa        INT UNSIGNED NOT NULL,
  estado         ENUM('pendiente','en_atencion','completado') NOT NULL DEFAULT 'pendiente',
  hora_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_mesa) 
    REFERENCES mesas(id_mesa)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 8. Tabla propinas
-- -------------------------------------------------
CREATE TABLE propinas (
  id_propina     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pedido      INT UNSIGNED NOT NULL,
  id_mozo        INT UNSIGNED NULL,
  monto          DECIMAL(10,2) NOT NULL,
  fecha_hora     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_pedido) 
    REFERENCES pedidos(id_pedido)
      ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (id_mozo) 
    REFERENCES usuarios(id_usuario)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 9. Tabla pagos
-- -------------------------------------------------
CREATE TABLE pagos (
  id_pago         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pedido       INT UNSIGNED NOT NULL,
  monto           DECIMAL(10,2) NOT NULL,
  fecha_hora      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  medio_pago      VARCHAR(50) NOT NULL,
  estado_transaccion ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  FOREIGN KEY (id_pedido) 
    REFERENCES pedidos(id_pedido)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------
-- 10. Índices para optimización
-- -------------------------------------------------
CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_pedidos_fecha ON pedidos(fecha_hora);
CREATE INDEX idx_pedidos_mesa ON pedidos(id_mesa);
CREATE INDEX idx_pedidos_mozo ON pedidos(id_mozo);
CREATE INDEX idx_mesas_estado ON mesas(estado);
CREATE INDEX idx_mesas_numero ON mesas(numero);
CREATE INDEX idx_mesas_mozo ON mesas(id_mozo);
CREATE INDEX idx_carta_disponibilidad ON carta(disponibilidad);
CREATE INDEX idx_carta_categoria ON carta(categoria);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_usuarios_estado ON usuarios(estado);
CREATE INDEX idx_propinas_fecha ON propinas(fecha_hora);
CREATE INDEX idx_pagos_estado ON pagos(estado_transaccion);
CREATE INDEX idx_llamados_estado ON llamados_mesa(estado);
CREATE INDEX idx_llamados_fecha ON llamados_mesa(hora_solicitud);

-- =====================================================
-- DATOS DE PRUEBA COMPLETOS
-- =====================================================

-- -------------------------------------------------
-- 11. Usuarios de prueba (admin + mozos)
-- -------------------------------------------------
INSERT INTO usuarios (nombre, apellido, email, contrasenia, rol, estado) VALUES
-- Administrador (password: admin123)
('Admin', 'Sistema', 'admin@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', 'activo'),

-- Mozos activos (password: mozo123)
('Juan', 'Pérez', 'juan.perez@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo', 'activo'),
('María', 'García', 'maria.garcia@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo', 'activo'),
('Carlos', 'López', 'carlos.lopez@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo', 'activo'),
('Ana', 'Martínez', 'ana.martinez@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo', 'activo'),
('Diego', 'Rodríguez', 'diego.rodriguez@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo', 'activo'),

-- Mozo inactivo (para pruebas)
('Luis', 'Fernández', 'luis.fernandez@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo', 'inactivo');

-- -------------------------------------------------
-- 12. Mesas de prueba CON MOZOS ASIGNADOS
-- -------------------------------------------------
INSERT INTO mesas (numero, ubicacion, estado, id_mozo) VALUES
-- Terraza (Juan Pérez - ID 2)
(1, 'Terraza - Lado Norte', 'libre', 2),
(2, 'Terraza - Lado Norte', 'ocupada', 2),
(3, 'Terraza - Lado Sur', 'libre', 2),

-- Interior (María García - ID 3)
(4, 'Interior - Ventana', 'libre', 3),
(5, 'Interior - Central', 'ocupada', 3),
(6, 'Interior - Rincón', 'libre', 3),

-- Barra (Carlos López - ID 4)
(7, 'Barra - Alta', 'libre', 4),
(8, 'Barra - Alta', 'libre', 4),

-- Jardín (Ana Martínez - ID 5)
(9, 'Jardín - Pérgola', 'libre', 5),
(10, 'Jardín - Pérgola', 'ocupada', 5),

-- VIP (Diego Rodríguez - ID 6)
(11, 'Salón VIP', 'libre', 6),
(12, 'Salón VIP', 'libre', 6),

-- Mesas sin asignar (para pruebas)
(13, 'Terraza - Auxiliar', 'libre', NULL),
(14, 'Interior - Auxiliar', 'libre', NULL),
(15, 'Patio - Auxiliar', 'libre', NULL);

-- -------------------------------------------------
-- 13. Carta de prueba completa
-- -------------------------------------------------
INSERT INTO carta (nombre, descripcion, precio, categoria, disponibilidad) VALUES
-- ENTRADAS
('Tabla de Fiambres', 'Selección de jamón crudo, salame, quesos y aceitunas', 18.50, 'Entradas', 1),
('Empanadas de Carne', 'Empanadas caseras de carne cortada a cuchillo (6 unidades)', 12.00, 'Entradas', 1),
('Empanadas de Pollo', 'Empanadas de pollo y verdura (6 unidades)', 11.50, 'Entradas', 1),
('Provoleta', 'Queso provolone a la parrilla con oregano y aceite de oliva', 8.50, 'Entradas', 1),
('Rabas', 'Anillos de calamar rebozados con salsa alioli', 15.00, 'Entradas', 1),

-- PLATOS PRINCIPALES
('Bife de Chorizo', 'Bife de chorizo de 350g a la parrilla con guarnición', 28.00, 'Carnes', 1),
('Asado de Tira', 'Asado de tira de 400g con chimichurri y papas', 25.50, 'Carnes', 1),
('Pollo Grillé', 'Pechuga de pollo grillada con hierbas y vegetales', 22.00, 'Aves', 1),
('Salmón a la Plancha', 'Filet de salmón con salsa de eneldo y arroz', 32.00, 'Pescados', 1),
('Milanesa Napolitana', 'Milanesa de ternera con jamón, queso y salsa', 24.00, 'Carnes', 1),
('Hamburguesa Clásica', 'Hamburguesa de 200g con lechuga, tomate, queso y papas', 18.50, 'Hamburguesas', 1),
('Hamburguesa Gourmet', 'Hamburguesa de 200g con cebolla caramelizada, queso azul y rúcula', 22.00, 'Hamburguesas', 1),
('Pizza Margherita', 'Salsa de tomate, mozzarella y albahaca fresca', 16.00, 'Pizzas', 1),
('Pizza Napolitana', 'Salsa de tomate, mozzarella, jamón y morrones', 19.00, 'Pizzas', 1),
('Ñoquis con Salsa', 'Ñoquis caseros con salsa bolognesa o fileto', 15.50, 'Pastas', 1),

-- ENSALADAS
('Ensalada César', 'Lechuga, crutones, parmesano y aderezo césar', 13.50, 'Ensaladas', 1),
('Ensalada Mixta', 'Lechuga, tomate, zanahoria, huevo y aceitunas', 11.00, 'Ensaladas', 1),
('Ensalada de Rúcula', 'Rúcula, tomates cherry, queso parmesano y nueces', 14.00, 'Ensaladas', 1),

-- BEBIDAS
('Coca Cola 500ml', 'Gaseosa cola', 4.50, 'Bebidas', 1),
('Sprite 500ml', 'Gaseosa lima-limón', 4.50, 'Bebidas', 1),
('Agua Mineral 500ml', 'Agua mineral sin gas', 3.50, 'Bebidas', 1),
('Agua con Gas 500ml', 'Agua mineral con gas', 3.50, 'Bebidas', 1),
('Cerveza Quilmes 1L', 'Cerveza rubia tirada', 8.00, 'Bebidas', 1),
('Vino Tinto Copa', 'Copa de vino tinto de la casa', 6.50, 'Bebidas', 1),
('Vino Blanco Copa', 'Copa de vino blanco de la casa', 6.50, 'Bebidas', 1),
('Café Espresso', 'Café espresso pequeño', 3.00, 'Bebidas', 1),
('Café con Leche', 'Café con leche en taza grande', 4.00, 'Bebidas', 1),

-- POSTRES
('Tiramisú', 'Postre italiano con café, mascarpone y cacao', 8.50, 'Postres', 1),
('Flan Casero', 'Flan de huevo con dulce de leche y crema', 6.50, 'Postres', 1),
('Helado (2 bochas)', 'Dos bochas de helado a elección', 7.00, 'Postres', 1),
('Brownie con Helado', 'Brownie de chocolate caliente con helado de vainilla', 9.50, 'Postres', 1),
('Cheesecake', 'Tarta de queso con frutos rojos', 8.00, 'Postres', 1);

-- -------------------------------------------------
-- 14. Pedidos de prueba
-- -------------------------------------------------
INSERT INTO pedidos (id_mesa, modo_consumo, total, estado, id_mozo, observaciones, fecha_hora) VALUES
-- Pedidos activos
(2, 'stay', 45.50, 'en_preparacion', 2, 'Cliente pidió el bife bien cocido', '2024-01-09 19:30:00'),
(5, 'stay', 32.00, 'pendiente', 3, NULL, '2024-01-09 20:15:00'),
(10, 'stay', 78.50, 'servido', 5, 'Mesa celebrando cumpleaños', '2024-01-09 18:45:00'),

-- Pedidos takeaway
(NULL, 'takeaway', 23.50, 'cuenta', 4, 'Pedido para retirar en 15 minutos', '2024-01-09 20:00:00'),
(NULL, 'takeaway', 12.00, 'cerrado', 2, NULL, '2024-01-09 19:00:00'),

-- Pedidos históricos
(1, 'stay', 67.00, 'cerrado', 2, NULL, '2024-01-08 21:30:00'),
(4, 'stay', 89.50, 'cerrado', 3, 'Mesa muy satisfecha', '2024-01-08 20:15:00'),
(7, 'stay', 34.50, 'cerrado', 4, NULL, '2024-01-07 19:45:00');

-- -------------------------------------------------
-- 15. Detalles de pedidos de prueba
-- -------------------------------------------------
INSERT INTO detalle_pedido (id_pedido, id_item, cantidad, precio_unitario) VALUES
-- Pedido 1 (Mesa 2): Bife + Bebidas
(1, 6, 1, 28.00),  -- Bife de Chorizo
(1, 20, 2, 4.50),  -- Coca Cola x2
(1, 26, 1, 8.50),  -- Tiramisú

-- Pedido 2 (Mesa 5): Salmón
(2, 9, 1, 32.00),  -- Salmón a la Plancha

-- Pedido 3 (Mesa 10): Cena para varios
(3, 1, 1, 18.50),  -- Tabla de Fiambres
(3, 11, 2, 18.50), -- Hamburguesa Clásica x2
(3, 21, 3, 8.00),  -- Cerveza x3
(3, 27, 2, 6.50),  -- Flan x2

-- Pedido 4 (Takeaway): Empanadas
(4, 2, 2, 12.00),  -- Empanadas de Carne x2

-- Pedido 5 (Takeaway cerrado): Empanadas
(5, 2, 1, 12.00),  -- Empanadas de Carne

-- Pedidos históricos
(6, 12, 1, 22.00), -- Hamburguesa Gourmet
(6, 13, 1, 16.00), -- Pizza Margherita
(6, 23, 2, 6.50),  -- Vino Tinto x2
(6, 30, 2, 8.00),  -- Cheesecake x2

(7, 7, 1, 25.50),  -- Asado de Tira
(7, 15, 1, 15.50), -- Ñoquis
(7, 20, 2, 4.50),  -- Coca Cola x2
(7, 28, 3, 7.00),  -- Helado x3
(7, 24, 2, 3.00),  -- Café x2

(8, 10, 1, 24.00), -- Milanesa Napolitana
(8, 21, 1, 8.00),  -- Cerveza
(8, 26, 1, 8.50);  -- Tiramisú

-- -------------------------------------------------
-- 16. Llamados de mesa de prueba
-- -------------------------------------------------
INSERT INTO llamados_mesa (id_mesa, estado, hora_solicitud) VALUES
-- Llamados pendientes
(2, 'pendiente', '2024-01-09 20:25:00'),
(5, 'pendiente', '2024-01-09 20:20:00'),
(10, 'en_atencion', '2024-01-09 20:10:00'),

-- Llamados completados
(1, 'completado', '2024-01-09 19:45:00'),
(4, 'completado', '2024-01-09 19:30:00');

-- -------------------------------------------------
-- 17. Propinas de prueba
-- -------------------------------------------------
INSERT INTO propinas (id_pedido, id_mozo, monto, fecha_hora) VALUES
(6, 2, 6.70, '2024-01-08 21:45:00'),
(7, 3, 8.95, '2024-01-08 20:30:00'),
(8, 4, 3.45, '2024-01-07 20:00:00');

-- -------------------------------------------------
-- 18. Pagos de prueba
-- -------------------------------------------------
INSERT INTO pagos (id_pedido, monto, medio_pago, estado_transaccion, fecha_hora) VALUES
(5, 12.00, 'Efectivo', 'aprobado', '2024-01-09 19:05:00'),
(6, 67.00, 'Tarjeta de Crédito', 'aprobado', '2024-01-08 21:35:00'),
(7, 89.50, 'Tarjeta de Débito', 'aprobado', '2024-01-08 20:20:00'),
(8, 34.50, 'Efectivo', 'aprobado', '2024-01-07 19:50:00');

-- =====================================================
-- COMENTARIOS SOBRE EL ESQUEMA FINAL
-- =====================================================

/*
MEJORAS IMPLEMENTADAS:

1. ASIGNACIÓN DE MOZOS A MESAS:
   - Campo id_mozo en tabla mesas
   - Relación con tabla usuarios
   - Índice para optimización

2. ESTADOS COMPLETOS DE PEDIDOS:
   - pendiente: Pedido recién creado
   - en_preparacion: Pedido siendo preparado en cocina
   - servido: Pedido servido al cliente
   - cuenta: Cliente solicita la cuenta
   - cerrado: Pedido finalizado, mesa liberada

3. DATOS DE PRUEBA COMPLETOS:
   - 1 Administrador + 6 Mozos (5 activos, 1 inactivo)
   - 15 Mesas distribuidas entre mozos y algunas sin asignar
   - Carta completa con 30 items organizados por categorías
   - 8 Pedidos de prueba en diferentes estados
   - Detalles de pedidos con items reales
   - Llamados de mesa activos y completados
   - Propinas y pagos históricos

4. FUNCIONALIDADES SOPORTADAS:
   - Gestión completa de mozos con inactivación inteligente
   - Asignación y reasignación de mesas
   - Llamados de mesa con información de mozo asignado
   - Seguimiento completo de pedidos con estados descriptivos
   - Sistema de propinas y pagos
   - Reportes con datos reales para pruebas

CREDENCIALES DE PRUEBA:
- Admin: admin@comanda.com / admin123
- Mozos: [nombre.apellido]@comanda.com / mozo123
  Ej: juan.perez@comanda.com / mozo123

ESTRUCTURA DE MESAS:
- Mesas 1-3: Juan Pérez (Terraza)
- Mesas 4-6: María García (Interior)  
- Mesas 7-8: Carlos López (Barra)
- Mesas 9-10: Ana Martínez (Jardín)
- Mesas 11-12: Diego Rodríguez (VIP)
- Mesas 13-15: Sin asignar

NOTA: Para aplicar triggers de consistencia de datos, ejecutar también:
database/triggers.sql

Esto agregará:
- Actualización automática de estado de mesas
- Prevención de eliminación de mesas ocupadas  
- Auditoría de cambios críticos
- Validaciones de integridad de datos
*/
