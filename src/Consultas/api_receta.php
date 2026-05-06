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

if (!$input || !isset($input['id_consulta']) || !isset($input['id_cita'])) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'error' => 'Datos incompletos']);
    exit;
}

$db = conectar();
$idConsulta = (int) $input['id_consulta'];
$idCita = (int) $input['id_cita'];

$resultado = obtenerOGenerarReceta($db, $idConsulta, $idCita);

if ($resultado['exito']) {
    echo json_encode([
        'exito' => true,
        'ruta' => $resultado['ruta'],
        'existente' => $resultado['existente'] ?? false
    ]);
} else {
    http_response_code(500);
    echo json_encode(['exito' => false, 'error' => $resultado['error']]);
}
