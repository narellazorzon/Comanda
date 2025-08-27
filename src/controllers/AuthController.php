<?php
namespace App\Controllers;
use App\Models\Usuario;

class AuthController {
    public static function login() {
        // Sólo arrancamos sesión si no está ya
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Sanitiza y comprueba que existan ambos campos
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password']  ?? '';
        
        if ($email === '' || $pass === '') {
            header('Location: login.php?error=' . urlencode('Por favor, completa todos los campos') . '&email=' . urlencode($email));
            exit;
        }

        // Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: login.php?error=' . urlencode('El formato del email no es válido') . '&email=' . urlencode($email));
            exit;
        }

        $usuario = Usuario::findByEmail($email);
        
        if (!$usuario) {
            // Usuario no encontrado
            header('Location: login.php?error=' . urlencode('No existe una cuenta con este email') . '&email=' . urlencode($email));
            exit;
        }
        
        if (!password_verify($pass, $usuario['contrasenia'])) {
            // Contraseña incorrecta
            header('Location: login.php?error=' . urlencode('La contraseña es incorrecta') . '&email=' . urlencode($email));
            exit;
        }
        
        if ($usuario['estado'] !== 'activo') {
            // Usuario inactivo
            header('Location: login.php?error=' . urlencode('Tu cuenta está desactivada. Contacta al administrador') . '&email=' . urlencode($email));
            exit;
        }

        // Login exitoso
        $_SESSION['user'] = $usuario;
        header('Location: index.php');
        exit;
    }

    public static function logout() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
