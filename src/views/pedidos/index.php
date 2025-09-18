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
    // Si el pedido está cerrado, no se puede cambiar
    if ($estado_actual === 'cerrado') {
        return false;
    }

    // Definir transiciones permitidas (hacia adelante y hacia atrás)
    $transiciones_permitidas = [
        'pendiente' => ['en_preparacion', 'cuenta', 'cerrado'], // pendiente → en_preparacion, cuenta o cerrado
        'en_preparacion' => ['pendiente', 'servido', 'cuenta', 'cerrado'], // en_preparacion → pendiente, servido, cuenta o cerrado
        'servido' => ['pendiente', 'en_preparacion', 'cuenta', 'cerrado'], // servido → pendiente, en_preparacion, cuenta o cerrado
        'cuenta' => ['pendiente', 'en_preparacion', 'servido', 'cerrado'], // cuenta → pendiente, en_preparacion, servido o cerrado
        'cerrado' => [] // cerrado no puede cambiar
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
        'cuenta' => 'Por Pagar',
        'cerrado' => 'Cerrado'
    ];

    return $nombres[$estado] ?? $estado;
}

// Función para obtener transiciones permitidas desde un estado
function obtenerTransicionesPermitidas($estado_actual) {
    $transiciones = [
        'pendiente' => ['en_preparacion', 'cuenta', 'cerrado'],
        'en_preparacion' => ['pendiente', 'servido', 'cuenta', 'cerrado'],
        'servido' => ['pendiente', 'en_preparacion', 'cuenta', 'cerrado'],
        'cuenta' => ['pendiente', 'en_preparacion', 'servido', 'cerrado'],
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
        'cuenta' => '💳',
        'cerrado' => '🔒'
    ];

    return $iconos[$estado] ?? '❓';
}

// El procesamiento de cambio de estado ahora se maneja via AJAX en el controlador

// Cargar pedidos según el rol
if ($rol === 'mozo') {
    // Los mozos solo ven pedidos del día actual
    $pedidos = Pedido::todayOnly();
} else {
    // Los administradores ven todos los pedidos
    $pedidos = Pedido::all();
}

// Función para generar botones de acción
function generarBotonesAccion($pedido, $rol) {
    $botones = '';
    
    if ($rol === 'administrador') {
        // Botón Ver información (siempre disponible)
        $botones .= '<button class="btn-action btn-info" title="Ver información del pedido" onclick="mostrarInfoPedido(' . $pedido['id_pedido'] . ')">
            <span class="btn-icon">👁️</span>
            <span class="btn-text">Ver</span>
        </button>';
        
        if ($pedido['estado'] !== 'cerrado') {
            // Botón Editar (solo si no está cerrado)
            $botones .= '<a href="' . url('pedidos/edit', ['id' => $pedido['id_pedido']]) . '" class="btn-action btn-edit" title="Editar pedido">
                <span class="btn-icon">✏️</span>
                <span class="btn-text">Editar</span>
            </a>';
            
            // Botón Eliminar (solo si no está cerrado)
            $botones .= '<button class="btn-action btn-delete" title="Eliminar pedido" onclick="confirmarBorradoPedido(' . $pedido['id_pedido'] . ', \'Pedido #' . $pedido['id_pedido'] . '\')">
                <span class="btn-icon">🗑️</span>
                <span class="btn-text">Eliminar</span>
            </button>';
        } else {
            // Botones deshabilitados para pedidos cerrados
            $botones .= '<button class="btn-action btn-disabled" title="No se puede editar un pedido cerrado" disabled>
                <span class="btn-icon">✏️</span>
                <span class="btn-text">Editar</span>
            </button>';
            
            $botones .= '<button class="btn-action btn-disabled" title="No se puede eliminar un pedido cerrado" disabled>
                <span class="btn-icon">🗑️</span>
                <span class="btn-text">Eliminar</span>
            </button>';
        }
    }
    
    return $botones;
}

