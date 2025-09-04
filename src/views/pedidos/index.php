<?php
// src/views/pedidos/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Pedido;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Mozos y administradores pueden ver pedidos
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];

// Procesar cambio de estado si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    if ($rol === 'administrador') {
        $id_pedido = (int) $_POST['id_pedido'];
        $nuevo_estado = $_POST['nuevo_estado'];
        
        // Validar que el estado sea válido
        $estados_validos = ['pendiente', 'en_preparacion', 'servido', 'cuenta', 'cerrado'];
        if (in_array($nuevo_estado, $estados_validos)) {
            // Verificar que el pedido no esté cerrado
            $pedido = Pedido::find($id_pedido);
            if ($pedido && $pedido['estado'] === 'cerrado') {
                header('Location: ' . url('pedidos', ['error' => '3']));
                exit;
            }
            
            $resultado = Pedido::updateEstado($id_pedido, $nuevo_estado);
            if ($resultado) {
                header('Location: ' . url('pedidos', ['success' => '1']));
            } else {
                header('Location: ' . url('pedidos', ['error' => '1']));
            }
        } else {
            header('Location: ' . url('pedidos', ['error' => '2']));
        }
        exit;
    }
}

// Cargar pedidos
$pedidos = Pedido::all();

?>

<h2><?= $rol === 'administrador' ? 'Gestión de Pedidos' : 'Consulta de Pedidos' ?></h2>

<?php if (isset($_GET['success'])): ?>
    <div class="success" style="color: green; background: #d4edda; padding: 6px 10px; border-radius: 4px; margin-bottom: 0.6rem; font-size: 0.85rem;">
        <?php if ($_GET['success'] == '1'): ?>
        Estado del pedido actualizado correctamente.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="error" style="color: red; background: #f8d7da; padding: 6px 10px; border-radius: 4px; margin-bottom: 0.6rem; font-size: 0.85rem;">
        <?php if ($_GET['error'] == '1'): ?>
        Error al actualizar el estado del pedido.
        <?php elseif ($_GET['error'] == '2'): ?>
        Estado inválido.
        <?php elseif ($_GET['error'] == '3'): ?>
        No se puede cambiar el estado de un pedido cerrado.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($rol === 'administrador'): ?>
  <div class="action-buttons" style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 0.6rem; align-items: center;">
    <a href="<?= url('pedidos/create') ?>" class="button" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; white-space: nowrap;">
      ➕ Nuevo Pedido
    </a>
  </div>
<?php else: ?>
  <div style="background: #d1ecf1; padding: 6px; border-radius: 4px; margin-bottom: 0.6rem; color: #0c5460; font-size: 0.8rem; text-align: center;">
    🍽️ Vista de pedidos - Gestiona los pedidos de las mesas
  </div>
<?php endif; ?>

