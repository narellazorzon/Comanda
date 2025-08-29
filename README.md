# Sistema de Comanda - Restaurante

Sistema completo de gestión de pedidos para restaurantes con funcionalidades de administración, gestión de mesas, pedidos y reportes.

## 🚀 Características Principales

### 👥 Gestión de Usuarios
- **Administradores**: Acceso completo al sistema
- **Mozos**: Gestión de pedidos y mesas asignadas
- Sistema de autenticación seguro

### 🪑 Gestión de Mesas
- Crear, editar y eliminar mesas
- Estados: Libre/Ocupada
- Protección contra eliminación de mesas ocupadas
- Liberación automática al cerrar pedidos

### 🍽️ Gestión de Pedidos
- **Estados actualizados**: pendiente → en_preparacion → servido → cuenta → cerrado
- Creación de pedidos en mesa o para llevar
- Gestión de detalles de pedido
- Cálculo automático de totales
- Liberación automática de mesas al cerrar pedidos

### 📋 Gestión de Carta
- Items del menú con categorías
- Control de disponibilidad
- Precios y descripciones

### 📊 Reportes
- Ventas por período
- Platos más vendidos
- Rendimiento de mozos
- Recaudación mensual
- Propinas

## 🛠️ Instalación

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone [url-del-repositorio]
   cd Comanda
   ```

2. **Configurar la base de datos**
   - Crear una base de datos MySQL
   - Ejecutar el archivo `sql/schema.sql` para crear todas las tablas
   - Configurar las credenciales en `src/config/database.php`

3. **Instalar dependencias**
   ```bash
   composer install
   ```

4. **Configurar el servidor web**
   - Apuntar el document root a la carpeta `public/`
   - Asegurar que PHP tenga permisos de escritura

5. **Acceder al sistema**
   - URL: `http://localhost/Comanda/public/`
   - Usuario por defecto: `admin@comanda.com`
   - Contraseña: `password`

## 📁 Estructura del Proyecto

```
Comanda/
├── public/                 # Archivos públicos (document root)
│   ├── assets/            # CSS, JS, imágenes
│   ├── includes/          # Header, footer, navegación
│   ├── reportes/          # Módulo de reportes
│   └── *.php             # Archivos principales
├── src/                   # Código fuente
│   ├── config/           # Configuración de base de datos
│   ├── controllers/      # Controladores
│   ├── models/          # Modelos de datos
│   └── services/        # Servicios
├── sql/                  # Esquemas de base de datos
│   └── schema.sql       # Esquema completo actualizado
└── vendor/              # Dependencias de Composer
```

## 🔧 Configuración

### Base de Datos
Editar `src/config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'comanda');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

## 📊 Estados del Sistema

### Estados de Pedidos
- **pendiente**: Pedido recién creado
- **en_preparacion**: Pedido siendo preparado
- **servido**: Pedido servido al cliente
- **cuenta**: Cliente solicita la cuenta
- **cerrado**: Pedido finalizado, mesa liberada

### Estados de Mesas
- **libre**: Mesa disponible
- **ocupada**: Mesa con clientes activos

## 🔒 Seguridad

- Autenticación por sesiones
- Validación de roles y permisos
- Protección contra eliminación de datos en uso
- Sanitización de datos de entrada

## 🐛 Correcciones Implementadas

### ✅ ABM de Pedidos
- Corregido borrado de pedidos
- Solucionado error "El número de mesa es obligatorio"
- Mejorados mensajes de usuario

### ✅ Estados de Pedidos
- Actualizados estados según requerimientos del negocio
- Implementada liberación automática de mesas
- Corregido cambio de estado en gestión de pedidos

### ✅ Protección de Datos
- No se pueden borrar mesas ocupadas
- Botones deshabilitados para operaciones no permitidas
- Validaciones de integridad referencial

## 📝 Uso del Sistema

### Para Administradores
1. **Gestión de Usuarios**: Crear y gestionar mozos
2. **Gestión de Mesas**: Configurar mesas del restaurante
3. **Gestión de Carta**: Mantener el menú actualizado
4. **Reportes**: Analizar ventas y rendimiento

### Para Mozos
1. **Gestión de Pedidos**: Crear y gestionar pedidos
2. **Estado de Pedidos**: Seguir el flujo de preparación
3. **Llamados de Mesa**: Atender solicitudes de clientes

## 🤝 Contribución

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o consultas, contactar al equipo de desarrollo.

---

**Desarrollado con ❤️ para la gestión eficiente de restaurantes**
