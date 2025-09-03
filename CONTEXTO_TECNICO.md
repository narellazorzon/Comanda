# 🔧 Contexto Técnico Completo - Sistema Comanda

## 🪑 Sistema de Mesas y Asignaciones DETALLADO

### Estados de Mesa
- **libre**: Disponible para nuevos clientes
- **ocupada**: Con clientes activos (automático al crear pedido)

### Asignación de Mozos - Funcionalidad Central
Cada mesa puede tener un mozo asignado específico. Esta es una funcionalidad CLAVE del sistema.

#### Ventajas del Sistema de Asignación:
1. **Responsabilidad clara**: Cada mesa tiene un responsable
2. **Filtrado de llamados**: Mozos solo ven SUS mesas
3. **Distribución de carga**: Mesas distribuidas equitativamente
4. **Seguimiento personalizado**: Continuidad en el servicio

#### Gestión de Emergencias (Mozo Enfermo) - FLUJO CRÍTICO:
```
Flujo de Inactivación Inteligente:
1. Admin intenta inactivar mozo con mesas asignadas
2. Sistema detecta mesas asignadas automáticamente
3. Redirige a pantalla de confirmación con opciones:
   • Reasignar todas las mesas a otro mozo activo
   • Liberar todas las mesas (sin mozo asignado)
4. Confirmación visual con lista de mesas afectadas
5. Procesamiento automático según la opción elegida
6. Feedback informativo del resultado
```

### Modelo de Datos - Mesa
```sql
CREATE TABLE mesas (
  id_mesa        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero         INT NOT NULL UNIQUE,
  estado         ENUM('libre','ocupada') NOT NULL DEFAULT 'libre',
  ubicacion      VARCHAR(100) NULL,
  id_mozo        INT UNSIGNED NULL,  -- ← CLAVE: Asignación de mozo
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mesas_mozo 
    FOREIGN KEY (id_mozo) REFERENCES usuarios(id_usuario) 
    ON UPDATE CASCADE ON DELETE SET NULL
);
```

## 📞 Sistema de Llamados de Mesa DETALLADO

### Funcionamiento Inteligente
El sistema de llamados está **integrado** con la asignación de mozos:

#### Para Mozos:
- Solo ven llamados de **SUS mesas asignadas**
- Filtrado automático por `mesa.id_mozo = usuario.id_usuario`
- Interface simplificada con información relevante

#### Para Administradores:
- Ven **TODOS** los llamados del restaurante
- Información completa incluyendo mozo asignado
- Capacidad de supervisión general

### Estados de Llamado
- **pendiente**: Cliente solicitó atención
- **en_atencion**: Mozo está atendiendo
- **completado**: Llamado resuelto

### Consulta Optimizada CRÍTICA
```sql
-- Los mozos solo ven SUS llamados
SELECT lm.*, m.numero as numero_mesa, u.nombre as mozo_nombre
FROM llamados_mesa lm
INNER JOIN mesas m ON lm.id_mesa = m.id_mesa
LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
WHERE m.id_mozo = ? AND lm.estado = 'pendiente'
ORDER BY lm.hora_solicitud DESC
```

## 🍴 Sistema de Pedidos COMPLETO

### Estados Completos del Pedido
El sistema implementa un flujo de estados que refleja la operación real de un restaurante:

```
┌─────────────┐    ┌──────────────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐
│  pendiente  │───▶│  en_preparacion  │───▶│ servido │───▶│ cuenta  │───▶│ cerrado │
└─────────────┘    └──────────────────┘    └─────────┘    └─────────┘    └─────────┘
      ↓                       ↓                  ↓           ↓              ↓
   Mesa se              Cocina está         Mozo sirvió   Cliente     Mesa liberada
  marca ocupada         preparando         el pedido     pide pagar   automáticamente
```

### Modalidades de Consumo
- **stay**: Cliente consume en mesa (requiere mesa asignada)
- **takeaway**: Para llevar (sin mesa)

### Automatizaciones Implementadas
1. **Liberación automática**: Al cerrar pedido, mesa pasa a "libre"
2. **Asignación de mozo**: Pedidos heredan el mozo de la mesa
3. **Cálculo de totales**: Automático basado en detalle_pedido
4. **Validaciones**: No se puede borrar mesa ocupada

### Modelo de Datos - Pedido
```sql
CREATE TABLE pedidos (
  id_pedido    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_mesa      INT UNSIGNED NULL,
  modo_consumo ENUM('stay','takeaway') NOT NULL,
  total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado       ENUM('pendiente','en_preparacion','servido','cuenta','cerrado') NOT NULL DEFAULT 'pendiente',
  fecha_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_mozo      INT UNSIGNED NULL,
  observaciones TEXT NULL
);
```

## 🔄 Flujos de Trabajo CRÍTICOS

