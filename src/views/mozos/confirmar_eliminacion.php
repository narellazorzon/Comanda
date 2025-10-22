<?php
// src/views/mozos/confirmar_eliminacion.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Usuario;
use App\Models\Mesa;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo administradores pueden acceder
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . url('login'));
    exit;
}

// Verificar parámetros requeridos
$id_mozo = (int) ($_GET['id_mozo'] ?? 0);
$mesas_asignadas = (int) ($_GET['mesas_asignadas'] ?? 0);

if ($id_mozo <= 0) {
    header('Location: ' . url('mozos', ['error' => 'Parámetros inválidos']));
    exit;
}

// Obtener información del mozo
$mozo = Usuario::find($id_mozo);
if (!$mozo || !in_array($mozo['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('mozos', ['error' => 'Usuario no encontrado']));
    exit;
}

// Obtener mesas asignadas al mozo
$mesas_del_mozo = Mesa::getMesasByMozo($id_mozo);

// Obtener otros mozos activos para reasignación
$mozos_activos = Usuario::getMozosActivos();
// Filtrar el mozo que se está eliminando
$mozos_activos = array_filter($mozos_activos, function($m) use ($id_mozo) {
    return $m['id_usuario'] != $id_mozo;
});

?>

<h2>🗑️ Confirmar Eliminación del Personal</h2>

<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 20px; margin-bottom: 2rem;">
    <h3 style="margin: 0 0 15px 0; color: #721c24;">⚠️ ATENCIÓN: Eliminación del Personal</h3>
    <p style="margin: 0 0 15px 0; color: #721c24; font-size: 1.1em;">
        <strong><?= htmlspecialchars($mozo['nombre'] . ' ' . $mozo['apellido']) ?></strong> 
        tiene <strong><?= $mesas_asignadas ?> mesa(s)</strong> asignada(s).
    </p>
    <p style="margin: 0; color: #721c24;">
        <strong>El mozo será marcado como eliminado</strong> (borrado lógico) y no podrá acceder al sistema. Antes de eliminar este mozo, debes decidir qué hacer con sus mesas asignadas.
    </p>
</div>

<!-- Mostrar mesas asignadas -->
<div style="background: #f8f9fa; border-radius: 6px; padding: 15px; margin-bottom: 20px;">
    <h4 style="margin: 0 0 15px 0; color: #495057;">📍 Mesas Actualmente Asignadas</h4>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php foreach ($mesas_del_mozo as $mesa): ?>
            <div style="background: white; border: 2px solid #dc3545; border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; gap: 8px;">
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

<!-- Formulario de confirmación -->
<form method="post" action="<?= url('mozos/procesar-eliminacion') ?>" style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 25px;">
    <!-- Datos del mozo (hidden) -->
    <input type="hidden" name="id_mozo" value="<?= $id_mozo ?>">
    <input type="hidden" name="nombre" value="<?= htmlspecialchars($_GET['nombre'] ?? $mozo['nombre']) ?>">
    <input type="hidden" name="apellido" value="<?= htmlspecialchars($_GET['apellido'] ?? $mozo['apellido']) ?>">
    <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? $mozo['email']) ?>">
    
    <h3 style="margin: 0 0 20px 0; color: #495057;">🎯 ¿Qué hacer con las mesas asignadas?</h3>
    
    <div style="margin-bottom: 25px;">
        <label style="display: flex; align-items: center; margin-bottom: 15px; cursor: pointer; padding: 15px; border: 2px solid #dee2e6; border-radius: 8px; transition: all 0.3s;" 
               onmouseover="this.style.backgroundColor='#f8f9fa'" 
               onmouseout="this.style.backgroundColor='white'">
            <input type="radio" name="accion_mesas" value="reasignar" required style="margin-right: 12px; transform: scale(1.2);">
            <div>
                <strong style="color: #007bff; font-size: 1.1em;">🔄 Reasignar a otro mozo</strong>
                <div style="color: #6c757d; font-size: 0.9em; margin-top: 5px;">
                    Transferir todas las mesas a un mozo activo disponible
                </div>
            </div>
        </label>
        
        <div id="selector-mozo" style="margin-left: 30px; margin-bottom: 15px; display: none;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #495057;">Seleccionar nuevo mozo:</label>
            <select name="nuevo_mozo" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;">
                <option value="">-- Seleccionar mozo --</option>
                <?php foreach ($mozos_activos as $mozo_activo): ?>
                    <option value="<?= $mozo_activo['id_usuario'] ?>">
                        <?= htmlspecialchars($mozo_activo['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <label style="display: flex; align-items: center; cursor: pointer; padding: 15px; border: 2px solid #dee2e6; border-radius: 8px; transition: all 0.3s;" 
               onmouseover="this.style.backgroundColor='#f8f9fa'" 
               onmouseout="this.style.backgroundColor='white'">
            <input type="radio" name="accion_mesas" value="liberar" required style="margin-right: 12px; transform: scale(1.2);">
            <div>
                <strong style="color: #ffc107; font-size: 1.1em;">🏃‍♂️ Liberar mesas</strong>
                <div style="color: #6c757d; font-size: 0.9em; margin-top: 5px;">
                    Dejar las mesas sin mozo asignado (podrán ser asignadas posteriormente)
                </div>
            </div>
        </label>
    </div>
    
    <div style="display: flex; gap: 15px; justify-content: center; padding-top: 20px; border-top: 1px solid #dee2e6;">
        <button type="submit" class="button" style="background: #dc3545; font-size: 1.1em; padding: 12px 25px;">
            🗑️ Confirmar Eliminación
        </button>
        <a href="<?= url('mozos/edit', ['id' => $id_mozo]) ?>" class="button" style="background: #6c757d; font-size: 1.1em; padding: 12px 25px;">
            ❌ Cancelar
        </a>
    </div>
</form>

<script>
// Mostrar/ocultar selector de mozo según la opción elegida
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="accion_mesas"]');
    const selectorMozo = document.getElementById('selector-mozo');
    const selectMozo = document.querySelector('select[name="nuevo_mozo"]');
    
    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'reasignar') {
                selectorMozo.style.display = 'block';
                selectMozo.required = true;
            } else {
                selectorMozo.style.display = 'none';
                selectMozo.required = false;
                selectMozo.value = '';
            }
        });
    });
    
    // Validación del formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const accionSeleccionada = document.querySelector('input[name="accion_mesas"]:checked');
        
        if (!accionSeleccionada) {
            e.preventDefault();
            alert('Por favor, selecciona qué hacer con las mesas asignadas.');
            return;
        }
        
        if (accionSeleccionada.value === 'reasignar' && !selectMozo.value) {
            e.preventDefault();
            alert('Por favor, selecciona un mozo para reasignar las mesas.');
            return;
        }
        
        // Confirmación final
        const mozoNombre = '<?= htmlspecialchars($mozo['nombre'] . ' ' . $mozo['apellido']) ?>';
        const mesasCount = <?= $mesas_asignadas ?>;
        let mensaje = `⚠️ CONFIRMACIÓN FINAL ⚠️\n\n¿Estás SEGURO de eliminar a ${mozoNombre}?\n\n`;
        mensaje += `El mozo será marcado como eliminado y no podrá acceder al sistema.\n\n`;
        
        if (accionSeleccionada.value === 'reasignar') {
            const nuevoMozoTexto = selectMozo.options[selectMozo.selectedIndex].text;
            mensaje += `Sus ${mesasCount} mesa(s) serán reasignadas a: ${nuevoMozoTexto}`;
        } else {
            mensaje += `Sus ${mesasCount} mesa(s) quedarán sin mozo asignado.`;
        }
        
        mensaje += `\n\nEscribe "ELIMINAR" para confirmar:`;
        
        const confirmacion = prompt(mensaje);
        if (confirmacion !== 'ELIMINAR') {
            e.preventDefault();
            alert('Eliminación cancelada. Debes escribir "ELIMINAR" para confirmar.');
        }
    });
});
</script>
