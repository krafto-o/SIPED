<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/reportes.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Reportes/index');
    exit;
}

$rango = $_POST['rango'] ?? '';

$hoy = date('Y-m-d');

switch ($rango) {
    case 'dia':
        $fechaInicio = $hoy;
        $fechaFin = $hoy;
        $titulo = 'Corte de Caja del Dia';
        break;
    case 'mes':
        $fechaInicio = date('Y-m-01');
        $fechaFin = $hoy;
        $titulo = 'Reporte Mensual - ' . date('F Y');
        break;
    default:
        $_SESSION['error_reporte'] = 'Rango de fechas no valido';
        header('Location: /Reportes/index');
        exit;
}

$db = conectar();
$resultado = generarReporteCortePDF($db, $fechaInicio, $fechaFin, $titulo);

if (!$resultado['exito']) {
    $_SESSION['error_reporte'] = $resultado['error'] ?? 'Error al generar el reporte';
    header('Location: /Reportes/index');
    exit;
}

$rutaArchivo = __DIR__ . '/../../' . $resultado['ruta'];

if (!file_exists($rutaArchivo)) {
    $_SESSION['error_reporte'] = 'El archivo generado no se encuentra';
    header('Location: /Reportes/index');
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($rutaArchivo) . '"');
header('Content-Length: ' . filesize($rutaArchivo));
readfile($rutaArchivo);
exit;
