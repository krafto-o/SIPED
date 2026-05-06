<?php

require_once __DIR__ . '/Funciones_SQL.php';
require_once __DIR__ . '/citas.php';
require_once __DIR__ . '/pacientes.php';

if (!function_exists('obtenerDatosConsulta')) {
function obtenerDatosConsulta(PDO $db, int $idCita, int $idUsuario): ?array
{
    $citas = ejecutarConsulta(
        $db,
        "SELECT c.id_cita, c.fecha_hora, c.tipo_cita, c.estado, c.motivo,
                p.id_paciente, p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
                p.fecha_nacimiento, p.sexo, p.tipo_sangre, p.alergias,
                u.id_usuario AS medico_id, u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
         FROM citas c
         INNER JOIN paciente p ON c.id_paciente = p.id_paciente
         INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
         WHERE c.id_cita = ? AND c.id_usuario = ? AND c.estado != 'cancelada'",
        [$idCita, $idUsuario]
    );

    if (empty($citas)) {
        return null;
    }

    return $citas[0];
}
}

if (!function_exists('obtenerHistorialPaciente')) {
function obtenerHistorialPaciente(PDO $db, int $idPaciente): array
{
    $consultasPrevias = ejecutarConsulta(
        $db,
        "SELECT q.id_consulta, c.id_cita, c.fecha_hora, q.motivo_consulta, q.diagnostico, q.anamnesis,
                q.peso, q.talla, q.perimetro_cefalico, q.temperatura, q.frec_cardiaca, q.frec_respiratoria, q.notas_laboratorio
         FROM consultas q
         INNER JOIN citas c ON q.id_cita = c.id_cita
         WHERE c.id_paciente = ?
         ORDER BY c.fecha_hora DESC",
        [$idPaciente]
    );

    return $consultasPrevias;
}
}

if (!function_exists('guardarBorrador')) {
function guardarBorrador(PDO $db, int $idCita, int $idUsuario, array $datos): bool
{
    $datosJson = json_encode($datos, JSON_UNESCAPED_UNICODE);

    $existente = obtenerDatos(
        $db,
        'borradores_consultas',
        'id_cita = ? AND id_usuario = ?',
        [$idCita, $idUsuario]
    );

    if (!empty($existente)) {
        return actualizarDatos(
            $db,
            'borradores_consultas',
            ['datos_json' => $datosJson],
            'id_cita = :id_cita AND id_usuario = :id_usuario',
            ['id_cita' => $idCita, 'id_usuario' => $idUsuario]
        );
    }

    return insertarDatos($db, 'borradores_consultas', [
        'id_cita' => $idCita,
        'id_usuario' => $idUsuario,
        'datos_json' => $datosJson
    ]);
}
}

if (!function_exists('obtenerBorrador')) {
function obtenerBorrador(PDO $db, int $idCita, int $idUsuario): ?array
{
    $resultado = obtenerDatos(
        $db,
        'borradores_consultas',
        'id_cita = ? AND id_usuario = ?',
        [$idCita, $idUsuario]
    );

    if (empty($resultado)) {
        return null;
    }

    return json_decode($resultado[0]['datos_json'], true);
}
}

if (!function_exists('eliminarBorrador')) {
function eliminarBorrador(PDO $db, int $idCita, int $idUsuario): bool
{
    return eliminarRegistro($db, 'borradores_consultas', 'id_cita = ? AND id_usuario = ?', [$idCita, $idUsuario]);
}
}

