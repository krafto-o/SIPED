<?php

require_once __DIR__ . '/Funciones_SQL.php';

function calcularEdad($fechaNacimiento): string
{
    $fechaNac = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $diferencia = $hoy->diff($fechaNac);

    $anios = $diferencia->y;
    $meses = $diferencia->m;

    if ($anios < 3) {
        if ($anios === 0) {
            if ($meses === 0) {
                return 'Recien nacido';
            }
            return $meses . ' mes' . ($meses > 1 ? 'es' : '');
        }
        return $anios . ' año' . ($anios > 1 ? 's' : '') . ' y ' . $meses . ' mes' . ($meses > 1 ? 'es' : '');
    }

    return $anios . ' año' . ($anios > 1 ? 's' : '');
}

function validarDuplicadoPaciente(PDO $db, $nombre, $apellidos, $fechaNacimiento): bool
{
    $resultado = obtenerDatos(
        $db,
        'paciente',
        'LOWER(nombre) = LOWER(?) AND LOWER(apellidos) = LOWER(?) AND fecha_nacimiento = ? AND estado = ?',
        [$nombre, $apellidos, $fechaNacimiento, 'activo']
    );

    return !empty($resultado);
}

function registrarPacienteConTutores(PDO $db, array $datosPaciente, array $tutores): bool
{
    $transaccionActiva = $db->inTransaction();
    if (!$transaccionActiva) {
        $db->beginTransaction();
    }
    try {
        $exitoPaciente = insertarDatos($db, 'paciente', $datosPaciente);

        if (!$exitoPaciente) {
            if (!$transaccionActiva) {
                $db->rollBack();
            }
            return false;
        }

        $idPaciente = $db->lastInsertId();

        foreach ($tutores as $tutor) {
            $idTutor = null;

            if (!empty($tutor['id_tutor_existente'])) {
                $idTutor = (int) $tutor['id_tutor_existente'];
            } else {
                $datosTutor = [
                    'nombre' => $tutor['nombre'],
                    'apellidos' => $tutor['apellidos'],
                    'telefono' => $tutor['telefono'],
                    'correo' => $tutor['correo'] ?? null,
                    'direccion' => $tutor['direccion'] ?? null,
                    'estado' => 'activo',
                ];

                $exitoTutor = insertarDatos($db, 'tutor', $datosTutor);

                if (!$exitoTutor) {
                    if (!$transaccionActiva) {
                        $db->rollBack();
                    }
                    return false;
                }

                $idTutor = (int) $db->lastInsertId();
            }

            $datosPivote = [
                'id_paciente' => $idPaciente,
                'id_tutor' => $idTutor,
                'parentesco' => $tutor['parentesco'],
            ];

            $exitoPivote = insertarDatos($db, 'paciente_tutor', $datosPivote);

            if (!$exitoPivote) {
                if (!$transaccionActiva) {
                    $db->rollBack();
                }
                return false;
            }
        }

        if (!$transaccionActiva) {
            $db->commit();
        }
        return true;
    } catch (\Exception $e) {
        if (!$transaccionActiva) {
            $db->rollBack();
        }
        registrarError('Error al registrar paciente con tutores: ' . $e->getMessage());
        return false;
    }
}

function obtenerPacienteConTutores(PDO $db, int $idPaciente): ?array
{
    $pacientes = obtenerDatos($db, 'paciente', 'id_paciente = ? AND estado = ?', [$idPaciente, 'activo']);

    if (empty($pacientes)) {
        return null;
    }

    $paciente = $pacientes[0];

    $tutores = ejecutarConsulta(
        $db,
        "SELECT t.id_tutor, t.nombre, t.apellidos, t.telefono, t.correo, t.direccion,
                pt.parentesco
         FROM paciente_tutor pt
         INNER JOIN tutor t ON pt.id_tutor = t.id_tutor
         WHERE pt.id_paciente = ? AND t.estado = 'activo'",
        [$idPaciente]
    );

    $paciente['tutores'] = $tutores;

    return $paciente;
}

function obtenerPacienteCompleto(PDO $db, int $idPaciente): ?array
{
    $pacientes = obtenerDatos($db, 'paciente', 'id_paciente = ? AND estado = ?', [$idPaciente, 'activo']);

    if (empty($pacientes)) {
        return null;
    }

    $paciente = $pacientes[0];

    $tutores = ejecutarConsulta(
        $db,
        "SELECT t.id_tutor, t.nombre, t.apellidos, t.telefono, t.correo, t.direccion,
                pt.parentesco
         FROM paciente_tutor pt
         INNER JOIN tutor t ON pt.id_tutor = t.id_tutor
         WHERE pt.id_paciente = ? AND t.estado = 'activo'",
        [$idPaciente]
    );

    $paciente['tutores'] = $tutores;

    return $paciente;
}

function darBajaPaciente(PDO $db, int $idPaciente): bool
{
    return actualizarDatos($db, 'paciente', ['estado' => 'inactivo'], 'id_paciente = :id_paciente', ['id_paciente' => $idPaciente]);
}

function desvincularTutor(PDO $db, int $idPaciente, int $idTutor): bool
{
    return eliminarRegistro($db, 'paciente_tutor', 'id_paciente = ? AND id_tutor = ?', [$idPaciente, $idTutor]);
}

function buscarTutores(PDO $db, string $termino): array
{
    return ejecutarConsulta(
        $db,
        "SELECT id_tutor, nombre, apellidos, telefono, correo
         FROM tutor
         WHERE estado = 'activo'
         AND (LOWER(nombre) LIKE LOWER(?) OR LOWER(apellidos) LIKE LOWER(?) OR telefono LIKE ?)
         ORDER BY nombre ASC
         LIMIT 10",
        ["%{$termino}%", "%{$termino}%", "%{$termino}%"]
    );
}

function listarPacientes(PDO $db, string $termino = ''): array
{
    if (!empty($termino)) {
        return ejecutarConsulta(
            $db,
            "SELECT id_paciente, nombre, apellidos, fecha_nacimiento, sexo
             FROM paciente
             WHERE estado = 'activo'
             AND (LOWER(nombre) LIKE LOWER(?) OR LOWER(apellidos) LIKE LOWER(?))
             ORDER BY nombre ASC",
            ["%{$termino}%", "%{$termino}%"]
        );
    }

    return obtenerDatos($db, 'paciente', "estado = 'activo'", [], 'nombre ASC');
}

function actualizarPaciente(PDO $db, int $idPaciente, array $datos): bool
{
    return actualizarDatos($db, 'paciente', $datos, 'id_paciente = :id_paciente', ['id_paciente' => $idPaciente]);
}

function vincularTutorAPaciente(PDO $db, int $idPaciente, int $idTutor, string $parentesco): bool
{
    return insertarDatos($db, 'paciente_tutor', [
        'id_paciente' => $idPaciente,
        'id_tutor' => $idTutor,
        'parentesco' => $parentesco,
    ]);
}

function obtenerCitasPaciente(PDO $db, int $idPaciente): array
{
    return ejecutarConsulta(
        $db,
        "SELECT c.id_cita, c.fecha_hora, c.tipo_cita, c.estado, c.motivo,
                u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
         FROM citas c
         INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
         WHERE c.id_paciente = ?
         ORDER BY c.fecha_hora DESC",
        [$idPaciente]
    );
}
