<?php
// src/views/mesas/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Mesa;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mozos y administradores pueden ver esta página
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];

// Solo administradores pueden eliminar mesas
if (isset($_GET['delete']) && $rol === 'administrador') {
    $id = (int) $_GET['delete'];
    
    if ($id > 0) {
        $mesa = Mesa::find($id);
        
        if ($mesa && $mesa['estado'] === 'libre') {
            // Verificar si la mesa tiene pedidos activos
            if (Mesa::tienePedidosActivos($id)) {
                header('Location: ' . url('mesas', ['error' => '3', 'message' => urlencode('No se puede eliminar una mesa con pedidos activos. Primero debe cerrar todos los pedidos de esta mesa.')]));
            } else {
                $resultado = Mesa::delete($id);
                
                if ($resultado['success']) {
                    header('Location: ' . url('mesas', ['success' => '1']));
                } else {
                    header('Location: ' . url('mesas', ['error' => '3', 'message' => urlencode($resultado['message'])]));
                }
            }
        } else {
            header('Location: ' . url('mesas', ['error' => '1']));
        }
        exit;
    }
    header('Location: ' . url('mesas'));
    exit;
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id_mesa = (int) ($_POST['id_mesa'] ?? 0);
    $nuevo_estado = trim($_POST['nuevo_estado'] ?? '');
    
    if ($id_mesa > 0 && in_array($nuevo_estado, ['libre', 'ocupada', 'reservada'])) {
        $resultado = Mesa::updateEstado($id_mesa, $nuevo_estado);
        if ($resultado) {
            // Devolver respuesta JSON para AJAX
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado']);
        }
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
}

// 1) Cargamos todas las mesas
$mesas = Mesa::all();
?>

<!-- Header de gestión -->
<div class="management-header">
  <h1><?= $rol === 'administrador' ? '🍽️ Gestión de Mesas' : '👁️ Consulta de Mesas' ?></h1>
  <?php if ($rol === 'administrador'): ?>
    <div class="header-actions">
      <a href="<?= url('mesas/create') ?>" class="header-btn">
        ➕ Nueva Mesa
      </a>
      <a href="<?= url('mesas/cambiar-mozo') ?>" class="header-btn secondary">
        🔄 Gestionar asignaciones
      </a>
    </div>
  <?php endif; ?>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="success" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?php if ($_GET['success'] == '1'): ?>
        Mesa eliminada correctamente.
        <?php elseif ($_GET['success'] == '3'): ?>
            Estado de la mesa actualizado correctamente.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="error" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?php if ($_GET['error'] == '1'): ?>
        No se puede eliminar una mesa que está ocupada.
        <?php elseif ($_GET['error'] == '2'): ?>
        Error al eliminar la mesa.
        <?php elseif ($_GET['error'] == '3'): ?>
            <?php if (isset($_GET['message'])): ?>
                <?= htmlspecialchars(urldecode($_GET['message'])) ?>
            <?php else: ?>
                Error al eliminar la mesa.
            <?php endif; ?>
        <?php elseif ($_GET['error'] == '4'): ?>
            Error al cambiar el estado de la mesa.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($rol !== 'administrador'): ?>
  <div style="background: #d1ecf1; padding: 8px; border-radius: 4px; margin-bottom: 1rem; color: #0c5460; font-size: 0.9rem; text-align: center;">
    👁️ Vista de solo lectura - Consulta las mesas disponibles
  </div>
<?php endif; ?>

<!-- Filtros de búsqueda y estado -->
<div class="filters-container">
  <!-- Botón para mostrar/ocultar filtros en móvil -->
  <button id="toggleFilters" class="toggle-filters-btn" onclick="toggleFilters()">
    🔍 Filtros
  </button>
  
  <div id="filtersContent" class="search-filter" style="background: rgb(250, 238, 193); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.6rem; margin-bottom: 1rem; position: relative; z-index: 1;">
  <!-- Filtro por número -->
  <div style="display: flex; align-items: center; gap: 0.3rem; flex-wrap: nowrap; margin-bottom: 0.5rem;">
    <label for="mesaSearch" style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">🔍 Buscar:</label>
    <input type="text" 
           id="mesaSearch" 
           name="mesaSearch"
           placeholder="Número..." 
           style="padding: 0.3rem 0.5rem; border: 1px solid var(--accent); border-radius: 3px; font-size: 0.8rem; min-width: 120px; height: 28px;">
    <button id="clearSearch" 
            type="button"
            style="padding: 0.3rem 0.6rem; background: var(--secondary); color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.75rem; height: 28px;">
      Limpiar
    </button>
  </div>
  
  <!-- Filtro por estado -->
  <div style="display: flex; align-items: center; gap: 0.3rem; flex-wrap: wrap;">
    <label style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">📊 Estado:</label>
    <div class="status-filters" style="display: flex; gap: 0.3rem; flex-wrap: wrap;">
      <button class="status-filter-btn active" data-status="all" style="padding: 4px 8px; border: none; background: var(--secondary); color: white; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s ease;">
        Todas
      </button>
      <button class="status-filter-btn" data-status="libre" style="padding: 4px 8px; border: none; background: #d4edda; color: #155724; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s ease;">
        Libre
      </button>
      <button class="status-filter-btn" data-status="ocupada" style="padding: 4px 8px; border: none; background: #f8d7da; color: #721c24; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s ease;">
        Ocupada
      </button>
      <button class="status-filter-btn" data-status="reservada" style="padding: 4px 8px; border: none; background: #fff3cd; color: #856404; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s ease;">
        Reservada
      </button>
    </div>
  </div>
  
  <div id="searchResults" style="margin-top: 0.3rem; font-size: 0.8rem; color: var(--secondary);"></div>
  </div>
</div>

<!-- Vista de tabla para desktop -->
<div class="table-responsive">
<table class="table">
  <thead>
    <tr>
      <th>Número</th>
      <th>Ubicación</th>
      <th>Estado</th>
      <th>Mozo Asignado</th>
      <th>Cambiar Estado</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mesas as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['numero']) ?></td>
        <td><?= htmlspecialchars($m['ubicacion'] ?? '—') ?></td>
        <td>
          <?php
          // Definir colores según el estado
          $estado = $m['estado'];
          if ($estado === 'libre') {
              $bg_color = '#d4edda';
              $text_color = '#155724';
          } elseif ($estado === 'reservada') {
              $bg_color = '#fff3cd';
              $text_color = '#856404';
          } else { // ocupada
              $bg_color = '#f8d7da';
              $text_color = '#721c24';
          }
          ?>
          <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                       background: <?= $bg_color ?>; 
                       color: <?= $text_color ?>;">
            <?= htmlspecialchars(ucfirst($m['estado'])) ?>
          </span>
        </td>
        <td>
          <?php if (!empty($m['mozo_nombre_completo'])): ?>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                         background: #e2e3e5; color: #383d41;">
              👤 <?= htmlspecialchars($m['mozo_nombre_completo']) ?>
            </span>
          <?php else: ?>
            <span style="color: #6c757d; font-style: italic;">Sin asignar</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="state-shortcuts">
            <?php if ($m['estado'] !== 'libre'): ?>
              <button class="state-btn libre" onclick="cambiarEstado(<?= $m['id_mesa'] ?>, 'libre')" title="Marcar como Libre">
                🟢
              </button>
            <?php endif; ?>
            <?php if ($m['estado'] !== 'ocupada'): ?>
              <button class="state-btn ocupada" onclick="cambiarEstado(<?= $m['id_mesa'] ?>, 'ocupada')" title="Marcar como Ocupada">
                🔴
              </button>
            <?php endif; ?>
            <?php if ($m['estado'] !== 'reservada'): ?>
              <button class="state-btn reservada" onclick="cambiarEstado(<?= $m['id_mesa'] ?>, 'reservada')" title="Marcar como Reservada">
                🟡
              </button>
            <?php endif; ?>
          </div>
        </td>
        <?php if ($rol === 'administrador'): ?>
          <td>
            <a href="<?= url('mesas/edit', ['id' => $m['id_mesa']]) ?>" class="btn-action" title="Editar mesa">
              ✏️
            </a>
            <?php if ($m['estado'] === 'libre'): ?>
              <a href="#" class="btn-action delete"
                 onclick="confirmarBorradoMesa(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>)"
                 title="Eliminar mesa">
                ❌
              </a>
            <?php else: ?>
              <span class="btn-action disabled" 
                    title="No se puede borrar una mesa <?= $m['estado'] ?>">
                ❌
              </span>
            <?php endif; ?>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Vista de tarjetas para móviles -->
