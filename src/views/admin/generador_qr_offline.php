<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Mesa;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: index.php?route=unauthorized');
    exit;
}

$baseUrl = rtrim(getBaseUrl(), '/');
$mesas = Mesa::all();
$totalMesas = count($mesas);

$statusClasses = [
    'libre' => 'status-badge status-active',
    'ocupada' => 'status-badge status-inactive',
    'reservada' => 'status-badge status-warning',
];

$takeawayPresets = [
    [
        'id' => 'takeaway-principal',
        'title' => 'Take away',
        'subtitle' => 'Pedidos para llevar',
        'name' => 'TAKE_AWAY',
        'url' => $baseUrl . '/index.php?route=cliente&takeaway=1',
    ],
];
?>
<!-- Estilos específicos para móvil -->
<style>
/* Correcciones móvil para QR Mesas */
@media (max-width: 768px) {
    /* Ajustar botones de tipo QR */
    .tipo-qr-selector {
        padding: 0.75rem !important;
    }

    .tipo-qr-selector span {
        display: block !important;
        margin-bottom: 0.5rem !important;
        font-size: 0.85rem !important;
    }

    .tipo-qr-selector div {
        display: flex !important;
        gap: 0.3rem !important;
    }

    .btn-tipo-qr {
        flex: 1 !important;
        min-width: 0 !important;
        padding: 0.6rem 0.5rem !important;
        font-size: 0.75rem !important;
    }

    /* Ajustar grid principal */
    #qr-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
        padding: 0 10px !important;
    }

    /* Configuración QR responsive */
    .qr-config-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 0.5rem !important;
    }

    /* Botones de acción */
    .qr-action-buttons {
        flex-direction: column !important;
    }

    .qr-action-buttons button {
        width: 100% !important;
        margin-bottom: 0.5rem !important;
    }

    /* Cards de QR */
    .qr-card {
        padding: 0.75rem !important;
    }

    /* Ajustar h2 principal */
    h2 {
        font-size: 1.3rem !important;
        margin-bottom: 1rem !important;
        padding: 0 10px !important;
    }

    /* Estadísticas */
    .stats-grid {
        grid-template-columns: 1fr !important;
        gap: 0.75rem !important;
    }
}

@media (max-width: 480px) {
    /* Móviles pequeños */
    .qr-config-grid {
        grid-template-columns: 1fr !important;
    }

    .btn-tipo-qr {
        font-size: 0.7rem !important;
        padding: 0.5rem 0.3rem !important;
    }
}
</style>

<!-- Header de gestión -->
<div class="management-header">
  <h1>🔲 Gestión de Códigos QR</h1>
  <div class="header-actions">
    <button type="button" class="header-btn" onclick="regenerarQRs()">
      🔄 Actualizar QRs
    </button>
    <button type="button" class="header-btn secondary" onclick="descargarSeleccionados()">
      ⬇️ Descargar seleccionados
    </button>
    <button type="button" class="header-btn secondary" onclick="imprimirSeleccionados()">
      🖨️ Imprimir seleccionados
    </button>
  </div>
</div>

<!-- Configuración de QR -->
<div class="filters-container" style="margin-bottom: 1.5rem;">
    <div class="search-filter" style="background: rgb(250, 238, 193); border: 1px solid #e0e0e0; border-radius: 6px; padding: 1rem;">
        <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary); font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
            ⚙️ Configuración de QR
        </h3>
        
        <div class="qr-config-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
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
        
        <div class="qr-action-buttons" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
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
        <div class="tipo-qr-selector" style="padding: 0.75rem; background: #f8f9fa; border-radius: 6px; border: 1px solid #e0e0e0;">
            <span style="display: block; font-weight: 600; color: var(--secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">Tipo de QR:</span>
            <div style="display: flex; gap: 0.5rem;">
                <button id="btn-stay" onclick="mostrarTipoQR('stay')" class="button btn-tipo-qr" style="flex: 1; padding: 0.5rem 0.75rem; font-size: 0.8rem; background: var(--secondary); color: white; white-space: nowrap; overflow: hidden;">
                    🪑 STAY
                </button>
                <button id="btn-takeaway" onclick="mostrarTipoQR('takeaway')" class="button btn-tipo-qr" style="flex: 1; padding: 0.5rem 0.75rem; font-size: 0.8rem; background: #6c757d; color: white; white-space: nowrap; overflow: hidden;">
                    🥡 TAKE AWAY
                </button>
            </div>
        </div>
      </div>
    </div>

    <!-- Configuración de QR -->
    <div class="filter-group">
      <label>⚙️ Configuración QR:</label>
      <div class="qr-config-grid">
        <div class="qr-config-item">
          <span>Tamaño (px)</span>
          <input type="number" id="qr-size" class="js-qr-config" value="220" min="120" max="520" step="10">
        </div>
        <div class="qr-config-item">
          <span>Margen</span>
          <input type="number" id="qr-margin" class="js-qr-config" value="1" min="0" max="10">
        </div>
        <div class="qr-config-item">
          <span>Color</span>
          <input type="color" id="qr-color" class="js-qr-config" value="#000000">
        </div>
        <div class="qr-config-item">
          <span>Fondo</span>
          <input type="color" id="qr-bgcolor" class="js-qr-config" value="#FFFFFF">
        </div>
      </div>
      <button type="button" class="btn-modern btn-edit" onclick="regenerarQRs()" style="margin-top: 0.5rem; width: 100%;">
        🔄 Aplicar cambios
      </button>
    </div>
  </div>
</div>

<!-- Estadísticas rápidas -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
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