### 1. Flujo Completo de Pedido
```
1. LLEGADA DEL CLIENTE
   ├─ Mesa libre seleccionada
   ├─ Mozo asignado automáticamente
   └─ Estado: libre → ocupada

2. TOMA DE PEDIDO
   ├─ Crear pedido (estado: pendiente)
   ├─ Agregar items del menú
   ├─ Calcular total automáticamente
   └─ Asignar mozo de la mesa

3. PREPARACIÓN
   ├─ Cambiar estado: pendiente → en_preparacion
   ├─ Cocina recibe orden
   └─ Tiempo estimado de preparación

4. SERVICIO
   ├─ Cambiar estado: en_preparacion → servido
   ├─ Mozo sirve al cliente
   └─ Cliente consume

5. PAGO
   ├─ Cliente solicita cuenta (estado: cuenta)
   ├─ Procesamiento de pago
   ├─ Cambiar estado: cuenta → cerrado
   └─ Mesa liberada automáticamente (ocupada → libre)
```

### 2. Flujo de Inactivación de Mozo
```
1. DETECCIÓN AUTOMÁTICA
   ├─ Admin intenta cambiar estado mozo a "inactivo"
   ├─ Sistema verifica mesas asignadas
   └─ Si tiene mesas → Redirigir a confirmación

2. PANTALLA DE CONFIRMACIÓN
   ├─ Mostrar mesas afectadas visualmente
   ├─ Opciones: Reasignar / Liberar
   ├─ Selector de nuevo mozo (si aplica)
   └─ Confirmación final con JavaScript

3. PROCESAMIENTO
   ├─ Reasignar/Liberar mesas según elección
   ├─ Cambiar estado del mozo a inactivo
   ├─ Mensaje informativo del resultado
   └─ Redirección a lista de mozos
```

### 3. Flujo de Llamado de Mesa
```
1. SOLICITUD DEL CLIENTE
   ├─ Cliente presiona botón/solicita atención
   ├─ Se crea llamado (estado: pendiente)
   └─ Timestamp automático

2. NOTIFICACIÓN INTELIGENTE
   ├─ Solo aparece al mozo asignado a esa mesa
   ├─ Admin ve todos los llamados
   └─ Información contextual (mesa, ubicación, mozo)

3. ATENCIÓN
   ├─ Mozo ve el llamado en su panel
   ├─ Cambia estado: pendiente → en_atencion
   ├─ Mozo atiende al cliente
   └─ Marca como completado
```

## 🗄️ Esquema de Base de Datos COMPLETO

### Tablas Principales y Relaciones

```sql
-- Usuarios (Base del sistema de roles)
usuarios {
  id_usuario PK
  nombre VARCHAR(50)
  apellido VARCHAR(50)
  email VARCHAR(100) UNIQUE
  contrasenia VARCHAR(255) -- Hasheada
  rol ENUM('administrador','mozo')
  estado ENUM('activo','inactivo')
}

-- Mesas (Con asignación de mozo)
mesas {
  id_mesa PK
  numero INT UNIQUE
  estado ENUM('libre','ocupada')
  ubicacion VARCHAR(100)
  id_mozo FK → usuarios.id_usuario -- CLAVE: Asignación
}

-- Pedidos (Estados completos)
pedidos {
  id_pedido PK
  id_mesa FK → mesas.id_mesa
  modo_consumo ENUM('stay','takeaway')
  total DECIMAL(10,2)
  estado ENUM('pendiente','en_preparacion','servido','cuenta','cerrado')
  fecha_hora DATETIME
  id_mozo FK → usuarios.id_usuario
}

-- Llamados (Filtrados por mozo)
llamados_mesa {
  id_llamado PK
  id_mesa FK → mesas.id_mesa -- Permite filtrar por mozo asignado
  estado ENUM('pendiente','en_atencion','completado')
  hora_solicitud DATETIME
}
```

### Consultas JOIN Críticas

```sql
-- Mesas con información de mozo asignado
SELECT m.*, u.nombre as mozo_nombre, u.apellido as mozo_apellido
FROM mesas m
LEFT JOIN usuarios u ON m.id_mozo = u.id_usuario
ORDER BY m.numero;

-- Pedidos con información completa
SELECT p.*, 
       m.numero as numero_mesa,
       u.nombre as mozo_nombre
FROM pedidos p
LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
LEFT JOIN usuarios u ON p.id_mozo = u.id_usuario
ORDER BY p.fecha_hora DESC;

-- Llamados filtrados por mozo (para mozos)
SELECT lm.*, m.numero as numero_mesa
FROM llamados_mesa lm
INNER JOIN mesas m ON lm.id_mesa = m.id_mesa
WHERE m.id_mozo = ? AND lm.estado = 'pendiente';
```

## 📊 Datos de Prueba COMPLETOS

### Usuarios Predefinidos
```
Administrador:
├─ Email: admin@comanda.com
├─ Password: admin123
└─ Permisos: Completos

Mozos Activos:
├─ juan.perez@comanda.com / mozo123
├─ maria.garcia@comanda.com / mozo123
├─ carlos.lopez@comanda.com / mozo123
├─ ana.martinez@comanda.com / mozo123
└─ diego.rodriguez@comanda.com / mozo123

Mozo Inactivo (para pruebas):
└─ luis.fernandez@comanda.com / mozo123
```