<div class="mobile-cards">
  <?php foreach ($mesas as $m): ?>
    <div class="mobile-card">
      <div class="mobile-card-header">
        <div class="mobile-card-number">Mesa <?= htmlspecialchars($m['numero']) ?></div>
        <?php if ($rol === 'administrador'): ?>
          <div class="mobile-card-actions">
            <a href="<?= url('mesas/edit', ['id' => $m['id_mesa']]) ?>" class="btn-action" title="Editar mesa">
              ✏️
            </a>
            <?php if ($m['estado'] === 'libre'): ?>
              <a href="#" class="btn-action delete"
                 onclick="confirmarBorradoMesa(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>)"
                 title="Eliminar mesa">
                ❌
              </a>
            <?php else: ?>
              <span class="btn-action disabled" 
                    title="No se puede borrar una mesa <?= $m['estado'] ?>">
                ❌
              </span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      
      <div class="mobile-card-body">
        <div class="mobile-card-item">
          <div class="mobile-card-label">📍 Ubicación</div>
          <div class="mobile-card-value"><?= htmlspecialchars($m['ubicacion'] ?? '—') ?></div>
        </div>
        
        <div class="mobile-card-item">
          <div class="mobile-card-label">📊 Estado</div>
          <div class="mobile-card-value">
            <?php
            // Definir colores según el estado
            $estado = $m['estado'];
            if ($estado === 'libre') {
                $bg_color = '#d4edda';
                $text_color = '#155724';
            } elseif ($estado === 'reservada') {
                $bg_color = '#fff3cd';
                $text_color = '#856404';
            } else { // ocupada
                $bg_color = '#f8d7da';
                $text_color = '#721c24';
            }
            ?>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                         background: <?= $bg_color ?>; 
                         color: <?= $text_color ?>;">
              <?= htmlspecialchars(ucfirst($m['estado'])) ?>
            </span>
          </div>
        </div>
        
        <div class="mobile-card-item">
          <div class="mobile-card-label">👤 Mozo</div>
          <div class="mobile-card-value">
            <?php if (!empty($m['mozo_nombre_completo'])): ?>
              <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                           background: #e2e3e5; color: #383d41;">
                <?= htmlspecialchars($m['mozo_nombre_completo']) ?>
              </span>
            <?php else: ?>
              <span style="color: #6c757d; font-style: italic;">Sin asignar</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <div class="mobile-state-shortcuts">
        <div class="mobile-card-label" style="margin-bottom: 0.3rem;">🔄 Cambiar Estado:</div>
        <div class="state-shortcuts">
          <?php if ($m['estado'] !== 'libre'): ?>
            <button class="state-btn libre" onclick="cambiarEstado(<?= $m['id_mesa'] ?>, 'libre')" title="Marcar como Libre">
              🟢 Libre
            </button>
          <?php endif; ?>
          <?php if ($m['estado'] !== 'ocupada'): ?>
            <button class="state-btn ocupada" onclick="cambiarEstado(<?= $m['id_mesa'] ?>, 'ocupada')" title="Marcar como Ocupada">
              🔴 Ocupada
            </button>
          <?php endif; ?>
          <?php if ($m['estado'] !== 'reservada'): ?>
            <button class="state-btn reservada" onclick="cambiarEstado(<?= $m['id_mesa'] ?>, 'reservada')" title="Marcar como Reservada">
              🟡 Reservada
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>



