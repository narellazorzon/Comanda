<?php
// src/views/mesas/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Mesa;
use App\Models\Usuario;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Personal y administradores pueden ver esta página
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
// Si es un mozo, mostrar sus mesas asignadas primero
if ($rol === 'mozo') {
    $mesasActivas = Mesa::allForMozo($_SESSION['user']['id_usuario']);
} else {
    $mesasActivas = Mesa::all();
}
$mesasInactivas = Mesa::allInactive();

// Obtener lista de mozos únicos para el filtro
$mozos = Usuario::getMozosActivos();
?>

<!-- Header de gestión -->
<div class="management-header">
  <h1><?= $rol === 'administrador' ? '🍽️ Gestión de Mesas' : 'Consulta de Mesas' ?></h1>
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


<!-- Filtros de búsqueda y estado -->
<div class="filters-container">
  <!-- Botón para mostrar/ocultar filtros -->
  <button id="toggleFilters" class="toggle-filters-btn" onclick="toggleFilters()">
    🔍 Filtrar
  </button>
  
  <div id="filtersContent" class="filters-content">
  <!-- Filtro por número -->
    <div class="filter-group">
      <label for="mesaSearch">🔍 Buscar por número:</label>
      <div class="search-input-group">
        <input type="text" id="mesaSearch" placeholder="Número de mesa..." />
        <button id="clearSearch" type="button">Limpiar</button>
      </div>
  </div>
  
  <!-- Filtro por estado -->
    <div class="filter-group">
      <label>📊 Filtrar por estado:</label>
      <div class="status-filters">
        <button class="status-filter-btn active" data-status="all">Todas</button>
        <button class="status-filter-btn" data-status="libre">Libre</button>
        <button class="status-filter-btn" data-status="ocupada">Ocupada</button>
        <button class="status-filter-btn" data-status="reservada">Reservada</button>
    </div>
  </div>
  
  <!-- Filtro por mozo -->
    <div class="filter-group">
      <label for="mozoFilter">👤 Filtrar por mozo:</label>
      <select id="mozoFilter" class="mozo-filter-select">
        <option value="">Todos los mozos</option>
        <option value="sin-asignar">Sin asignar</option>
        <?php foreach ($mozos as $mozo): ?>
          <option value="<?= htmlspecialchars(strtolower($mozo['nombre_completo'])) ?>">
            <?= htmlspecialchars($mozo['nombre_completo']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  
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
      <th style="width: 20%;">Número</th>
      <th>Ubicación</th>
      <th style="width: 12%;">Estado</th>
      <th>Mozo Asignado</th>
      <th style="width: 12%;">Cambiar Estado</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mesasActivas as $m): ?>
      <?php 
      // Verificar si esta mesa está asignada al mozo logueado
      $esMesaAsignada = ($rol === 'mozo' && $m['id_mozo'] == $_SESSION['user']['id_usuario']);
      ?>
      <tr class="mesa-row" data-numero="<?= htmlspecialchars($m['numero']) ?>" data-estado="<?= htmlspecialchars($m['estado']) ?>" data-ubicacion="<?= htmlspecialchars(strtolower($m['ubicacion'] ?? '')) ?>" data-mozo="<?= !empty($m['mozo_nombre_completo']) ? htmlspecialchars(strtolower($m['mozo_nombre_completo'])) : 'sin-asignar' ?>" <?= $esMesaAsignada ? 'style="background-color: #fff8e1; border-left: 4px solid rgb(135, 98, 34);"' : '' ?>>
        <td>
          <?= htmlspecialchars($m['numero']) ?>
          <?php if ($esMesaAsignada): ?>
            <span style="background: linear-gradient(135deg, rgb(240, 196, 118) 0%, rgb(135, 98, 34) 100%); color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7em; font-weight: bold; margin-left: 8px;">
              👤 Mi Mesa
            </span>
          <?php endif; ?>
        </td>
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
      <tr class="mesa-row" data-numero="<?= htmlspecialchars($m['numero']) ?>" data-estado="inactiva" data-ubicacion="<?= htmlspecialchars(strtolower($m['ubicacion'] ?? '')) ?>" data-mozo="<?= !empty($m['mozo_nombre_completo']) ? htmlspecialchars(strtolower($m['mozo_nombre_completo'])) : 'sin-asignar' ?>">
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
    <?php 
    // Verificar si esta mesa está asignada al mozo logueado
    $esMesaAsignada = ($rol === 'mozo' && $m['id_mozo'] == $_SESSION['user']['id_usuario']);
    ?>
    <div class="mobile-card mesa-activa mesa-row" data-numero="<?= htmlspecialchars($m['numero']) ?>" data-estado="<?= htmlspecialchars($m['estado']) ?>" data-ubicacion="<?= htmlspecialchars(strtolower($m['ubicacion'] ?? '')) ?>" data-mozo="<?= !empty($m['mozo_nombre_completo']) ? htmlspecialchars(strtolower($m['mozo_nombre_completo'])) : 'sin-asignar' ?>" <?= $esMesaAsignada ? 'style="background-color: #fff8e1; border-left: 4px solid rgb(135, 98, 34);"' : '' ?>>
      <div class="mobile-card-header">
        <div class="mobile-card-number">
          Mesa <?= htmlspecialchars($m['numero']) ?>
          <?php if ($esMesaAsignada): ?>
            <span style="background: linear-gradient(135deg, rgb(240, 196, 118) 0%, rgb(135, 98, 34) 100%); color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7em; font-weight: bold; margin-left: 8px;">
              👤 Mi Mesa
            </span>
          <?php endif; ?>
        </div>
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
    <div class="mobile-card mesa-inactiva mesa-row" data-numero="<?= htmlspecialchars($m['numero']) ?>" data-estado="inactiva" data-ubicacion="<?= htmlspecialchars(strtolower($m['ubicacion'] ?? '')) ?>" data-mozo="<?= !empty($m['mozo_nombre_completo']) ? htmlspecialchars(strtolower($m['mozo_nombre_completo'])) : 'sin-asignar' ?>" style="display: none;">
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
// Variables globales
    let currentSearchTerm = '';
    let currentStatusFilter = 'all';
    let currentMozoFilter = '';
    
