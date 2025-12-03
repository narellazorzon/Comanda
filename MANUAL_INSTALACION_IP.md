# 📖 Manual de Instalación - Configuración de IP para Acceso Remoto

Este manual te guiará paso a paso para configurar tu sistema Comanda y poder acceder desde dispositivos que no sean tu computadora localhost.

---

## 📋 Tabla de Contenidos

1. [Obtener tu IP Local](#1-obtener-tu-ip-local)
2. [Configurar XAMPP para Acceso Externo](#2-configurar-xampp-para-acceso-externo)
3. [Configurar Firewall de Windows](#3-configurar-firewall-de-windows)
4. [Actualizar IP en el Código](#4-actualizar-ip-en-el-código)
5. [Acceder desde Otros Dispositivos](#5-acceder-desde-otros-dispositivos)
6. [Solución de Problemas](#6-solución-de-problemas)

---

## 1. Obtener tu IP Local

### Método 1: Usando PowerShell (Recomendado)

1. Presiona `Windows + X` y selecciona **"Windows PowerShell"** o **"Terminal"**
2. Ejecuta el siguiente comando:
   ```powershell
   ipconfig
   ```
3. Busca la sección **"Adaptador de Ethernet"** o **"Adaptador de LAN inalámbrica"**
4. Encuentra la línea **"Dirección IPv4"** - esa es tu IP local
   - Ejemplo: `192.168.1.23` o `192.168.0.105`

### Método 2: Usando Configuración de Windows

1. Presiona `Windows + I` para abrir Configuración
2. Ve a **"Red e Internet"** → **"Propiedades"**
3. Busca **"Dirección IPv4"** - esa es tu IP local

### Método 3: Usando CMD

1. Presiona `Windows + R`, escribe `cmd` y presiona Enter
2. Ejecuta:
   ```cmd
   ipconfig
   ```
3. Busca **"Dirección IPv4"** en la sección de tu adaptador de red

**⚠️ IMPORTANTE:** Anota esta IP, la necesitarás en los siguientes pasos.

---

## 2. Configurar XAMPP para Acceso Externo

### Paso 1: Editar httpd.conf

1. Abre el **Panel de Control de XAMPP**
2. Haz clic en **"Config"** junto a Apache
3. Selecciona **"httpd.conf"**
4. Busca la línea que dice:
   ```apache
   Listen 80
   ```
5. Asegúrate de que esté configurada así (sin restricciones):
   ```apache
   Listen 80
   ```
   Si ves `Listen 127.0.0.1:80`, cámbialo a `Listen 80`

### Paso 2: Configurar Virtual Host (Opcional pero Recomendado)

1. En el mismo archivo `httpd.conf`, busca la sección que contiene:
   ```apache
   <Directory />
       AllowOverride none
       Require all denied
   </Directory>
   ```
2. Busca la sección de tu directorio `htdocs` (debería estar más abajo):
   ```apache
   <Directory "C:/xampp/htdocs">
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```
3. Asegúrate de que diga **`Require all granted`** (no `Require local`)

### Paso 3: Reiniciar Apache

1. En el Panel de Control de XAMPP, detén Apache (si está corriendo)
2. Inicia Apache nuevamente
3. Verifica que no haya errores en el panel

---

## 3. Configurar Firewall de Windows

### Opción A: Permitir Puerto 80 a través del Firewall (Recomendado)

1. Presiona `Windows + R`, escribe `wf.msc` y presiona Enter
2. En el panel izquierdo, haz clic en **"Reglas de entrada"**
3. En el panel derecho, haz clic en **"Nueva regla..."**
4. Selecciona **"Puerto"** → **"Siguiente"**
5. Selecciona **"TCP"** y escribe **`80`** en "Puertos locales específicos" → **"Siguiente"**
6. Selecciona **"Permitir la conexión"** → **"Siguiente"**
7. Marca todas las casillas (Dominio, Privada, Pública) → **"Siguiente"**
8. Nombra la regla: **"XAMPP Apache HTTP"** → **"Finalizar"**

### Opción B: Permitir XAMPP a través del Firewall (Más Simple)

1. Presiona `Windows + R`, escribe `firewall.cpl` y presiona Enter
2. Haz clic en **"Permitir una aplicación o característica a través del Firewall de Windows"**
3. Haz clic en **"Cambiar configuración"** (requiere permisos de administrador)
4. Busca **"Apache HTTP Server"** en la lista
5. Si no está, haz clic en **"Permitir otra aplicación..."**
6. Haz clic en **"Examinar..."** y navega a:
   ```
   C:\xampp\apache\bin\httpd.exe
   ```
7. Marca las casillas **"Privada"** y **"Pública"** para Apache
8. Haz clic en **"Aceptar"**

### Opción C: Desactivar Firewall Temporalmente (Solo para Pruebas)

⚠️ **NO RECOMENDADO para uso permanente** - Solo para verificar que el firewall es el problema.

1. Ve a **"Configuración"** → **"Seguridad de Windows"** → **"Firewall y protección de red"**
2. Desactiva el firewall temporalmente
3. **Recuerda reactivarlo después de las pruebas**

---

## 4. Actualizar IP en el Código

Tu aplicación tiene una IP hardcodeada que necesita actualizarse. Sigue estos pasos:

### Paso 1: Localizar el Archivo

El archivo que contiene la IP está en:
```
src/views/admin/generador_qr_offline.php
```

### Paso 2: Editar la IP

1. Abre el archivo `src/views/admin/generador_qr_offline.php` en un editor de texto
2. Busca la línea 1481 que dice:
   ```javascript
   const baseUrl = 'http://192.168.1.23/Comanda/public';
   ```
3. Reemplaza `192.168.1.23` con **tu IP local** que obtuviste en el Paso 1
   - Ejemplo: Si tu IP es `192.168.0.105`, debería quedar:
   ```javascript
   const baseUrl = 'http://192.168.0.105/Comanda/public';
   ```
4. Guarda el archivo

### Paso 3: Verificar Otros Archivos (Opcional)

El sistema detecta automáticamente la URL base en la mayoría de los casos, pero si encuentras problemas, verifica que no haya otras IPs hardcodeadas:

1. Busca en el proyecto archivos que contengan `192.168.` o `localhost`
2. Reemplázalos con tu IP local si es necesario

---

## 5. Acceder desde Otros Dispositivos

### Requisitos Previos

✅ Tu computadora y el dispositivo deben estar en la **misma red WiFi/Ethernet**
✅ XAMPP debe estar corriendo (Apache y MySQL)
✅ El firewall debe estar configurado correctamente

### Paso 1: Verificar que Todo Funciona Localmente

1. En tu computadora, abre un navegador
2. Accede a: `http://localhost/Comanda/public`
3. Verifica que la aplicación funcione correctamente

### Paso 2: Acceder desde Otro Dispositivo

1. **En el otro dispositivo** (celular, tablet, otra computadora):
   - Conéctalo a la **misma red WiFi** que tu computadora
2. Abre un navegador en ese dispositivo
3. Accede usando tu IP local:
   ```
   http://TU_IP_LOCAL/Comanda/public
   ```
   - Ejemplo: `http://192.168.1.23/Comanda/public`
   - Ejemplo: `http://192.168.0.105/Comanda/public`

### Paso 3: Probar los Códigos QR

1. Si generaste códigos QR antes de cambiar la IP, **regenera los QRs** desde el panel de administración
2. Los nuevos QRs apuntarán a la IP correcta
3. Escanea un QR desde tu celular para verificar que funciona

---

## 6. Solución de Problemas

### ❌ Problema: "No se puede acceder a este sitio"

**Posibles causas y soluciones:**

1. **Verifica que XAMPP esté corriendo**
   - Abre el Panel de Control de XAMPP
   - Asegúrate de que Apache esté en verde (corriendo)

2. **Verifica tu IP**
   - Ejecuta `ipconfig` nuevamente
   - Asegúrate de usar la IP correcta
   - ⚠️ La IP puede cambiar si te desconectas y reconectas a la red

3. **Verifica el firewall**
   - Asegúrate de haber configurado las reglas del firewall correctamente
   - Prueba desactivar temporalmente el firewall para verificar

4. **Verifica que estés en la misma red**
   - Ambos dispositivos deben estar en la misma red WiFi
   - No funcionará si uno está en WiFi y otro en datos móviles

### ❌ Problema: "La conexión se agotó"

1. **Verifica que Apache esté escuchando en el puerto 80**
   - Abre `httpd.conf` y verifica `Listen 80`
   - Reinicia Apache

2. **Verifica que no haya otro programa usando el puerto 80**
   - Cierra Skype u otros programas que puedan usar el puerto 80
   - O cambia el puerto de Apache a 8080 en `httpd.conf`:
     ```apache
     Listen 8080
     ```
   - Luego accede con: `http://TU_IP:8080/Comanda/public`

### ❌ Problema: "Los QRs no funcionan"

1. **Regenera los códigos QR**
   - Ve al panel de administración
   - Accede a "Generador de QRs"
   - Haz clic en "Regenerar todos los QRs"

2. **Verifica que la IP en el código sea correcta**
   - Revisa el archivo `generador_qr_offline.php`
   - Asegúrate de que la IP coincida con tu IP actual

### ❌ Problema: "La IP cambia constantemente"

**Solución: Configurar IP Estática**

1. Presiona `Windows + X` → **"Configuración de red"**
2. Haz clic en **"Cambiar opciones del adaptador"**
3. Haz clic derecho en tu adaptador de red → **"Propiedades"**
4. Selecciona **"Protocolo de Internet versión 4 (TCP/IPv4)"** → **"Propiedades"**
5. Selecciona **"Usar la siguiente dirección IP"**
6. Completa:
   - **Dirección IP:** Tu IP actual (ej: `192.168.1.23`)
   - **Máscara de subred:** `255.255.255.0` (generalmente)
   - **Puerta de enlace predeterminada:** La IP de tu router (ej: `192.168.1.1`)
   - **Servidor DNS preferido:** `8.8.8.8` (Google DNS)
7. Haz clic en **"Aceptar"**

### ❌ Problema: "No puedo acceder desde fuera de mi red local"

**Nota:** Para acceder desde Internet (fuera de tu red local), necesitas:
- Configurar port forwarding en tu router
- Tener una IP pública estática o usar un servicio como ngrok
- **Esto está fuera del alcance de este manual básico**

---

## 📝 Resumen Rápido

1. ✅ Obtén tu IP local con `ipconfig`
2. ✅ Configura XAMPP para escuchar en todas las interfaces (`Listen 80`)
3. ✅ Permite el puerto 80 en el Firewall de Windows
4. ✅ Actualiza la IP en `generador_qr_offline.php` (línea 1481)
5. ✅ Accede desde otros dispositivos usando `http://TU_IP/Comanda/public`
6. ✅ Regenera los códigos QR después de cambiar la IP

---

## 🔒 Consideraciones de Seguridad

⚠️ **IMPORTANTE:** Al permitir acceso externo a tu servidor:

- Solo permite acceso desde tu red local (no desde Internet)
- Asegúrate de tener contraseñas seguras en tu base de datos
- No uses esta configuración en producción sin medidas de seguridad adicionales
- Considera usar HTTPS en lugar de HTTP para mayor seguridad

---

## 📞 Soporte Adicional

Si después de seguir este manual sigues teniendo problemas:

1. Verifica los logs de Apache en: `C:\xampp\apache\logs\error.log`
2. Verifica que MySQL también esté corriendo
3. Asegúrate de que la base de datos esté configurada correctamente
4. Revisa que el archivo `database.php` tenga las credenciales correctas

---

**¡Listo!** Ahora deberías poder acceder a tu sistema Comanda desde cualquier dispositivo en tu red local. 🎉