<!-- Vista de tabla para desktop -->
<div class="table-responsive" id="qr-stay-section">
<table class="table">
  <thead>
    <tr>
      <th>Mesa</th>
      <th>Ubicación</th>
      <th>Estado</th>
      <th>Mozo</th>
      <th>Código QR</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mesas as $mesa): ?>
      <?php
        $estado = strtolower($mesa['estado'] ?? 'libre');
        $badgeClass = $statusClasses[$estado] ?? 'status-badge status-neutral';
        $estadoLabel = ucfirst($estado);
        $mesaNumero = (int) ($mesa['numero'] ?? 0);
        $idMesa = (int) ($mesa['id_mesa'] ?? 0);
        $ubicacion = $mesa['ubicacion'] ?? '';
        $capacidad = $mesa['capacidad'] ?? null;
        $mozo = $mesa['mozo_nombre_completo'] ?? null;
        $pedidosActivos = (int) ($mesa['pedidos_activos'] ?? 0);
        $mesaUrl = $baseUrl . '/index.php?route=cliente&qr=' . rawurlencode((string) $mesaNumero);
      ?>
      <tr data-mesa-number="<?php echo htmlspecialchars((string) $mesaNumero); ?>" data-ubicacion="<?php echo htmlspecialchars($ubicacion); ?>" data-mozo="<?php echo htmlspecialchars((string) $mozo); ?>">
        <td>
          <strong>Mesa <?php echo htmlspecialchars((string) $mesaNumero); ?></strong>
          <?php if ($capacidad): ?>
            <br><small>Capacidad: <?php echo htmlspecialchars((string) $capacidad); ?></small>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($ubicacion ?? '—'); ?></td>
        <td>
          <span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($estadoLabel); ?></span>
          <?php if ($pedidosActivos > 0): ?>
            <br><small style="color: #dc3545;"><?php echo $pedidosActivos; ?> pedidos activos</small>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($mozo): ?>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; background: #e2e3e5; color: #383d41;">
              👤 <?php echo htmlspecialchars($mozo); ?>
            </span>
          <?php else: ?>
            <span style="color: #6c757d; font-style: italic;">Sin asignar</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="qr-card-preview" id="qr-mesa-<?php echo $idMesa; ?>">
            <div class="qr-card-placeholder">
              <div class="qr-spinner" aria-hidden="true"></div>
              <p>Generando QR...</p>
            </div>
          </div>
        </td>
        <td>
          <div style="display: flex; gap: 0.3rem; flex-wrap: wrap;">
            <button type="button" class="btn-action" onclick="descargarQR(<?php echo $idMesa; ?>, '<?php echo htmlspecialchars((string) $mesaNumero); ?>')" title="Descargar QR">
              ⬇️
            </button>
            <button type="button" class="btn-action" onclick="imprimirQR(<?php echo $idMesa; ?>, '<?php echo htmlspecialchars((string) $mesaNumero); ?>')" title="Imprimir QR">
              🖨️
            </button>
            <label style="font-size: 0.8rem; margin: 0; cursor: pointer;">
              <input type="checkbox" class="qr-checkbox" data-mesa-id="<?php echo $idMesa; ?>" data-mesa-numero="<?php echo htmlspecialchars((string) $mesaNumero); ?>" style="margin-right: 0.3rem;">
              Seleccionar
            </label>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Tarjetas móviles para mesas -->
<div class="mobile-cards" id="qr-stay-mobile" style="display: none;">
  <?php foreach ($mesas as $mesa): ?>
    <?php
      $estado = strtolower($mesa['estado'] ?? 'libre');
      $badgeClass = $statusClasses[$estado] ?? 'status-badge status-neutral';
      $estadoLabel = ucfirst($estado);
      $mesaNumero = (int) ($mesa['numero'] ?? 0);
      $idMesa = (int) ($mesa['id_mesa'] ?? 0);
      $ubicacion = $mesa['ubicacion'] ?? '';
      $capacidad = $mesa['capacidad'] ?? null;
      $mozo = $mesa['mozo_nombre_completo'] ?? null;
      $pedidosActivos = (int) ($mesa['pedidos_activos'] ?? 0);
    ?>
    <div class="mobile-card" data-mesa-number="<?php echo htmlspecialchars((string) $mesaNumero); ?>" data-ubicacion="<?php echo htmlspecialchars($ubicacion); ?>" data-mozo="<?php echo htmlspecialchars((string) $mozo); ?>">
      <div class="mobile-card-header">
        <div class="mobile-card-number">
          <strong>Mesa <?php echo htmlspecialchars((string) $mesaNumero); ?></strong>
          <?php if ($capacidad): ?>
            <span class="card-category">Cap: <?php echo htmlspecialchars((string) $capacidad); ?></span>
          <?php endif; ?>
        </div>
        <div class="mobile-card-actions">
          <label style="font-size: 0.7rem; cursor: pointer;">
            <input type="checkbox" class="qr-checkbox" data-mesa-id="<?php echo $idMesa; ?>" data-mesa-numero="<?php echo htmlspecialchars((string) $mesaNumero); ?>" style="margin-right: 0.2rem;">
            ✅
          </label>
        </div>
      </div>

      <div class="mobile-card-body">
        <div class="mobile-card-item">
          <div class="mobile-card-label">📍 Ubicación</div>
          <div class="mobile-card-value"><?php echo htmlspecialchars($ubicacion ?? '—'); ?></div>
        </div>

        <div class="mobile-card-item">
          <div class="mobile-card-label">📊 Estado</div>
          <div class="mobile-card-value">
            <span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($estadoLabel); ?></span>
          </div>
        </div>

        <?php if ($mozo): ?>
        <div class="mobile-card-item">
          <div class="mobile-card-label">👤 Mozo</div>
          <div class="mobile-card-value"><?php echo htmlspecialchars($mozo); ?></div>
        </div>
        <?php endif; ?>

        <?php if ($pedidosActivos > 0): ?>
        <div class="mobile-card-item">
          <div class="mobile-card-value" style="color: #dc3545; font-weight: bold;">
            ⚠️ <?php echo $pedidosActivos; ?> pedidos activos
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="card-image">
        <div class="qr-card-preview" id="qr-mesa-mobile-<?php echo $idMesa; ?>">
          <div class="qr-card-placeholder">
            <div class="qr-spinner" aria-hidden="true"></div>
            <p>Generando QR...</p>
          </div>
        </div>
      </div>

      <div class="card-actions">
        <button type="button" class="btn-modern btn-edit" onclick="descargarQR(<?php echo $idMesa; ?>, '<?php echo htmlspecialchars((string) $mesaNumero); ?>')">
          ⬇️ Descargar
        </button>
        <button type="button" class="btn-modern btn-delete" onclick="imprimirQR(<?php echo $idMesa; ?>, '<?php echo htmlspecialchars((string) $mesaNumero); ?>')">
          🖨️ Imprimir
        </button>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Sección Takeaway -->
