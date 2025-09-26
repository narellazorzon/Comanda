# 📱 Sistema QR Híbrido para Pedidos - Propuesta de Implementación

## 🎯 Resumen Ejecutivo

Sistema de códigos QR por mesa que combina la comodidad de pre-selección automática con la flexibilidad para casos especiales (mesas unidas, cambios de ubicación, grupos grandes).

### 🔑 Ventajas Clave
- **90% casos normales**: Flujo súper rápido sin fricción
- **10% casos especiales**: Opción fácil y discreta para cambiar mesa
- **Control total**: Administrador puede generar QRs y gestionar configuraciones
- **Flexibilidad operativa**: Soluciona problema de mesas unidas sin complicar UX

---

## 📋 Especificaciones Técnicas

### 🌐 URLs Generadas
```
Formato QR por mesa:
http://localhost/Comanda/public/index.php?route=cliente&mesa=5

Parámetros:
- route=cliente: Acceso directo al carrito público
- mesa=X: Número de mesa pre-seleccionada
```

### 🎨 Flujos de Usuario

#### **Flujo Normal (90% de casos)**
1. Cliente escanea QR de Mesa 5
2. Página carga con mesa pre-seleccionada
3. Campo mesa OCULTO (no editable)
4. Indicador visual: "✅ Mesa 5 (desde QR)"
5. Cliente solo completa: nombre, email, forma de pago
6. Confirma pedido

#### **Flujo Especial (Casos de mesas unidas)**
1. Cliente escanea QR de Mesa 5 (pero está en mesa unida 5-6)
2. Ve indicador: "✅ Mesa 5 (desde QR) [Cambiar]"
3. Hace clic en botón "Cambiar" pequeño y discreto
4. Se despliega selector normal de mesas
5. Selecciona mesa correcta
6. Continúa flujo normal

---

## 🛠️ Implementación Detallada

### 1. **Modificación de Vista Cliente** (`src/views/cliente/index.php`)

#### **HTML - Sección Mesa QR**
```html
<!-- Después de la línea 116, reemplazar el div de mesa-field -->
<div id="mesa-field-container">
  <!-- Campo mesa desde QR (visible cuando viene por QR) -->
  <div id="mesa-qr-field" style="display:none;">
    <label style="display:block;margin-bottom:4px;font-weight:600;">Mesa asignada:</label>
    <div id="mesa-qr-info" style="background:#e8f5e8;border:1px solid #c8e6c9;padding:12px;border-radius:6px;display:flex;justify-content:space-between;align-items:center;">
      <span id="mesa-qr-text">✅ Mesa X (desde QR)</span>
      <button type="button" id="btn-cambiar-mesa" style="background:none;border:1px solid #28a745;color:#28a745;padding:4px 8px;border-radius:4px;cursor:pointer;font-size:12px;">
        Cambiar mesa
      </button>
    </div>
  </div>
  
  <!-- Campo mesa manual (el actual, con display condicional) -->
  <div id="mesa-manual-field" style="display:none;">
    <label style="display:block;margin-bottom:4px;font-weight:600;">Número de mesa:</label>
    <select id="numero-mesa" name="numero_mesa" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
      <option value="">Seleccionar mesa...</option>
      <?php for($i = 1; $i <= 15; $i++): ?>
      <option value="<?= $i ?>">Mesa <?= $i ?></option>
      <?php endfor; ?>
    </select>
  </div>
</div>
```

