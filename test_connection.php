<?php
// test_connection.php - Crear este archivo en la raíz del proyecto

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Probando conexión a la base de datos...\n";
echo "Host: " . $_ENV['DB_HOST'] . "\n";
echo "Port: " . $_ENV['DB_PORT'] . "\n";
echo "Database: " . $_ENV['DB_DATABASE'] . "\n";
echo "Username: " . $_ENV['DB_USERNAME'] . "\n";
echo "Password: " . (strlen($_ENV['DB_PASSWORD']) > 0 ? str_repeat('*', strlen($_ENV['DB_PASSWORD'])) : 'VACÍO') . "\n\n";

try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}";
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    
    echo "✅ ¡Conexión exitosa!\n";
    
    // Probar una consulta simple
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Consulta de prueba exitosa: " . $result['test'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    echo "❌ Código de error: " . $e->getCode() . "\n";
    
    // Diagnósticos adicionales
    if ($e->getCode() == 2002) {
        echo "\n🔍 Diagnóstico para error 2002:\n";
        echo "- Verifica que el host sea accesible\n";
        echo "- Verifica que el puerto esté abierto\n";
        echo "- Verifica que MySQL esté ejecutándose en el servidor\n";
        
        // Probar conectividad básica
        echo "\n🔍 Probando conectividad básica...\n";
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);
        if ($connection) {
            echo "✅ Puerto $port está abierto en $host\n";
            fclose($connection);
        } else {
            echo "❌ No se puede conectar al puerto $port en $host\n";
            echo "   Error: $errstr ($errno)\n";
        }
    }
}