<div id="qr-takeaway-section" style="display: none;">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Servicio</th>
          <th>Descripción</th>
          <th>Código QR</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <strong>🥡 Take Away</strong>
          </td>
          <td>
            Pedidos para llevar
          </td>
          <td>
            <div id="qr-takeaway-content">
              <div class="qr-card-placeholder">
                <div class="qr-spinner" aria-hidden="true"></div>
                <p>Generando QR...</p>
              </div>
            </div>
          </td>
          <td>
            <label style="font-size: 0.8rem; margin: 0; cursor: pointer;">
              <input type="checkbox" class="qr-checkbox-takeaway" data-tipo="takeaway" style="margin-right: 0.3rem;">
              Seleccionar
            </label>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal de progreso -->
<div id="qr-progress-modal" class="qr-modal" aria-hidden="true">
  <div class="qr-modal__content" role="alert" aria-live="polite">
    <h3 class="qr-modal__title">Generando códigos...</h3>
    <p class="qr-modal__subtitle">Estamos actualizando los QR según tu configuración.</p>
    <div class="qr-progress">
      <div id="qr-progress-bar" class="qr-progress__fill"></div>
    </div>
    <p id="qr-progress-text" class="qr-progress__text">0 / <?php echo $totalMesas; ?> generados</p>
  </div>
</div>

<!-- Modal de confirmación -->
<div id="modalConfirmacion" class="modal-overlay">
  <div class="modal-container">
    <div class="modal-header">
      <div class="modal-icon">⚠️</div>
      <h3>Confirmar Acción</h3>
    </div>
    <div class="modal-body">
      <p id="modal-message">¿Estás seguro de realizar esta acción?</p>
      <div class="item-preview">
        <span class="item-name" id="modal-item-name"></span>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
      <button type="button" class="btn-delete-confirm" id="modal-confirm">Confirmar</button>
    </div>
  </div>
</div>

<style>
/* Header de gestión */
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
}

