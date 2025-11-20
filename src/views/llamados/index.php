<?php
// src/views/llamados/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Models\LlamadoMesa;

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Personal y administradores pueden acceder
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
    $id_llamado = (int) $_GET['atender'];
    if (LlamadoMesa::updateEstado($id_llamado, 'atendido')) {
        header('Location: index.php?route=llamados&atendido=1');
        exit;
    } else {
        header('Location: index.php?route=llamados&error=2');
        exit;
    }
}

// Verificar si se acaba de atender un llamado
$mensaje_atendido = isset($_GET['atendido']) && $_GET['atendido'] == '1';
$mensaje_error = isset($_GET['error']) ? $_GET['error'] : null;

// Obtener filtro de estado
$estado = $_GET['estado'] ?? 'pendiente';

// Si es un mozo, solo mostrar sus llamados; si es admin, mostrar todos
$user_id = $_SESSION['user']['id_usuario'];
$user_rol = $_SESSION['user']['rol'];
$rol = $user_rol;

if ($user_rol === 'mozo') {
  // Los mozos pueden ver y filtrar solo sus propios llamados
  $llamados = LlamadoMesa::getByMozo($user_id, $estado);
} else {
  // El administrador puede ver todos los llamados
  $llamados = LlamadoMesa::getByEstado($estado);
}

?>

