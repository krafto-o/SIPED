<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/citas.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Agenda/index');
    exit;
}

if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_cita'] = 'Solicitud no valida';
    header('Location: /Agenda/index');
    exit;
}
regenerarTokenCSRF();

$idCita = $_POST['id_cita'] ?? null;
$accion = $_POST['accion'] ?? null;

if (!$idCita || !$accion) {
    $_SESSION['error_cita'] = 'Datos incompletos';
    header('Location: /Agenda/index');
    exit;
}

$estadoMap = [
    'confirmar' => 'confirmada',
    'cancelar' => 'cancelada',
];

if (!isset($estadoMap[$accion])) {
    $_SESSION['error_cita'] = 'Accion no valida';
    header('Location: /Agenda/index');
    exit;
}

$db = conectar();
$exito = cambiarEstadoCita($db, (int) $idCita, $estadoMap[$accion]);

if ($exito) {
    $_SESSION['success_cita'] = 'Cita ' . $estadoMap[$accion] . ' exitosamente';
} else {
    $_SESSION['error_cita'] = 'Error al cambiar estado de la cita';
}

header('Location: /Agenda/index');
exit;
