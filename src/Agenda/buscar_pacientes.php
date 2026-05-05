<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/citas.php';

requerirRol(['recepcionista', 'pediatra']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$termino = $input['termino'] ?? '';

if (empty($termino) || strlen($termino) < 2) {
    echo json_encode([]);
    exit;
}

$db = conectar();
$pacientes = buscarPacientesParaCita($db, $termino);

echo json_encode($pacientes);
