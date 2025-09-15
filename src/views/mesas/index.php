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
        // Validación especial: no permitir cambiar a "libre" si hay pedidos activos
        if ($nuevo_estado === 'libre' && Mesa::tienePedidosActivos($id_mesa)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'No se puede marcar la mesa como libre porque tiene pedidos activos. Primero debe cerrar todos los pedidos de esta mesa.'
            ]);
            exit;
        }
        
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

// 1) Cargamos todas las mesas (activas e inactivas)
$mesasActivas = Mesa::all();
$mesasInactivas = Mesa::allInactive();
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

<!-- Sistema de notificaciones temporales -->
<div id="notification-container"></div>


<?php if ($rol !== 'administrador'): ?>
  <div style="background: #d1ecf1; padding: 8px; border-radius: 4px; margin-bottom: 1rem; color: #0c5460; font-size: 0.9rem; text-align: center;">
    👁️ Vista de solo lectura - Consulta las mesas disponibles
  </div>
<?php endif; ?>

<!-- Filtros de búsqueda y estado -->
<div class="filters-container">
  <!-- Botón para mostrar/ocultar filtros en móvil -->
  <button id="toggleFilters" class="toggle-filters-btn" onclick="toggleFilters()">
    Filtrar
  </button>
  
  <div id="filtersContent" class="search-filter" style="background: rgb(250, 238, 193); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.6rem; margin-bottom: 1rem; position: relative; z-index: 1; display: none;">
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
      <button class="status-filter-btn" data-status="inactiva" style="padding: 4px 8px; border: none; background: #dc3545; color: white; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s ease;">
        Inactiva
      </button>
    </div>
  </div>
  
  <div id="searchResults" style="margin-top: 0.3rem; font-size: 0.8rem; color: var(--secondary);"></div>
  </div>
</div>

<!-- Pestañas para mesas activas e inactivas -->
<div class="tabs-container">
  <div class="tabs">
    <button class="tab-button active" onclick="showTab('activas')">
      🟢 Mesas Activas (<?= count($mesasActivas) ?>)
    </button>
    <button class="tab-button" onclick="showTab('inactivas')">
      🔴 Mesas Inactivas (<?= count($mesasInactivas) ?>)
    </button>
  </div>
</div>

<!-- Vista de tabla para desktop -->
<div class="table-responsive" id="mesas-activas">
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
    <?php foreach ($mesasActivas as $m): ?>
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
              <?php 
              $tienePedidosActivos = ($m['pedidos_activos'] ?? 0) > 0;
              $disabled = $tienePedidosActivos ? 'disabled' : '';
              $title = $tienePedidosActivos ? 
                'No se puede marcar como libre (tiene ' . $m['pedidos_activos'] . ' pedido(s) activo(s))' : 
                'Marcar como Libre';
              $class = $tienePedidosActivos ? 'state-btn libre disabled' : 'state-btn libre';
              ?>
              <button class="<?= $class ?>" onclick="<?= $tienePedidosActivos ? 'void(0)' : 'cambiarEstado(' . $m['id_mesa'] . ', \'libre\')' ?>" title="<?= $title ?>" <?= $disabled ?>>
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
              <a href="javascript:void(0)" class="btn-action delete"
                 onclick="confirmarBorradoMesa(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>); return false;"
                 title="Desactivar mesa">
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

<!-- Tabla de mesas inactivas (oculta por defecto) -->
<div class="table-responsive" id="mesas-inactivas" style="display: none;">
<table class="table">
  <thead>
    <tr>
      <th>Número</th>
      <th>Ubicación</th>
      <th>Estado</th>
      <th>Mozo Asignado</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mesasInactivas as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['numero']) ?></td>
        <td><?= htmlspecialchars($m['ubicacion'] ?? '—') ?></td>
        <td>
          <span class="status-badge status-inactiva">🔴 Inactiva</span>
        </td>
        <td>
          <?php if ($m['mozo_nombre_completo']): ?>
            <?= htmlspecialchars($m['mozo_nombre_completo']) ?>
          <?php else: ?>
            <span class="text-muted">Sin asignar</span>
          <?php endif; ?>
        </td>
        <?php if ($rol === 'administrador'): ?>
          <td class="actions">
            <a href="javascript:void(0)" class="btn-action reactivate"
               onclick="confirmarReactivacionMesa(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>); return false;"
               title="Reactivar mesa">
              ✅
            </a>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Vista de tarjetas para móviles -->
