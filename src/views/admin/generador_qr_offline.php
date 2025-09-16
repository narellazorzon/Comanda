<?php
// src/views/admin/generador_qr_offline.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Mesa;

// Verificar permisos de administrador
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
    header('Location: index.php?route=unauthorized');
    exit;
}

// Configuración
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_url = $protocol . '://' . $host . dirname($script_name);

// Obtener todas las mesas de la base de datos
$mesas = Mesa::all();
?>

<h2>Gestión de Códigos QR</h2>

<!-- Mensaje informativo -->
<div style="background: #d1ecf1; padding: 12px; border-radius: 6px; margin-bottom: 1.5rem; color: #0c5460; border: 1px solid #bee5eb;">
    <strong>ℹ️ Información:</strong> Genera códigos QR para cada mesa del restaurante (STAY) y para pedidos para llevar (TAKE AWAY). Los clientes pueden escanear el código para acceder al menú y realizar pedidos.
</div>

<!-- Configuración de QR -->
<div class="filters-container" style="margin-bottom: 1.5rem;">
    <div class="search-filter" style="background: rgb(250, 238, 193); border: 1px solid #e0e0e0; border-radius: 6px; padding: 1rem;">
        <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary); font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
            ⚙️ Configuración de QR
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label for="qr-size" style="font-weight: 600; color: var(--secondary); font-size: 0.9rem; display: block; margin-bottom: 0.3rem;">
                    📐 Tamaño (px):
                </label>
                <input type="number" id="qr-size" value="200" min="100" max="500" 
                       style="width: 100%; padding: 0.5rem; border: 1px solid var(--accent); border-radius: 4px; font-size: 0.9rem;">
            </div>
            
            <div>
                <label for="qr-margin" style="font-weight: 600; color: var(--secondary); font-size: 0.9rem; display: block; margin-bottom: 0.3rem;">
                    📏 Margen:
                </label>
                <input type="number" id="qr-margin" value="1" min="0" max="10" 
                       style="width: 100%; padding: 0.5rem; border: 1px solid var(--accent); border-radius: 4px; font-size: 0.9rem;">
            </div>
            
            <div>
                <label for="qr-color" style="font-weight: 600; color: var(--secondary); font-size: 0.9rem; display: block; margin-bottom: 0.3rem;">
                    🎨 Color:
                </label>
                <input type="color" id="qr-color" value="#000000" 
                       style="width: 100%; height: 38px; padding: 0.25rem; border: 1px solid var(--accent); border-radius: 4px; cursor: pointer;">
            </div>
            
            <div>
                <label for="qr-bgcolor" style="font-weight: 600; color: var(--secondary); font-size: 0.9rem; display: block; margin-bottom: 0.3rem;">
                    🎨 Fondo:
                </label>
                <input type="color" id="qr-bgcolor" value="#FFFFFF" 
                       style="width: 100%; height: 38px; padding: 0.25rem; border: 1px solid var(--accent); border-radius: 4px; cursor: pointer;">
            </div>
        </div>
        
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
            <button onclick="regenerarTodos()" class="button" style="padding: 0.6rem 1rem; font-size: 0.9rem;">
                🔄 Regenerar Todos
            </button>
            <button onclick="descargarTodos()" class="button" style="background: rgb(40, 167, 69); padding: 0.6rem 1rem; font-size: 0.9rem;">
                📥 Descargar Todos (ZIP)
            </button>
            <button onclick="imprimirSeleccionados()" class="button" style="background: rgb(237, 221, 172); color: #212529; padding: 0.6rem 1rem; font-size: 0.9rem;">
                🖨️ Imprimir Seleccionados
            </button>
        </div>
        
        <!-- Selector de tipo de QR -->
        <div style="display: flex; gap: 0.5rem; align-items: center; padding: 0.75rem; background: #f8f9fa; border-radius: 6px; border: 1px solid #e0e0e0;">
            <span style="font-weight: 600; color: var(--secondary); font-size: 0.9rem;">Tipo de QR:</span>
            <button id="btn-stay" onclick="mostrarTipoQR('stay')" class="button" style="padding: 0.5rem 1rem; font-size: 0.85rem; background: var(--secondary); color: white;">
                🪑 STAY (Mesas)
            </button>
            <button id="btn-takeaway" onclick="mostrarTipoQR('takeaway')" class="button" style="padding: 0.5rem 1rem; font-size: 0.85rem; background: #6c757d; color: white;">
                🥡 TAKE AWAY
            </button>
        </div>
    </div>
