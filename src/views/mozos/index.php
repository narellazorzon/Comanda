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

<h2>Gestión de Mozos</h2>

<?php if (isset($_GET['success'])): ?>
  <div style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
    <?= htmlspecialchars($_GET['success']) ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
    <?= htmlspecialchars($_GET['error']) ?>
  </div>
<?php endif; ?>

<div class="action-buttons" style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 0.6rem; align-items: center;">
  <a href="<?= url('mozos/create') ?>" class="button" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; white-space: nowrap;">
    ➕ Nuevo Mozo
  </a>
</div>

<!-- Filtros de búsqueda -->
<div class="filters-container">
  <!-- Botón para mostrar/ocultar filtros en móvil -->
  <button id="toggleFilters" class="toggle-filters-btn" onclick="toggleFilters()">
    🔍 Filtros
  </button>
  
  <div id="filtersContent" class="search-filter" style="background:rgb(250, 238, 193); border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.6rem; margin-bottom: 0.8rem;">
  <!-- Filtros en la misma fila -->
  <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
    <!-- Filtro por Nombre -->
    <div class="filter-group" style="min-width: 200px;">
      <label style="display: block; font-weight: 600; color: var(--secondary); font-size: 0.7rem; margin-bottom: 0.15rem;">👤 Nombre</label>
      <div style="display: flex; gap: 0.2rem;">
        <input type="text" id="searchNombre" placeholder="Buscar por nombre..." 
               style="flex: 1; padding: 0.3rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.7rem; height: 26px; min-width: 120px;">
        <button id="clearNombreSearch" 
                style="padding: 0.3rem 0.5rem; background: var(--secondary); color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.7rem; height: 26px; white-space: nowrap;">
          ✕
        </button>
      </div>
    </div>

    <!-- Filtro por Estado -->
    <div class="filter-group">
      <label style="display: block; font-weight: 600; color: var(--secondary); font-size: 0.7rem; margin-bottom: 0.15rem;">📊 Estado</label>
      <div class="status-filters" style="display: flex; gap: 0.3rem; flex-wrap: wrap;">
        <button class="status-filter-btn active" data-status="all" style="padding: 4px 8px; border: none; background: var(--secondary); color: white; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
          Todas
        </button>
        <button class="status-filter-btn" data-status="activo" style="padding: 4px 8px; border: none; background: #d4edda; color: #155724; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
          ✅ Activo
        </button>
        <button class="status-filter-btn" data-status="inactivo" style="padding: 4px 8px; border: none; background: #f8d7da; color: #721c24; border-radius: 12px; cursor: pointer; font-size: 0.7rem; font-weight: bold; transition: all 0.3s ease;">
          ❌ Inactivo
        </button>
      </div>
    </div>
  </div>
  </div>
</div>

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
      <tr data-mozo-id="<?= $m['id_usuario'] ?>">
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
          <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                       background: <?= $bg_color ?>; 
                       color: <?= $text_color ?>;">
            <?= htmlspecialchars(ucfirst($m['estado'])) ?>
          </span>
        </td>
        <td>
          <a href="<?= url('mozos/edit', ['id' => $m['id_usuario']]) ?>" class="btn-action" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; margin-right: 0.3rem;">
            ✏️
          </a>
          <a href="#" class="btn-action delete" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;"
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


<script>
// Filtros de búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const searchNombre = document.getElementById('searchNombre');
    const clearNombreSearch = document.getElementById('clearNombreSearch');
    const tableRows = document.querySelectorAll('.table tbody tr');
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
        tableRows.forEach(row => {
            const nombreCell = row.querySelector('td:nth-child(2)');
            const nombreText = nombreCell ? nombreCell.textContent.toLowerCase() : '';
            const estadoText = getMozoStatus(row);
            
            const matchesNombre = nombreText.includes(currentNombreSearch);
            const matchesStatus = currentStatusFilter === 'all' || estadoText === currentStatusFilter;
            
            if (matchesNombre && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
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