<div class="mobile-cards">
  <?php foreach ($mesasActivas as $m): ?>
    <div class="mobile-card mesa-activa">
      <div class="mobile-card-header">
        <div class="mobile-card-number">Mesa <?= htmlspecialchars($m['numero']) ?></div>
        <?php if ($rol === 'administrador'): ?>
          <div class="mobile-card-actions">
            <a href="<?= url('mesas/edit', ['id' => $m['id_mesa']]) ?>" class="btn-action" title="Editar mesa">
              ✏️
            </a>
            <?php if ($m['estado'] === 'libre'): ?>
              <a href="javascript:void(0)" class="btn-action delete"
                 onclick="confirmarBorradoMesa(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>); return false;"
                 title="Desactivar mesa">
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
            <?php 
            $tienePedidosActivos = ($m['pedidos_activos'] ?? 0) > 0;
            $disabled = $tienePedidosActivos ? 'disabled' : '';
            $title = $tienePedidosActivos ? 
              'No se puede marcar como libre (tiene ' . $m['pedidos_activos'] . ' pedido(s) activo(s))' : 
              'Marcar como Libre';
            $class = $tienePedidosActivos ? 'state-btn libre disabled' : 'state-btn libre';
            ?>
            <button class="<?= $class ?>" onclick="<?= $tienePedidosActivos ? 'void(0)' : 'cambiarEstado(' . $m['id_mesa'] . ', \'libre\')' ?>" title="<?= $title ?>" <?= $disabled ?>>
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
  
  <!-- Tarjetas móviles para mesas inactivas (ocultas por defecto) -->
  <?php foreach ($mesasInactivas as $m): ?>
    <div class="mobile-card mesa-inactiva" style="display: none;">
      <div class="mobile-card-header">
        <div class="mobile-card-number">Mesa <?= htmlspecialchars($m['numero']) ?></div>
        <?php if ($rol === 'administrador'): ?>
          <div class="mobile-card-actions">
            <a href="javascript:void(0)" class="btn-action reactivate"
               onclick="confirmarReactivacionMesa(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>); return false;"
               title="Reactivar mesa">
              ✅
            </a>
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
            <span class="status-badge status-inactiva">🔴 Inactiva</span>
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
              <span class="text-muted">Sin asignar</span>
            <?php endif; ?>
          </div>
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
        // Si es una tarjeta inactiva, devolver 'inactiva'
        if (card.classList.contains('mesa-inactiva')) {
            return 'inactiva';
        }
        
        // Buscar el estado en la tarjeta móvil activa
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
            
            // Verificar si la tarjeta debe mostrarse según la pestaña activa
            const isActiveTab = document.querySelector('.tab-button.active').onclick.toString().includes('activas');
            const isInactiveTab = document.querySelector('.tab-button.active').onclick.toString().includes('inactivas');
            
            let shouldShow = false;
            if (isActiveTab && card.classList.contains('mesa-activa')) {
                shouldShow = true;
            } else if (isInactiveTab && card.classList.contains('mesa-inactiva')) {
                shouldShow = true;
            }
            
            if (matchesSearch && matchesStatus && shouldShow) {
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
                    // Mostrar notificación de éxito
                    showNotification('Estado de la mesa actualizado correctamente', 'success', 4000);
                    // Recargar la página para mostrar los cambios
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    // Mostrar notificación de error con el mensaje específico
                    showNotification(data.message || 'Error al cambiar el estado de la mesa', 'error', 6000);
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
            showNotification('Error al cambiar el estado de la mesa: ' + error.message, 'error', 6000);
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
        toggleBtn.innerHTML = 'Ocultar Filtros';
    } else {
        filtersContent.style.display = 'none';
        toggleBtn.innerHTML = 'Filtrar';
    }
}

