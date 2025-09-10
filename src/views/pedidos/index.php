<?php
// src/views/pedidos/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Pedido;
use App\Models\Mesa;
use App\Models\Usuario;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    // La sesión ya está iniciada desde public/index.php
}
// Mozos y administradores pueden ver pedidos
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];

// Cargar pedidos
$pedidos = Pedido::all();

// Obtener listas únicas para los filtros
$mesas = Mesa::all();
$mozos = Usuario::findByRole('mozo');

// Obtener valores únicos de los pedidos existentes
$estados_unicos = array_unique(array_column($pedidos, 'estado'));
$fechas_unicas = array_unique(array_map(function($p) {
    return date('Y-m-d', strtotime($p['fecha_hora'] ?? $p['fecha_creacion']));
}, $pedidos));
sort($fechas_unicas);

?>

<h2><?= $rol === 'administrador' ? 'Gestión de Pedidos' : 'Consulta de Pedidos' ?></h2>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
    <div style="flex: 1;">
        <?php if ($rol === 'administrador'): ?>
            <a href="<?= url('pedidos/create') ?>" class="button">Nuevo Pedido</a>
        <?php else: ?>
            <div style="background: #d1ecf1; padding: 10px; border-radius: 4px; color: #0c5460;">
                🍽️ Vista de pedidos - Gestiona los pedidos de las mesas
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Panel de Filtros -->
<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
        <h4 style="margin: 0; color: #6c757d; font-size: 1rem;">
            <span style="margin-right: 0.5rem;">🔍</span>Filtros de búsqueda
        </h4>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
        <!-- Filtro por Estado -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Estado:
            </label>
            <select id="filtro-estado" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todos los estados</option>
                <option value="pendiente">⏳ Pendiente</option>
                <option value="en_preparacion">👨‍🍳 En preparación</option>
                <option value="servido">✅ Servido</option>
                <option value="cuenta">💳 Cuenta</option>
                <option value="cerrado">🔒 Cerrado</option>
            </select>
        </div>
        
        <!-- Filtro por Mesa -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Mesa:
            </label>
            <select id="filtro-mesa" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todas las mesas</option>
                <option value="takeaway">🥡 Takeaway</option>
                <?php foreach ($mesas as $mesa): ?>
                    <option value="<?= $mesa['numero'] ?>">Mesa <?= $mesa['numero'] ?> - <?= $mesa['ubicacion'] ?? 'Sin ubicación' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Filtro por Mozo -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Mozo:
            </label>
            <select id="filtro-mozo" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todos los mozos</option>
                <?php foreach ($mozos as $mozo): ?>
                    <option value="<?= htmlspecialchars($mozo['nombre'] . ' ' . $mozo['apellido']) ?>">
                        <?= htmlspecialchars($mozo['nombre'] . ' ' . $mozo['apellido']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Filtro por Fecha -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Fecha:
            </label>
            <input type="date" id="filtro-fecha" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
        </div>
        
        <!-- Filtro por Rango de Total -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Total mínimo:
            </label>
            <input type="number" id="filtro-total-min" placeholder="$0.00" step="0.01" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Total máximo:
            </label>
            <input type="number" id="filtro-total-max" placeholder="$999.99" step="0.01" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
        </div>
    </div>
    
    <!-- Botones de acción -->
    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
        <button onclick="aplicarFiltros()" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
            Aplicar Filtros
        </button>
        <button onclick="limpiarFiltros()" style="padding: 0.5rem 1rem; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Limpiar
        </button>
        <span id="contador-resultados" style="margin-left: auto; padding: 0.5rem 1rem; color: #6c757d; font-size: 0.875rem;">
            Mostrando <span id="num-resultados"><?= count($pedidos) ?></span> pedido(s)
        </span>
    </div>
</div>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Mesa</th>
      <th>Mozo</th>
      <th>Estado</th>
      <th>Total</th>
      <th>Fecha</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($pedidos)): ?>
      <tr>
        <td colspan="<?= $rol === 'administrador' ? '7' : '6' ?>">No hay pedidos registrados.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($pedidos as $pedido): ?>
        <tr class="pedido-row" 
            data-estado="<?= htmlspecialchars($pedido['estado']) ?>"
            data-mesa="<?= htmlspecialchars($pedido['numero_mesa'] ?? 'takeaway') ?>"
            data-mozo="<?= htmlspecialchars($pedido['nombre_mozo_completo'] ?? '') ?>"
            data-fecha="<?= date('Y-m-d', strtotime($pedido['fecha_hora'] ?? $pedido['fecha_creacion'])) ?>"
            data-total="<?= $pedido['total'] ?>">
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
                    $bg_color = '#d1ecf1';
                    $text_color = '#0c5460';
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
          <td><?= !empty($pedido['fecha_creacion']) ? date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) : 'N/A' ?></td>
          <?php if ($rol === 'administrador'): ?>
            <td>
              <a href="<?= url('pedidos/edit', ['id' => $pedido['id_pedido']]) ?>" class="btn-action">Editar</a>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<script>
