<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ConsultasTest extends TestCase
{
    private static $db = null;
    private static $idPaciente = null;
    private static $idPediatra = null;
    private static $idCita = null;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../funciones/Funciones_SQL.php';
        require_once __DIR__ . '/../../funciones/pacientes.php';
        require_once __DIR__ . '/../../funciones/citas.php';
        require_once __DIR__ . '/../../funciones/consultas.php';

        self::$db = conectar();

        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'PacientePrueba',
            'apellidos' => 'Consultas Test',
            'fecha_nacimiento' => '2021-06-15',
            'sexo' => 'femenino',
            'tipo_sangre' => 'O+',
            'alergias' => 'Penicilina',
            'estado' => 'activo'
        ]);
        self::$idPaciente = (int) self::$db->lastInsertId();

        $usuarios = obtenerDatos(self::$db, 'usuarios', "rol = 'pediatra' AND estado = 'activo'");
        self::$idPediatra = (int) $usuarios[0]['id_usuario'];

        ejecutarConsulta(self::$db, "DELETE FROM citas WHERE id_usuario = ?", [self::$idPediatra]);
        ejecutarConsulta(self::$db, "DELETE FROM borradores_consultas WHERE id_usuario = ?", [self::$idPediatra]);

        $fechaHora = '2028-06-15 03:00:00';
        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'primera_vez',
            'Consulta de prueba'
        );

        if (!$resultado['exito']) {
            throw new \RuntimeException('Failed to create test cita: ' . ($resultado['error'] ?? 'unknown'));
        }

        self::$idCita = $resultado['id_cita'];
    }

    protected function setUp(): void
    {
        self::$db->beginTransaction();
    }

    protected function tearDown(): void
    {
        self::$db->rollBack();
    }

    public function testObtenerDatosConsultaExitosa(): void
    {
        $datos = obtenerDatosConsulta(self::$db, self::$idCita, self::$idPediatra);

        $this->assertNotNull($datos);
        $this->assertEquals('PacientePrueba', $datos['paciente_nombre']);
        $this->assertEquals('Consultas Test', $datos['paciente_apellidos']);
        $this->assertEquals(self::$idPediatra, (int) $datos['medico_id']);
    }

    public function testObtenerDatosConsultaCitaInexistente(): void
    {
        $datos = obtenerDatosConsulta(self::$db, 99999, self::$idPediatra);
        $this->assertNull($datos);
    }

    public function testObtenerDatosConsultaOtroPediatra(): void
    {
        insertarDatos(self::$db, 'usuarios', [
            'nombre' => 'OtroPediatra',
            'apellidos' => 'Test',
            'correo' => 'otro_pediatra_test_' . time() . '@siped.com',
            'telefono' => '555' . time() % 1000000000,
            'password' => password_hash('siped123', PASSWORD_DEFAULT),
            'rol' => 'pediatra',
            'estado' => 'activo'
        ]);
        $otroPediatra = (int) self::$db->lastInsertId();

        $datos = obtenerDatosConsulta(self::$db, self::$idCita, $otroPediatra);
        $this->assertNull($datos);
    }

    public function testGuardarBorrador(): void
    {
        $datos = [
            'peso' => '12.5',
            'talla' => '85.0',
            'motivo_consulta' => 'Fiebre',
            'anamnesis' => 'Paciente con fiebre de 3 dias',
            'diagnostico' => 'Infeccion viral',
            'tratamientos' => []
        ];

        $exito = guardarBorrador(self::$db, self::$idCita, self::$idPediatra, $datos);
        $this->assertTrue($exito);

        $borrador = obtenerBorrador(self::$db, self::$idCita, self::$idPediatra);
        $this->assertNotNull($borrador);
        $this->assertEquals('12.5', $borrador['peso']);
        $this->assertEquals('Fiebre', $borrador['motivo_consulta']);
    }

    public function testActualizarBorrador(): void
    {
        $datos1 = ['peso' => '10.0', 'diagnostico' => 'Inicial'];
        guardarBorrador(self::$db, self::$idCita, self::$idPediatra, $datos1);

        $datos2 = ['peso' => '11.0', 'diagnostico' => 'Actualizado'];
        guardarBorrador(self::$db, self::$idCita, self::$idPediatra, $datos2);

        $borrador = obtenerBorrador(self::$db, self::$idCita, self::$idPediatra);
        $this->assertEquals('11.0', $borrador['peso']);
        $this->assertEquals('Actualizado', $borrador['diagnostico']);
    }

    public function testEliminarBorrador(): void
    {
        $datos = ['peso' => '10.0', 'diagnostico' => 'Test'];
        guardarBorrador(self::$db, self::$idCita, self::$idPediatra, $datos);

        $borrador = obtenerBorrador(self::$db, self::$idCita, self::$idPediatra);
        $this->assertNotNull($borrador);

        eliminarBorrador(self::$db, self::$idCita, self::$idPediatra);

        $borrador = obtenerBorrador(self::$db, self::$idCita, self::$idPediatra);
        $this->assertNull($borrador);
    }

    public function testFinalizarConsultaConTratamientos(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+401 days 03:00:00'));
        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );
        $idCitaNueva = $resultado['id_cita'];

        $datosConsulta = [
            'motivo_consulta' => 'Control',
            'anamnesis' => 'Paciente acude a control',
            'peso' => '13.5',
            'talla' => '88.0',
            'perimetro_cefalico' => '48.0',
            'temperatura' => '36.8',
            'frec_cardiaca' => 100,
            'frec_respiratoria' => 25,
            'notas_laboratorio' => 'Sin notas',
            'diagnostico' => 'Control sano'
        ];

        $tratamientos = [
            [
                'medicamento' => 'Paracetamol',
                'presentacion' => 'Jarabe',
                'dosis_frecuencia' => '5ml cada 8 horas',
                'funcion' => 'Antipiretico'
            ],
            [
                'medicamento' => 'Ibuprofeno',
                'presentacion' => 'Suspension',
                'dosis_frecuencia' => '3ml cada 6 horas',
                'funcion' => 'Antiinflamatorio'
            ]
        ];

        $resultado = finalizarConsulta(self::$db, $idCitaNueva, $datosConsulta, $tratamientos);

        $this->assertTrue($resultado['exito']);
        $this->assertArrayHasKey('id_consulta', $resultado);

        $consulta = obtenerDatos(self::$db, 'consultas', 'id_consulta = ?', [$resultado['id_consulta']]);
        $this->assertCount(1, $consulta);
        $this->assertEquals('Control sano', $consulta[0]['diagnostico']);
        $this->assertEquals('13.50', $consulta[0]['peso']);

        $tratamientosDb = obtenerDatos(self::$db, 'tratamientos', 'id_consulta = ?', [$resultado['id_consulta']]);
        $this->assertCount(2, $tratamientosDb);
        $this->assertEquals('Paracetamol', $tratamientosDb[0]['medicamento']);
        $this->assertEquals('Ibuprofeno', $tratamientosDb[1]['medicamento']);

        $cita = obtenerDatos(self::$db, 'citas', 'id_cita = ?', [$idCitaNueva]);
        $this->assertEquals('realizada', $cita[0]['estado']);
    }

    public function testFinalizarConsultaSinDiagnosticoFalla(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+402 days 03:00:00'));
        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );
        $idCitaNueva = $resultado['id_cita'];

        $datosConsulta = [
            'motivo_consulta' => 'Control',
            'anamnesis' => 'Paciente acude a control'
        ];

        $resultado = finalizarConsulta(self::$db, $idCitaNueva, $datosConsulta, []);

        $this->assertFalse($resultado['exito']);
    }

    public function testFinalizarConsultaSinTratamientos(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+403 days 03:00:00'));
        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );
        $idCitaNueva = $resultado['id_cita'];

        $datosConsulta = [
            'motivo_consulta' => 'Revision',
            'anamnesis' => 'Revision general',
            'diagnostico' => 'Paciente sano'
        ];

        $resultado = finalizarConsulta(self::$db, $idCitaNueva, $datosConsulta, []);

        $this->assertTrue($resultado['exito']);

        $tratamientosDb = obtenerDatos(self::$db, 'tratamientos', 'id_consulta = ?', [$resultado['id_consulta']]);
        $this->assertEmpty($tratamientosDb);
    }

    public function testObtenerHistorialPaciente(): void
    {
        $historial = obtenerHistorialPaciente(self::$db, self::$idPaciente);
        $this->assertIsArray($historial);
    }

    public function testObtenerHistorialPacienteSinConsultas(): void
    {
        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'PacienteNuevo',
            'apellidos' => 'Sin Historial',
            'fecha_nacimiento' => '2022-01-01',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ]);
        $idPacienteNuevo = (int) self::$db->lastInsertId();

        $historial = obtenerHistorialPaciente(self::$db, $idPacienteNuevo);
        $this->assertIsArray($historial);
        $this->assertEmpty($historial);
    }

    public function testLimpiarRecetasViejas(): void
    {
        $ruta = __DIR__ . '/../../../storage/recetas_temporales';

        if (!is_dir($ruta)) {
            mkdir($ruta, 0755, true);
        }

        $archivoViejo = $ruta . '/receta_vieja_' . time() . '.pdf';
        file_put_contents($archivoViejo, 'test');
        touch($archivoViejo, time() - (10 * 24 * 60 * 60));

        $archivoNuevo = $ruta . '/receta_nueva_' . time() . '.pdf';
        file_put_contents($archivoNuevo, 'test');

        $eliminadas = limpiarRecetasViejas($ruta, 7);
        $this->assertGreaterThanOrEqual(1, $eliminadas);

        $this->assertFileDoesNotExist($archivoViejo);
        $this->assertFileExists($archivoNuevo);

        if (file_exists($archivoNuevo)) {
            unlink($archivoNuevo);
        }
    }

    public function testCalcularEdadMenor3Anios(): void
    {
        $edad = calcularEdad(date('Y-m-d', strtotime('-2 years -6 months')));
        $this->assertStringContainsString('año', $edad);
        $this->assertStringContainsString('mes', $edad);
    }

    public function testCalcularEdadRecienNacido(): void
    {
        $fecha = date('Y-m-d', strtotime('-15 days'));
        $edad = calcularEdad($fecha);
        $this->assertEquals('Recien nacido', $edad);
    }

    public function testCalcularEdadMeses(): void
    {
        $fecha = date('Y-m-d', strtotime('-8 months'));
        $edad = calcularEdad($fecha);
        $this->assertStringContainsString('mes', $edad);
    }
}