</div>

<!-- Estadísticas rápidas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div style="background: white; padding: 1rem; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <div style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;">📊</div>
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--secondary);"><?= count($mesas) ?></div>
        <div style="color: #666; font-size: 0.9rem;">Total Mesas</div>
    </div>
    
    <div style="background: white; padding: 1rem; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <div style="font-size: 2rem; color: #28a745; margin-bottom: 0.5rem;">✅</div>
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--secondary);" id="qr-generados">0</div>
        <div style="color: #666; font-size: 0.9rem;">QR Generados</div>
    </div>
    
    <div style="background: white; padding: 1rem; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <div style="font-size: 2rem; color: #007bff; margin-bottom: 0.5rem;">📱</div>
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--secondary);" id="qr-seleccionados">0</div>
        <div style="color: #666; font-size: 0.9rem;">Seleccionados</div>
    </div>
</div>

<!-- Filtro de búsqueda -->
<div class="filters-container" style="margin-bottom: 1rem;">
    <div class="search-filter" style="background: rgb(250, 238, 193); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.6rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="buscarMesa" style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">
                🔍 Buscar Mesa:
            </label>
            <input type="text" id="buscarMesa" placeholder="Número o ubicación..." 
                   style="padding: 0.3rem 0.5rem; border: 1px solid var(--accent); border-radius: 3px; font-size: 0.8rem; min-width: 200px;">
            <button onclick="limpiarBusqueda()" 
                    style="padding: 0.3rem 0.6rem; background: var(--secondary); color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.75rem;">
                Limpiar
            </button>
            
            <div style="margin-left: auto;">
                <label style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">
                    <input type="checkbox" id="seleccionarTodos" onchange="toggleSeleccionarTodos()">
                    Seleccionar Todos
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Grid de QR codes -->
<div id="qr-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <?php foreach ($mesas as $mesa): ?>
    <div class="qr-card" data-mesa="<?= $mesa['numero'] ?>" data-ubicacion="<?= htmlspecialchars($mesa['ubicacion'] ?? '') ?>" 
         style="background: white; border-radius: 8px; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s ease;">
        
        <!-- Header de la tarjeta -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div>
                <h3 style="margin: 0; color: var(--secondary); font-size: 1.1rem;">
                    Mesa <?= htmlspecialchars($mesa['numero']) ?>
                </h3>
                <?php if ($mesa['ubicacion']): ?>
                <small style="color: #666;">📍 <?= htmlspecialchars($mesa['ubicacion']) ?></small>
                <?php endif; ?>
            </div>
            <label style="cursor: pointer;">
                <input type="checkbox" class="qr-checkbox" data-mesa-id="<?= $mesa['id_mesa'] ?>" onchange="actualizarContadorSeleccionados()">
            </label>
        </div>
        
        <!-- Estado de la mesa -->
        <div style="margin-bottom: 1rem;">
            <?php
            $estado = $mesa['estado'];
            $estado_config = [
                'libre' => ['bg' => '#d4edda', 'color' => '#155724', 'icon' => '🟢'],
                'ocupada' => ['bg' => '#f8d7da', 'color' => '#721c24', 'icon' => '🔴'],
                'reservada' => ['bg' => '#fff3cd', 'color' => '#856404', 'icon' => '🟡']
            ];
            $config = $estado_config[$estado] ?? $estado_config['libre'];
            ?>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                         background: <?= $config['bg'] ?>; color: <?= $config['color'] ?>;">
                <?= $config['icon'] ?> <?= ucfirst($estado) ?>
            </span>
            
            <?php if ($mesa['mozo_nombre_completo']): ?>
            <span style="margin-left: 0.5rem; padding: 4px 8px; border-radius: 12px; font-size: 0.8em; 
                         background: #e2e3e5; color: #383d41;">
                👤 <?= htmlspecialchars($mesa['mozo_nombre_completo']) ?>
            </span>
            <?php endif; ?>
        </div>
        
        <!-- Contenedor del QR -->
        <div class="qr-container" style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 4px; min-height: 220px; display: flex; align-items: center; justify-content: center;">
            <div class="qr-image" id="qr-mesa-<?= $mesa['id_mesa'] ?>">
                <div style="color: #666;">
                    <div class="spinner" style="border: 3px solid #f3f3f3; border-top: 3px solid var(--secondary); 
                                                border-radius: 50%; width: 40px; height: 40px; 
                                                animation: spin 1s linear infinite; margin: 0 auto;"></div>
                    <p style="margin-top: 1rem; font-size: 0.9rem;">Generando QR...</p>
                </div>
            </div>
        </div>
        
        <!-- URL del QR -->
        <div style="margin-top: 1rem; padding: 0.5rem; background: #f8f9fa; border-radius: 4px; font-size: 0.75rem; color: #666; word-break: break-all;">
            <strong>URL:</strong> <?= $base_url ?>/index.php?route=cliente&qr=<?= $mesa['numero'] ?>
        </div>
        
        <!-- Acciones -->
        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
            <button onclick="descargarQR(<?= $mesa['id_mesa'] ?>, <?= $mesa['numero'] ?>)" 
                    class="button" style="flex: 1; padding: 0.5rem; font-size: 0.85rem; background: var(--accent); color: var(--text);">
                📥 Descargar
            </button>
            <button onclick="imprimirQR(<?= $mesa['id_mesa'] ?>, <?= $mesa['numero'] ?>)" 
                    class="button" style="flex: 1; padding: 0.5rem; font-size: 0.85rem; background: var(--secondary); color: white;">
                🖨️ Imprimir
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Sección QR TAKE AWAY -->
<div id="qr-takeaway-section" style="display: none;">
    <h3 style="color: var(--secondary); margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
        🥡 Códigos QR para TAKE AWAY
    </h3>
    
    <div class="filters-container" style="margin-bottom: 1rem;">
        <div class="search-filter" style="background: rgb(250, 238, 193); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.6rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label for="buscarTakeaway" style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">
                    🔍 Buscar QR:
                </label>
                <input type="text" id="buscarTakeaway" placeholder="Tipo de QR..." 
                       style="padding: 0.3rem 0.5rem; border: 1px solid var(--accent); border-radius: 3px; font-size: 0.8rem; min-width: 200px;">
                <button onclick="limpiarBusquedaTakeaway()" 
                        style="padding: 0.3rem 0.6rem; background: var(--secondary); color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.75rem;">
                    Limpiar
                </button>
                
                <div style="margin-left: auto;">
                    <label style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">
                        <input type="checkbox" id="seleccionarTodosTakeaway" onchange="toggleSeleccionarTodosTakeaway()">
                        Seleccionar Todos
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Grid de QR TAKE AWAY -->
    <div id="qr-takeaway-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- QR TAKE AWAY Principal -->
        <div class="qr-card takeaway" data-tipo="takeaway-principal" 
             style="background: white; border-radius: 8px; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s ease; border-left: 4px solid #28a745;">
            
            <!-- Header de la tarjeta -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div>
                    <h3 style="margin: 0; color: var(--secondary); font-size: 1.1rem;">
                        🥡 TAKE AWAY
                    </h3>
                    <small style="color: #666;">Pedidos para llevar</small>
                </div>
                <label style="cursor: pointer;">
                    <input type="checkbox" class="qr-checkbox-takeaway" data-tipo="takeaway-principal" onchange="actualizarContadorSeleccionadosTakeaway()">
                </label>
            </div>
            
            <!-- Estado -->
            <div style="margin-bottom: 1rem;">
                <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                             background: #d4edda; color: #155724;">
                    🟢 Disponible
                </span>
            </div>
            
            <!-- Contenedor del QR -->
            <div class="qr-container" style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 4px; min-height: 220px; display: flex; align-items: center; justify-content: center;">
                <div class="qr-image" id="qr-takeaway-principal">
                    <div style="color: #666;">
                        <div class="spinner" style="border: 3px solid #f3f3f3; border-top: 3px solid var(--secondary); 
                                                    border-radius: 50%; width: 40px; height: 40px; 
                                                    animation: spin 1s linear infinite; margin: 0 auto;"></div>
                        <p style="margin-top: 1rem; font-size: 0.9rem;">Generando QR...</p>
                    </div>
                </div>
            </div>
            
            <!-- URL del QR -->
            <div style="margin-top: 1rem; padding: 0.5rem; background: #f8f9fa; border-radius: 4px; font-size: 0.75rem; color: #666; word-break: break-all;">
                <strong>URL:</strong> <?= $base_url ?>/index.php?route=cliente&takeaway=1
            </div>
            
            <!-- Acciones -->
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <button onclick="descargarQRTakeaway('takeaway-principal', 'TAKE_AWAY')" 
                        class="button" style="flex: 1; padding: 0.5rem; font-size: 0.85rem; background: var(--accent); color: var(--text);">
                    📥 Descargar
                </button>
                <button onclick="imprimirQRTakeaway('takeaway-principal', 'TAKE_AWAY')" 
                        class="button" style="flex: 1; padding: 0.5rem; font-size: 0.85rem; background: var(--secondary); color: white;">
                    🖨️ Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de progreso -->
