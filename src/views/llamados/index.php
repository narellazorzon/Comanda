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

<div class="info-banner" style="background: #d1ecf1; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; color: #0c5460; font-size: 0.9rem; border-left: 4px solid #17a2b8;">
  📞 Gestiona las solicitudes de atención de las mesas
</div>

<!-- Vista de escritorio -->
<div class="table-responsive" style="overflow-x: auto; margin-bottom: 1rem;">
  <table class="table" style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <thead style="background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%); color: white;">
      <tr>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">ID</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Mesa</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Ubicación</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Mozo Asignado</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Estado</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Hora</th>
        <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.9rem;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($llamados)): ?>
        <tr>
          <td colspan="7" style="padding: 2rem; text-align: center; color: #6c757d; font-style: italic; background: #f8f9fa;">
            No hay llamados pendientes para tus mesas.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($llamados as $llamado): ?>
          <tr style="border-bottom: 1px solid #e9ecef; transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='white'">
            <td style="padding: 0.75rem; font-weight: 600; color: #8B4513;">#<?= htmlspecialchars($llamado['id_llamado']) ?></td>
            <td style="padding: 0.75rem;">
              <strong style="color: #8B4513; font-size: 1rem;">🪑 Mesa <?= htmlspecialchars($llamado['numero_mesa']) ?></strong>
            </td>
            <td style="padding: 0.75rem; color: #6c757d;">
              <?= htmlspecialchars($llamado['ubicacion_mesa'] ?? '—') ?>
            </td>
            <td style="padding: 0.75rem;">
              <?php if (!empty($llamado['mozo_nombre_completo'])): ?>
                <span style="padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; 
                             background: #e2e3e5; color: #383d41; display: inline-block;">
                  👤 <?= htmlspecialchars($llamado['mozo_nombre_completo']) ?>
                </span>
              <?php else: ?>
                <span style="color: #dc3545; font-style: italic; font-size: 0.8rem;">⚠️ Sin asignar</span>
              <?php endif; ?>
            </td>
            <td style="padding: 0.75rem;">
              <span style="padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; 
                           background: <?= $llamado['estado'] === 'completado' ? '#d4edda' : '#f8d7da' ?>; 
                           color: <?= $llamado['estado'] === 'completado' ? '#155724' : '#721c24' ?>; 
                           border: 1px solid <?= $llamado['estado'] === 'completado' ? '#c3e6cb' : '#f5c6cb' ?>;">
                <?= $llamado['estado'] === 'completado' ? '✅ Completado' : '⏰ Pendiente' ?>
              </span>
            </td>
            <td style="padding: 0.75rem; font-weight: 600; color: #8B4513;">
              <?= date('H:i', strtotime($llamado['hora_solicitud'])) ?>
            </td>
            <td style="padding: 0.75rem; text-align: center;">
              <?php if ($llamado['estado'] === 'pendiente'): ?>
                <a href="?atender=<?= $llamado['id_llamado'] ?>" 
                   style="background: #28a745; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; 
                          text-decoration: none; font-weight: 600; font-size: 0.8rem; 
                          transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);"
                   onmouseover="this.style.backgroundColor='#218838'; this.style.transform='translateY(-1px)'"
                   onmouseout="this.style.backgroundColor='#28a745'; this.style.transform='translateY(0)'">
                  ✅ Atender
                </a>
              <?php else: ?>
                <span style="background: #6c757d; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; 
                             font-weight: 600; font-size: 0.8rem; cursor: not-allowed; opacity: 0.6; display: inline-block;">
                  ✅ Completado
                </span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Vista móvil -->
<div class="mobile-cards" style="display: none;">
  <?php if (empty($llamados)): ?>
    <div class="empty-state" style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px; color: #6c757d; font-style: italic;">
      No hay llamados pendientes para tus mesas.
    </div>
  <?php else: ?>
    <?php foreach ($llamados as $llamado): ?>
      <div class="llamado-card" style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; 
                                       box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid <?= $llamado['estado'] === 'completado' ? '#28a745' : '#ffc107' ?>;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.2rem;">🔔</span>
            <strong style="color: #8B4513; font-size: 1rem;">Llamado #<?= htmlspecialchars($llamado['id_llamado']) ?></strong>
          </div>
          <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: bold; 
                       background: <?= $llamado['estado'] === 'completado' ? '#d4edda' : '#f8d7da' ?>; 
                       color: <?= $llamado['estado'] === 'completado' ? '#155724' : '#721c24' ?>;">
            <?= $llamado['estado'] === 'completado' ? '✅ Completado' : '⏰ Pendiente' ?>
          </span>
        </div>
        
        <div class="card-content" style="margin-bottom: 1rem;">
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <span style="font-size: 1.1rem;">🪑</span>
            <strong style="color: #8B4513; font-size: 1.1rem;">Mesa <?= htmlspecialchars($llamado['numero_mesa']) ?></strong>
          </div>
          
          <?php if (!empty($llamado['ubicacion_mesa'])): ?>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; color: #6c757d; font-size: 0.9rem;">
              <span>📍</span>
              <span><?= htmlspecialchars($llamado['ubicacion_mesa']) ?></span>
            </div>
          <?php endif; ?>
          
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <span>👤</span>
            <?php if (!empty($llamado['mozo_nombre_completo'])): ?>
              <span style="color: #383d41; font-size: 0.9rem;"><?= htmlspecialchars($llamado['mozo_nombre_completo']) ?></span>
            <?php else: ?>
              <span style="color: #dc3545; font-style: italic; font-size: 0.9rem;">Sin asignar</span>
            <?php endif; ?>
          </div>
          
          <div style="display: flex; align-items: center; gap: 0.5rem; color: #8B4513; font-weight: 600;">
            <span>🕐</span>
            <span><?= date('H:i', strtotime($llamado['hora_solicitud'])) ?></span>
          </div>
        </div>
        
        <div class="card-actions" style="text-align: center;">
          <?php if ($llamado['estado'] === 'pendiente'): ?>
            <a href="?atender=<?= $llamado['id_llamado'] ?>" 
               style="background: #28a745; color: white; padding: 0.6rem 1.5rem; border-radius: 6px; 
                      text-decoration: none; font-weight: 600; font-size: 0.9rem; 
                      transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
                      width: 100%; text-align: center;">
              ✅ Atender Llamado
            </a>
          <?php else: ?>
            <span style="background: #6c757d; color: white; padding: 0.6rem 1.5rem; border-radius: 6px; 
                         font-weight: 600; font-size: 0.9rem; cursor: not-allowed; opacity: 0.6; 
                         display: inline-block; width: 100%; text-align: center;">
              ✅ Completado
            </span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<style>
/* Responsive Design */
@media (max-width: 768px) {
  .table-responsive {
    display: none !important;
  }
  
  .mobile-cards {
    display: block !important;
  }
  
  .info-banner {
    font-size: 0.8rem !important;
    padding: 0.6rem !important;
  }
}

@media (min-width: 769px) {
  .table-responsive {
    display: block !important;
  }
  
  .mobile-cards {
    display: none !important;
  }
}

/* Hover effects para desktop */
.table tbody tr:hover {
  background-color: #f8f9fa !important;
}

/* Animaciones */
.llamado-card {
  transition: all 0.3s ease;
}

.llamado-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

/* Estilos para botones */
.btn-action {
  transition: all 0.3s ease;
}

.btn-action:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
}
</style>