<script>
// Variables globales para el modal de cambio de estado
let mesaIdParaCambiar = null;
let nuevoEstadoParaCambiar = null;

// Efectos hover para los botones
document.getElementById('btnCancelar').addEventListener('mouseenter', function() {
    this.style.background = '#6c757d';
    this.style.color = 'white';
    this.style.borderColor = '#5a6268';
    this.style.boxShadow = '0 4px 8px rgba(108, 117, 125, 0.3)';
    this.style.opacity = '0.95';
});

document.getElementById('btnCancelar').addEventListener('mouseleave', function() {
    this.style.background = 'white';
    this.style.color = '#6c757d';
    this.style.borderColor = '#6c757d';
    this.style.boxShadow = '0 2px 4px rgba(108, 117, 125, 0.2)';
    this.style.opacity = '1';
});

document.getElementById('btnConfirmar').addEventListener('mouseenter', function() {
    this.style.background = '#c82333';
    this.style.borderColor = '#bd2130';
    this.style.boxShadow = '0 4px 8px rgba(220, 53, 69, 0.4)';
    this.style.opacity = '0.95';
});

document.getElementById('btnConfirmar').addEventListener('mouseleave', function() {
    this.style.background = '#dc3545';
    this.style.borderColor = '#dc3545';
    this.style.boxShadow = '0 2px 4px rgba(220, 53, 69, 0.3)';
    this.style.opacity = '1';
});

