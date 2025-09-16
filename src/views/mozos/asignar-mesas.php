<?php
// src/views/mozos/asignar-mesas.php - Vista compacta para asignación rápida de mesas
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Mesa;
use App\Models\Usuario;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo administradores pueden acceder
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . url('login'));
    exit;
}

$error = '';
$success = '';

// Procesar el formulario de asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    if ($accion === 'asignar_mesa_individual') {
        $mesa_id = (int) ($_POST['mesa_id'] ?? 0);
        $nuevo_mozo = !empty($_POST['nuevo_mozo']) ? (int) $_POST['nuevo_mozo'] : null;
        
        if ($mesa_id > 0) {
            if (Mesa::asignarMozo($mesa_id, $nuevo_mozo)) {
                $mesa = Mesa::find($mesa_id);
                $mozo_info = $nuevo_mozo ? Usuario::find($nuevo_mozo) : null;
                
                if ($mozo_info) {
                    $success = "Mesa {$mesa['numero']} asignada a {$mozo_info['nombre']} {$mozo_info['apellido']}.";
                } else {
                    $success = "Mesa {$mesa['numero']} liberada (sin mozo asignado).";
                }
            } else {
                $error = 'No se pudo actualizar la mesa.';
            }
        } else {
            $error = 'Debe seleccionar una mesa válida.';
        }
    }
    
    if ($accion === 'cambiar_mozo_masivo') {
        $mozo_origen = (int) ($_POST['mozo_origen'] ?? 0);
        $mozo_destino = !empty($_POST['mozo_destino']) ? (int) $_POST['mozo_destino'] : null;
        
        if ($mozo_origen > 0) {
            // Obtener todas las mesas del mozo origen
            $mesas_origen = Mesa::getMesasByMozo($mozo_origen);
            
            $mesas_actualizadas = 0;
            foreach ($mesas_origen as $mesa) {
                if (Mesa::asignarMozo($mesa['id_mesa'], $mozo_destino)) {
                    $mesas_actualizadas++;
                }
            }
            
            if ($mesas_actualizadas > 0) {
                $mozo_origen_info = Usuario::find($mozo_origen);
                $mozo_destino_info = $mozo_destino ? Usuario::find($mozo_destino) : null;
                
                if ($mozo_destino_info) {
                    $success = "Se cambiaron {$mesas_actualizadas} mesas de {$mozo_origen_info['nombre']} {$mozo_origen_info['apellido']} a {$mozo_destino_info['nombre']} {$mozo_destino_info['apellido']}.";
                } else {
                    $success = "Se liberaron {$mesas_actualizadas} mesas de {$mozo_origen_info['nombre']} {$mozo_origen_info['apellido']} (sin asignar).";
                }
            } else {
                $error = 'No se pudieron actualizar las mesas.';
            }
        } else {
            $error = 'Debe seleccionar un mozo de origen válido.';
        }
    }
}

// Obtener todos los mozos activos y mesas
$mozos = Usuario::getMozosActivos();
$mesas = Mesa::all();

// Agrupar mesas por mozo para mostrar asignaciones
$mesas_por_mozo = [];
$mesas_sin_asignar = [];

foreach ($mesas as $mesa) {
    if (!empty($mesa['id_mozo'])) {
        $mozo_key = $mesa['id_mozo'];
        if (!isset($mesas_por_mozo[$mozo_key])) {
            $mesas_por_mozo[$mozo_key] = [
                'mozo' => [
                    'id' => $mesa['id_mozo'],
                    'nombre' => $mesa['mozo_nombre_completo']
                ],
                'mesas' => []
            ];
        }
        $mesas_por_mozo[$mozo_key]['mesas'][] = $mesa;
    } else {
        $mesas_sin_asignar[] = $mesa;
    }
}
?>

<div class="page-header" style="text-align: center; margin-bottom: 1.5rem; padding: 1rem; background: linear-gradient(135deg, var(--secondary) 0%, #8b5e46 100%); border-radius: 8px; color: white;">
    <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">🎯 Asignación Rápida de Mesas</h2>
    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 0.9rem;">Gestionar asignaciones de mesas a mozos de forma eficiente</p>
</div>

