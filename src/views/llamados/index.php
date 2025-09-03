<?php
// src/views/llamados/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Models\LlamadoMesa;

session_start();
// Solo mozos pueden acceder
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'mozo') {
    header('Location: ../../public/index.php?route=unauthorized');
    exit;
}

// Cargar llamados
$llamados = LlamadoMesa::all();

include __DIR__ . '/../includes/header.php';
?>

<h2>🔔 Llamados de Mesa</h2>

<div style="background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #0c5460;">
  📞 Gestiona las solicitudes de atención de las mesas
</div>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Mesa</th>
      <th>Tipo de Llamado</th>
      <th>Estado</th>
      <th>Hora</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($llamados)): ?>
      <tr>
        <td colspan="6">No hay llamados pendientes.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($llamados as $llamado): ?>
        <tr>
          <td><?= htmlspecialchars($llamado['id_llamado']) ?></td>
          <td><strong>Mesa <?= htmlspecialchars($llamado['numero_mesa']) ?></strong></td>
          <td><?= htmlspecialchars($llamado['tipo_llamado']) ?></td>
          <td>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                         background: <?= $llamado['estado'] === 'atendido' ? '#d4edda' : '#f8d7da' ?>; 
                         color: <?= $llamado['estado'] === 'atendido' ? '#155724' : '#721c24' ?>;">
              <?= $llamado['estado'] === 'atendido' ? '✅ Atendido' : '⏰ Pendiente' ?>
            </span>
          </td>
          <td><?= date('H:i', strtotime($llamado['fecha_creacion'])) ?></td>
          <td>
            <?php if ($llamado['estado'] === 'pendiente'): ?>
              <a href="?atender=<?= $llamado['id_llamado'] ?>" class="btn-action" style="background: #28a745;">
                Atender
              </a>
            <?php else: ?>
              <span class="btn-action" style="background: #6c757d; cursor: not-allowed; opacity: 0.6;">
                Atendido
              </span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
