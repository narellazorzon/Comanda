<?php
// src/config/BaseRepository.php
namespace App\Config;

use PDO;
use PDOException;

/**
 * Clase base para repositories de base de datos
 *
 * Esta clase proporciona funcionalidad común para todas las operaciones de base de datos,
 * reduciendo la duplicación de código y asegurando consistencia en el acceso a datos.
 */
abstract class BaseRepository
{
    /** @var PDO Instancia de conexión a la base de datos */
    protected PDO $db;

    /** @var string Nombre de la tabla principal */
    protected string $table;

    /** @var string Nombre de la clave primaria */
    protected string $primaryKey = 'id';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Ejecuta una consulta SQL con parámetros
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros para la consulta
     * @return array Resultados de la consulta
     * @throws PDOException Si hay un error en la consulta
     */
    protected function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ejecuta una consulta que retorna un solo registro
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros para la consulta
     * @return array|null Registro encontrado o null
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params);
        return $result[0] ?? null;
    }

    /**
     * Ejecuta una consulta que retorna un solo valor (escalar)
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros para la consulta
     * @return mixed Valor escalar
     */
    protected function queryScalar(string $sql, array $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Busca un registro por su ID
     *
     * @param int $id ID del registro
     * @return array|null Registro encontrado o null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->queryOne($sql, [$id]);
    }

    /**
     * Busca registros por un campo específico
     *
     * @param string $field Nombre del campo
     * @param mixed $value Valor a buscar
     * @return array Registros encontrados
     */
    public function findBy(string $field, $value): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        return $this->query($sql, [$value]);
    }

    /**
     * Busca un registro por un campo específico
     *
     * @param string $field Nombre del campo
     * @param mixed $value Valor a buscar
     * @return array|null Registro encontrado o null
     */
    public function findOneBy(string $field, $value): ?array
    {
        $result = $this->findBy($field, $value);
        return $result[0] ?? null;
    }

    /**
     * Obtiene todos los registros con opción de filtro WHERE
     *
     * @param string $where Condición WHERE (opcional)
     * @param array $params Parámetros para la condición
     * @param string $orderBy Cláusula ORDER BY
     * @param int $limit Límite de registros
     * @return array Todos los registros
     */
    public function findAll(string $where = '', array $params = [], string $orderBy = '', int $limit = 0): array
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($where) {
            $sql .= " WHERE {$where}";
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->query($sql, $params);
    }

    /**
     * Cuenta registros que coinciden con un criterio
     *
     * @param string $where Condición WHERE (opcional)
     * @param array $params Parámetros para la condición
     * @return int Número de registros
     */
    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";

        if ($where) {
            $sql .= " WHERE {$where}";
        }

        return (int) $this->queryScalar($sql, $params);
    }

    /**
     * Verifica si existe un registro con ciertos criterios
     *
     * @param string $where Condición WHERE
     * @param array $params Parámetros para la condición
     * @return bool True si existe al menos un registro
     */
    public function exists(string $where, array $params = []): bool
    {
        return $this->count($where, $params) > 0;
    }

    /**
     * Inserta un nuevo registro
     *
     * @param array $data Datos a insertar
     * @return int ID del nuevo registro
     * @throws PDOException Si hay un error
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $values = array_fill(0, count($columns), '?');
        $placeholders = implode(', ', $values);
        $columnList = implode(', ', $columns);

        $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES ({$placeholders})";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Insert error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza un registro
     *
     * @param int $id ID del registro a actualizar
     * @param array $data Datos a actualizar
     * @return int Número de filas afectadas
     */
    public function update(int $id, array $data): int
    {
        $setParts = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $params[] = $value;
        }

        $setClause = implode(', ', $setParts);
        $params[] = $id;

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza registros por una condición WHERE
     *
     * @param array $data Datos a actualizar
     * @param string $where Condición WHERE
     * @param array $params Parámetros para la condición
     * @return int Número de filas afectadas
     */
    public function updateWhere(array $data, string $where, array $params = []): int
    {
        $setParts = [];
        $allParams = [];

        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $allParams[] = $value;
        }

        $setClause = implode(', ', $setParts);
        $allParams = array_merge($allParams, $params);

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$where}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($allParams);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("UpdateWhere error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un registro
     *
     * @param int $id ID del registro a eliminar
     * @return int Número de filas afectadas
     */
    public function delete(int $id): int
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Delete error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina registros por una condición WHERE
     *
     * @param string $where Condición WHERE
     * @param array $params Parámetros para la condición
     * @return int Número de filas afectadas
     */
    public function deleteWhere(string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$this->table} WHERE {$where}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("DeleteWhere error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Inicia una transacción
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Confirma una transacción
     */
    public function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Revierte una transacción
     */
    public function rollback(): void
    {
        $this->db->rollBack();
    }

    /**
     * Ejecuta una función dentro de una transacción
     *
     * @param callable $callback Función a ejecutar
     * @return mixed Resultado de la función
     * @throws \Exception Si hay un error
     */
    public function transaction(callable $callback)
    {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Escapa un nombre de columna o tabla para prevenir SQL injection
     *
     * @param string $identifier Identificador a escapar
     * @return string Identificador escapado
     */
    protected function escapeIdentifier(string $identifier): string
    {
        return "`" . str_replace("`", "``", $identifier) . "`";
    }

    /**
     * Construye una cláusula IN con parámetros seguros
     *
     * @param array $values Valores para la cláusula IN
     * @return string Cláusula IN con placeholders
     */
    protected function buildInClause(array $values): string
    {
        if (empty($values)) {
            return "IN (NULL)";
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        return "IN ({$placeholders})";
    }

    /**
     * Obtiene el último error de la base de datos
     *
     * @return array Información del error
     */
    public function getLastError(): array
    {
        return [
            'code' => $this->db->errorCode(),
            'info' => $this->db->errorInfo()
        ];
    }
}