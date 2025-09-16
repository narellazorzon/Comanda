# 🍽️ Sistema Comanda - Gestión Integral de Restaurante

<!-- 
CONTEXTO COMPLETO PARA IA:
Este es un sistema completo de gestión de comandas para restaurantes desarrollado en PHP con arquitectura MVC.
El sistema está diseñado para manejar TODO el flujo operacional de un restaurante desde la llegada del cliente
hasta el pago y liberación de mesa. Incluye gestión de usuarios, mesas, pedidos, carta, reportes y funcionalidades
avanzadas como asignación inteligente de mozos y manejo de emergencias.

FUNCIONALIDADES CLAVE IMPLEMENTADAS:
- Autenticación con roles (Administrador/Mozo)
- Asignación de mozos a mesas específicas
- Inactivación inteligente de mozos con reasignación automática de mesas
- Sistema de llamados filtrado por mozo asignado
- Estados completos de pedidos con flujo secuencial
- Reportes avanzados con visualización
- Interfaz responsive con diseño consistente

ARQUITECTURA:
- Backend: PHP 8.0+ con POO y namespaces
- Base de datos: MySQL con InnoDB y relaciones FK
- Frontend: HTML5, CSS3, JavaScript vanilla
- Routing: Sistema centralizado en public/index.php
- Autoloading: Composer PSR-4
- Seguridad: Prepared statements, validación de roles, sanitización

FLUJO DE NEGOCIO:
1. Cliente llega → Se asigna mesa con mozo
2. Cliente pide → Mozo toma pedido (pendiente)
3. Cocina prepara → Estado "en_preparacion"
4. Mozo sirve → Estado "pagado"
5. Cliente pide cuenta → Estado "cerrado"
6. Cliente paga → Estado "cerrado", mesa se libera automáticamente

CASOS DE USO CRÍTICOS:
- Mozo enfermo: Sistema permite reasignación masiva de mesas a otro mozo
- Llamados de mesa: Solo aparecen para el mozo asignado a esa mesa
- Reportes: Análisis completo de ventas, rendimiento y propinas
- Estados de pedido: Flujo controlado que refleja operación real del restaurante
-->

## 📖 Descripción del Proyecto

Sistema web integral para la gestión completa de restaurantes que abarca desde la recepción de clientes hasta el análisis de ventas. Desarrollado específicamente para optimizar las operaciones diarias de un restaurante mediante la automatización de procesos clave y la centralización de información.

### 🎯 Objetivo del Sistema

Digitalizar y optimizar la gestión operacional de restaurantes mediante:
- **Automatización** del flujo de pedidos y estados
- **Asignación inteligente** de mozos a mesas
- **Seguimiento en tiempo real** del estado de mesas y pedidos
- **Análisis de rendimiento** a través de reportes detallados
- **Gestión de emergencias** (mozos enfermos, cambios de turno)

## 🏗️ Arquitectura Técnica

### Stack Tecnológico
```
┌─────────────────────────────────────────┐
│                Frontend                 │
│  HTML5 + CSS3 + JavaScript Vanilla     │
│  Responsive Design + Bootstrap Icons    │
└─────────────────────────────────────────┘
                     │
┌─────────────────────────────────────────┐
│              Backend PHP                │
│  • Arquitectura MVC Personalizada      │
│  • PHP 8.0+ con POO y Namespaces      │
│  • Composer PSR-4 Autoloading         │
│  • Sistema de Routing Centralizado     │
└─────────────────────────────────────────┘
                     │
┌─────────────────────────────────────────┐
│            Base de Datos               │
│  • MySQL 8.0+ con InnoDB              │
│  • Relaciones FK con integridad       │
│  • Índices optimizados                │
│  • Triggers para automatización       │
└─────────────────────────────────────────┘
```

