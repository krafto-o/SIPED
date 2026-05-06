<?php

require_once __DIR__ . '/Funciones_SQL.php';
require_once __DIR__ . '/consultas.php';

if (!function_exists('obtenerIngresosDia')) {
    function obtenerIngresosDia(PDO $db): float
    {
        $resultado = ejecutarConsulta(
            $db,
            "SELECT COALESCE(SUM(monto), 0) AS total
             FROM pagos
             WHERE DATE(fecha_pago) = CURDATE()",
            []
        );

        return (float) $resultado[0]['total'];
    }
}

if (!function_exists('obtenerIngresosSemana')) {
    function obtenerIngresosSemana(PDO $db): float
    {
        $resultado = ejecutarConsulta(
            $db,
            "SELECT COALESCE(SUM(monto), 0) AS total
             FROM pagos
             WHERE YEARWEEK(fecha_pago, 1) = YEARWEEK(CURDATE(), 1)",
            []
        );

        return (float) $resultado[0]['total'];
    }
}

if (!function_exists('obtenerIngresosMes')) {
    function obtenerIngresosMes(PDO $db): float
    {
        $resultado = ejecutarConsulta(
            $db,
            "SELECT COALESCE(SUM(monto), 0) AS total
             FROM pagos
             WHERE MONTH(fecha_pago) = MONTH(CURDATE())
             AND YEAR(fecha_pago) = YEAR(CURDATE())",
            []
        );

        return (float) $resultado[0]['total'];
    }
}

if (!function_exists('obtenerPagosRecientes')) {
    function obtenerPagosRecientes(PDO $db, int $limite = 50): array
    {
        return ejecutarConsulta(
            $db,
            "SELECT p.id_pago, p.monto, p.forma_pago, p.fecha_pago,
                    q.id_consulta,
                    pac.nombre AS paciente_nombre, pac.apellidos AS paciente_apellidos,
                    u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
             FROM pagos p
             INNER JOIN consultas q ON p.id_consulta = q.id_consulta
             INNER JOIN citas c ON q.id_cita = c.id_cita
             INNER JOIN paciente pac ON c.id_paciente = pac.id_paciente
             INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
             ORDER BY p.fecha_pago DESC
             LIMIT ?",
            [$limite]
        );
    }
}

if (!function_exists('obtenerPagosPorRango')) {
    function obtenerPagosPorRango(PDO $db, string $fechaInicio, string $fechaFin): array
    {
        return ejecutarConsulta(
            $db,
            "SELECT p.id_pago, p.monto, p.forma_pago, p.fecha_pago,
                    pac.nombre AS paciente_nombre, pac.apellidos AS paciente_apellidos,
                    u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
             FROM pagos p
             INNER JOIN consultas q ON p.id_consulta = q.id_consulta
             INNER JOIN citas c ON q.id_cita = c.id_cita
             INNER JOIN paciente pac ON c.id_paciente = pac.id_paciente
             INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
             WHERE DATE(p.fecha_pago) >= ? AND DATE(p.fecha_pago) <= ?
             ORDER BY p.fecha_pago ASC",
            [$fechaInicio, $fechaFin]
        );
    }
}

if (!function_exists('generarReporteCortePDF')) {
    function generarReporteCortePDF(PDO $db, string $fechaInicio, string $fechaFin, string $titulo = 'Corte de Caja'): array
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        $pagos = obtenerPagosPorRango($db, $fechaInicio, $fechaFin);

        $totalGeneral = 0;
        foreach ($pagos as $pago) {
            $totalGeneral += (float) $pago['monto'];
        }

        $vars = [
            'titulo' => $titulo,
            'fecha_generacion' => date('d/m/Y H:i:s'),
            'rango_fechas' => date('d/m/Y', strtotime($fechaInicio)) . ' - ' . date('d/m/Y', strtotime($fechaFin)),
            'pagos' => array_map(function($p) {
                return [
                    'fecha' => date('d/m/Y H:i', strtotime($p['fecha_pago'])),
                    'paciente' => htmlspecialchars($p['paciente_nombre'] . ' ' . $p['paciente_apellidos']),
                    'medico' => htmlspecialchars($p['medico_nombre'] . ' ' . $p['medico_apellidos']),
                    'forma_pago' => ucfirst(htmlspecialchars($p['forma_pago'])),
                    'monto' => '$' . number_format((float) $p['monto'], 2),
                ];
            }, $pagos),
            'total_general' => '$' . number_format($totalGeneral, 2),
            'linea_firma' => 'Nombre y firma de quien realiza el corte',
        ];

        $html = renderizarPlantilla(__DIR__ . '/../plantillas/reporte_corte/default.php', $vars);

        try {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('Letter', 'portrait');
            $dompdf->render();

            $directorio = __DIR__ . '/../../storage/reportes';

            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            $nombreArchivo = 'corte_' . date('Y-m-d_His') . '.pdf';
            $rutaArchivo = $directorio . '/' . $nombreArchivo;

            file_put_contents($rutaArchivo, $dompdf->output());

            return ['exito' => true, 'ruta' => '/storage/reportes/' . $nombreArchivo];
        } catch (\Exception $e) {
            registrarError('Error al generar reporte PDF: ' . $e->getMessage());
            return ['exito' => false, 'error' => 'Error al generar el reporte PDF'];
        }
    }
}
