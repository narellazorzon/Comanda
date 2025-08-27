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
            PDO::ATTR_TIMEOUT            => 5,  // Timeout de 5 segundos
            PDO::ATTR_PERSISTENT         => false, // No usar conexiones persistentes
        ];

        $maxRetries = 3;
        $retryDelay = 1; // segundos

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
                return $this->pdo;
            } catch (\PDOException $e) {
                $errorCode = $e->getCode();
                $errorMessage = $e->getMessage();
                
                // Si es el último intento o un error irrecuperable, lanzar excepción
                if ($attempt === $maxRetries || $this->isIrrecoverableError($errorCode)) {
                    $this->handleConnectionError($errorMessage, $errorCode);
                }
                
                // Esperar antes del siguiente intento
                sleep($retryDelay);
            }
        }
        
        // Esto nunca debería ejecutarse, pero por seguridad
        throw new \Exception('Error de conexión a la base de datos después de múltiples intentos');
    }

    private function isIrrecoverableError(string $errorCode): bool {
        // Errores que no se van a resolver con reintentos
        $irrecoverableErrors = [
            '1045', // Access denied
            '1049', // Unknown database
            '2005', // Unknown MySQL server host
        ];
        
        return in_array($errorCode, $irrecoverableErrors);
    }

    private function handleConnectionError(string $message, string $code): void {
        $errorMappings = [
            '2002' => 'No se puede conectar al servidor MySQL. Verifica que XAMPP/MySQL esté iniciado.',
            '1045' => 'Credenciales de base de datos incorrectas. Verifica usuario y contraseña.',
            '1049' => 'La base de datos "comanda" no existe. Ejecuta el script de creación primero.',
            '2005' => 'No se puede resolver el host de la base de datos.',
        ];

        $userMessage = $errorMappings[$code] ?? "Error de conexión a la base de datos: $message";
        
        throw new \Exception($userMessage);
    }
}
