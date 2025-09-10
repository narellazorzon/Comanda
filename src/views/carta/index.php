<?php
// src/views/carta/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\CartaItem;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Mozos y administradores pueden ver la carta
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];

// Solo administradores pueden eliminar items
if (isset($_GET['delete']) && $rol === 'administrador') {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        CartaItem::delete($id);
    }
    header('Location: ' . url('carta'));
    exit;
}

// 3) Cargamos todos los ítems de la carta
$items = CartaItem::all();

// Obtener datos únicos para filtros
$categorias_unicas = array_unique(array_filter(array_column($items, 'categoria')));
sort($categorias_unicas);

// Obtener rango de precios
$precios = array_column($items, 'precio');
$precio_min = !empty($precios) ? min($precios) : 0;
$precio_max = !empty($precios) ? max($precios) : 0;

?>

<h2><?= $rol === 'administrador' ? 'Gestión de Carta' : 'Consulta de Carta' ?></h2>

<?php if ($rol === 'administrador'): ?>
  <a href="<?= url('carta/create') ?>" class="button">Nuevo Ítem</a>
<?php else: ?>
  <div style="background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #0c5460;">
    📋 Vista de solo lectura - Consulta los items del menú y precios
  </div>
<?php endif; ?>

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
                Buscar item:
            </label>
            <input type="text" id="filtro-nombre" placeholder="Nombre del plato..." 
                   style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
        </div>
        
        <!-- Filtro por Categoría -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Categoría:
            </label>
            <select id="filtro-categoria" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias_unicas as $categoria): ?>
                    <option value="<?= htmlspecialchars($categoria) ?>">
                        <?= htmlspecialchars($categoria) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Filtro por Disponibilidad -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Disponibilidad:
            </label>
            <select id="filtro-disponibilidad" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                <option value="">Todos</option>
                <option value="1">✅ Disponible</option>
                <option value="0">❌ No disponible</option>
            </select>
        </div>
        
        <!-- Filtro por Precio Mínimo -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Precio mínimo:
            </label>
            <input type="number" id="filtro-precio-min" placeholder="$0.00" step="0.01" min="0" 
                   style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
        </div>
        
        <!-- Filtro por Precio Máximo -->
        <div>
            <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #495057; font-weight: 500;">
                Precio máximo:
            </label>
            <input type="number" id="filtro-precio-max" placeholder="$<?= number_format($precio_max, 2) ?>" step="0.01" min="0" 
                   style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white;">
        </div>
    </div>
    
    <!-- Botones de acción y estadísticas -->
    <div style="display: flex; gap: 0.5rem; margin-top: 1rem; align-items: center; justify-content: space-between;">
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="limpiarFiltrosCarta()" style="padding: 0.5rem 1rem; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Limpiar Filtros
            </button>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <span style="color: #6c757d; font-size: 0.875rem;">
                Rango: $<?= number_format($precio_min, 2) ?> - $<?= number_format($precio_max, 2) ?>
            </span>
            <span id="contador-carta" style="padding: 0.5rem 1rem; color: #6c757d; font-size: 0.875rem;">
                Mostrando <span id="num-items"><?= count($items) ?></span> item(s)
            </span>
        </div>
    </div>
</div>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Descripción</th>
      <th>Precio</th>
      <th>Categoría</th>
      <th>Disponible</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $item): ?>
      <tr class="carta-row"
          data-nombre="<?= htmlspecialchars(strtolower($item['nombre'])) ?>"
          data-categoria="<?= htmlspecialchars(strtolower($item['categoria'] ?? '')) ?>"
          data-disponibilidad="<?= $item['disponibilidad'] ?>"
          data-precio="<?= $item['precio'] ?>">
        <td><?= htmlspecialchars($item['id_item']) ?></td>
        <td><strong><?= htmlspecialchars($item['nombre']) ?></strong></td>
        <td style="max-width: 200px; font-size: 0.9em; color: #666;">
          <?= htmlspecialchars($item['descripcion'] ?? '—') ?>
        </td>
        <td><strong>$<?= number_format($item['precio'], 2) ?></strong></td>
        <td>
          <span style="background: #e9ecef; padding: 2px 6px; border-radius: 10px; font-size: 0.8em;">
            <?= htmlspecialchars($item['categoria'] ?? 'Sin categoría') ?>
          </span>
        </td>
        <td>
          <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                       background: <?= $item['disponibilidad'] ? '#d4edda' : '#f8d7da' ?>; 
                       color: <?= $item['disponibilidad'] ? '#155724' : '#721c24' ?>;">
            <?= $item['disponibilidad'] ? '✅ Disponible' : '❌ No disponible' ?>
          </span>
        </td>
        <?php if ($rol === 'administrador'): ?>
          <td>
            <a href="<?= url('carta/edit') ?>&id=<?= $item['id_item'] ?>" class="btn-action">Editar</a>
            <a href="?delete=<?= $item['id_item'] ?>" class="btn-action" style="background: #dc3545;"
               onclick="return confirm('¿Borrar ítem &quot;<?= htmlspecialchars($item['nombre']) ?>&quot;?')">
              Borrar
            </a>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