<!-- Filtros de búsqueda -->
<div class="filters-container">
  <!-- Botón para mostrar/ocultar filtros en móvil -->
  <button id="toggleFilters" class="toggle-filters-btn" onclick="toggleFilters()">
    🔍 Filtros
  </button>
  
  <div id="filtersContent" class="search-filter" style="background: rgb(247, 235, 202); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.6rem; margin-bottom: 0.8rem;">
  <!-- Primera fila: Filtros de texto -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.6rem; margin-bottom: 0.6rem;">
    <!-- Filtro por ID -->
    <div class="filter-group">
      <label style="display: block; font-weight: 600; color: var(--secondary); font-size: 0.7rem; margin-bottom: 0.15rem;">🔍 ID</label>
      <div style="display: flex; gap: 0.2rem;">
        <input type="text" id="searchId" placeholder="Buscar por ID..." 
               style="flex: 1; padding: 0.3rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.7rem; height: 26px;">
        <button id="clearIdSearch" 
                style="padding: 0.3rem 0.5rem; background: var(--secondary); color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.7rem; height: 26px; white-space: nowrap;">
          ✕
        </button>
      </div>
    </div>

    <!-- Filtro por Mesa -->
    <div class="filter-group">
      <label style="display: block; font-weight: 600; color: var(--secondary); font-size: 0.7rem; margin-bottom: 0.15rem;">🪑 Mesa</label>
      <div style="display: flex; gap: 0.2rem;">
        <input type="text" id="searchMesa" placeholder="Buscar por mesa..." 
               style="flex: 1; padding: 0.3rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.7rem; height: 26px;">
        <button id="clearMesaSearch" 
                style="padding: 0.3rem 0.5rem; background: var(--secondary); color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.7rem; height: 26px; white-space: nowrap;">
          ✕
        </button>
      </div>
    </div>

    <!-- Filtro por Mozo -->
    <div class="filter-group">
      <label style="display: block; font-weight: 600; color: var(--secondary); font-size: 0.7rem; margin-bottom: 0.15rem;">👤 Mozo</label>
      <div style="display: flex; gap: 0.2rem;">
        <input type="text" id="searchMozo" placeholder="Buscar por mozo..." 
               style="flex: 1; padding: 0.3rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.7rem; height: 26px;">
        <button id="clearMozoSearch" 
                style="padding: 0.3rem 0.5rem; background: var(--secondary); color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.7rem; height: 26px; white-space: nowrap;">
          ✕
        </button>
      </div>
    </div>

    <!-- Filtro por Fecha -->
    <div class="filter-group">
      <label style="display: block; font-weight: 600; color: var(--secondary); font-size: 0.7rem; margin-bottom: 0.15rem;">📅 Fecha</label>
      <div style="display: flex; gap: 0.2rem;">
        <input type="date" id="searchFecha" 
               style="flex: 1; padding: 0.3rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.7rem; height: 26px;">
        <button id="clearFechaSearch" 
                style="padding: 0.3rem 0.5rem; background: var(--secondary); color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.7rem; height: 26px; white-space: nowrap;">
          ✕
        </button>
      </div>
    </div>
  </div>

  <!-- Segunda fila: Filtro por Estado -->
  <div class="filter-group">
    <label style="display: block; font-weight: 600; color: var(--secondary); font-size: 0.7rem; margin-bottom: 0.3rem;">📊 Estado</label>
    <div class="status-filters" style="display: flex; gap: 0.3rem; flex-wrap: wrap;">
      <button class="status-filter-btn active" data-status="all" style="padding: 4px 8px; border: none; background: var(--secondary); color: white; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
        Todas
      </button>
      <button class="status-filter-btn" data-status="pendiente" style="padding: 4px 8px; border: none; background: #fff3cd; color: #856404; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
        ⏳ Pendiente
      </button>
      <button class="status-filter-btn" data-status="en_preparacion" style="padding: 4px 8px; border: none; background: #cce5ff; color: #004085; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
        👨‍🍳 En Preparación
      </button>
      <button class="status-filter-btn" data-status="servido" style="padding: 4px 8px; border: none; background: #d4edda; color: #155724; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
        ✅ Servido
      </button>
      <button class="status-filter-btn" data-status="cuenta" style="padding: 4px 8px; border: none; background: #d1ecf1; color: #0c5460; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
        💳 Cuenta
      </button>
      <button class="status-filter-btn" data-status="cerrado" style="padding: 4px 8px; border: none; background: #e2e3e5; color: #383d41; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
        🔒 Cerrado
      </button>
    </div>
  </div>
  </div>
</div>