// Sistema de notificaciones temporales
function showNotification(message, type = 'success', duration = 4000) {
  const container = document.getElementById('notification-container');
  
  // Crear elemento de notificación
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  
  // Icono según el tipo y mensaje
  let icon = '✅';
  if (type === 'error') {
    // Si es un mensaje de eliminación, usar icono de basura
    if (message.toLowerCase().includes('eliminada') || message.toLowerCase().includes('eliminado')) {
      icon = '🗑️';
    } else {
      icon = '❌';
    }
  }
  
  notification.innerHTML = `
    <span class="notification-icon">${icon}</span>
    <span class="notification-content">${message}</span>
    <button class="notification-close" onclick="closeNotification(this)">×</button>
  `;
  
  // Agregar al contenedor
  container.appendChild(notification);
  
  // Mostrar con animación
  setTimeout(() => {
    notification.classList.add('show');
  }, 100);
  
  // Auto-eliminar después del tiempo especificado
  setTimeout(() => {
    closeNotification(notification.querySelector('.notification-close'));
  }, duration);
}

function closeNotification(closeButton) {
  const notification = closeButton.closest('.notification');
  if (notification) {
    notification.classList.remove('show');
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 400);
  }
}

// Verificar si hay mensajes en la URL y mostrarlos como notificaciones
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  
  if (urlParams.has('success')) {
    let message = '';
    const successCode = urlParams.get('success');
    
    if (successCode == '1') {
      message = 'Mesa desactivada correctamente.';
    } else if (successCode == '2') {
      message = 'Mesa reactivada correctamente.';
    } else if (successCode == '3') {
      message = 'Estado de la mesa actualizado correctamente.';
    }
    
    if (message) {
      // Determinar el tipo de notificación basado en el mensaje
      let notificationType = 'success';
      let duration = 5000;
      
      // Si el mensaje es de desactivación, usar estilo de error (rojo)
      if (message.toLowerCase().includes('desactivada')) {
        notificationType = 'error';
        duration = 6000;
      } else if (message.toLowerCase().includes('reactivada')) {
        // Los mensajes de reactivación son éxito (verde)
        notificationType = 'success';
        duration = 5000;
      }
      
      showNotification(message, notificationType, duration);
    }
    
    // Limpiar la URL sin recargar la página
    const newUrl = window.location.pathname + window.location.search.replace(/[?&]success=[^&]*/, '').replace(/[?&]error=[^&]*/, '');
    if (newUrl.endsWith('?')) {
      window.history.replaceState({}, '', newUrl.slice(0, -1));
    } else {
      window.history.replaceState({}, '', newUrl);
    }
  }
  
  if (urlParams.has('error')) {
    let message = '';
    const errorCode = urlParams.get('error');
    
    if (errorCode == '1') {
      message = 'No se puede eliminar una mesa que está ocupada.';
    } else if (errorCode == '2') {
      message = 'Error al eliminar la mesa.';
    } else if (errorCode == '3') {
      if (urlParams.has('message')) {
        message = decodeURIComponent(urlParams.get('message'));
      } else {
        message = 'Error al eliminar la mesa.';
      }
    } else if (errorCode == '4') {
      message = 'Error al cambiar el estado de la mesa.';
    }
    
    if (message) {
      showNotification(message, 'error', 6000);
    }
    
    // Limpiar la URL sin recargar la página
    const newUrl = window.location.pathname + window.location.search.replace(/[?&]success=[^&]*/, '').replace(/[?&]error=[^&]*/, '');
    if (newUrl.endsWith('?')) {
      window.history.replaceState({}, '', newUrl.slice(0, -1));
    } else {
      window.history.replaceState({}, '', newUrl);
    }
  }
});

