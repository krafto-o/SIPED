<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Funciones_SQL.php';

function obtenerTimeoutSesion(): int
{
    try {
        $db = conectar();
        $resultado = obtenerDatos($db, 'configuracion', 'clave = ?', ['timeout_sesion']);
        if (!empty($resultado)) {
            return (int) $resultado[0]['valor'];
        }
    } catch (\Exception $e) {
        registrarError('Error al obtener timeout de sesion: ' . $e->getMessage());
    }
    return 10;
}

function iniciarSesion(array $usuario): void
{
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['apellidos'] = $usuario['apellidos'];
    $_SESSION['ultimo_acceso'] = time();
    session_regenerate_id(true);
}

function verificarSesion(): bool
{
    if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['ultimo_acceso'])) {
        return false;
    }

    $timeoutMinutos = obtenerTimeoutSesion();
    $tiempoInactividad = time() - $_SESSION['ultimo_acceso'];

    if ($tiempoInactividad > ($timeoutMinutos * 60)) {
        cerrarSesion();
        header('Location: /login?error=timeout');
        exit;
    }

    $_SESSION['ultimo_acceso'] = time();
    return true;
}

function requerirRol(array $rolesPermitidos): void
{
    if (!verificarSesion()) {
        header('Location: /login?error=no_autenticado');
        exit;
    }

    if (!in_array($_SESSION['rol'], $rolesPermitidos, true)) {
        header('Location: /login?error=rol_no_autorizado');
        exit;
    }
}

function cerrarSesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $parametros['path'],
            $parametros['domain'],
            $parametros['secure'],
            $parametros['httponly']
        );
    }

    session_destroy();
}

function usuarioAutenticado(): array
{
    return [
        'id_usuario' => $_SESSION['id_usuario'] ?? null,
        'rol' => $_SESSION['rol'] ?? null,
        'nombre' => $_SESSION['nombre'] ?? null,
        'apellidos' => $_SESSION['apellidos'] ?? null,
    ];
}