// Función para obtener el estado de una mesa desde la tabla
    function getMesaStatus(row) {
        const statusCell = row.querySelector('td:nth-child(3)');
        if (statusCell) {
            const statusSpan = statusCell.querySelector('span');
        if (statusSpan) {
            return statusSpan.textContent.toLowerCase().trim();
            }
        }
        return '';
    }
    
// Función para obtener el estado de una mesa desde la tarjeta móvil
    function getMesaStatusFromCard(card) {
    if (card.classList.contains('mesa-inactiva')) {
        return 'inactiva';
    }
    
        const statusItems = card.querySelectorAll('.mobile-card-item');
        for (let item of statusItems) {
            const label = item.querySelector('.mobile-card-label');
            if (label && label.textContent.includes('Estado')) {
                const statusSpan = item.querySelector('.mobile-card-value span');
        if (statusSpan) {
                return statusSpan.textContent.toLowerCase().trim();
                }
            }
        }
        return '';
    }
    
// Función principal de filtrado
    function filterMesas() {
        const searchTerm = currentSearchTerm.toLowerCase().trim();
        const statusFilter = currentStatusFilter;
        const mozoFilter = currentMozoFilter.toLowerCase().trim();
        let visibleCount = 0;
        
        // Filtrar filas de la tabla
    const tableRows = document.querySelectorAll('.table tbody tr.mesa-row');
    tableRows.forEach((row) => {
            const firstCell = row.querySelector('td:first-child');
        if (!firstCell) return;
            
            const mesaNumber = firstCell.textContent.toLowerCase();
            const mesaStatus = getMesaStatus(row);
            const mesaMozo = row.dataset.mozo ? row.dataset.mozo.toLowerCase() : 'sin-asignar';
            
            const matchesSearch = searchTerm === '' || mesaNumber.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || mesaStatus === statusFilter;
            const matchesMozo = mozoFilter === '' || mesaMozo === mozoFilter;
            
            if (matchesSearch && matchesStatus && matchesMozo) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Filtrar tarjetas móviles
    const mobileCards = document.querySelectorAll('.mobile-card.mesa-row');
    mobileCards.forEach((card) => {
            const numberElement = card.querySelector('.mobile-card-number');
        if (!numberElement) return;
            
            const mesaNumber = numberElement.textContent.toLowerCase();
            const mesaStatus = getMesaStatusFromCard(card);
            const mesaMozo = card.dataset.mozo ? card.dataset.mozo.toLowerCase() : 'sin-asignar';
            
            const matchesSearch = searchTerm === '' || mesaNumber.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || mesaStatus === statusFilter;
            const matchesMozo = mozoFilter === '' || mesaMozo === mozoFilter;
            
        // Verificar si la tarjeta debe mostrarse según la pestaña activa
        const activeTab = document.querySelector('.tab-button.active');
        const isActiveTab = activeTab && activeTab.textContent.includes('Activas');
        const isInactiveTab = activeTab && activeTab.textContent.includes('Inactivas');
        
        let shouldShow = false;
        if (isActiveTab && card.classList.contains('mesa-activa')) {
            shouldShow = true;
        } else if (isInactiveTab && card.classList.contains('mesa-inactiva')) {
            shouldShow = true;
        }
        
        if (matchesSearch && matchesStatus && matchesMozo && shouldShow) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
}

// Función para inicializar los filtros
function initFilters() {
    const searchInput = document.getElementById('mesaSearch');
    const clearButton = document.getElementById('clearSearch');
    const statusButtons = document.querySelectorAll('.status-filter-btn');
    
    if (!searchInput || !clearButton) {
        console.error('Elementos de filtro no encontrados');
        return;
    }
    
    // Event listener para el input de búsqueda
    searchInput.addEventListener('input', function() {
        currentSearchTerm = this.value;
        filterMesas();
    });
    
    // Event listener para el botón limpiar
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        currentSearchTerm = '';
        // Limpiar filtro de mozo también
        if (mozoFilter) {
            mozoFilter.value = '';
            currentMozoFilter = '';
        }
        // Resetear filtro de estado
        statusButtons.forEach(btn => btn.classList.remove('active'));
        const allButton = document.querySelector('.status-filter-btn[data-status="all"]');
        if (allButton) {
            allButton.classList.add('active');
            currentStatusFilter = 'all';
        }
        filterMesas();
        searchInput.focus();
    });
    
    // Event listeners para los botones de estado
    if (statusButtons && statusButtons.length > 0) {
        statusButtons.forEach((button) => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remover clase active de todos los botones
                statusButtons.forEach(btn => btn.classList.remove('active'));
                
                // Agregar clase active al botón clickeado
                this.classList.add('active');
                
                // Actualizar filtro de estado
                currentStatusFilter = this.dataset.status;
                filterMesas();
            });
        });
    }
    
    // Event listener para el filtro de mozo
    const mozoFilter = document.getElementById('mozoFilter');
    if (mozoFilter) {
        mozoFilter.addEventListener('change', function() {
            currentMozoFilter = this.value;
            filterMesas();
        });
    }
    
    // Ejecutar filtro inicial
    filterMesas();
}