console.log('Script de mesas cargado correctamente');

// Función para cambiar entre pestañas
function showTab(tabName) {
  // Ocultar todas las secciones de desktop
  document.getElementById('mesas-activas').style.display = 'none';
  document.getElementById('mesas-inactivas').style.display = 'none';
  
  // Ocultar todas las tarjetas móviles
  const mobileCards = document.querySelectorAll('.mobile-card');
  mobileCards.forEach(card => {
    card.style.display = 'none';
  });
  
  // Remover clase active de todos los botones
  document.querySelectorAll('.tab-button').forEach(btn => {
    btn.classList.remove('active');
  });
  
  // Mostrar la sección seleccionada
  if (tabName === 'activas') {
    // Mostrar tabla de desktop
    document.getElementById('mesas-activas').style.display = 'block';
    
    // Mostrar tarjetas móviles activas
    mobileCards.forEach(card => {
      if (card.classList.contains('mesa-activa')) {
        card.style.display = 'block';
      }
    });
    
    document.querySelector('.tab-button[onclick="showTab(\'activas\')"]').classList.add('active');
  } else if (tabName === 'inactivas') {
    // Mostrar tabla de desktop
    document.getElementById('mesas-inactivas').style.display = 'block';
    
    // Mostrar tarjetas móviles inactivas
    mobileCards.forEach(card => {
      if (card.classList.contains('mesa-inactiva')) {
        card.style.display = 'block';
      }
    });
    
    document.querySelector('.tab-button[onclick="showTab(\'inactivas\')"]').classList.add('active');
  }
}

// Función para reactivar mesa (usando el modal existente)
function confirmarReactivacionMesa(id, numero) {
  console.log('confirmarReactivacionMesa llamado con:', id, numero);
  
  ModalConfirmacion.show({
    title: '✅ Reactivar Mesa',
    message: '¿Estás seguro de que quieres reactivar esta mesa?',
    itemName: `Mesa #${numero}`,
    note: 'La mesa volverá a aparecer en las listas y estará disponible para asignar mozos y recibir pedidos.',
    confirmText: '✅ Reactivar',
    cancelText: '❌ Cancelar',
    onConfirm: () => {
      // Crear un formulario temporal para enviar la solicitud de reactivación
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = window.location.origin + window.location.pathname + '?route=mesas/reactivate';
      
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'id';
      input.value = id;
      
      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    },
    onCancel: () => {
      console.log('Reactivación de mesa cancelada');
    }
  });
}
</script>

<!-- Incluir CSS y JS del modal de confirmación -->
<link rel="stylesheet" href="<?= url('assets/css/modal-confirmacion.css') ?>">
<script src="<?= url('assets/js/modal-confirmacion.js') ?>"></script>

