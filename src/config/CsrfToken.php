<?php
namespace App\Config;

/**
 * Clase para generar y validar tokens CSRF
 * Protege contra ataques de Cross-Site Request Forgery
 */
class CsrfToken 
{
    /**
     * Genera un token CSRF único y lo almacena en sesión
     * @return string Token CSRF generado
     */
    public static function generate(): string 
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Generar token aleatorio seguro
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return $token;
    }
    
    /**
     * Valida si el token CSRF proporcionado es válido
     * @param string $token Token a validar
     * @return bool True si es válido, false si no
     */
    public static function validate(string $token): bool 
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Verificar que existe el token en sesión
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Comparación segura contra timing attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Obtiene el token CSRF actual de la sesión
     * @return string|null Token actual o null si no existe
     */
    public static function get(): ?string 
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        return $_SESSION['csrf_token'] ?? null;
    }
    
    /**
     * Genera el campo HTML oculto con el token CSRF
     * @return string HTML del input hidden
     */
    public static function field(): string 
    {
        $token = self::generate();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Limpia el token CSRF de la sesión
     */
    public static function clear(): void 
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        unset($_SESSION['csrf_token']);
    }
}