// Función para generar botones de cambio de estado
function generarBotonesEstado($pedido, $isMobile = false) {
    $estado_actual = $pedido['estado'];
    $estados_disponibles = obtenerTransicionesPermitidas($estado_actual);
    
    if (empty($estados_disponibles)) {
        $style = $isMobile ? 'color: #6c757d; font-size: 0.7rem; font-style: italic; text-align: center; display: block; padding: 0.3rem;' : 'color: #6c757d; font-size: 0.8rem; font-style: italic;';
        return '<span style="' . $style . '">Pedido cerrado</span>';
    }
    
    $botones = '';
    foreach ($estados_disponibles as $estado) {
        $icono = obtenerIconoEstado($estado);
        $nombre = obtenerNombreEstado($estado);
        $botones .= '<button class="state-btn ' . $estado . '" onclick="confirmarCambioEstado(' . $pedido['id_pedido'] . ', \'' . $estado . '\')" title="Cambiar a ' . $nombre . '">
            ' . $icono . '
        </button>';
    }
    
    return $botones;
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
    🔍 <strong>Filtrar</strong>
  </button>
  
  <div id="filtersContent" class="search-filter" style="background: rgb(238, 224, 191); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.6rem; margin-bottom: 0.8rem; display: none; transition: all 0.3s ease;">
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
      <button class="status-filter-btn" data-status="cuenta" style="padding: 4px 8px; border: none; background: #e7f3ff; color: #0066cc; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
        💳 Por Pagar
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
        <?php
          $fechaRaw = $pedido['fecha_creacion'] ?? null;
          $fechaIso = $fechaRaw ? date('Y-m-d', strtotime($fechaRaw)) : '';
          $fechaFormateada = $fechaRaw ? date('d/m/Y H:i', strtotime($fechaRaw)) : 'N/A';
        ?>
        <tr data-fecha="<?= htmlspecialchars($fechaIso) ?>">
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
                    $bg_color = '#e7f3ff';
                    $text_color = '#0066cc';
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
          <td data-role="fecha" data-fecha="<?= htmlspecialchars($fechaIso) ?>">
            <?= htmlspecialchars($fechaFormateada) ?>
          </td>
          <td class="action-cell">
            <div class="state-shortcuts">
              <?= generarBotonesEstado($pedido) ?>
            </div>
          </td>
          <?php if ($rol === 'administrador'): ?>
            <td class="action-cell">
              <div class="action-buttons-container">
                <?= generarBotonesAccion($pedido, $rol) ?>
              </div>
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
      <?php
        $fechaRaw = $pedido['fecha_creacion'] ?? null;
        $fechaIso = $fechaRaw ? date('Y-m-d', strtotime($fechaRaw)) : '';
        $fechaFormateada = $fechaRaw ? date('d/m/Y H:i', strtotime($fechaRaw)) : 'N/A';
      ?>
      <div class="mobile-card" data-fecha="<?= htmlspecialchars($fechaIso) ?>">
        <div class="mobile-card-header">
          <div class="mobile-card-number">
            Pedido #<?= htmlspecialchars($pedido['id_pedido']) ?>
          </div>
          <div class="mobile-card-actions">
            <div class="action-buttons-container">
              <?= generarBotonesAccion($pedido, $rol) ?>
            </div>
          </div>
        </div>
        
        <div class="mobile-state-shortcuts">
          <div class="state-shortcuts">
            <?= generarBotonesEstado($pedido, true) ?>
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
                case 'cuenta':
                  $bg_color = '#e7f3ff';
                  $text_color = '#0066cc';
                  $icono = '💳';
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
          <div class="mobile-card-item" data-role="fecha">
            <div class="mobile-card-label">Fecha</div>
            <div class="mobile-card-value" data-fecha="<?= htmlspecialchars($fechaIso) ?>">
              <?= htmlspecialchars($fechaFormateada) ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Modal de confirmación para cambio de estado -->
<div id="modalCambioEstado" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
  <div style="background: rgb(247, 241, 225); padding: 2rem; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 400px; width: 90%; text-align: center;">
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

<!-- Modal de información del pedido -->
<div id="modalInfoPedido" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
  <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #eee; padding-bottom: 1rem;">
      <h3 style="margin: 0; color: var(--secondary); font-size: 1.5rem;">📋 Información del Pedido</h3>
      <button id="cerrarInfoPedido" style="background: #6c757d; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-size: 1rem;">
        ✕
      </button>
    </div>
    
    <div id="contenidoInfoPedido">
      <!-- El contenido se cargará dinámicamente -->
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
                'por pagar': 'cuenta',
                'cuenta': 'cuenta',
                'cerrado': 'cerrado'
            };
            
            return statusMap[status] || status;
        }
        return '';
    }
    
    function getPedidoFecha(element) {
        if (element.dataset && element.dataset.fecha) {
            return element.dataset.fecha;
        }

        const fechaCell = element.querySelector('[data-fecha]');
        if (fechaCell) {
            if (fechaCell.dataset && fechaCell.dataset.fecha) {
                return fechaCell.dataset.fecha;
            }

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
        toggleBtn.innerHTML = '🔍 <strong>Ocultar Filtros</strong>';
    } else {
        filtersContent.style.display = 'none';
        toggleBtn.innerHTML = '🔍 <strong>Filtrar</strong>';
    }
}