<div class="table-responsive">
<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Mesa</th>
      <th>Mozo</th>
      <th>Estado</th>
      <th>Total</th>
      <th>Fecha</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Cambiar Estado</th>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($pedidos)): ?>
      <tr>
        <td colspan="<?= $rol === 'administrador' ? '8' : '6' ?>">No hay pedidos registrados.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($pedidos as $pedido): ?>
        <tr>
          <td><?= htmlspecialchars($pedido['id_pedido']) ?></td>
          <td><?= htmlspecialchars($pedido['numero_mesa'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($pedido['nombre_mozo_completo'] ?? 'N/A') ?></td>
          <td>
            <?php
            // Definir colores según el estado del pedido
            $estado = $pedido['estado'];
            switch ($estado) {
                case 'pendiente':
                    $bg_color = '#fff3cd';
                    $text_color = '#856404';
                    $icon = '⏳';
                    break;
                case 'en_preparacion':
                    $bg_color = '#cce5ff';
                    $text_color = '#004085';
                    $icon = '👨‍🍳';
                    break;
                case 'servido':
                    $bg_color = '#d4edda';
                    $text_color = '#155724';
                    $icon = '✅';
                    break;
                case 'cuenta':
                    $bg_color = '#d1ecf1';
                    $text_color = '#0c5460';
                    $icon = '💳';
                    break;
                case 'cerrado':
                    $bg_color = '#e2e3e5';
                    $text_color = '#383d41';
                    $icon = '🔒';
                    break;
                default:
                    $bg_color = '#f8d7da';
                    $text_color = '#721c24';
                    $icon = '❓';
            }
            ?>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                         background: <?= $bg_color ?>; 
                         color: <?= $text_color ?>;">
              <?= $icon ?> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $pedido['estado']))) ?>
            </span>
          </td>
          <td><strong>$<?= number_format($pedido['total'] ?? 0, 2) ?></strong></td>
          <td><?= !empty($pedido['fecha_creacion']) ? date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) : 'N/A' ?></td>
          <?php if ($rol === 'administrador'): ?>
            <td class="action-cell">
              <div class="state-shortcuts">
                <?php if ($pedido['estado'] !== 'cerrado'): ?>
                  <button class="state-btn pendiente" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'pendiente')" title="Cambiar a Pendiente">
                    ⏳
                  </button>
                  <button class="state-btn en_preparacion" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'en_preparacion')" title="Cambiar a En Preparación">
                    👨‍🍳
                  </button>
                  <button class="state-btn servido" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'servido')" title="Cambiar a Servido">
                    ✅
                  </button>
                  <button class="state-btn cuenta" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'cuenta')" title="Cambiar a Cuenta">
                    💳
                  </button>
                  <button class="state-btn cerrado" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'cerrado')" title="Cambiar a Cerrado">
                    🔒
                  </button>
                <?php else: ?>
                  <span style="color: #6c757d; font-size: 0.8rem; font-style: italic;">Pedido cerrado</span>
                <?php endif; ?>
              </div>
            </td>
            <td class="action-cell">
              <a href="<?= url('pedidos/edit', ['id' => $pedido['id_pedido']]) ?>" class="btn-action" title="Editar pedido">
                ✏️
              </a>
              <a href="#" class="btn-action delete" title="Eliminar pedido" onclick="confirmarBorradoPedido(<?= $pedido['id_pedido'] ?>, 'Pedido #<?= $pedido['id_pedido'] ?>')">
                ❌
              </a>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
</div>