// Funcionalidad de búsqueda y filtrado de mesas
function initMesasFilters() {
    console.log('=== INICIANDO FILTROS DE MESAS ===');
    
    // Buscar elementos con timeout para asegurar que el DOM esté listo
    const searchInput = document.getElementById('mesaSearch');
    const clearButton = document.getElementById('clearSearch');
    const searchResults = document.getElementById('searchResults');
    const tableRows = document.querySelectorAll('.table tbody tr');
    const mobileCards = document.querySelectorAll('.mobile-card');
    const statusButtons = document.querySelectorAll('.status-filter-btn');
    
    console.log('=== ELEMENTOS ENCONTRADOS ===');
    console.log('searchInput:', searchInput);
    console.log('clearButton:', clearButton);
    console.log('searchResults:', searchResults);
    console.log('tableRows count:', tableRows.length);
    console.log('mobileCards count:', mobileCards.length);
    console.log('statusButtons count:', statusButtons.length);
    
    // Verificar si los elementos existen
    if (!searchInput || !clearButton || !searchResults) {
        console.error('❌ ERROR: Elementos de filtro no encontrados');
        console.log('Elementos disponibles:', document.querySelectorAll('[id]'));
        return false;
    }
    
    let currentSearchTerm = '';
    let currentStatusFilter = 'all';
    
    function getMesaStatus(row) {
        // Buscar el span de estado en la tercera columna
        const statusCell = row.querySelector('td:nth-child(3)');
        if (statusCell) {
            const statusSpan = statusCell.querySelector('span');
        if (statusSpan) {
                const statusText = statusSpan.textContent.toLowerCase().trim();
                return statusText;
            }
        }
        return '';
    }
    
    function getMesaStatusFromCard(card) {
        // Buscar el estado en la tarjeta móvil
        const statusItems = card.querySelectorAll('.mobile-card-item');
        for (let item of statusItems) {
            const label = item.querySelector('.mobile-card-label');
            if (label && label.textContent.includes('Estado')) {
                const statusSpan = item.querySelector('.mobile-card-value span');
        if (statusSpan) {
                    const statusText = statusSpan.textContent.toLowerCase().trim();
                    return statusText;
                }
            }
        }
        return '';
    }
    
    function filterMesas() {
        console.log('=== FILTRANDO MESAS ===');
        console.log('Término de búsqueda:', currentSearchTerm);
        console.log('Filtro de estado:', currentStatusFilter);
        
        const searchTerm = currentSearchTerm.toLowerCase().trim();
        const statusFilter = currentStatusFilter;
        let visibleCount = 0;
        
        // Filtrar filas de la tabla
        tableRows.forEach((row, index) => {
            const firstCell = row.querySelector('td:first-child');
            if (!firstCell) {
                console.error(`Fila ${index}: No se encontró la primera celda`);
                return;
            }
            
            const mesaNumber = firstCell.textContent.toLowerCase();
            const mesaStatus = getMesaStatus(row);
            
            const matchesSearch = searchTerm === '' || mesaNumber.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || mesaStatus === statusFilter;
            
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Filtrar tarjetas móviles
        mobileCards.forEach((card, index) => {
            const numberElement = card.querySelector('.mobile-card-number');
            if (!numberElement) {
                console.error(`Tarjeta ${index}: No se encontró el elemento de número`);
                return;
            }
            
            const mesaNumber = numberElement.textContent.toLowerCase();
            const mesaStatus = getMesaStatusFromCard(card);
            
            const matchesSearch = searchTerm === '' || mesaNumber.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || mesaStatus === statusFilter;
            
            if (matchesSearch && matchesStatus) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Mostrar resultados
        let resultText = `Mostrando ${visibleCount} mesa(s)`;
        if (searchTerm !== '' && statusFilter !== 'all') {
            resultText += ` que coinciden con "${searchTerm}" y estado "${statusFilter}"`;
        } else if (searchTerm !== '') {
            resultText += ` que coinciden con "${searchTerm}"`;
        } else if (statusFilter !== 'all') {
            resultText += ` con estado "${statusFilter}"`;
        }
        
        searchResults.textContent = resultText;
        console.log('=== RESULTADO FINAL ===');
        console.log('Mesas visibles:', visibleCount);
    }
    
    // Event listener para el input de búsqueda
    searchInput.addEventListener('input', function() {
        console.log('Input cambiado:', this.value);
        currentSearchTerm = this.value;
        filterMesas();
    });
    
    // Event listener para el botón limpiar
    clearButton.addEventListener('click', function() {
        console.log('Botón limpiar clickeado');
        searchInput.value = '';
        currentSearchTerm = '';
        filterMesas();
        searchInput.focus();
    });
    
    // Event listeners para los botones de estado
    statusButtons.forEach((button, index) => {
        console.log(`Configurando botón ${index}:`, button.dataset.status);
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Botón de estado clickeado:', this.dataset.status);
            
            // Remover clase active de todos los botones
            statusButtons.forEach(btn => {
                btn.classList.remove('active');
                // Restaurar estilos originales según el estado
                const status = btn.getAttribute('data-status');
                if (status === 'all') {
                    btn.style.background = 'var(--secondary)';
                    btn.style.color = 'white';
                } else if (status === 'libre') {
                    btn.style.background = '#d4edda';
                    btn.style.color = '#155724';
                } else if (status === 'ocupada') {
                    btn.style.background = '#f8d7da';
                    btn.style.color = '#721c24';
                } else if (status === 'reservada') {
                    btn.style.background = '#fff3cd';
                    btn.style.color = '#856404';
                }
            });
            
            // Agregar clase active al botón clickeado
            this.classList.add('active');
            
            // Actualizar filtro de estado
            currentStatusFilter = this.dataset.status;
            console.log('Filtro de estado actualizado a:', currentStatusFilter);
            filterMesas();
        });
        
        // También agregar un event listener de mouse para debugging
        button.addEventListener('mousedown', function() {
            console.log('Botón presionado:', this.dataset.status);
        });
    });
    
    // Permitir búsqueda con Enter
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            currentSearchTerm = this.value;
            filterMesas();
        }
    });
    
    // Ejecutar filtro inicial
    console.log('Ejecutando filtro inicial...');
    filterMesas();
    
    return true;
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando filtros...');
    
    // Test simple para verificar que JavaScript funciona
    console.log('✅ JavaScript funcionando correctamente');
    
    // Intentar inicializar inmediatamente
    if (!initMesasFilters()) {
        // Si falla, intentar después de un pequeño delay
        console.log('Filtros no inicializados, reintentando en 100ms...');
        setTimeout(function() {
            if (!initMesasFilters()) {
                console.log('Filtros no inicializados, reintentando en 500ms...');
                setTimeout(function() {
                    initMesasFilters();
                }, 500);
            }
        }, 100);
    }
});