<?php if ($error): ?>
    <div class="alert error" style="color: red; background: #ffe6e6; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert success" style="color: green; background: #e6ffe6; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem;">
        ✅ <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<!-- Asignación Individual -->
<div class="card" style="background: white; border: 2px solid #e9ecef; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    <div class="card-header" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
        <div style="background: var(--primary); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <span style="font-size: 1.5rem; color: white;">🎯</span>
        </div>
        <div>
            <h3 style="margin: 0; color: var(--secondary); font-size: 1.2rem; font-weight: 600;">Asignación Individual</h3>
            <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">Cambiar mozo de una mesa específica</p>
        </div>
    </div>
    
    <form method="post" class="form-compact" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
        <input type="hidden" name="accion" value="asignar_mesa_individual">
        
        <div class="form-group">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--secondary); font-size: 0.85rem;">🪑 Mesa:</label>
            <select name="mesa_id" required style="width: 100%; padding: 0.75rem; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; background: #f8f9fa; transition: all 0.2s ease;">
                <option value="">-- Seleccionar mesa --</option>
                <?php foreach ($mesas as $mesa): ?>
                    <option value="<?= $mesa['id_mesa'] ?>">
                        Mesa <?= $mesa['numero'] ?>
                        <?php if (!empty($mesa['ubicacion'])): ?>
                            (<?= htmlspecialchars($mesa['ubicacion']) ?>)
                        <?php endif; ?>
                        <?php if (!empty($mesa['mozo_nombre_completo'])): ?>
                            - Actual: <?= htmlspecialchars($mesa['mozo_nombre_completo']) ?>
                        <?php else: ?>
                            - Sin asignar
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--secondary); font-size: 0.85rem;">👤 Nuevo mozo:</label>
            <select name="nuevo_mozo" style="width: 100%; padding: 0.75rem; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; background: #f8f9fa; transition: all 0.2s ease;">
                <option value="">-- Sin asignar --</option>
                <?php foreach ($mozos as $mozo): ?>
                    <option value="<?= $mozo['id_usuario'] ?>">
                        <?= htmlspecialchars($mozo['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary" style="background: var(--secondary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; white-space: nowrap; font-size: 0.9rem; box-shadow: 0 2px 8px rgba(161, 134, 111, 0.3);">
            ✅ Asignar
        </button>
    </form>
</div>

<!-- Cambio Masivo -->
<div class="card emergency" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 1px solid #ffc107; border-radius: 6px; padding: 0.5rem; margin-bottom: 0.75rem; box-shadow: 0 1px 4px rgba(255, 193, 7, 0.2);">
    <div class="card-header" style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.5rem;">
        <div style="background: #ffc107; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
            <span style="font-size: 0.8rem;">🚑</span>
        </div>
        <div style="flex: 1;">
            <h3 style="margin: 0; color: #856404; font-size: 0.8rem; font-weight: 600;">Cambio Masivo</h3>
            <p style="margin: 0; color: #856404; font-size: 0.65rem; opacity: 0.8;">Reasignar todas las mesas</p>
        </div>
        <span class="badge" style="background: #ffc107; color: #212529; padding: 0.15rem 0.3rem; border-radius: 6px; font-size: 0.55rem; font-weight: bold;">EMERGENCIA</span>
    </div>
    
    <form method="post" class="form-compact" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.4rem; align-items: end;">
        <input type="hidden" name="accion" value="cambiar_mozo_masivo">
        
        <div class="form-group">
            <label style="display: block; margin-bottom: 0.15rem; font-weight: 600; color: #856404; font-size: 0.65rem;">👤 Origen:</label>
            <select name="mozo_origen" required style="width: 100%; padding: 0.3rem; border: 1px solid #e9ecef; border-radius: 3px; font-size: 0.7rem; background: #f8f9fa; transition: all 0.2s ease;">
                <option value="">-- Seleccionar --</option>
                <?php foreach ($mesas_por_mozo as $data): ?>
                    <option value="<?= $data['mozo']['id'] ?>">
                        <?= htmlspecialchars($data['mozo']['nombre']) ?> (<?= count($data['mesas']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label style="display: block; margin-bottom: 0.15rem; font-weight: 600; color: #856404; font-size: 0.65rem;">➡️ Destino:</label>
            <select name="mozo_destino" style="width: 100%; padding: 0.3rem; border: 1px solid #e9ecef; border-radius: 3px; font-size: 0.7rem; background: #f8f9fa; transition: all 0.2s ease;">
                <option value="">-- Sin asignar --</option>
                <?php foreach ($mozos as $mozo): ?>
                    <option value="<?= $mozo['id_usuario'] ?>">
                        <?= htmlspecialchars($mozo['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-warning" style="background: #ffc107; color: #212529; border: none; padding: 0.3rem 0.6rem; border-radius: 3px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; white-space: nowrap; font-size: 0.7rem; box-shadow: 0 1px 3px rgba(255, 193, 7, 0.3);">
            🔄 Transferir
        </button>
    </form>
</div>

<!-- Estado Actual Compacto -->
<div class="card" style="background: white; border: 2px solid #e9ecef; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    <div class="card-header" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
        <div style="background: var(--secondary); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <span style="font-size: 1.5rem; color: white;">📊</span>
        </div>
        <div>
            <h3 style="margin: 0; color: var(--secondary); font-size: 1.2rem; font-weight: 600;">Estado Actual</h3>
            <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">Distribución de mesas por mozo</p>
        </div>
    </div>
    
    <!-- Resumen compacto -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="stat-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: var(--secondary);"><?= count($mozos) ?></div>
            <div style="font-size: 0.8rem; color: #6c757d;">Personal Activo</div>
        </div>
        
        <div class="stat-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🪑</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: var(--secondary);"><?= count($mesas) ?></div>
            <div style="font-size: 0.8rem; color: #6c757d;">Total Mesas</div>
        </div>
        
        <div class="stat-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚠️</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: #ffc107;"><?= count($mesas_sin_asignar) ?></div>
            <div style="font-size: 0.8rem; color: #6c757d;">Sin Asignar</div>
        </div>
    </div>
    
    <!-- Lista compacta de asignaciones -->
    <div class="asignaciones-compactas">
        <?php foreach ($mesas_por_mozo as $data): ?>
            <div class="asignacion-item" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: var(--secondary); border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 1.2rem; color: white;">👤</span>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--secondary); font-size: 0.9rem;">
                            <?= htmlspecialchars($data['mozo']['nombre']) ?>
                        </div>
                        <div style="font-size: 0.8rem; color: #6c757d;">
                            <?= count($data['mesas']) ?> mesa(s) asignada(s)
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php foreach ($data['mesas'] as $mesa): ?>
                        <span style="background: white; border: 1px solid #dee2e6; border-radius: 6px; padding: 0.25rem 0.5rem; font-size: 0.75rem; color: var(--secondary); font-weight: 500;">
                            Mesa <?= $mesa['numero'] ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (!empty($mesas_sin_asignar)): ?>
            <div class="asignacion-item unassigned" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: #ffc107; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 1.2rem; color: #212529;">⚠️</span>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #856404; font-size: 0.9rem;">
                            Mesas Sin Asignar
                        </div>
                        <div style="font-size: 0.8rem; color: #856404; opacity: 0.8;">
                            Requieren asignación
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php foreach ($mesas_sin_asignar as $mesa): ?>
                        <span style="background: white; border: 1px solid #ffc107; border-radius: 6px; padding: 0.25rem 0.5rem; font-size: 0.75rem; color: #856404; font-weight: 500;">
                            Mesa <?= $mesa['numero'] ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div style="text-align: center; margin-top: 2rem;">
    <a href="<?= url('mozos') ?>" class="btn btn-secondary" style="background: #6c757d; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; display: inline-block; font-size: 0.9rem; box-shadow: 0 2px 8px rgba(108, 117, 125, 0.3);">
        ← Volver al Personal
    </a>
</div>

<style>
/* Estilos básicos para la asignación de mesas */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

select:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(161, 134, 111, 0.15);
}

/* Responsive design */
@media (max-width: 768px) {
    .form-compact {
        grid-template-columns: 1fr !important;
        gap: 0.75rem !important;
    }
    
    .stats-grid {
        grid-template-columns: 1fr !important;
    }
    
    .asignacion-item {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 1rem !important;
    }
}
</style>
