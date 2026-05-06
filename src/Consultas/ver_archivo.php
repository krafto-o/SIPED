<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/Funciones_SQL.php';

verificarSesion();

if (!isset($_GET['ruta'])) {
    http_response_code(400);
    die('Ruta de archivo no proporcionada');
}

$ruta = $_GET['ruta'];

$rutaLimpia = realpath(__DIR__ . '/../../' . $ruta);

if (!$rutaLimpia || !file_exists($rutaLimpia)) {
    http_response_code(404);
    die('Archivo no encontrado');
}

$rutaBase = realpath(__DIR__ . '/../../storage');

if (strpos($rutaLimpia, $rutaBase) !== 0) {
    http_response_code(403);
    die('Acceso denegado');
}

$usuario = usuarioAutenticado();

if ($usuario['rol'] !== 'pediatra') {
    http_response_code(403);
    die('No autorizado para ver este archivo');
}

$extension = strtolower(pathinfo($rutaLimpia, PATHINFO_EXTENSION));

$tiposMime = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

$tipoMime = $tiposMime[$extension] ?? 'application/octet-stream';

header('Content-Type: ' . $tipoMime);
header('Content-Length: ' . filesize($rutaLimpia));
header('Content-Disposition: inline; filename="' . basename($rutaLimpia) . '"');

readfile($rutaLimpia);
exit;