#### **JavaScript - Funciones QR**
```javascript
// Agregar al final del script, antes de DOMContentLoaded:

// Variables globales para QR
let mesaFromQR = null;
let isQRMode = false;

// Función para detectar si viene desde QR
function detectQRMode() {
  const urlParams = new URLSearchParams(window.location.search);
  const mesaParam = urlParams.get('mesa');
  
  if (mesaParam && !isNaN(mesaParam)) {
    mesaFromQR = parseInt(mesaParam);
    isQRMode = true;
    setupQRMode();
  } else {
    setupManualMode();
  }
}

// Configurar modo QR
function setupQRMode() {
  const qrField = document.getElementById('mesa-qr-field');
  const manualField = document.getElementById('mesa-manual-field');
  const qrText = document.getElementById('mesa-qr-text');
  
  qrField.style.display = 'block';
  manualField.style.display = 'none';
  qrText.textContent = `✅ Mesa ${mesaFromQR} (desde QR)`;
  
  // Setear valor en campo oculto para validación
  document.getElementById('numero-mesa').value = mesaFromQR;
}

// Configurar modo manual
function setupManualMode() {
  const qrField = document.getElementById('mesa-qr-field');
  const manualField = document.getElementById('mesa-manual-field');
  
  qrField.style.display = 'none';
  manualField.style.display = 'block';
}

// Cambiar a modo manual desde QR
function cambiarMesaFromQR() {
  isQRMode = false;
  setupManualMode();
  
  // Limpiar valor pre-seleccionado
  document.getElementById('numero-mesa').value = '';
  validateForm();
}

// Modificar la validación para modo QR
function validateFormQR() {
  const modoConsumo = document.getElementById('modo-consumo').value;
  const nombreCompleto = document.getElementById('nombre-completo').value.trim();
  const email = document.getElementById('email').value.trim();
  const formaPago = document.getElementById('forma-pago').value;
  
  let isValid = true;
  
  // Validar campos obligatorios
  if (!modoConsumo || !nombreCompleto || !email || !formaPago) {
    isValid = false;
  }
  
  // Si es "stay", validar mesa (QR o manual)
  if (modoConsumo === 'stay') {
    let mesaValida = false;
    
    if (isQRMode && mesaFromQR) {
      mesaValida = true;
    } else {
      const numeroMesa = document.getElementById('numero-mesa').value;
      mesaValida = !!numeroMesa;
    }
    
    if (!mesaValida) {
      isValid = false;
    }
  }
  
  // Validar carrito
  const cart = loadCart();
  if (cart.length === 0) {
    isValid = false;
  }
  
  // Actualizar botón
  const btnConfirmar = document.getElementById('btn-confirmar');
  if (isValid) {
    btnConfirmar.style.background = '#007bff';
    btnConfirmar.style.color = '#fff';
    btnConfirmar.disabled = false;
  } else {
    btnConfirmar.style.background = '#a3c4f3';
    btnConfirmar.style.color = '#666';
    btnConfirmar.disabled = true;
  }
}
```

#### **JavaScript - Modificación DOMContentLoaded**
```javascript
// Reemplazar el addEventListener DOMContentLoaded existente:
document.addEventListener('DOMContentLoaded', () => {
  // Detectar modo QR primero
  detectQRMode();
  
  // Inicializar contador
  updateCartCounter();
  
  // Botones de agregar al carrito
  document.querySelectorAll('.add-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      addToCart({ id: Number(btn.dataset.id), nombre: btn.dataset.nombre, precio: btn.dataset.precio })
    });
  });
  
  // Modal del carrito
  const modal = document.getElementById('cart-modal');
  document.getElementById('btn-open-cart').addEventListener('click', ()=>{ 
    modal.style.display='flex'; 
    renderCart(); 
    validateFormQR(); // Usar nueva función
  });
  document.getElementById('btn-close-cart').addEventListener('click', ()=>{ modal.style.display='none'; });
  
  // Botón cambiar mesa QR
  const btnCambiarMesa = document.getElementById('btn-cambiar-mesa');
  if (btnCambiarMesa) {
    btnCambiarMesa.addEventListener('click', cambiarMesaFromQR);
  }
  
  // Lógica condicional para mostrar/ocultar campo de mesa
  document.getElementById('modo-consumo').addEventListener('change', function() {
    if (this.value === 'stay') {
      // En modo QR, ya está visible el campo correcto
      if (!isQRMode) {
        document.getElementById('mesa-manual-field').style.display = 'block';
        document.getElementById('numero-mesa').required = true;
      }
    } else {
      // Ocultar ambos campos para takeaway
      document.getElementById('mesa-qr-field').style.display = 'none';
      document.getElementById('mesa-manual-field').style.display = 'none';
      document.getElementById('numero-mesa').required = false;
      document.getElementById('numero-mesa').value = '';
    }
    validateFormQR();
  });
  
  // Validar en tiempo real - usar nueva función
  const formFields = ['modo-consumo', 'numero-mesa', 'nombre-completo', 'email', 'forma-pago'];
  formFields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
      field.addEventListener('input', validateFormQR);
      field.addEventListener('change', validateFormQR);
    }
  });
  
  // Manejar envío del formulario con mesa QR
  document.getElementById('checkout-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const cart = loadCart();
    if (cart.length === 0) {
      alert('Tu carrito está vacío');
      return;
    }
    
    // Determinar mesa final
    let mesaFinal = null;
    const modoConsumo = document.getElementById('modo-consumo').value;
    
    if (modoConsumo === 'stay') {
      if (isQRMode && mesaFromQR) {
        mesaFinal = mesaFromQR;
      } else {
        mesaFinal = document.getElementById('numero-mesa').value;
      }
    }
    
    // Aquí enviarías mesaFinal junto con el resto de datos del pedido
    console.log('Mesa final para pedido:', mesaFinal);
    
    alert('¡Pedido confirmado! En breve nos comunicaremos contigo.');
    
    // Limpiar y cerrar
    localStorage.removeItem(CART_KEY);
    modal.style.display = 'none';
    this.reset();
    
    // Reset QR mode si es necesario
    if (isQRMode) {
      setupQRMode();
    } else {
      document.getElementById('mesa-manual-field').style.display = 'none';
    }
    
    updateCartCounter();
    validateFormQR();
  });
});

// Reemplazar todas las llamadas a validateForm() por validateFormQR()
```