.header-btn.secondary {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.header-btn.secondary:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Filtros y configuración */
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
  margin-bottom: 1rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toggle-filters-btn:hover {
  background: linear-gradient(135deg, rgb(190, 141, 56) 0%, rgb(170, 125, 50) 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.filters-content {
  padding: 1rem;
}

.filter-group {
  margin-bottom: 1rem;
}

.filter-group label {
  display: block;
  font-weight: 600;
  color: #333;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.qr-summary {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: space-between;
}

.qr-summary-item {
  flex: 1;
  min-width: 120px;
  text-align: center;
  background: white;
  padding: 0.8rem;
  border-radius: 6px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.qr-summary-item strong {
  display: block;
  font-size: 1.4rem;
  color: #333;
  margin-bottom: 0.2rem;
}

.qr-summary-item span {
  font-size: 0.85rem;
  color: rgba(63, 63, 63, 0.75);
}

.qr-config-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
}

.qr-config-item {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.qr-config-item span {
  font-size: 0.85rem;
  color: #333;
  font-weight: 600;
}

.qr-config-item input {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.95rem;
}

/* Pestañas */
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

/* Estilos para QR card preview */
.qr-card-preview {
  background: #f8f9fa;
  border-radius: 6px;
  min-height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 0.5rem;
  margin: 0.5rem 0;
}

.qr-card-preview img {
  max-width: 100px;
  max-height: 100px;
  border-radius: 4px;
}

/* Contenedor de código QR */
.qr-code-container {
  text-align: center;
  width: 100%;
}

.qr-image {
  max-width: 100px;
  height: auto;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-bottom: 0.5rem;
}

.qr-info {
  margin-top: 0.5rem;
}

.qr-url {
  font-size: 0.7rem;
  color: #666;
  word-break: break-all;
  background: #f5f5f5;
  padding: 4px 6px;
  border-radius: 3px;
  border: 1px solid #e0e0e0;
  margin-top: 0.3rem;
  text-align: center;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* Contenedor de Takeaway */
.takeaway-qr-container {
  text-align: center;
  padding: 1rem;
}

.takeaway-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  margin-top: 1rem;
}

.qr-card-placeholder {
  color: #666666;
  font-size: 0.85rem;
  text-align: center;
}

.qr-spinner {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #333;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  margin: 0 auto 0.5rem;
  animation: qr-spin 1s linear infinite;
}

@keyframes qr-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Estilos para badges de estado */
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.65rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-active {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.status-inactive {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.status-warning {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
}

.status-neutral {
  background: #e2e3e5;
  color: #383d41;
  border: 1px solid #d6d8db;
}

/* Botones modernos */
.btn-modern {
  padding: 6px 12px;
  border: 1px solid;
  border-radius: 4px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
  font-size: 0.8rem;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.btn-edit {
  background: #6c757d;
  color: white;
  border-color: #5a6268;
}

.btn-edit:hover {
  background: #5a6268;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.btn-delete {
  background: #dc3545;
  color: white;
  border-color: #c82333;
}

.btn-delete:hover {
  background: #c82333;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.btn-action {
  padding: 4px 8px;
  background: #28a745;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;
  transition: all 0.2s ease;
}

.btn-action:hover {
  background: #218838;
  transform: translateY(-1px);
}

/* Notificaciones temporales */
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

/* Modal de confirmación */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
  animation: fadeIn 0.3s ease;
}

.modal-overlay.show {
  display: flex;
}

.modal-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  max-width: 400px;
  width: 90%;
  margin: 20px;
  overflow: hidden;
  animation: slideUp 0.3s ease;
  transform: scale(0.9);
  transition: transform 0.3s ease;
}

.modal-overlay.show .modal-container {
  transform: scale(1);
}

.modal-header {
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
  color: white;
  padding: 20px;
  text-align: center;
  position: relative;
}

.modal-icon {
  font-size: 2.5rem;
  margin-bottom: 10px;
  animation: pulse 2s infinite;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
}

.modal-body {
  padding: 25px;
  text-align: center;
}

.modal-body p {
  margin: 0 0 15px 0;
  color: #555;
  font-size: 1rem;
  line-height: 1.5;
}

.item-preview {
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 15px;
  margin: 15px 0;
}

.item-name {
  font-weight: 600;
  color: #333;
  font-size: 1.1rem;
}

.modal-footer {
  padding: 20px 25px;
  background: #f8f9fa;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-cancel {
  background: #6c757d;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn-cancel:hover {
  background: #5a6268;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-delete-confirm {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
  box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

.btn-delete-confirm:hover {
  background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
  transform: translateY(-1px);
  box-shadow: 0 6px 12px rgba(220, 53, 69, 0.4);
}

/* Modal de progreso QR */
.qr-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.qr-modal.is-visible {
  display: flex;
}

.qr-modal__content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  min-width: 300px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.qr-modal__title {
  margin: 0 0 0.5rem 0;
  color: #333;
  font-size: 1.3rem;
}

.qr-modal__subtitle {
  margin: 0 0 1.5rem 0;
  color: #666;
  font-size: 0.95rem;
}

.qr-progress {
  width: 100%;
  height: 8px;
  background: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 1rem;
}

.qr-progress__fill {
  height: 100%;
  background: linear-gradient(90deg, #28a745, #20c997);
  width: 0%;
  transition: width 0.3s ease;
}

.qr-progress__text {
  margin: 0;
  color: #666;
  font-size: 0.9rem;
  font-weight: 500;
}

/* Estilos para tarjetas móviles */
.mobile-cards {
  display: none;
}

.mobile-card {
  background: white;
  border-radius: 8px;
  margin-bottom: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow: hidden;
}

.mobile-card-header {
  background: #f8f9fa;
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.mobile-card-number {
  font-weight: 600;
  color: #333;
}

.card-category {
  background: #e9ecef;
  color: #6c757d;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  margin-left: 0.5rem;
}

.mobile-card-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.mobile-card-body {
  padding: 1rem;
}

.mobile-card-item {
  margin-bottom: 0.8rem;
}

.mobile-card-item:last-child {
  margin-bottom: 0;
}

.mobile-card-label {
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 0.2rem;
  font-weight: 500;
}

.mobile-card-value {
  font-size: 0.9rem;
  color: #333;
  font-weight: 500;
}

.card-image {
  padding: 1rem;
  background: #f8f9fa;
  text-align: center;
  border-top: 1px solid #dee2e6;
}

.card-actions {
  padding: 1rem;
  background: #f8f9fa;
  border-top: 1px solid #dee2e6;
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  flex-wrap: wrap;
}

/* Animaciones */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(0.9);
  }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

/* Responsive mejorado */
@media (max-width: 768px) {
    /* Grid responsive para móvil */
    #qr-grid, #qr-takeaway-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
        padding: 0 10px;
    }

    /* Cards optimizadas para móvil */
    .qr-card {
        padding: 0.75rem !important;
    }

    /* Configuración QR responsive */
    .search-filter > div {
        grid-template-columns: 1fr 1fr !important;
    }

    /* Botones responsive */
    .search-filter button {
        min-height: 44px !important;
        padding: 10px !important;
        font-size: 14px !important;
    }

    /* Estadísticas en columna */
    .filters-container + div {
        grid-template-columns: 1fr !important;
    }

    /* Ajustar contenedor principal */
    main {
        padding: 10px !important;
    }

    /* Selector de tipo QR responsive */
    #btn-stay, #btn-takeaway {
        min-height: 44px !important;
        padding: 10px 15px !important;
    }

    /* Modal responsive */
    #modalProgreso > div {
        margin: 1rem !important;
        padding: 1.5rem !important;
    }

    /* Ocultar texto largo en móvil */
    .hide-mobile {
        display: none !important;
    }

    /* URL más pequeña en móvil */
    .qr-card > div[style*="word-break"] {
        font-size: 0.65rem !important;
    }
}

@media (max-width: 480px) {
    /* Ajustes para móviles pequeños */
    h2 {
        font-size: 1.3rem !important;
    }

    h3 {
        font-size: 1rem !important;
    }

    /* Configuración en columna única */
    .search-filter > div {
        grid-template-columns: 1fr !important;
    }
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

  .filters-content {
    padding: 0.5rem;
  }

  .qr-summary-item {
    flex: 1 1 100px;
    padding: 0.5rem;
  }

  .qr-summary-item strong {
    font-size: 1.2rem;
  }

  .qr-config-grid {
    grid-template-columns: 1fr;
  }

  .tabs {
    flex-direction: column;
  }

  .tab-button {
    text-align: center;
  }

  .table-responsive {
    display: none !important;
  }

  .mobile-cards {
    display: block !important;
  }
}
</style>

<script>
//<![CDATA[
const baseUrl = '<?php echo addslashes($baseUrl); ?>';
const mesas = <?php echo json_encode($mesas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const TAKEAWAY_PRESETS = <?php echo json_encode($takeawayPresets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

const state = {
  generated: 0,
  total: mesas.length,
  config: null,
};

let takeawayInitialized = false;

const progressModal = {
  modal: null,
  bar: null,
  text: null,
  total: 0,

  init() {
    this.modal = document.getElementById('qr-progress-modal');
    this.bar = document.getElementById('qr-progress-bar');
    this.text = document.getElementById('qr-progress-text');
  },

  show(totalCount) {
    if (!this.modal) this.init();
    this.total = totalCount;
    if (!this.total) {
      this.hide();
      return;
    }
    if (this.bar) this.bar.style.width = '0%';
    if (this.text) this.text.textContent = '0 / ' + this.total + ' generados';
    this.modal.classList.add('is-visible');
    this.modal.setAttribute('aria-hidden', 'false');
  },

  update(done, totalCount) {
    if (!this.modal) this.init();
    if (!totalCount || !this.bar || !this.text) return;
    const percent = Math.min(100, Math.round((done / totalCount) * 100));
    this.bar.style.width = percent + '%';
    this.text.textContent = done + ' / ' + totalCount + ' generados';
  },

  hide() {
    if (!this.modal) this.init();
    this.modal.classList.remove('is-visible');
    this.modal.setAttribute('aria-hidden', 'true');
  }
};

document.addEventListener('DOMContentLoaded', function() {
  state.config = readConfig();
  setupEventListeners();
  renderAllMesas({ showProgress: false });
  setTimeout(function() {
    generarQRTakeaway();
    takeawayInitialized = true;
  }, 400);
});

function setupEventListeners() {
  // Configurar event listeners para checkboxes
  document.querySelectorAll('.qr-checkbox').forEach(function(checkbox) {
    checkbox.addEventListener('change', handleMesaSelectionChange);
  });

  // Configurar event listeners para configuración QR
  document.querySelectorAll('.js-qr-config').forEach(function(input) {
    input.addEventListener('change', function() {
      state.config = readConfig();
      renderAllMesas({ showProgress: true });
      if (takeawayInitialized) {
        generarQRTakeaway(true);
      }
    });
  });
}

function readConfig() {
  const size = clamp(parseInt(document.getElementById('qr-size').value || '220', 10), 120, 520);
  const margin = clamp(parseInt(document.getElementById('qr-margin').value || '1', 10), 0, 10);
  const color = document.getElementById('qr-color').value || '#000000';
  const bgcolor = document.getElementById('qr-bgcolor').value || '#FFFFFF';

  return { size: size, margin: margin, color: color, bgcolor: bgcolor };
}

function clamp(value, min, max) {
  if (isNaN(value)) return min;
  return Math.min(Math.max(value, min), max);
}

function renderAllMesas(options) {
  options = options || {};
  state.config = readConfig();
  state.generated = 0;
  updateGeneratedCounter(0);

  const total = mesas.length;
  state.total = total;
  if (!total) {
    progressModal.hide();
    return;
  }

  if (options.showProgress) {
    progressModal.show(total);
  }

  mesas.forEach(function(mesa, index) {
    const container = document.getElementById('qr-mesa-' + mesa.id_mesa);
    const mobileContainer = document.getElementById('qr-mesa-mobile-' + mesa.id_mesa);

    if (!container && !mobileContainer) return;

    if (container) {
      setPlaceholder(container);
    }
    if (mobileContainer) {
      setPlaceholder(mobileContainer);
    }

    setTimeout(function() {
      renderMesaQr(mesa, state.config, function() {
        state.generated += 1;
        updateGeneratedCounter(state.generated);
        if (options.showProgress) {
          progressModal.update(state.generated, total);
          if (state.generated >= total) {
            setTimeout(function() { progressModal.hide(); }, 300);
          }
        }
      });
    }, index * 40);
  });
}

function renderMesaQr(mesa, config, callback) {
  const idMesa = mesa.id_mesa;
  const mesaNumero = mesa.numero;
  const container = document.getElementById('qr-mesa-' + idMesa);
  const mobileContainer = document.getElementById('qr-mesa-mobile-' + idMesa);

  if (!container && !mobileContainer) {
    if (callback) callback();
    return;
  }

  const url = baseUrl + '/index.php?route=cliente&qr=' + encodeURIComponent(mesaNumero);
  const qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' + config.size + 'x' + config.size + '&data=' + encodeURIComponent(url) + '&color=' + config.color.replace('#', '') + '&bgcolor=' + config.bgcolor.replace('#', '') + '&margin=' + config.margin;

  // Create image element to handle loading errors
  const img = new Image();
  img.onload = function() {
    const qrHtml = '<div class="qr-code-container"><img src="' + qrImageUrl + '" alt="QR Mesa ' + mesaNumero + '" class="qr-image" /><div class="qr-info"><div class="qr-url">' + url + '</div></div></div>';

    if (container) {
      container.innerHTML = qrHtml;
    }
    if (mobileContainer) {
      mobileContainer.innerHTML = qrHtml;
    }

    if (callback) callback();
  };

  img.onerror = function() {
    const errorHtml = '<div class="qr-code-container"><div class="qr-error" style="color: #dc3545; padding: 20px; text-align: center;">❌ Error al generar QR<br><small>Verifique su conexión a internet</small></div><div class="qr-info"><div class="qr-url">' + url + '</div></div></div>';

    if (container) {
      container.innerHTML = errorHtml;
    }
    if (mobileContainer) {
      mobileContainer.innerHTML = errorHtml;
    }

    if (callback) callback();
  };

  img.src = qrImageUrl;

  // Set timeout in case image takes too long to load
  setTimeout(function() {
    if (!img.complete) {
      const timeoutHtml = '<div class="qr-code-container"><div class="qr-timeout" style="color: #ffc107; padding: 20px; text-align: center;">⏱️ Tiempo de espera agotado<br><small>Intente recargar la página</small></div><div class="qr-info"><div class="qr-url">' + url + '</div></div></div>';

      if (container && container.innerHTML.includes('Generando QR...')) {
        container.innerHTML = timeoutHtml;
      }
      if (mobileContainer && mobileContainer.innerHTML.includes('Generando QR...')) {
        mobileContainer.innerHTML = timeoutHtml;
      }

      if (callback) callback();
    }
  }, 10000); // 10 second timeout
}

function setPlaceholder(container) {
  container.innerHTML = '<div class="qr-card-placeholder"><div class="qr-spinner" aria-hidden="true"></div><p>Generando QR...</p></div>';
}

function updateGeneratedCounter(count) {
  const counter = document.getElementById('qr-generados');
  if (counter) {
    counter.textContent = count || state.generated;
  }
}

function regenerarQRs() {
  showModal('¿Estás seguro de regenerar todos los códigos QR?', 'Esta acción actualizará todos los QR con la nueva configuración', function() {
    renderAllMesas({ showProgress: true });
    if (takeawayInitialized) {
      generarQRTakeaway(true);
    }
    showNotification('🔄 Regenerando todos los códigos QR...', 'success', 3000);
  });
}

function descargarQR(idMesa, mesaNumero) {
  const container = document.getElementById('qr-mesa-' + idMesa);
  if (!container) return;

  const img = container.querySelector('.qr-image');
  if (!img) {
    showNotification('❌ QR no generado aún', 'error', 3000);
    return;
  }

  const link = document.createElement('a');
  link.download = 'qr-mesa-' + mesaNumero + '.png';
  link.href = img.src;
  link.click();

  showNotification('⬇️ QR de Mesa ' + mesaNumero + ' descargado', 'success', 2000);
}

function imprimirQR(idMesa, mesaNumero) {
  const container = document.getElementById('qr-mesa-' + idMesa);
  if (!container) return;

  const img = container.querySelector('.qr-image');
  if (!img) {
    showNotification('❌ QR no generado aún', 'error', 3000);
    return;
  }

  const url = container.querySelector('.qr-url').textContent;

  const printWindow = window.open('', '_blank');
  const printContent = '<!DOCTYPE html><html><head><title>QR Mesa ' + mesaNumero + '</title><style>body { margin: 0; padding: 20px; text-align: center; font-family: Arial, sans-serif; }.qr-container { display: inline-block; padding: 20px; border: 2px solid #ddd; border-radius: 8px; }.qr-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #333; }.qr-image { max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px; }.qr-url { font-size: 12px; color: #666; margin-top: 10px; word-break: break-all; background: #f5f5f5; padding: 8px; border-radius: 4px; }</style></head><body><div class="qr-container"><div class="qr-title">Mesa ' + mesaNumero + '</div><img src="' + img.src + '" class="qr-image" alt="QR Mesa ' + mesaNumero + '" /><div class="qr-url">' + url + '</div></div><script>window.onload = function() { setTimeout(function() { window.print(); window.close(); }, 500); }<\/script></body></html>';

  printWindow.document.write(printContent);
  printWindow.document.close();

  showNotification('🖨️ Enviando a imprimir QR de Mesa ' + mesaNumero, 'success', 2000);
}

function generarQRTakeaway(force) {
  force = force || false;
  const takeawayContent = document.getElementById('qr-takeaway-content');
  if (!takeawayContent) return;

  if (!force && takeawayContent.querySelector('.qr-image')) {
    return;
  }

  const config = readConfig();
  const takeawayUrl = baseUrl + '/index.php?route=cliente&takeaway=1';
  const qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' + config.size + 'x' + config.size + '&data=' + encodeURIComponent(takeawayUrl) + '&color=' + config.color.replace('#', '') + '&bgcolor=' + config.bgcolor.replace('#', '') + '&margin=' + config.margin;

  // Show loading state
  takeawayContent.innerHTML = '<div class="qr-card-placeholder"><div class="qr-spinner" aria-hidden="true"></div><p>Generando QR...</p></div>';

  // Create image element to handle loading errors
  const img = new Image();
  img.onload = function() {
    const takeawayHtml = '<div class="qr-code-container"><img src="' + qrImageUrl + '" alt="QR Takeaway" class="qr-image" /><div class="qr-info"><div class="qr-url">' + takeawayUrl + '</div></div></div>';

    takeawayContent.innerHTML = takeawayHtml;

    const takeawayCheckbox = takeawayContent.querySelector('.qr-checkbox-takeaway');
    if (takeawayCheckbox) {
      takeawayCheckbox.addEventListener('change', handleTakeawaySelectionChange);
    }
  };

  img.onerror = function() {
    const errorHtml = '<div class="qr-code-container"><div class="qr-error" style="color: #dc3545; padding: 20px; text-align: center;">❌ Error al generar QR<br><small>Verifique su conexión a internet</small></div><div class="qr-info"><div class="qr-url">' + takeawayUrl + '</div></div></div>';

    takeawayContent.innerHTML = errorHtml;
  };

  // Set timeout in case image takes too long to load
  setTimeout(function() {
    if (!img.complete && takeawayContent.innerHTML.includes('Generando QR...')) {
      const timeoutHtml = '<div class="qr-code-container"><div class="qr-timeout" style="color: #ffc107; padding: 20px; text-align: center;">⏱️ Tiempo de espera agotado<br><small>Intente recargar la página</small></div><div class="qr-info"><div class="qr-url">' + takeawayUrl + '</div></div></div>';

      takeawayContent.innerHTML = timeoutHtml;
    }
  }, 10000); // 10 second timeout

  img.src = qrImageUrl;
}

function descargarQRTakeaway() {
  const takeawayContent = document.getElementById('qr-takeaway-content');
  if (!takeawayContent) return;

  const img = takeawayContent.querySelector('.qr-image');
  if (!img) {
    showNotification('❌ QR Takeaway no generado aún', 'error', 3000);
    return;
  }

  const link = document.createElement('a');
  link.download = 'qr-takeaway.png';
  link.href = img.src;
  link.click();

  showNotification('⬇️ QR Takeaway descargado', 'success', 2000);
}

function imprimirQRTakeaway() {
  const takeawayContent = document.getElementById('qr-takeaway-content');
  if (!takeawayContent) return;

  const img = takeawayContent.querySelector('.qr-image');
  if (!img) {
    showNotification('❌ QR Takeaway no generado aún', 'error', 3000);
    return;
  }

  const url = takeawayContent.querySelector('.qr-url').textContent;

  const printWindow = window.open('', '_blank');
  const printContent = '<!DOCTYPE html><html><head><title>QR Take Away</title><style>body { margin: 0; padding: 20px; text-align: center; font-family: Arial, sans-serif; }.qr-container { display: inline-block; padding: 20px; border: 2px solid #ddd; border-radius: 8px; }.qr-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #333; }.qr-image { max-width: 300px; height: auto; border: 1px solid #ddd; border-radius: 4px; }.qr-url { font-size: 12px; color: #666; margin-top: 10px; word-break: break-all; background: #f5f5f5; padding: 8px; border-radius: 4px; }</style></head><body><div class="qr-container"><div class="qr-title">Take Away</div><img src="' + img.src + '" class="qr-image" alt="QR Take Away" /><div class="qr-url">' + url + '</div></div><script>window.onload = function() { setTimeout(function() { window.print(); window.close(); }, 500); }<\/script></body></html>';

  printWindow.document.write(printContent);
  printWindow.document.close();

  showNotification('🖨️ Enviando a imprimir QR Takeaway', 'success', 2000);
}

function toggleFilters() {
  const filtersContent = document.getElementById('filtersContent');
  const toggleBtn = document.getElementById('toggleFilters');

  if (filtersContent.style.display === 'none' || filtersContent.style.display === '') {
    filtersContent.style.display = 'block';
    toggleBtn.innerHTML = '🔍 Ocultar Filtros';
  } else {
    filtersContent.style.display = 'none';
    toggleBtn.innerHTML = '🔍 Filtros y Configuración';
  }
}

function showTab(tabName) {
  const staySection = document.getElementById('qr-stay-section');
  const takeWaySection = document.getElementById('qr-takeaway-section');
  const stayMobile = document.getElementById('qr-stay-mobile');

  if (staySection) staySection.style.display = 'none';
  if (takeWaySection) takeWaySection.style.display = 'none';
  if (stayMobile) stayMobile.style.display = 'none';

  document.querySelectorAll('.tab-button').forEach(function(btn) {
    btn.classList.remove('active');
  });

  if (tabName === 'stay') {
    if (staySection) staySection.style.display = 'block';
    if (stayMobile) stayMobile.style.display = 'block';
    const stayTab = document.querySelector('.tab-button[onclick="showTab(\'stay\')"]');
    if (stayTab) stayTab.classList.add('active');
  } else if (tabName === 'takeaway') {
    if (takeWaySection) {
      takeWaySection.style.display = 'block';
      if (!takeWaySection.querySelector('.qr-image')) {
        generarQRTakeaway();
      }
    }
    const takeawayTab = document.querySelector('.tab-button[onclick="showTab(\'takeaway\')"]');
    if (takeawayTab) takeawayTab.classList.add('active');
  }
}

function handleMesaSelectionChange() {
  updateSelectedCounter();
}

function handleTakeawaySelectionChange() {
  updateSelectedCounter();
}

function updateSelectedCounter() {
  const selectedCheckboxes = document.querySelectorAll('.qr-checkbox:checked');
  const takeawayCheckbox = document.querySelector('.qr-checkbox-takeaway:checked');
  const total = selectedCheckboxes.length + (takeawayCheckbox ? 1 : 0);

  const counter = document.getElementById('qr-seleccionados');
  if (counter) {
    counter.textContent = total;
  }
}

function descargarSeleccionados() {
  const selectedCheckboxes = document.querySelectorAll('.qr-checkbox:checked');
  const takeawayCheckbox = document.querySelector('.qr-checkbox-takeaway:checked');

  if (selectedCheckboxes.length === 0 && !takeawayCheckbox) {
    showNotification('❌ No hay QR seleccionados', 'error', 3000);
    return;
  }

  let downloadCount = 0;

  selectedCheckboxes.forEach(function(checkbox) {
    const idMesa = checkbox.dataset.mesaId;
    const mesaNumero = checkbox.dataset.mesaNumero;
    descargarQR(idMesa, mesaNumero);
    downloadCount++;
  });

  if (takeawayCheckbox) {
    descargarQRTakeaway();
    downloadCount++;
  }

  showNotification('⬇️ Descargando ' + downloadCount + ' QR' + (downloadCount > 1 ? 'es' : ''), 'success', 3000);
}

function imprimirSeleccionados() {
  const selectedCheckboxes = document.querySelectorAll('.qr-checkbox:checked');
  const takeawayCheckbox = document.querySelector('.qr-checkbox-takeaway:checked');

  if (selectedCheckboxes.length === 0 && !takeawayCheckbox) {
    showNotification('❌ No hay QR seleccionados', 'error', 3000);
    return;
  }

  const printWindow = window.open('', '_blank');
  let printContent = '<!DOCTYPE html><html><head><title>Códigos QR Seleccionados</title><style>body { margin: 0; padding: 20px; font-family: Arial, sans-serif; background: #f5f5f5; }.qr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }.qr-container { background: white; padding: 15px; border: 2px solid #ddd; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }.qr-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; color: #333; }.qr-image { max-width: 250px; height: auto; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }.qr-url { font-size: 10px; color: #666; word-break: break-all; line-height: 1.2; background: #f5f5f5; padding: 8px; border-radius: 4px; }.header { text-align: center; margin-bottom: 30px; color: #333; }.print-date { font-size: 12px; color: #666; margin-bottom: 20px; }@media print { body { background: white; }.qr-grid { page-break-inside: avoid; }}</style></head><body><div class="header"><h1>Códigos QR - Sistema de Comandas</h1><div class="print-date">Generado: ' + new Date().toLocaleString() + '</div></div><div class="qr-grid">';

  selectedCheckboxes.forEach(function(checkbox) {
    const idMesa = checkbox.dataset.mesaId;
    const mesaNumero = checkbox.dataset.mesaNumero;
    const container = document.getElementById('qr-mesa-' + idMesa);
    const img = container?.querySelector('.qr-image');
    const url = container?.querySelector('.qr-url')?.textContent;

    if (img && url) {
      printContent += '<div class="qr-container"><div class="qr-title">Mesa ' + mesaNumero + '</div><img src="' + img.src + '" class="qr-image" alt="QR Mesa ' + mesaNumero + '" /><div class="qr-url">' + url + '</div></div>';
    }
  });

  if (takeawayCheckbox) {
    const takeawayContent = document.getElementById('qr-takeaway-content');
    const img = takeawayContent?.querySelector('.qr-image');
    const url = takeawayContent?.querySelector('.qr-url')?.textContent;

    if (img && url) {
      printContent += '<div class="qr-container"><div class="qr-title">Take Away</div><img src="' + img.src + '" class="qr-image" alt="QR Take Away" /><div class="qr-url">' + url + '</div></div>';
    }
  }

  printContent += '</div><script>window.onload = function() { setTimeout(function() { window.print(); }, 1000); }<\/script></body></html>';

  printWindow.document.write(printContent);
  printWindow.document.close();

  const totalItems = selectedCheckboxes.length + (takeawayCheckbox ? 1 : 0);
  showNotification('🖨️ Enviando ' + totalItems + ' QR' + (totalItems > 1 ? 'es' : '') + ' a imprimir', 'success', 3000);
}

function showModal(message, itemName, onConfirm) {
  const modal = document.getElementById('modalConfirmacion');
  const modalMessage = document.getElementById('modal-message');
  const modalItemName = document.getElementById('modal-item-name');
  const confirmBtn = document.getElementById('modal-confirm');

  modalMessage.textContent = message;
  modalItemName.textContent = itemName;

  confirmBtn.onclick = function() {
    onConfirm();
    closeModal();
  };

  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  const modal = document.getElementById('modalConfirmacion');
  modal.classList.remove('show');
  document.body.style.overflow = 'auto';
}

document.addEventListener('click', function(event) {
  const modal = document.getElementById('modalConfirmacion');
  if (event.target === modal) {
    closeModal();
  }
});

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeModal();
  }
});

function showNotification(message, type, duration) {
  type = type || 'success';
  duration = duration || 4000;

  const container = document.getElementById('notification-container');
  const notification = document.createElement('div');
  notification.className = 'notification ' + type;

  let icon = '✅';
  if (type === 'error') icon = '❌';
  else if (type === 'warning') icon = '⚠️';
  else if (type === 'info') icon = 'ℹ️';

  notification.innerHTML = '<span class="notification-icon">' + icon + '</span><span class="notification-content">' + message + '</span><button class="notification-close" onclick="closeNotification(this)">×</button>';

  container.appendChild(notification);

  setTimeout(function() {
    notification.classList.add('show');
  }, 100);

  setTimeout(function() {
    closeNotification(notification.querySelector('.notification-close'));
  }, duration);
}

function closeNotification(closeButton) {
  const notification = closeButton.closest('.notification');
  if (notification) {
    notification.classList.remove('show');
    setTimeout(function() {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 400);
  }
}
//]]>
</script>