-- Crear tabla para notificaciones de mozos
CREATE TABLE IF NOT EXISTS notificaciones_mozos (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_mozo INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    mensaje TEXT NOT NULL,
    data JSON DEFAULT NULL,
    leida BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_mozo) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,

    INDEX idx_id_mozo (id_mozo),
    INDEX idx_leida (leida),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;