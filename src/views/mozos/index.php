<?php
// src/views/mozos/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Controllers\MozoController;
use App\Models\Usuario;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Solo administradores pueden acceder
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . url('login'));
    exit;
}

// 1) Si llega ?delete=ID, borramos y redirigimos
if (isset($_GET['delete'])) {
    $resultado = MozoController::delete();
    
    // Si es una petición AJAX, devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $resultado]);
        exit;
    }
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
      <button class="status-filter-btn active" data-status="all" style="padding: 4px 8px; border: none; background: var(--secondary); color: white; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s ease;">
        Todas
      </button>
      <button class="status-filter-btn" data-status="activo" style="padding: 4px 8px; border: none; background: #d4edda; color: #155724; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s ease;">
        ✅ Activo
      </button>
      <button class="status-filter-btn" data-status="inactivo" style="padding: 4px 8px; border: none; background: #f8d7da; color: #721c24; border-radius: 12px; cursor: pointer; font-size: 0.8em; font-weight: bold; transition: all 0.3s ease;">
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
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($mozos)): ?>
    <tr>
      <td colspan="5">No hay mozos registrados.</td>
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
          <a href="<?= url('mozos/edit', ['id' => $m['id_usuario']]) ?>" class="btn-action edit-btn" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; margin-right: 0.3rem; text-decoration: none; background: #6c757d; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;">
            ✏️
          </a>
          <?php if (!$esAdminActual): ?>
            <a href="<?= url('mozos/delete', ['delete' => $m['id_usuario']]) ?>" class="btn-action delete-btn" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; text-decoration: none; background: #dc3545; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;"
               onclick="return confirm('¿Estás seguro de eliminar a <?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?>?')">
              ❌
            </a>
          <?php else: ?>
            <span class="btn-action disabled" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; background: #6c757d; color: #999; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px; cursor: not-allowed;" title="No puedes eliminarte a ti mismo">
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
          <a href="<?= url('mozos/edit', ['id' => $m['id_usuario']]) ?>" class="btn-action edit-btn" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; text-decoration: none; background: #6c757d; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;">
            ✏️
          </a>
          <?php if (!$esAdminActual): ?>
            <a href="<?= url('mozos/delete', ['delete' => $m['id_usuario']]) ?>" class="btn-action delete-btn" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; text-decoration: none; background: #dc3545; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;"
               onclick="return confirm('¿Estás seguro de eliminar a <?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?>?')">
              ❌
            </a>
          <?php else: ?>
            <span class="btn-action disabled" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; background: #6c757d; color: #999; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px; cursor: not-allowed;" title="No puedes eliminarte a ti mismo">
              🔒
            </span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>


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
        const statusSpan = row.querySelector('td:nth-child(4) span');
        if (statusSpan) {
            return statusSpan.textContent.toLowerCase().trim().replace(/[✅❌]/g, '').trim();
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
            const estadoElement = card.querySelector('.card-status span');
            const estadoText = estadoElement ? estadoElement.textContent.toLowerCase().trim().replace(/[✅❌]/g, '').trim() : '';
            
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
    searchNombre.addEventListener('input', function() {
        currentNombreSearch = this.value.toLowerCase();
        filterMozos();
    });
    
    clearNombreSearch.addEventListener('click', function() {
        searchNombre.value = '';
        currentNombreSearch = '';
        filterMozos();
    });
    
    // Event listeners para filtros de estado
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

<!-- Modal de confirmación ya incluido en header.php -->

<style>
/* Estilos para el administrador actual */
.admin-current {
    border-left: 4px solid #ffd700 !important;
    box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3) !important;
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
    
    .search-filter .status-filter-btn {
        font-size: 0.6rem;
        padding: 2px 4px;
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
        background: rgb(245, 236, 198
        );
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
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .card-actions .edit-btn {
        background: #6c757d;
        color: white;
    }
    
    .card-actions .edit-btn:hover {
        background: #5a6268;
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
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.edit-btn {
    background: #6c757d;
    color: white;
}

.edit-btn:hover {
    background: #5a6268;
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
    flex-wrap: wrap;
  }
  
  .header-btn {
    font-size: 0.7rem;
    padding: 0.3rem 0.6rem;
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
</style>

