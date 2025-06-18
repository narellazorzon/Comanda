<?php
namespace App\Config;

use PDO;

class Database {
    private string $host     = 'localhost';
    private string $db       = 'comanda';
    private string $user     = 'root';
    private string $pass     = '';
    private string $charset  = 'utf8mb4';
    private ?PDO   $pdo      = null;

    public function getConnection(): PDO {
        if ($this->pdo) {
            return $this->pdo;
        }

        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            return $this->pdo;
        } catch (\PDOException $e) {
            throw new \Exception('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }
}