### Distribución de Mesas
```
┌─── Terraza (Juan Pérez) ───┐
│ Mesa 1, 2, 3              │
│ Ubicación: Lado Norte/Sur  │
└───────────────────────────┘

┌─── Interior (María García) ───┐
│ Mesa 4, 5, 6                 │
│ Ubicación: Ventana/Central   │
└─────────────────────────────┘

┌─── Barra (Carlos López) ───┐
│ Mesa 7, 8                  │
│ Ubicación: Barra Alta      │
└───────────────────────────┘

┌─── Jardín (Ana Martínez) ───┐
│ Mesa 9, 10                  │
│ Ubicación: Pérgola          │
└────────────────────────────┘

┌─── VIP (Diego Rodríguez) ───┐
│ Mesa 11, 12                 │
│ Ubicación: Salón VIP        │
└────────────────────────────┘

┌─── Sin Asignar ───┐
│ Mesa 13, 14, 15   │
│ Para pruebas      │
└──────────────────┘
```

## 🔒 Seguridad IMPLEMENTADA

### Autenticación y Autorización
```php
// Ejemplo de control de acceso por ruta
switch ($route) {
    case 'mesas/create':
        requireAdmin();  // Solo administradores
        break;
    case 'pedidos':
        requireMozoOrAdmin();  // Mozos y administradores
        break;
    case 'llamados':
        requireMozoOrAdmin();  // Con filtrado automático por rol
        break;
}
```

### Protección de Datos
1. **Prepared Statements**: Prevención de SQL injection
2. **Sanitización HTML**: `htmlspecialchars()` en todas las salidas
3. **Validación de entrada**: Tipos de datos y rangos
4. **Control de sesiones**: Verificación en cada request
5. **Integridad referencial**: FKs con CASCADE/SET NULL apropiados

### Validaciones de Negocio
```php
// Ejemplo: No se puede borrar mesa ocupada
if ($mesa['estado'] === 'ocupada') {
    throw new Exception('No se puede borrar una mesa ocupada');
}

// Ejemplo: Verificar permisos antes de inactivar mozo
if ($nuevo_estado === 'inactivo' && Mesa::countMesasByMozo($id) > 0) {
    // Redirigir a pantalla de reasignación
}
```

## 🎯 Casos de Uso y Testing CRÍTICOS

### Scenarios de Prueba Críticos

#### 1. Test de Asignación de Mozos
```
GIVEN: Mesa sin mozo asignado
WHEN: Admin asigna mozo a la mesa
THEN: 
  ✅ Mesa muestra el mozo asignado
  ✅ Llamados de esa mesa aparecen solo al mozo asignado
  ✅ Pedidos futuros heredan el mozo de la mesa
```

#### 2. Test de Inactivación Inteligente
```
GIVEN: Mozo activo con 3 mesas asignadas
WHEN: Admin intenta inactivar el mozo
THEN:
  ✅ Sistema detecta las mesas asignadas
  ✅ Redirige a pantalla de confirmación
  ✅ Muestra las 3 mesas visualmente
  ✅ Permite elegir nuevo mozo o liberar
  ✅ Ejecuta la acción seleccionada
  ✅ Confirma resultado con mensaje
```

#### 3. Test de Flujo Completo de Pedido
```
GIVEN: Mesa libre con mozo asignado
WHEN: Se crea pedido para esa mesa
THEN:
  ✅ Mesa pasa a estado "ocupada"
  ✅ Pedido hereda el mozo de la mesa
  ✅ Estados cambian secuencialmente
  ✅ Al cerrar pedido, mesa queda "libre"
```

#### 4. Test de Filtrado de Llamados
```
GIVEN: 2 mozos con mesas asignadas diferentes
WHEN: Se crean llamados en ambas mesas
THEN:
  ✅ Mozo 1 solo ve llamados de SUS mesas
  ✅ Mozo 2 solo ve llamados de SUS mesas
  ✅ Admin ve TODOS los llamados
```

## 🚨 Solución de Problemas COMUNES

### Errores Frecuentes y Soluciones

#### "Session already started"
```php
// Problema: session_start() duplicado
// Solución: Verificar antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

#### "Failed to open vendor/autoload.php"
```php
// Problema: Ruta incorrecta al autoloader
// Correcto desde src/views/reportes/:
require_once __DIR__ . '/../../../vendor/autoload.php';
```

#### "Cannot delete occupied table"
```php
// Problema: Intentar borrar mesa ocupada
// Solución: Verificar estado antes de borrar
if ($mesa['estado'] === 'ocupada') {
    throw new Exception('Mesa ocupada no se puede borrar');
}
```

#### "404 en reportes"
```php
// Problema: Rutas no definidas en routing
// Solución: Agregar en public/index.php
case 'reportes/nuevo-reporte':
    requireAdmin();
    include __DIR__ . '/../src/views/reportes/nuevo_reporte.php';
    break;
```

---

**ESTE ARCHIVO CONTIENE EL CONTEXTO TÉCNICO COMPLETO PARA COMPRENSIÓN TOTAL DEL SISTEMA**
