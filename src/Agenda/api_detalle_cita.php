<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/citas.php';

requerirRol(['recepcionista', 'pediatra']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Metodo no permitido']);
    exit;
}

$idCita = $_POST['id_cita'] ?? null;

if (!$idCita) {
    echo json_encode(['error' => 'ID de cita requerido']);
    exit;
}

$db = conectar();
$detalle = obtenerDetalleCita($db, (int) $idCita);

if (!$detalle) {
    echo json_encode(['error' => 'Cita no encontrada']);
    exit;
}

echo json_encode($detalle);
