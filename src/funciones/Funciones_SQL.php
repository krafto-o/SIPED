<?php

function registrarError($mensaje)
{
    $archivoLog = __DIR__ . '/../logs/errores_db.log';
    $fecha = date('Y-m-d H:i:s');
    $contenido = "[$fecha] ERROR: $mensaje" . PHP_EOL;
    error_log($contenido, 3, $archivoLog);
}

function conectar(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $host = 'db';
            $db = 'siped';
            $user = 'root';
            $pass = 'root';
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            registrarError("Conexión fallida: " . $e->getMessage());
            die("Error interno del servidor. Consulte al administrador.");
        }
    }
    return $pdo;
}

function obtenerDatos(PDO $conexion, $tabla, $condicion = "1", $params = [])
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        throw new Exception("Nombre de tabla no válido.");
    }
    try {
        $sql = "SELECT * FROM $tabla WHERE $condicion";
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        registrarError("Error en SELECT ($tabla): " . $e->getMessage());
        return []; // Devuelve array vacío para que el frontend no falle
    }
// MODO DE USO:
// $db = conectar();
// $datos = obtenerDatos($db, 'usuarios', 'id = ? AND estado = ?', [1, 'activo']);
}

function obtenerDatoEspecifico(PDO $conexion, $columnas, $tabla, $condicion = "1", $params = [])
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        throw new Exception("Nombre de tabla no válido.");
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $columnas)) {
        throw new Exception("Nombre de la columna no válido.");
    }
    try {
        $sql = "SELECT $columnas FROM $tabla WHERE $condicion";
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        registrarError("Error en SELECT ($tabla): " . $e->getMessage());
        return []; // Devuelve array vacío para que el frontend no falle
    }
}

function insertarDatos(PDO $conexion, $tabla, $datos)
{
    // 1. Extraer los nombres de las columnas (las llaves del array)
    $columnas = array_keys($datos);
    // 2. Crear los marcadores de posición como: :nombre, :email, :edad
    $marcadores = array_map(fn($col) => ":$col", $columnas);
    // 3. Se Construye el SQL -> INSERT INTO usuarios (nombre, email) VALUES (:nombre, :email)
    $sql = sprintf(
        "INSERT INTO %s (%s) VALUES (%s)",
        $tabla,
        implode(", ", $columnas),
        implode(", ", $marcadores)
    );
    try {
        // 4. Ejecutar pasando el array original.
        // PDO mapea automáticamente las llaves (:nombre) con sus valores.
        $stmt = $conexion->prepare($sql);
        $resultado = $stmt->execute($datos);
        return $resultado; // Devuelve true si tuvo éxito
    } catch (\PDOException $e) {
        registrarError("Error en INSERT ($tabla): " . $e->getMessage() . " | Datos: " . json_encode($datos));
        return false;
    }
}

function actualizarDatos(PDO $conexion, $tabla, $datos, $condicion, $paramsCondicion = [])
{
    // Construye la parte "SET columna1 = :columna1, columna2 = :columna2"
    $sets = array_map(fn($col) => "$col = :$col", array_keys($datos));
    $sql = sprintf(
        "UPDATE %s SET %s WHERE %s",
        $tabla,
        implode(", ", $sets),
        $condicion
    );

    try {
        $stmt = $conexion->prepare($sql);
        // Combinamos los datos a actualizar con los parámetros de la condición (WHERE)
        // Por ejemplo si actualizas nombre y el WHERE es id = ?, unimos ambos arrays.
        $todosLosParams = array_merge($datos, $paramsCondicion);
        return $stmt->execute($todosLosParams);
    } catch (\PDOException $e) {
        registrarError("Error en UPDATE ($tabla): " . $e->getMessage());
        return false;
    }
// Ejemplo de uso:
// actualizarDatos($db, 'usuarios', ['nombre' => 'Nuevo Nombre'], 'id = :id', ['id' => 5]);
}

function ejecutarConsulta(PDO $conexion, $sql, $params = [])
{
    try {
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        registrarError("Error en la consulta: " . $e->getMessage());
        return [];
    }
}
