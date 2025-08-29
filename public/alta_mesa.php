<?php
// public/alta_mesa.php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\Mesa;

session_start();
// Solo administradores pueden ver esta página
if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

$mesa = null;
$error = '';
$success = '';

// Si viene un ID, cargamos la mesa para editar
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $mesa = Mesa::find($id);
    if (!$mesa) {
        header('Location: cme_mesas.php');
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debugging temporal
    error_log("POST data: " . print_r($_POST, true));
    
    $numero = (int) $_POST['numero'];
    $ubicacion = trim($_POST['ubicacion']);
    $estado = $_POST['estado'] ?? 'libre';
    
    // Debugging temporal
    error_log("Numero procesado: " . $numero);
    error_log("Ubicacion procesada: " . $ubicacion);
    
    // Validaciones
    if ($numero <= 0) {
        $error = 'El número de mesa debe ser mayor a 0';
    } elseif (empty($ubicacion)) {
        $error = 'La ubicación es obligatoria';
    } else {
        try {
            if (isset($mesa)) {
                // Modificar mesa existente
                if (Mesa::update($mesa['id_mesa'], [
                    'numero' => $numero,
                    'ubicacion' => $ubicacion,
                    'estado' => $estado
                ])) {
                    $success = 'Mesa modificada correctamente';
                    $mesa = Mesa::find($mesa['id_mesa']); // Recargar datos
                } else {
                    $error = 'Error al modificar la mesa';
                }
            } else {
                // Crear nueva mesa
                if (Mesa::create([
                    'numero' => $numero,
                    'ubicacion' => $ubicacion,
                    'estado' => $estado
                ])) {
                    $success = 'Mesa creada correctamente';
                    // Limpiar formulario
                    $_POST = [];
                } else {
                    $error = 'Error al crear la mesa';
                }
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<h2><?= isset($mesa) ? 'Modificar Mesa' : 'Alta de Mesa' ?></h2>

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

<form method="post">
    <label>Número de Mesa:</label>
    <input type="number" name="numero" required min="1" 
           value="<?= htmlspecialchars($mesa['numero'] ?? $_POST['numero'] ?? '') ?>">

    <label>Ubicación:</label>
    <input type="text" name="ubicacion" required 
           value="<?= htmlspecialchars($mesa['ubicacion'] ?? $_POST['ubicacion'] ?? '') ?>"
           placeholder="Ej: Terraza, Interior, Bar">

    <?php if (isset($mesa)): ?>
        <label>Estado:</label>
        <select name="estado">
            <option value="libre" <?= ($mesa['estado'] ?? '') == 'libre' ? 'selected' : '' ?>>Libre</option>
            <option value="ocupada" <?= ($mesa['estado'] ?? '') == 'ocupada' ? 'selected' : '' ?>>Ocupada</option>
        </select>
    <?php endif; ?>

    <button type="submit">
        <?= isset($mesa) ? 'Guardar cambios' : 'Crear mesa' ?>
    </button>
    
    <a href="cme_mesas.php" class="button" style="margin-left: 10px;">Volver</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
