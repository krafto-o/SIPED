<?php

require_once __DIR__ . '/Funciones_SQL.php';

function calcularDuracionCita(PDO $db, string $tipoCita): int
{
    if ($tipoCita === 'primera_vez') {
        $resultado = obtenerDatos($db, 'configuracion', 'clave = ?', ['duracion_primera_cita']);
        if (!empty($resultado)) {
            return (int) $resultado[0]['valor'];
        }
        return 30;
    }

    $resultado = obtenerDatos($db, 'configuracion', 'clave = ?', ['duracion_cita_regular']);
    if (!empty($resultado)) {
        return (int) $resultado[0]['valor'];
    }

    $resultado = obtenerDatos($db, 'configuracion', 'clave = ?', ['duracion_cita']);
    if (!empty($resultado)) {
        return (int) $resultado[0]['valor'];
    }

    return 30;
}

function verificarDisponibilidad(PDO $db, int $idUsuario, string $fechaInicio, string $fechaFin): bool
{
    $resultado = ejecutarConsulta(
        $db,
        "SELECT COUNT(*) AS total
         FROM citas
         WHERE id_usuario = ?
         AND estado != 'cancelada'
         AND fecha_hora < ?
         AND DATE_ADD(fecha_hora, INTERVAL (
             CASE WHEN tipo_cita = 'primera_vez' THEN 30 ELSE 20 END
         ) MINUTE) > ?",
        [$idUsuario, $fechaFin, $fechaInicio]
    );

    return (int) $resultado[0]['total'] === 0;
}

function agendarCita(PDO $db, int $idPaciente, int $idUsuario, string $fechaHora, string $tipoCita, string $motivo = null): array
{
    $transaccionActiva = $db->inTransaction();
    if (!$transaccionActiva) {
        $db->beginTransaction();
    }

    try {
        $duracionMinutos = calcularDuracionCita($db, $tipoCita);

        $fechaInicio = new DateTime($fechaHora);
        $fechaFin = clone $fechaInicio;
        $fechaFin->modify("+{$duracionMinutos} minutes");

        $disponible = verificarDisponibilidad($db, $idUsuario, $fechaHora, $fechaFin->format('Y-m-d H:i:s'));

        if (!$disponible) {
            if (!$transaccionActiva) {
                $db->rollBack();
            }
            return ['exito' => false, 'error' => 'Horario no disponible'];
        }

        $datosCita = [
            'id_paciente' => $idPaciente,
            'id_usuario' => $idUsuario,
            'fecha_hora' => $fechaHora,
            'tipo_cita' => $tipoCita,
            'motivo' => $motivo,
            'estado' => 'pendiente'
        ];

        $exito = insertarDatos($db, 'citas', $datosCita);

        if (!$exito) {
            if (!$transaccionActiva) {
                $db->rollBack();
            }
            return ['exito' => false, 'error' => 'Error al agendar la cita'];
        }

        $idCita = (int) $db->lastInsertId();

        if (!$transaccionActiva) {
            $db->commit();
        }

        return ['exito' => true, 'id_cita' => $idCita];
    } catch (\Exception $e) {
        if (!$transaccionActiva) {
            $db->rollBack();
        }
        registrarError('Error al agendar cita: ' . $e->getMessage());
        return ['exito' => false, 'error' => 'Error interno al agendar'];
    }
}

