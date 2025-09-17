<?php
/**
 * Funciones de ayuda para el sistema
 */

/**
 * Obtiene la URL base del proyecto
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];

    // Si el script es index.php, solo usar el directorio
    if (basename($script_name) === 'index.php') {
        return $protocol . '://' . $host . dirname($script_name);
    }

    // En cualquier otro caso, usar el path completo sin el nombre del archivo
    return $protocol . '://' . $host . dirname($script_name);
}

/**
 * Genera una URL para una ruta específica
 */
function url($route = '', $params = []) {
    // Simplemente usar index.php desde el directorio actual
    $url = 'index.php';

    if ($route) {
        $url .= '?route=' . $route;
    }

    // Agregar parámetros adicionales
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $url .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }

    return $url;
}