// Función para mostrar/ocultar filtros
function toggleFilters() {
    const filtersContent = document.getElementById('filtersContent');
    const toggleBtn = document.getElementById('toggleFilters');
    
    if (filtersContent.style.display === 'none' || filtersContent.style.display === '') {
        filtersContent.style.display = 'block';
        toggleBtn.innerHTML = '🔍 Ocultar Filtros';
    } else {
        filtersContent.style.display = 'none';
        toggleBtn.innerHTML = '🔍 Filtrar';
    }
}

// Variables globales para el modal de cambio de estado
let mesaIdParaCambiar = null;
let nuevoEstadoParaCambiar = null;

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
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
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
                // Si no es JSON válido, recargar la página
                location.reload();
            }
        })
        .catch(error => {
            showNotification('Error al cambiar el estado de la mesa: ' + error.message, 'error', 6000);
        });
        
        // Limpiar variables
        mesaIdParaCambiar = null;
        nuevoEstadoParaCambiar = null;
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
  ModalConfirmacion.show({
    title: '✅ Reactivar Mesa',
    message: '¿Estás seguro de que quieres reactivar esta mesa?',
    itemName: `Mesa #${numero}`,
    note: 'La mesa volverá a las listas activas y quedará libre, disponible para asignar un mozo más adelante.',
    confirmText: '✅ Reactivar',
    cancelText: '🚫 Cancelar',
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
      // Reactivación cancelada
    }
  });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    
    // Agregar eventos a los filtros
    const filtroNumero = document.getElementById('filtro-numero');
    const filtroEstado = document.getElementById('filtro-estado');
    const filtroUbicacion = document.getElementById('filtro-ubicacion');
    const filtroMozo = document.getElementById('filtro-mozo');

    if (filtroNumero) filtroNumero.addEventListener('input', aplicarFiltrosMesas);
    if (filtroEstado) filtroEstado.addEventListener('change', aplicarFiltrosMesas);
    if (filtroUbicacion) filtroUbicacion.addEventListener('change', aplicarFiltrosMesas);
    if (filtroMozo) filtroMozo.addEventListener('change', aplicarFiltrosMesas);
});