<style>
/* Estilos para el header de gestión */
.management-header {
  background: linear-gradient(135deg, rgb(144, 104, 76), rgb(92, 64, 51));
  color: white !important;
  padding: 1rem 1.5rem;
  margin-bottom: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.management-header * {
  color: white !important;
}

.management-header h1 {
  margin: 0 !important;
  font-size: 1.4rem !important;
  font-weight: 600 !important;
  color: white !important;
  flex: 1;
  min-width: 200px;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.header-btn {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  color: white !important;
  text-decoration: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  border: 1px solid rgba(255, 255, 255, 0.3);
  white-space: nowrap;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.header-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  color: white !important;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.header-btn.secondary {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.header-btn.secondary:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Responsive para móvil */
@media (max-width: 992px) {
  .management-header {
    padding: 10px;
    margin-bottom: 10px;
  }
  
  .management-header h1 {
    font-size: 1.1rem !important;
  }
  
  .header-btn {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
  }
}

@media (max-width: 768px) {
  .management-header {
    padding: 8px;
    margin-bottom: 8px;
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }
  
  .management-header h1 {
    font-size: 0.9rem !important;
    text-align: center;
    margin-bottom: 0.5rem;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .header-btn {
    font-size: 0.7rem;
    padding: 0.3rem 0.6rem;
  }
}

@media (max-width: 480px) {
  .management-header {
    padding: 6px;
    margin-bottom: 6px;
  }
  
  .management-header h1 {
    font-size: 0.8rem !important;
  }
  
  .header-btn {
    font-size: 0.65rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>

<!-- Header de gestión -->
<div class="management-header">
  <h1><?= $rol === 'administrador' ? '🔔 Llamados de Mesas' : 'Llamados de Mesas' ?></h1>
  <div class="header-actions">
    <!-- Aquí se pueden agregar botones de acción si es necesario -->
  </div>
</div>

<!-- Panel de filtros -->
<div style="margin-bottom: 1.5rem;">
  <form method="GET" action="index.php" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
    <input type="hidden" name="route" value="llamados">
    <label for="estado-filtro" style="font-weight: 600; color: #495057; margin: 0;">Filtrar por estado:</label>
    <select id="estado-filtro" name="estado" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; background: white; font-size: 0.9rem; min-width: 150px;">
      <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>🟡 Pendientes</option>
      <option value="atendido" <?= $estado === 'atendido' ? 'selected' : '' ?>>🟢 Atendidos</option>
      <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>📋 Todos</option>
    </select>
    <span style="color: #6c757d; font-size: 0.85rem;">
      Mostrando: <strong><?= count($llamados) ?></strong> llamados
    </span>
  </form>
</div>

<?php if ($mensaje_atendido): ?>
<div style="background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #155724; border: 1px solid #c3e6cb;">
  ✅ Llamado atendido exitosamente
</div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
<div style="background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #721c24; border: 1px solid #f5c6cb;">
  ❌ Error al atender el llamado
</div>
<?php endif; ?>


<!-- Vista de escritorio -->
<div class="table-responsive" style="overflow-x: auto; margin-bottom: 1rem;">
  <table class="table" style="width: 100%; border-collapse: collapse; background: #f0e6c7; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <thead style="background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%); color: white;">
      <tr>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">ID</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Mesa</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Ubicación</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Mozo Asignado</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Estado</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Hora</th>
        <?php if ($estado === 'atendido' || $estado === 'todos'): ?>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Atendido por</th>
        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.9rem;">Hora Atención</th>
        <?php endif; ?>
        <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.9rem;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($llamados)): ?>
        <tr>
          <td colspan="<?= ($estado === 'atendido' || $estado === 'todos') ? '9' : '7' ?>" style="padding: 2rem; text-align: center; color: #6c757d; font-style: italic; background: #f0e6c7;">
            <?php if ($estado === 'pendiente'): ?>
              No hay llamados pendientes para tus mesas.
            <?php elseif ($estado === 'atendido'): ?>
              No hay llamados atendidos.
            <?php else: ?>
              No hay llamados registrados.
            <?php endif; ?>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($llamados as $llamado): ?>
          <tr style="border-bottom: 1px solid rgb(203, 193, 177); transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#e8d5b7'" onmouseout="this.style.backgroundColor='#f0e6c7'">
            <td style="padding: 0.75rem; font-weight: 600; color: #8B4513;">#<?= htmlspecialchars($llamado['id_llamado']) ?></td>
            <td style="padding: 0.75rem;">
              <strong style="color: #8B4513; font-size: 1rem;">🪑 Mesa <?= htmlspecialchars($llamado['numero_mesa']) ?></strong>
            </td>
            <td style="padding: 0.75rem; color: #6c757d;">
              <?= htmlspecialchars($llamado['ubicacion_mesa'] ?? '—') ?>
            </td>
            <td style="padding: 0.75rem;">
              <?php if (!empty($llamado['mozo_nombre_completo'])): ?>
                <span style="color: #383d41; font-weight: 500;"><?= htmlspecialchars($llamado['mozo_nombre_completo']) ?></span>
              <?php else: ?>
                <span style="color: #dc3545; font-style: italic; font-size: 0.9rem;">⚠️ Sin asignar</span>
              <?php endif; ?>
            </td>
            <td style="padding: 0.75rem;">
              <span style="padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; 
                           background: <?= $llamado['estado'] === 'atendido' ? '#d4edda' : '#f8d7da' ?>; 
                           color: <?= $llamado['estado'] === 'atendido' ? '#155724' : '#721c24' ?>; 
                           border: 1px solid <?= $llamado['estado'] === 'atendido' ? '#c3e6cb' : '#f5c6cb' ?>;">
                <?= $llamado['estado'] === 'atendido' ? '✅ Atendido' : '⏰ Pendiente' ?>
              </span>
            </td>
            <td style="padding: 0.75rem; font-weight: 600; color: #8B4513;">
              <?= date('H:i', strtotime($llamado['hora_solicitud'])) ?>
            </td>
            <?php if ($estado === 'atendido' || $estado === 'todos'): ?>
            <td style="padding: 0.75rem; color: #6c757d;">
              <?= isset($llamado['atendido_por_completo']) && $llamado['atendido_por_completo'] ? htmlspecialchars($llamado['atendido_por_completo']) : '—' ?>
            </td>
            <td style="padding: 0.75rem; color: #6c757d;">
              <?= isset($llamado['hora_atencion']) && $llamado['hora_atencion'] ? date('H:i', strtotime($llamado['hora_atencion'])) : '—' ?>
            </td>
            <?php endif; ?>
            <td style="padding: 0.75rem; text-align: center;">
              <?php if ($llamado['estado'] === 'pendiente'): ?>
                <a href="index.php?route=llamados&atender=<?= $llamado['id_llamado'] ?>" 
                   style="background: #28a745; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; 
                          text-decoration: none; font-weight: 600; font-size: 0.8rem; 
                          transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);"
                   onmouseover="this.style.backgroundColor='#218838'; this.style.transform='translateY(-1px)'"
                   onmouseout="this.style.backgroundColor='#28a745'; this.style.transform='translateY(0)'">
                  ✅ Atender
                </a>
              <?php elseif ($llamado['estado'] === 'atendido'): ?>
                <span style="background: #d4edda; color: #155724; padding: 0.4rem 0.8rem; border-radius: 6px; 
                             font-weight: 600; font-size: 0.8rem; display: inline-block; border: 1px solid #c3e6cb;">
                  ✅ Atendido
                </span>
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
<div class="mobile-view" style="display: none;">
  <?php if (empty($llamados)): ?>
    <div class="empty-state" style="text-align: center; padding: 2rem; background: #f0e6c7; border-radius: 8px; color: #6c757d; font-style: italic;">
      No hay llamados pendientes para tus mesas.
    </div>
  <?php else: ?>
    <?php foreach ($llamados as $llamado): ?>
      <div class="llamado-card" style="background: #f0e6c7; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; 
                                       box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid <?= $llamado['estado'] === 'atendido' ? '#28a745' : '#ffc107' ?>;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.2rem;">🔔</span>
            <strong style="color: #8B4513; font-size: 1rem;">Llamado #<?= htmlspecialchars($llamado['id_llamado']) ?></strong>
          </div>
          <span style="padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: bold; 
                       background: <?= $llamado['estado'] === 'atendido' ? '#d4edda' : '#f8d7da' ?>; 
                       color: <?= $llamado['estado'] === 'atendido' ? '#155724' : '#721c24' ?>;">
            <?= $llamado['estado'] === 'atendido' ? '✅ Atendido' : '⏰ Pendiente' ?>
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
          
          <?php if (($estado === 'atendido' || $estado === 'todos') && $llamado['estado'] === 'atendido'): ?>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; color: #6c757d; font-size: 0.9rem;">
              <span>👤</span>
              <span>Atendido por: <?= isset($llamado['atendido_por_completo']) && $llamado['atendido_por_completo'] ? htmlspecialchars($llamado['atendido_por_completo']) : '—' ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; color: #6c757d; font-size: 0.9rem;">
              <span>🕐</span>
              <span>Hora atención: <?= isset($llamado['hora_atencion']) && $llamado['hora_atencion'] ? date('H:i', strtotime($llamado['hora_atencion'])) : '—' ?></span>
            </div>
          <?php endif; ?>
        </div>
        
        <div class="card-actions" style="text-align: center;">
          <?php if ($llamado['estado'] === 'pendiente'): ?>
            <a href="index.php?route=llamados&atender=<?= $llamado['id_llamado'] ?>" 
               style="background: #28a745; color: white; padding: 0.6rem 1.5rem; border-radius: 6px; 
                      text-decoration: none; font-weight: 600; font-size: 0.9rem; 
                      transition: all 0.3s ease; display: inline-block; box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
                      width: 100%; text-align: center;">
              ✅ Atender Llamado
            </a>
          <?php elseif ($llamado['estado'] === 'atendido'): ?>
            <span style="background: #d4edda; color: #155724; padding: 0.6rem 1.5rem; border-radius: 6px; 
                         font-weight: 600; font-size: 0.9rem; display: inline-block; width: 100%; text-align: center;
                         border: 1px solid #c3e6cb;">
              ✅ Atendido
            </span>
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
/* Control de ancho de columnas */
.table th:nth-child(1),
.table td:nth-child(1) {
  width: 8%; 
}

.table th:nth-child(5),
.table td:nth-child(5) {
  width: 15%;
}

@media (max-width: 768px) {
  .table-responsive { display: none; }
  .mobile-view { display: block !important; }
}
</style>
