<?php
// src/views/mozos/confirmar_inactivacion.php
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
if (!$mozo || $mozo['rol'] !== 'mozo') {
    header('Location: ' . url('mozos', ['error' => 'Mozo no encontrado']));
    exit;
}

// Obtener mesas asignadas al mozo
$mesas_del_mozo = Mesa::getMesasByMozo($id_mozo);

// Obtener otros mozos activos para reasignación
$mozos_activos = Usuario::getMozosActivos();
// Filtrar el mozo que se está inactivando
$mozos_activos = array_filter($mozos_activos, function($m) use ($id_mozo) {
    return $m['id_usuario'] != $id_mozo;
});

?>

<h2>⚠️ Confirmar Inactivación del Personal</h2>

<div style="background:rgb(238, 224, 191); border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin-bottom: 2rem;">
    <h3 style="margin: 0 0 15px 0; color:rgb(240, 100, 98);">🚨 Atención: Mozo con Mesas Asignadas</h3>
    <p style="margin: 0 0 15px 0; color:rgb(109, 93, 69); font-size: 1.1em;">
        <strong><?= htmlspecialchars($mozo['nombre'] . ' ' . $mozo['apellido']) ?></strong> 
        tiene <strong><?= $mesas_asignadas ?> mesa(s)</strong> asignada(s).
    </p>
    <p style="margin: 0; color:rgb(109, 93, 69);">
        Antes de inactivar este mozo, debes decidir qué hacer con sus mesas asignadas.
    </p>
</div>

<!-- Mostrar mesas asignadas -->
<div style="background:rgb(238, 224, 191);border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-bottom: 20px;">
    <h4 style="margin: 0 0 15px 0; rgb(109, 93, 69);">📍 Mesas Actualmente Asignadas</h4>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php foreach ($mesas_del_mozo as $mesa): ?>
            <div style="background: white; border: 2px solid rgb(111, 88, 57); border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; gap: 8px;">
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
<form method="post" action="<?= url('mozos/procesar-inactivacion') ?>" style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 25px;">
    <!-- Datos del mozo (hidden) -->
    <input type="hidden" name="id_mozo" value="<?= $id_mozo ?>">
    <input type="hidden" name="nombre" value="<?= htmlspecialchars($_GET['nombre'] ?? $mozo['nombre']) ?>">
    <input type="hidden" name="apellido" value="<?= htmlspecialchars($_GET['apellido'] ?? $mozo['apellido']) ?>">
    <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? $mozo['email']) ?>">
    <?php if (!empty($_GET['nueva_contrasenia'])): ?>
        <input type="hidden" name="nueva_contrasenia" value="<?= htmlspecialchars($_GET['nueva_contrasenia']) ?>">
    <?php endif; ?>
    
    <h3 style="margin: 0 0 20px 0; color:rgb(109, 93, 69);">🎯 ¿Qué hacer con las mesas asignadas?</h3>
    
    <div style="margin-bottom: 25px;">
        <label style="display: flex; align-items: center; margin-bottom: 15px; cursor: pointer; padding: 15px; border: 2px solid #dee2e6; border-radius: 8px; transition: all 0.3s; background-color: rgb(238, 224, 191);" 
               onmouseover="this.style.backgroundColor='rgb(220, 200, 160)'" 
               onmouseout="this.style.backgroundColor='rgb(238, 224, 191)'">
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
        
        <label style="display: flex; align-items: center; cursor: pointer; padding: 15px; border: 2px solid #dee2e6; border-radius: 8px; transition: all 0.3s; background-color: rgb(238, 224, 191);" 
               onmouseover="this.style.backgroundColor='rgb(220, 200, 160)'" 
               onmouseout="this.style.backgroundColor='rgb(238, 224, 191)'">
            <input type="radio" name="accion_mesas" value="liberar" required style="margin-right: 12px; transform: scale(1.2);">
            <div>
                <strong style="color: #ffc107; font-size: 1.1em;">🏃‍♂️ Liberar mesas</strong>
                <div style="color: #6c757d; font-size: 0.9em; margin-top: 5px;">
                    Dejar las mesas sin mozo asignado (podrán ser asignadas posteriormente)
                </div>
            </div>
        </label>
    </div>
    
    <div style="
      display: flex;
      gap: 20px;
      justify-content: center;
      padding-top: 15px;
      border-top: 1px solid #dee2e6;
      margin-top: 15px;
    ">
      <!-- Botón Confirmar -->
      <button type="submit" style="
          background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
          color: white;
          border: none;
          border-radius: 10px;
          font-size: 1.05em;
          font-weight: 500;
          padding: 12px 25px;
          cursor: pointer;
          transition: all 0.3s ease;
          box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
          width: 220px;
          height: 60px;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 10px;
          line-height: 1.2;
          transform: translateY(-25px);
      " 
      onmouseover="this.style.transform='translateY(-27px)'; this.style.boxShadow='0 6px 16px rgba(220, 53, 69, 0.4)'" 
      onmouseout="this.style.transform='translateY(-25px)'; this.style.boxShadow='0 4px 12px rgba(220, 53, 69, 0.3)'">
          <span style="font-size: 1.1em; display: flex; align-items: center; justify-content: center; transform: translateY(-1px);">⚠️</span>
          <span>Confirmar Inactivación</span>
      </button>

      <!-- Botón Cancelar -->
      <a href="<?= url('mozos/edit', ['id' => $id_mozo]) ?>" style="
          background: linear-gradient(135deg, #8b5e46 0%, #6b442f 100%);
          color: white;
          border: none;
          border-radius: 10px;
          font-size: 1.05em;
          font-weight: 500;
          padding: 12px 25px;
          cursor: pointer;
          transition: all 0.3s ease;
          box-shadow: 0 4px 12px rgba(139, 94, 70, 0.3);
          text-decoration: none;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 10px;
          width: 220px;
          height: 60px;
          line-height: 1.2;
      " 
      onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(139, 94, 70, 0.4)'" 
      onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(139, 94, 70, 0.3)'">
          <span style="font-size: 1.2em; display: flex; align-items: center; justify-content: center;">❌</span>
          <span>Cancelar</span>
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
        let mensaje = `¿Estás seguro de inactivar a ${mozoNombre}?\n\n`;
        
        if (accionSeleccionada.value === 'reasignar') {
            const nuevoMozoTexto = selectMozo.options[selectMozo.selectedIndex].text;
            mensaje += `Sus ${mesasCount} mesa(s) serán reasignadas a: ${nuevoMozoTexto}`;
        } else {
            mensaje += `Sus ${mesasCount} mesa(s) quedarán sin mozo asignado.`;
        }
        
        if (!confirm(mensaje)) {
            e.preventDefault();
        }
    });
});
</script>