// Función de prueba global
function testFilters() {
    console.log('=== TEST DE FILTROS ===');
    const searchInput = document.getElementById('mesaSearch');
    const clearButton = document.getElementById('clearSearch');
    const statusButtons = document.querySelectorAll('.status-filter-btn');
    
    console.log('searchInput:', searchInput);
    console.log('clearButton:', clearButton);
    console.log('statusButtons:', statusButtons.length);
    
    if (searchInput) {
        console.log('✅ Campo de búsqueda encontrado');
    } else {
        console.log('❌ Campo de búsqueda NO encontrado');
    }
    
    if (clearButton) {
        console.log('✅ Botón limpiar encontrado');
    } else {
        console.log('❌ Botón limpiar NO encontrado');
    }
    
    if (statusButtons.length > 0) {
        console.log('✅ Botones de estado encontrados:', statusButtons.length);
    } else {
        console.log('❌ Botones de estado NO encontrados');
    }
}

// Función para cambiar el estado de una mesa
function cambiarEstado(idMesa, nuevoEstado) {
    mesaIdParaCambiar = idMesa;
    nuevoEstadoParaCambiar = nuevoEstado;
    
    // Usar el modal personalizado estilizado
    confirmarCambioEstadoMesa(idMesa, nuevoEstado, (id, estado) => {
        // Función callback que se ejecuta al confirmar
        cambiarEstadoConfirmado(id, estado);
    });
}

// Función para confirmar el cambio de estado (llamada desde el modal)
function cambiarEstadoConfirmado(idMesa, nuevoEstado) {
    if (idMesa && nuevoEstado) {
        // Enviar petición AJAX
        const formData = new FormData();
        formData.append('cambiar_estado', '1');
        formData.append('id_mesa', idMesa);
        formData.append('nuevo_estado', nuevoEstado);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Status:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            console.log('Respuesta del servidor (texto):', text);
            try {
                const data = JSON.parse(text);
                console.log('Respuesta del servidor (JSON):', data);
                if (data.success) {
                    // Recargar la página para mostrar los cambios
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                console.log('Respuesta no es JSON válida:', text);
                // Si no es JSON válido, recargar la página
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cambiar el estado de la mesa: ' + error.message);
        });
        
        // Limpiar variables
        mesaIdParaCambiar = null;
        nuevoEstadoParaCambiar = null;
    }
}