<div id="modalProgreso" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                                background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
                max-width: 400px; width: 90%; text-align: center;">
        <h3 style="margin: 0 0 1rem 0; color: #333;">⏳ Procesando...</h3>
        <div style="margin-bottom: 1rem;">
            <div style="background: #e9ecef; border-radius: 4px; overflow: hidden; height: 20px;">
                <div id="progressBar" style="background: var(--secondary); height: 100%; width: 0%; transition: width 0.3s;"></div>
            </div>
        </div>
        <p id="progressText" style="color: #666; margin: 0;">Preparando...</p>
    </div>
</div>

<style>
/* Animación de spinner */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Hover effects */
.qr-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    #qr-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
    }
}

/* Print styles */
@media print {
    .navbar, .filters-container, .button, .qr-checkbox, h2 {
        display: none !important;
    }
    
    .qr-card {
        page-break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #ddd;
    }
    
    #qr-grid {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}

/* Checkbox styling */
.qr-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.qr-card.selected {
    border: 2px solid var(--secondary);
    background: #fafafa;
}
</style>

<script>
// Configuración
const baseUrl = '<?= $base_url ?>';
const mesas = <?= json_encode($mesas) ?>;

// Generar todos los QR al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        generarTodosLosQR();
    }, 500);
    
    // Activar por defecto la vista STAY
    mostrarTipoQR('stay');
});

