<?php
// src/config/SystemConstants.php
namespace App\Config;

/**
 * Constantes del sistema para estados y valores fijos
 *
 * Esta clase centraliza todas las constantes utilizadas en toda la aplicación
 * para evitar el uso de "magic strings" y mantener consistencia.
 */
class SystemConstants
{
    // ===============================
    // Estados de Usuario
    // ===============================

    /** @var string Usuario activo */
    public const USER_STATUS_ACTIVE = 'activo';

    /** @var string Usuario inactivo */
    public const USER_STATUS_INACTIVE = 'inactivo';

    /** @var string Usuario eliminado (soft delete) */
    public const USER_STATUS_DELETED = 'eliminado';

    // ===============================
    // Roles de Usuario
    // ===============================

    /** @var string Rol de administrador */
    public const ROLE_ADMIN = 'administrador';

    /** @var string Rol de mozo */
    public const ROLE_MOZO = 'mozo';

    // ===============================
    // Estados de Mesa
    // ===============================

    /** @var string Mesa libre */
    public const TABLE_STATUS_FREE = 'libre';

    /** @var string Mesa ocupada */
    public const TABLE_STATUS_BUSY = 'ocupada';

    /** @var string Mesa reservada */
    public const TABLE_STATUS_RESERVED = 'reservada';

    // ===============================
    // Estados de Items de Carta
    // ===============================

    /** @var string Item disponible */
    public const ITEM_STATUS_AVAILABLE = 1;

    /** @var string Item no disponible */
    public const ITEM_STATUS_UNAVAILABLE = 0;

    // ===============================
    // Modos de Consumo
    // ===============================

    /** @var string Consumo en el local */
    public const CONSUMPTION_MODE_STAY = 'stay';

    /** @var string Consumo para llevar */
    public const CONSUMPTION_MODE_TAKEAWAY = 'takeaway';

    // ===============================
    // Formas de Pago
    // ===============================

    /** @var string Pago en efectivo */
    public const PAYMENT_CASH = 'efectivo';

    /** @var string Pago con tarjeta */
    public const PAYMENT_CARD = 'tarjeta';

    /** @var string Pago con transferencia */
    public const PAYMENT_TRANSFER = 'transferencia';

    /** @var string Pago con MercadoPago */
    public const PAYMENT_MERCADOPAGO = 'mercadopago';

    // ===============================
    // Categorías de Carta
    // ===============================

    /** @var string Categoría Entradas */
    public const CATEGORY_STARTERS = 'entradas';

    /** @var string Categoría Platos Principales */
    public const CATEGORY_MAINS = 'platos_principales';

    /** @var string Categoría Postres */
    public const CATEGORY_DESSERTS = 'postres';

    /** @var string Categoría Bebidas */
    public const CATEGORY_DRINKS = 'bebidas';

    /** @var string Categoría Cafés */
    public const CATEGORY_COFFEE = 'cafes';

    // ===============================
    // Estados de Llamados
    // ===============================

    /** @var string Llamado pendiente */
    public const CALL_STATUS_PENDING = 'pendiente';

    /** @var string Llamado atendido */
    public const CALL_STATUS_ATTENDED = 'atendido';

    /** @var string Llamado cancelado */
    public const CALL_STATUS_CANCELLED = 'cancelado';

    // ===============================
    // Límites del Sistema
    // ===============================

    /** @var int Máximo número de mesas */
    public const MAX_TABLES = 999;

    /** @var int Máximo número de caracteres para descripciones */
    public const MAX_DESCRIPTION_LENGTH = 1000;

    /** @var int Máximo número de caracteres para nombres */
    public const MAX_NAME_LENGTH = 100;

    /** @var float Máximo monto monetario */
    public const MAX_AMOUNT = 999999.99;

    /** @var int Mínima longitud de contraseña */
    public const MIN_PASSWORD_LENGTH = 6;

    // ===============================
    // Mensajes de Error Comunes
    // ===============================

    /** @var string Mensaje de error genérico */
    public const ERROR_GENERIC = 'Ha ocurrido un error. Por favor intente nuevamente.';

    /** @var string Mensaje de permiso denegado */
    public const ERROR_UNAUTHORIZED = 'No tiene permisos para realizar esta acción.';

    /** @var string Mensaje de recurso no encontrado */
    public const ERROR_NOT_FOUND = 'El recurso solicitado no existe.';

    /** @var string Mensaje de datos inválidos */
    public const ERROR_INVALID_DATA = 'Los datos proporcionados no son válidos.';

    // ===============================
    // Rutas del Sistema
    // ===============================

    /** @var string Ruta principal */
    public const ROUTE_HOME = 'home';

    /** @var string Ruta de login */
    public const ROUTE_LOGIN = 'login';

    /** @var string Ruta de logout */
    public const ROUTE_LOGOUT = 'logout';

    /** @var string Ruta de cliente */
    public const ROUTE_CLIENT = 'cliente';