if (!function_exists('finalizarConsulta')) {
function finalizarConsulta(PDO $db, int $idCita, array $datosConsulta, array $tratamientos, array $archivosAdjuntos = [], int $idUsuario = null, bool $eliminarBorrador = false, bool $generarReceta = false): array
{
    $transaccionActiva = $db->inTransaction();
    if (!$transaccionActiva) {
        $db->beginTransaction();
    }

    try {
        if (empty($datosConsulta['diagnostico'])) {
            if (!$transaccionActiva) {
                $db->rollBack();
            }
            return ['exito' => false, 'error' => 'El diagnostico es obligatorio'];
        }

        $peso = isset($datosConsulta['peso']) && $datosConsulta['peso'] !== '' ? (float) $datosConsulta['peso'] : null;
        $talla = isset($datosConsulta['talla']) && $datosConsulta['talla'] !== '' ? (float) $datosConsulta['talla'] : null;
        $perimetroCefalico = isset($datosConsulta['perimetro_cefalico']) && $datosConsulta['perimetro_cefalico'] !== '' ? (float) $datosConsulta['perimetro_cefalico'] : null;
        $temperatura = isset($datosConsulta['temperatura']) && $datosConsulta['temperatura'] !== '' ? (float) $datosConsulta['temperatura'] : null;
        $frecCardiaca = isset($datosConsulta['frec_cardiaca']) && $datosConsulta['frec_cardiaca'] !== '' ? (int) $datosConsulta['frec_cardiaca'] : null;
        $frecRespiratoria = isset($datosConsulta['frec_respiratoria']) && $datosConsulta['frec_respiratoria'] !== '' ? (int) $datosConsulta['frec_respiratoria'] : null;

        $datosInsert = [
            'id_cita' => $idCita,
            'motivo_consulta' => ($datosConsulta['motivo_consulta'] ?? '') !== '' ? $datosConsulta['motivo_consulta'] : null,
            'anamnesis' => ($datosConsulta['anamnesis'] ?? '') !== '' ? $datosConsulta['anamnesis'] : null,
            'peso' => $peso,
            'talla' => $talla,
            'perimetro_cefalico' => $perimetroCefalico,
            'temperatura' => $temperatura,
            'frec_cardiaca' => $frecCardiaca,
            'frec_respiratoria' => $frecRespiratoria,
            'notas_laboratorio' => ($datosConsulta['notas_laboratorio'] ?? '') !== '' ? $datosConsulta['notas_laboratorio'] : null,
            'diagnostico' => $datosConsulta['diagnostico'],
            'estado_pago' => 'pendiente'
        ];

        $exito = insertarDatos($db, 'consultas', $datosInsert);

        if (!$exito) {
            if (!$transaccionActiva) {
                $db->rollBack();
            }
            return ['exito' => false, 'error' => 'Error al guardar la consulta'];
        }

        $idConsulta = (int) $db->lastInsertId();

        foreach ($tratamientos as $tratamiento) {
            if (empty($tratamiento['medicamento']) || empty($tratamiento['presentacion']) || empty($tratamiento['dosis_frecuencia'])) {
                continue;
            }

            insertarDatos($db, 'tratamientos', [
                'id_consulta' => $idConsulta,
                'medicamento' => $tratamiento['medicamento'],
                'presentacion' => $tratamiento['presentacion'],
                'dosis_frecuencia' => $tratamiento['dosis_frecuencia'],
                'funcion' => $tratamiento['funcion'] ?? null
            ]);
        }

        registrarArchivosAdjuntos($db, $idCita, $idConsulta, $archivosAdjuntos);

        cambiarEstadoCita($db, $idCita, 'realizada');

        if ($eliminarBorrador && $idUsuario !== null) {
            eliminarBorrador($db, $idCita, $idUsuario);
        }

        $recetaUrl = null;
        if ($generarReceta) {
            try {
                $recetaResultado = generarRecetaPDF($db, $idConsulta, $idCita);
                if ($recetaResultado['exito']) {
                    $recetaUrl = $recetaResultado['ruta'];
                } else {
                    registrarError('Error al generar receta PDF: ' . ($recetaResultado['error'] ?? 'desconocido'));
                }
            } catch (\Exception $e) {
                registrarError('Excepcion al generar receta PDF: ' . $e->getMessage());
            }
        }

        if (!$transaccionActiva) {
            $db->commit();
        }

        return ['exito' => true, 'id_consulta' => $idConsulta, 'receta_url' => $recetaUrl];
    } catch (\Exception $e) {
        if (!$transaccionActiva) {
            $db->rollBack();
        }
        registrarError('Error al finalizar consulta: ' . $e->getMessage());
        return ['exito' => false, 'error' => 'Error interno al finalizar consulta'];
    }
}
}

if (!function_exists('guardarArchivoTemporal')) {
function guardarArchivoTemporal(int $idCita, int $idPaciente, array $archivo): array
{
    $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
    $tamanoMaximo = 5 * 1024 * 1024;

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas)) {
        return ['exito' => false, 'error' => 'Tipo de archivo no permitido. Solo PDF, JPG, PNG'];
    }

    if ($archivo['size'] > $tamanoMaximo) {
        return ['exito' => false, 'error' => 'El archivo excede el tamano maximo de 5MB'];
    }

    $directorio = __DIR__ . '/../../storage/pacientes/temp/' . $idCita;

    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $nombreSeguro = 'adjunto_' . $idCita . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $rutaSegura = $directorio . '/' . $nombreSeguro;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaSegura)) {
        return ['exito' => false, 'error' => 'Error al subir el archivo'];
    }

    $rutaRelativa = '/storage/pacientes/temp/' . $idCita . '/' . $nombreSeguro;

    return ['exito' => true, 'nombre_original' => $archivo['name'], 'ruta' => $rutaRelativa, 'extension' => $extension];
}
}

