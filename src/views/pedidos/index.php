<?php
// src/views/pedidos/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Pedido;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar permisos
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];

// Función para validar transiciones de estado
function validarTransicionEstado($estado_actual, $nuevo_estado) {
    // Definir transiciones permitidas según el flujo de negocio
    $transiciones_permitidas = [
        'pendiente' => ['en_preparacion', 'cerrado'], // pendiente → en_preparacion o cerrado
        'en_preparacion' => ['servido', 'cerrado'],   // en_preparacion → servido o cerrado
        'servido' => ['cerrado'],                     // servido → cerrado
        'cerrado' => []                               // cerrado no puede cambiar
    ];
    
    // Si el estado actual no existe en las transiciones, no permitir
    if (!isset($transiciones_permitidas[$estado_actual])) {
        return false;
    }
    
    // Verificar si la transición está permitida
    return in_array($nuevo_estado, $transiciones_permitidas[$estado_actual]);
}

// Función para obtener el nombre legible del estado
function obtenerNombreEstado($estado) {
    $nombres = [
        'pendiente' => 'Pendiente',
        'en_preparacion' => 'En Preparación',
        'servido' => 'Servido',
        'cerrado' => 'Cerrado'
    ];
    
    return $nombres[$estado] ?? $estado;
}

// Función para obtener transiciones permitidas desde un estado
function obtenerTransicionesPermitidas($estado_actual) {
    $transiciones = [
        'pendiente' => ['en_preparacion', 'cerrado'],
        'en_preparacion' => ['servido', 'cerrado'],
        'servido' => ['cerrado'],
        'cerrado' => []
    ];
    
    return $transiciones[$estado_actual] ?? [];
}

// Función para obtener iconos de estados
function obtenerIconoEstado($estado) {
    $iconos = [
        'pendiente' => '⏳',
        'en_preparacion' => '👨‍🍳',
        'servido' => '✅',
        'cerrado' => '🔒'
    ];
    
    return $iconos[$estado] ?? '❓';
}

