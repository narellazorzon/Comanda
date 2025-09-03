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

// Obtener todos los mozos activos
$mozos = Usuario::getMozosActivos();

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

<h2>🔄 Gestión de Asignación de Mozos</h2>

<div style="background: #d1ecf1; padding: 15px; border-radius: 8px; margin-bottom: 2rem; color: #0c5460;">
    <h4 style="margin: 0 0 10px 0;">ℹ️ Funcionalidades Disponibles</h4>
    <ul style="margin: 0; padding-left: 20px;">
        <li><strong>Cambio Masivo:</strong> Transferir todas las mesas de un mozo a otro (útil en caso de enfermedad)</li>
        <li><strong>Asignación Individual:</strong> Cambiar el mozo de una mesa específica</li>
        <li><strong>Liberación:</strong> Quitar la asignación de mozo de una mesa</li>
    </ul>
</div>

<?php if ($error): ?>
    <div class="error" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<!-- Cambio Masivo de Mozo -->
<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin-bottom: 2rem;">
    <h3 style="margin: 0 0 15px 0; color: #856404;">🚑 Cambio Masivo (Emergencia)</h3>
    <p style="margin: 0 0 15px 0; color: #856404;">
        <em>Usar cuando un mozo no puede trabajar y necesitas reasignar todas sus mesas.</em>
    </p>
    
    <form method="post" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
        <input type="hidden" name="accion" value="cambiar_mozo_masivo">
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Mozo que no puede trabajar:</label>
            <select name="mozo_origen" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-width: 200px;">
                <option value="">-- Seleccionar mozo --</option>
                <?php foreach ($mesas_por_mozo as $data): ?>
                    <option value="<?= $data['mozo']['id'] ?>">
                        <?= htmlspecialchars($data['mozo']['nombre']) ?> (<?= count($data['mesas']) ?> mesas)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Reasignar a:</label>
            <select name="mozo_destino" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-width: 200px;">
                <option value="">-- Sin asignar --</option>
                <?php foreach ($mozos as $mozo): ?>
                    <option value="<?= $mozo['id_usuario'] ?>">
                        <?= htmlspecialchars($mozo['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="button" style="background: #ffc107; color: #212529;">
            Transferir Mesas
        </button>
    </form>
</div>

<!-- Estado Actual de Asignaciones -->
<div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 2rem;">
    <h3 style="margin: 0 0 20px 0;">📊 Estado Actual de Asignaciones</h3>
    
    <!-- Mesas por Mozo -->
    <?php foreach ($mesas_por_mozo as $data): ?>
        <div style="background: #f8f9fa; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
            <h4 style="margin: 0 0 10px 0; color: #495057;">
                👤 <?= htmlspecialchars($data['mozo']['nombre']) ?>
                <span style="background: #6c757d; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; margin-left: 10px;">
                    <?= count($data['mesas']) ?> mesas
                </span>
            </h4>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php foreach ($data['mesas'] as $mesa): ?>
                    <div style="background: white; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px 12px; display: flex; align-items: center; gap: 8px;">
                        <strong>Mesa <?= $mesa['numero'] ?></strong>
                        <?php if (!empty($mesa['ubicacion'])): ?>
                            <span style="color: #6c757d; font-size: 0.9em;">(<?= htmlspecialchars($mesa['ubicacion']) ?>)</span>
                        <?php endif; ?>
                        <span style="padding: 2px 6px; border-radius: 8px; font-size: 0.7em; 
                                     background: <?= $mesa['estado'] === 'libre' ? '#d4edda' : '#f8d7da' ?>; 
                                     color: <?= $mesa['estado'] === 'libre' ? '#155724' : '#721c24' ?>;">
                            <?= ucfirst($mesa['estado']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Mesas Sin Asignar -->
    <?php if (!empty($mesas_sin_asignar)): ?>
        <div style="background: #fff3cd; border-radius: 6px; padding: 15px;">
            <h4 style="margin: 0 0 10px 0; color: #856404;">
                ⚠️ Mesas Sin Asignar
                <span style="background: #ffc107; color: #212529; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; margin-left: 10px;">
                    <?= count($mesas_sin_asignar) ?> mesas
                </span>
            </h4>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php foreach ($mesas_sin_asignar as $mesa): ?>
                    <div style="background: white; border: 1px solid #ffc107; border-radius: 4px; padding: 8px 12px; display: flex; align-items: center; gap: 8px;">
                        <strong>Mesa <?= $mesa['numero'] ?></strong>
                        <?php if (!empty($mesa['ubicacion'])): ?>
                            <span style="color: #6c757d; font-size: 0.9em;">(<?= htmlspecialchars($mesa['ubicacion']) ?>)</span>
                        <?php endif; ?>
                        <span style="padding: 2px 6px; border-radius: 8px; font-size: 0.7em; 
                                     background: <?= $mesa['estado'] === 'libre' ? '#d4edda' : '#f8d7da' ?>; 
                                     color: <?= $mesa['estado'] === 'libre' ? '#155724' : '#721c24' ?>;">
                            <?= ucfirst($mesa['estado']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Asignación Individual -->
<div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px;">
    <h3 style="margin: 0 0 15px 0;">🎯 Asignación Individual</h3>
    
    <form method="post" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
        <input type="hidden" name="accion" value="asignar_mesa_individual">
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Mesa:</label>
            <select name="mesa_id" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-width: 150px;">
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
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nuevo mozo:</label>
            <select name="nuevo_mozo" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-width: 200px;">
                <option value="">-- Sin asignar --</option>
                <?php foreach ($mozos as $mozo): ?>
                    <option value="<?= $mozo['id_usuario'] ?>">
                        <?= htmlspecialchars($mozo['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="button" style="background: #007bff;">
            Asignar
        </button>
    </form>
</div>

<div style="margin-top: 2rem; text-align: center;">
    <a href="<?= url('mesas') ?>" class="button" style="background: #6c757d;">
        Volver a Mesas
    </a>
</div>