// Función para generar todos los QR
function generarTodosLosQR() {
    const size = document.getElementById('qr-size').value;
    const margin = document.getElementById('qr-margin').value;
    const color = document.getElementById('qr-color').value.replace('#', '');
    const bgcolor = document.getElementById('qr-bgcolor').value.replace('#', '');
    
    let generados = 0;
    
    mesas.forEach((mesa, index) => {
        setTimeout(() => {
            generarQR(mesa.id_mesa, mesa.numero, size, margin, color, bgcolor);
            generados++;
            document.getElementById('qr-generados').textContent = generados;
        }, index * 100); // Delay para no sobrecargar
    });
}

// Función para generar un QR individual
function generarQR(idMesa, numeroMesa, size, margin, color, bgcolor) {
    const url = `${baseUrl}/index.php?route=cliente&qr=${numeroMesa}`;
    const encodedUrl = encodeURIComponent(url);
    const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=${margin}&color=${color}&bgcolor=${bgcolor}&data=${encodedUrl}`;
    
    const container = document.getElementById(`qr-mesa-${idMesa}`);
    
    // Crear imagen
    const img = new Image();
    img.onload = function() {
        container.innerHTML = '';
        img.style.maxWidth = '100%';
        img.style.height = 'auto';
        container.appendChild(img);
    };
    
    img.onerror = function() {
        // Fallback si falla la API
        container.innerHTML = `
            <div style="padding: 1rem; background: #fff3cd; border-radius: 4px; color: #856404;">
                <p>⚠️ Error al generar QR</p>
                <small>Mesa ${numeroMesa}</small>
            </div>
        `;
    };
    
    img.src = qrApiUrl;
}

// Regenerar todos los QR
function regenerarTodos() {
    document.getElementById('qr-generados').textContent = '0';
    generarTodosLosQR();
}

// Buscar mesas
document.getElementById('buscarMesa').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.qr-card');
    
    cards.forEach(card => {
        const numero = card.dataset.mesa;
        const ubicacion = card.dataset.ubicacion.toLowerCase();
        
        if (numero.includes(busqueda) || ubicacion.includes(busqueda)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Limpiar búsqueda
function limpiarBusqueda() {
    document.getElementById('buscarMesa').value = '';
    document.querySelectorAll('.qr-card').forEach(card => {
        card.style.display = 'block';
    });
}

// Seleccionar todos
function toggleSeleccionarTodos() {
    const selectAll = document.getElementById('seleccionarTodos').checked;
    const checkboxes = document.querySelectorAll('.qr-checkbox');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll;
        updateCardSelection(cb);
    });
    
    actualizarContadorSeleccionados();
}

// Actualizar visual de selección
function updateCardSelection(checkbox) {
    const card = checkbox.closest('.qr-card');
    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }
}

// Actualizar contador de seleccionados
function actualizarContadorSeleccionados() {
    const seleccionados = document.querySelectorAll('.qr-checkbox:checked').length;
    document.getElementById('qr-seleccionados').textContent = seleccionados;
    
    document.querySelectorAll('.qr-checkbox').forEach(cb => {
        updateCardSelection(cb);
    });
}

// Descargar QR individual
function descargarQR(idMesa, numeroMesa) {
    const container = document.getElementById(`qr-mesa-${idMesa}`);
    const img = container.querySelector('img');
    
    if (img) {
        const link = document.createElement('a');
        link.download = `QR_Mesa_${numeroMesa}.png`;
        link.href = img.src;
        link.click();
    } else {
        alert('El QR aún no se ha generado');
    }
}

// Imprimir QR individual
function imprimirQR(idMesa, numeroMesa) {
    const container = document.getElementById(`qr-mesa-${idMesa}`);
    const img = container.querySelector('img');
    
    if (img) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>QR Mesa ${numeroMesa}</title>
                <style>
                    body { 
                        margin: 0; 
                        padding: 20px; 
                        text-align: center; 
                        font-family: Arial, sans-serif;
                    }
                    h1 { 
                        color: #333; 
                        margin-bottom: 20px;
                    }
                    img { 
                        max-width: 400px; 
                        margin: 0 auto;
                    }
                    .info {
                        margin-top: 20px;
                        padding: 10px;
                        background: #f0f0f0;
                        border-radius: 4px;
                    }
                </style>
            </head>
            <body>
                <h1>Mesa ${numeroMesa}</h1>
                <img src="${img.src}" />
                <div class="info">
                    <strong>Sistema Comanda</strong><br>
                    Escanee el código QR para acceder al menú
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        setTimeout(() => {
            printWindow.print();
        }, 500);
    } else {
        alert('El QR aún no se ha generado');
    }
}

// Descargar todos los QR seleccionados
async function descargarTodos() {
    const seleccionados = document.querySelectorAll('.qr-checkbox:checked');
    
    if (seleccionados.length === 0) {
        alert('Por favor seleccione al menos una mesa');
        return;
    }
    
    // Mostrar modal de progreso
    const modal = document.getElementById('modalProgreso');
    modal.style.display = 'flex';
    document.getElementById('progressText').textContent = 'Preparando descarga...';
    
    // Aquí normalmente se generaría un ZIP con las imágenes
    // Por simplicidad, descargamos una por una con delay
    for (let i = 0; i < seleccionados.length; i++) {
        const checkbox = seleccionados[i];
        const mesaId = checkbox.dataset.mesaId;
        const card = checkbox.closest('.qr-card');
        const numeroMesa = card.dataset.mesa;
        
        const progress = ((i + 1) / seleccionados.length) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('progressText').textContent = `Descargando Mesa ${numeroMesa}...`;
        
        descargarQR(mesaId, numeroMesa);
        
        // Delay entre descargas
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    
    // Ocultar modal
    setTimeout(() => {
        modal.style.display = 'none';
        alert(`✅ Se descargaron ${seleccionados.length} códigos QR`);
    }, 500);
}

// Imprimir seleccionados
function imprimirSeleccionados() {
    const seleccionados = document.querySelectorAll('.qr-checkbox:checked');
    
    if (seleccionados.length === 0) {
        alert('Por favor seleccione al menos una mesa');
        return;
    }
    
    // Marcar solo los seleccionados como visibles para impresión
    document.querySelectorAll('.qr-card').forEach(card => {
        card.style.display = 'none';
    });
    
    seleccionados.forEach(cb => {
        cb.closest('.qr-card').style.display = 'block';
    });
    
    // Imprimir
    window.print();
    
    // Restaurar visibilidad
    setTimeout(() => {
        document.querySelectorAll('.qr-card').forEach(card => {
            card.style.display = 'block';
        });
        limpiarBusqueda(); // Por si había filtros activos
    }, 100);
}

// ===== FUNCIONES PARA TAKE AWAY =====

// Mostrar tipo de QR (STAY o TAKE AWAY)
function mostrarTipoQR(tipo) {
    const staySection = document.getElementById('qr-grid');
    const takeawaySection = document.getElementById('qr-takeaway-section');
    const btnStay = document.getElementById('btn-stay');
    const btnTakeaway = document.getElementById('btn-takeaway');
    
    if (tipo === 'stay') {
        staySection.style.display = 'grid';
        takeawaySection.style.display = 'none';
        btnStay.style.background = 'var(--secondary)';
        btnTakeaway.style.background = '#6c757d';
        
        // Generar QR de TAKE AWAY si no está generado
        setTimeout(() => {
            generarQRTakeaway();
        }, 100);
    } else {
        staySection.style.display = 'none';
        takeawaySection.style.display = 'block';
        btnStay.style.background = '#6c757d';
        btnTakeaway.style.background = 'var(--secondary)';
        
        // Generar QR de TAKE AWAY
        setTimeout(() => {
            generarQRTakeaway();
        }, 100);
    }
}

// Generar QR para TAKE AWAY
function generarQRTakeaway() {
    const size = document.getElementById('qr-size').value;
    const margin = document.getElementById('qr-margin').value;
    const color = document.getElementById('qr-color').value.replace('#', '');
    const bgcolor = document.getElementById('qr-bgcolor').value.replace('#', '');
    
    const url = `${baseUrl}/index.php?route=cliente&takeaway=1`;
    const encodedUrl = encodeURIComponent(url);
    const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=${margin}&color=${color}&bgcolor=${bgcolor}&data=${encodedUrl}`;
    
    const container = document.getElementById('qr-takeaway-principal');
    
    // Crear imagen
    const img = new Image();
    img.onload = function() {
        container.innerHTML = '';
        img.style.maxWidth = '100%';
        img.style.height = 'auto';
        container.appendChild(img);
    };
    
    img.onerror = function() {
        // Fallback si falla la API
        container.innerHTML = `
            <div style="padding: 1rem; background: #fff3cd; border-radius: 4px; color: #856404;">
                <p>⚠️ Error al generar QR</p>
                <small>TAKE AWAY</small>
            </div>
        `;
    };
    
    img.src = qrApiUrl;
}

