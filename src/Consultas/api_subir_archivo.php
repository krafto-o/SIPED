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

if (!validarTokenCSRF($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    echo json_encode(['exito' => false, 'error' => 'Solicitud no valida']);
    exit;
}

if (!isset($_FILES['archivo']) || !isset($_POST['id_cita']) || !isset($_POST['id_paciente'])) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'error' => 'Datos incompletos']);
    exit;
}

$idCita = (int) $_POST['id_cita'];
$idPaciente = (int) $_POST['id_paciente'];
$archivo = $_FILES['archivo'];

if ($archivo['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'error' => 'Error en la subida del archivo']);
    exit;
}

$resultado = guardarArchivoTemporal($idCita, $idPaciente, $archivo);

if ($resultado['exito']) {
    echo json_encode([
        'exito' => true,
        'nombre_original' => $resultado['nombre_original'],
        'ruta' => $resultado['ruta']
    ]);
} else {
    http_response_code(400);
    echo json_encode(['exito' => false, 'error' => $resultado['error']]);
}
