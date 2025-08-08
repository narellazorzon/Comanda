<?php
// public/cme_pedidos.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Pedido;
use App\Models\Mesa;
use App\Models\Usuario;
use App\Models\DetallePedido;

session_start();

// Solo mozos y administradores pueden ver esta página
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['mozo', 'administrador'])) {
    header('Location: login.php');
    exit;
}

$rol = $_SESSION['user']['rol'];
$userId = $_SESSION['user']['id_usuario'];

// Procesar cambios de estado
if (isset($_POST['cambiar_estado'])) {
    $pedidoId = (int) $_POST['pedido_id'];
    $nuevoEstado = $_POST['nuevo_estado'];
    
    if (Pedido::updateEstado($pedidoId, $nuevoEstado)) {
        // Si el pedido se marca como pagado, liberar la mesa
        if ($nuevoEstado === 'pagado') {
            $pedido = Pedido::find($pedidoId);
            if ($pedido && $pedido['id_mesa']) {
                Mesa::update($pedido['id_mesa'], ['estado' => 'libre']);
            }
        }
        header('Location: cme_pedidos.php?success=1');
        exit;
    } else {
        header('Location: cme_pedidos.php?error=1');
        exit;
    }
}

// Cargar pedidos según el rol
if ($rol === 'administrador') {
    $pedidos = Pedido::all();
} else {
    // Mozos ven solo sus pedidos
    $pedidos = Pedido::allByMozo($userId);
}

// Cargar datos adicionales
$mesas = Mesa::all();
$mesasIndex = [];
foreach ($mesas as $mesa) {
    $mesasIndex[$mesa['id_mesa']] = $mesa;
}

$usuarios = Usuario::allByRole('mozo');
$mozosIndex = [];
foreach ($usuarios as $mozo) {
    $mozosIndex[$mozo['id_usuario']] = $mozo;
}

require_once __DIR__ . '/includes/header.php';
?>

<h2>Gestión de Pedidos</h2>

<?php if (isset($_GET['success'])): ?>
    <div class="success" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        Estado del pedido actualizado correctamente.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="error" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        Error al actualizar el estado del pedido.
    </div>
<?php endif; ?>

<?php if ($rol === 'administrador'): ?>
    <a href="alta_pedido.php" class="button">Nuevo Pedido</a>
<?php endif; ?>

<?php if (empty($pedidos)): ?>
    <p style="color: #666; font-style: italic; margin-top: 2rem;">No hay pedidos para mostrar.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th># Pedido</th>
                <th>Mesa/Modo</th>
                <th>Mozo</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>
                        <strong>#<?= $pedido['id_pedido'] ?></strong>
                        <?php if ($pedido['observaciones']): ?>
                            <br><small style="color: #666;"><?= htmlspecialchars($pedido['observaciones']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($pedido['id_mesa']): ?>
                            Mesa <?= $mesasIndex[$pedido['id_mesa']]['numero'] ?? 'N/A' ?>
                            <br><small><?= $mesasIndex[$pedido['id_mesa']]['ubicacion'] ?? '' ?></small>
                        <?php else: ?>
                            <span style="color: #666;">Takeaway</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($pedido['id_mozo']): ?>
                            <?= htmlspecialchars($mozosIndex[$pedido['id_mozo']]['nombre'] ?? 'N/A') ?>
                            <?= htmlspecialchars($mozosIndex[$pedido['id_mozo']]['apellido'] ?? '') ?>
                        <?php else: ?>
                            <span style="color: #666;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="estado-badge estado-<?= $pedido['estado'] ?>" 
                              style="padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold;">
                            <?= ucfirst(str_replace('_', ' ', $pedido['estado'])) ?>
                        </span>
                    </td>
                    <td>
                        <strong>$<?= number_format($pedido['total'], 2) ?></strong>
                    </td>
                    <td>
                        <?= date('d/m/Y H:i', strtotime($pedido['fecha_hora'])) ?>
                    </td>
                    <td class="action-cell">
                        <a href="#" onclick="verDetalle(<?= $pedido['id_pedido'] ?>)" class="btn-action">
                            Ver Detalle
                        </a>
                        
                        <?php if ($rol === 'administrador' || $pedido['id_mozo'] == $userId): ?>
                            <form method="post" class="action-form" style="display: inline;">
                                <input type="hidden" name="pedido_id" value="<?= $pedido['id_pedido'] ?>">
                                <select name="nuevo_estado" onchange="this.form.submit()" style="font-size: 0.8em;">
                                    <option value="">Cambiar estado</option>
                                    <?php if ($pedido['estado'] === 'pendiente'): ?>
                                        <option value="en_preparacion">En Preparación</option>
                                    <?php endif; ?>
                                    <?php if ($pedido['estado'] === 'en_preparacion'): ?>
                                        <option value="listo">Listo</option>
                                    <?php endif; ?>
                                    <?php if ($pedido['estado'] === 'listo'): ?>
                                        <option value="pagado">Pagado</option>
                                    <?php endif; ?>
                                </select>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($rol === 'administrador'): ?>
                            <a href="?delete=<?= $pedido['id_pedido'] ?>" 
                               onclick="return confirm('¿Borrar pedido #<?= $pedido['id_pedido'] ?>?')"
                               class="btn-action" style="color: #d32f2f;">
                                Borrar
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Modal para ver detalles del pedido -->
<div id="detalleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
     background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
         background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; max-height: 80%; overflow-y: auto;">
        <h3>Detalle del Pedido #<span id="pedidoNumero"></span></h3>
        <div id="detalleContenido"></div>
        <button onclick="cerrarDetalle()" style="margin-top: 1rem;">Cerrar</button>
    </div>
</div>

<style>
.estado-pendiente { background: #fff3cd; color: #856404; }
.estado-en_preparacion { background: #cce5ff; color: #004085; }
.estado-listo { background: #d4edda; color: #155724; }
.estado-pagado { background: #d1ecf1; color: #0c5460; }

.btn-action {
    display: inline-block;
    padding: 4px 8px;
    margin: 2px;
    background: var(--secondary);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.8em;
}

.btn-action:hover {
    background: var(--accent);
    color: var(--text);
}
</style>

<script>
function verDetalle(pedidoId) {
    // Aquí podrías hacer una llamada AJAX para obtener los detalles
    // Por ahora, mostraremos un mensaje simple
    document.getElementById('pedidoNumero').textContent = pedidoId;
    document.getElementById('detalleContenido').innerHTML = 
        '<p>Cargando detalles del pedido...</p>';
    document.getElementById('detalleModal').style.display = 'block';
}

function cerrarDetalle() {
    document.getElementById('detalleModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('detalleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarDetalle();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
