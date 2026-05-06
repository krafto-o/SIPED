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

if (!$input || !isset($input['id_cita']) || !isset($input['consulta']) || !isset($input['tratamientos'])) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'error' => 'Datos incompletos']);
    exit;
}

$db = conectar();
$idCita = (int) $input['id_cita'];
$idUsuario = (int) $_SESSION['id_usuario'];
$datosConsulta = $input['consulta'];
$tratamientos = $input['tratamientos'];
$archivos = $input['archivos'] ?? [];

$datosCita = obtenerDatosConsulta($db, $idCita, $idUsuario);

if (!$datosCita) {
    http_response_code(404);
    echo json_encode(['exito' => false, 'error' => 'Cita no encontrada o no autorizada']);
    exit;
}

if (empty($datosConsulta['diagnostico'])) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'error' => 'El diagnostico es obligatorio']);
    exit;
}

$resultado = finalizarConsulta($db, $idCita, $datosConsulta, $tratamientos, $archivos, $idUsuario, true, true);

if (!$resultado['exito']) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'error' => $resultado['error']]);
    exit;
}

$idConsulta = $resultado['id_consulta'];

$respuesta = [
    'exito' => true,
    'id_consulta' => $idConsulta,
    'mensaje' => 'Consulta finalizada exitosamente'
];

if (!empty($resultado['receta_url'])) {
    $respuesta['receta_url'] = $resultado['receta_url'];
}

echo json_encode($respuesta);
