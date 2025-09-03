<?php
// src/views/llamados/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Models\LlamadoMesa;

session_start();
// Mozos y administradores pueden acceder
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ../../public/index.php?route=unauthorized');
    exit;
}

// Si es un mozo, solo mostrar sus llamados; si es admin, mostrar todos
$user_id = $_SESSION['user']['id_usuario'];
$user_rol = $_SESSION['user']['rol'];

if ($user_rol === 'mozo') {
    // Obtener llamados solo de las mesas asignadas a este mozo
    $llamados = LlamadoMesa::getByMozo($user_id);
} else {
    // Admin puede ver todos los llamados
    $llamados = LlamadoMesa::all();
}

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
      <th>Ubicación</th>
      <th>Mozo Asignado</th>
      <th>Estado</th>
      <th>Hora</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($llamados)): ?>
      <tr>
        <td colspan="7">No hay llamados pendientes para tus mesas.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($llamados as $llamado): ?>
        <tr>
          <td><?= htmlspecialchars($llamado['id_llamado']) ?></td>
          <td><strong>Mesa <?= htmlspecialchars($llamado['numero_mesa']) ?></strong></td>
          <td><?= htmlspecialchars($llamado['ubicacion_mesa'] ?? '—') ?></td>
          <td>
            <?php if (!empty($llamado['mozo_nombre_completo'])): ?>
              <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                           background: #e2e3e5; color: #383d41;">
                👤 <?= htmlspecialchars($llamado['mozo_nombre_completo']) ?>
              </span>
            <?php else: ?>
              <span style="color: #dc3545; font-style: italic;">⚠️ Sin asignar</span>
            <?php endif; ?>
          </td>
          <td>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                         background: <?= $llamado['estado'] === 'completado' ? '#d4edda' : '#f8d7da' ?>; 
                         color: <?= $llamado['estado'] === 'completado' ? '#155724' : '#721c24' ?>;">
              <?= $llamado['estado'] === 'completado' ? '✅ Completado' : '⏰ Pendiente' ?>
            </span>
          </td>
          <td><?= date('H:i', strtotime($llamado['hora_solicitud'])) ?></td>
          <td>
            <?php if ($llamado['estado'] === 'pendiente'): ?>
              <a href="?atender=<?= $llamado['id_llamado'] ?>" class="btn-action" style="background: #28a745;">
                Atender
              </a>
            <?php else: ?>
              <span class="btn-action" style="background: #6c757d; cursor: not-allowed; opacity: 0.6;">
                Completado
              </span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
