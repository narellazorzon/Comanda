<?php
// src/views/mesas/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Mesa;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mozos y administradores pueden ver esta página
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];

// Solo administradores pueden eliminar mesas
if (isset($_GET['delete']) && $rol === 'administrador') {
    $id = (int) $_GET['delete'];
    
    if ($id > 0) {
        $mesa = Mesa::find($id);
        
        if ($mesa && $mesa['estado'] === 'libre') {
            $resultado = Mesa::delete($id);
            
            if ($resultado) {
                header('Location: ' . url('mesas', ['success' => '1']));
            } else {
                header('Location: ' . url('mesas', ['error' => '2']));
            }
        } else {
            header('Location: ' . url('mesas', ['error' => '1']));
        }
        exit;
    }
    header('Location: ' . url('mesas'));
    exit;
}

// 1) Cargamos todas las mesas
$mesas = Mesa::all();
?>

<h2><?= $rol === 'administrador' ? 'Gestión de Mesas' : 'Consulta de Mesas' ?></h2>

<?php if (isset($_GET['success'])): ?>
    <div class="success" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        Mesa eliminada correctamente.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="error" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        No se puede eliminar una mesa que está ocupada.
    </div>
<?php endif; ?>

<?php if ($rol === 'administrador'): ?>
  <a href="<?= url('mesas/create') ?>" class="button">Nueva Mesa</a>
<?php else: ?>
  <div style="background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #0c5460;">
    👁️ Vista de solo lectura - Consulta las mesas disponibles
  </div>
<?php endif; ?>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Número</th>
      <th>Ubicación</th>
      <th>Estado</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mesas as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['id_mesa']) ?></td>
        <td><?= htmlspecialchars($m['numero']) ?></td>
        <td><?= htmlspecialchars($m['ubicacion'] ?? '—') ?></td>
        <td>
          <?php
          // Definir colores según el estado
          $estado = $m['estado'];
          if ($estado === 'libre') {
              $bg_color = '#d4edda';
              $text_color = '#155724';
          } elseif ($estado === 'reservada') {
              $bg_color = '#fff3cd';
              $text_color = '#856404';
          } else { // ocupada
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
        <?php if ($rol === 'administrador'): ?>
          <td>
            <a href="<?= url('mesas/edit', ['id' => $m['id_mesa']]) ?>" class="btn-action">Editar</a>
            <?php if ($m['estado'] === 'libre'): ?>
              <a href="#" class="btn-action" style="background: #dc3545;"
                 onclick="confirmarBorrado(<?= $m['id_mesa'] ?>, <?= $m['numero'] ?>)">
                Borrar
              </a>
            <?php else: ?>
              <span class="btn-action" style="background: #6c757d; cursor: not-allowed; opacity: 0.6;" 
                    title="No se puede borrar una mesa <?= $m['estado'] ?>">
                Borrar
              </span>
            <?php endif; ?>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Modal de confirmación -->
<div id="modalConfirmacion" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
  <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 400px; width: 90%; text-align: center;">
    <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
    <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.5rem;">Confirmar Eliminación</h3>
    <p style="margin: 0 0 2rem 0; color: #666; line-height: 1.5;">
      ¿Estás seguro de que quieres eliminar la mesa <strong id="numeroMesa"></strong>?<br>
      <small style="color: #999;">Esta acción no se puede deshacer.</small>
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
      <button id="btnCancelar" style="padding: 0.75rem 1.5rem; border: 2px solid #6c757d; background: white; color: #6c757d; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s;">
        Cancelar
      </button>
      <button id="btnConfirmar" style="padding: 0.75rem 1.5rem; border: none; background: #dc3545; color: white; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s;">
        Sí, Eliminar
      </button>
    </div>
  </div>
</div>

<script>
let mesaIdAEliminar = null;

function confirmarBorrado(id, numero) {
    mesaIdAEliminar = id;
    document.getElementById('numeroMesa').textContent = numero;
    document.getElementById('modalConfirmacion').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalConfirmacion').style.display = 'none';
    mesaIdAEliminar = null;
}

function eliminarMesa() {
    if (mesaIdAEliminar) {
        window.location.href = '<?= url('mesas', ['delete' => '']) ?>' + mesaIdAEliminar;
    }
}

// Event listeners
document.getElementById('btnCancelar').addEventListener('click', cerrarModal);
document.getElementById('btnConfirmar').addEventListener('click', eliminarMesa);

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
    this.style.transform = 'translateY(-2px)';
});

document.getElementById('btnConfirmar').addEventListener('mouseleave', function() {
    this.style.background = '#dc3545';
    this.style.transform = 'translateY(0)';
});
</script>
