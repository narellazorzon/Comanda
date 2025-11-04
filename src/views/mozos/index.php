<?php
// src/views/mozos/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Controllers\MozoController;
use App\Models\Usuario;
use App\Models\Mesa;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Solo administradores pueden acceder
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . url('login'));
    exit;
}


// 2) Cargamos todos los mozos
$mozos = Usuario::allByRole('mozo');

// Si el usuario actual es administrador, agregarlo a la lista para que pueda cambiar su contraseña
if (($_SESSION['user']['rol'] ?? '') === 'administrador') {
    $adminActual = $_SESSION['user'];
    // Verificar si el admin ya está en la lista (por si acaso)
    $adminEnLista = false;
    foreach ($mozos as $mozo) {
        if ($mozo['id_usuario'] == $adminActual['id_usuario']) {
            $adminEnLista = true;
            break;
        }
    }
    
    // Si no está en la lista, agregarlo al principio
    if (!$adminEnLista) {
        array_unshift($mozos, $adminActual);
    }
}

?>

<!-- Header de gestión -->
<div class="management-header">
  <h1>👥 Gestión del Personal</h1>
  <div class="header-actions">
    <a href="<?= url('mozos/create') ?>" class="header-btn">
      ➕ Nuevo Mozo
    </a>
  </div>
</div>

<!-- Sistema de notificaciones temporales -->
<div id="notification-container"></div>