// Variables globales para el cambio de estado
let pedidoIdCambio = null;
let nuevoEstadoCambio = null;

// Función para actualizar el estado de un pedido en la interfaz
function actualizarEstadoPedido(pedidoId, nuevoEstado) {
    // Mapear estados a nombres e iconos
    const nombresEstados = {
        'pendiente': 'Pendiente',
        'en_preparacion': 'En Preparación',
        'servido': 'Servido',
        'cuenta': 'Por Pagar',
        'cerrado': 'Cerrado'
    };

    const iconosEstados = {
        'pendiente': '⏳',
        'en_preparacion': '👨‍🍳',
        'servido': '✅',
        'cuenta': '💳',
        'cerrado': '🔒'
    };

    const coloresEstados = {
        'pendiente': { bg: '#fff3cd', text: '#856404' },
        'en_preparacion': { bg: '#cce5ff', text: '#004085' },
        'servido': { bg: '#d4edda', text: '#155724' },
        'cuenta': { bg: '#e7f3ff', text: '#0066cc' },
        'cerrado': { bg: '#e2e3e5', text: '#383d41' }
    };
    
    const nombreEstado = nombresEstados[nuevoEstado];
    const iconoEstado = iconosEstados[nuevoEstado];
    const colores = coloresEstados[nuevoEstado];
    
    // Actualizar en la tabla
    const filasTabla = document.querySelectorAll('.table tbody tr');
    filasTabla.forEach(fila => {
        const idCell = fila.querySelector('td:nth-child(1)');
        if (idCell && idCell.textContent.trim() == pedidoId) {
            // Actualizar el estado en la columna 4
            const estadoCell = fila.querySelector('td:nth-child(4) span');
            if (estadoCell) {
                estadoCell.innerHTML = `${iconoEstado} ${nombreEstado}`;
                estadoCell.style.background = colores.bg;
                estadoCell.style.color = colores.text;
            }
            
            // Actualizar los botones de estado en la columna 8
            const botonesCell = fila.querySelector('td:nth-child(8) .state-shortcuts');
            if (botonesCell) {
                // Obtener transiciones permitidas para el nuevo estado
                const transiciones = obtenerTransicionesPermitidas(nuevoEstado);
                
                // Limpiar botones existentes
                botonesCell.innerHTML = '';
                
                if (transiciones.length === 0) {
                    // Si no hay transiciones, mostrar mensaje
                    const span = document.createElement('span');
                    span.style.color = '#6c757d';
                    span.style.fontSize = '0.8rem';
                    span.style.fontStyle = 'italic';
                    span.textContent = 'Pedido cerrado';
                    botonesCell.appendChild(span);
                } else {
                    // Crear botones para las transiciones permitidas
                    transiciones.forEach(estado => {
                        const boton = document.createElement('button');
                        boton.className = `state-btn ${estado}`;
                        boton.onclick = () => confirmarCambioEstado(pedidoId, estado);
                        boton.title = `Cambiar a ${nombresEstados[estado]}`;
                        boton.innerHTML = iconosEstados[estado];
                        botonesCell.appendChild(boton);
                    });
                }
            }
        }
    });
    
    // Actualizar en las tarjetas móviles
    const tarjetasMoviles = document.querySelectorAll('.mobile-cards .mobile-card');
    tarjetasMoviles.forEach(tarjeta => {
        const header = tarjeta.querySelector('.mobile-card-number');
        if (header && header.textContent.includes(`#${pedidoId}`)) {
            // Actualizar el estado en la tarjeta
            const estadoItem = tarjeta.querySelector('.mobile-card-item:nth-child(3) .mobile-card-value span');
            if (estadoItem) {
                estadoItem.innerHTML = `${iconoEstado} ${nombreEstado}`;
                estadoItem.style.background = colores.bg;
                estadoItem.style.color = colores.text;
            }
            
            // Actualizar los botones de estado
            const botonesContainer = tarjeta.querySelector('.mobile-state-shortcuts .state-shortcuts');
            if (botonesContainer) {
                const transiciones = obtenerTransicionesPermitidas(nuevoEstado);
                
                // Limpiar botones existentes
                botonesContainer.innerHTML = '';
                
                if (transiciones.length === 0) {
                    const span = document.createElement('span');
                    span.style.color = '#6c757d';
                    span.style.fontSize = '0.7rem';
                    span.style.fontStyle = 'italic';
                    span.style.textAlign = 'center';
                    span.style.display = 'block';
                    span.style.padding = '0.3rem';
                    span.textContent = 'Pedido cerrado';
                    botonesContainer.appendChild(span);
                } else {
                    transiciones.forEach(estado => {
                        const boton = document.createElement('button');
                        boton.className = `state-btn ${estado}`;
                        boton.onclick = () => confirmarCambioEstado(pedidoId, estado);
                        boton.title = `Cambiar a ${nombresEstados[estado]}`;
                        boton.innerHTML = iconosEstados[estado];
                        botonesContainer.appendChild(boton);
                    });
                }
            }
        }
    });
}

