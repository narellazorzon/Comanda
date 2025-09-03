<?php
// src/config/helpers.php

/**
 * Obtiene la URL base del proyecto
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    return $protocol . '://' . $host . dirname($script_name);
}

/**
 * Genera una URL para una ruta específica
 */
function url($route = '', $params = []) {
    $base_url = getBaseUrl();
    $url = $base_url . '/index.php';
    
    if ($route) {
        $url .= '?route=' . $route;
    }
    
    // Agregar parámetros adicionales
    foreach ($params as $key => $value) {
        $url .= ($route || !empty($params)) ? '&' : '?';
        $url .= urlencode($key) . '=' . urlencode($value);
    }
    
    return $url;
}
?>
