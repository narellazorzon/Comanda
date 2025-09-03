<?php
// src/views/pedidos/index.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Pedido;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    // La sesión ya está iniciada desde public/index.php
}
// Mozos y administradores pueden ver pedidos
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: ' . url('login'));
    exit;
}

$rol = $_SESSION['user']['rol'];

// Cargar pedidos
$pedidos = Pedido::all();

?>

<h2><?= $rol === 'administrador' ? 'Gestión de Pedidos' : 'Consulta de Pedidos' ?></h2>

<?php if ($rol === 'administrador'): ?>
  <a href="<?= url('pedidos/create') ?>" class="button">Nuevo Pedido</a>
<?php else: ?>
  <div style="background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 1rem; color: #0c5460;">
    🍽️ Vista de pedidos - Gestiona los pedidos de las mesas
  </div>
<?php endif; ?>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Mesa</th>
      <th>Mozo</th>
      <th>Estado</th>
      <th>Total</th>
      <th>Fecha</th>
      <?php if ($rol === 'administrador'): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($pedidos)): ?>
      <tr>
        <td colspan="<?= $rol === 'administrador' ? '7' : '6' ?>">No hay pedidos registrados.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($pedidos as $pedido): ?>
        <tr>
          <td><?= htmlspecialchars($pedido['id_pedido']) ?></td>
          <td><?= htmlspecialchars($pedido['numero_mesa'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($pedido['nombre_mozo_completo'] ?? 'N/A') ?></td>
          <td>
            <?php
            // Definir colores según el estado del pedido
            $estado = $pedido['estado'];
            switch ($estado) {
                case 'pendiente':
                    $bg_color = '#fff3cd';
                    $text_color = '#856404';
                    $icon = '⏳';
                    break;
                case 'en_preparacion':
                    $bg_color = '#cce5ff';
                    $text_color = '#004085';
                    $icon = '👨‍🍳';
                    break;
                case 'servido':
                    $bg_color = '#d4edda';
                    $text_color = '#155724';
                    $icon = '✅';
                    break;
                case 'cuenta':
                    $bg_color = '#d1ecf1';
                    $text_color = '#0c5460';
                    $icon = '💳';
                    break;
                case 'cerrado':
                    $bg_color = '#e2e3e5';
                    $text_color = '#383d41';
                    $icon = '🔒';
                    break;
                default:
                    $bg_color = '#f8d7da';
                    $text_color = '#721c24';
                    $icon = '❓';
            }
            ?>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; 
                         background: <?= $bg_color ?>; 
                         color: <?= $text_color ?>;">
              <?= $icon ?> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $pedido['estado']))) ?>
            </span>
          </td>
          <td><strong>$<?= number_format($pedido['total'] ?? 0, 2) ?></strong></td>
          <td><?= !empty($pedido['fecha_creacion']) ? date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) : 'N/A' ?></td>
          <?php if ($rol === 'administrador'): ?>
            <td>
              <a href="<?= url('pedidos/edit', ['id' => $pedido['id_pedido']]) ?>" class="btn-action">Editar</a>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>


