-- =====================================================
-- ESQUEMA COMPLETO DE LA BASE DE DATOS - SISTEMA COMANDA
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
-- 2. Tabla usuarios
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
-- 3. Tabla mesas
-- -------------------------------------------------
CREATE TABLE mesas (
  id_mesa        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero         INT NOT NULL UNIQUE,
  estado         ENUM('libre','ocupada') NOT NULL DEFAULT 'libre',
  ubicacion      VARCHAR(100) NULL,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
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
CREATE INDEX idx_carta_disponibilidad ON carta(disponibilidad);
CREATE INDEX idx_carta_categoria ON carta(categoria);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_usuarios_estado ON usuarios(estado);
CREATE INDEX idx_propinas_fecha ON propinas(fecha_hora);
CREATE INDEX idx_pagos_estado ON pagos(estado_transaccion);
CREATE INDEX idx_llamados_estado ON llamados_mesa(estado);
CREATE INDEX idx_llamados_fecha ON llamados_mesa(hora_solicitud);

-- -------------------------------------------------
-- 11. Datos de ejemplo (opcional)
-- -------------------------------------------------

-- Insertar usuarios de ejemplo
INSERT INTO usuarios (nombre, apellido, email, contrasenia, rol) VALUES
('Admin', 'Sistema', 'admin@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador'),
('Juan', 'Pérez', 'juan@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo'),
('María', 'García', 'maria@comanda.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mozo');

-- Insertar mesas de ejemplo
INSERT INTO mesas (numero, ubicacion) VALUES
(1, 'Terraza'),
(2, 'Terraza'),
(3, 'Interior'),
(4, 'Interior'),
(5, 'Barra');

-- Insertar items de carta de ejemplo
INSERT INTO carta (nombre, descripcion, precio, categoria, disponibilidad) VALUES
('Hamburguesa Clásica', 'Hamburguesa con carne, lechuga, tomate y queso', 12.50, 'Platos Principales', 1),
('Pizza Margherita', 'Pizza con tomate, mozzarella y albahaca', 15.00, 'Platos Principales', 1),
('Ensalada César', 'Lechuga, crutones, parmesano y aderezo César', 8.50, 'Entradas', 1),
('Coca Cola', 'Bebida gaseosa 500ml', 3.00, 'Bebidas', 1),
('Agua Mineral', 'Agua mineral sin gas 500ml', 2.50, 'Bebidas', 1),
('Tiramisú', 'Postre italiano con café y mascarpone', 6.00, 'Postres', 1);

-- -------------------------------------------------
-- 12. Comentarios sobre el esquema
-- -------------------------------------------------

/*
ESTADOS DE PEDIDOS:
- pendiente: Pedido recién creado
- en_preparacion: Pedido siendo preparado en cocina
- servido: Pedido servido al cliente
- cuenta: Cliente solicita la cuenta
- cerrado: Pedido finalizado, mesa liberada automáticamente

ESTADOS DE MESAS:
- libre: Mesa disponible para nuevos clientes
- ocupada: Mesa con clientes activos

ESTADOS DE USUARIOS:
- activo: Usuario puede acceder al sistema
- inactivo: Usuario suspendido

ESTADOS DE LLAMADOS:
- pendiente: Cliente solicitó atención
- en_atencion: Mozo atendiéndolo
- completado: Llamado resuelto

ESTADOS DE TRANSACCIONES:
- pendiente: Pago en proceso
- aprobado: Pago exitoso
- rechazado: Pago fallido
*/
