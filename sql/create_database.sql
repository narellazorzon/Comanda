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
-- 5. Tabla pedidos
-- -------------------------------------------------
CREATE TABLE pedidos (
  id_pedido    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_mesa      INT UNSIGNED NULL,
  modo_consumo ENUM('stay','takeaway') NOT NULL,
  total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado       ENUM('pendiente','en_preparacion','listo','pagado') NOT NULL DEFAULT 'pendiente',
  fecha_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_mozo      INT UNSIGNED NULL,
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
-- 8. Tabla propinas (CORREGIDA para que id_mozo sea NULLABLE)
-- -------------------------------------------------
CREATE TABLE propinas (
  id_propina     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pedido      INT UNSIGNED NOT NULL,
  id_mozo        INT UNSIGNED NULL,  -- Permitir NULL para que ON DELETE SET NULL funcione
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
-- 10. Índices
-- -------------------------------------------------
CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_propinas_fecha ON propinas(fecha_hora);
CREATE INDEX idx_pagos_estado ON pagos(estado_transaccion);
