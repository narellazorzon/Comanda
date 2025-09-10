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

// 1) Si llega ?delete=ID, borramos y redirigimos
if (isset($_GET['delete'])) {
    MozoController::delete();
}

// 2) Cargamos todos los mozos
$mozos = Usuario::allByRole('mozo');

// 3) Agregar información de mesas asignadas a cada mozo
foreach ($mozos as &$mozo) {
    $mozo['mesas_asignadas'] = Mesa::countMesasByMozo($mozo['id_usuario']);
}

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

<a class="button" href="<?= url('mozos/create') ?>">Nuevo Mozo</a>

<!-- Panel de Filtros -->
<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
        <h4 style="margin: 0; color: #6c757d; font-size: 1rem;">
            <span style="margin-right: 0.5rem;">🔍</span>Filtros de búsqueda
        </h4>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <!-- Búsqueda por nombre -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Buscar por nombre:
            </label>
            <input type="text" id="filtro-nombre" placeholder="Nombre o apellido" 
                   style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
        </div>
        
        <!-- Filtro por Estado -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Estado:
            </label>
            <select id="filtro-estado" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todos los estados</option>
                <option value="activo">✅ Activo</option>
                <option value="inactivo">❌ Inactivo</option>
            </select>
        </div>
        
        <!-- Búsqueda por email -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Buscar por email:
            </label>
            <input type="text" id="filtro-email" placeholder="ejemplo@comanda.com" 
                   style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
        </div>
        
        <!-- Filtro por Mesas Asignadas -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Mesas asignadas:
            </label>
            <select id="filtro-mesas" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todos</option>
                <option value="con-mesas">Con mesas asignadas</option>
                <option value="sin-mesas">Sin mesas asignadas</option>
            </select>
        </div>
    </div>
    
    <!-- Botones de acción -->
    <div style="display: flex; gap: 0.5rem; margin-top: 1rem; align-items: center;">
        <button onclick="limpiarFiltrosMozos()" style="padding: 0.5rem 1rem; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Limpiar Filtros
        </button>
        <span id="contador-mozos" style="margin-left: auto; padding: 0.5rem 1rem; color: #6c757d; font-size: 0.875rem;">
            Mostrando <span id="num-mozos"><?= count($mozos) ?></span> mozo(s)
        </span>
    </div>
</div>

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
      <tr class="mozo-row"
          data-nombre="<?= htmlspecialchars(strtolower($m['nombre'].' '.$m['apellido'])) ?>"
          data-email="<?= htmlspecialchars(strtolower($m['email'])) ?>"
          data-estado="<?= htmlspecialchars($m['estado']) ?>"
          data-mesas="<?= $m['mesas_asignadas'] ?>">
        <td><?= $m['id_usuario'] ?></td>
        <td><?= htmlspecialchars($m['nombre'].' '.$m['apellido']) ?></td>
        <td><?= htmlspecialchars($m['email']) ?></td>
        <td>
          <?php if ($m['mesas_asignadas'] > 0): ?>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                         background: #cce5ff; color: #004085;">
              🪑 <?= $m['mesas_asignadas'] ?> mesa(s)
            </span>
          <?php else: ?>
            <span style="color: #6c757d; font-style: italic;">Sin mesas</span>
          <?php endif; ?>
        </td>
        <td>
          <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                       background: <?= $m['estado'] === 'activo' ? '#d4edda' : '#f8d7da' ?>; 
                       color: <?= $m['estado'] === 'activo' ? '#155724' : '#721c24' ?>;">
            <?= $m['estado'] === 'activo' ? '✅ Activo' : '❌ Inactivo' ?>
          </span>
        </td>
        <td>
          <a href="<?= url('mozos/edit', ['id' => $m['id_usuario']]) ?>" class="btn-action">Editar</a>
          <a href="<?= url('mozos', ['delete' => $m['id_usuario']]) ?>" class="btn-action" style="background: #dc3545;"
             onclick="return confirm('¿Estás seguro de que quieres borrar este mozo?')">Borrar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>

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
    document.getElementById('filtro-nombre').addEventListener('input', aplicarFiltrosMozos);
    document.getElementById('filtro-estado').addEventListener('change', aplicarFiltrosMozos);
    document.getElementById('filtro-email').addEventListener('input', aplicarFiltrosMozos);
    document.getElementById('filtro-mesas').addEventListener('change', aplicarFiltrosMozos);
});
</script>


