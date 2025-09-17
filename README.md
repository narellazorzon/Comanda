# Sistema de Gestión de Comandas v2.0

## 📋 Descripción General

Sistema integral de gestión gastronómica desarrollado en PHP que permite la administración completa de un restaurante, desde la toma de pedidos hasta el procesamiento de pagos con propinas. Incluye interfaces diferenciadas para clientes, mozos y administradores.

## 🚀 Características Principales

### Para Clientes
- **Menú Digital Interactivo**: Acceso vía QR o directo con categorización de productos
- **Carrito de Compras**: Sistema de selección múltiple con cantidades personalizables
- **Sistema de Propinas**: Opciones predefinidas (10%, 15%, 20%) o monto personalizado
- **Múltiples Métodos de Pago**: Efectivo y tarjeta
- **Confirmación en Tiempo Real**: Notificaciones visuales del estado del pedido

### Para Personal (Mozos)
- **Gestión de Pedidos**: Creación, edición y seguimiento de pedidos
- **Sistema de Llamados**: Atención de solicitudes de clientes por mesa
- **Cambio de Estados**: Actualización del progreso de pedidos
- **Asignación de Mesas**: Sistema dinámico de distribución de mesas

### Para Administradores
- **Dashboard Completo**: Métricas y estadísticas en tiempo real
- **Gestión de Personal**: ABM de usuarios con roles diferenciados
- **Control de Inventario**: Sistema completo de stock con alertas
- **Reportes Avanzados**:
  - Rendimiento de mozos con propinas
  - Productos más vendidos
  - Recaudación mensual
  - Ventas por categoría
- **Administración de Carta**: Gestión completa de productos y precios

## 🛠️ Stack Tecnológico

### Backend
- **PHP 7.4+**: Lenguaje principal con paradigma MVC
- **MySQL 5.7+**: Base de datos relacional
- **PDO**: Capa de abstracción de base de datos
- **Composer**: Gestión de dependencias

### Frontend
- **HTML5/CSS3**: Estructura y estilos responsivos
- **JavaScript ES6+**: Interactividad y validaciones
- **Chart.js**: Visualización de datos y gráficos
- **Bootstrap Icons**: Iconografía consistente

### Arquitectura
- **Patrón MVC**: Separación clara de responsabilidades
- **PSR-4 Autoloading**: Carga automática de clases
- **RESTful Routes**: Enrutamiento semántico
- **Prepared Statements**: Seguridad contra SQL Injection

## 📁 Estructura del Proyecto

```
comanda/
├── database/
│   └── comanda_v2.sql          # Script completo de BD con datos de prueba
├── public/
│   ├── index.php                # Punto de entrada principal
│   ├── assets/
│   │   ├── css/                # Estilos personalizados
│   │   ├── js/                 # Scripts del cliente
│   │   └── images/             # Recursos visuales
│   └── .htaccess               # Configuración Apache
├── src/
│   ├── config/
│   │   ├── database.php       # Configuración de BD
│   │   ├── helpers.php        # Funciones auxiliares
│   │   └── SessionManager.php # Gestión de sesiones
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── ClienteController.php
│   │   ├── MesaController.php
│   │   ├── MozoController.php
│   │   ├── PedidoController.php
│   │   └── ReporteController.php
│   ├── models/
│   │   ├── BaseModel.php      # Modelo base con operaciones CRUD
│   │   ├── Usuario.php
│   │   ├── Mesa.php
│   │   ├── Pedido.php
│   │   ├── Propina.php
│   │   ├── CartaItem.php
│   │   └── DetallePedido.php
│   ├── services/
│   │   └── ReporteService.php # Lógica de negocio para reportes
│   ├── database/
│   │   └── QueryBuilder.php   # Constructor de consultas SQL
│   └── views/
│       ├── cliente/            # Vistas públicas
│       ├── pedidos/            # Gestión de pedidos
│       ├── mesas/              # Administración de mesas
│       ├── mozos/              # Gestión de personal
│       ├── reportes/           # Dashboards y estadísticas
│       └── includes/           # Componentes reutilizables
├── vendor/                     # Dependencias de Composer
├── composer.json              # Configuración de Composer
├── .gitignore                 # Archivos ignorados por Git
├── .htaccess                  # Configuración raíz Apache
└── README.md                  # Este archivo
```

## 🔧 Instalación

### Requisitos Previos
- PHP >= 7.4
- MySQL >= 5.7
- Apache con mod_rewrite habilitado
- Composer
- Extensiones PHP: pdo, pdo_mysql, json, session

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/tu-usuario/comanda.git
cd comanda
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar la base de datos**
```bash
mysql -u root -p < database/comanda_v2.sql
```