<!-- Filtros de búsqueda y estado -->
<div class="filters-container">
  <!-- Botón para mostrar/ocultar filtros en móvil -->
  <button id="toggleFilters" class="toggle-filters-btn" onclick="toggleFilters()">
    🔍 Filtrar
  </button>
  
  <div id="filtersContent" class="search-filter" style="background: rgb(238, 224, 191); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.75rem; margin-bottom: 1rem; display: none;">
  <!-- Filtro por nombre -->
  <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
    <label style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">👤 Nombre:</label>
    <input type="text" id="searchNombre" placeholder="Buscar por inicial del nombre..." 
           style="flex: 1; min-width: 200px; padding: 0.4rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem;">
    <button id="clearNombreSearch" 
            style="padding: 0.4rem 0.6rem; background: var(--secondary); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
      Limpiar
    </button>
  </div>
  
  <!-- Filtro por estado -->
  <div style="display: flex; align-items: center; gap: 0.3rem; flex-wrap: wrap;">
    <label style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">📊 Estado:</label>
    <div class="status-filters" style="display: flex; gap: 0.3rem; flex-wrap: wrap;">
      <button class="status-filter-btn active" data-status="all" style="padding: 4px 8px; border: none; background: var(--secondary); color: white; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);">
        Todas
      </button>
      <button class="status-filter-btn" data-status="activo" style="padding: 4px 8px; border: none; background: #d4edda; color: #155724; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);">
        ✅ Activo
      </button>
      <button class="status-filter-btn" data-status="inactivo" style="padding: 4px 8px; border: none; background: #f8d7da; color: #721c24; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);">
        ❌ Inactivo
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
      <th>ID</th>
      <th>Nombre</th>
      <th>Email</th>
      <th>Mesas Asignadas</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($mozos)): ?>
    <tr>
      <td colspan="6">No hay mozos registrados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($mozos as $m): ?>
      <?php $esAdminActual = ($_SESSION['user']['id_usuario'] ?? 0) == $m['id_usuario']; ?>
      <tr data-mozo-id="<?= $m['id_usuario'] ?>" style="border-bottom: 1px solid #e0e0e0; <?= $esAdminActual ? 'background-color: #fff8e1;' : '' ?>">
        <td><?= $m['id_usuario'] ?></td>
        <td>
          <?= htmlspecialchars($m['nombre'].' '.$m['apellido']) ?>
          <?php if ($esAdminActual): ?>
            <span class="admin-badge" style="background: linear-gradient(45deg, #ffd700, #ffed4e); color: #8b6914; padding: 2px 6px; border-radius: 10px; font-size: 0.7em; font-weight: bold; margin-left: 8px;">
              👑 Admin
            </span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($m['email']) ?></td>
        <td>
          <?php
          // Obtener mesas asignadas al mozo
          $mesasAsignadas = Mesa::getMesasByMozo($m['id_usuario']);
          if (empty($mesasAsignadas)) {
              echo '<span style="color: #6c757d; font-style: italic;">Sin mesas asignadas</span>';
          } else {
              // Contar mesas por estado
              $mesasLibres = array_filter($mesasAsignadas, fn($m) => $m['estado'] === 'libre');
              $mesasOcupadas = array_filter($mesasAsignadas, fn($m) => $m['estado'] === 'ocupada');
              $totalMesas = count($mesasAsignadas);
              
              echo '<div style="margin-bottom: 4px;">';
              echo '<span style="font-size: 0.8em; color: #666; font-weight: 500;">';
              echo '📊 ' . $totalMesas . ' mesa' . ($totalMesas > 1 ? 's' : '') . ' asignada' . ($totalMesas > 1 ? 's' : '');
              if (count($mesasLibres) > 0) echo ' • ' . count($mesasLibres) . ' libre' . (count($mesasLibres) > 1 ? 's' : '');
              if (count($mesasOcupadas) > 0) echo ' • ' . count($mesasOcupadas) . ' ocupada' . (count($mesasOcupadas) > 1 ? 's' : '');
              echo '</span>';
              echo '</div>';
              
              echo '<div style="display: flex; flex-wrap: wrap; gap: 4px;">';
              foreach ($mesasAsignadas as $mesa) {
                  // Definir color según el estado de la mesa
                  $estadoMesa = $mesa['estado'];
                  if ($estadoMesa === 'libre') {
                      $bgColor = '#e8f5e8';
                      $textColor = '#2e7d32';
                      $icono = '🟢';
                  } elseif ($estadoMesa === 'ocupada') {
                      $bgColor = '#ffebee';
                      $textColor = '#c62828';
                      $icono = '🔴';
                  } else {
                      $bgColor = '#fff3e0';
                      $textColor = '#f57c00';
                      $icono = '🟡';
                  }
                  
                  echo '<span style="background: ' . $bgColor . '; color: ' . $textColor . '; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; font-weight: 500; display: inline-flex; align-items: center; gap: 3px;" title="Mesa ' . $mesa['numero'] . ' - ' . ucfirst($estadoMesa) . '">';
                  echo $icono . ' Mesa ' . $mesa['numero'];
                  echo '</span>';
              }
              echo '</div>';
          }
          ?>
        </td>
        <td>
          <?php
          // Definir colores según el estado
          $estado = $m['estado'];
          if ($estado === 'activo') {
              $bg_color = '#d4edda';
              $text_color = '#155724';
          } else { // inactivo
              $bg_color = '#f8d7da';
              $text_color = '#721c24';
          }
          ?>
          <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.85em; font-weight: bold; 
                       background: <?= $bg_color ?>; 
                       color: <?= $text_color ?>;">
            <?= htmlspecialchars(ucfirst($m['estado'])) ?>
          </span>
        </td>
        <td>
          <a href="<?= url('mesas/cambiar-mozo', ['mozo_id' => $m['id_usuario']]) ?>" class="btn-action mesas-btn" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; margin-right: 0.3rem; text-decoration: none; background: #17a2b8; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;" title="Gestionar mesas asignadas">
            🍽️
          </a>
          <a href="<?= url('mozos/edit', ['id' => $m['id_usuario']]) ?>" class="btn-action edit-btn" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; margin-right: 0.3rem; text-decoration: none; background: rgb(144, 104, 76); color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;" title="Editar mozo">
            ✏️
          </a>
          <?php if (!$esAdminActual): ?>
            <button class="btn-action delete-btn" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; text-decoration: none; background: #dc3545; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px; border: none; cursor: pointer;" title="Eliminar mozo"
               onclick="confirmarEliminacion(<?= $m['id_usuario'] ?>, '<?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?>')">
              ❌
            </button>
          <?php else: ?>
            <span class="btn-action disabled" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; background: rgb(144, 104, 76); color: #999; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px; cursor: not-allowed;" title="No puedes eliminarte a ti mismo">
              🔒
            </span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</div>