<!-- Vista móvil con tarjetas -->
<div class="mobile-cards" style="max-height: 60vh; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
  <?php if (empty($pedidos)): ?>
    <div class="mobile-card" style="text-align: center; padding: 1rem; color: #666;">
      No hay pedidos registrados.
    </div>
  <?php else: ?>
    <?php foreach ($pedidos as $pedido): ?>
      <div class="mobile-card">
        <div class="mobile-card-header">
          <div class="mobile-card-number">
            Pedido #<?= htmlspecialchars($pedido['id_pedido']) ?>
          </div>
          <div class="mobile-card-actions">
            <?php if ($rol === 'administrador'): ?>
              <a href="<?= url('pedidos/edit', ['id' => $pedido['id_pedido']]) ?>" class="btn-action" title="Editar pedido">
                ✏️
              </a>
              <a href="#" class="btn-action delete" title="Eliminar pedido" onclick="confirmarBorradoPedido(<?= $pedido['id_pedido'] ?>, 'Pedido #<?= $pedido['id_pedido'] ?>')">
                ❌
              </a>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($rol === 'administrador'): ?>
        <div class="mobile-state-shortcuts">
          <div class="state-shortcuts">
            <?php if ($pedido['estado'] !== 'cerrado'): ?>
              <button class="state-btn pendiente" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'pendiente')" title="Cambiar a Pendiente">
                ⏳
              </button>
              <button class="state-btn en_preparacion" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'en_preparacion')" title="Cambiar a En Preparación">
                👨‍🍳
              </button>
              <button class="state-btn servido" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'servido')" title="Cambiar a Servido">
                ✅
              </button>
              <button class="state-btn cuenta" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'cuenta')" title="Cambiar a Cuenta">
                💳
              </button>
              <button class="state-btn cerrado" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, 'cerrado')" title="Cambiar a Cerrado">
                🔒
              </button>
            <?php else: ?>
              <span style="color: #6c757d; font-size: 0.7rem; font-style: italic; text-align: center; display: block; padding: 0.3rem;">Pedido cerrado</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        </div>
        <div class="mobile-card-body">
          <div class="mobile-card-item">
            <div class="mobile-card-label">Mesa</div>
            <div class="mobile-card-value"><?= htmlspecialchars($pedido['numero_mesa'] ?? 'N/A') ?></div>
          </div>
          <div class="mobile-card-item">
            <div class="mobile-card-label">Mozo</div>
            <div class="mobile-card-value"><?= htmlspecialchars($pedido['nombre_mozo_completo'] ?? 'N/A') ?></div>
          </div>
          <div class="mobile-card-item">
            <div class="mobile-card-label">Estado</div>
            <div class="mobile-card-value">
              <?php
              // Definir colores según el estado del pedido
              $estado = $pedido['estado'];
              switch ($estado) {
                  case 'pendiente':
                      $bg_color = '#fff3cd';
                      $text_color = '#856404';
                      $icon = '⏳';
                      break;
                  case 'en_preparacion':
                      $bg_color = '#cce5ff';
                      $text_color = '#004085';
                      $icon = '👨‍🍳';
                      break;
                  case 'servido':
                      $bg_color = '#d4edda';
                      $text_color = '#155724';
                      $icon = '✅';
                      break;
                  case 'cuenta':
                      $bg_color = '#d1ecf1';
                      $text_color = '#0c5460';
                      $icon = '💳';
                      break;
                  case 'cerrado':
                      $bg_color = '#e2e3e5';
                      $text_color = '#383d41';
                      $icon = '🔒';
                      break;
                  default:
                      $bg_color = '#f8d7da';
                      $text_color = '#721c24';
                      $icon = '❓';
              }
              ?>
              <span style="padding: 2px 6px; border-radius: 8px; font-size: 0.7em; font-weight: bold; 
                           background: <?= $bg_color ?>; 
                           color: <?= $text_color ?>;">
                <?= $icon ?> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $pedido['estado']))) ?>
              </span>
            </div>
          </div>
          <div class="mobile-card-item">
            <div class="mobile-card-label">Total</div>
            <div class="mobile-card-value"><strong>$<?= number_format($pedido['total'] ?? 0, 2) ?></strong></div>
          </div>
          <div class="mobile-card-item">
            <div class="mobile-card-label">Fecha</div>
            <div class="mobile-card-value"><?= !empty($pedido['fecha_creacion']) ? date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) : 'N/A' ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Modal de confirmación para cambio de estado -->
