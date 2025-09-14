-- =====================================================
-- CONSULTAS PARA DEBUG DEL MÓDULO RENDIMIENTO DE MOZOS
-- =====================================================

USE comanda;

-- 1. Verificar que los pedidos se insertaron correctamente
SELECT 'PEDIDOS INSERTADOS' as debug_step;
SELECT 
    id_pedido,
    id_mozo,
    fecha_hora,
    total,
    estado
FROM pedidos 
WHERE fecha_hora BETWEEN '2024-09-01' AND '2024-09-30'
ORDER BY fecha_hora;

-- 2. Verificar que las propinas se insertaron correctamente
SELECT 'PROPINAS INSERTADAS' as debug_step;
SELECT 
    pr.id_propina,
    pr.id_pedido,
    pr.id_mozo,
    pr.monto,
    pr.fecha_hora,
    pe.total as total_pedido
FROM propinas pr
JOIN pedidos pe ON pr.id_pedido = pe.id_pedido
WHERE pr.fecha_hora BETWEEN '2024-09-01' AND '2024-09-30'
ORDER BY pr.fecha_hora;

-- 3. Verificar mozos activos
SELECT 'MOZOS ACTIVOS' as debug_step;
SELECT 
    id_usuario,
    nombre,
    apellido,
    rol,
    estado
FROM usuarios 
WHERE rol = 'mozo';

-- 4. Contar pedidos por mozo en septiembre
SELECT 'PEDIDOS POR MOZO SEPTIEMBRE' as debug_step;
SELECT 
    u.id_usuario,
    u.nombre,
    u.apellido,
    COUNT(p.id_pedido) as total_pedidos,
    SUM(p.total) as total_vendido
FROM usuarios u
LEFT JOIN pedidos p ON u.id_usuario = p.id_mozo 
    AND p.fecha_hora BETWEEN '2024-09-01 00:00:00' AND '2024-09-30 23:59:59'
WHERE u.rol = 'mozo' AND u.estado = 'activo'
GROUP BY u.id_usuario, u.nombre, u.apellido
ORDER BY total_pedidos DESC;

-- 5. Probar la consulta exacta del servicio
SELECT 'CONSULTA DEL SERVICIO (sin fechas específicas)' as debug_step;
SELECT 
    u.id_usuario AS mozo_id,
    CONCAT(u.nombre, ' ', u.apellido) AS mozo,
    COUNT(DISTINCT pe.id_pedido) AS pedidos,
    COALESCE(SUM(pr.monto), 0) AS propina_total,
    COALESCE(SUM(pe.total), 0) AS total_vendido,
    ROUND(COALESCE(SUM(pr.monto), 0) / NULLIF(COUNT(DISTINCT pe.id_pedido), 0), 2) AS propina_promedio_por_pedido,
    ROUND(COALESCE(SUM(pr.monto), 0) / NULLIF(COALESCE(SUM(pe.total), 0), 0), 4) AS tasa_propina
FROM usuarios u
LEFT JOIN pedidos pe ON u.id_usuario = pe.id_mozo 
    AND pe.estado IN ('servido', 'cuenta', 'cerrado')
LEFT JOIN propinas pr ON pr.id_pedido = pe.id_pedido
WHERE u.rol = 'mozo' AND u.estado = 'activo'
GROUP BY u.id_usuario, mozo
ORDER BY tasa_propina DESC, propina_total DESC;

-- 6. Probar con fechas específicas de septiembre
SELECT 'CONSULTA DEL SERVICIO (septiembre 2024)' as debug_step;
SELECT 
    u.id_usuario AS mozo_id,
    CONCAT(u.nombre, ' ', u.apellido) AS mozo,
    COUNT(DISTINCT pe.id_pedido) AS pedidos,
    COALESCE(SUM(pr.monto), 0) AS propina_total,
    COALESCE(SUM(pe.total), 0) AS total_vendido,
    ROUND(COALESCE(SUM(pr.monto), 0) / NULLIF(COUNT(DISTINCT pe.id_pedido), 0), 2) AS propina_promedio_por_pedido,
    ROUND(COALESCE(SUM(pr.monto), 0) / NULLIF(COALESCE(SUM(pe.total), 0), 0), 4) AS tasa_propina
FROM usuarios u
LEFT JOIN pedidos pe ON u.id_usuario = pe.id_mozo 
    AND pe.fecha_hora BETWEEN '2024-09-01 00:00:00' AND '2024-09-30 23:59:59'
    AND pe.estado IN ('servido', 'cuenta', 'cerrado')
LEFT JOIN propinas pr ON pr.id_pedido = pe.id_pedido
WHERE u.rol = 'mozo' AND u.estado = 'activo'
GROUP BY u.id_usuario, mozo
ORDER BY tasa_propina DESC, propina_total DESC;

-- 7. Verificar los estados de pedidos que tenemos
SELECT 'ESTADOS DE PEDIDOS' as debug_step;
SELECT 
    estado,
    COUNT(*) as cantidad
FROM pedidos
WHERE fecha_hora BETWEEN '2024-09-01' AND '2024-09-30'
GROUP BY estado;

-- 8. Verificar si hay problemas con los filtros de fechas del formulario
SELECT 'FECHAS EXACTAS EN PEDIDOS' as debug_step;
SELECT 
    DATE(fecha_hora) as fecha,
    COUNT(*) as pedidos_por_dia
FROM pedidos
WHERE fecha_hora BETWEEN '2024-09-01' AND '2024-09-30'
GROUP BY DATE(fecha_hora)
ORDER BY fecha;