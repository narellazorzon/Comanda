<?php
// src/config/PedidoStateManager.php
namespace App\Config;

/**
 * Gestor centralizado de estados y transiciones de pedidos
 *
 * Esta clase gestiona todas las reglas de negocio relacionadas con los estados
 * de los pedidos, asegurando que las transiciones sean válidas y consistentes
 * en toda la aplicación.
 */
class PedidoStateManager
{
    /**
     * Estados posibles para un pedido
     */
    public const STATUS_PENDING = 'pendiente';
    public const STATUS_PREPARING = 'en_preparacion';
    public const STATUS_READY = 'listo_para_servir';
    public const STATUS_SERVED = 'servido';
    public const STATUS_BILLING = 'cuenta';
    public const STATUS_CLOSED = 'cerrado';
    public const STATUS_CANCELLED = 'cancelado';

    /**
     * Transiciones válidas entre estados
     * [estado_actual => [estados_permitidos]]
     */
    private const VALID_TRANSITIONS = [
        self::STATUS_PENDING => [
            self::STATUS_PREPARING,
            self::STATUS_CANCELLED,
            self::STATUS_CLOSED
        ],
        self::STATUS_PREPARING => [
            self::STATUS_PENDING,
            self::STATUS_READY,
            self::STATUS_CANCELLED,
            self::STATUS_CLOSED
        ],
        self::STATUS_READY => [
            self::STATUS_PREPARING,
            self::STATUS_SERVED,
            self::STATUS_CANCELLED,
            self::STATUS_CLOSED
        ],
        self::STATUS_SERVED => [
            self::STATUS_BILLING,
            self::STATUS_CLOSED
        ],
        self::STATUS_BILLING => [
            self::STATUS_SERVED,
            self::STATUS_CLOSED
        ],
        self::STATUS_CANCELLED => [
            self::STATUS_PENDING
        ],
        self::STATUS_CLOSED => [] // Estado final, no permite transiciones
    ];

    /**
     * Estados que indican que el pedido está activo
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_SERVED,
        self::STATUS_BILLING
    ];

    /**
     * Estados que indican que el pedido está en cocina
     */
    public const KITCHEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PREPARING,
        self::STATUS_READY
    ];

    /**
     * Estados que permiten modificar items del pedido
     */
    public const EDITABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PREPARING
    ];

    /**
     * Estados que permiten cancelar el pedido
     */
    public const CANCELLABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PREPARING,
        self::STATUS_READY
    ];

    /**
     * Verifica si una transición de estado es válida
     *
     * @param string $fromState Estado actual
     * @param string $toState Estado deseado
     * @return bool True si la transición es válida
     */
    public static function canTransition(string $fromState, string $toState): bool
    {
        if (!isset(self::VALID_TRANSITIONS[$fromState])) {
            return false;
        }

        return in_array($toState, self::VALID_TRANSITIONS[$fromState]);
    }

    /**
     * Obtiene los estados posibles a los que puede transicionar desde un estado dado
     *
     * @param string $currentState Estado actual
     * @return array Estados permitidos
     */
    public static function getPossibleTransitions(string $currentState): array
    {
        return self::VALID_TRANSITIONS[$currentState] ?? [];
    }

    /**
     * Verifica si un estado está activo
     *
     * @param string $status Estado a verificar
     * @return bool True si el estado está activo
     */
    public static function isActive(string $status): bool
    {
        return in_array($status, self::ACTIVE_STATUSES);
    }

    /**
     * Verifica si un pedido está en cocina
     *
     * @param string $status Estado a verificar
     * @return bool True si está en cocina
     */
    public static function isInKitchen(string $status): bool
    {
        return in_array($status, self::KITCHEN_STATUSES);
    }

    /**
     * Verifica si un pedido se puede editar
     *
     * @param string $status Estado a verificar
     * @return bool True si se puede editar
     */
    public static function isEditable(string $status): bool
    {
        return in_array($status, self::EDITABLE_STATUSES);
    }

    /**
     * Verifica si un pedido se puede cancelar
     *
     * @param string $status Estado a verificar
     * @return bool True si se puede cancelar
     */
    public static function isCancellable(string $status): bool
    {
        return in_array($status, self::CANCELLABLE_STATUSES);
    }

    /**
     * Verifica si un pedido está cerrado o cancelado
     *
     * @param string $status Estado a verificar
     * @return bool True si está finalizado
     */
    public static function isFinal(string $status): bool
    {
        return $status === self::STATUS_CLOSED || $status === self::STATUS_CANCELLED;
    }

    /**
     * Obtiene el estado inicial para un nuevo pedido
     *
     * @return string Estado inicial
     */
    public static function getInitialState(): string
    {
        return self::STATUS_PENDING;
    }

    /**
     * Obtiene todos los estados disponibles
     *
     * @return array Lista de estados
     */
    public static function getAllStatuses(): array
    {
        return array_keys(self::VALID_TRANSITIONS);
    }

    /**
     * Obtiene una representación legible del estado
     *
     * @param string $status Estado
     * @return string Representación legible
     */
    public static function getReadableStatus(string $status): string
    {
        $statusMap = [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_PREPARING => 'En Preparación',
            self::STATUS_READY => 'Listo para Servir',
            self::STATUS_SERVED => 'Servido',
            self::STATUS_BILLING => 'Cuenta Solicitada',
            self::STATUS_CLOSED => 'Cerrado',
            self::STATUS_CANCELLED => 'Cancelado'
        ];

        return $statusMap[$status] ?? ucfirst($status);
    }

    /**
     * Obtiene el color asociado a un estado para UI
     *
     * @param string $status Estado
     * @return string Código de color CSS
     */
    public static function getStatusColor(string $status): string
    {
        $colorMap = [
            self::STATUS_PENDING => '#ffc107', // Amarillo
            self::STATUS_PREPARING => '#17a2b8', // Cyan
            self::STATUS_READY => '#28a745', // Verde
            self::STATUS_SERVED => '#6f42c1', // Púrpura
            self::STATUS_BILLING => '#fd7e14', // Naranja
            self::STATUS_CLOSED => '#6c757d', // Gris
            self::STATUS_CANCELLED => '#dc3545' // Rojo
        ];

        return $colorMap[$status] ?? '#6c757d';
    }

    /**
     * Valida un estado
     *
     * @param string $status Estado a validar
     * @return bool True si es un estado válido
     */
    public static function isValidStatus(string $status): bool
    {
        return array_key_exists($status, self::VALID_TRANSITIONS);
    }

    /**
     * Genera un array con información de estados para JavaScript
     *
     * @return array Información de estados
     */
    public static function getJavaScriptData(): array
    {
        $jsData = [
            'transitions' => [],
            'statusColors' => [],
            'statusNames' => []
        ];

        foreach (self::VALID_TRANSITIONS as $from => $toStates) {
            $jsData['transitions'][$from] = $toStates;
        }

        foreach (self::getAllStatuses() as $status) {
            $jsData['statusColors'][$status] = self::getStatusColor($status);
            $jsData['statusNames'][$status] = self::getReadableStatus($status);
        }

        return $jsData;
    }
}