// Buscar QRs de TAKE AWAY
document.getElementById('buscarTakeaway').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.qr-card.takeaway');
    
    cards.forEach(card => {
        const tipo = card.dataset.tipo.toLowerCase();
        
        if (tipo.includes(busqueda)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Limpiar búsqueda TAKE AWAY
function limpiarBusquedaTakeaway() {
    document.getElementById('buscarTakeaway').value = '';
    document.querySelectorAll('.qr-card.takeaway').forEach(card => {
        card.style.display = 'block';
    });
}

// Seleccionar todos los QRs de TAKE AWAY
function toggleSeleccionarTodosTakeaway() {
    const selectAll = document.getElementById('seleccionarTodosTakeaway').checked;
    const checkboxes = document.querySelectorAll('.qr-checkbox-takeaway');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll;
        updateCardSelectionTakeaway(cb);
    });
    
    actualizarContadorSeleccionadosTakeaway();
}

// Actualizar visual de selección TAKE AWAY
function updateCardSelectionTakeaway(checkbox) {
    const card = checkbox.closest('.qr-card');
    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }
}

// Actualizar contador de seleccionados TAKE AWAY
function actualizarContadorSeleccionadosTakeaway() {
    const seleccionados = document.querySelectorAll('.qr-checkbox-takeaway:checked').length;
    document.getElementById('qr-seleccionados').textContent = seleccionados;
    
    document.querySelectorAll('.qr-checkbox-takeaway').forEach(cb => {
        updateCardSelectionTakeaway(cb);
    });
}

