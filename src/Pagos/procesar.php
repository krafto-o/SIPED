<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/pagos.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Pagos/index');
    exit;
}

if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_pago'] = 'Solicitud no valida';
    header('Location: /Pagos/index');
    exit;
}
regenerarTokenCSRF();

$idConsulta = filter_input(INPUT_POST, 'id_consulta', FILTER_VALIDATE_INT);
$monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
$formaPago = $_POST['forma_pago'] ?? '';

if (!$idConsulta || $monto === false || $monto === null || empty($formaPago)) {
    $_SESSION['error_pago'] = 'Datos incompletos o invalidos';
    header('Location: /Pagos/index');
    exit;
}

$db = conectar();
$resultado = registrarPago($db, $idConsulta, $monto, $formaPago);

if ($resultado['exito']) {
    $_SESSION['success_pago'] = 'Pago registrado exitosamente';
} else {
    $_SESSION['error_pago'] = $resultado['error'] ?? 'Error al registrar el pago';
}

header('Location: /Pagos/index');
exit;