<!-- Tarjetas móviles (solo visibles en móvil) -->
<div class="mobile-cards" style="display: none;">
  <?php if (empty($mozos)): ?>
    <div class="mobile-card">
      <div class="card-content">
        <p>No hay mozos registrados.</p>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($mozos as $m): ?>
      <?php $esAdminActual = ($_SESSION['user']['id_usuario'] ?? 0) == $m['id_usuario']; ?>
      <div class="mobile-card <?= $esAdminActual ? 'admin-current' : '' ?>" data-mozo-id="<?= $m['id_usuario'] ?>">
        <div class="card-header">
          <div class="card-title">
            <strong><?= htmlspecialchars($m['nombre'].' '.$m['apellido']) ?></strong>
            <span class="card-id">#<?= $m['id_usuario'] ?></span>
            <?php if ($esAdminActual): ?>
              <span class="admin-badge" style="background: linear-gradient(45deg, #ffd700, #ffed4e); color: #8b6914; padding: 2px 6px; border-radius: 10px; font-size: 0.7em; font-weight: bold; margin-left: 8px;">
                👑 Admin
              </span>
            <?php endif; ?>
          </div>
          <div class="card-status">
            <?php
            $estado = $m['estado'];
            if ($estado === 'activo') {
              $bg_color = '#d4edda';
              $text_color = '#155724';
              $icon = '✅';
            } else {
              $bg_color = '#f8d7da';
              $text_color = '#721c24';
              $icon = '❌';
            }
            ?>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                         background: <?= $bg_color ?>; 
                         color: <?= $text_color ?>;">
              <?= $icon ?> <?= htmlspecialchars(ucfirst($m['estado'])) ?>
            </span>
          </div>
        </div>
        <div class="card-content">
          <div class="card-field">
            <strong>Email:</strong> <?= htmlspecialchars($m['email']) ?>
          </div>
        </div>
        <div class="card-actions">
          <a href="<?= url('mesas/cambiar-mozo', ['mozo_id' => $m['id_usuario']]) ?>" class="btn-action mesas-btn" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; margin-right: 0.3rem; text-decoration: none; background: #17a2b8; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;" title="Gestionar mesas asignadas">
            🍽️
          </a>
          <a href="<?= url('mozos/edit', ['id' => $m['id_usuario']]) ?>" class="btn-action edit-btn" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; text-decoration: none; background: rgb(144, 104, 76); color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;">
            ✏️
          </a>
          <?php if (!$esAdminActual): ?>
            <button class="btn-action delete-btn" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; text-decoration: none; background: #dc3545; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px; border: none; cursor: pointer;"
               onclick="confirmarEliminacion(<?= $m['id_usuario'] ?>, '<?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?>')">
              ❌
            </button>
          <?php else: ?>
            <span class="btn-action disabled" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; background: rgb(144, 104, 76); color: #999; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px; cursor: not-allowed;" title="No puedes eliminarte a ti mismo">
              🔒
            </span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
