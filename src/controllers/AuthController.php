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
            // redirige con mensaje de error
            header('Location: login.php?error=' . urlencode('Completa todos los campos'));
            exit;
        }

        $usuario = Usuario::findByEmail($email);
        if ($usuario && password_verify($pass, $usuario['contrasenia'])) {
            $_SESSION['user'] = $usuario;
            header('Location: index.php');
            exit;
        } else {
            header('Location: login.php?error=' . urlencode('Credenciales inválidas'));
            exit;
        }
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