// Función auxiliar para obtener transiciones permitidas (duplicada del PHP)
function obtenerTransicionesPermitidas(estado_actual) {
    const transiciones = {
        'pendiente': ['en_preparacion', 'cuenta', 'cerrado'],
        'en_preparacion': ['pendiente', 'servido', 'cuenta', 'cerrado'],
        'servido': ['pendiente', 'en_preparacion', 'cuenta', 'cerrado'],
        'cuenta' => ['pendiente', 'en_preparacion', 'servido', 'cerrado'],
        'cerrado': []
    };

    return transiciones[estado_actual] || [];
}

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
        'cuenta': 'Por Pagar',
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
            // Enviar petición AJAX directamente
            const formData = new FormData();
            formData.append('id_pedido', pedidoIdCambio);
            formData.append('estado', nuevoEstadoCambio);
            
            console.log('Enviando petición de cambio de estado...');
            console.log('ID:', pedidoIdCambio, 'Estado:', nuevoEstadoCambio);
            
            fetch('index.php?route=pedidos/update-estado', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.success) {
                        // Mostrar notificación de éxito
                        showNotification(data.message, 'success', 4000);
                        
                        // Actualizar la interfaz sin recargar
                        actualizarEstadoPedido(pedidoIdCambio, nuevoEstadoCambio);
                    } else {
                        // Mostrar notificación de error
                        showNotification(data.message, 'error', 6000);
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.log('Raw response:', text);
                    showNotification('Error al procesar la respuesta del servidor: ' + text.substring(0, 100), 'error', 6000);
                }
                
                // Cerrar modal
                modal.style.display = 'none';
                pedidoIdCambio = null;
                nuevoEstadoCambio = null;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showNotification('Error de conexión: ' + error.message, 'error', 6000);
                modal.style.display = 'none';
                pedidoIdCambio = null;
                nuevoEstadoCambio = null;
            });
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

