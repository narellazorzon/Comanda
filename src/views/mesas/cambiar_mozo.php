<?php
// src/views/mesas/cambiar_mozo.php - Vista para cambiar mozo asignado a mesas
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

// Obtener todos los mozos (activos e inactivos) para la asignación individual
// Para cambio masivo, solo mostramos mozos con mesas asignadas
$mozos_todos = Usuario::allByRole('mozo');
// Formatear para que tenga el mismo formato que getMozosActivos
$mozos = array_map(function($mozo) {
    return [
        'id_usuario' => $mozo['id_usuario'],
        'nombre' => $mozo['nombre'],
        'apellido' => $mozo['apellido'],
        'nombre_completo' => $mozo['nombre'] . ' ' . $mozo['apellido'],
        'estado' => $mozo['estado']
    ];
}, $mozos_todos);

// Para el cambio masivo, solo necesitamos mozos activos
$mozos_activos = Usuario::getMozosActivos();

// Procesar el formulario de cambio masivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
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
    
    if ($accion === 'desasignar_mesa') {
        $mesa_id = (int) ($_POST['mesa_id'] ?? 0);
        
        if ($mesa_id > 0) {
            if (Mesa::asignarMozo($mesa_id, null)) {
                $mesa = Mesa::find($mesa_id);
                $success = "Mesa {$mesa['numero']} desasignada exitosamente.";
            } else {
                $error = 'No se pudo desasignar la mesa.';
            }
        } else {
            $error = 'Debe seleccionar una mesa válida.';
        }
    }
    
    if ($accion === 'desasignar_todas_mesas') {
        $mozo_id = (int) ($_POST['mozo_id'] ?? 0);
        
        if ($mozo_id > 0) {
            $mesas_del_mozo = Mesa::getMesasByMozo($mozo_id);
            $mesas_desasignadas = 0;
            
            foreach ($mesas_del_mozo as $mesa) {
                if (Mesa::asignarMozo($mesa['id_mesa'], null)) {
                    $mesas_desasignadas++;
                }
            }
            
            if ($mesas_desasignadas > 0) {
                $mozo_info = Usuario::find($mozo_id);
                $success = "Se desasignaron {$mesas_desasignadas} mesa(s) de {$mozo_info['nombre']} {$mozo_info['apellido']}.";
            } else {
                $error = 'No se pudieron desasignar las mesas.';
            }
        } else {
            $error = 'Debe seleccionar un mozo válido.';
        }
    }
}

// Obtener todas las mesas con información de mozos
$mesas = Mesa::all();

// Agrupar mesas por mozo
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

<h2>Gestión del Personal</h2>

<?php if ($error): ?>
    <div class="alert error" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 6px; padding: 0.75rem; margin-bottom: 1rem; font-weight: 500; font-size: 0.85rem;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert success" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 6px; padding: 0.75rem; margin-bottom: 1rem; font-weight: 500; font-size: 0.85rem;">
        ✅ <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<!-- Asignación Individual -->
<div class="card" style="background: var(--surface); border: 2px solidrgb(169, 30, 30); border-radius: 8px; padding: 0.75rem; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-header" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
        <div style="background: var(--secondary); border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
            <span style="font-size: 1rem; color: white;">🎯</span>
        </div>
        <div>
            <h3 style="margin: 0; color: var(--secondary); font-size: 0.9rem; font-weight: 600;">Asignación Individual</h3>
            <p style="margin: 0.15rem 0 0 0; color: #666; font-size: 0.7rem;">Cambiar mozo de una mesa específica</p>
        </div>
    </div>
    
    <form method="post" id="formAsignacionIndividual" class="form-compact" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; align-items: end;">
        <input type="hidden" name="accion" value="asignar_mesa_individual">
        
        <div class="form-group">
            <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; color: var(--secondary); font-size: 0.8rem;">🪑 Mesa:</label>
            <select name="mesa_id" id="mesa_id" required class="form-select">
                <option value="">-- Seleccionar mesa --</option>
                <?php foreach ($mesas as $mesa): ?>
                    <option value="<?= $mesa['id_mesa'] ?>" data-numero="<?= $mesa['numero'] ?>" data-ubicacion="<?= htmlspecialchars($mesa['ubicacion'] ?? '') ?>" data-mozo-actual="<?= htmlspecialchars($mesa['mozo_nombre_completo'] ?? 'Sin asignar') ?>">
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
            <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; color: var(--secondary); font-size: 0.8rem;">👤 Nuevo mozo:</label>
            <select name="nuevo_mozo" id="nuevo_mozo" class="form-select">
                <option value="">-- Sin asignar --</option>
                <?php foreach ($mozos as $mozo): ?>
                    <option value="<?= $mozo['id_usuario'] ?>" data-nombre="<?= htmlspecialchars($mozo['nombre_completo']) ?>" <?= $mozo['estado'] === 'inactivo' ? 'style="color: #6c757d; font-style: italic;"' : '' ?>>
                        <?= htmlspecialchars($mozo['nombre_completo']) ?>
                        <?php if ($mozo['estado'] === 'inactivo'): ?>
                            (Inactivo)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary" style="background: var(--secondary); color: white; border: none; padding: 0.6rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; white-space: nowrap; font-size: 0.8rem; box-shadow: 0 1px 4px rgba(155, 114, 79, 0.94);">
            ✅ Asignar
        </button>
    </form>