if (!function_exists('registrarArchivosAdjuntos')) {
function registrarArchivosAdjuntos(PDO $db, int $idCita, int $idConsulta, array $archivos): void
{
    $citas = obtenerDatos($db, 'citas', 'id_cita = ?', [$idCita]);
    if (empty($citas)) {
        return;
    }

    $idPaciente = (int) $citas[0]['id_paciente'];
    $directorioFinal = __DIR__ . '/../../storage/pacientes/' . $idPaciente;

    if (!is_dir($directorioFinal)) {
        mkdir($directorioFinal, 0755, true);
    }

    foreach ($archivos as $archivo) {
        if (empty($archivo['nombre_original']) || empty($archivo['ruta'])) {
            continue;
        }

        $rutaTemporal = __DIR__ . '/../../' . $archivo['ruta'];

        if (file_exists($rutaTemporal)) {
            $nombreFinal = basename($rutaTemporal);
            $rutaFinal = $directorioFinal . '/' . $nombreFinal;
            rename($rutaTemporal, $rutaFinal);
            $rutaRelativaFinal = '/storage/pacientes/' . $idPaciente . '/' . $nombreFinal;
        } else {
            $rutaRelativaFinal = $archivo['ruta'];
        }

        insertarDatos($db, 'archivos_adjuntos', [
            'id_consulta' => $idConsulta,
            'nombre_original' => $archivo['nombre_original'],
            'ruta_segura' => $rutaRelativaFinal
        ]);
    }
}
}

if (!function_exists('limpiarRecetasViejas')) {
function limpiarRecetasViejas(string $ruta, int $dias = 7): int
{
    $contador = 0;
    $tiempoLimite = time() - ($dias * 24 * 60 * 60);

    if (!is_dir($ruta)) {
        return 0;
    }

    $archivos = glob($ruta . '/*.pdf');

    foreach ($archivos as $archivo) {
        if (filemtime($archivo) < $tiempoLimite) {
            unlink($archivo);
            $contador++;
        }
    }

    return $contador;
}
}

if (!function_exists('limpiarArchivosTemporales')) {
function limpiarArchivosTemporales(string $rutaBase, int $horas = 24): int
{
    $contador = 0;
    $tiempoLimite = time() - ($horas * 60 * 60);

    if (!is_dir($rutaBase)) {
        return 0;
    }

    $directoriosTemp = glob($rutaBase . '/temp/*', GLOB_ONLYDIR);

    foreach ($directoriosTemp as $directorio) {
        if (filemtime($directorio) < $tiempoLimite) {
            $archivos = glob($directorio . '/*');
            foreach ($archivos as $archivo) {
                unlink($archivo);
                $contador++;
            }
            rmdir($directorio);
        }
    }

    return $contador;
}
}

if (!function_exists('obtenerOGenerarReceta')) {
function obtenerOGenerarReceta(PDO $db, int $idConsulta, int $idCita): array
{
    $directorio = __DIR__ . '/../../storage/recetas_temporales';

    if (is_dir($directorio)) {
        $patron = $directorio . '/receta_' . $idCita . '_*.pdf';
        $archivos = glob($patron);

        if (!empty($archivos)) {
            $rutaRelativa = '/storage/recetas_temporales/' . basename(end($archivos));
            return ['exito' => true, 'ruta' => $rutaRelativa, 'existente' => true];
        }
    }

    return generarRecetaPDF($db, $idConsulta, $idCita);
}
}