// Descargar QR TAKE AWAY individual
function descargarQRTakeaway(tipo, nombre) {
    const container = document.getElementById(`qr-${tipo}`);
    const img = container.querySelector('img');
    
    if (img) {
        const link = document.createElement('a');
        link.download = `QR_${nombre}.png`;
        link.href = img.src;
        link.click();
    } else {
        alert('El QR aún no se ha generado');
    }
}

// Imprimir QR TAKE AWAY individual
function imprimirQRTakeaway(tipo, nombre) {
    const container = document.getElementById(`qr-${tipo}`);
    const img = container.querySelector('img');
    
    if (img) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>QR ${nombre}</title>
                <style>
                    body { 
                        margin: 0; 
                        padding: 20px; 
                        text-align: center; 
                        font-family: Arial, sans-serif;
                    }
                    h1 { 
                        color: #333; 
                        margin-bottom: 20px;
                    }
                    img { 
                        max-width: 400px; 
                        margin: 0 auto;
                    }
                    .info {
                        margin-top: 20px;
                        padding: 10px;
                        background: #f0f0f0;
                        border-radius: 4px;
                    }
                </style>
            </head>
            <body>
                <h1>${nombre}</h1>
                <img src="${img.src}" />
                <div class="info">
                    <strong>Sistema Comanda</strong><br>
                    Escanee el código QR para acceder al menú
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        setTimeout(() => {
            printWindow.print();
        }, 500);
    } else {
        alert('El QR aún no se ha generado');
    }
}
</script>