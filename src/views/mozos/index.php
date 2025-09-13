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
    <div class="success" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="error" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="action-buttons" style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-bottom: 1rem; align-items: center;">
  <a href="<?= url('mozos/create') ?>" class="button" style="padding: 0.6rem 1rem; font-size: 0.9rem; white-space: nowrap;">
    ➕ Nuevo Mozo
  </a>
</div>

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
    <input type="text" id="searchNombre" placeholder="Buscar por nombre..." 
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
          <a href="<?= url('mozos/edit', ['id' => $m['id_usuario']]) ?>" class="btn-action" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; margin-right: 0.3rem; text-decoration: none; background: #007bff; color: white; border-radius: 4px; transition: all 0.2s ease;">
            ✏️ Editar
          </a>
          <a href="#" class="btn-action delete" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; text-decoration: none; background: #dc3545; color: white; border-radius: 4px; transition: all 0.2s ease;"
             onclick="confirmarBorradoMozo(<?= $m['id_usuario'] ?>, '<?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?>')">
            ❌ Eliminar
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
          <a href="<?= url('mozos/edit', ['id' => $m['id_usuario']]) ?>" class="btn-action">
            ✏️ Editar
          </a>
          <a href="#" class="btn-action delete"
             onclick="confirmarBorradoMozo(<?= $m['id_usuario'] ?>, '<?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?>')">
            ❌ Eliminar
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
        background: rgb(247, 239, 207);
        border: 1px solidrgb(240, 230, 208);
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
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
        text-decoration: none;
        border-radius: 4px;
        border: 1px solid #ddd;
        background:rgb(176, 227, 249);
        color: #333;
        transition: all 0.2s ease;
    }
    
    .card-actions .btn-action:hover {
        background: #e9ecef;
        transform: translateY(-1px);
    }
    
    .card-actions .btn-action.delete {
        background: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }
    
    .card-actions .btn-action.delete:hover {
        background: #f1b0b7;
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
    display: inline-block;
    padding: 0.25rem 0.5rem;
    margin: 0 0.1rem;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
    background: #f8f9fa;
    color: #333;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    background: #e9ecef;
}

.btn-action.delete {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.btn-action.delete:hover {
    background: #f1b0b7;
    color: #721c24;
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
</style>