// === FUNCIONALIDAD DE FILTROS ===
function aplicarFiltrosMesas() {
    const filtroNumero = document.getElementById('filtro-numero').value.toLowerCase();
    const filtroEstado = document.getElementById('filtro-estado').value.toLowerCase();
    const filtroUbicacion = document.getElementById('filtro-ubicacion').value.toLowerCase();
    const filtroMozo = document.getElementById('filtro-mozo').value.toLowerCase();
    
    const filas = document.querySelectorAll('.mesa-row');
    let contadorVisible = 0;
    
    filas.forEach(fila => {
        const numero = fila.dataset.numero.toLowerCase();
        const estado = fila.dataset.estado.toLowerCase();
        const ubicacion = fila.dataset.ubicacion.toLowerCase();
        const mozo = fila.dataset.mozo.toLowerCase();
        
        let mostrar = true;
        
        // Filtro por número (búsqueda parcial)
        if (filtroNumero && !numero.includes(filtroNumero)) {
            mostrar = false;
        }
        
        // Filtro por estado
        if (filtroEstado && estado !== filtroEstado) {
            mostrar = false;
        }
        
        // Filtro por ubicación
        if (filtroUbicacion && ubicacion !== filtroUbicacion) {
            mostrar = false;
        }
        
        // Filtro por mozo
        if (filtroMozo) {
            if (filtroMozo === 'sin-asignar' && mozo !== 'sin-asignar') {
                mostrar = false;
            } else if (filtroMozo !== 'sin-asignar' && mozo !== filtroMozo) {
                mostrar = false;
            }
        }
        
        // Mostrar u ocultar la fila
        if (mostrar) {
            fila.style.display = '';
            contadorVisible++;
        } else {
            fila.style.display = 'none';
        }
    });
    
    // Actualizar contador
    document.getElementById('num-mesas').textContent = contadorVisible;
    
    // Mostrar mensaje si no hay resultados
    if (contadorVisible === 0 && filas.length > 0) {
        let filaNoResultados = document.getElementById('fila-no-mesas');
        if (!filaNoResultados) {
            const tbody = document.querySelector('.table tbody');
            const nuevaFila = document.createElement('tr');
            nuevaFila.id = 'fila-no-mesas';
            const numColumnas = document.querySelector('.table thead tr').children.length;
            nuevaFila.innerHTML = `<td colspan="${numColumnas}" style="text-align: center; padding: 2rem; color: #6c757d;">
                <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">🪑 No se encontraron mesas con los filtros aplicados</div>
                <div style="font-size: 0.9rem;">Intenta ajustar los criterios de búsqueda</div>
            </td>`;
            tbody.appendChild(nuevaFila);
        }
    } else {
        const filaNoResultados = document.getElementById('fila-no-mesas');
        if (filaNoResultados) {
            filaNoResultados.remove();
        }
    }
}

function limpiarFiltrosMesas() {
    document.getElementById('filtro-numero').value = '';
    document.getElementById('filtro-estado').value = '';
    document.getElementById('filtro-ubicacion').value = '';
    document.getElementById('filtro-mozo').value = '';
    
    const filas = document.querySelectorAll('.mesa-row');
    filas.forEach(fila => {
        fila.style.display = '';
    });
    
    document.getElementById('num-mesas').textContent = filas.length;
    
    const filaNoResultados = document.getElementById('fila-no-mesas');
    if (filaNoResultados) {
        filaNoResultados.remove();
    }
}

</script>