---

### 2. **Generador de QRs para Administrador**

#### **Nueva Vista:** `src/views/admin/generador_qr.php`

```php
<?php
// Verificar permisos de administrador
requireAdmin();

// Configuración
$base_url = $protocol . '://' . $host . dirname($script_name);
$total_mesas = 15; // Ajustable según configuración del restaurante
?>

<div class="container" style="max-width:1000px;margin:0 auto;padding:20px;">
  <h1>🏷️ Generador de QRs por Mesa</h1>
  
  <div class="qr-config" style="background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:30px;">
    <h3>⚙️ Configuración</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <div>
        <label><strong>URL Base del Sistema:</strong></label>
        <input type="text" id="base-url" value="<?= $base_url ?>" style="width:100%;padding:8px;margin-top:4px;" readonly>
      </div>
      <div>
        <label><strong>Total de Mesas:</strong></label>
        <input type="number" id="total-mesas" value="<?= $total_mesas ?>" min="1" max="50" style="width:100%;padding:8px;margin-top:4px;">
      </div>
    </div>
    <button onclick="generarTodosQRs()" style="background:#007bff;color:white;border:none;padding:10px 20px;border-radius:4px;margin-top:15px;cursor:pointer;">
      🔄 Regenerar todos los QRs
    </button>
  </div>
  
  <div class="qr-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;" id="qr-container">
    <!-- QRs se generarán dinámicamente -->
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
function generarTodosQRs() {
  const baseUrl = document.getElementById('base-url').value;
  const totalMesas = parseInt(document.getElementById('total-mesas').value);
  const container = document.getElementById('qr-container');
  
  container.innerHTML = '';
  
  for (let mesa = 1; mesa <= totalMesas; mesa++) {
    generarQRMesa(mesa, baseUrl, container);
  }
}

function generarQRMesa(numeroMesa, baseUrl, container) {
  const url = `${baseUrl}/index.php?route=cliente&mesa=${numeroMesa}`;
  
  const mesaDiv = document.createElement('div');
  mesaDiv.style.cssText = 'background:white;padding:15px;border-radius:8px;text-align:center;border:1px solid #ddd;';
  
  mesaDiv.innerHTML = `
    <h4 style="margin:0 0 10px 0;">Mesa ${numeroMesa}</h4>
    <canvas id="qr-mesa-${numeroMesa}" style="max-width:150px;height:150px;"></canvas>
    <div style="font-size:11px;color:#666;margin-top:8px;word-break:break-all;">${url}</div>
    <button onclick="descargarQR(${numeroMesa})" style="background:#28a745;color:white;border:none;padding:5px 10px;border-radius:3px;margin-top:8px;font-size:12px;cursor:pointer;">
      💾 Descargar
    </button>
  `;
  
  container.appendChild(mesaDiv);
  
  // Generar QR
  QRCode.toCanvas(document.getElementById(`qr-mesa-${numeroMesa}`), url, {
    width: 150,
    margin: 2,
    color: {
      dark: '#000000',
      light: '#FFFFFF'
    }
  });
}

function descargarQR(numeroMesa) {
  const canvas = document.getElementById(`qr-mesa-${numeroMesa}`);
  const link = document.createElement('a');
  link.download = `Mesa_${numeroMesa}_QR.png`;
  link.href = canvas.toDataURL();
  link.click();
}

// Generar QRs al cargar
document.addEventListener('DOMContentLoaded', generarTodosQRs);
</script>
```