// Función para mostrar la información del pedido
function mostrarInfoPedido(pedidoId) {
    console.log('=== INICIANDO mostrarInfoPedido ===');
    console.log('Pedido ID recibido:', pedidoId);
    console.log('Tipo de pedidoId:', typeof pedidoId);
    
    const modal = document.getElementById('modalInfoPedido');
    const contenido = document.getElementById('contenidoInfoPedido');
    
    console.log('Modal encontrado:', modal);
    console.log('Contenido encontrado:', contenido);
    
    if (!modal || !contenido) {
        console.error('No se encontraron los elementos del modal');
        alert('Error: No se encontraron los elementos del modal');
        return;
    }
    
    // Agregar estado de carga al botón
    const infoButtons = document.querySelectorAll(`[onclick="mostrarInfoPedido(${pedidoId})"]`);
    infoButtons.forEach(btn => {
        btn.classList.add('loading');
        btn.disabled = true;
    });
    
    // Mostrar modal con loading
    contenido.innerHTML = '<div style="text-align: center; padding: 2rem;"><div style="font-size: 2rem; margin-bottom: 1rem;">⏳</div><p>Cargando información del pedido...</p></div>';
    modal.style.display = 'flex';
    
    // Hacer petición AJAX para obtener los detalles del pedido
    const url = `index.php?route=pedidos/info&id=${pedidoId}`;
    console.log('URL de petición:', url);
    console.log('Llamando a fetch con pedido:', pedidoId);
    
    fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => {
            console.log('Respuesta recibida:', r);
            console.log('Status:', r.status);
            console.log('OK:', r.ok);
            console.log('Content-Type:', r.headers.get('content-type'));
            
            // Verificar si es HTML (redirección al login)
            if (r.headers.get('content-type')?.includes('text/html')) {
                throw new Error('El servidor devolvió HTML en lugar de JSON. Posible problema de sesión.');
            }
            
            return r.json();
        })
        .then(data => {
            console.log('Respuesta JSON:', data);
            if (data.success) {
                mostrarContenidoPedido(data.pedido);
            } else {
                console.error('Error en respuesta:', data);
                contenido.innerHTML = `<div style="text-align: center; padding: 2rem; color: #dc3545;"><div style="font-size: 2rem; margin-bottom: 1rem;">❌</div><p>Error al cargar la información del pedido: ${data.error || data.message || 'Error desconocido'}</p></div>`;
            }
        })
        .catch(err => {
            console.error('Error en fetch:', err);
            contenido.innerHTML = `<div style="text-align: center; padding: 2rem; color: #dc3545;"><div style="font-size: 2rem; margin-bottom: 1rem;">❌</div><p>Error de conexión: ${err.message}</p></div>`;
        })
        .finally(() => {
            // Remover estado de carga de los botones
            const infoButtons = document.querySelectorAll(`[onclick="mostrarInfoPedido(${pedidoId})"]`);
            infoButtons.forEach(btn => {
                btn.classList.remove('loading');
                btn.disabled = false;
            });
        });
}

