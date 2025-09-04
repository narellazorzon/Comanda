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

<h2><?= $rol === 'administrador' ? 'Gestión de Mesas' : 'Consulta de Mesas' ?></h2>

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

<?php if ($rol === 'administrador'): ?>
  <div class="action-buttons" style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-bottom: 1rem; align-items: center;">
    <a href="<?= url('mesas/create') ?>" class="button" style="padding: 0.6rem 1rem; font-size: 0.9rem; white-space: nowrap;">
      ➕ Nueva Mesa
    </a>
    <a href="<?= url('mesas/cambiar-mozo') ?>" class="button" style="background:rgb(237, 221, 172); color: #212529; padding: 0.6rem 1rem; font-size: 0.9rem; white-space: nowrap;">
      🔄 Gestionar Mozos
    </a>
  </div>
<?php else: ?>
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
  
  <div id="filtersContent" class="search-filter" style="background: rgb(250, 238, 193); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.6rem; margin-bottom: 1rem;">
  <!-- Filtro por número -->
  <div style="display: flex; align-items: center; gap: 0.3rem; flex-wrap: nowrap; margin-bottom: 0.5rem;">
    <label for="mesaSearch" style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">🔍 Buscar:</label>
    <input type="text" 
           id="mesaSearch" 
           placeholder="Número..." 
           style="padding: 0.3rem 0.5rem; border: 1px solid var(--accent); border-radius: 3px; font-size: 0.8rem; min-width: 120px; height: 28px;">
    <button id="clearSearch" 
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
      <?php if ($rol === 'administrador'): ?>
          <th>Cambiar Estado</th>
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
        <?php if ($rol === 'administrador'): ?>
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
          <td>
            <a href="<?= url('mesas/edit', ['id' => $m['id_mesa']]) ?>" class="btn-action" title="Editar mesa">
              ✏️
            </a>
            <?php if ($m['estado'] === 'libre'): ?>
              <a href="#" class="btn-action delete"
                 onclick="confirmarBorrado(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>)"
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
                 onclick="confirmarBorrado(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>)"
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
      
      <?php if ($rol === 'administrador'): ?>
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
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<!-- Modal de confirmación -->
<div id="modalConfirmacion" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; padding: 1rem;">
  <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 400px; width: 100%; text-align: center;">
    <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
    <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.5rem;">Confirmar Eliminación</h3>
    <p style="margin: 0 0 2rem 0; color: #666; line-height: 1.5;">
      ¿Estás seguro de que quieres eliminar la mesa <strong id="numeroMesa"></strong>?<br>
      <small style="color: #999;">Esta acción no se puede deshacer.</small>
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <button id="btnCancelar" style="padding: 0.75rem 1.5rem; border: 2px solid #6c757d; background: white; color: #6c757d; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s; min-width: 120px; box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);">
        Cancelar
      </button>
      <button id="btnConfirmar" style="padding: 0.75rem 1.5rem; border: 2px solid #dc3545; background: #dc3545; color: white; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s; min-width: 120px; box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);">
        Sí, Eliminar
      </button>
    </div>
  </div>
</div>

<!-- Modal de confirmación para cambio de estado -->
<div id="modalCambioEstado" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; padding: 1rem;">
  <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 400px; width: 100%; text-align: center;">
    <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.2rem;">🔄 Cambiar Estado de Mesa</h3>
    <p id="mensajeCambioEstado" style="margin: 0 0 2rem 0; color: #666; line-height: 1.5;">
      ¿Estás seguro de que quieres cambiar el estado de la mesa?
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <button onclick="cancelarCambioEstado()" style="padding: 0.75rem 1.5rem; border: 2px solid #6c757d; background: white; color: #6c757d; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s; min-width: 120px; box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);">
        Cancelar
      </button>
      <button onclick="confirmarCambioEstado()" style="padding: 0.75rem 1.5rem; border: 2px solid #28a745; background: #28a745; color: white; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s; min-width: 120px; box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);">
        Sí, Cambiar
      </button>
    </div>
  </div>
</div>

<script>
let mesaIdAEliminar = null;

function confirmarBorrado(id, numero) {
    mesaIdAEliminar = id;
    document.getElementById('numeroMesa').textContent = numero;
    document.getElementById('modalConfirmacion').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalConfirmacion').style.display = 'none';
    mesaIdAEliminar = null;
}

function eliminarMesa() {
    if (mesaIdAEliminar) {
        window.location.href = '<?= url('mesas', ['delete' => '']) ?>' + mesaIdAEliminar;
    }
}

// Event listeners
document.getElementById('btnCancelar').addEventListener('click', cerrarModal);
document.getElementById('btnConfirmar').addEventListener('click', eliminarMesa);