// Procesar cambio de estado si es POST (ANTES de cualquier salida HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    // Permitir a mozos y administradores cambiar estado
    if (in_array($rol, ['mozo', 'administrador'])) {
        $id_pedido = (int) $_POST['id_pedido'];
        $nuevo_estado = $_POST['nuevo_estado'];
        
        // Validar que el estado sea válido
        $estados_validos = ['pendiente', 'en_preparacion', 'servido', 'cerrado'];
        if (in_array($nuevo_estado, $estados_validos)) {
            // Verificar que el pedido no esté cerrado
            $pedido = Pedido::find($id_pedido);
            if ($pedido && $pedido['estado'] === 'cerrado') {
                header('Location: ' . url('pedidos', ['error' => '3']));
                exit;
            }
            
            // Verificar si el estado actual es el mismo que el nuevo estado
            if ($pedido && $pedido['estado'] === $nuevo_estado) {
                header('Location: ' . url('pedidos', ['error' => '4']));
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

// Cargar pedidos según el rol
if ($rol === 'mozo') {
    // Los mozos solo ven pedidos del día actual
    $pedidos = Pedido::todayOnly();
} else {
    // Los administradores ven todos los pedidos
    $pedidos = Pedido::all();
}

// Incluir header DESPUÉS de procesar POST
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Estilos para filtros desplegables */
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
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    overflow: hidden;
}

#filtersContent {
    display: none;
    padding: 1rem;
}

.filter-group {
    margin-bottom: 1rem;
}

.filter-group:last-child {
    margin-bottom: 0;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.85rem;
}

.status-filters {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.status-filter-btn {
    padding: 0.4rem 0.8rem;
    border: none;
    background: #f8f9fa;
    color: #495057;
    border-radius: 12px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.status-filter-btn:hover {
    background: #e9ecef;
}

.status-filter-btn.active {
    background: rgb(83, 52, 31);
    color: white;
}

/* Estilos para el header de gestión */
.management-header {
  background: linear-gradient(135deg, rgb(144, 104, 76), rgb(92, 64, 51));
  color: white !important;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
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
}

.header-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.header-btn {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  color: white !important;
  text-decoration: none;
  border-radius: 4px;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.1);
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
    font-size: 0.9rem;
    text-align: center;
    margin-bottom: 0.5rem;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .header-btn {
    font-size: 0.7rem;
    padding: 0.3rem 0.6rem;
  }
}
</style>


<!-- Header de gestión -->
<div class="management-header">
  <h1><?= $rol === 'administrador' ? '📋 Gestión de Pedidos' : '📋 Pedidos del Día' ?></h1>
  <div class="header-actions">
    <a href="<?= url('pedidos/create') ?>" class="header-btn">
      ➕ Nuevo Pedido
    </a>
  </div>
</div>

<?php if ($rol === 'mozo'): ?>
  <div style="background: #d1ecf1; padding: 8px; border-radius: 4px; margin-bottom: 0.8rem; color: #0c5460; font-size: 0.85rem;">
    📅 Mostrando únicamente los pedidos del día de hoy (<?= date('d/m/Y') ?>)
  </div>
<?php endif; ?>

<!-- Sistema de notificaciones temporales -->
<div id="notification-container"></div>


<!-- Filtros de búsqueda -->
<div class="filters-container">
  <!-- Botón para mostrar/ocultar filtros -->
  <button id="toggleFilters" class="toggle-filters-btn" onclick="toggleFilters()">
    🔍 Filtros
  </button>
  
  <div id="filtersContent" class="search-filter" style="background: rgb(247, 235, 202); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.6rem; margin-bottom: 0.8rem; display: none; transition: all 0.3s ease;">
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
      <button class="status-filter-btn" data-status="cerrado" style="padding: 4px 8px; border: none; background: #e2e3e5; color: #383d41; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
        🔒 Cerrado
      </button>
    </div>
  </div>
  </div>
</div>

<div class="pedidos-container">
<div class="table-responsive">
<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Mesa</th>
      <th>Mozo</th>
      <th>Estado</th>
      <th>Total</th>
      <th>Método de Pago</th>
      <th>Fecha</th>
      <th>Cambiar Estado</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($pedidos)): ?>
      <tr>
        <td colspan="<?= $rol === 'administrador' ? '9' : '8' ?>">No hay pedidos registrados.</td>
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
          <td>
            <?php
            $forma_pago = $pedido['forma_pago'] ?? '';
            switch ($forma_pago) {
                case 'efectivo':
                    echo '<span style="color: #28a745; font-weight: bold;">💵 Efectivo</span>';
                    break;
                case 'tarjeta':
                    echo '<span style="color: #007bff; font-weight: bold;">💳 Tarjeta</span>';
                    break;
                case 'transferencia':
                    echo '<span style="color: #6f42c1; font-weight: bold;">🏦 Transferencia</span>';
                    break;
                default:
                    echo '<span style="color: #6c757d; font-style: italic;">Sin definir</span>';
            }
            ?>
          </td>
          <td><?= !empty($pedido['fecha_creacion']) ? date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) : 'N/A' ?></td>
          <td class="action-cell">
            <div class="state-shortcuts">
              <?php 
              $estado_actual = $pedido['estado'];
              $estados_disponibles = obtenerTransicionesPermitidas($estado_actual);
              
              if (empty($estados_disponibles)): ?>
                <span style="color: #6c757d; font-size: 0.8rem; font-style: italic;">Pedido cerrado</span>
              <?php else: ?>
                <?php foreach ($estados_disponibles as $estado): ?>
                  <?php
                  $icono = obtenerIconoEstado($estado);
                  $nombre = obtenerNombreEstado($estado);
                  ?>
                  <button class="state-btn <?= $estado ?>" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, '<?= $estado ?>')" title="Cambiar a <?= $nombre ?>">
                    <?= $icono ?>
                  </button>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </td>
          <?php if ($rol === 'administrador'): ?>
            <td class="action-cell">
              <?php if ($pedido['estado'] !== 'cerrado'): ?>
                <a href="<?= url('pedidos/edit', ['id' => $pedido['id_pedido']]) ?>" class="btn-action" title="Editar pedido">
                  ✏️
                </a>
                <a href="#" class="btn-action delete" title="Eliminar pedido" onclick="confirmarBorradoPedido(<?= $pedido['id_pedido'] ?>, 'Pedido #<?= $pedido['id_pedido'] ?>')">
                  ❌
                </a>
              <?php else: ?>
                <span class="btn-action disabled" title="No se puede editar un pedido cerrado" style="opacity: 0.5; cursor: not-allowed;">
                  ✏️
                </span>
                <span class="btn-action disabled" title="No se puede eliminar un pedido cerrado" style="opacity: 0.5; cursor: not-allowed;">
                  ❌
                </span>
              <?php endif; ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