<style>
/* Estilos para filtros desplegables en móvil */
.toggle-filters-btn {
    display: block;
    width: 100%;
    padding: 0.6rem 1rem;
    background: linear-gradient(135deg, rgb(240, 196, 118) 0%, rgb(135, 98, 34) 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toggle-filters-btn:hover {
    background: linear-gradient(135deg, rgb(190, 141, 56) 0%, rgb(170, 125, 50) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.filters-container {
    background: rgb(238, 224, 191);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    overflow: hidden;
}

#filtersContent {
    display: none;
    padding: 1rem;
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
        gap: 0.5rem;
    }
    
    .mobile-card {
        padding: 0.6rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .mobile-card-header {
        margin-bottom: 0.4rem !important;
    }
    
    .mobile-card-number {
        font-size: 0.8rem !important;
    }
    
    .mobile-card-item {
        margin-bottom: 0.3rem !important;
    }
    
    .mobile-card-label {
        font-size: 0.7rem !important;
    }
    
    .mobile-card-value {
        font-size: 0.75rem !important;
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

/* En desktop, mantener botón visible */
@media (min-width: 769px) {
    .toggle-filters-btn {
        display: block;
    }
    
    #filtersContent {
        display: none;
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
@media (max-width: 992px) {
  .management-header {
    padding: 10px;
    margin-bottom: 10px;
  }
  
  .management-header h1 {
    font-size: 1.1rem;
  }
  
  .header-btn {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
  }
}

@media (max-width: 768px) {
  .management-header {
    padding: 8px;
    margin-bottom: 8px;
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }
  
  .management-header h1 {
    font-size: 0.9rem;
    text-align: center;
    margin-bottom: 0.5rem;
  }
  
  .header-actions {
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .header-btn {
    font-size: 0.7rem;
    padding: 0.3rem 0.6rem;
  }
  
  .table {
    font-size: 0.85rem;
  }
  
  .table th,
  .table td {
    padding: 0.5rem 0.3rem;
  }
}

@media (max-width: 480px) {
  .management-header {
    padding: 6px;
    margin-bottom: 6px;
  }
  
  .management-header h1 {
    font-size: 0.8rem;
  }
  
  .header-btn {
    font-size: 0.65rem;
    padding: 0.25rem 0.5rem;
  }
  
  .table {
    font-size: 0.8rem;
  }
  
  .table th,
  .table td {
    padding: 0.4rem 0.2rem;
  }
  
  .btn-action {
    min-width: 24px;
    height: 24px;
    font-size: 0.7rem;
  }
}

/* Estilos para notificaciones temporales */
#notification-container {
  position: fixed;
  top: 16px;
  right: 16px;
  z-index: 2000;
  max-width: 380px;
}

.notification {
  background: white;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 10px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
  border-left: 3px solid;
  display: flex;
  align-items: center;
  gap: 10px;
  transform: translateX(100%);
  opacity: 0;
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  position: relative;
  overflow: hidden;
}

.notification.show {
  transform: translateX(0);
  opacity: 1;
}

.notification.success {
  border-left-color: #28a745;
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
}

.notification.error {
  border-left-color: #dc3545;
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
}

.notification-icon {
  font-size: 1.3rem;
  flex-shrink: 0;
}

.notification-content {
  flex: 1;
  color: #333;
  font-weight: 500;
  font-size: 0.9rem;
  line-height: 1.3;
}

.notification-close {
  background: none;
  border: none;
  font-size: 1.1rem;
  color: #666;
  cursor: pointer;
  padding: 3px;
  border-radius: 3px;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.notification-close:hover {
  background: rgba(0, 0, 0, 0.1);
  color: #333;
}

/* Efecto de progreso */
.notification::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: currentColor;
  opacity: 0.3;
  animation: progress 4s linear forwards;
}

.notification.success::after {
  background: #28a745;
}

.notification.error::after {
  background: #dc3545;
}

@keyframes progress {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

/* Responsive para notificaciones */
@media (max-width: 768px) {
  #notification-container {
    top: 12px;
    right: 12px;
    left: 12px;
    max-width: none;
  }
  
  .notification {
    padding: 10px 14px;
    margin-bottom: 8px;
  }
  
  .notification-icon {
    font-size: 1.2rem;
  }
  
  .notification-content {
    font-size: 0.85rem;
  }
}

@media (max-width: 480px) {
  #notification-container {
    top: 8px;
    right: 8px;
    left: 8px;
  }
  
  .notification {
    padding: 8px 12px;
    margin-bottom: 6px;
    border-radius: 8px;
  }
  
  .notification-icon {
    font-size: 1.1rem;
  }
  
  .notification-content {
    font-size: 0.8rem;
  }
  
  .notification-close {
    font-size: 1rem;
    padding: 2px;
  }
}

/* Estilos para pestañas */
.tabs-container {
  margin-bottom: 1.2rem;
  border-bottom: 2px solid #e9ecef;
}

.tabs {
  display: flex;
  gap: 0;
}

.tab-button {
  background: none;
  border: none;
  padding: 10px 20px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 500;
  color: #6c757d;
  border-bottom: 3px solid transparent;
  transition: all 0.3s ease;
  position: relative;
}

.tab-button:hover {
  color: #495057;
  background: #f8f9fa;
}

.tab-button.active {
  color: #007bff;
  border-bottom-color: #007bff;
  background: #f8f9fa;
}

.tab-button.active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 3px;
  background: #007bff;
}

/* Estilos para estado inactivo */
.status-inactiva {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  padding: 3px 10px;
  border-radius: 16px;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

/* Estilos para botón de reactivar */
.btn-action.reactivate {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  border-radius: 5px;
  padding: 5px 8px;
  text-decoration: none;
  font-size: 0.8rem;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
}

.btn-action.reactivate:hover {
  background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
  color: white;
  text-decoration: none;
}

/* Responsive para pestañas */
@media (max-width: 992px) {
  .tab-button {
    padding: 8px 16px;
    font-size: 0.85rem;
  }
}

@media (max-width: 768px) {
  .tabs-container {
    margin-bottom: 1rem;
  }
  
  .tabs {
    flex-direction: column;
    gap: 0;
  }
  
  .tab-button {
    padding: 10px 16px;
    font-size: 0.8rem;
    border-bottom: 1px solid #e9ecef;
    border-radius: 0;
    text-align: left;
  }
  
  .tab-button.active {
    border-bottom-color: #007bff;
    background: #e3f2fd;
  }
  
  .tab-button:hover {
    background: #f5f5f5;
  }
}

@media (max-width: 480px) {
  .tabs-container {
    margin-bottom: 0.8rem;
  }
  
  .tab-button {
    padding: 8px 12px;
    font-size: 0.75rem;
  }
  
  .status-inactiva {
    padding: 2px 8px;
    font-size: 0.7rem;
    border-radius: 12px;
  }
  
  .btn-action.reactivate {
    min-width: 24px;
    height: 24px;
    font-size: 0.7rem;
    padding: 4px 6px;
  }
}

@media (max-width: 360px) {
  .tabs-container {
    margin-bottom: 0.6rem;
  }
  
  .tab-button {
    padding: 6px 8px;
    font-size: 0.7rem;
  }
  
  .management-header h1 {
    font-size: 0.75rem;
  }
  
  .header-btn {
    font-size: 0.6rem;
    padding: 0.2rem 0.4rem;
  }
  
  .table {
    font-size: 0.75rem;
  }
  
  .table th,
  .table td {
    padding: 0.3rem 0.1rem;
  }
  
  .btn-action {
    min-width: 20px;
    height: 20px;
    font-size: 0.6rem;
  }
  
  .mobile-card {
    padding: 0.4rem !important;
  }
  
  .mobile-card-number {
    font-size: 0.75rem !important;
  }
  
  .mobile-card-label {
    font-size: 0.65rem !important;
  }
  
  .mobile-card-value {
    font-size: 0.7rem !important;
  }
  
  .status-inactiva {
    padding: 1px 6px;
    font-size: 0.65rem;
    border-radius: 10px;
  }
  
  .btn-action.reactivate {
    min-width: 20px;
    height: 20px;
    font-size: 0.6rem;
    padding: 3px 5px;
  }
}

/* Estilos para botones de estado deshabilitados */
.state-btn.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background-color: #f8f9fa !important;
  color: #6c757d !important;
  border: 1px solid #dee2e6 !important;
  pointer-events: none;
}

.state-btn.disabled:hover {
  transform: none !important;
  box-shadow: none !important;
  background-color: #f8f9fa !important;
  color: #6c757d !important;
}

/* Estilos específicos para botón libre deshabilitado */
.state-btn.libre.disabled {
  background-color: #f8f9fa !important;
  color: #6c757d !important;
  border: 1px solid #dee2e6 !important;
}

.state-btn.libre.disabled:hover {
  background-color: #f8f9fa !important;
  color: #6c757d !important;
}

</style>