// Función para aplicar los filtros de carta
function aplicarFiltrosCarta() {
    const filtroNombre = document.getElementById('filtro-nombre').value.toLowerCase();
    const filtroCategoria = document.getElementById('filtro-categoria').value.toLowerCase();
    const filtroDisponibilidad = document.getElementById('filtro-disponibilidad').value;
    const filtroPrecioMin = parseFloat(document.getElementById('filtro-precio-min').value) || 0;
    const filtroPrecioMax = parseFloat(document.getElementById('filtro-precio-max').value) || 999999;
    
    const filas = document.querySelectorAll('.carta-row');
    let contadorVisible = 0;
    
    filas.forEach(fila => {
        const nombre = fila.dataset.nombre;
        const categoria = fila.dataset.categoria;
        const disponibilidad = fila.dataset.disponibilidad;
        const precio = parseFloat(fila.dataset.precio);
        
        let mostrar = true;
        
        // Filtro por nombre (búsqueda parcial)
        if (filtroNombre && !nombre.includes(filtroNombre)) {
            mostrar = false;
        }
        
        // Filtro por categoría
        if (filtroCategoria && categoria !== filtroCategoria) {
            mostrar = false;
        }
        
        // Filtro por disponibilidad
        if (filtroDisponibilidad !== '' && disponibilidad !== filtroDisponibilidad) {
            mostrar = false;
        }
        
        // Filtro por rango de precio
        if (precio < filtroPrecioMin || precio > filtroPrecioMax) {
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
    
    // Actualizar contador
    document.getElementById('num-items').textContent = contadorVisible;
    
    // Mostrar mensaje si no hay resultados
    if (contadorVisible === 0 && filas.length > 0) {
        let filaNoResultados = document.getElementById('fila-no-items');
        if (!filaNoResultados) {
            const tbody = document.querySelector('.table tbody');
            const nuevaFila = document.createElement('tr');
            nuevaFila.id = 'fila-no-items';
            const numColumnas = document.querySelector('.table thead tr').children.length;
            nuevaFila.innerHTML = `<td colspan="${numColumnas}" style="text-align: center; padding: 2rem; color: #6c757d;">
                <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">🍽️ No se encontraron items con los filtros aplicados</div>
                <div style="font-size: 0.9rem;">Intenta ajustar los criterios de búsqueda</div>
            </td>`;
            tbody.appendChild(nuevaFila);
        }
    } else {
        const filaNoResultados = document.getElementById('fila-no-items');
        if (filaNoResultados) {
            filaNoResultados.remove();
        }
    }
}

function limpiarFiltrosCarta() {
    document.getElementById('filtro-nombre').value = '';
    document.getElementById('filtro-categoria').value = '';
    document.getElementById('filtro-disponibilidad').value = '';
    document.getElementById('filtro-precio-min').value = '';
    document.getElementById('filtro-precio-max').value = '';
    
    const filas = document.querySelectorAll('.carta-row');
    filas.forEach(fila => {
        fila.style.display = '';
    });
    
    document.getElementById('num-items').textContent = filas.length;
    
    const filaNoResultados = document.getElementById('fila-no-items');
    if (filaNoResultados) {
        filaNoResultados.remove();
    }
}

// Agregar eventos a los filtros
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtro-nombre').addEventListener('input', aplicarFiltrosCarta);
    document.getElementById('filtro-categoria').addEventListener('change', aplicarFiltrosCarta);
    document.getElementById('filtro-disponibilidad').addEventListener('change', aplicarFiltrosCarta);
    document.getElementById('filtro-precio-min').addEventListener('input', aplicarFiltrosCarta);
    document.getElementById('filtro-precio-max').addEventListener('input', aplicarFiltrosCarta);
});
</script>