</div>

<!-- Vista móvil con tarjetas -->
<div class="mobile-cards">
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
              <?php if ($pedido['estado'] !== 'cerrado'): ?>
                <a href="#" class="btn-action delete" title="Eliminar pedido" onclick="confirmarBorradoPedido(<?= $pedido['id_pedido'] ?>, 'Pedido #<?= $pedido['id_pedido'] ?>')">
                  ❌
                </a>
              <?php else: ?>
                <span class="btn-action disabled" title="No se puede eliminar un pedido cerrado" style="opacity: 0.5; cursor: not-allowed;">
                  🔒
                </span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        
        <div class="mobile-state-shortcuts">
          <div class="state-shortcuts">
            <?php 
            $estado_actual = $pedido['estado'];
            $estados_disponibles = obtenerTransicionesPermitidas($estado_actual);
            
            if (empty($estados_disponibles)): ?>
              <span style="color: #6c757d; font-size: 0.7rem; font-style: italic; text-align: center; display: block; padding: 0.3rem;">Pedido cerrado</span>
            <?php else: ?>
              <?php foreach ($estados_disponibles as $estado): ?>
                <?php
                $icono = obtenerIconoEstado($estado);
                $nombre = obtenerNombreEstado($estado);
                ?>
                <button class="state-btn <?= $estado ?>" onclick="confirmarCambioEstado(<?= $pedido['id_pedido'] ?>, '<?= $estado ?>')" title="Cambiar a <?= $nombre ?>">
                  <?= $icono ?>
                </button>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
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
              $estado = $pedido['estado'];
              $bg_color = '';
              $text_color = '';
              $icono = '';
              
              switch ($estado) {
                case 'pendiente':
                  $bg_color = '#fff3cd';
                  $text_color = '#856404';
                  $icono = '⏳';
                  break;
                case 'en_preparacion':
                  $bg_color = '#cce5ff';
                  $text_color = '#004085';
                  $icono = '👨‍🍳';
                  break;
                case 'servido':
                  $bg_color = '#d4edda';
                  $text_color = '#155724';
                  $icono = '✅';
                  break;
                case 'cerrado':
                  $bg_color = '#e2e3e5';
                  $text_color = '#383d41';
                  $icono = '🔒';
                  break;
                default:
                  $bg_color = '#f8d7da';
                  $text_color = '#721c24';
                  $icono = '❓';
              }
              ?>
              <span style="padding: 2px 6px; border-radius: 8px; font-size: 0.7em; font-weight: bold; 
                           background: <?= $bg_color ?>; 
                           color: <?= $text_color ?>;">
                <?= $icono ?> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $estado))) ?>
              </span>
            </div>
          </div>
          <div class="mobile-card-item">
            <div class="mobile-card-label">Total</div>
            <div class="mobile-card-value"><strong>$<?= number_format($pedido['total'] ?? 0, 2) ?></strong></div>
          </div>
          <div class="mobile-card-item">
            <div class="mobile-card-label">Método de Pago</div>
            <div class="mobile-card-value">
              <?php
              $forma_pago = $pedido['forma_pago'] ?? '';
              switch ($forma_pago) {
                  case 'efectivo':
                      echo '<span style="color: #28a745; font-weight: bold;">💵 Efectivo</span>';
                      break;
                  case 'tarjeta':
                      echo '<span style="color: #007bff; font-weight: bold;">💳 Tarjeta</span>';
                      break;
                  case 'transferencia':
                      echo '<span style="color: #6f42c1; font-weight: bold;">🏦 Transferencia</span>';
                      break;
                  default:
                      echo '<span style="color: #6c757d; font-style: italic;">Sin definir</span>';
              }
              ?>
            </div>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Solo tabla visible - sin tarjetas móviles por ahora
    
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
        let statusSpan;
        
        if (element.tagName === 'TR') {
            // Para filas de tabla, buscar en la columna 4 (Estado)
            statusSpan = element.querySelector('td:nth-child(4) span');
        } else {
            // Para tarjetas móviles, buscar en el span del estado
            const estadoItem = element.querySelector('.mobile-card-item:nth-child(3) .mobile-card-value span');
            statusSpan = estadoItem;
        }
        
        if (statusSpan) {
            let status = statusSpan.textContent.toLowerCase().trim().replace(/[⏳👨‍🍳✅💳🔒]/g, '').trim();
            
            // Mapear los nombres mostrados a los valores de la base de datos
            const statusMap = {
                'pendiente': 'pendiente',
                'en preparación': 'en_preparacion',
                'en preparacion': 'en_preparacion', // Agregar sin tilde también
                'servido': 'servido',
                'cerrado': 'cerrado'
            };
            
            return statusMap[status] || status;
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

// Función para mostrar/ocultar filtros
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

// Sistema de notificaciones temporales
function showNotification(message, type = 'success', duration = 4000) {
  const container = document.getElementById('notification-container');
  
  // Crear elemento de notificación
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  
  // Icono según el tipo y mensaje
  let icon = '✅';
  if (type === 'error') {
    icon = '❌';
  } else if (type === 'warning') {
    icon = '⚠️';
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
      message = 'Estado del pedido actualizado correctamente.';
    } else {
      message = successCode;
    }
    
    if (message) {
      showNotification(message, 'success', 5000);
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
      message = 'Error al actualizar el estado del pedido.';
    } else if (errorCode == '2') {
      message = 'Estado inválido.';
    } else if (errorCode == '3') {
      message = 'No se puede cambiar el estado de un pedido cerrado.';
    } else if (errorCode == '4') {
      message = '⚠️ El pedido ya tiene ese estado. No se realizó ningún cambio.';
    } else {
      message = errorCode;
    }
    
    if (message) {
      // Usar tipo 'warning' para el error 4, 'error' para los demás
      const notificationType = errorCode == '4' ? 'warning' : 'error';
      showNotification(message, notificationType, 6000);
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
</script>

<style>
/* Estilos para filtros desplegables */
.toggle-filters-btn {
    display: block;
    width: 100%;
    padding: 0.6rem 1rem;
    background: linear-gradient(135deg,rgb(233, 193, 130),rgb(146, 114, 60));
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.toggle-filters-btn:hover {
    background: linear-gradient(135deg,rgb(135, 103, 65),rgb(86, 66, 35));
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.filters-container {
    margin-bottom: 1rem;
}

/* Estilos responsivos para filtros */
@media (max-width: 768px) {
    .toggle-filters-btn {
        padding: 0.3rem !important;
        font-size: 0.8rem !important;
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


.state-btn.cerrado {
  background-color: #e2e3e5;
  color: #383d41;
}

.state-btn.cerrado:hover {
  background-color: #383d41;
  color: #e2e3e5;
}


/* Reglas específicas para pedidos - control estricto de visualización dual */

/* Desktop: SOLO tabla, NUNCA tarjetas móviles */
@media (min-width: 769px) {
    .table-responsive {
        display: block !important;
        visibility: visible !important;
    }
    
    .mobile-cards {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        max-height: 0 !important;
        overflow: hidden !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }
}

/* Móvil: SOLO tarjetas, NUNCA tabla */
@media (max-width: 768px) {
    .table-responsive {
        display: none !important;
        visibility: hidden !important;
    }
    
    .mobile-cards {
        display: block !important;
        visibility: visible !important;
        max-height: 60vh;
        overflow-y: auto;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 0.5rem;
    }
}

/* Regla de emergencia para desktop */
@media screen and (min-width: 769px) {
    .mobile-cards,
    .mobile-cards * {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        max-height: 0 !important;
        overflow: hidden !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
        position: absolute !important;
        left: -9999px !important;
        top: -9999px !important;
    }
}

/* Estilos para notificaciones temporales */
#notification-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 2000;
  max-width: 400px;
}

.notification {
  background: white;
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 12px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-left: 4px solid;
  display: flex;
  align-items: center;
  gap: 12px;
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

.notification.warning {
  border-left-color: #ffc107;
  background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
}

.notification-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.notification-content {
  flex: 1;
  color: #333;
  font-weight: 500;
  font-size: 0.95rem;
  line-height: 1.4;
}

.notification-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #666;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
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

.notification.warning::after {
  background: #ffc107;
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
@media (max-width: 480px) {
  #notification-container {
    top: 10px;
    right: 10px;
    left: 10px;
    max-width: none;
  }
  
  .notification {
    padding: 12px 16px;
    font-size: 0.9rem;
  }
  
  .notification-icon {
    font-size: 1.3rem;
  }
}
</style>

<?php
// Incluir footer
require_once __DIR__ . '/../includes/footer.php';
?>