// Función para mostrar/ocultar filtros en móvil
function toggleFilters() {
    const filtersContent = document.getElementById('filtersContent');
    const toggleBtn = document.getElementById('toggleFilters');
    
    if (filtersContent.style.display === 'none' || filtersContent.style.display === '') {
        filtersContent.style.display = 'block';
        toggleBtn.innerHTML = '🔍 Ocultar Filtros';
    } else {
        filtersContent.style.display = 'none';
        toggleBtn.innerHTML = '🔍 Filtros';
    }
}
</script>

<!-- Incluir CSS y JS del modal de confirmación -->
<link rel="stylesheet" href="<?= url('assets/css/modal-confirmacion.css') ?>">
<script src="<?= url('assets/js/modal-confirmacion.js') ?>"></script>

<style>
/* Estilos para filtros desplegables en móvil */
.toggle-filters-btn {
    display: none;
    width: 100%;
    padding: 0.5rem;
    background: var(--secondary);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: normal;
    cursor: pointer;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.toggle-filters-btn:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.filters-container {
    margin-bottom: 1rem;
}

/* En móvil, ocultar filtros por defecto y mostrar botón */
@media (max-width: 768px) {
    .toggle-filters-btn {
        display: block;
        padding: 0.3rem !important;
        font-size: 0.8rem !important;
    }
    
    #filtersContent {
        display: none;
    }
    
    /* Cuando se muestran los filtros en móvil,  que ocupen menos espacio */
    .search-filter {
        padding: 0.3rem !important;
    }
    
    .search-filter .filter-group {
        margin-bottom: 0.3rem;
    }
    
    .search-filter input,
    .search-filter select {
        font-size: 0.7rem !important;
        padding: 0.2rem !important;
        height: 24px !important;
    }
    
    .search-filter .status-filters {
        gap: 0.15rem !important;
    }
    
    .search-filter .status-filter-btn {
        font-size: 0.6rem !important;
        padding: 2px 4px !important;
    }
    
    /* Reducir tamaño general de elementos en móvil */
    .table {
        font-size: 0.7rem !important;
    }
    
    .table th,
    .table td {
        padding: 0.3rem !important;
        font-size: 0.7rem !important;
    }
    
    .btn-action {
        padding: 0.2rem 0.4rem !important;
        font-size: 0.7rem !important;
    }
    
    .state-btn {
        padding: 2px 4px !important;
        font-size: 0.6rem !important;
    }
    
    .mobile-cards {
        padding: 0.4rem !important;
    }
    
    .mobile-cards .card-title {
        font-size: 0.8rem !important;
    }
    
    .mobile-cards .card-text {
        font-size: 0.7rem !important;
    }
    
    .action-buttons {
        margin-bottom: 0.5rem !important;
    }
    
    .action-buttons .button {
        padding: 0.3rem 0.6rem !important;
        font-size: 0.7rem !important;
    }
    
    h1 {
        font-size: 1.2rem !important;
    }
    
    h2 {
        font-size: 1rem !important;
    }
    
    h3 {
        font-size: 0.9rem !important;
    }
}

/* En desktop, mostrar filtros normalmente */
@media (min-width: 769px) {
    .toggle-filters-btn {
        display: none;
    }
    
    #filtersContent {
        display: block !important;
    }
}

/* Estilos para el header de gestión */
.management-header {
  background: linear-gradient(135deg, rgb(144, 104, 76), rgb(92, 64, 51));
  color: white !important;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 12px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.management-header * {
  color: white !important;
}

.management-header h1 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
  color: white !important;
  flex: 1;
  min-width: 200px;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.header-btn {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  color: white !important;
  text-decoration: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.3);
  white-space: nowrap;
}

.header-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  color: white !important;
}

.header-btn.secondary {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.header-btn.secondary:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Responsive para móvil */
@media (max-width: 768px) {
  .management-header {
    padding: 8px;
    margin-bottom: 8px;
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }
  
  .management-header h1 {
    font-size: 1.1rem;
    text-align: center;
    margin-bottom: 0.5rem;
  }
  
  .header-actions {
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .header-btn {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
  }
}
</style>
