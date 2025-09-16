<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOStatement;

/**
 * Clase base para todos los modelos
 * Centraliza la gestión de conexiones a base de datos
 */
abstract class BaseModel {
    /**
     * Conexión única compartida (Singleton)
     */
    private static ?PDO $connection = null;

    /**
     * Obtiene la conexión a la base de datos (Singleton)
     */
    protected static function getDb(): PDO {
        if (self::$connection === null) {
            self::$connection = (new Database)->getConnection();
        }
        return self::$connection;
    }

    /**
     * Ejecuta una consulta preparada
     */
    protected static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Ejecuta una consulta y devuelve todos los resultados
     */
    protected static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ejecuta una consulta y devuelve un solo resultado
     */
    protected static function fetchOne(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Ejecuta una consulta y devuelve un solo valor
     */
    protected static function fetchColumn(string $sql, array $params = [], int $column = 0) {
        return self::query($sql, $params)->fetchColumn($column);
    }

    /**
     * Ejecuta una consulta INSERT y devuelve el último ID insertado
     */
    protected static function insert(string $table, array $data): int {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        self::query($sql, $data);
        return (int) self::getDb()->lastInsertId();
    }

    /**
     * Ejecuta una consulta UPDATE
     */
    protected static function updateTable(string $table, array $data, string $where, array $whereParams = []): int {
        $setPairs = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setPairs[] = "$column = :set_$column";
            $params["set_$column"] = $value;
        }

        // Agregar parámetros WHERE
        foreach ($whereParams as $key => $value) {
            $params[$key] = $value;
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setPairs),
            $where
        );

        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Ejecuta una consulta DELETE
     */
    protected static function deleteFrom(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Inicia una transacción
     */
    protected static function beginTransaction(): void {
        self::getDb()->beginTransaction();
    }

    /**
     * Confirma una transacción
     */
    protected static function commit(): void {
        self::getDb()->commit();
    }

    /**
     * Revierte una transacción
     */
    protected static function rollback(): void {
        self::getDb()->rollBack();
    }

    /**
     * Verifica si existe un registro
     */
    protected static function exists(string $table, string $where, array $params = []): bool {
        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        return (bool) self::fetchColumn($sql, $params);
    }

    /**
     * Cuenta registros
     */
    protected static function count(string $table, string $where = '1=1', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        return (int) self::fetchColumn($sql, $params);
    }

    /**
     * Escapa caracteres especiales para LIKE
     */
    protected static function escapeLike(string $value): string {
        return str_replace(['%', '_'], ['\%', '\_'], $value);
    }
}