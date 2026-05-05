<?php
require_once 'Funciones_SQL.php';

try {
    $pdo = conectar();
    echo "<h1>¡Conexion Exitosa!</h1>";
    echo "<p>El sistema de la consulta pediatrica ya se habla con la base de datos.</p>";
} catch (PDOException $e) {
    echo "<h1>Error de conexion</h1>";
    echo "<p>Detalle: " . $e->getMessage() . "</p>";
}
?>
