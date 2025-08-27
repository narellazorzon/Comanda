<?php
// src/controllers/MozoController.php
namespace App\Controllers;

use App\Models\Usuario;

class MozoController {
    protected static function authorize() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            header('Location: unauthorized.php');
            exit;
        }
    }

    public static function index() {
        self::authorize();
        $mozos = Usuario::allByRole('mozo');
        include __DIR__ . '/../../public/cme_mozos.php';
    }

    public static function create() {
        self::authorize();
        
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id > 0) {
            // Es una actualización
            self::update($id);
        } else {
            // Es una creación nueva
            $_POST['rol'] = 'mozo';
            $_POST['contrasenia'] = password_hash($_POST['contrasenia'], PASSWORD_DEFAULT);
            Usuario::create($_POST);
            header('Location: cme_mozos.php?success=' . urlencode('Mozo creado exitosamente'));
            exit;
        }
    }

    public static function update(int $id) {
        self::authorize();
        
        $mozo = Usuario::find($id);
        if (!$mozo || $mozo['rol'] !== 'mozo') {
            header('Location: cme_mozos.php?error=' . urlencode('Mozo no encontrado'));
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
            header('Location: cme_mozos.php?success=' . urlencode('Mozo actualizado exitosamente'));
        } else {
            header('Location: cme_mozos.php?error=' . urlencode('Error al actualizar el mozo'));
        }
        exit;
    }

    public static function delete() {
        self::authorize();
        $id = (int) ($_GET['delete'] ?? 0);
        if ($id > 0) {
            Usuario::delete($id);
        }
        header('Location: cme_mozos.php');
        exit;
    }
}