// Cerrar modal al hacer clic fuera
document.getElementById('modalConfirmacion').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});

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
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('mesaSearch');
    const clearButton = document.getElementById('clearSearch');
    const searchResults = document.getElementById('searchResults');
    const tableRows = document.querySelectorAll('.table tbody tr');
    const mobileCards = document.querySelectorAll('.mobile-card');
    const statusButtons = document.querySelectorAll('.status-filter-btn');
    
    let currentSearchTerm = '';
    let currentStatusFilter = 'all';
    
    function getMesaStatus(row) {
        const statusSpan = row.querySelector('td:nth-child(3) span');
        if (statusSpan) {
            return statusSpan.textContent.toLowerCase().trim();
        }
        return '';
    }
    
    function getMesaStatusFromCard(card) {
        const statusSpan = card.querySelector('.mobile-card-value span');
        if (statusSpan) {
            return statusSpan.textContent.toLowerCase().trim();
        }
        return '';
    }
    
    function filterMesas() {
        const searchTerm = currentSearchTerm.toLowerCase().trim();
        const statusFilter = currentStatusFilter;
        let visibleCount = 0;
        
        // Filtrar filas de la tabla
        tableRows.forEach(row => {
            const mesaNumber = row.querySelector('td:first-child').textContent.toLowerCase();
            const mesaStatus = getMesaStatus(row);
            
            const matchesSearch = mesaNumber.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || mesaStatus === statusFilter;
            
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Filtrar tarjetas móviles
        mobileCards.forEach(card => {
            const mesaNumber = card.querySelector('.mobile-card-number').textContent.toLowerCase();
            const mesaStatus = getMesaStatusFromCard(card);
            
            const matchesSearch = mesaNumber.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || mesaStatus === statusFilter;
            
            if (matchesSearch && matchesStatus) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Mostrar resultados de filtrado
        let resultText = '';
        if (searchTerm !== '' && statusFilter !== 'all') {
            resultText = `Mostrando ${visibleCount} mesa(s) que coinciden con "${searchTerm}" y estado "${statusFilter}"`;
        } else if (searchTerm !== '') {
            resultText = `Mostrando ${visibleCount} mesa(s) que coinciden con "${searchTerm}"`;
        } else if (statusFilter !== 'all') {
            resultText = `Mostrando ${visibleCount} mesa(s) con estado "${statusFilter}"`;
        }
        
        searchResults.textContent = resultText;
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
        filterMesas();
        searchInput.focus();
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
            // Mantener el estilo original para el botón activo
            
            // Actualizar filtro de estado
            currentStatusFilter = this.dataset.status;
            filterMesas();
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
});

// Variables globales para el modal de cambio de estado
let mesaIdParaCambiar = null;
let nuevoEstadoParaCambiar = null;

// Función para cambiar el estado de una mesa
function cambiarEstado(idMesa, nuevoEstado) {
    mesaIdParaCambiar = idMesa;
    nuevoEstadoParaCambiar = nuevoEstado;
    
    // Mostrar el modal de confirmación personalizado
    document.getElementById('modalCambioEstado').style.display = 'flex';
    document.getElementById('mensajeCambioEstado').textContent = `¿Estás seguro de que quieres cambiar el estado de la mesa a "${nuevoEstado}"?`;
}

// Función para confirmar el cambio de estado
function confirmarCambioEstado() {
    if (mesaIdParaCambiar && nuevoEstadoParaCambiar) {
        // Cerrar el modal
        document.getElementById('modalCambioEstado').style.display = 'none';
        
        // Enviar petición AJAX
        const formData = new FormData();
        formData.append('cambiar_estado', '1');
        formData.append('id_mesa', mesaIdParaCambiar);
        formData.append('nuevo_estado', nuevoEstadoParaCambiar);
        
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

// Función para cancelar el cambio de estado
function cancelarCambioEstado() {
    document.getElementById('modalCambioEstado').style.display = 'none';
    mesaIdParaCambiar = null;
    nuevoEstadoParaCambiar = null;
}



// Efectos hover para los botones del modal de cambio de estado
document.addEventListener('DOMContentLoaded', function() {
    // Botón cancelar del modal de cambio de estado
    const btnCancelarCambio = document.querySelector('#modalCambioEstado button[onclick="cancelarCambioEstado()"]');
    if (btnCancelarCambio) {
        btnCancelarCambio.addEventListener('mouseenter', function() {
            this.style.background = '#6c757d';
            this.style.color = 'white';
            this.style.borderColor = '#5a6268';
            this.style.boxShadow = '0 4px 8px rgba(108, 117, 125, 0.3)';
            this.style.opacity = '0.95';
        });
        
        btnCancelarCambio.addEventListener('mouseleave', function() {
            this.style.background = 'white';
            this.style.color = '#6c757d';
            this.style.borderColor = '#6c757d';
            this.style.boxShadow = '0 2px 4px rgba(108, 117, 125, 0.2)';
            this.style.opacity = '1';
        });
    }
    
    // Botón confirmar del modal de cambio de estado
    const btnConfirmarCambio = document.querySelector('#modalCambioEstado button[onclick="confirmarCambioEstado()"]');
    if (btnConfirmarCambio) {
        btnConfirmarCambio.addEventListener('mouseenter', function() {
            this.style.background = '#1e7e34';
            this.style.borderColor = '#1c7430';
            this.style.boxShadow = '0 4px 8px rgba(40, 167, 69, 0.4)';
            this.style.opacity = '0.95';
        });
        
        btnConfirmarCambio.addEventListener('mouseleave', function() {
            this.style.background = '#28a745';
            this.style.borderColor = '#28a745';
            this.style.boxShadow = '0 2px 4px rgba(40, 167, 69, 0.3)';
            this.style.opacity = '1';
        });
    }
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
</style>