// Función para mostrar el contenido del pedido
function mostrarContenidoPedido(pedido) {
    const contenido = document.getElementById('contenidoInfoPedido');
    
    // Formatear fecha
    const fecha = new Date(pedido.fecha_creacion).toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Formatear estado
    const estados = {
        'pendiente': '⏳ Pendiente',
        'en_preparacion': '👨‍🍳 En Preparación',
        'servido': '✅ Servido',
        'cerrado': '🔒 Cerrado'
    };
    
    // Formatear forma de pago
    const formasPago = {
        'efectivo': '💵 Efectivo',
        'tarjeta': '💳 Tarjeta',
        'transferencia': '🏦 Transferencia'
    };
    
    let html = `
        <div style="display: grid; gap: 1.5rem;">
            <!-- Información básica -->
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border-left: 4px solid var(--secondary);">
                <h4 style="margin: 0 0 1rem 0; color: var(--secondary); font-size: 1.1rem;">📋 Información Básica</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.8rem;">
                    <div><strong>ID del Pedido:</strong> #${pedido.id_pedido}</div>
                    <div><strong>Estado:</strong> ${estados[pedido.estado] || pedido.estado}</div>
                    <div><strong>Mesa:</strong> ${pedido.numero_mesa || 'N/A'}</div>
                    <div><strong>Mozo:</strong> ${pedido.nombre_mozo_completo || 'N/A'}</div>
                    <div><strong>Fecha:</strong> ${fecha}</div>
                    <div><strong>Total:</strong> $${parseFloat(pedido.total).toFixed(2)}</div>
                    <div><strong>Forma de Pago:</strong> ${formasPago[pedido.forma_pago] || pedido.forma_pago || 'Sin definir'}</div>
                </div>
            </div>
            
            <!-- Información del cliente -->
            ${pedido.cliente_nombre || pedido.cliente_email ? `
            <div style="background: #e3f2fd; padding: 1rem; border-radius: 6px; border-left: 4px solid #2196f3;">
                <h4 style="margin: 0 0 1rem 0; color: #1976d2; font-size: 1.1rem;">👤 Información del Cliente</h4>
                <div style="display: grid; gap: 0.5rem;">
                    ${pedido.cliente_nombre ? `<div><strong>Nombre:</strong> ${pedido.cliente_nombre}</div>` : ''}
                    ${pedido.cliente_email ? `<div><strong>Email:</strong> ${pedido.cliente_email}</div>` : ''}
                </div>
            </div>
            ` : ''}
            
            <!-- Observaciones -->
            ${pedido.observaciones ? `
            <div style="background: #fff3e0; padding: 1rem; border-radius: 6px; border-left: 4px solid #ff9800;">
                <h4 style="margin: 0 0 1rem 0; color: #f57c00; font-size: 1.1rem;">📝 Observaciones</h4>
                <p style="margin: 0; font-style: italic;">${pedido.observaciones}</p>
            </div>
            ` : ''}
            
            <!-- Items del pedido -->
            <div style="background: #f1f8e9; padding: 1rem; border-radius: 6px; border-left: 4px solid #4caf50;">
                <h4 style="margin: 0 0 1rem 0; color: #388e3c; font-size: 1.1rem;">🍽️ Items del Pedido</h4>
                <div style="display: grid; gap: 0.8rem;">
    `;
    
    // Agregar cada item del pedido
    if (pedido.items && pedido.items.length > 0) {
        pedido.items.forEach((item, index) => {
            html += `
                <div style="background: white; padding: 0.8rem; border-radius: 4px; border: 1px solid #e0e0e0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <div style="flex: 1;">
                            <div style="font-weight: bold; color: var(--secondary); margin-bottom: 0.3rem;">${item.nombre}</div>
                            <div style="font-size: 0.9rem; color: #666;">Categoría: ${item.categoria || 'Sin categoría'}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: bold; color: var(--secondary);">$${parseFloat(item.precio).toFixed(2)}</div>
                            <div style="font-size: 0.9rem; color: #666;">Cantidad: ${item.cantidad}</div>
                        </div>
                    </div>
                    ${item.detalle ? `
                        <div style="background: #fff3cd; padding: 0.5rem; border-radius: 4px; border-left: 3px solid #ffc107; margin-top: 0.5rem;">
                            <div style="font-size: 0.9rem; color: #856404; font-weight: bold; margin-bottom: 0.2rem;">📝 Detalle especial:</div>
                            <div style="font-size: 0.9rem; color: #856404; font-style: italic;">${item.detalle}</div>
                        </div>
                    ` : ''}
                </div>
            `;
        });
    } else {
        html += '<div style="text-align: center; color: #666; font-style: italic; padding: 1rem;">No hay items en este pedido</div>';
    }
    
    html += `
                </div>
            </div>
        </div>
    `;
    
    contenido.innerHTML = html;
}