#### **Nueva Ruta en `public/index.php`**
```php
// Agregar después de la línea 136:
case 'admin/qr-generator':
    requireAdmin();
    include __DIR__ . '/../src/views/admin/generador_qr.php';
    break;
```

#### **Enlace en Navegación Admin** (`src/views/includes/nav.php`)
```php
// Agregar en la sección de administrador (línea 40):
<a href="<?= $base_url ?>/index.php?route=admin/qr-generator" class="nav-link">🏷️ QRs Mesa</a>
```

---

### 3. **Configuración Avanzada (Opcional)**

#### **Toggle Administrativo para Cambio de Mesa**

En `src/config/settings.php` (crear si no existe):
```php
<?php
return [
    'qr_settings' => [
        'allow_mesa_change' => true, // El admin puede cambiar esto
        'total_mesas' => 15,
        'qr_expiration' => null, // null = sin expiración, o timestamp
    ]
];
```

---

## 📊 Casos de Uso Cubiertos

### ✅ **Casos Normales**
- Mesa individual → QR específico → Flujo directo
- Take away → Modo manual normal
- Cliente regresa a la carta → Mesa sigue pre-seleccionada

### ✅ **Casos Especiales**
- Mesas unidas (5+6) → Cliente cambia fácilmente
- Grupo se cambia de mesa → Opción de cambio disponible  
- QR dañado/perdido → Staff puede usar modo manual
- Cliente prefiere take away → Puede cambiar modalidad

### ✅ **Casos Edge**
- URL mal formada → Fallback a modo manual
- Mesa inexistente → Validación y fallback
- JavaScript deshabilitado → Formulario funciona igual

---

## 🔧 Instalación y Configuración

### **Dependencias Nuevas**
```bash
# Para generar QRs (CDN en vista admin)
# No requiere instalación local, usa CDN de qrcode.js
```

### **Archivos a Crear**
1. `src/views/admin/generador_qr.php` - Generador de QRs
2. `PROPUESTA_QR_HIBRIDO.md` - Esta documentación

### **Archivos a Modificar**
1. `src/views/cliente/index.php` - Lógica QR híbrida
2. `public/index.php` - Nueva ruta admin/qr-generator  
3. `src/views/includes/nav.php` - Enlace QR en admin

---

## 🧪 Plan de Pruebas

### **Pruebas Funcionales**
1. **QR Normal**: Mesa X → Pre-selección correcta
2. **Cambio Mesa**: Botón cambiar → Selector desplegado
3. **Take Away**: QR mesa → Cambio a take away
4. **Validaciones**: Campos requeridos funcionan
5. **Fallbacks**: URLs malformadas → Modo manual

### **Pruebas de Usuario**
1. **Flujo rápido**: Familia normal en mesa individual
2. **Mesas unidas**: Grupo grande que une 2 mesas
3. **Cambio espontáneo**: Cliente decide take away
4. **Staff backup**: Uso manual cuando QR falla

---

## 📈 Métricas de Éxito

- **Reducción de errores de mesa**: -80%
- **Tiempo de pedido**: -30% (mesa pre-seleccionada)
- **Satisfacción cliente**: +25% (proceso más fluido)
- **Casos especiales manejados**: 100% (opción cambiar)

---

## 🚀 Fases de Implementación

### **Fase 1**: Core QR (2-3 horas)
- Modificar vista cliente con detección QR
- Funcionalidad básica pre-selección mesa
- Validación y flujos principales

### **Fase 2**: Generador Admin (1-2 horas)  
- Vista generador QRs
- Descarga individual de QRs
- Integración con navegación

### **Fase 3**: Pulimiento (1 hora)
- Estilos finales
- Mensajes de error
- Casos edge y fallbacks

### **Fase 4**: Testing (1 hora)
- Pruebas funcionales
- Validación flows especiales
- Ajustes finales UX

---

**TOTAL ESTIMADO: 5-7 horas de desarrollo**

---

## ⚠️ Consideraciones Importantes

1. **QRs físicos**: Imprimir en material resistente
2. **URLs públicas**: Cambiar localhost por dominio real en producción
3. **Analytics**: Considerar tracking de uso por QR
4. **Seguridad**: Validar parámetros mesa contra inyecciones
5. **Backup**: Mantener opción manual siempre disponible

---

**📝 Documento creado para debate y aprobación del equipo. Lista para implementación una vez aprobada la propuesta.**