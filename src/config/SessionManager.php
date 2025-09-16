<?php
namespace App\Config;

/**
 * Gestor centralizado de sesiones
 * Elimina la duplicación de código de manejo de sesiones
 */
class SessionManager {
    /**
     * Asegura que la sesión esté iniciada
     */
    public static function ensureStarted(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica si hay un usuario autenticado
     */
    public static function isAuthenticated(): bool {
        self::ensureStarted();
        return !empty($_SESSION['user']);
    }

    /**
     * Obtiene el usuario actual de la sesión
     */
    public static function getCurrentUser(): ?array {
        self::ensureStarted();
        return $_SESSION['user'] ?? null;
    }

    /**
     * Obtiene el rol del usuario actual
     */
    public static function getUserRole(): ?string {
        $user = self::getCurrentUser();
        return $user['rol'] ?? null;
    }

    /**
     * Verifica si el usuario tiene uno de los roles permitidos
     */
    public static function hasRole(array $allowedRoles): bool {
        $userRole = self::getUserRole();
        return $userRole && in_array($userRole, $allowedRoles);
    }

    /**
     * Requiere autenticación y opcionalmente roles específicos
     */
    public static function requireAuth(array $allowedRoles = []): void {
        self::ensureStarted();

        if (!self::isAuthenticated()) {
            self::redirectToLogin();
        }

        if (!empty($allowedRoles) && !self::hasRole($allowedRoles)) {
            self::redirectToUnauthorized();
        }
    }

    /**
     * Requiere rol de administrador
     */
    public static function requireAdmin(): void {
        self::requireAuth(['administrador']);
    }

    /**
     * Requiere rol de mozo o administrador
     */
    public static function requireMozoOrAdmin(): void {
        self::requireAuth(['mozo', 'administrador']);
    }

    /**
     * Establece el usuario en la sesión
     */
    public static function setUser(array $userData): void {
        self::ensureStarted();
        $_SESSION['user'] = $userData;
    }

    /**
     * Destruye la sesión actual
     */
    public static function destroy(): void {
        self::ensureStarted();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Redirige al login
     */
    private static function redirectToLogin(): void {
        // Si es una petición AJAX, devolver JSON
        if (self::isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No está autenticado. Por favor, inicie sesión.']);
            exit;
        }

        header('Location: index.php?route=login');
        exit;
    }

    /**
     * Redirige a página de no autorizado
     */
    private static function redirectToUnauthorized(): void {
        // Si es una petición AJAX, devolver JSON
        if (self::isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para acceder a esta información']);
            exit;
        }

        header('Location: index.php?route=unauthorized');
        exit;
    }

    /**
     * Verifica si es una petición AJAX
     */
    private static function isAjaxRequest(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}