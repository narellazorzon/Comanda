<?php
// src/views/mozos/create.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Controllers\MozoController;
use App\Models\Usuario;

// Solo iniciar sesión si no está activa
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
    MozoController::create();
    exit;
}

// Si viene ?id= para editar, cargamos los datos del mozo
$mozo = null;
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        $mozo = Usuario::find($id);
        if (!$mozo || !in_array($mozo['rol'], ['mozo', 'administrador'])) {
            header('Location: ' . url('mozos', ['error' => 'Usuario no encontrado']));
            exit;
        }
    }
}
?>

<h2><?= isset($mozo) ? ($mozo['rol'] === 'administrador' ? 'Modificar Administrador' : 'Modificar Mozo') : 'Alta de Mozo' ?></h2>

<?php if (isset($_GET['success'])): ?>
  <div style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
    <?= htmlspecialchars($_GET['success']) ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
    <?= htmlspecialchars($_GET['error']) ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= url('mozos/create') ?>" autocomplete="off">
  <?php if (isset($mozo)): ?>
    <input type="hidden" name="id" value="<?= $mozo['id_usuario'] ?>">
  <?php endif; ?>
  
  <?php 
  // Usar datos de la sesión si hay error de validación, o datos del mozo si estamos editando
  $formData = null;
  if (isset($mozo)) {
      $formData = $mozo;
  } elseif (isset($_GET['error']) && isset($_SESSION['form_data'])) {
      $formData = $_SESSION['form_data'];
      // Limpiar los datos de la sesión después de usarlos
      unset($_SESSION['form_data']);
  }
  ?>
  
  <label>Nombre:</label>
  <input type="text" name="nombre" required value="<?= htmlspecialchars($formData['nombre'] ?? '') ?>" autocomplete="off">

  <label>Apellido:</label>
  <input type="text" name="apellido" required value="<?= htmlspecialchars($formData['apellido'] ?? '') ?>" autocomplete="off">

  <label>Email:</label>
  <input type="email" name="email" required value="<?= htmlspecialchars($formData['email'] ?? '') ?>" autocomplete="off">

  <?php if (isset($mozo)): ?>
    <label>Estado:</label>
    <select name="estado" required>
      <?php $estado = $formData['estado'] ?? 'activo'; ?>
      <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option>
      <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
    </select>

    <label>Nueva Contraseña (opcional):</label>
    <input type="password" name="contrasenia" id="contrasenia_edit" placeholder="Dejar vacío para mantener la actual" autocomplete="new-password" minlength="8">
    <small style="color: #666; display: block; margin-top: 0.25rem;">
      Solo completa este campo si deseas cambiar la contraseña. Debe tener al menos 8 caracteres.
    </small>
  <?php else: ?>
    <label>Contraseña:</label>
    <input type="password" name="contrasenia" id="contrasenia" required autocomplete="new-password" minlength="8">
    <small style="color: #666; display: block; margin-top: 0.25rem;">
      La contraseña debe tener al menos 8 caracteres
    </small>
  <?php endif; ?>

  <button type="submit"><?= isset($mozo) ? 'Guardar cambios' : 'Crear Mozo' ?></button>
</form>

<a href="<?= url('mozos') ?>" class="button" style="background-color: rgb(83, 52, 31); margin-top: 1rem;">
  ← Volver a la lista
</a>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contraseniaInput = document.getElementById('contrasenia');
    const contraseniaEditInput = document.getElementById('contrasenia_edit');
    const form = document.querySelector('form');
    
    function validatePassword(input, isRequired = true) {
        if (!input) return true;
        
        const value = input.value.trim();
        const minLength = 8;
        
        // Si no es requerido y está vacío, es válido
        if (!isRequired && value === '') {
            input.setCustomValidity('');
            return true;
        }
        
        // Si es requerido o tiene contenido, validar longitud
        if (value.length < minLength) {
            input.setCustomValidity(`La contraseña debe tener al menos ${minLength} caracteres`);
            return false;
        }
        
        input.setCustomValidity('');
        return true;
    }
    
    function updatePasswordHelpText(input, isRequired = true) {
        if (!input) return;
        
        const value = input.value.trim();
        const minLength = 8;
        const helpText = input.nextElementSibling;
        
        if (!isRequired && value === '') {
            helpText.textContent = 'Solo completa este campo si deseas cambiar la contraseña. Debe tener al menos 8 caracteres.';
            helpText.style.color = '#666';
        } else if (value.length > 0 && value.length < minLength) {
            helpText.textContent = `La contraseña debe tener al menos ${minLength} caracteres (${value.length}/${minLength})`;
            helpText.style.color = '#dc3545';
        } else if (value.length >= minLength) {
            helpText.textContent = 'Contraseña válida ✓';
            helpText.style.color = '#28a745';
        } else {
            helpText.textContent = isRequired ? 
                'La contraseña debe tener al menos 8 caracteres' : 
                'Solo completa este campo si deseas cambiar la contraseña. Debe tener al menos 8 caracteres.';
            helpText.style.color = '#666';
        }
    }
    
    // Validación en tiempo real para contraseña nueva
    if (contraseniaInput) {
        contraseniaInput.addEventListener('input', function() {
            validatePassword(this, true);
            updatePasswordHelpText(this, true);
        });
        
        contraseniaInput.addEventListener('blur', function() {
            validatePassword(this, true);
            updatePasswordHelpText(this, true);
        });
    }
    
    // Validación en tiempo real para contraseña de edición
    if (contraseniaEditInput) {
        contraseniaEditInput.addEventListener('input', function() {
            validatePassword(this, false);
            updatePasswordHelpText(this, false);
        });
        
        contraseniaEditInput.addEventListener('blur', function() {
            validatePassword(this, false);
            updatePasswordHelpText(this, false);
        });
    }
    
    // Validación antes de enviar el formulario
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        if (contraseniaInput) {
            isValid = validatePassword(contraseniaInput, true) && isValid;
        }
        
        if (contraseniaEditInput) {
            isValid = validatePassword(contraseniaEditInput, false) && isValid;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Por favor, corrige los errores en el formulario antes de enviar.');
        }
    });
});
</script>