if (!function_exists('generarRecetaPDF')) {
function generarRecetaPDF(PDO $db, int $idConsulta, int $idCita): array
{
    require_once __DIR__ . '/../vendor/autoload.php';

    $datos = ejecutarConsulta(
        $db,
        "SELECT c.id_cita, c.fecha_hora,
                p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
                p.fecha_nacimiento, p.sexo,
                u.nombre AS medico_nombre, u.apellidos AS medico_apellidos,
                u.correo AS medico_correo, u.telefono AS medico_telefono,
                q.motivo_consulta, q.anamnesis, q.diagnostico,
                q.peso, q.talla, q.temperatura, q.frec_cardiaca, q.frec_respiratoria,
                q.perimetro_cefalico, q.notas_laboratorio
         FROM consultas q
         INNER JOIN citas c ON q.id_cita = c.id_cita
         INNER JOIN paciente p ON c.id_paciente = p.id_paciente
         INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
         WHERE q.id_consulta = ?",
        [$idConsulta]
    );

    if (empty($datos)) {
        return ['exito' => false, 'error' => 'Consulta no encontrada'];
    }

    $consulta = $datos[0];

    $tratamientosRaw = ejecutarConsulta(
        $db,
        "SELECT medicamento, presentacion, dosis_frecuencia, funcion
         FROM tratamientos
         WHERE id_consulta = ?
         ORDER BY id_tratamiento ASC",
        [$idConsulta]
    );

    $vars = [
        'medico_nombre_completo' => htmlspecialchars($consulta['medico_nombre'] . ' ' . $consulta['medico_apellidos']),
        'medico_correo' => htmlspecialchars($consulta['medico_correo']),
        'medico_telefono' => htmlspecialchars($consulta['medico_telefono']),
        'fecha_consulta' => date('d/m/Y', strtotime($consulta['fecha_hora'])),
        'paciente_nombre_completo' => htmlspecialchars($consulta['paciente_nombre'] . ' ' . $consulta['paciente_apellidos']),
        'paciente_edad' => calcularEdad($consulta['fecha_nacimiento']),
        'paciente_sexo' => htmlspecialchars($consulta['sexo']),
        'paciente_fecha_nacimiento' => date('d/m/Y', strtotime($consulta['fecha_nacimiento'])),
        'signos_vitales' => [
            ['etiqueta' => 'Peso', 'valor' => $consulta['peso'] ? $consulta['peso'] . ' kg' : 'N/A'],
            ['etiqueta' => 'Talla', 'valor' => $consulta['talla'] ? $consulta['talla'] . ' cm' : 'N/A'],
            ['etiqueta' => 'Perimetro Cefalico', 'valor' => $consulta['perimetro_cefalico'] ? $consulta['perimetro_cefalico'] . ' cm' : 'N/A'],
            ['etiqueta' => 'Temperatura', 'valor' => $consulta['temperatura'] ? $consulta['temperatura'] . ' C' : 'N/A'],
            ['etiqueta' => 'Frec. Cardiaca', 'valor' => $consulta['frec_cardiaca'] ? $consulta['frec_cardiaca'] . ' lpm' : 'N/A'],
            ['etiqueta' => 'Frec. Respiratoria', 'valor' => $consulta['frec_respiratoria'] ? $consulta['frec_respiratoria'] . ' rpm' : 'N/A'],
        ],
        'motivo_consulta' => htmlspecialchars($consulta['motivo_consulta'] ?? 'No especificado'),
        'diagnostico_html' => nl2br(htmlspecialchars($consulta['diagnostico'])),
        'anamnesis_html' => !empty($consulta['anamnesis']) ? nl2br(htmlspecialchars($consulta['anamnesis'])) : null,
        'notas_laboratorio_html' => !empty($consulta['notas_laboratorio']) ? nl2br(htmlspecialchars($consulta['notas_laboratorio'])) : null,
        'tratamientos' => array_map(function($i, $t) {
            return [
                'numero' => $i + 1,
                'medicamento' => htmlspecialchars($t['medicamento']),
                'presentacion' => htmlspecialchars($t['presentacion']),
                'dosis_frecuencia' => htmlspecialchars($t['dosis_frecuencia']),
                'funcion' => htmlspecialchars($t['funcion'] ?? 'No especificada'),
            ];
        }, array_keys($tratamientosRaw), $tratamientosRaw),
    ];

    $html = renderizarPlantilla(__DIR__ . '/../plantillas/receta/default.php', $vars);

    try {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('Letter', 'portrait');
        $dompdf->render();

        $directorio = __DIR__ . '/../../storage/recetas_temporales';

        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $nombreArchivo = 'receta_' . $idCita . '_' . time() . '.pdf';
        $rutaArchivo = $directorio . '/' . $nombreArchivo;

        file_put_contents($rutaArchivo, $dompdf->output());

        return ['exito' => true, 'ruta' => '/storage/recetas_temporales/' . $nombreArchivo];
    } catch (\Exception $e) {
        registrarError('Error al generar receta PDF: ' . $e->getMessage());
        return ['exito' => false, 'error' => 'Error al generar la receta PDF'];
    }
}
}

if (!function_exists('renderizarPlantilla')) {
function renderizarPlantilla(string $ruta, array $vars): string
{
    extract($vars);
    ob_start();
    require $ruta;
    return ob_get_clean();
}
}
