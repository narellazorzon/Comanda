<?php 
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

// La sesión ya está iniciada desde public/index.php
// Verificar autenticación y permisos
if (empty($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    header('Location: ' . url('unauthorized'));
    exit;
}

use App\Models\Reporte;

// Parámetros de filtro
$mes = $_GET['mes'] ?? date('Y-m');

// Obtener recaudación mensual
$recaud = Reporte::recaudacionMensual($mes);
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

.stat-card {
    background: var(--surface);
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    margin-bottom: 2rem;
}

.stat-card .icon {
    font-size: 3em;
    margin-bottom: 1rem;
    display: block;
}

.stat-card .value {
    font-size: 2.5em;
    font-weight: bold;
    color: var(--secondary);
    margin-bottom: 0.5rem;
}

.stat-card .subtitle {
    color: var(--text);
    font-size: 1.1em;
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
    <h2>📊 Recaudación Mensual (<?= htmlspecialchars($mes) ?>)</h2>
    
    <a href="<?= url('reportes') ?>" class="button">← Volver a Reportes</a>

    <div class="stat-card">
        <span class="icon">💰</span>
        <div class="value">
            <?php if (empty($recaud) || $recaud[0]['total'] === null): ?>
                $0.00
            <?php else: ?>
                $<?= number_format($recaud[0]['total'], 2) ?>
            <?php endif; ?>
        </div>
        <div class="subtitle">Total Recaudado en <?= htmlspecialchars($mes) ?></div>
    </div>

    <?php if (empty($recaud) || $recaud[0]['total'] === null): ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; text-align: center;">
            <h3>📈 No hay datos disponibles</h3>
            <p>No se registraron ingresos para este mes.</p>
        </div>
    <?php endif; ?>
</main>