</div>

<!-- Cambio Masivo de Mozo -->
<div class="card emergency" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 2px solid #ffc107; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);">
    <div class="card-header" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
        <div style="background: #ffc107; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
            <span style="font-size: 1.2rem;">🚑</span>
        </div>
        <div>
            <h3 style="margin: 0; color: #856404; font-size: 1rem; font-weight: 600;">Cambio Masivo</h3>
            <p style="margin: 0.25rem 0 0 0; color: #856404; font-size: 0.8rem; opacity: 0.8;">Reasignar todas las mesas de un mozo</p>
        </div>
        <span class="badge" style="background: #ffc107; color: #212529; padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: bold; margin-left: auto;">EMERGENCIA</span>
    </div>
    
    <form method="post" id="formCambioMasivo" class="form-compact" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; align-items: end;">
        <input type="hidden" name="accion" value="cambiar_mozo_masivo">
        
        <div class="form-group">
            <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; color: #856404; font-size: 0.8rem;">👤 Mozo que no puede trabajar:</label>
            <select name="mozo_origen" id="mozo_origen" required class="form-select">
                <option value="">-- Seleccionar mozo --</option>
                <?php foreach ($mesas_por_mozo as $data): ?>
                    <option value="<?= $data['mozo']['id'] ?>" data-nombre="<?= htmlspecialchars($data['mozo']['nombre']) ?>" data-mesas="<?= count($data['mesas']) ?>">
                        <?= htmlspecialchars($data['mozo']['nombre']) ?> (<?= count($data['mesas']) ?> mesas)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; color: #856404; font-size: 0.8rem;">➡️ Reasignar a:</label>
            <select name="mozo_destino" id="mozo_destino" class="form-select">
                <option value="">-- Sin asignar --</option>
                <?php foreach ($mozos_activos as $mozo): ?>
                    <option value="<?= $mozo['id_usuario'] ?>" data-nombre="<?= htmlspecialchars($mozo['nombre_completo']) ?>">
                        <?= htmlspecialchars($mozo['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-warning" style="background: #ffc107; color: #212529; border: none; padding: 0.6rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; white-space: nowrap; font-size: 0.8rem; box-shadow: 0 1px 4px rgba(255, 193, 7, 0.3);">
            🔄 Transferir
        </button>
    </form>
</div>

<!-- Estado Actual de Asignaciones -->
<div class="card" style="background: var(--surface); border: 2px solid #e0e0e0; border-radius: 8px; padding: 0.75rem; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-header" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
        <div style="background: var(--secondary); border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
            <span style="font-size: 1rem; color: white;">📊</span>
        </div>
        <div>
            <h3 style="margin: 0; color: var(--secondary); font-size: 0.9rem; font-weight: 600;">Estado Actual</h3>
            <p style="margin: 0.15rem 0 0 0; color: #666; font-size: 0.7rem;">Distribución de mesas por mozo</p>
        </div>
    </div>
    
    <!-- Mesas por Mozo -->
    <?php foreach ($mesas_por_mozo as $data): ?>
        <div class="mozo-group" style="background: linear-gradient(135deg, rgb(237, 230, 215) 0%, rgb(245, 238, 220) 100%); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; border: 1px solid #d4c4a8; box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
            <div class="mozo-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: var(--secondary); border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 1rem; color: white;">👤</span>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--secondary); font-size: 0.9rem; font-weight: 600;">
                            <?= htmlspecialchars($data['mozo']['nombre']) ?>
                        </h4>
                        <p style="margin: 0.15rem 0 0 0; color: #666; font-size: 0.7rem;">Mozo asignado</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="badge" style="background: var(--secondary); color: white; padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: bold; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                        <?= count($data['mesas']) ?> mesas
                    </span>
                    <form method="post" style="display: inline; margin: 0;" onsubmit="return confirm('¿Desasignar todas las mesas de <?= htmlspecialchars($data['mozo']['nombre']) ?>?');">
                        <input type="hidden" name="accion" value="desasignar_todas_mesas">
                        <input type="hidden" name="mozo_id" value="<?= $data['mozo']['id'] ?>">
                        <button type="submit" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 5px; font-size: 0.65rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.3rem; box-shadow: 0 1px 3px rgba(220, 53, 69, 0.3); line-height: 1.2;" 
                                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 5px rgba(220, 53, 69, 0.4)';" 
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(220, 53, 69, 0.3)';" 
                                title="Desasignar todas las mesas de este mozo">
                            <span>🔓</span>
                            <span>Todas</span>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="mesas-scroll-container" style="max-height: 280px; overflow-y: auto; overflow-x: hidden; padding-right: 8px; margin-top: 0.5rem;">
                <div class="mesas-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.6rem; padding-bottom: 0.5rem;">
                    <?php foreach ($data['mesas'] as $mesa): ?>
                        <div class="mesa-card" style="background: rgb(245, 244, 241); border: 1px solid #d4c4a8; border-radius: 6px; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.3rem; transition: all 0.3s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.05); min-width: 120px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.4rem;">
                                <strong style="color: var(--secondary); font-size: 0.85rem; font-weight: 600;">🪑 <?= $mesa['numero'] ?></strong>
                                <span class="estado-badge" style="padding: 0.15rem 0.35rem; border-radius: 6px; font-size: 0.65rem; font-weight: bold; 
                                             background: <?= $mesa['estado'] === 'libre' ? '#d4edda' : ($mesa['estado'] === 'ocupada' ? '#f8d7da' : '#fff3cd') ?>; 
                                             color: <?= $mesa['estado'] === 'libre' ? '#155724' : ($mesa['estado'] === 'ocupada' ? '#721c24' : '#856404') ?>; 
                                             border: 1px solid <?= $mesa['estado'] === 'libre' ? '#c3e6cb' : ($mesa['estado'] === 'ocupada' ? '#f5c6cb' : '#ffeaa7') ?>;">
                                    <?= $mesa['estado'] === 'libre' ? '🟢' : ($mesa['estado'] === 'ocupada' ? '🔴' : '🟡') ?>
                                </span>
                            </div>
                            <?php if (!empty($mesa['ubicacion'])): ?>
                                <div style="color: #6c757d; font-size: 0.65rem; display: flex; align-items: center; gap: 0.25rem; line-height: 1.2;">
                                    <span>📍</span>
                                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($mesa['ubicacion']) ?></span>
                                </div>
                            <?php endif; ?>
                            <form method="post" style="display: inline; margin: 0; margin-top: 0.2rem;" onsubmit="return confirm('¿Desasignar la Mesa <?= $mesa['numero'] ?>?');">
                                <input type="hidden" name="accion" value="desasignar_mesa">
                                <input type="hidden" name="mesa_id" value="<?= $mesa['id_mesa'] ?>">
                                <button type="submit" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.55rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.25rem; box-shadow: 0 1px 2px rgba(220, 53, 69, 0.25); line-height: 1.2;" 
                                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 4px rgba(220, 53, 69, 0.35)';" 
                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px rgba(220, 53, 69, 0.25)';" 
                                        title="Desasignar esta mesa">
                                    <span>🔓</span>
                                    <span>Desasignar</span>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Mesas Sin Asignar -->
    <?php if (!empty($mesas_sin_asignar)): ?>
        <div class="mozo-group unassigned" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border-radius: 8px; padding: 1rem; border: 1px solid #ffc107; box-shadow: 0 1px 4px rgba(255, 193, 7, 0.2);">
            <div class="mozo-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: #ffc107; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 1rem; color: #212529;">⚠️</span>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: #856404; font-size: 0.9rem; font-weight: 600;">
                            Mesas Sin Asignar
                        </h4>
                        <p style="margin: 0.15rem 0 0 0; color: #856404; font-size: 0.7rem; opacity: 0.8;">Requieren asignación</p>
                    </div>
                </div>
                <span class="badge" style="background: #ffc107; color: #212529; padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: bold; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                    <?= count($mesas_sin_asignar) ?> mesas
                </span>
            </div>
            
            <div class="mesas-scroll-container" style="max-height: 280px; overflow-y: auto; overflow-x: hidden; padding-right: 8px; margin-top: 0.5rem;">
                <div class="mesas-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.6rem; padding-bottom: 0.5rem;">
                    <?php foreach ($mesas_sin_asignar as $mesa): ?>
                        <div class="mesa-card" style="background: rgb(247, 243, 238); border: 1px solid #ffc107; border-radius: 6px; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.3rem; transition: all 0.3s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.05); min-width: 120px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.4rem;">
                                <strong style="color: var(--secondary); font-size: 0.85rem; font-weight: 600;">🪑 <?= $mesa['numero'] ?></strong>
                                <span class="estado-badge" style="padding: 0.15rem 0.35rem; border-radius: 6px; font-size: 0.65rem; font-weight: bold; 
                                             background: <?= $mesa['estado'] === 'libre' ? '#d4edda' : ($mesa['estado'] === 'ocupada' ? '#f8d7da' : '#fff3cd') ?>; 
                                             color: <?= $mesa['estado'] === 'libre' ? '#155724' : ($mesa['estado'] === 'ocupada' ? '#721c24' : '#856404') ?>; 
                                             border: 1px solid <?= $mesa['estado'] === 'libre' ? '#c3e6cb' : ($mesa['estado'] === 'ocupada' ? '#f5c6cb' : '#ffeaa7') ?>;">
                                    <?= $mesa['estado'] === 'libre' ? '🟢' : ($mesa['estado'] === 'ocupada' ? '🔴' : '🟡') ?>
                                </span>
                            </div>
                            <?php if (!empty($mesa['ubicacion'])): ?>
                                <div style="color: #6c757d; font-size: 0.65rem; display: flex; align-items: center; gap: 0.25rem; line-height: 1.2;">
                                    <span>📍</span>
                                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($mesa['ubicacion']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div style="text-align: center; margin-top: 1rem;">
    <a href="<?= url('mesas') ?>" class="btn btn-secondary" style="background:rgb(124, 92, 70); color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.3s ease; display: inline-block; font-size: 0.8rem; box-shadow: 0 1px 4px rgba(127, 100, 82, 0.96);">
        ← Volver a Mesas
    </a>
</div>

<style>
/* Estilos globales para la página */
.form-select {
    width: 100%;
    padding: 0.6rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.form-select:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(161, 134, 111, 0.15);
    transform: translateY(-1px);
}

.btn {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.btn:active {
    transform: translateY(-1px);
}

.btn-warning:hover {
    background: #e0a800 !important;
    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
}

.btn-primary:hover {
    background: #8a6f5a !important;
    box-shadow: 0 6px 20px rgba(161, 134, 111, 0.4);
}

.btn-secondary:hover {
    background: #654321 !important;
    box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
}

.mesa-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    border-color: var(--secondary);
}

.mozo-group:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Animaciones de entrada */
.mozo-group {
    animation: slideInUp 0.6s ease forwards;
    opacity: 0;
    transform: translateY(20px);
}

.mozo-group:nth-child(1) { animation-delay: 0.1s; }
.mozo-group:nth-child(2) { animation-delay: 0.2s; }
.mozo-group:nth-child(3) { animation-delay: 0.3s; }
.mozo-group:nth-child(4) { animation-delay: 0.4s; }
.mozo-group:nth-child(5) { animation-delay: 0.5s; }

@keyframes slideInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Efectos de hover mejorados */
.estado-badge {
    transition: all 0.3s ease;
}

.mesa-card:hover .estado-badge {
    transform: scale(1.05);
}

/* Estilos para scroll de mesas */
.mesas-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: rgba(155, 114, 79, 0.5) transparent;
}

.mesas-scroll-container::-webkit-scrollbar {
    width: 8px;
}

.mesas-scroll-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.mesas-scroll-container::-webkit-scrollbar-thumb {
    background: rgba(155, 114, 79, 0.5);
    border-radius: 10px;
    transition: background 0.3s ease;
}

.mesas-scroll-container::-webkit-scrollbar-thumb:hover {
    background: rgba(155, 114, 79, 0.7);
}

/* Mejoras para grupos de mozo */
.mozo-group {
    max-width: 100%;
    overflow: hidden;
}

.mozo-group .mozo-header {
    position: sticky;
    top: 0;
    background: linear-gradient(135deg, rgb(239, 231, 213) 0%, rgb(245, 238, 220) 100%);
    z-index: 10;
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
}

.mozo-group.unassigned .mozo-header {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
}

/* Responsive mejorado */
@media (max-width: 768px) {
    .form-compact {
        grid-template-columns: 1fr !important;
        gap: 1.5rem !important;
    }
    
    .mesas-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    
    .card {
        padding: 1rem !important;
        margin-bottom: 1.5rem !important;
    }
    
    .mozo-group {
        padding: 1rem !important;
    }
    
    .mesa-card {
        padding: 1rem !important;
    }
    
    .page-header {
        padding: 1rem !important;
        margin-bottom: 1.5rem !important;
    }
    
    .page-header h2 {
        font-size: 1.5rem !important;
    }
}

@media (max-width: 480px) {
    .mesas-grid {
        grid-template-columns: 1fr !important;
    }
    
    .mozo-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
    }
    
    .badge {
        align-self: flex-start !important;
    }
}
</style>