### Estructura de Directorios
```
Comanda/
├── public/                    # Punto de entrada web
│   ├── index.php             # Router principal con manejo de rutas
│   ├── assets/css/           # Estilos del sistema
│   └── assets/img/           # Imágenes y logos
├── src/
│   ├── config/
│   │   ├── database.php      # Configuración de BD
│   │   └── helpers.php       # Funciones auxiliares (url(), etc.)
│   ├── controllers/          # Controladores MVC
│   │   ├── AuthController.php
│   │   ├── MesaController.php
│   │   ├── MozoController.php # Incluye gestión de inactivación
│   │   ├── PedidoController.php
│   │   └── ReporteController.php
│   ├── models/               # Modelos de datos
│   │   ├── Usuario.php       # Administradores y mozos
│   │   ├── Mesa.php          # Con asignación de mozos
│   │   ├── Pedido.php        # Estados completos
│   │   ├── LlamadoMesa.php   # Filtrado por mozo
│   │   └── Reporte.php       # Análisis de datos
│   ├── views/                # Vistas HTML/PHP
│   │   ├── auth/            # Login y logout
│   │   ├── home/            # Dashboard principal
│   │   ├── mesas/           # ABM + asignación de mozos
│   │   ├── mozos/           # ABM + inactivación inteligente
│   │   ├── pedidos/         # Gestión con estados
│   │   ├── llamados/        # Filtrado por mozo asignado
│   │   ├── reportes/        # Análisis y estadísticas
│   │   └── includes/        # Header, footer, nav
│   └── services/            # Lógica de negocio compleja
├── database/
│   └── schema.sql           # Schema completo con datos de prueba
├── vendor/                  # Dependencias de Composer
└── Artefactos/             # Documentación del proyecto
```

## 👥 Sistema de Usuarios y Roles

### Roles Implementados

#### 🔧 Administrador
**Permisos Completos:**
- ✅ Gestión del personal (crear, editar, inactivar con reasignación)
- ✅ Gestión de mesas (crear, editar, asignar mozos)
- ✅ Gestión de carta (productos del menú)
- ✅ Gestión de pedidos (todos los estados)
- ✅ Visualización de todos los llamados de mesa
- ✅ Acceso completo a reportes y estadísticas
- ✅ Funciones de emergencia (reasignación masiva de mesas)

#### 👨‍💼 Mozo
**Permisos Operacionales:**
- ✅ Gestión de pedidos (crear, cambiar estado)
- ✅ Visualización solo de llamados de SUS mesas asignadas
- ✅ Consulta de mesas (solo lectura)
- ✅ Consulta de carta
- ❌ No puede gestionar otros mozos
- ❌ No puede acceder a reportes administrativos

### Flujo de Autenticación
```php
// Ejemplo de verificación de permisos
function requireAdmin() {
    if ($_SESSION['user']['rol'] !== 'administrador') {
        header('Location: index.php?route=unauthorized');
        exit;
    }
}

function requireMozoOrAdmin() {
    if (!in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
        header('Location: index.php?route=unauthorized');
        exit;
    }
}
```

## 🚀 Instalación y Configuración

### Requisitos del Sistema
- **XAMPP** (Apache + MySQL + PHP 8.0+)
- **Composer** para dependencias

### Pasos de Instalación
1. **Clonar repositorio**
   ```bash
   git clone [url-del-repo]
   cd Comanda
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   ```

3. **Configurar base de datos**
   - Crear base de datos en phpMyAdmin
   - Importar `database/schema.sql`
   - Configurar conexión en `src/config/database.php`

4. **Configurar servidor web**
   - Colocar proyecto en `htdocs` de XAMPP
   - Acceder via `http://localhost/Comanda/public/`

## 🔑 Credenciales de Prueba

### Administrador
- **Email**: admin@comanda.com
- **Contraseña**: admin123

### Personal
- **Juan Pérez**: juan.perez@comanda.com / mozo123
- **María García**: maria.garcia@comanda.com / mozo123
- **Carlos López**: carlos.lopez@comanda.com / mozo123
- **Ana Martínez**: ana.martinez@comanda.com / mozo123
- **Diego Rodríguez**: diego.rodriguez@comanda.com / mozo123

## 📊 Funcionalidades Principales

### 🪑 Gestión de Mesas con Asignación de Mozos
- **Asignación inteligente**: Cada mesa tiene un mozo responsable
- **Gestión de emergencias**: Reasignación automática cuando un mozo se enferma
- **Estados automatizados**: Libre/Ocupada según pedidos activos

### 📞 Sistema de Llamados Filtrados
- **Filtrado por mozo**: Cada mozo solo ve llamados de SUS mesas
- **Información completa**: Mesa, ubicación, mozo asignado
- **Estados de atención**: Pendiente, En atención, Completado

### 🍴 Gestión Completa de Pedidos
- **Estados del flujo real**: Pendiente → En preparación → Pagado → Cerrado
- **Modalidades**: Stay (mesa) / Takeaway (para llevar)
- **Automatizaciones**: Liberación de mesa al cerrar pedido

