<?php
// src/config/ValidationService.php
namespace App\Config;

/**
 * Servicio centralizado para validaciones comunes en el sistema
 *
 * Esta clase proporciona métodos estáticos para validar diferentes tipos de datos
 * de manera consistente en toda la aplicación, reduciendo la duplicación de código.
 */
class ValidationService
{
    /**
     * Valida un email según formato estándar
     *
     * @param string $email Email a validar
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateEmail(string $email): array
    {
        if (empty($email)) {
            return ['valid' => false, 'message' => 'El email es obligatorio'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'El formato del email no es válido'];
        }

        if (strlen($email) > 255) {
            return ['valid' => false, 'message' => 'El email no puede exceder los 255 caracteres'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Valida una contraseña según requisitos de seguridad
     *
     * @param string $password Contraseña a validar
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validatePassword(string $password): array
    {
        if (empty($password)) {
            return ['valid' => false, 'message' => 'La contraseña es obligatoria'];
        }

        if (strlen($password) < 6) {
            return ['valid' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Valida un nombre de usuario
     *
     * @param string $username Nombre de usuario a validar
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateUsername(string $username): array
    {
        if (empty($username)) {
            return ['valid' => false, 'message' => 'El nombre de usuario es obligatorio'];
        }

        if (strlen($username) < 3) {
            return ['valid' => false, 'message' => 'El nombre de usuario debe tener al menos 3 caracteres'];
        }

        if (strlen($username) > 50) {
            return ['valid' => false, 'message' => 'El nombre de usuario no puede exceder los 50 caracteres'];
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'message' => 'El nombre de usuario solo puede contener letras, números y guiones bajos'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Valida un número de mesa
     *
     * @param int $number Número de mesa a validar
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateTableNumber(int $number): array
    {
        if ($number <= 0) {
            return ['valid' => false, 'message' => 'El número de mesa debe ser mayor a 0'];
        }

        if ($number > 999) {
            return ['valid' => false, 'message' => 'El número de mesa no puede exceder 999'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Valida un valor monetario
     *
     * @param float $amount Monto a validar
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateAmount(float $amount): array
    {
        if ($amount < 0) {
            return ['valid' => false, 'message' => 'El monto no puede ser negativo'];
        }

        if ($amount > 999999.99) {
            return ['valid' => false, 'message' => 'El monto no puede exceder $999,999.99'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Valida que un string no esté vacío y tenga longitud razonable
     *
     * @param string $value String a validar
     * @param string $fieldName Nombre del campo para mensajes
     * @param int $maxLength Longitud máxima permitida
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateString(string $value, string $fieldName, int $maxLength = 255): array
    {
        if (empty(trim($value))) {
            return ['valid' => false, 'message' => "El campo {$fieldName} es obligatorio"];
        }

        if (strlen($value) > $maxLength) {
            return ['valid' => false, 'message' => "El campo {$fieldName} no puede exceder {$maxLength} caracteres"];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Sanitiza un string para prevenir XSS
     *
     * @param string $string String a sanitizar
     * @return string String sanitizado
     */
    public static function sanitizeString(string $string): string
    {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida múltiples campos a la vez
     *
     * @param array $data Datos a validar ['campo' => 'valor']
     * @param array $rules Reglas de validación ['campo' => ['tipo', 'opciones']]
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    public static function validateMultiple(array $data, array $rules): array
    {
        $errors = [];
        $validData = [];

        foreach ($rules as $field => $rule) {
            $type = $rule['type'];
            $options = $rule['options'] ?? [];

            $value = $data[$field] ?? null;

            switch ($type) {
                case 'email':
                    $result = self::validateEmail($value);
                    break;

                case 'password':
                    $result = self::validatePassword($value);
                    break;

                case 'username':
                    $result = self::validateUsername($value);
                    break;

                case 'table_number':
                case 'positive_integer':
                    $intValue = (int) $value;
                    if ($type === 'table_number') {
                        $result = self::validateTableNumber($intValue);
                    } else {
                        $min = $options['min'] ?? 1;
                        $max = $options['max'] ?? PHP_INT_MAX;
                        $result = self::validatePositiveInteger($intValue, $min, $max);
                    }
                    break;

                case 'amount':
                    $floatValue = (float) $value;
                    $result = self::validateAmount($floatValue);
                    $validData[$field] = $floatValue;
                    break;

                case 'string':
                    $maxLength = $options['max_length'] ?? 255;
                    $result = self::validateString($value ?? '', $options['label'] ?? $field, $maxLength);
                    $validData[$field] = self::sanitizeString($value ?? '');
                    break;

                default:
                    $result = ['valid' => true, 'message' => ''];
            }

            if (!$result['valid']) {
                $errors[$field] = $result['message'];
            } elseif (!isset($validData[$field])) {
                $validData[$field] = $value;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validData
        ];
    }

    /**
     * Valida un entero positivo
     *
     * @param int $value Valor a validar
     * @param int $min Valor mínimo permitido
     * @param int $max Valor máximo permitido
     * @return array ['valid' => bool, 'message' => string]
     */
    private static function validatePositiveInteger(int $value, int $min, int $max): array
    {
        if ($value < $min) {
            return ['valid' => false, 'message' => "El valor debe ser mayor o igual a {$min}"];
        }

        if ($value > $max) {
            return ['valid' => false, 'message' => "El valor no puede exceder {$max}"];
        }

        return ['valid' => true, 'message' => ''];
    }
}