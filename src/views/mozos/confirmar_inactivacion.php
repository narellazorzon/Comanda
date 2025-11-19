<?php
// src/views/mozos/confirmar_inactivacion.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Usuario;
use App\Models\Mesa;
use App\Controllers\MozoController;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo administradores pueden acceder
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: ' . url('login'));
    exit;
}

// Si viene un POST, delegamos al controlador y salimos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    MozoController::procesarInactivacion();
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
<form method="post" action="<?= url('mozos/confirmar-inactivacion') ?>" style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 25px;">
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
      <a href="<?= url('mozos') ?>" style="
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
    const form = document.querySelector('form');
    if (form) {
        form._submitHandler = function(e) {
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
            
            // Mostrar modal personalizado en lugar de confirm()
            e.preventDefault();
            mostrarModalConfirmacion(mensaje, accionSeleccionada.value, selectMozo.value);
        };
        
        form.addEventListener('submit', form._submitHandler);
    }
});

// Función para mostrar el modal de confirmación
function mostrarModalConfirmacion(mensaje, accion, nuevoMozoId) {
    const modal = document.getElementById('modalConfirmacionInactivacion');
    const mensajeModal = document.getElementById('mensajeModalConfirmacion');
    
    // Formatear el mensaje para HTML
    let mensajeHTML = mensaje.replace(/\n/g, '<br>');
    mensajeHTML = mensajeHTML.replace(/¿Estás seguro de inactivar a (.+?)\?/g, '¿Estás seguro de inactivar a <strong>$1</strong>?');
    
    // Si es reasignación, mostrar el nombre del nuevo mozo
    if (accion === 'reasignar' && nuevoMozoId) {
        const selectMozo = document.querySelector('select[name="nuevo_mozo"]');
        const nuevoMozoTexto = selectMozo.options[selectMozo.selectedIndex].text;
        mensajeHTML = mensajeHTML.replace(/serán reasignadas a: (.+)/g, `serán reasignadas a: <strong>$1</strong>`);
    }
    
    mensajeModal.innerHTML = mensajeHTML;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Guardar referencia al formulario para enviarlo después
    modal.dataset.formId = 'formConfirmacion';
}

// Función para confirmar la inactivación
function confirmarInactivacion() {
    const form = document.querySelector('form');
    // Remover el event listener temporal para evitar el loop
    form.removeEventListener('submit', form._submitHandler);
    form.submit();
}

// Función para cerrar el modal
function cerrarModalConfirmacion() {
    document.getElementById('modalConfirmacionInactivacion').style.display = 'none';
    document.body.style.overflow = 'auto';
}
</script>

<!-- Modal de confirmación personalizado -->
<div id="modalConfirmacionInactivacion" class="modal-reasignacion" style="display: none;">
  <div class="modal-overlay" onclick="cerrarModalConfirmacion()"></div>
  <div class="modal-content modal-warning">
    <div class="modal-header">
      <h3>⚠️ Confirmar Inactivación</h3>
      <button class="modal-close" onclick="cerrarModalConfirmacion()">×</button>
    </div>
    <div class="modal-body">
      <div class="warning-icon">⚠️</div>
      <p class="modal-message" id="mensajeModalConfirmacion"></p>
      <div class="modal-info">
        <p>Esta acción es irreversible. El mozo quedará inactivo y las mesas serán procesadas según tu selección.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancelar" onclick="cerrarModalConfirmacion()">Cancelar</button>
      <button class="btn-confirmar btn-confirmar-warning" onclick="confirmarInactivacion()">⚠️ Confirmar Inactivación</button>
    </div>
  </div>
</div>

<style>
/* Estilos para modal de confirmación de inactivación */
.modal-reasignacion {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease;
}

.modal-reasignacion .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal-reasignacion .modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    animation: slideInScale 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    z-index: 10001;
}

.modal-reasignacion .modal-header {
    color: white;
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-reasignacion .modal-content.modal-warning .modal-header {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

.modal-reasignacion .modal-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-reasignacion .modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 4px;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.modal-reasignacion .modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

.modal-reasignacion .modal-body {
    padding: 24px;
    text-align: center;
}

.modal-reasignacion .warning-icon {
    font-size: 3rem;
    margin-bottom: 16px;
    animation: pulse 2s infinite;
}

.modal-reasignacion .modal-message {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 16px;
    line-height: 1.5;
}

.modal-reasignacion .modal-info {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 12px;
    margin-top: 16px;
}

.modal-reasignacion .modal-info p {
    margin: 0;
    font-size: 0.9rem;
    color: #6c757d;
    line-height: 1.4;
}

.modal-reasignacion .modal-footer {
    padding: 20px 24px;
    background: #f8f9fa;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.modal-reasignacion .btn-cancelar,
.modal-reasignacion .btn-confirmar {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    min-width: 100px;
}

.modal-reasignacion .btn-cancelar {
    background: rgb(144, 104, 76);
    color: white;
}

.modal-reasignacion .btn-cancelar:hover {
    background: rgb(92, 64, 51);
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 12px rgba(144, 104, 76, 0.3);
}

.modal-reasignacion .btn-confirmar-warning {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.modal-reasignacion .btn-confirmar-warning:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4);
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

@keyframes slideInScale {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@media (max-width: 768px) {
    .modal-reasignacion .modal-content {
        max-width: 95%;
        width: 95%;
    }
    
    .modal-reasignacion .modal-footer {
        flex-direction: column;
    }
    
    .modal-reasignacion .btn-cancelar,
    .modal-reasignacion .btn-confirmar {
        width: 100%;
        margin: 4px 0;
    }
}
</style>

<script>
// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalConfirmacion();
    }
});

// Prevenir cierre del modal al hacer clic en el contenido
document.addEventListener('DOMContentLoaded', function() {
    const modalContent = document.querySelector('.modal-reasignacion .modal-content');
    if (modalContent) {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>
