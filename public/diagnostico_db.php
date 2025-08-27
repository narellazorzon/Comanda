<?php
// Página de diagnóstico para la base de datos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Base de Datos - Comanda</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #b8d4da; color: #0c5460; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnóstico de Base de Datos</h1>
        
        <h2>1. Verificación de Extensiones PHP</h2>
        <?php
        $requiredExtensions = ['pdo', 'pdo_mysql', 'mysqli'];
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<div class='status success'>✅ Extensión <code>$ext</code> está cargada</div>";
            } else {
                echo "<div class='status error'>❌ Extensión <code>$ext</code> NO está cargada</div>";
            }
        }
        ?>

        <h2>2. Verificación de Conexión MySQL</h2>
        <?php
        $host = 'localhost';
        $port = 3306;
        $user = 'root';
        $pass = '';
        $database = 'comanda';

        // Verificar si el puerto está abierto
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($connection) {
            echo "<div class='status success'>✅ Puerto MySQL ($port) está abierto y respondiendo</div>";
            fclose($connection);
        } else {
            echo "<div class='status error'>❌ No se puede conectar al puerto MySQL ($port)<br>";
            echo "Error: $errstr ($errno)</div>";
            echo "<div class='status warning'>⚠️ Posibles soluciones:<br>";
            echo "• Inicia XAMPP Control Panel<br>";
            echo "• Haz clic en 'Start' en el módulo MySQL<br>";
            echo "• Verifica que no haya otro servicio usando el puerto 3306</div>";
        }

        // Intentar conexión PDO
        try {
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            echo "<div class='status success'>✅ Conexión PDO al servidor MySQL exitosa</div>";

            // Verificar si la base de datos existe
            $stmt = $pdo->query("SHOW DATABASES LIKE '$database'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='status success'>✅ Base de datos '<code>$database</code>' existe</div>";
                
                // Conectar a la base de datos específica
                $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Verificar tablas
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($tables) > 0) {
                    echo "<div class='status success'>✅ Base de datos contiene " . count($tables) . " tablas</div>";
                    echo "<div class='status info'>📋 Tablas encontradas: " . implode(', ', $tables) . "</div>";
                } else {
                    echo "<div class='status warning'>⚠️ La base de datos existe pero no tiene tablas<br>";
                    echo "Ejecuta el script de creación: <code>sql/create_database.sql</code></div>";
                }
                
            } else {
                echo "<div class='status error'>❌ Base de datos '<code>$database</code>' no existe</div>";
                echo "<div class='status warning'>⚠️ Crea la base de datos ejecutando: <code>sql/create_database.sql</code></div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='status error'>❌ Error de conexión PDO: " . htmlspecialchars($e->getMessage()) . "</div>";
            
            $errorCode = $e->getCode();
            switch ($errorCode) {
                case '2002':
                    echo "<div class='status warning'>⚠️ El servidor MySQL no está ejecutándose. Inicia XAMPP/MySQL.</div>";
                    break;
                case '1045':
                    echo "<div class='status warning'>⚠️ Credenciales incorrectas. Verifica usuario/contraseña.</div>";
                    break;
                case '1049':
                    echo "<div class='status warning'>⚠️ Base de datos no existe. Créala primero.</div>";
                    break;
                default:
                    echo "<div class='status warning'>⚠️ Error desconocido. Código: $errorCode</div>";
            }
        }
        ?>

        <h2>3. Información del Sistema</h2>
        <div class='status info'>
            <strong>PHP Version:</strong> <?= phpversion() ?><br>
            <strong>Sistema Operativo:</strong> <?= php_uname() ?><br>
            <strong>Servidor Web:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido' ?><br>
            <strong>Directorio del proyecto:</strong> <code><?= __DIR__ ?></code>
        </div>

        <h2>4. Configuración MySQL Recomendada</h2>
        <div class='status info'>
            <strong>Host:</strong> localhost<br>
            <strong>Puerto:</strong> 3306<br>
            <strong>Usuario:</strong> root<br>
            <strong>Contraseña:</strong> (vacía)<br>
            <strong>Base de datos:</strong> comanda
        </div>

        <h2>5. Pasos para Resolver Problemas</h2>
        <div class='status warning'>
            <strong>Si MySQL no responde:</strong><br>
            1. Abre XAMPP Control Panel<br>
            2. Detén Apache si está corriendo<br>
            3. Inicia MySQL (Start)<br>
            4. Inicia Apache<br>
            5. Actualiza esta página<br><br>
            
            <strong>Si la base de datos no existe:</strong><br>
            1. Ve a <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a><br>
            2. Importa el archivo <code>sql/create_database.sql</code><br>
            3. Actualiza esta página
        </div>

        <div style="margin-top: 30px;">
            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn">🔄 Actualizar Diagnóstico</a>
            <a href="index.php" class="btn">🏠 Volver al Sistema</a>
            <?php if (isset($pdo)): ?>
                <a href="#" class="btn" style="background: #28a745;">✅ Conexión OK - Continuar</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
