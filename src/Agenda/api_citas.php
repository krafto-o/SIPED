<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/citas.php';

requerirRol(['recepcionista', 'pediatra']);

header('Content-Type: application/json');

$usuario = usuarioAutenticado();
$db = conectar();

$fechaInicio = $_GET['start'] ?? date('Y-m-d H:i:s', strtotime('-1 month'));
$fechaFin = $_GET['end'] ?? date('Y-m-d H:i:s', strtotime('+2 months'));

$idUsuario = $_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

$eventos = obtenerCitasCalendario($db, $fechaInicio, $fechaFin, $idUsuario, $rol);

echo json_encode($eventos);
