<?php
// public/llamados.php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\LlamadoMesa;

session_start();
// Verificar rol mozo
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'mozo') {
    header('Location: login.php');
    exit;
}

// Procesar cambio de estado
if (isset($_GET['id'], $_GET['estado'])) {
    $id  = (int) $_GET['id'];
    $new = $_GET['estado'];
    $est = ['pendiente','en_atencion','completado'];
    if (in_array($new, $est, true)) {
        LlamadoMesa::updateEstado($id, $new);
        header('Location: llamados.php');
        exit;
    }
    $error = 'Estado inválido.';
}

// Obtener todos los llamados
$llamados = LlamadoMesa::all();

include __DIR__ . '/includes/header.php';
?>

<h2>Llamados de Mesa</h2>
<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Mesa</th>
      <th>Solicitud</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($llamados as $l): ?>
      <tr>
        <td><?= htmlspecialchars($l['id_llamado']) ?></td>
        <td><?= htmlspecialchars($l['id_mesa']) ?></td>
        <td>
          <?= date('d/m/Y H:i:s', strtotime($l['hora_solicitud'])) ?>
        </td>
        <td><?= htmlspecialchars($l['estado']) ?></td>
        <td>
          <?php if ($l['estado'] === 'pendiente'): ?>
            <a href="?id=<?= $l['id_llamado'] ?>&estado=en_atencion"
               onclick="return confirm('Marcar en atención?')">
              En atención
            </a>
          <?php elseif ($l['estado'] === 'en_atencion'): ?>
            <a href="?id=<?= $l['id_llamado'] ?>&estado=completado"
               onclick="return confirm('Marcar completado?')">
              Completado
            </a>
          <?php else: ?>
            &mdash;
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>
