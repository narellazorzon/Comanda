<?php
// src/views/mesas/create.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

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

$mesa = null;
$error = '';
$success = '';

// Si viene id por GET, estamos en modo edición
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $mesa = Mesa::find($id);
    if (!$mesa) {
        header('Location: ' . url('mesas'));
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $estado = trim($_POST['estado'] ?? 'libre');
    


    // Validaciones
    if (empty($numero)) {
        $error = 'El número de mesa es obligatorio';
    } elseif (!is_numeric($numero) || $numero <= 0) {
        $error = 'El número de mesa debe ser un número positivo';
    } else {
        try {
            $data = [
                'numero' => $numero,
                'ubicacion' => $ubicacion ?: null,
                'estado' => $estado,
            ];

            if (isset($mesa)) {
                // Modificar mesa existente
                if (Mesa::update($mesa['id_mesa'], $data)) {
                    $success = 'Mesa modificada correctamente';
                    $mesa = Mesa::find($mesa['id_mesa']); // Recargar datos
                } else {
                    $error = 'Error al modificar la mesa';
                }
            } else {
                // Crear nueva mesa
                if (Mesa::create($data)) {
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

?>

<h2><?= isset($mesa) ? 'Editar Mesa' : 'Nueva Mesa' ?></h2>

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
           value="<?= htmlspecialchars($mesa['numero'] ?? $_POST['numero'] ?? '') ?>"
           placeholder="Ej: 1, 2, 3...">

    <label>Ubicación (opcional):</label>
    <input type="text" name="ubicacion" 
           value="<?= htmlspecialchars($mesa['ubicacion'] ?? $_POST['ubicacion'] ?? '') ?>"
           placeholder="Ej: Terraza, Interior, Ventana...">

    <label>Estado:</label>
    <select name="estado">
        <option value="libre" <?= ($mesa['estado'] ?? $_POST['estado'] ?? 'libre') == 'libre' ? 'selected' : '' ?>>Libre</option>
        <option value="ocupada" <?= ($mesa['estado'] ?? $_POST['estado'] ?? 'libre') == 'ocupada' ? 'selected' : '' ?>>Ocupada</option>
        <option value="reservada" <?= ($mesa['estado'] ?? $_POST['estado'] ?? 'libre') == 'reservada' ? 'selected' : '' ?>>Reservada</option>
    </select>

    <button type="submit">
        <?= isset($mesa) ? 'Actualizar Mesa' : 'Crear Mesa' ?>
    </button>
    
    <a href="<?= url('mesas') ?>" class="button" style="margin-left: 10px;">Volver</a>
</form>