    // ===============================
    // Configuración de Sesión
    // ===============================

    /** @var string Nombre de la sesión */
    public const SESSION_NAME = 'COMANDA_SESSION';

    /** @var int Tiempo de vida de la sesión en segundos */
    public const SESSION_LIFETIME = 7200; // 2 horas

    /** @var string Prefijo para claves de sesión */
    public const SESSION_PREFIX = 'comanda_';

    // ===============================
    // Expresiones Regulares
    // ===============================

    /** @var string Regex para validar emails */
    public const REGEX_EMAIL = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    /** @var string Regex para validar nombres de usuario */
    public const REGEX_USERNAME = '/^[a-zA-Z0-9_]+$/';

    /** @var string Regex para validar números de teléfono */
    public const REGEX_PHONE = '/^[0-9\s\-\+\(\)]+$/';

    // ===============================
    // Formatos de Fecha y Hora
    // ===============================

    /** @var string Formato de fecha para base de datos */
    public const DB_DATE_FORMAT = 'Y-m-d';

    /** @var string Formato de hora para base de datos */
    public const DB_TIME_FORMAT = 'H:i:s';

    /** @var string Formato de datetime para base de datos */
    public const DB_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /** @var string Formato de fecha para mostrar */
    public const DISPLAY_DATE_FORMAT = 'd/m/Y';

    /** @var string Formato de hora para mostrar */
    public const DISPLAY_TIME_FORMAT = 'H:i';

    // ===============================
    // Códigos HTTP
    // ===============================

    /** @var int OK */
    public const HTTP_OK = 200;

    /** @var int Created */
    public const HTTP_CREATED = 201;

    /** @var int Bad Request */
    public const HTTP_BAD_REQUEST = 400;

    /** @var int Unauthorized */
    public const HTTP_UNAUTHORIZED = 401;

    /** @var int Forbidden */
    public const HTTP_FORBIDDEN = 403;

    /** @var int Not Found */
    public const HTTP_NOT_FOUND = 404;

    /** @var int Internal Server Error */
    public const HTTP_INTERNAL_ERROR = 500;

    // ===============================
    // Métodos Utilitarios
    // ===============================

    /**
     * Obtiene todos los roles válidos del sistema
     *
     * @return array Lista de roles
     */
    public static function getValidRoles(): array
    {
        return [self::ROLE_ADMIN, self::ROLE_MOZO];
    }

    /**
     * Obtiene todos los estados de usuario válidos
     *
     * @return array Lista de estados
     */
    public static function getValidUserStatuses(): array
    {
        return [self::USER_STATUS_ACTIVE, self::USER_STATUS_INACTIVE, self::USER_STATUS_DELETED];
    }

    /**
     * Obtiene todos los estados de mesa válidos
     *
     * @return array Lista de estados
     */
    public static function getValidTableStatuses(): array
    {
        return [self::TABLE_STATUS_FREE, self::TABLE_STATUS_BUSY, self::TABLE_STATUS_RESERVED];
    }

    /**
     * Obtiene todas las categorías de carta válidas
     *
     * @return array Lista de categorías
     */
    public static function getValidCategories(): array
    {
        return [
            self::CATEGORY_STARTERS,
            self::CATEGORY_MAINS,
            self::CATEGORY_DESSERTS,
            self::CATEGORY_DRINKS,
            self::CATEGORY_COFFEE
        ];
    }

    /**
     * Obtiene todas las formas de pago válidas
     *
     * @return array Lista de formas de pago
     */
    public static function getValidPaymentMethods(): array
    {
        return [
            self::PAYMENT_CASH,
            self::PAYMENT_CARD,
            self::PAYMENT_TRANSFER,
            self::PAYMENT_MERCADOPAGO
        ];
    }

    /**
     * Verifica si un rol es válido
     *
     * @param string $role Rol a verificar
     * @return bool True si es válido
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::getValidRoles());
    }

    /**
     * Verifica si un estado de usuario es válido
     *
     * @param string $status Estado a verificar
     * @return bool True si es válido
     */
    public static function isValidUserStatus(string $status): bool
    {
        return in_array($status, self::getValidUserStatuses());
    }

    /**
     * Verifica si un estado de mesa es válido
     *
     * @param string $status Estado a verificar
     * @return bool True si es válido
     */
    public static function isValidTableStatus(string $status): bool
    {
        return in_array($status, self::getValidTableStatuses());
    }

    /**
     * Verifica si una categoría es válida
     *
     * @param string $category Categoría a verificar
     * @return bool True si es válida
     */
    public static function isValidCategory(string $category): bool
    {
        return in_array($category, self::getValidCategories());
    }

    /**
     * Verifica si una forma de pago es válida
     *
     * @param string $paymentMethod Forma de pago a verificar
     * @return bool True si es válida
     */
    public static function isValidPaymentMethod(string $paymentMethod): bool
    {
        return in_array($paymentMethod, self::getValidPaymentMethods());
    }
}