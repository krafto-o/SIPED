<?php
$host = 'db'; // Nombre del servicio en docker-compose
$db   = 'siped';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "<h1>¡Conexión Exitosa!</h1>";
    echo "<p>El sistema de la consulta pediátrica ya se habla con la base de datos.</p>";
} catch (PDOException $e) {
    echo "<h1>Error de conexión</h1>";
    echo "<p>Detalle: " . $e->getMessage() . "</p>";
}
?>
