<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/consultas.php';

verificarSesion();
requerirRol(['pediatra']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'error' => 'Metodo no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id_cita']) || !isset($input['datos'])) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'error' => 'Datos incompletos']);
    exit;
}

$db = conectar();
$idCita = (int) $input['id_cita'];
$idUsuario = (int) $_SESSION['id_usuario'];
$datos = $input['datos'];

$datosCita = obtenerDatosConsulta($db, $idCita, $idUsuario);

if (!$datosCita) {
    http_response_code(403);
    echo json_encode(['exito' => false, 'error' => 'Cita no encontrada o no autorizada']);
    exit;
}

$exito = guardarBorrador($db, $idCita, $idUsuario, $datos);

if ($exito) {
    echo json_encode(['exito' => true, 'mensaje' => 'Borrador guardado']);
} else {
    http_response_code(500);
    echo json_encode(['exito' => false, 'error' => 'Error al guardar borrador']);
}