<div id="modalCambioEstado" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
  <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 400px; width: 90%; text-align: center;">
    <h3 style="margin-bottom: 1rem; color: var(--secondary);">Confirmar Cambio de Estado</h3>
    <p id="mensajeCambioEstado" style="margin-bottom: 1.5rem; color: #666;"></p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
      <button id="confirmarCambio" style="background: var(--secondary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; font-size: 1rem;">
        Confirmar
      </button>
      <button id="cancelarCambio" style="background: #6c757d; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; font-size: 1rem;">
        Cancelar
      </button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const searchId = document.getElementById('searchId');
    const searchMesa = document.getElementById('searchMesa');
    const searchMozo = document.getElementById('searchMozo');
    const searchFecha = document.getElementById('searchFecha');
    const searchResults = document.getElementById('searchResults');
    const tableRows = document.querySelectorAll('.table tbody tr');
    const mobileCards = document.querySelectorAll('.mobile-cards .mobile-card');
    const statusButtons = document.querySelectorAll('.status-filter-btn');
    
    let currentIdSearch = '';
    let currentMesaSearch = '';
    let currentMozoSearch = '';
    let currentFechaSearch = '';
    let currentStatusFilter = 'all';
    
    function getPedidoStatus(element) {
        const statusSpan = element.querySelector('td:nth-child(4) span') || element.querySelector('.mobile-card-value span');
        if (statusSpan) {
            return statusSpan.textContent.toLowerCase().trim().replace(/[⏳👨‍🍳✅💳🔒]/g, '').trim();
        }
        return '';
    }
    
    function getPedidoFecha(element) {
        const fechaCell = element.querySelector('td:nth-child(6)') || element.querySelector('.mobile-card-item:last-child .mobile-card-value');
        if (fechaCell) {
            const fechaText = fechaCell.textContent.trim();
            // Convertir formato dd/mm/yyyy a yyyy-mm-dd para comparación
            const parts = fechaText.split(' ');
            if (parts.length > 0) {
                const datePart = parts[0];
                const dateParts = datePart.split('/');
                if (dateParts.length === 3) {
                    return `${dateParts[2]}-${dateParts[1].padStart(2, '0')}-${dateParts[0].padStart(2, '0')}`;
                }
            }
        }
        return '';
    }
    
    function getPedidoData(element) {
        if (element.tagName === 'TR') {
            // Es una fila de tabla
            const idCell = element.querySelector('td:nth-child(1)');
            const mesaCell = element.querySelector('td:nth-child(2)');
            const mozoCell = element.querySelector('td:nth-child(3)');
            
            return {
                id: idCell ? idCell.textContent.toLowerCase() : '',
                mesa: mesaCell ? mesaCell.textContent.toLowerCase() : '',
                mozo: mozoCell ? mozoCell.textContent.toLowerCase() : '',
                status: getPedidoStatus(element),
                fecha: getPedidoFecha(element)
            };
        } else {
            // Es una tarjeta móvil
            const header = element.querySelector('.mobile-card-number');
            const mesaItem = element.querySelector('.mobile-card-item:nth-child(1) .mobile-card-value');
            const mozoItem = element.querySelector('.mobile-card-item:nth-child(2) .mobile-card-value');
            
            return {
                id: header ? header.textContent.toLowerCase().replace('pedido #', '') : '',
                mesa: mesaItem ? mesaItem.textContent.toLowerCase() : '',
                mozo: mozoItem ? mozoItem.textContent.toLowerCase() : '',
                status: getPedidoStatus(element),
                fecha: getPedidoFecha(element)
            };
        }
    }
    
    function filterPedidos() {
        // Filtrar filas de tabla
        tableRows.forEach(row => {
            const pedidoData = getPedidoData(row);
            
            const matchesId = pedidoData.id.includes(currentIdSearch);
            const matchesMesa = pedidoData.mesa.includes(currentMesaSearch);
            const matchesMozo = pedidoData.mozo.includes(currentMozoSearch);
            const matchesFecha = currentFechaSearch === '' || pedidoData.fecha === currentFechaSearch;
            const matchesStatus = currentStatusFilter === 'all' || pedidoData.status === currentStatusFilter;
            
            if (matchesId && matchesMesa && matchesMozo && matchesFecha && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Filtrar tarjetas móviles
        mobileCards.forEach(card => {
            const pedidoData = getPedidoData(card);
            
            const matchesId = pedidoData.id.includes(currentIdSearch);
            const matchesMesa = pedidoData.mesa.includes(currentMesaSearch);
            const matchesMozo = pedidoData.mozo.includes(currentMozoSearch);
            const matchesFecha = currentFechaSearch === '' || pedidoData.fecha === currentFechaSearch;
            const matchesStatus = currentStatusFilter === 'all' || pedidoData.status === currentStatusFilter;
            
            if (matchesId && matchesMesa && matchesMozo && matchesFecha && matchesStatus) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Event listeners para búsquedas
    searchId.addEventListener('input', function() {
        currentIdSearch = this.value.toLowerCase();
        filterPedidos();
    });
    
    searchMesa.addEventListener('input', function() {
        currentMesaSearch = this.value.toLowerCase();
        filterPedidos();
    });
    
    searchMozo.addEventListener('input', function() {
        currentMozoSearch = this.value.toLowerCase();
        filterPedidos();
    });
    
    searchFecha.addEventListener('change', function() {
        currentFechaSearch = this.value;
        filterPedidos();
    });
    
    // Botones de limpiar
    document.getElementById('clearIdSearch').addEventListener('click', function() {
        searchId.value = '';
        currentIdSearch = '';
        filterPedidos();
        searchId.focus();
    });
    
    document.getElementById('clearMesaSearch').addEventListener('click', function() {
        searchMesa.value = '';
        currentMesaSearch = '';
        filterPedidos();
        searchMesa.focus();
    });
    
    document.getElementById('clearMozoSearch').addEventListener('click', function() {
        searchMozo.value = '';
        currentMozoSearch = '';
        filterPedidos();
        searchMozo.focus();
    });
    
    document.getElementById('clearFechaSearch').addEventListener('click', function() {
        searchFecha.value = '';
        currentFechaSearch = '';
        filterPedidos();
        searchFecha.focus();
    });
    
    // Event listeners para los botones de estado
    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remover clase active de todos los botones
            statusButtons.forEach(btn => {
                btn.classList.remove('active');
                // Restaurar estilos originales según el estado
                const status = btn.getAttribute('data-status');
                if (status === 'all') {
                    btn.style.background = 'var(--secondary)';
                    btn.style.color = 'white';
                } else if (status === 'pendiente') {
                    btn.style.background = '#fff3cd';
                    btn.style.color = '#856404';
                } else if (status === 'en_preparacion') {
                    btn.style.background = '#cce5ff';
                    btn.style.color = '#004085';
                } else if (status === 'servido') {
                    btn.style.background = '#d4edda';
                    btn.style.color = '#155724';
                } else if (status === 'cuenta') {
                    btn.style.background = '#d1ecf1';
                    btn.style.color = '#0c5460';
                } else if (status === 'cerrado') {
                    btn.style.background = '#e2e3e5';
                    btn.style.color = '#383d41';
                }
            });
            
            // Agregar clase active al botón clickeado
            this.classList.add('active');
            // Mantener el estilo original para el botón activo
            
            // Actualizar filtro de estado
            currentStatusFilter = this.dataset.status;
            filterPedidos();
        });
    });
    
    // Inicializar filtros
    filterPedidos();
});

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

