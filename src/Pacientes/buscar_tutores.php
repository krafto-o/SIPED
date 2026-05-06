<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/pacientes.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$tokenCSRF = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validarTokenCSRF($tokenCSRF)) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud no valida']);
    exit;
}

if (!$input || empty($input['termino'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Termino de busqueda requerido']);
    exit;
}

$db = conectar();
$termino = trim($input['termino']);

if (strlen($termino) < 2) {
    echo json_encode([]);
    exit;
}

$tutores = buscarTutores($db, $termino);

echo json_encode($tutores);
