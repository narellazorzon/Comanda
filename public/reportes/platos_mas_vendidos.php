<?php 
session_start();

// Verificar autenticación y permisos
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
    header('Location: ../unauthorized.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Reporte;

// Parámetros de filtro
$periodo = $_GET['periodo'] ?? 'mes';
$limite = (int)($_GET['limite'] ?? 10);

// Validar período
$periodos_validos = ['semana', 'mes', 'año'];
if (!in_array($periodo, $periodos_validos)) {
    $periodo = 'mes';
}

// Obtener datos usando el modelo Reporte
$platos = Reporte::platosMasVendidos($periodo, $limite);
$stats = Reporte::estadisticasPeriodo($periodo);
?>

<style>
/* Usar las mismas variables de color del dashboard principal */
:root {
  --background: #f7f1e1; /* beige muy claro */
  --surface: #eee0be; /* blanco para cartas y tablas */
  --primary: #a5a4a1; /* beige medio */
  --secondary: #a1866f; /* marrón suave */
  --accent: #eee0be; /* tonalidad intermedia */
  --text: #3f3f3f; /* gris oscuro para texto */
  --text-light: #ffffff; /* texto claro sobre fondo oscuro */
}

body {
    background-color: var(--background);
    font-family: "Segoe UI", Tahoma, sans-serif;
    margin: 0;
    padding: 0;
    color: var(--text);
}

nav {
    background-color: var(--primary);
    padding: 0.75rem 1rem;
    border-bottom: 2px solid var(--secondary);
}

nav a {
    color: var(--text);
    text-decoration: none;
    margin-right: 1rem;
    font-weight: 600;
}

nav a:hover {
    color: var(--secondary);
}

main {
    max-width: 960px;
    margin: 1.5rem auto;
    padding: 0 1rem;
}

.report-header {
    background: var(--secondary);
    color: var(--text-light);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: center;
}

.report-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
}

.report-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.filters-section {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    gap: 1.5rem;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 600;
    color: var(--secondary);
}

.filter-group select, .filter-group input {
    padding: 0.5rem;
    border: 1px solid var(--primary);
    border-radius: 4px;
    font-size: 1rem;
}

.apply-btn {
    background: var(--secondary);
    color: var(--text-light);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.2s ease;
}

.apply-btn:hover {
    background-color: #8b5e46;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: var(--text);
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-card .value {
    font-size: 2em;
    font-weight: bold;
    color: var(--secondary);
}

.platos-table {
    background: var(--surface);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.platos-table table {
    width: 100%;
    border-collapse: collapse;
}

.platos-table th {
    background: var(--secondary);
    color: var(--text-light);
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
}

.platos-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--accent);
}

.platos-table tr:hover td {
    background-color: var(--accent);
}

.plato-name {
    font-weight: 600;
    color: var(--secondary);
}

.plato-category {
    color: var(--text);
    font-size: 0.9em;
}

.quantity-badge {
    background: var(--secondary);
    color: var(--text-light);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.9em;
    font-weight: 600;
}

.revenue {
    color: var(--secondary);
    font-weight: 600;
}

.no-data {
    text-align: center;
    padding: 2rem;
    color: var(--text);
    font-style: italic;
}

.back-btn {
    display: inline-block;
    background: var(--secondary);
    color: var(--text-light);
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.2s ease;
    margin-bottom: 1.5rem;
}

.back-btn:hover {
    background-color: #8b5e46;
    text-decoration: none;
    color: var(--text-light);
}

@media (max-width: 600px) {
    .filters-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .platos-table {
        overflow-x: auto;
    }
}
</style>

<main>
    <a href="index.php" class="back-btn">← Volver a Reportes</a>

    <div class="report-header">
        <h1>🍽️ Reporte de Platos Más Vendidos</h1>
        <p>Análisis de ventas por período de tiempo</p>
    </div>

    <div class="filters-section">
        <div class="filter-group">
            <label for="periodo">Período:</label>
            <select name="periodo" id="periodo" onchange="updateFilters()">
                <option value="semana" <?= $periodo === 'semana' ? 'selected' : '' ?>>Última Semana</option>
                <option value="mes" <?= $periodo === 'mes' ? 'selected' : '' ?>>Último Mes</option>
                <option value="año" <?= $periodo === 'año' ? 'selected' : '' ?>>Último Año</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="limite">Cantidad de resultados:</label>
            <select name="limite" id="limite" onchange="updateFilters()">
                <option value="5" <?= $limite === 5 ? 'selected' : '' ?>>Top 5</option>
                <option value="10" <?= $limite === 10 ? 'selected' : '' ?>>Top 10</option>
                <option value="20" <?= $limite === 20 ? 'selected' : '' ?>>Top 20</option>
                <option value="50" <?= $limite === 50 ? 'selected' : '' ?>>Top 50</option>
            </select>
        </div>
        
        <button class="apply-btn" onclick="applyFilters()">Aplicar Filtros</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total de Pedidos</h3>
            <div class="value"><?= number_format($stats['total_pedidos'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <h3>Ingresos Totales</h3>
            <div class="value">$<?= number_format($stats['ingresos_totales'] ?? 0, 2) ?></div>
        </div>
        <div class="stat-card">
            <h3>Platos Analizados</h3>
            <div class="value"><?= count($platos) ?></div>
        </div>
        <div class="stat-card">
            <h3>Período</h3>
            <div class="value"><?= ucfirst($periodo) ?></div>
        </div>
    </div>

    <div class="platos-table">
        <?php if (empty($platos)): ?>
            <div class="no-data">
                <h3>No hay datos disponibles</h3>
                <p>No se encontraron ventas para el período seleccionado.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Plato</th>
                        <th>Categoría</th>
                        <th>Precio Unitario</th>
                        <th>Total Vendido</th>
                        <th>Pedidos</th>
                        <th>Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($platos as $index => $plato): ?>
                        <tr>
                            <td>
                                <span class="quantity-badge"><?= $index + 1 ?></span>
                            </td>
                            <td>
                                <div class="plato-name"><?= htmlspecialchars($plato['nombre']) ?></div>
                            </td>
                            <td>
                                <div class="plato-category"><?= htmlspecialchars($plato['categoria'] ?? 'Sin categoría') ?></div>
                            </td>
                            <td>$<?= number_format($plato['precio'], 2) ?></td>
                            <td>
                                <span class="quantity-badge"><?= number_format($plato['total_vendido']) ?></span>
                            </td>
                            <td><?= number_format($plato['total_pedidos']) ?></td>
                            <td class="revenue">$<?= number_format($plato['ingresos_totales'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<script>
function updateFilters() {
    const periodo = document.getElementById('periodo').value;
    const limite = document.getElementById('limite').value;
    
    const url = new URL(window.location);
    url.searchParams.set('periodo', periodo);
    url.searchParams.set('limite', limite);
    
    window.location.href = url.toString();
}

function applyFilters() {
    updateFilters();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
