<?php

require_once __DIR__ . '/Funciones_SQL.php';

if (!function_exists('obtenerConsultasPendientes')) {
    function obtenerConsultasPendientes(PDO $db, ?int $idUsuario = null, string $rol = 'recepcionista'): array
    {
        if ($rol === 'pediatra' && $idUsuario !== null) {
            return ejecutarConsulta(
                $db,
                "SELECT q.id_consulta, c.fecha_hora, c.tipo_cita,
                        p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
                        q.motivo_consulta,
                        u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
                 FROM consultas q
                 INNER JOIN citas c ON q.id_cita = c.id_cita
                 INNER JOIN paciente p ON c.id_paciente = p.id_paciente
                 INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
                 WHERE q.estado_pago = 'pendiente'
                 AND c.estado != 'cancelada'
                 AND c.id_usuario = ?
                 ORDER BY c.fecha_hora ASC",
                [$idUsuario]
            );
        }

        return ejecutarConsulta(
            $db,
            "SELECT q.id_consulta, c.fecha_hora, c.tipo_cita,
                    p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
                    q.motivo_consulta,
                    u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
             FROM consultas q
             INNER JOIN citas c ON q.id_cita = c.id_cita
             INNER JOIN paciente p ON c.id_paciente = p.id_paciente
             INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
             WHERE q.estado_pago = 'pendiente'
             AND c.estado != 'cancelada'
             ORDER BY c.fecha_hora ASC",
            []
        );
    }
}

if (!function_exists('obtenerDetalleConsultaParaPago')) {
    function obtenerDetalleConsultaParaPago(PDO $db, int $idConsulta): ?array
    {
        $resultado = ejecutarConsulta(
            $db,
            "SELECT q.id_consulta, c.fecha_hora, c.tipo_cita,
                    p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
                    u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
             FROM consultas q
             INNER JOIN citas c ON q.id_cita = c.id_cita
             INNER JOIN paciente p ON c.id_paciente = p.id_paciente
             INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
             WHERE q.id_consulta = ?
             AND q.estado_pago = 'pendiente'
             AND c.estado != 'cancelada'",
            [$idConsulta]
        );

        if (empty($resultado)) {
            return null;
        }

        return $resultado[0];
    }
}

if (!function_exists('registrarPago')) {
    function registrarPago(PDO $db, int $idConsulta, float $monto, string $formaPago): array
    {
        if ($monto <= 0) {
            return ['exito' => false, 'error' => 'El monto debe ser mayor a cero'];
        }

        $formasValidas = ['efectivo', 'tarjeta', 'transferencia'];
        if (!in_array($formaPago, $formasValidas, true)) {
            return ['exito' => false, 'error' => 'Forma de pago no valida'];
        }

        $detalle = obtenerDetalleConsultaParaPago($db, $idConsulta);
        if ($detalle === null) {
            return ['exito' => false, 'error' => 'La consulta no existe, ya fue pagada o la cita fue cancelada'];
        }

        $transaccionActiva = $db->inTransaction();
        if (!$transaccionActiva) {
            $db->beginTransaction();
        }

        try {
            $exito = insertarDatos($db, 'pagos', [
                'id_consulta' => $idConsulta,
                'monto' => $monto,
                'forma_pago' => $formaPago,
                'fecha_pago' => date('Y-m-d H:i:s')
            ]);

            if (!$exito) {
                if (!$transaccionActiva) {
                    $db->rollBack();
                }
                return ['exito' => false, 'error' => 'Error al registrar el pago'];
            }

            actualizarDatos($db, 'consultas', ['estado_pago' => 'pagado'], 'id_consulta = :id_consulta', ['id_consulta' => $idConsulta]);

            if (!$transaccionActiva) {
                $db->commit();
            }

            return ['exito' => true];
        } catch (\Exception $e) {
            if (!$transaccionActiva) {
                $db->rollBack();
            }
            registrarError('Error al registrar pago: ' . $e->getMessage());
            return ['exito' => false, 'error' => 'Error interno al procesar el pago'];
        }
    }
}