// Variables globales para el cambio de estado
let pedidoIdCambio = null;
let nuevoEstadoCambio = null;

// Función para confirmar cambio de estado
function confirmarCambioEstado(idPedido, nuevoEstado) {
    pedidoIdCambio = idPedido;
    nuevoEstadoCambio = nuevoEstado;
    
    const modal = document.getElementById('modalCambioEstado');
    const mensaje = document.getElementById('mensajeCambioEstado');
    
    // Mapear estados a nombres legibles
    const nombresEstados = {
        'pendiente': 'Pendiente',
        'en_preparacion': 'En Preparación',
        'servido': 'Servido',
        'cuenta': 'Cuenta',
        'cerrado': 'Cerrado'
    };
    
    mensaje.textContent = `¿Estás seguro de que quieres cambiar el estado del pedido #${idPedido} a "${nombresEstados[nuevoEstado]}"?`;
    modal.style.display = 'flex';
}

// Event listeners para el modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalCambioEstado');
    const confirmarBtn = document.getElementById('confirmarCambio');
    const cancelarBtn = document.getElementById('cancelarCambio');
    
    confirmarBtn.addEventListener('click', function() {
        if (pedidoIdCambio && nuevoEstadoCambio) {
            // Crear formulario para enviar el cambio
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const inputPedido = document.createElement('input');
            inputPedido.type = 'hidden';
            inputPedido.name = 'id_pedido';
            inputPedido.value = pedidoIdCambio;
            
            const inputEstado = document.createElement('input');
            inputEstado.type = 'hidden';
            inputEstado.name = 'nuevo_estado';
            inputEstado.value = nuevoEstadoCambio;
            
            const inputAccion = document.createElement('input');
            inputAccion.type = 'hidden';
            inputAccion.name = 'cambiar_estado';
            inputAccion.value = '1';
            
            form.appendChild(inputPedido);
            form.appendChild(inputEstado);
            form.appendChild(inputAccion);
            document.body.appendChild(form);
            form.submit();
        }
    });
    
    cancelarBtn.addEventListener('click', function() {
        modal.style.display = 'none';
        pedidoIdCambio = null;
        nuevoEstadoCambio = null;
    });
    
    // Cerrar modal al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
            pedidoIdCambio = null;
            nuevoEstadoCambio = null;
        }
    });
});
</script>

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
    
    /* Cuando se muestran los filtros en móvil, hacer que ocupen menos espacio */
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

