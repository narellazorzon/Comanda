<?php
// src/views/mesas/create.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/helpers.php';

use App\Models\Mesa;
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

$mesa = null;
$error = '';
$success = '';

// Obtener todos los mozos activos para el dropdown
$mozos = Usuario::getMozosActivos();

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
    $id_mozo = !empty($_POST['id_mozo']) ? (int) $_POST['id_mozo'] : null;

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
                'id_mozo' => $id_mozo,
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
    <div class="estado-options" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
        <label class="estado-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 8px 12px; border-radius: 12px; background: #d4edda; color: #155724; cursor: pointer; font-weight: bold; font-size: 0.8em; transition: all 0.3s ease; border: 2px solid transparent;">
            <input type="radio" name="estado" value="libre" <?= ($mesa['estado'] ?? $_POST['estado'] ?? 'libre') == 'libre' ? 'checked' : '' ?> style="margin: 0;">
            <span>🟢 Libre</span>
        </label>
        <label class="estado-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 8px 12px; border-radius: 12px; background: #f8d7da; color: #721c24; cursor: pointer; font-weight: bold; font-size: 0.8em; transition: all 0.3s ease; border: 2px solid transparent;">
            <input type="radio" name="estado" value="ocupada" <?= ($mesa['estado'] ?? $_POST['estado'] ?? 'libre') == 'ocupada' ? 'checked' : '' ?> style="margin: 0;">
            <span>🔴 Ocupada</span>
        </label>
        <label class="estado-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 8px 12px; border-radius: 12px; background: #fff3cd; color: #856404; cursor: pointer; font-weight: bold; font-size: 0.8em; transition: all 0.3s ease; border: 2px solid transparent;">
            <input type="radio" name="estado" value="reservada" <?= ($mesa['estado'] ?? $_POST['estado'] ?? 'libre') == 'reservada' ? 'checked' : '' ?> style="margin: 0;">
            <span>🟡 Reservada</span>
        </label>
    </div>

    <label>Mozo Asignado (opcional):</label>
    <select name="id_mozo">
        <option value="">-- Sin asignar --</option>
        <?php foreach ($mozos as $mozo): ?>
            <option value="<?= $mozo['id_usuario'] ?>" 
                    <?= ($mesa['id_mozo'] ?? $_POST['id_mozo'] ?? '') == $mozo['id_usuario'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($mozo['nombre_completo']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">
        <?= isset($mesa) ? 'Actualizar Mesa' : 'Crear Mesa' ?>
    </button>
    
    <a href="<?= url('mesas') ?>" class="button" style="margin-left: 10px;">Volver</a>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mejorar la interacción de los botones de estado
    const estadoOptions = document.querySelectorAll('.estado-option');
    
    estadoOptions.forEach(option => {
        const radio = option.querySelector('input[type="radio"]');
        
        // Agregar efecto hover
        option.addEventListener('mouseenter', function() {
            if (!radio.checked) {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.15)';
            }
        });
        
        option.addEventListener('mouseleave', function() {
            if (!radio.checked) {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            }
        });
        
        // Actualizar estilo cuando se selecciona
        radio.addEventListener('change', function() {
            estadoOptions.forEach(opt => {
                const optRadio = opt.querySelector('input[type="radio"]');
                if (optRadio.checked) {
                    opt.style.border = '2px solid #333';
                    opt.style.transform = 'translateY(-2px)';
                    opt.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                } else {
                    opt.style.border = '2px solid transparent';
                    opt.style.transform = 'translateY(0)';
                    opt.style.boxShadow = 'none';
                }
            });
        });
        
        // Aplicar estilo inicial si está seleccionado
        if (radio.checked) {
            option.style.border = '2px solid #333';
            option.style.transform = 'translateY(-2px)';
            option.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
        }
    });
});
</script>

<style>
/* Mejorar el estilo del formulario */
form {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-width: 600px;
    margin: 0 auto;
}

form label {
    display: block;
    margin-bottom: 0.3rem;
    font-weight: 600;
    color: var(--secondary);
    font-size: 0.9rem;
}

form input[type="number"],
form input[type="text"],
form select {
    width: 100%;
    padding: 0.6rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    margin-bottom: 0.7rem;
    background: white;
}

form input[type="number"]:focus,
form input[type="text"]:focus,
form select:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(161, 134, 111, 0.1);
}

form button[type="submit"] {
    background: var(--secondary);
    color: white;
    padding: 0.9rem 3.2rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-right: 1rem;
}

form button[type="submit"]:hover {
    background: #8a6f5a;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 134, 111, 0.3);
}

.button {
    background: #6c757d;
    color: white;
    padding: 0.6rem 1.2rem;
    text-decoration: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
}

.button:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.error {
    background: #f8d7da !important;
    color: #721c24 !important;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    font-weight: 500;
}

.success {
    background: #d4edda !important;
    color: #155724 !important;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    font-weight: 500;
}

/* ==== RESPONSIVE DESIGN ==== */
@media (max-width: 768px) {
    form {
        padding: 1rem;
        margin: 0.5rem;
        max-width: 100%;
    }
    
    form label {
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }
    
    form input[type="number"],
    form input[type="text"],
    form select {
        padding: 0.5rem;
        font-size: 0.9rem;
        margin-bottom: 0.6rem;
    }
    
    .estado-options {
        flex-direction: column;
        gap: 0.3rem !important;
    }
    
    .estado-option {
        padding: 6px 10px !important;
        font-size: 0.75rem !important;
        justify-content: center;
    }
    
    form button[type="submit"],
    .button {
        width: 100%;
        padding: 0.5rem 0.8rem;
        font-size: 0.9rem;
        margin-right: 0;
        margin-bottom: 0.4rem;
    }
    
    .button {
        margin-left: 0 !important;
        text-align: center;
    }
    
    h2 {
        font-size: 1.3rem;
        text-align: center;
        margin-bottom: 1rem;
    }
}

@media (max-width: 480px) {
    form {
        padding: 0.8rem;
        margin: 0.3rem;
    }
    
    form input[type="number"],
    form input[type="text"],
    form select {
        padding: 0.4rem;
        font-size: 0.85rem;
    }
    
    .estado-option {
        padding: 5px 8px !important;
        font-size: 0.7rem !important;
    }
    
    form button[type="submit"],
    .button {
        padding: 0.4rem 0.7rem;
        font-size: 0.85rem;
    }
    
    h2 {
        font-size: 1.1rem;
    }
}
</style>

