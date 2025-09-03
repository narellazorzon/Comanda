<?php
// src/views/mozos/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Controllers\MozoController;
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

// 1) Si llega ?delete=ID, borramos y redirigimos
if (isset($_GET['delete'])) {
    MozoController::delete();
}

// 2) Cargamos todos los mozos
$mozos = Usuario::allByRole('mozo');

?>

<h2>Gestión de Mozos</h2>

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

<a class="button" href="<?= url('mozos/create') ?>">Nuevo Mozo</a>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Email</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($mozos)): ?>
    <tr>
      <td colspan="5">No hay mozos registrados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($mozos as $m): ?>
      <tr>
        <td><?= $m['id_usuario'] ?></td>
        <td><?= htmlspecialchars($m['nombre'].' '.$m['apellido']) ?></td>
        <td><?= htmlspecialchars($m['email']) ?></td>
        <td>
          <?php
          // Definir colores según el estado
          $estado = $m['estado'];
          if ($estado === 'activo') {
              $bg_color = '#d4edda';
              $text_color = '#155724';
          } else { // inactivo
              $bg_color = '#f8d7da';
              $text_color = '#721c24';
          }
          ?>
          <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                       background: <?= $bg_color ?>; 
                       color: <?= $text_color ?>;">
            <?= htmlspecialchars(ucfirst($m['estado'])) ?>
          </span>
        </td>
        <td>
          <a href="<?= url('mozos/edit', ['id' => $m['id_usuario']]) ?>" class="btn-action">Editar</a>
          <a href="#" class="btn-action" style="background: #dc3545;"
             onclick="confirmarBorrado(<?= $m['id_usuario'] ?>, '<?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?>')">
            Borrar
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>

<!-- Modal de confirmación -->
<div id="modalConfirmacion" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
  <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 400px; width: 90%; text-align: center;">
    <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
    <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.5rem;">Confirmar Eliminación</h3>
    <p style="margin: 0 0 2rem 0; color: #666; line-height: 1.5;">
      ¿Estás seguro de que quieres eliminar al mozo <strong id="nombreMozo"></strong>?<br>
      <small style="color: #999;">Esta acción no se puede deshacer.</small>
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
      <button id="btnCancelar" style="padding: 0.75rem 1.5rem; border: 2px solid #6c757d; background: white; color: #6c757d; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s;">
        Cancelar
      </button>
      <button id="btnConfirmar" style="padding: 0.75rem 1.5rem; border: 2px solid #dc3545; background: #dc3545; color: white; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s;">
        Eliminar
      </button>
    </div>
  </div>
</div>

<script>
let mozoIdAEliminar = null;

function confirmarBorrado(id, nombre) {
    mozoIdAEliminar = id;
    document.getElementById('nombreMozo').textContent = nombre;
    document.getElementById('modalConfirmacion').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalConfirmacion').style.display = 'none';
    mozoIdAEliminar = null;
}

function eliminarMozo() {
    if (mozoIdAEliminar) {
        window.location.href = '<?= url('mozos', ['delete' => '']) ?>' + mozoIdAEliminar;
    }
}

// Event listeners
document.getElementById('btnCancelar').addEventListener('click', cerrarModal);
document.getElementById('btnConfirmar').addEventListener('click', eliminarMozo);

// Cerrar modal al hacer clic fuera
document.getElementById('modalConfirmacion').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});

// Efectos hover para los botones
document.getElementById('btnCancelar').addEventListener('mouseenter', function() {
    this.style.background = '#6c757d';
    this.style.color = 'white';
});

document.getElementById('btnCancelar').addEventListener('mouseleave', function() {
    this.style.background = 'white';
    this.style.color = '#6c757d';
});

document.getElementById('btnConfirmar').addEventListener('mouseenter', function() {
    this.style.background = '#c82333';
    this.style.borderColor = '#c82333';
});

document.getElementById('btnConfirmar').addEventListener('mouseleave', function() {
    this.style.background = '#dc3545';
    this.style.borderColor = '#dc3545';
});
</script>

