<?php 
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

// La sesión ya está iniciada desde public/index.php
// Verificar autenticación y permisos
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'administrador') {
    header('Location: ' . url('unauthorized'));
    exit;
}

use App\Models\Reporte;

// Parámetros de filtro
$mes = $_GET['mes'] ?? date('Y-m');

// Obtener propinas por mozo
$tips = Reporte::propinasPorMozo($mes);
?>

<style>
/* Usar las mismas variables de color del dashboard principal */
:root {
  --background: #f7f1e1;
  --surface: #eee0be;
  --primary: #a5a4a1;
  --secondary: #a1866f;
  --accent: #eee0be;
  --text: #3f3f3f;
  --text-light: #ffffff;
}

body {
    background-color: var(--background);
    font-family: "Segoe UI", Tahoma, sans-serif;
    margin: 0;
    padding: 0;
    color: var(--text);
}

main {
    max-width: 960px;
    margin: 1.5rem auto;
    padding: 0 1rem;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2rem;
    background: var(--surface);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.table th,
.table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--accent);
}

.table th {
    background-color: var(--secondary);
    color: var(--text-light);
    font-weight: 600;
}

.table tr:hover td {
    background-color: var(--accent);
}

.button {
    display: inline-block;
    background-color: var(--secondary);
    color: var(--text-light);
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.95rem;
    transition: background-color 0.2s ease;
    margin-bottom: 1rem;
}

.button:hover {
    background-color: #8b5e46;
    text-decoration: none;
    color: var(--text-light);
}
</style>

<main>
    <h2>💰 Propinas por Mozo (<?= htmlspecialchars($mes) ?>)</h2>
    
    <a href="<?= url('reportes') ?>" class="button">← Volver a Reportes</a>

    <table class="table">
        <thead>
            <tr>
                <th>Mozo</th>
                <th>Total de Propinas</th>
                <th>Número de Pedidos</th>
                <th>Promedio por Pedido</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tips)): ?>
                <tr>
                    <td colspan="4">No hay datos de propinas para este período.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tips as $tip): ?>
                    <tr>
                        <td><?= htmlspecialchars($tip['nombre'] . ' ' . $tip['apellido']) ?></td>
                        <td><strong>$<?= number_format($tip['monto'] ?? 0, 2) ?></strong></td>
                        <td><?= number_format($tip['pedidos'] ?? 0) ?></td>
                        <td>$<?= number_format(($tip['monto'] ?? 0) / max(1, $tip['pedidos'] ?? 1), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</main>