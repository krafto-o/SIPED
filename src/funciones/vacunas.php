<?php

require_once __DIR__ . '/Funciones_SQL.php';
require_once __DIR__ . '/pacientes.php';

if (!function_exists('obtenerVacunasCatalogo')) {
    function obtenerVacunasCatalogo(PDO $db): array
    {
        return obtenerDatos($db, 'vacunas_catalogo', '1', [], 'id_vacuna ASC');
    }
}

if (!function_exists('obtenerVacunasPaciente')) {
    function obtenerVacunasPaciente(PDO $db, int $idPaciente): array
    {
        return ejecutarConsulta(
            $db,
            "SELECT va.id_registro, va.id_vacuna, va.fecha_aplicacion, va.lote,
                    va.aplicada_externamente, vc.nombre AS vacuna_nombre,
                    vc.esquema_edad
             FROM vacunas_aplicadas va
             INNER JOIN vacunas_catalogo vc ON va.id_vacuna = vc.id_vacuna
             WHERE va.id_paciente = ?
             ORDER BY va.fecha_aplicacion DESC",
            [$idPaciente]
        );
    }
}

if (!function_exists('parsearEsquemaEdadMeses')) {
    function parsearEsquemaEdadMeses(string $esquemaEdad): int
    {
        if (stripos($esquemaEdad, 'recien nacido') !== false) {
            return 0;
        }

        if (preg_match('/(\d+)-(\d+)\s*mes/', $esquemaEdad, $coincidencias)) {
            return (int) $coincidencias[1];
        }

        if (preg_match('/(\d+)\s*a[nñ]o/', $esquemaEdad, $coincidencias)) {
            return (int) $coincidencias[1] * 12;
        }

        if (preg_match('/(\d+)\s*mes/', $esquemaEdad, $coincidencias)) {
            return (int) $coincidencias[1];
        }

        return 999;
    }
}

if (!function_exists('calcularVacunasPendientes')) {
    function calcularVacunasPendientes(PDO $db, int $idPaciente): array
    {
        $pacientes = obtenerDatos($db, 'paciente', 'id_paciente = ?', [$idPaciente]);
        if (empty($pacientes)) {
            return [];
        }

        $paciente = $pacientes[0];
        $fechaNac = new DateTime($paciente['fecha_nacimiento']);
        $hoy = new DateTime();
        $diferencia = $hoy->diff($fechaNac);
        $edadMeses = ($diferencia->y * 12) + $diferencia->m;

        $vacunasCatalogo = obtenerVacunasCatalogo($db);
        $vacunasAplicadas = obtenerVacunasPaciente($db, $idPaciente);

        $idsAplicadas = [];
        foreach ($vacunasAplicadas as $aplicada) {
            $idsAplicadas[] = (int) $aplicada['id_vacuna'];
        }

        $vacunasAtrasadas = [];
        foreach ($vacunasCatalogo as $vacuna) {
            $edadEsquema = parsearEsquemaEdadMeses($vacuna['esquema_edad']);
            if ($edadMeses >= $edadEsquema && !in_array((int) $vacuna['id_vacuna'], $idsAplicadas)) {
                $vacunasAtrasadas[] = [
                    'id_vacuna' => (int) $vacuna['id_vacuna'],
                    'nombre' => $vacuna['nombre'],
                    'esquema_edad' => $vacuna['esquema_edad'],
                ];
            }
        }

        return $vacunasAtrasadas;
    }
}

if (!function_exists('verificarVacunaDuplicada')) {
    function verificarVacunaDuplicada(PDO $db, int $idPaciente, int $idVacuna, string $fechaAplicacion): bool
    {
        $resultado = obtenerDatos(
            $db,
            'vacunas_aplicadas',
            'id_paciente = ? AND id_vacuna = ? AND fecha_aplicacion = ?',
            [$idPaciente, $idVacuna, $fechaAplicacion]
        );

        return !empty($resultado);
    }
}

if (!function_exists('registrarVacuna')) {
    function registrarVacuna(PDO $db, int $idPaciente, array $datos): bool
    {
        $transaccionActiva = $db->inTransaction();
        if (!$transaccionActiva) {
            $db->beginTransaction();
        }

        try {
            $datosVacuna = [
                'id_paciente' => $idPaciente,
                'id_vacuna' => (int) $datos['id_vacuna'],
                'fecha_aplicacion' => $datos['fecha_aplicacion'],
                'aplicada_externamente' => !empty($datos['aplicada_externamente']) ? 1 : 0,
                'lote' => !empty($datos['aplicada_externamente']) ? null : ($datos['lote'] ?? null),
            ];

            $exito = insertarDatos($db, 'vacunas_aplicadas', $datosVacuna);

            if (!$exito) {
                if (!$transaccionActiva) {
                    $db->rollBack();
                }
                return false;
            }

            if (!$transaccionActiva) {
                $db->commit();
            }
            return true;
        } catch (\Exception $e) {
            if (!$transaccionActiva) {
                $db->rollBack();
            }
            registrarError('Error al registrar vacuna: ' . $e->getMessage());
            return false;
        }
    }
}