// Función para aplicar los filtros de mozos
function aplicarFiltrosMozos() {
    const filtroNombre = document.getElementById('filtro-nombre').value.toLowerCase();
    const filtroEstado = document.getElementById('filtro-estado').value.toLowerCase();
    const filtroEmail = document.getElementById('filtro-email').value.toLowerCase();
    const filtroMesas = document.getElementById('filtro-mesas').value;
    
    const filas = document.querySelectorAll('.mozo-row');
    let contadorVisible = 0;
    
    filas.forEach(fila => {
        const nombre = fila.dataset.nombre;
        const email = fila.dataset.email;
        const estado = fila.dataset.estado;
        const mesas = parseInt(fila.dataset.mesas);
        
        let mostrar = true;
        
        // Filtro por nombre
        if (filtroNombre && !nombre.includes(filtroNombre)) {
            mostrar = false;
        }
        
        // Filtro por estado
        if (filtroEstado && estado !== filtroEstado) {
            mostrar = false;
        }
        
        // Filtro por email
        if (filtroEmail && !email.includes(filtroEmail)) {
            mostrar = false;
        }
        
        // Filtro por mesas asignadas
        if (filtroMesas) {
            if (filtroMesas === 'con-mesas' && mesas === 0) {
                mostrar = false;
            } else if (filtroMesas === 'sin-mesas' && mesas > 0) {
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
    document.getElementById('num-mozos').textContent = contadorVisible;
    
    // Mostrar mensaje si no hay resultados
    if (contadorVisible === 0 && filas.length > 0) {
        let filaNoResultados = document.getElementById('fila-no-mozos');
        if (!filaNoResultados) {
            const tbody = document.querySelector('.table tbody');
            const nuevaFila = document.createElement('tr');
            nuevaFila.id = 'fila-no-mozos';
            nuevaFila.innerHTML = `<td colspan="6" style="text-align: center; padding: 2rem; color: #6c757d;">
                <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">👤 No se encontraron mozos con los filtros aplicados</div>
                <div style="font-size: 0.9rem;">Intenta ajustar los criterios de búsqueda</div>
            </td>`;
            tbody.appendChild(nuevaFila);
        }
    } else {
        const filaNoResultados = document.getElementById('fila-no-mozos');
        if (filaNoResultados) {
            filaNoResultados.remove();
        }
    }
}

function limpiarFiltrosMozos() {
    document.getElementById('filtro-nombre').value = '';
    document.getElementById('filtro-estado').value = '';
    document.getElementById('filtro-email').value = '';
    document.getElementById('filtro-mesas').value = '';
    
    const filas = document.querySelectorAll('.mozo-row');
    filas.forEach(fila => {
        fila.style.display = '';
    });
    
    document.getElementById('num-mozos').textContent = filas.length;
    
    const filaNoResultados = document.getElementById('fila-no-mozos');
    if (filaNoResultados) {
        filaNoResultados.remove();
    }
}

// Agregar eventos a los filtros
document.addEventListener('DOMContentLoaded', function() {
    const filtroNombre = document.getElementById('filtro-nombre');
    const filtroEstado = document.getElementById('filtro-estado');
    const filtroEmail = document.getElementById('filtro-email');
    const filtroMesas = document.getElementById('filtro-mesas');
    
    if (filtroNombre) filtroNombre.addEventListener('input', aplicarFiltrosMozos);
    if (filtroEstado) filtroEstado.addEventListener('change', aplicarFiltrosMozos);
    if (filtroEmail) filtroEmail.addEventListener('input', aplicarFiltrosMozos);
    if (filtroMesas) filtroMesas.addEventListener('change', aplicarFiltrosMozos);
});
</script>


<script>
// Filtros de búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const searchNombre = document.getElementById('searchNombre');
    const clearNombreSearch = document.getElementById('clearNombreSearch');
    const tableRows = document.querySelectorAll('.table tbody tr');
    const mobileCards = document.querySelectorAll('.mobile-card');
    const statusButtons = document.querySelectorAll('.status-filter-btn');
    
    let currentNombreSearch = '';
    let currentStatusFilter = 'all';
    
    function getMozoStatus(row) {
        const statusSpan = row.querySelector('td:nth-child(5) span');
        if (statusSpan) {
            // Extraer el texto del estado, remover emojis y espacios, convertir a minúsculas
            let estadoText = statusSpan.textContent.trim();
            estadoText = estadoText.replace(/[✅❌]/g, '').trim();
            estadoText = estadoText.toLowerCase();
            // Si el texto es "activo" o "inactivo", retornarlo directamente
            if (estadoText === 'activo' || estadoText === 'inactivo') {
                return estadoText;
            }
        }
        return '';
    }
    
    function getMozoStatusFromCard(card) {
        const estadoElement = card.querySelector('.card-status span');
        if (estadoElement) {
            // Extraer el texto del estado, remover emojis y espacios, convertir a minúsculas
            let estadoText = estadoElement.textContent.trim();
            estadoText = estadoText.replace(/[✅❌]/g, '').trim();
            estadoText = estadoText.toLowerCase();
            // Si el texto es "activo" o "inactivo", retornarlo directamente
            if (estadoText === 'activo' || estadoText === 'inactivo') {
                return estadoText;
            }
        }
        return '';
    }
    
    function filterMozos() {
        // Filtrar filas de la tabla
        tableRows.forEach(row => {
            const nombreCell = row.querySelector('td:nth-child(2)');
            const nombreText = nombreCell ? nombreCell.textContent.toLowerCase().trim() : '';
            const estadoText = getMozoStatus(row);
            
            // Buscar solo al inicio del nombre
            const matchesNombre = currentNombreSearch === '' || nombreText.startsWith(currentNombreSearch);
            const matchesStatus = currentStatusFilter === 'all' || estadoText === currentStatusFilter;
            
            if (matchesNombre && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Filtrar tarjetas móviles
        mobileCards.forEach(card => {
            const nombreElement = card.querySelector('.card-title strong');
            const nombreText = nombreElement ? nombreElement.textContent.toLowerCase().trim() : '';
            const estadoText = getMozoStatusFromCard(card);
            
            // Buscar solo al inicio del nombre
            const matchesNombre = currentNombreSearch === '' || nombreText.startsWith(currentNombreSearch);
            const matchesStatus = currentStatusFilter === 'all' || estadoText === currentStatusFilter;
            
            if (matchesNombre && matchesStatus) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Event listeners para búsqueda por nombre
    if (searchNombre) {
        searchNombre.addEventListener('input', function() {
            currentNombreSearch = this.value.toLowerCase();
            filterMozos();
        });
    }
    
    if (clearNombreSearch) {
        clearNombreSearch.addEventListener('click', function() {
            if (searchNombre) {
                searchNombre.value = '';
                currentNombreSearch = '';
                filterMozos();
            }
        });
    }
    
    // Event listeners para filtros de estado
    if (statusButtons && statusButtons.length > 0) {
        statusButtons.forEach(button => {
            button.addEventListener('click', function() {
            // Remover clase active de todos los botones
            statusButtons.forEach(btn => {
                btn.classList.remove('active');
                const status = btn.getAttribute('data-status');
                // Restaurar estilos originales
                if (status === 'all') {
                    btn.style.background = 'var(--secondary)';
                    btn.style.color = 'white';
                } else if (status === 'activo') {
                    btn.style.background = '#d4edda';
                    btn.style.color = '#155724';
                } else if (status === 'inactivo') {
                    btn.style.background = '#f8d7da';
                    btn.style.color = '#721c24';
                }
            });
            
            // Agregar clase active al botón clickeado
            this.classList.add('active');
            currentStatusFilter = this.dataset.status;
            filterMozos();
        });
    });
    }
    
    // Aplicar filtros iniciales
    filterMozos();
});

// Función para mostrar/ocultar filtros en móvil
function toggleFilters() {
    const filtersContent = document.getElementById('filtersContent');
    const toggleBtn = document.getElementById('toggleFilters');
    
    if (filtersContent.style.display === 'none' || filtersContent.style.display === '') {
        filtersContent.style.display = 'block';
        toggleBtn.innerHTML = 'Ocultar Filtros';
    } else {
        filtersContent.style.display = 'none';
        toggleBtn.innerHTML = '🔍 Filtrar';
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
    if (message.toLowerCase().includes('eliminado') || message.toLowerCase().includes('inactivado')) {
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
    const message = urlParams.get('success');
    
    // Determinar el tipo de notificación basado en el mensaje
    let notificationType = 'success';
    let duration = 5000;
    
    // Si el mensaje es de eliminación o inactivación, usar estilo de error (rojo)
    if (message.toLowerCase().includes('eliminado') || message.toLowerCase().includes('inactivado')) {
      notificationType = 'error';
      duration = 6000;
    }
    
    showNotification(message, notificationType, duration);
    
    // Limpiar la URL sin recargar la página
    const newUrl = window.location.pathname + window.location.search.replace(/[?&]success=[^&]*/, '').replace(/[?&]error=[^&]*/, '');
    if (newUrl.endsWith('?')) {
      window.history.replaceState({}, '', newUrl.slice(0, -1));
    } else {
      window.history.replaceState({}, '', newUrl);
    }
  }
  
  if (urlParams.has('error')) {
    const message = urlParams.get('error');
    showNotification(message, 'error', 6000);
    
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

<!-- Modal de confirmación personalizado -->
<div id="modalEliminacion" class="modal-eliminacion" style="display: none;">
  <div class="modal-overlay" onclick="cerrarModalEliminacion()"></div>
  <div class="modal-content">
    <div class="modal-header">
      <h3>❌ Confirmar Inactivación</h3>
      <button class="modal-close" onclick="cerrarModalEliminacion()">×</button>
    </div>
    <div class="modal-body">
      <div class="warning-icon">⚠️</div>
      <p class="modal-message">¿Estás seguro de inactivar a <strong id="nombreMozo"></strong>?</p>
      <div class="modal-info">
        <p>Esta acción marcará al mozo como inactivo (borrado lógico). El mozo no podrá acceder al sistema y, si tiene mesas asignadas, deberás reasignarlas o liberarlas.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancelar" onclick="cerrarModalEliminacion()">Cancelar</button>
      <button class="btn-confirmar" onclick="eliminarMozo()">Inactivar</button>
    </div>
  </div>
</div>

<!-- Modal de confirmación ya incluido en header.php -->

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

.table-responsive,
.mobile-cards {
  animation: fadeInScale 0.8s ease-out;
}

.table tbody tr {
  animation: slideInUp 0.5s ease-out;
  animation-fill-mode: both;
}

.table tbody tr:nth-child(1) { animation-delay: 0.1s; }
.table tbody tr:nth-child(2) { animation-delay: 0.2s; }
.table tbody tr:nth-child(3) { animation-delay: 0.3s; }
.table tbody tr:nth-child(4) { animation-delay: 0.4s; }
.table tbody tr:nth-child(5) { animation-delay: 0.5s; }
.table tbody tr:nth-child(6) { animation-delay: 0.6s; }
.table tbody tr:nth-child(7) { animation-delay: 0.7s; }
.table tbody tr:nth-child(8) { animation-delay: 0.8s; }
.table tbody tr:nth-child(9) { animation-delay: 0.9s; }
.table tbody tr:nth-child(10) { animation-delay: 1.0s; }

.mobile-card {
  animation: slideInUp 0.5s ease-out;
  animation-fill-mode: both;
}

.mobile-card:nth-child(1) { animation-delay: 0.1s; }
.mobile-card:nth-child(2) { animation-delay: 0.2s; }
.mobile-card:nth-child(3) { animation-delay: 0.3s; }
.mobile-card:nth-child(4) { animation-delay: 0.4s; }
.mobile-card:nth-child(5) { animation-delay: 0.5s; }
.mobile-card:nth-child(6) { animation-delay: 0.6s; }
.mobile-card:nth-child(7) { animation-delay: 0.7s; }
.mobile-card:nth-child(8) { animation-delay: 0.8s; }
.mobile-card:nth-child(9) { animation-delay: 0.9s; }
.mobile-card:nth-child(10) { animation-delay: 1.0s; }

/* Efectos de hover mejorados */
.table tbody tr:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.mobile-card:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 6px 20px rgba(0,0,0,0.15);
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.filters-container {
  animation: slideInUp 0.7s ease-out;
}

/* Estilos para el administrador actual */
.admin-current {
    /* Efectos especiales eliminados para mantener diseño uniforme */
}

.admin-badge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

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
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 6px 12px rgba(0,0,0,0.2);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
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
        padding: 0.3rem;
        font-size: 0.8rem;
    }
    
    #filtersContent {
        display: none;
    }
    
    /* Cuando se muestran los filtros en móvil, hacer que ocupen menos espacio */
    .search-filter {
        padding: 0.3rem;
    }
    
    .search-filter .filter-group {
        margin-bottom: 0.3rem;
    }
    
    .search-filter input,
    .search-filter select {
        font-size: 0.7rem;
        padding: 0.2rem;
        height: 24px;
    }
    
    .search-filter .status-filters {
        gap: 0.15rem;
    }
    
    .search-filter     .status-filter-btn {
        font-size: 0.6rem;
        padding: 2px 4px;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    
    .status-filter-btn:hover {
        transform: translateY(-1px) scale(1.05);
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }
    
    .status-filter-btn.active {
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    
    /* Reducir tamaño general de elementos en móvil */
    .table {
        font-size: 0.7rem;
    }
    
    .table th,
    .table td {
        padding: 0.3rem;
        font-size: 0.7rem;
    }
    
    .btn-action {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
    
    .action-buttons {
        margin-bottom: 0.5rem;
    }
    
    .action-buttons .button {
        padding: 0.3rem 0.6rem;
        font-size: 0.7rem;
    }
    
    h1 {
        font-size: 1.2rem;
    }
    
    h2 {
        font-size: 1rem;
    }
    
    h3 {
        font-size: 0.9rem;
    }
    
    /* Ocultar tabla en móvil y mostrar tarjetas */
    .table-responsive {
        display: none;
    }
    
    .mobile-cards {
        display: block;
        max-height: 60vh;
        overflow-y: auto;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 0.5rem;
    }
    
    .mobile-card {
        background: rgb(255, 248, 225);
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transform: scale(0.9);
        transform-origin: top center;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }
    
    .card-title {
        flex: 1;
    }
    
    .card-title strong {
        display: block;
        font-size: 1rem;
        color: #333;
    }
    
    .card-id {
        font-size: 0.8rem;
        color: #666;
        font-weight: normal;
    }
    
    .card-status {
        margin-left: 0.5rem;
    }
    
    .card-content {
        margin-bottom: 0.5rem;
    }
    
    .card-field {
        font-size: 0.9rem;
        color: #555;
        margin-bottom: 0.25rem;
    }
    
    .card-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .card-actions .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 32px;
        font-size: 0.8rem;
        text-decoration: none;
        border-radius: 6px;
        border: 1px solid transparent;
        transition: all 0.2s ease;
    }
    
    .card-actions .btn-action:hover {
        transform: translateY(-2px) scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.25);
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    
    .card-actions .edit-btn {
        background: rgb(144, 104, 76);
        color: white;
    }
    
    .card-actions .edit-btn:hover {
        background: rgb(92, 64, 51);
    }
    
    .card-actions .delete-btn {
        background: #dc3545;
        color: white;
    }
    
    .card-actions .delete-btn:hover {
        background: #c82333;
    }
}

/* Reglas generales para tabla */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.table th,
.table td {
    padding: 0.5rem;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.table th {
    background-color: rgb(125, 93, 69);
    font-weight: 600;
}

/* Estilos para botones de acción */
.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 0.1rem;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
    width: 40px;
    height: 32px;
}

.btn-action:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.25);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.edit-btn {
    background: rgb(144, 104, 76);
    color: white;
}

.edit-btn:hover {
    background: rgb(92, 64, 51);
}

.delete-btn {
    background: #dc3545;
    color: white;
}

.delete-btn:hover {
    background: #c82333;
}

.btn-action.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* Efectos de hover para botones de filtro en desktop */
.status-filter-btn:hover {
    transform: translateY(-1px) scale(1.05);
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.status-filter-btn.active {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

/* En desktop, mantener botón visible */
@media (min-width: 769px) {
    .toggle-filters-btn {
        display: block;
    }
    
    #filtersContent {
        display: none;
    }
    
    /* Ocultar tarjetas móviles en desktop */
    .mobile-cards {
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

/* Responsive para el header */
@media (max-width: 768px) {
  .management-header {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }
  
  .management-header h1 {
    font-size: 0.9rem !important;
    text-align: center;
    margin-bottom: 0.2rem;
    min-width: auto;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .header-btn {
    flex: 1;
    text-align: center;
    min-width: 120px;
  }
}

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

/* Estilos para el modal de eliminación personalizado */
.modal-eliminacion {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: fadeIn 0.3s ease-out;
}

.modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
}

.modal-content {
  position: relative;
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 450px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  animation: slideInScale 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.modal-header {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.modal-close {
  background: none;
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

.modal-body {
  padding: 24px;
  text-align: center;
}

.warning-icon {
  font-size: 3rem;
  margin-bottom: 16px;
  animation: pulse 2s infinite;
}

.modal-message {
  font-size: 1.1rem;
  color: #333;
  margin-bottom: 16px;
  line-height: 1.5;
}

.modal-info {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 12px;
  margin-top: 16px;
}

.modal-info p {
  margin: 0;
  font-size: 0.9rem;
  color: #6c757d;
  line-height: 1.4;
}

.modal-footer {
  padding: 20px 24px;
  background: #f8f9fa;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-cancelar,
.btn-confirmar {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  min-width: 100px;
}

.btn-cancelar {
  background: rgb(144, 104, 76);
  color: white;
}

.btn-cancelar:hover {
  background: rgb(92, 64, 51);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 12px rgba(144, 104, 76, 0.3);
}

.btn-confirmar {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
  box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.btn-confirmar:hover {
  background: linear-gradient(135deg, #c82333, #bd2130);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideInScale {
  from {
    opacity: 0;
    transform: scale(0.7) translateY(-50px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

/* Responsive para móvil */
@media (max-width: 480px) {
  .modal-content {
    width: 95%;
    margin: 20px;
  }
  
  .modal-header {
    padding: 16px 20px;
  }
  
  .modal-header h3 {
    font-size: 1.1rem;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .warning-icon {
    font-size: 2.5rem;
  }
  
  .modal-message {
    font-size: 1rem;
  }
  
  .modal-footer {
    padding: 16px 20px;
    flex-direction: column;
  }
  
  .btn-cancelar,
  .btn-confirmar {
    width: 100%;
    margin: 4px 0;
  }
}
</style>

<script>
// Variables globales para el modal
let mozoIdAEliminar = null;

// Función para mostrar el modal de confirmación
function confirmarEliminacion(id, nombre) {
  mozoIdAEliminar = id;
  document.getElementById('nombreMozo').textContent = nombre;
  document.getElementById('modalEliminacion').style.display = 'flex';
  document.body.style.overflow = 'hidden'; // Prevenir scroll del body
}

// Función para cerrar el modal
function cerrarModalEliminacion() {
  document.getElementById('modalEliminacion').style.display = 'none';
  document.body.style.overflow = 'auto'; // Restaurar scroll del body
  mozoIdAEliminar = null;
}

// Función para confirmar la inactivación (borrado lógico)
function eliminarMozo() {
  if (mozoIdAEliminar) {
    // Redirigir a la URL de inactivación
    const inactivarUrl = '<?= url("mozos/inactivar") ?>';
    window.location.href = inactivarUrl + (inactivarUrl.includes('?') ? '&' : '?') + 'id=' + mozoIdAEliminar;
  }
}

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    cerrarModalEliminacion();
  }
});

// Prevenir cierre del modal al hacer clic en el contenido
const modalContent = document.querySelector('.modal-content');
if (modalContent) {
  modalContent.addEventListener('click', function(e) {
    e.stopPropagation();
  });
}
</script>