function obtenerCitasCalendario(PDO $db, string $fechaInicio, string $fechaFin, int $idUsuario = null, string $rol = null): array
{
    if ($rol === 'pediatra' && $idUsuario !== null) {
        $citas = ejecutarConsulta(
            $db,
            "SELECT c.id_cita, c.fecha_hora, c.tipo_cita, c.estado, c.motivo,
                    p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
                    u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
             FROM citas c
             INNER JOIN paciente p ON c.id_paciente = p.id_paciente
             INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
             WHERE c.fecha_hora >= ? AND c.fecha_hora <= ?
             AND c.id_usuario = ?
             AND c.estado != 'cancelada'
             ORDER BY c.fecha_hora ASC",
            [$fechaInicio, $fechaFin, $idUsuario]
        );
    } else {
        $citas = ejecutarConsulta(
            $db,
            "SELECT c.id_cita, c.fecha_hora, c.tipo_cita, c.estado, c.motivo,
                    p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
                    u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
             FROM citas c
             INNER JOIN paciente p ON c.id_paciente = p.id_paciente
             INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
             WHERE c.fecha_hora >= ? AND c.fecha_hora <= ?
             AND c.estado != 'cancelada'
             ORDER BY c.fecha_hora ASC",
            [$fechaInicio, $fechaFin]
        );
    }

    $eventos = [];
    foreach ($citas as $cita) {
        $color = match ($cita['estado']) {
            'pendiente' => '#f59e0b',
            'confirmada' => '#0ea5e9',
            'realizada' => '#10b981',
            default => '#64748b',
        };

        $duracion = $cita['tipo_cita'] === 'primera_vez' ? 30 : 20;
        $fechaInicioCita = new DateTime($cita['fecha_hora']);
        $fechaFinCita = clone $fechaInicioCita;
        $fechaFinCita->modify("+{$duracion} minutes");

        $eventos[] = [
            'id' => $cita['id_cita'],
            'title' => $cita['paciente_nombre'] . ' ' . $cita['paciente_apellidos'],
            'start' => $cita['fecha_hora'],
            'end' => $fechaFinCita->format('Y-m-d H:i:s'),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'extendedProps' => [
                'estado' => $cita['estado'],
                'tipo_cita' => $cita['tipo_cita'],
                'motivo' => $cita['motivo'],
                'medico' => $cita['medico_nombre'] . ' ' . $cita['medico_apellidos'],
            ],
        ];
    }

    return $eventos;
}

function obtenerDetalleCita(PDO $db, int $idCita): ?array
{
    $citas = ejecutarConsulta(
        $db,
        "SELECT c.id_cita, c.fecha_hora, c.tipo_cita, c.estado, c.motivo,
                p.id_paciente, p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
                p.fecha_nacimiento, p.sexo, p.tipo_sangre, p.alergias,
                u.id_usuario, u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
         FROM citas c
         INNER JOIN paciente p ON c.id_paciente = p.id_paciente
         INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
         WHERE c.id_cita = ?",
        [$idCita]
    );

    if (empty($citas)) {
        return null;
    }

    $cita = $citas[0];

    $tutores = ejecutarConsulta(
        $db,
        "SELECT t.id_tutor, t.nombre, t.apellidos, t.telefono, t.correo, t.direccion,
                pt.parentesco
         FROM paciente_tutor pt
         INNER JOIN tutor t ON pt.id_tutor = t.id_tutor
         WHERE pt.id_paciente = ? AND t.estado = 'activo'",
        [$cita['id_paciente']]
    );

    $cita['tutores'] = $tutores;

    return $cita;
}

function cambiarEstadoCita(PDO $db, int $idCita, string $nuevoEstado): bool
{
    $estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'realizada'];

    if (!in_array($nuevoEstado, $estadosValidos, true)) {
        return false;
    }

    return actualizarDatos($db, 'citas', ['estado' => $nuevoEstado], 'id_cita = :id_cita', ['id_cita' => $idCita]);
}

function listarPediatras(PDO $db): array
{
    return obtenerDatos($db, 'usuarios', "rol = 'pediatra' AND estado = 'activo'", [], 'nombre ASC');
}

function buscarPacientesParaCita(PDO $db, string $termino): array
{
    return ejecutarConsulta(
        $db,
        "SELECT id_paciente, nombre, apellidos, fecha_nacimiento
         FROM paciente
         WHERE estado = 'activo'
         AND (LOWER(nombre) LIKE LOWER(?) OR LOWER(apellidos) LIKE LOWER(?))
         ORDER BY nombre ASC
         LIMIT 15",
        ["%{$termino}%", "%{$termino}%"]
    );
}