// Función para aplicar los filtros
function aplicarFiltros() {
    const filtroEstado = document.getElementById('filtro-estado').value.toLowerCase();
    const filtroMesa = document.getElementById('filtro-mesa').value.toLowerCase();
    const filtroMozo = document.getElementById('filtro-mozo').value.toLowerCase();
    const filtroFecha = document.getElementById('filtro-fecha').value;
    const filtroTotalMin = parseFloat(document.getElementById('filtro-total-min').value) || 0;
    const filtroTotalMax = parseFloat(document.getElementById('filtro-total-max').value) || 999999;
    
    const filas = document.querySelectorAll('.pedido-row');
    let contadorVisible = 0;
    
    filas.forEach(fila => {
        const estado = fila.dataset.estado;
        const mesa = fila.dataset.mesa.toLowerCase();
        const mozo = fila.dataset.mozo.toLowerCase();
        const fecha = fila.dataset.fecha;
        const total = parseFloat(fila.dataset.total);
        
        let mostrar = true;
        
        // Filtro por estado
        if (filtroEstado && estado !== filtroEstado) {
            mostrar = false;
        }
        
        // Filtro por mesa
        if (filtroMesa) {
            if (filtroMesa === 'takeaway' && mesa !== 'takeaway') {
                mostrar = false;
            } else if (filtroMesa !== 'takeaway' && mesa !== filtroMesa) {
                mostrar = false;
            }
        }
        
        // Filtro por mozo
        if (filtroMozo && !mozo.includes(filtroMozo.toLowerCase())) {
            mostrar = false;
        }
        
        // Filtro por fecha
        if (filtroFecha && fecha !== filtroFecha) {
            mostrar = false;
        }
        
        // Filtro por rango de total
        if (total < filtroTotalMin || total > filtroTotalMax) {
            mostrar = false;
        }
        
        // Mostrar u ocultar la fila
        if (mostrar) {
            fila.style.display = '';
            contadorVisible++;
        } else {
            fila.style.display = 'none';
        }
    });
    
    // Actualizar contador de resultados
    document.getElementById('num-resultados').textContent = contadorVisible;
    
    // Si no hay resultados visibles, mostrar mensaje
    if (contadorVisible === 0 && filas.length > 0) {
        // Verificar si ya existe la fila de "sin resultados"
        let filaNoResultados = document.getElementById('fila-no-resultados');
        if (!filaNoResultados) {
            const tbody = document.querySelector('.table tbody');
            const nuevaFila = document.createElement('tr');
            nuevaFila.id = 'fila-no-resultados';
            nuevaFila.innerHTML = `<td colspan="${document.querySelector('.table thead tr').children.length}" style="text-align: center; padding: 2rem; color: #6c757d;">
                <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">🔍 No se encontraron pedidos con los filtros aplicados</div>
                <div style="font-size: 0.9rem;">Intenta ajustar los criterios de búsqueda</div>
            </td>`;
            tbody.appendChild(nuevaFila);
        }
    } else {
        // Remover fila de "sin resultados" si existe
        const filaNoResultados = document.getElementById('fila-no-resultados');
        if (filaNoResultados) {
            filaNoResultados.remove();
        }
    }
}

// Función para limpiar todos los filtros
function limpiarFiltros() {
    document.getElementById('filtro-estado').value = '';
    document.getElementById('filtro-mesa').value = '';
    document.getElementById('filtro-mozo').value = '';
    document.getElementById('filtro-fecha').value = '';
    document.getElementById('filtro-total-min').value = '';
    document.getElementById('filtro-total-max').value = '';
    
    // Mostrar todas las filas
    const filas = document.querySelectorAll('.pedido-row');
    filas.forEach(fila => {
        fila.style.display = '';
    });
    
    // Actualizar contador
    document.getElementById('num-resultados').textContent = filas.length;
    
    // Remover fila de "sin resultados" si existe
    const filaNoResultados = document.getElementById('fila-no-resultados');
    if (filaNoResultados) {
        filaNoResultados.remove();
    }
}

// Aplicar filtros automáticamente cuando cambian los valores
document.addEventListener('DOMContentLoaded', function() {
    // Agregar eventos a todos los filtros para aplicar automáticamente
    const filtros = ['filtro-estado', 'filtro-mesa', 'filtro-mozo', 'filtro-fecha', 'filtro-total-min', 'filtro-total-max'];
    
    filtros.forEach(filtroId => {
        const elemento = document.getElementById(filtroId);
        if (elemento) {
            elemento.addEventListener('change', aplicarFiltros);
            if (elemento.type === 'number' || elemento.type === 'text') {
                elemento.addEventListener('input', aplicarFiltros);
            }
        }
    });
    
    // Aplicar filtros si hay valores en la URL (para mantener estado)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('estado')) {
        document.getElementById('filtro-estado').value = urlParams.get('estado');
        aplicarFiltros();
    }
});
</script>