<style>
/* Efectos bounce y animaciones globales */
@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0.3) translateY(-50px);
  }
  50% {
    opacity: 1;
    transform: scale(1.05) translateY(0);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Aplicar animación de entrada a elementos principales */
.management-header {
  animation: slideInUp 0.6s ease-out;
}

.tabs-container,
.mesas-grid {
  animation: fadeInScale 0.8s ease-out;
}

.mesa-card {
  animation: slideInUp 0.5s ease-out;
  animation-fill-mode: both;
}

.mesa-card:nth-child(1) { animation-delay: 0.1s; }
.mesa-card:nth-child(2) { animation-delay: 0.2s; }
.mesa-card:nth-child(3) { animation-delay: 0.3s; }
.mesa-card:nth-child(4) { animation-delay: 0.4s; }
.mesa-card:nth-child(5) { animation-delay: 0.5s; }
.mesa-card:nth-child(6) { animation-delay: 0.6s; }
.mesa-card:nth-child(7) { animation-delay: 0.7s; }
.mesa-card:nth-child(8) { animation-delay: 0.8s; }
.mesa-card:nth-child(9) { animation-delay: 0.9s; }
.mesa-card:nth-child(10) { animation-delay: 1.0s; }

/* Efectos de hover mejorados */
.mesa-card:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 6px 20px rgba(0,0,0,0.15);
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.filters-container {
  animation: slideInUp 0.7s ease-out;
}

/* Estilos para pestañas - CRÍTICO: Debe ir primero para evitar FOUC */
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
    border-bottom-color:rgb(255, 166, 0);
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
}

@media (max-width: 360px) {
  .tabs-container {
    margin-bottom: 0.6rem;
  }
  
  .tab-button {
    padding: 6px 8px;
    font-size: 0.7rem;
  }
}

/* Estilos para filtros */
.filters-container {
    background: rgb(238, 224, 191);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    overflow: hidden;
}

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
    margin-bottom: 0;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toggle-filters-btn:hover {
    background: linear-gradient(135deg, rgb(190, 141, 56) 0%, rgb(170, 125, 50) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.filters-content {
    display: none;
    padding: 1rem;
}

.filter-group {
    margin-bottom: 1rem;
}

.filter-group:last-child {
    margin-bottom: 0;
}

.filter-group label {
    display: block;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.search-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.search-input-group input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.search-input-group button {
    padding: 0.5rem 1rem;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.3s ease;
}

.search-input-group button:hover {
    background:rgb(137, 122, 100);
}

.mozo-filter-select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
    transition: border-color 0.3s ease;
}

.mozo-filter-select:hover {
    border-color: rgb(144, 104, 76);
}

.mozo-filter-select:focus {
    outline: none;
    border-color: rgb(144, 104, 76);
    box-shadow: 0 0 0 2px rgba(144, 104, 76, 0.2);
}

.status-filters {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.status-filter-btn {
    padding: 0.4rem 0.8rem;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.status-filter-btn.active {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.status-filter-btn[data-status="all"] {
    background: #6c757d;
    color: white;
}

.status-filter-btn[data-status="libre"] {
    background: #d4edda;
    color: #155724;
}

.status-filter-btn[data-status="ocupada"] {
    background: #f8d7da;
    color: #721c24;
}

.status-filter-btn[data-status="reservada"] {
    background: #fff3cd;
    color: #856404;
}



/* Ajustes específicos para columnas de la tabla de mesas */
.table th:nth-child(1), .table td:nth-child(1) {
  width: 20% !important;
  min-width: 120px;
}

.table th:nth-child(3), .table td:nth-child(3) {
  width: 12% !important;
  min-width: 80px;
}

.table th:nth-child(5), .table td:nth-child(5) {
  width: 12% !important;
  min-width: 100px;
}

/* Asegurar que el badge "Mi Mesa" se vea completo */
.table td:nth-child(1) {
  white-space: nowrap;
  overflow: visible;
}

/* Responsive para móvil */
@media (max-width: 768px) {
    .toggle-filters-btn {
        padding: 0.5rem 0.8rem;
        font-size: 0.8rem;
    }
    
    .filters-content {
        padding: 0.8rem;
    }
    
    .filter-group {
        margin-bottom: 0.8rem;
    }
    
    .filter-group label {
        font-size: 0.8rem;
        margin-bottom: 0.3rem;
    }
    
    .search-input-group input {
        padding: 0.4rem;
        font-size: 0.8rem;
    }
    
    .search-input-group button {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
    
    .status-filters {
        gap: 0.3rem;
    }
    
    .status-filter-btn {
        padding: 0.3rem 0.6rem;
        font-size: 0.7rem;
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
    
    .filters-content {
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
  margin: 0 !important;
  font-size: 1.4rem !important;
  font-weight: 600 !important;
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
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  color: white !important;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
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
    font-size: 1.1rem !important;
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
    font-size: 0.9rem !important;
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
    font-size: 0.8rem !important;
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

<!-- Modal de confirmación ya incluido en header.php -->