/* Estilos para botones de estado de pedidos */
.state-btn.pendiente {
  background-color: #fff3cd;
  color: #856404;
}

.state-btn.pendiente:hover {
  background-color: #856404;
  color: #fff3cd;
}

.state-btn.en_preparacion {
  background-color: #cce5ff;
  color: #004085;
}

.state-btn.en_preparacion:hover {
  background-color: #004085;
  color: #cce5ff;
}

.state-btn.servido {
  background-color: #d4edda;
  color: #155724;
}

.state-btn.servido:hover {
  background-color: #155724;
  color: #d4edda;
}

.state-btn.cuenta {
  background-color: #d1ecf1;
  color: #0c5460;
}

.state-btn.cuenta:hover {
  background-color: #0c5460;
  color: #d1ecf1;
}

.state-btn.cerrado {
  background-color: #e2e3e5;
  color: #383d41;
}

.state-btn.cerrado:hover {
  background-color: #383d41;
  color: #e2e3e5;
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

/* Asegurar que en desktop se muestre la tabla y se oculten las tarjetas */
.table-responsive {
    display: block;
}

.mobile-cards {
    display: none;
}

/* En móvil, ocultar tabla y mostrar tarjetas */
@media (max-width: 768px) {
    .table-responsive {
        display: none !important;
    }
    
    .mobile-cards {
        display: block !important;
    }
}

/* Estilos para scroll personalizado en tarjetas móviles */
.mobile-cards::-webkit-scrollbar {
    width: 6px;
}

.mobile-cards::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.mobile-cards::-webkit-scrollbar-thumb {
    background: var(--secondary);
    border-radius: 3px;
}

.mobile-cards::-webkit-scrollbar-thumb:hover {
    background: #8a6f5a;
}
</style>


