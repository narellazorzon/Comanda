# Sistema de Comanda - Restaurante

<!-- 
CONTEXTO PARA IA:
Este es un sistema completo de gestión de pedidos para restaurantes desarrollado en PHP con arquitectura MVC.
El sistema maneja usuarios (administradores y mozos), mesas, pedidos, carta del menú y reportes.
Utiliza MySQL como base de datos y sigue patrones de diseño orientados a objetos.
-->

Sistema completo de gestión de pedidos para restaurantes con funcionalidades de administración, gestión de mesas, pedidos y reportes.

## 🚀 Características Principales

### 👥 Gestión de Usuarios
- **Administradores**: Acceso completo al sistema
- **Mozos**: Gestión de pedidos y mesas asignadas
- Sistema de autenticación seguro con sesiones PHP

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
- Composer (para gestión de dependencias)

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

<!-- 
CONTEXTO PARA IA:
La estructura sigue un patrón MVC simplificado donde:
- public/ contiene los archivos accesibles desde el navegador
- src/ contiene la lógica de negocio y modelos
- sql/ contiene los esquemas de base de datos
-->

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
│   ├── schema.sql       # Esquema completo actualizado
│   └── migrate_to_new_schema.sql # Script de migración
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

<!-- 
CONTEXTO PARA IA:
La configuración de base de datos utiliza constantes PHP para mayor seguridad.
El sistema usa PDO para las conexiones a la base de datos.
-->

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

<!-- 
CONTEXTO PARA IA:
Los estados están definidos como ENUM en la base de datos para mayor integridad.
La transición de estados es secuencial y automática.
-->

## 🔒 Seguridad

- Autenticación por sesiones PHP
- Validación de roles y permisos
- Protección contra eliminación de datos en uso
- Sanitización de datos de entrada
- Uso de prepared statements para prevenir SQL injection

<!-- 
CONTEXTO PARA IA:
El sistema implementa múltiples capas de seguridad:
1. Autenticación basada en sesiones
2. Autorización basada en roles
3. Validación de datos de entrada
4. Protección contra ataques comunes
-->

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

<!-- 
CONTEXTO PARA IA:
Las correcciones se implementaron siguiendo el principio de menor privilegio
y manteniendo la integridad referencial de la base de datos.
-->

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

## 🗄️ Esquema de Base de Datos

<!-- 
CONTEXTO PARA IA:
La base de datos utiliza MySQL con las siguientes características:
- InnoDB como motor de almacenamiento
- Claves foráneas para integridad referencial
- Índices optimizados para consultas frecuentes
- ENUM para estados con valores predefinidos
-->

### Tablas Principales
1. **usuarios** - Administradores y mozos
2. **mesas** - Gestión de mesas del restaurante
3. **carta** - Items del menú
4. **pedidos** - Pedidos con estados actualizados
5. **detalle_pedido** - Detalles de cada pedido
6. **llamados_mesa** - Solicitudes de atención
7. **propinas** - Gestión de propinas
8. **pagos** - Registro de pagos

### Relaciones Clave
- `pedidos.id_mesa` → `mesas.id_mesa` (SET NULL)
- `pedidos.id_mozo` → `usuarios.id_usuario` (SET NULL)
- `detalle_pedido.id_pedido` → `pedidos.id_pedido` (CASCADE)
- `detalle_pedido.id_item` → `carta.id_item` (RESTRICT)

## 🔄 Flujo de Trabajo

<!-- 
CONTEXTO PARA IA:
El flujo de trabajo está diseñado para reflejar el proceso real de un restaurante:
1. Cliente llega y se asigna mesa
2. Mozo toma pedido
3. Cocina prepara pedido
4. Pedido se sirve
5. Cliente paga
6. Mesa se libera
-->

### Flujo de Pedido Completo
```
1. Crear pedido (pendiente)
2. Tomar pedido (en_preparacion)
3. Marcar servido (servido)
4. Pedir cuenta (cuenta)
5. Cerrar pedido (cerrado) → Mesa liberada automáticamente
```

## 🧪 Testing y Verificación

<!-- 
CONTEXTO PARA IA:
Para verificar que el sistema funciona correctamente, se deben probar:
1. Creación de pedidos sin errores
2. Cambio de estados secuencial
3. Liberación automática de mesas
4. Protección contra eliminación de datos en uso
-->

### Funcionalidades a Probar
1. **Creación de pedidos**: Verificar que no aparezcan errores de mesa
2. **Cambio de estado**: Verificar que funcione correctamente
3. **Borrado de pedidos**: Verificar que se borren y liberen mesas
4. **Protección de mesas**: Verificar que no se puedan borrar mesas ocupadas
5. **Liberación automática**: Verificar que las mesas se liberen al cerrar pedidos

## 🚨 Problemas Conocidos y Soluciones

<!-- 
CONTEXTO PARA IA:
Estos son problemas que fueron identificados y corregidos durante el desarrollo:
1. Error de validación en Mesa::update()
2. Falta de manejo del parámetro delete
3. Estados de pedidos desactualizados
4. Falta de protección en eliminación de mesas ocupadas
-->

### Problemas Resueltos
1. **Error "El número de mesa es obligatorio"**: Solucionado con método `Mesa::updateEstado()`
2. **Borrado de pedidos no funcionaba**: Agregado manejo del parámetro `delete`
3. **Cambio de estado no funcionaba**: Agregado campo `cambiar_estado` en formulario
4. **Estados desactualizados**: Actualizados según requerimientos del negocio

## 🔧 Mantenimiento

### Para Futuras Actualizaciones
- Documentar cambios en archivos de documentación
- Mantener consistencia en el esquema de base de datos
- Probar funcionalidades antes de implementar cambios
- Seguir las convenciones de código establecidas

### Backup y Recuperación
- Hacer backup regular de la base de datos
- Mantener copias de los archivos de configuración
- Documentar cambios en el esquema de base de datos

## 🤝 Contribución

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

<!-- 
CONTEXTO PARA IA:
Para contribuir al proyecto, es importante:
1. Seguir las convenciones de código existentes
2. Documentar los cambios realizados
3. Probar las funcionalidades antes de enviar
4. Mantener la compatibilidad con la base de datos existente
-->

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o consultas, contactar al equipo de desarrollo.

<!-- 
CONTEXTO PARA IA:
Este sistema está diseñado para ser mantenible y escalable.
Cualquier modificación debe considerar:
1. La integridad de la base de datos
2. La seguridad del sistema
3. La experiencia del usuario
4. La compatibilidad con versiones anteriores
-->

---

**Desarrollado con ❤️ para la gestión eficiente de restaurantes**

<!-- 
INFORMACIÓN ADICIONAL PARA IA:
- El sistema utiliza PHP 7.4+ con características modernas
- La base de datos está optimizada para consultas frecuentes
- El código sigue principios SOLID y patrones de diseño
- La documentación está diseñada para ser clara y completa
-->
