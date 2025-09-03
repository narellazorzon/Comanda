<?php
// src/controllers/MozoController.php
namespace App\Controllers;

use App\Models\Usuario;

// Incluir helpers para las URLs
require_once __DIR__ . '/../config/helpers.php';

class MozoController {
    protected static function authorize() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            header('Location: ' . url('unauthorized'));
            exit;
        }
    }

    public static function index() {
        self::authorize();
        $mozos = Usuario::allByRole('mozo');
        include __DIR__ . '/../views/mozos/index.php';
    }

    public static function create() {
        self::authorize();
        
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id > 0) {
            // Es una actualización
            self::update($id);
        } else {
            // Es una creación nueva
            // Validar que el email no esté duplicado
            if (Usuario::emailExists($_POST['email'])) {
                header('Location: ' . url('mozos/create', ['error' => 'El email ya está en uso por otro usuario']));
                exit;
            }
            
            $data = $_POST;
            $data['rol'] = 'mozo';
            $data['contrasenia'] = password_hash($data['contrasenia'], PASSWORD_DEFAULT);
            Usuario::create($data);
            
            // Redirección POST-REDIRECT-GET para evitar reenvío de formulario
            header('Location: ' . url('mozos', ['success' => 'Mozo creado exitosamente']));
            exit;
        }
    }

    public static function update(int $id) {
        self::authorize();
        
        $mozo = Usuario::find($id);
        if (!$mozo || $mozo['rol'] !== 'mozo') {
            header('Location: ' . url('mozos', ['error' => 'Mozo no encontrado']));
            exit;
        }

        // Validar que el email no esté duplicado
        if (Usuario::emailExists($_POST['email'], $id)) {
            header('Location: ' . url('mozos/edit', ['id' => $id, 'error' => 'El email ya está en uso por otro usuario']));
            exit;
        }

        // Preparar datos para actualización
        $data = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'email' => $_POST['email'],
            'estado' => $_POST['estado'] ?? 'activo'
        ];

        // Solo incluir contraseña si se proporcionó
        if (!empty($_POST['contrasenia'])) {
            $data['contrasenia'] = $_POST['contrasenia'];
        }

        if (Usuario::update($id, $data)) {
            header('Location: ' . url('mozos', ['success' => 'Mozo actualizado exitosamente']));
        } else {
            header('Location: ' . url('mozos', ['error' => 'Error al actualizar el mozo']));
        }
        exit;
    }

    public static function delete() {
        self::authorize();
        $id = (int) ($_GET['delete'] ?? 0);
        if ($id > 0) {
            Usuario::delete($id);
        }
        header('Location: ' . url('mozos'));
        exit;
    }
}
