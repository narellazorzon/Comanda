<?php
// src/views/mesas/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Mesa;
use App\Models\Usuario;

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
            $resultado = Mesa::delete($id);
            
            if ($resultado) {
                header('Location: ' . url('mesas', ['success' => '1']));
            } else {
                header('Location: ' . url('mesas', ['error' => '2']));
            }
        } else {
            header('Location: ' . url('mesas', ['error' => '1']));
        }
        exit;
    }
    header('Location: ' . url('mesas'));
    exit;
}

// 1) Cargamos todas las mesas
$mesas = Mesa::all();

// Obtener datos únicos para filtros
$ubicaciones_unicas = array_unique(array_filter(array_column($mesas, 'ubicacion')));
sort($ubicaciones_unicas);

$mozos = Usuario::findByRole('mozo');
?>

<h2><?= $rol === 'administrador' ? 'Gestión de Mesas' : 'Consulta de Mesas' ?></h2>

<?php if (isset($_GET['success'])): ?>
    <div class="success" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        Mesa eliminada correctamente.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="error" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        No se puede eliminar una mesa que está ocupada.
    </div>
<?php endif; ?>

<?php if ($rol === 'administrador'): ?>
  <div style="margin-bottom: 1rem;">
    <a href="<?= url('mesas/create') ?>" class="button">Nueva Mesa</a>
    <a href="<?= url('mesas/cambiar-mozo') ?>" class="button" style="background: #ffc107; color: #212529; margin-left: 10px;">
      🔄 Gestionar Mozos
    </a>
  </div>
<?php else: ?>
  <div style="background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #0c5460;">
    👁️ Vista de solo lectura - Consulta las mesas disponibles
  </div>
<?php endif; ?>

<!-- Panel de Filtros -->
<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
        <h4 style="margin: 0; color: #6c757d; font-size: 1rem;">
            <span style="margin-right: 0.5rem;">🔍</span>Filtros de búsqueda
        </h4>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <!-- Búsqueda por número -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Buscar por número:
            </label>
            <input type="text" id="filtro-numero" placeholder="Ej: 5, 12" 
                   style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
        </div>
        
        <!-- Filtro por Estado -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Estado:
            </label>
            <select id="filtro-estado" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todos los estados</option>
                <option value="libre">🟢 Libre</option>
                <option value="ocupada">🔴 Ocupada</option>
                <option value="reservada">🟡 Reservada</option>
            </select>
        </div>
        
        <!-- Filtro por Ubicación -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Ubicación:
            </label>
            <select id="filtro-ubicacion" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todas las ubicaciones</option>
                <?php foreach ($ubicaciones_unicas as $ubicacion): ?>
                    <option value="<?= htmlspecialchars($ubicacion) ?>"><?= htmlspecialchars($ubicacion) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Filtro por Mozo Asignado -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Mozo asignado:
            </label>
            <select id="filtro-mozo" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todos los mozos</option>
                <option value="sin-asignar">Sin asignar</option>
                <?php foreach ($mozos as $mozo): ?>
                    <option value="<?= htmlspecialchars($mozo['nombre'] . ' ' . $mozo['apellido']) ?>">
                        <?= htmlspecialchars($mozo['nombre'] . ' ' . $mozo['apellido']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <!-- Botones de acción -->
    <div style="display: flex; gap: 0.5rem; margin-top: 1rem; align-items: center;">
        <button onclick="limpiarFiltrosMesas()" style="padding: 0.5rem 1rem; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Limpiar Filtros
        </button>
        <span id="contador-mesas" style="margin-left: auto; padding: 0.5rem 1rem; color: #6c757d; font-size: 0.875rem;">
            Mostrando <span id="num-mesas"><?= count($mesas) ?></span> mesa(s)
        </span>
    </div>
</div>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Número</th>
      <th>Ubicación</th>
      <th>Estado</th>
      <th>Mozo Asignado</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mesas as $m): ?>
      <tr class="mesa-row"
          data-numero="<?= htmlspecialchars($m['numero']) ?>"
          data-estado="<?= htmlspecialchars($m['estado']) ?>"
          data-ubicacion="<?= htmlspecialchars($m['ubicacion'] ?? '') ?>"
          data-mozo="<?= htmlspecialchars($m['mozo_nombre_completo'] ?? 'sin-asignar') ?>">
        <td><?= htmlspecialchars($m['id_mesa']) ?></td>
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
            <a href="<?= url('mesas/edit', ['id' => $m['id_mesa']]) ?>" class="btn-action">Editar</a>
            <?php if ($m['estado'] === 'libre'): ?>
              <a href="#" class="btn-action" style="background: #dc3545;"
                 onclick="confirmarBorrado(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>)">
                Borrar
              </a>
            <?php else: ?>
              <span class="btn-action" style="background: #6c757d; cursor: not-allowed; opacity: 0.6;" 
                    title="No se puede borrar una mesa <?= $m['estado'] ?>">
                Borrar
              </span>
            <?php endif; ?>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Modal de confirmación -->
