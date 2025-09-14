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

?>

<!-- Header de gestión -->
<div class="management-header">
  <h1>👥 Gestión de Mozos</h1>
  <div class="header-actions">
    <a href="<?= url('mozos/create') ?>" class="header-btn">
      ➕ Nuevo Mozo
    </a>
  </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="success" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="error" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>


<!-- Filtros de búsqueda y estado -->
<div class="filters-container">
  <!-- Botón para mostrar/ocultar filtros en móvil -->
  <button id="toggleFilters" class="toggle-filters-btn" onclick="toggleFilters()">
    🔍 Filtros
  </button>
  
  <div id="filtersContent" class="search-filter" style="background:rgb(245, 236, 198); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.75rem; margin-bottom: 1rem;">
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
      <tr data-mozo-id="<?= $m['id_usuario'] ?>" style="border-bottom: 1px solid #e0e0e0;">
        <td><?= $m['id_usuario'] ?></td>
        <td><?= htmlspecialchars($m['nombre'].' '.$m['apellido']) ?></td>
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
          <a href="#" class="btn-action delete-btn" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; text-decoration: none; background: #dc3545; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;"
             onclick="confirmarBorradoMozo(<?= $m['id_usuario'] ?>, '<?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?>')">
            ❌
          </a>
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
      <div class="mobile-card" data-mozo-id="<?= $m['id_usuario'] ?>">
        <div class="card-header">
          <div class="card-title">
            <strong><?= htmlspecialchars($m['nombre'].' '.$m['apellido']) ?></strong>
            <span class="card-id">#<?= $m['id_usuario'] ?></span>
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
          <a href="#" class="btn-action delete-btn" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; text-decoration: none; background: #dc3545; color: white; border-radius: 6px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 32px;"
             onclick="confirmarBorradoMozo(<?= $m['id_usuario'] ?>, '<?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?>')">
            ❌
          </a>
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
        console.log('Filtrando con:', currentNombreSearch);
        
        // Filtrar filas de la tabla
        tableRows.forEach(row => {
            const nombreCell = row.querySelector('td:nth-child(2)');
            const nombreText = nombreCell ? nombreCell.textContent.toLowerCase().trim() : '';
            const estadoText = getMozoStatus(row);
            
            console.log('Nombre en tabla:', nombreText, 'Búsqueda:', currentNombreSearch);
            
            // Buscar solo al inicio del nombre
            const matchesNombre = currentNombreSearch === '' || nombreText.startsWith(currentNombreSearch);
            const matchesStatus = currentStatusFilter === 'all' || estadoText === currentStatusFilter;
            
            if (matchesNombre && matchesStatus) {
                row.style.display = '';
                console.log('Mostrando:', nombreText);
            } else {
                row.style.display = 'none';
                console.log('Ocultando:', nombreText);
            }
        });
        
        // Filtrar tarjetas móviles
        mobileCards.forEach(card => {
            const nombreElement = card.querySelector('.card-title strong');
            const nombreText = nombreElement ? nombreElement.textContent.toLowerCase().trim() : '';
            const estadoElement = card.querySelector('.card-status span');
            const estadoText = estadoElement ? estadoElement.textContent.toLowerCase().trim().replace(/[✅❌]/g, '').trim() : '';
            
            console.log('Nombre en tarjeta móvil:', nombreText, 'Búsqueda:', currentNombreSearch);
            
            // Buscar solo al inicio del nombre
            const matchesNombre = currentNombreSearch === '' || nombreText.startsWith(currentNombreSearch);
            const matchesStatus = currentStatusFilter === 'all' || estadoText === currentStatusFilter;
            
            if (matchesNombre && matchesStatus) {
                card.style.display = '';
                console.log('Mostrando tarjeta:', nombreText);
            } else {
                card.style.display = 'none';
                console.log('Ocultando tarjeta:', nombreText);
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
    background-color:rgb(150, 129, 98);
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

/* En desktop, mostrar filtros normalmente */
@media (min-width: 769px) {
    .toggle-filters-btn {
        display: none;
    }
    
    #filtersContent {
        display: block;
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