4. **Configurar conexión a BD**

Editar `src/config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'comanda';
private $username = 'root';
private $password = '';
```

5. **Configurar Apache**

Asegurar que el DocumentRoot apunte a `/ruta/comanda/public`

6. **Permisos de directorio**
```bash
chmod 755 -R /ruta/comanda
chmod 777 -R /ruta/comanda/logs
```

## 🔐 Acceso al Sistema

### Credenciales de Prueba

**Administrador:**
- Email: `admin@comanda.com`
- Contraseña: `admin123`

**Mozo:**
- Email: `juan@comanda.com`
- Contraseña: `mozo123`

### URLs de Acceso

- **Cliente/Público**: `http://localhost/comanda/public/index.php?route=cliente`
- **Login Personal**: `http://localhost/comanda/public/index.php?route=login`
- **Dashboard Admin**: `http://localhost/comanda/public/index.php?route=home`

## 📱 Flujo de Usuario Cliente

1. **Acceso al Menú**
   - Escaneo de código QR de mesa
   - Acceso directo vía URL

2. **Selección de Productos**
   - Navegación por categorías
   - Agregar al carrito con cantidades

3. **Confirmación de Pedido**
   - Ingreso de datos personales
   - Selección de modo de consumo

4. **Proceso de Pago**
   - Selección de propina
   - Método de pago
   - Confirmación final

5. **Post-Pago**
   - Vista de confirmación con detalles
   - Opción de nuevo pedido
   - Redirección automática al menú

## 🔄 Flujo de Trabajo del Personal

### Mozo
1. Login al sistema
2. Visualización de mesas asignadas
3. Toma y gestión de pedidos
4. Actualización de estados
5. Atención de llamados

### Administrador
1. Login al sistema
2. Dashboard con métricas
3. Gestión integral:
   - Personal
   - Mesas
   - Carta
   - Inventario
4. Generación de reportes
5. Configuración del sistema

## 📊 Base de Datos

### Tablas Principales
- `usuarios`: Personal del sistema
- `mesas`: Configuración de mesas
- `carta`: Productos disponibles
- `pedidos`: Órdenes de clientes
- `detalle_pedido`: Items por pedido
- `propinas`: Sistema de gratificaciones
- `inventario`: Control de stock
- `llamados_mesa`: Solicitudes de atención

### Relaciones Clave
- Mesa → Mozo (N:1)
- Pedido → Mesa (N:1)
- Pedido → Detalles (1:N)
- Pedido → Propina (1:1)
- Usuario → Pedidos (1:N)

## 🔒 Seguridad

- **Autenticación**: Sistema de sesiones PHP
- **Autorización**: Roles diferenciados (admin/mozo)
- **SQL Injection**: Prepared Statements en todas las consultas
- **XSS**: Escapado de outputs con `htmlspecialchars()`
- **CSRF**: Tokens en formularios críticos
- **Passwords**: Hashing con bcrypt

## 🚀 Despliegue en Producción

### Recomendaciones
1. Cambiar credenciales por defecto
2. Configurar HTTPS con certificado SSL
3. Habilitar logs de errores en archivo
4. Configurar backups automáticos de BD
5. Implementar CDN para assets estáticos
6. Configurar límites de rate limiting

### Variables de Entorno
```env
DB_HOST=localhost
DB_NAME=comanda
DB_USER=usuario_produccion
DB_PASS=contraseña_segura
APP_ENV=production
APP_DEBUG=false
```

## 📈 Métricas y Reportes

El sistema incluye reportes automatizados de:
- **Rendimiento de Personal**: Pedidos atendidos, propinas recibidas
- **Análisis de Ventas**: Por período, categoría y producto
- **Control de Inventario**: Stock actual, productos bajo mínimo
- **Estadísticas Financieras**: Recaudación diaria/mensual

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama de feature (`git checkout -b feature/NuevaCaracteristica`)
3. Commit cambios (`git commit -m 'Agregar nueva característica'`)
4. Push a la rama (`git push origin feature/NuevaCaracteristica`)
5. Abrir Pull Request

## 📄 Licencia

Este proyecto está bajo Licencia MIT. Ver archivo `LICENSE` para más detalles.

## 👥 Equipo de Desarrollo

- **Arquitectura y Backend**: Sistema MVC con PHP nativo
- **Frontend y UX**: Interfaces responsivas y accesibles
- **Base de Datos**: Diseño relacional normalizado

## 📞 Soporte

Para reportar bugs o solicitar features:
- Abrir un issue en GitHub
- Contactar al equipo de desarrollo

---

**Versión**: 2.0.0
**Última Actualización**: Septiembre 2025
**Estado**: Producción Ready