<div id="modalConfirmacion" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
  <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 400px; width: 90%; text-align: center;">
    <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
    <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.5rem;">Confirmar Eliminación</h3>
    <p style="margin: 0 0 2rem 0; color: #666; line-height: 1.5;">
      ¿Estás seguro de que quieres eliminar la mesa <strong id="numeroMesa"></strong>?<br>
      <small style="color: #999;">Esta acción no se puede deshacer.</small>
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
      <button id="btnCancelar" style="padding: 0.75rem 1.5rem; border: 2px solid #6c757d; background: white; color: #6c757d; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s;">
        Cancelar
      </button>
      <button id="btnConfirmar" style="padding: 0.75rem 1.5rem; border: none; background: #dc3545; color: white; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s;">
        Sí, Eliminar
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
});

document.getElementById('btnCancelar').addEventListener('mouseleave', function() {
    this.style.background = 'white';
    this.style.color = '#6c757d';
});

document.getElementById('btnConfirmar').addEventListener('mouseenter', function() {
    this.style.background = '#c82333';
    this.style.transform = 'translateY(-2px)';
});

document.getElementById('btnConfirmar').addEventListener('mouseleave', function() {
    this.style.background = '#dc3545';
    this.style.transform = 'translateY(0)';
});

// === FUNCIONALIDAD DE FILTROS ===
function aplicarFiltrosMesas() {
    const filtroNumero = document.getElementById('filtro-numero').value.toLowerCase();
    const filtroEstado = document.getElementById('filtro-estado').value.toLowerCase();
    const filtroUbicacion = document.getElementById('filtro-ubicacion').value.toLowerCase();
    const filtroMozo = document.getElementById('filtro-mozo').value.toLowerCase();
    
    const filas = document.querySelectorAll('.mesa-row');
    let contadorVisible = 0;
    
    filas.forEach(fila => {
        const numero = fila.dataset.numero.toLowerCase();
        const estado = fila.dataset.estado.toLowerCase();
        const ubicacion = fila.dataset.ubicacion.toLowerCase();
        const mozo = fila.dataset.mozo.toLowerCase();
        
        let mostrar = true;
        
        // Filtro por número (búsqueda parcial)
        if (filtroNumero && !numero.includes(filtroNumero)) {
            mostrar = false;
        }
        
        // Filtro por estado
        if (filtroEstado && estado !== filtroEstado) {
            mostrar = false;
        }
        
        // Filtro por ubicación
        if (filtroUbicacion && ubicacion !== filtroUbicacion) {
            mostrar = false;
        }
        
        // Filtro por mozo
        if (filtroMozo) {
            if (filtroMozo === 'sin-asignar' && mozo !== 'sin-asignar') {
                mostrar = false;
            } else if (filtroMozo !== 'sin-asignar' && mozo !== filtroMozo) {
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
    document.getElementById('num-mesas').textContent = contadorVisible;
    
    // Mostrar mensaje si no hay resultados
    if (contadorVisible === 0 && filas.length > 0) {
        let filaNoResultados = document.getElementById('fila-no-mesas');
        if (!filaNoResultados) {
            const tbody = document.querySelector('.table tbody');
            const nuevaFila = document.createElement('tr');
            nuevaFila.id = 'fila-no-mesas';
            const numColumnas = document.querySelector('.table thead tr').children.length;
            nuevaFila.innerHTML = `<td colspan="${numColumnas}" style="text-align: center; padding: 2rem; color: #6c757d;">
                <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">🪑 No se encontraron mesas con los filtros aplicados</div>
                <div style="font-size: 0.9rem;">Intenta ajustar los criterios de búsqueda</div>
            </td>`;
            tbody.appendChild(nuevaFila);
        }
    } else {
        const filaNoResultados = document.getElementById('fila-no-mesas');
        if (filaNoResultados) {
            filaNoResultados.remove();
        }
    }
}

function limpiarFiltrosMesas() {
    document.getElementById('filtro-numero').value = '';
    document.getElementById('filtro-estado').value = '';
    document.getElementById('filtro-ubicacion').value = '';
    document.getElementById('filtro-mozo').value = '';
    
    const filas = document.querySelectorAll('.mesa-row');
    filas.forEach(fila => {
        fila.style.display = '';
    });
    
    document.getElementById('num-mesas').textContent = filas.length;
    
    const filaNoResultados = document.getElementById('fila-no-mesas');
    if (filaNoResultados) {
        filaNoResultados.remove();
    }
}

// Agregar eventos a los filtros
document.getElementById('filtro-numero').addEventListener('input', aplicarFiltrosMesas);
document.getElementById('filtro-estado').addEventListener('change', aplicarFiltrosMesas);
document.getElementById('filtro-ubicacion').addEventListener('change', aplicarFiltrosMesas);
document.getElementById('filtro-mozo').addEventListener('change', aplicarFiltrosMesas);
</script>