// Función mejorada para confirmar eliminación
function confirmarBorradoPedido(pedidoId, nombrePedido) {
    // Crear modal de confirmación personalizado
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        z-index: 2000;
        display: flex;
        justify-content: center;
        align-items: center;
    `;
    
    modal.innerHTML = `
        <div style="
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: modalSlideIn 0.3s ease-out;
        ">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
            <h3 style="margin: 0 0 1rem 0; color: #dc3545; font-size: 1.3rem;">Confirmar Eliminación</h3>
            <p style="margin: 0 0 1.5rem 0; color: #666; line-height: 1.5;">
                ¿Estás seguro de que quieres eliminar <strong>${nombrePedido}</strong>?<br>
                <small style="color: #999;">Esta acción no se puede deshacer.</small>
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button id="confirmarEliminar" style="
                    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                    color: white;
                    border: none;
                    padding: 0.75rem 1.5rem;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 1rem;
                    font-weight: 600;
                    transition: all 0.3s ease;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(220, 53, 69, 0.3)'" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    🗑️ Eliminar
                </button>
                <button id="cancelarEliminar" style="
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 0.75rem 1.5rem;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 1rem;
                    font-weight: 600;
                    transition: all 0.3s ease;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(108, 117, 125, 0.3)'" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    Cancelar
                </button>
            </div>
        </div>
    `;
    
    // Agregar animación CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(modal);
    
    // Event listeners
    document.getElementById('confirmarEliminar').addEventListener('click', function() {
        // Agregar estado de carga
        this.innerHTML = '⏳ Eliminando...';
        this.disabled = true;
        
        // Redirigir a la eliminación
        window.location.href = `index.php?route=pedidos/delete&delete=${pedidoId}`;
    });
    
    document.getElementById('cancelarEliminar').addEventListener('click', function() {
        document.body.removeChild(modal);
        document.head.removeChild(style);
    });
    
    // Cerrar al hacer clic fuera del modal
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
            document.head.removeChild(style);
        }
    });
}

// Event listeners para el modal de información
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalInfoPedido');
    const cerrarBtn = document.getElementById('cerrarInfoPedido');
    
    if (cerrarBtn) {
        cerrarBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    // Cerrar modal al hacer clic fuera de él
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
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
    
    /* Contenedor de botones de acción */
    .action-buttons-container {
        display: flex;
        gap: 0.4rem;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    /* Célula de acciones en la tabla */
    .action-cell {
        text-align: center;
        vertical-align: middle;
        padding: 0.5rem !important;
    }
    
    /* Estilos base para botones de acción */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.4rem 0.8rem;
        border: none;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        min-width: 60px;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
    }
    
    .btn-action:before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    .btn-action:hover:before {
        left: 100%;
    }
    
    .btn-icon {
        font-size: 0.9rem;
        line-height: 1;
    }
    
    .btn-text {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    
    /* Botón de información */
    .btn-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        color: white;
        border: 1px solid #138496;
    }
    
    .btn-info:hover {
        background: linear-gradient(135deg, #138496 0%, #0f6674 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        color: white;
    }
    
    .btn-info:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(23, 162, 184, 0.2);
    }
    
    /* Botón de editar */
    .btn-edit {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: 1px solid #20c997;
    }
    
    .btn-edit:hover {
        background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        color: white;
    }
    
    .btn-edit:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2);
    }
    
    /* Botón de eliminar */
    .btn-delete {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: 1px solid #c82333;
    }
    
    .btn-delete:hover {
        background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        color: white;
    }
    
    .btn-delete:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(220, 53, 69, 0.2);
    }
    
    /* Botón deshabilitado */
    .btn-disabled {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        color: #adb5bd;
        border: 1px solid #5a6268;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .btn-disabled:hover {
        transform: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    }
    
    /* Estilos específicos para móvil */
    .mobile-card-actions .action-buttons-container {
        gap: 0.3rem;
        justify-content: flex-end;
    }
    
    .mobile-card-actions .btn-action {
        padding: 0.3rem 0.6rem;
        font-size: 0.7rem;
        min-width: 50px;
    }
    
    .mobile-card-actions .btn-text {
        font-size: 0.65rem;
    }
    
    .mobile-card-actions .btn-icon {
        font-size: 0.8rem;
    }
    
    /* Mejoras de accesibilidad */
    .btn-action:focus {
        outline: 2px solid rgba(23, 162, 184, 0.5);
        outline-offset: 2px;
    }
    
    .btn-info:focus {
        outline-color: rgba(23, 162, 184, 0.5);
    }
    
    .btn-edit:focus {
        outline-color: rgba(40, 167, 69, 0.5);
    }
    
    .btn-delete:focus {
        outline-color: rgba(220, 53, 69, 0.5);
    }
    
    /* Animación de carga para botones */
    .btn-action.loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .btn-action.loading .btn-icon {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Mejoras para pantallas muy pequeñas */
    @media (max-width: 480px) {
        .action-buttons-container {
            gap: 0.2rem;
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.65rem;
            min-width: 45px;
        }
        
        .btn-text {
            display: none; /* Solo mostrar iconos en pantallas muy pequeñas */
        }
        
        .btn-action {
            min-width: 35px;
            justify-content: center;
        }
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
  background-color: #e7f3ff;
  color: #0066cc;
}

.state-btn.cuenta:hover {
  background-color: #0066cc;
  color: #e7f3ff;
}

/* Estilos para botones de estado */
.state-btn {
  border: none;
  border-radius: 6px;
  padding: 6px 10px;
  margin: 2px;
  cursor: pointer;
  font-size: 0.8rem;
  font-weight: 500;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  height: 32px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.state-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.state-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
</main>