### 👥 Gestión Avanzada del Personal
- **Inactivación inteligente**: Si un mozo tiene mesas asignadas, sistema solicita reasignación
- **Opciones de emergencia**: Transferir a otro mozo o liberar mesas
- **Confirmación visual**: Muestra impacto antes de procesar

### 📊 Reportes Analíticos
- **Platos más vendidos** con filtros por período
- **Ventas por categoría** con visualización
- **Rendimiento de mozos** con métricas
- **Propinas y recaudación** por período

## 🗄️ Base de Datos

### Distribución de Mesas de Prueba
- **Mesas 1-3**: Juan Pérez (Terraza)
- **Mesas 4-6**: María García (Interior)
- **Mesas 7-8**: Carlos López (Barra)
- **Mesas 9-10**: Ana Martínez (Jardín)
- **Mesas 11-12**: Diego Rodríguez (VIP)
- **Mesas 13-15**: Sin asignar

### Datos Incluidos
- **15 mesas** distribuidas entre 5 mozos
- **30 items** de carta organizados por categorías
- **8 pedidos** en diferentes estados
- **Llamados activos** y completados
- **Propinas y pagos** históricos

## 🔧 Tecnologías

- **Backend**: PHP 8.0+ con POO
- **Base de datos**: MySQL con InnoDB
- **Frontend**: HTML5, CSS3, JavaScript vanilla
- **Arquitectura**: MVC personalizado
- **Autoloading**: Composer PSR-4
- **Seguridad**: Prepared statements, validación de roles

## 🎯 Casos de Uso Principales

1. **Administrador gestiona mozos**: Crear, editar, inactivar con reasignación inteligente
2. **Mozo ve sus llamados**: Solo mesas asignadas, información completa
3. **Gestión de pedidos**: Desde creación hasta cierre con todos los estados
4. **Emergencia de mozo**: Sistema maneja reasignación automática de mesas

## 🔄 Flujo de Trabajo

1. **Cliente llega** → Se asigna mesa con mozo
2. **Cliente pide** → Mozo toma pedido
3. **Cocina prepara** → Estado "En preparación"
4. **Mozo sirve** → Estado "Servido"
5. **Cliente paga** → Estado "Cerrado", mesa libre

## ✨ Mejoras Implementadas

### 🔧 Correcciones de Bugs
- ✅ Vista de pedidos corregida (sin errores de campos undefined)
- ✅ Estados de pedidos con iconos y colores descriptivos
- ✅ Redirecciones 404 en gestión del personal solucionadas
- ✅ Diseño consistente de botones y tablas
- ✅ Sistema de reportes completamente funcional

### 🚀 Nuevas Funcionalidades
- ✅ **Asignación de mozos a mesas** con gestión completa
- ✅ **Inactivación inteligente de mozos** con reasignación de mesas
- ✅ **Llamados filtrados por mozo** asignado
- ✅ **Gestión de emergencias** (mozo enfermo, cambio de turno)

### 🎨 Mejoras de UX/UI
- ✅ Estados visuales con iconos (⏳ Pendiente, 👨‍🍳 En preparación, ✅ Servido, etc.)
- ✅ Confirmaciones visuales para acciones críticas
- ✅ Información contextual en todas las pantallas
- ✅ Navegación mejorada con breadcrumbs visuales

## 📚 Documentación Técnica

### Para Desarrolladores
- **`CONTEXTO_TECNICO.md`** - Documentación técnica completa con flujos, casos de uso y ejemplos de código
- **`database/schema.sql`** - Schema completo con comentarios y datos de prueba
- **`/Artefactos/`** - Diagramas de actividad y casos de uso del proyecto

### Arquitectura y Patrones
- Sistema MVC con routing centralizado
- Separación clara de responsabilidades
- Validaciones de negocio en modelos
- Seguridad por capas
- Optimizaciones de base de datos

### Testing y Debugging
Ver `CONTEXTO_TECNICO.md` para:
- Casos de prueba críticos
- Flujos de trabajo detallados
- Solución de problemas comunes
- Consultas SQL optimizadas

## 📝 Notas de Desarrollo

- **Seguridad**: Prepared statements, validación de inputs, control de sesiones
- **Performance**: Índices optimizados, consultas eficientes con JOINs
- **Mantenibilidad**: Código modular, separación de responsabilidades
- **UX**: Confirmaciones, mensajes informativos, navegación intuitiva

---

**Desarrollado para gestión eficiente de restaurantes** 🍴

**📖 Para información técnica detallada, consultar `CONTEXTO_TECNICO.md`**