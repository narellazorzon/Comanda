<?php
// src/views/llamados/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Models\LlamadoMesa;

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Mozos y administradores pueden acceder
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ../../public/index.php?route=unauthorized');
    exit;
}

// Eliminar llamados antiguos (más de 20 minutos) automáticamente
$llamados_eliminados = LlamadoMesa::deleteOldCalls();
if ($llamados_eliminados > 0) {
    error_log("Llamados eliminados automáticamente: " . $llamados_eliminados);
}

// Manejar acción de atender llamado
if (isset($_GET['atender'])) {
    $id_llamado = (int)$_GET['atender'];
    error_log("Intentando atender llamado ID: " . $id_llamado);
    
    if (LlamadoMesa::delete($id_llamado)) {
        error_log("Llamado eliminado exitosamente: " . $id_llamado);
        // Usar JavaScript para recargar la página con mensaje de éxito
        echo "<script>
            alert('✅ Llamado atendido exitosamente');
            window.location.href = 'index.php?route=llamados';
        </script>";
        exit;
    } else {
        error_log("Error al eliminar llamado: " . $id_llamado);
        echo "<script>
            alert('❌ Error al atender el llamado');
            window.location.href = 'index.php?route=llamados';
        </script>";
        exit;
    }
}

// Verificar si se acaba de atender un llamado
$mensaje_atendido = isset($_GET['atendido']) && $_GET['atendido'] == '1';

// Eliminar llamados antiguos (más de 20 minutos) automáticamente
$llamados_eliminados = LlamadoMesa::deleteOldCalls();
if ($llamados_eliminados > 0) {
    error_log("Llamados eliminados automáticamente: " . $llamados_eliminados);
}

// Manejar acción de atender llamado
if (isset($_GET['atender'])) {
    $id_llamado = (int)$_GET['atender'];
    error_log("Intentando atender llamado ID: " . $id_llamado);
    
    if (LlamadoMesa::delete($id_llamado)) {
        error_log("Llamado eliminado exitosamente: " . $id_llamado);
        // Usar JavaScript para recargar la página con mensaje de éxito
        echo "<script>
            alert('✅ Llamado atendido exitosamente');
            window.location.href = 'index.php?route=llamados';
        </script>";
        exit;
    } else {
        error_log("Error al eliminar llamado: " . $id_llamado);
        echo "<script>
            alert('❌ Error al atender el llamado');
            window.location.href = 'index.php?route=llamados';
        </script>";
        exit;
    }
}

// Verificar si se acaba de atender un llamado
$mensaje_atendido = isset($_GET['atendido']) && $_GET['atendido'] == '1';

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

?>

<h2>🔔 Llamados de Mesa</h2>

<?php if ($mensaje_atendido): ?>
<div style="background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #155724; border: 1px solid #c3e6cb;">
  ✅ Llamado atendido exitosamente
</div>
<?php endif; ?>

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
              <?= $llamado['estado'] === 'completado' ? '✅ Atendido' : '⏰ Pendiente' ?>
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
                Atendido
              </span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<script>
// Ocultar mensaje de éxito después de 3 segundos
document.addEventListener('DOMContentLoaded', function() {
    const mensaje = document.querySelector('[style*="background: #d4edda"]');
    if (mensaje) {
        setTimeout(() => {
            mensaje.style.transition = 'opacity 0.5s ease';
            mensaje.style.opacity = '0';
            setTimeout(() => {
                mensaje.remove();
            }, 500);
        }, 3000);
    }
});
</script>
