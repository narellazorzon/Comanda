<?php
namespace App\Config;

/**
 * Helper centralizado para manejo de respuestas HTTP
 * Elimina duplicación en redirects y respuestas JSON
 */
class ResponseHelper {
    /**
     * Envía una respuesta JSON
     */
    public static function json(array $data, int $statusCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Envía una respuesta JSON de éxito
     */
    public static function jsonSuccess($data = null, string $message = ''): void {
        $response = ['success' => true];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        self::json($response);
    }

    /**
     * Envía una respuesta JSON de error
     */
    public static function jsonError(string $error, int $statusCode = 400, array $additionalData = []): void {
        $response = array_merge(
            ['success' => false, 'error' => $error],
            $additionalData
        );

        self::json($response, $statusCode);
    }

    /**
     * Redirige a una ruta con mensaje opcional
     */
    public static function redirect(string $route, array $params = []): void {
        $url = self::buildUrl($route, $params);
        header("Location: $url");
        exit;
    }

    /**
     * Redirige con mensaje de éxito
     */
    public static function redirectWithSuccess(string $route, string $message): void {
        self::redirect($route, ['success' => $message]);
    }

    /**
     * Redirige con mensaje de error
     */
    public static function redirectWithError(string $route, string $message): void {
        self::redirect($route, ['error' => $message]);
    }

    /**
     * Redirige a la página de login
     */
    public static function redirectToLogin(): void {
        self::redirect('login');
    }

    /**
     * Redirige a la página de no autorizado
     */
    public static function redirectToUnauthorized(): void {
        self::redirect('unauthorized');
    }

    /**
     * Redirige a la página anterior o a una ruta por defecto
     */
    public static function redirectBack(string $defaultRoute = 'home'): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        if ($referer && self::isSafeUrl($referer)) {
            header("Location: $referer");
        } else {
            self::redirect($defaultRoute);
        }
        exit;
    }

    /**
     * Construye una URL con parámetros
     */
    private static function buildUrl(string $route, array $params = []): string {
        $base = "index.php?route=$route";

        if (!empty($params)) {
            $queryString = http_build_query($params);
            $base .= "&$queryString";
        }

        return $base;
    }

    /**
     * Verifica si una URL es segura para redirección
     */
    private static function isSafeUrl(string $url): bool {
        $parsed = parse_url($url);
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';

        // Solo permitir URLs del mismo host
        return isset($parsed['host']) && $parsed['host'] === $currentHost;
    }

    /**
     * Obtiene la URL base del proyecto
     */
    public static function getBaseUrl(): string {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = dirname($scriptName);

        return "$protocol://$host" . ($dir === '/' ? '' : $dir);
    }

    /**
     * Envía headers para prevenir caché
     */
    public static function noCache(): void {
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    }

    /**
     * Envía headers CORS
     */
    public static function cors(array $allowedOrigins = ['*']): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Max-Age: 86400');
        }

        // Si es una petición OPTIONS, terminar aquí
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}