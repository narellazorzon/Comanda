<?php
namespace App\Config;

/**
 * Clase centralizada para validaciones robustas de entrada
 * Proporciona métodos seguros para validar diferentes tipos de datos
 */
class Validator 
{
    /**
     * Valida formato de email
     * @param string $email Email a validar
     * @return bool True si es válido
     */
    public static function email(string $email): bool 
    {
        $email = trim($email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valida que sea un entero positivo
     * @param mixed $value Valor a validar
     * @param int $min Valor mínimo (opcional)
     * @param int $max Valor máximo (opcional)
     * @return bool True si es válido
     */
    public static function positiveInteger($value, int $min = 1, int $max = PHP_INT_MAX): bool 
    {
        if (!is_numeric($value)) return false;
        $int_value = (int) $value;
        return $int_value >= $min && $int_value <= $max && $int_value == $value;
    }
    
    /**
     * Valida longitud de string
     * @param string $text Texto a validar
     * @param int $min_length Longitud mínima
     * @param int $max_length Longitud máxima
     * @return bool True si es válido
     */
    public static function stringLength(string $text, int $min_length = 1, int $max_length = 255): bool 
    {
        $length = mb_strlen(trim($text), 'UTF-8');
        return $length >= $min_length && $length <= $max_length;
    }
    
    /**
     * Valida que contenga solo caracteres alfanuméricos y espacios
     * @param string $text Texto a validar
     * @return bool True si es válido
     */
    public static function alphanumericSpaces(string $text): bool 
    {
        return preg_match('/^[a-zA-ZÀ-ÿ0-9\s\.\-\_]+$/u', trim($text));
    }
    
    /**
     * Valida precio/decimal positivo
     * @param mixed $value Valor a validar
     * @param int $decimals Número máximo de decimales
     * @return bool True si es válido
     */
    public static function positiveDecimal($value, int $decimals = 2): bool 
    {
        if (!is_numeric($value)) return false;
        $float_value = (float) $value;
        if ($float_value <= 0) return false;
        
        // Verificar decimales
        $decimal_places = strlen(substr(strrchr($value, "."), 1));
        return $decimal_places <= $decimals;
    }
    
    /**
     * Valida enum values
     * @param string $value Valor a validar
     * @param array $allowed_values Valores permitidos
     * @return bool True si es válido
     */
    public static function enumValue(string $value, array $allowed_values): bool 
    {
        return in_array($value, $allowed_values, true);
    }
    
    /**
     * Valida contraseña fuerte
     * @param string $password Contraseña a validar
     * @param int $min_length Longitud mínima
     * @return bool True si es válida
     */
    public static function strongPassword(string $password, int $min_length = 6): bool 
    {
        if (strlen($password) < $min_length) return false;
        
        // Al menos una letra y un número
        if (!preg_match('/[A-Za-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        
        return true;
    }
    
    /**
     * Sanitiza string para prevenir XSS
     * @param string $input String a sanitizar
     * @return string String sanitizado
     */
    public static function sanitizeString(string $input): string 
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valida y sanitiza input de forma combinada
     * @param mixed $input Valor de entrada
     * @param string $type Tipo de validación
     * @param array $options Opciones adicionales
     * @return array ['valid' => bool, 'value' => mixed, 'error' => string]
     */
    public static function validateInput($input, string $type, array $options = []): array 
    {
        $result = ['valid' => false, 'value' => $input, 'error' => ''];
        
        switch ($type) {
            case 'email':
                if (self::email($input)) {
                    $result['valid'] = true;
                    $result['value'] = trim(strtolower($input));
                } else {
                    $result['error'] = 'Formato de email inválido';
                }
                break;
                
            case 'positive_integer':
                $min = $options['min'] ?? 1;
                $max = $options['max'] ?? PHP_INT_MAX;
                if (self::positiveInteger($input, $min, $max)) {
                    $result['valid'] = true;
                    $result['value'] = (int) $input;
                } else {
                    $result['error'] = "Debe ser un número entero entre {$min} y {$max}";
                }
                break;
                
            case 'string':
                $min_len = $options['min_length'] ?? 1;
                $max_len = $options['max_length'] ?? 255;
                if (self::stringLength($input, $min_len, $max_len)) {
                    if (self::alphanumericSpaces($input)) {
                        $result['valid'] = true;
                        $result['value'] = self::sanitizeString($input);
                    } else {
                        $result['error'] = 'Solo se permiten letras, números, espacios y caracteres básicos';
                    }
                } else {
                    $result['error'] = "Debe tener entre {$min_len} y {$max_len} caracteres";
                }
                break;
                
            case 'price':
                $decimals = $options['decimals'] ?? 2;
                if (self::positiveDecimal($input, $decimals)) {
                    $result['valid'] = true;
                    $result['value'] = round((float) $input, $decimals);
                } else {
                    $result['error'] = 'Debe ser un precio válido mayor a 0';
                }
                break;
                
            case 'enum':
                $allowed = $options['values'] ?? [];
                if (self::enumValue($input, $allowed)) {
                    $result['valid'] = true;
                    $result['value'] = $input;
                } else {
                    $result['error'] = 'Valor no permitido';
                }
                break;
                
            case 'password':
                $min_len = $options['min_length'] ?? 6;
                if (self::strongPassword($input, $min_len)) {
                    $result['valid'] = true;
                    $result['value'] = $input; // No sanitizar contraseñas
                } else {
                    $result['error'] = "Contraseña debe tener al menos {$min_len} caracteres, con letras y números";
                }
                break;
                
            default:
                $result['error'] = 'Tipo de validación desconocido';
        }
        
        return $result;
    }
    
    /**
     * Valida múltiples campos de una vez
     * @param array $data Datos a validar ['campo' => valor]
     * @param array $rules Reglas ['campo' => ['type' => 'tipo', 'options' => []]]
     * @return array ['valid' => bool, 'data' => array, 'errors' => array]
     */
    public static function validateMultiple(array $data, array $rules): array 
    {
        $result = ['valid' => true, 'data' => [], 'errors' => []];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';
            $type = $rule['type'] ?? 'string';
            $options = $rule['options'] ?? [];
            
            $validation = self::validateInput($value, $type, $options);
            
            if ($validation['valid']) {
                $result['data'][$field] = $validation['value'];
            } else {
                $result['valid'] = false;
                $result['errors'][$field] = $validation['error'];
            }
        }
        
        return $result;
    }
}