<?php

if (!function_exists('registrarError')) {
function registrarError($mensaje)
{
    $archivoLog = __DIR__ . '/../logs/errores_db.log';
    $fecha = date('Y-m-d H:i:s');
    $contenido = "[$fecha] ERROR: $mensaje" . PHP_EOL;
    error_log($contenido, 3, $archivoLog);
}
}

if (!function_exists('conectarSinBD')) {
function conectarSinBD(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $host = 'db';
            $user = 'root';
            $pass = 'root';
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            registrarError("Conexión fallida: " . $e->getMessage());
            die("Error interno del servidor. Consulte al administrador.");
        }
    }
    return $pdo;
}
}

if (!function_exists('conectar')) {
function conectar(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $host = 'db';
            $db = defined('MODO_PRUEBA') ? 'siped_test' : 'siped';
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
}

if (!function_exists('obtenerDatos')) {
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
        return [];
    }
}
}

if (!function_exists('obtenerDatoEspecifico')) {
function obtenerDatoEspecifico(PDO $conexion, $columnas, $tabla, $condicion = "1", $params = [])
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        throw new Exception("Nombre de tabla no válido.");
    }
    if (!preg_match('/^[a-zA-Z0-9_, ]+$/', $columnas)) {
        throw new Exception("Nombre de la columna no válido.");
    }
    try {
        $sql = "SELECT $columnas FROM $tabla WHERE $condicion";
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        registrarError("Error en SELECT ($tabla): " . $e->getMessage());
        return [];
    }
}
}

if (!function_exists('insertarDatos')) {
function insertarDatos(PDO $conexion, $tabla, $datos)
{
    $columnas = array_keys($datos);
    $marcadores = array_map(fn($col) => ":$col", $columnas);
    $sql = sprintf(
        "INSERT INTO %s (%s) VALUES (%s)",
        $tabla,
        implode(", ", $columnas),
        implode(", ", $marcadores)
    );
    try {
        $stmt = $conexion->prepare($sql);
        $resultado = $stmt->execute($datos);
        return $resultado;
    } catch (\PDOException $e) {
        registrarError("Error en INSERT ($tabla): " . $e->getMessage() . " | Datos: " . json_encode($datos));
        return false;
    }
}
}

if (!function_exists('actualizarDatos')) {
function actualizarDatos(PDO $conexion, $tabla, $datos, $condicion, $paramsCondicion = [])
{
    $sets = array_map(fn($col) => "$col = :set_$col", array_keys($datos));
    $datosRenombrados = [];
    foreach ($datos as $col => $valor) {
        $datosRenombrados["set_$col"] = $valor;
    }
    $sql = sprintf(
        "UPDATE %s SET %s WHERE %s",
        $tabla,
        implode(", ", $sets),
        $condicion
    );

    try {
        $stmt = $conexion->prepare($sql);
        $todosLosParams = array_merge($datosRenombrados, $paramsCondicion);
        return $stmt->execute($todosLosParams);
    } catch (\PDOException $e) {
        registrarError("Error en UPDATE ($tabla): " . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('ejecutarConsulta')) {
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
}

if (!function_exists('eliminarRegistro')) {
function eliminarRegistro(PDO $conexion, $tabla, $condicion, $params = [])
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        throw new Exception("Nombre de tabla no valido.");
    }
    try {
        $sql = "DELETE FROM $tabla WHERE $condicion";
        $stmt = $conexion->prepare($sql);
        return $stmt->execute($params);
    } catch (\PDOException $e) {
        registrarError("Error en DELETE ($tabla): " . $e->getMessage());
        return false;
    }
}
}
