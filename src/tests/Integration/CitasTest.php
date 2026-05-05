<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CitasTest extends TestCase
{
    private static $db = null;
    private static $idPaciente = null;
    private static $idPediatra = null;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../funciones/Funciones_SQL.php';
        require_once __DIR__ . '/../../funciones/citas.php';
        self::$db = conectar();

        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'PacientePrueba',
            'apellidos' => 'Citas Test',
            'fecha_nacimiento' => '2020-01-01',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ]);
        self::$idPaciente = (int) self::$db->lastInsertId();

        $usuarios = obtenerDatos(self::$db, 'usuarios', "rol = 'pediatra' AND estado = 'activo'");
        self::$idPediatra = (int) $usuarios[0]['id_usuario'];
    }

    protected function setUp(): void
    {
        self::$db->beginTransaction();
    }

    protected function tearDown(): void
    {
        self::$db->rollBack();
    }

    public function testCalcularDuracionPrimeraVez(): void
    {
        $duracion = calcularDuracionCita(self::$db, 'primera_vez');
        $this->assertIsInt($duracion);
        $this->assertGreaterThan(0, $duracion);
    }

    public function testCalcularDuracionConsecuente(): void
    {
        $duracion = calcularDuracionCita(self::$db, 'consecuente');
        $this->assertIsInt($duracion);
        $this->assertGreaterThan(0, $duracion);
    }

    public function testAgendarCitaPrimeraVezExitosa(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+7 days 10:00:00'));

        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'primera_vez',
            'Consulta inicial'
        );

        $this->assertTrue($resultado['exito']);
        $this->assertArrayHasKey('id_cita', $resultado);

        $citas = obtenerDatos(self::$db, 'citas', 'id_cita = ?', [$resultado['id_cita']]);
        $this->assertCount(1, $citas);
        $this->assertEquals('primera_vez', $citas[0]['tipo_cita']);
        $this->assertEquals('pendiente', $citas[0]['estado']);
        $this->assertEquals('Consulta inicial', $citas[0]['motivo']);
    }

    public function testAgendarCitaConsecuenteExitosa(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+7 days 14:00:00'));

        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );

        $this->assertTrue($resultado['exito']);
        $this->assertArrayHasKey('id_cita', $resultado);

        $citas = obtenerDatos(self::$db, 'citas', 'id_cita = ?', [$resultado['id_cita']]);
        $this->assertEquals('consecuente', $citas[0]['tipo_cita']);
    }

    public function testAntiDoubleBookingRechazaSuperposicion(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+10 days 09:00:00'));

        $resultado1 = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'primera_vez'
        );
        $this->assertTrue($resultado1['exito']);

        $fechaSuperpuesta = date('Y-m-d H:i:s', strtotime('+10 days 09:15:00'));

        $resultado2 = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaSuperpuesta,
            'consecuente'
        );

        $this->assertFalse($resultado2['exito']);
        $this->assertEquals('Horario no disponible', $resultado2['error']);
    }

    public function testCitaCanceladaNoBloqueaHorario(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+15 days 11:00:00'));

        $resultado1 = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'primera_vez'
        );
        $this->assertTrue($resultado1['exito']);

        cambiarEstadoCita(self::$db, $resultado1['id_cita'], 'cancelada');

        $resultado2 = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );

        $this->assertTrue($resultado2['exito']);
    }

    public function testCambiarEstadoConfirmada(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+20 days 10:00:00'));

        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );
        $this->assertTrue($resultado['exito']);

        $cambio = cambiarEstadoCita(self::$db, $resultado['id_cita'], 'confirmada');
        $this->assertTrue($cambio);

        $cita = obtenerDatos(self::$db, 'citas', 'id_cita = ?', [$resultado['id_cita']]);
        $this->assertEquals('confirmada', $cita[0]['estado']);
    }

    public function testCambiarEstadoCancelada(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+25 days 15:00:00'));

        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );
        $this->assertTrue($resultado['exito']);

        $cambio = cambiarEstadoCita(self::$db, $resultado['id_cita'], 'cancelada');
        $this->assertTrue($cambio);

        $cita = obtenerDatos(self::$db, 'citas', 'id_cita = ?', [$resultado['id_cita']]);
        $this->assertEquals('cancelada', $cita[0]['estado']);
    }

    public function testCambiarEstadoInvalidoRetornaFalso(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+30 days 09:00:00'));

        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );
        $this->assertTrue($resultado['exito']);

        $cambio = cambiarEstadoCita(self::$db, $resultado['id_cita'], 'estado_invalido');
        $this->assertFalse($cambio);
    }

    public function testObtenerDetalleCitaConTutores(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+35 days 10:00:00'));

        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );
        $this->assertTrue($resultado['exito']);

        insertarDatos(self::$db, 'tutor', [
            'nombre' => 'Tutor',
            'apellidos' => 'Prueba',
            'telefono' => '5550001111',
            'estado' => 'activo'
        ]);
        $idTutor = (int) self::$db->lastInsertId();

        insertarDatos(self::$db, 'paciente_tutor', [
            'id_paciente' => self::$idPaciente,
            'id_tutor' => $idTutor,
            'parentesco' => 'Madre'
        ]);

        $detalle = obtenerDetalleCita(self::$db, $resultado['id_cita']);
        $this->assertNotNull($detalle);
        $this->assertEquals('PacientePrueba', $detalle['paciente_nombre']);
        $this->assertArrayHasKey('tutores', $detalle);
        $this->assertCount(1, $detalle['tutores']);
        $this->assertEquals('Tutor', $detalle['tutores'][0]['nombre']);
    }

    public function testObtenerDetalleCitaInexistenteRetornaNull(): void
    {
        $detalle = obtenerDetalleCita(self::$db, 99999);
        $this->assertNull($detalle);
    }

    public function testObtenerCitasCalendarioRetornaEventos(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+40 days 10:00:00'));

        agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'primera_vez'
        );

        $fechaInicio = date('Y-m-d H:i:s', strtotime('+40 days 00:00:00'));
        $fechaFin = date('Y-m-d H:i:s', strtotime('+40 days 23:59:59'));

        $eventos = obtenerCitasCalendario(self::$db, $fechaInicio, $fechaFin);
        $this->assertIsArray($eventos);
        $this->assertNotEmpty($eventos);

        $evento = $eventos[0];
        $this->assertArrayHasKey('id', $evento);
        $this->assertArrayHasKey('title', $evento);
        $this->assertArrayHasKey('start', $evento);
        $this->assertArrayHasKey('end', $evento);
        $this->assertArrayHasKey('extendedProps', $evento);
        $this->assertEquals('pendiente', $evento['extendedProps']['estado']);
    }

    public function testObtenerCitasCalendarioExcluyeCanceladas(): void
    {
        $fechaHora = date('Y-m-d H:i:s', strtotime('+45 days 10:00:00'));

        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );
        cambiarEstadoCita(self::$db, $resultado['id_cita'], 'cancelada');

        $fechaInicio = date('Y-m-d H:i:s', strtotime('+45 days 00:00:00'));
        $fechaFin = date('Y-m-d H:i:s', strtotime('+45 days 23:59:59'));

        $eventos = obtenerCitasCalendario(self::$db, $fechaInicio, $fechaFin);
        $this->assertEmpty($eventos);
    }

    public function testListarPediatras(): void
    {
        $pediatras = listarPediatras(self::$db);
        $this->assertIsArray($pediatras);
        $this->assertNotEmpty($pediatras);

        foreach ($pediatras as $pediatra) {
            $this->assertEquals('pediatra', $pediatra['rol']);
            $this->assertEquals('activo', $pediatra['estado']);
        }
    }

    public function testBuscarPacientesParaCita(): void
    {
        $resultados = buscarPacientesParaCita(self::$db, 'PacientePrueba');
        $this->assertIsArray($resultados);
        $this->assertNotEmpty($resultados);
        $this->assertEquals('PacientePrueba', $resultados[0]['nombre']);
    }

    public function testBuscarPacientesParaCitaSinResultados(): void
    {
        $resultados = buscarPacientesParaCita(self::$db, 'xyznoexiste123');
        $this->assertIsArray($resultados);
        $this->assertEmpty($resultados);
    }

    public function testHorariosDiferentesMismoDiaPermitidos(): void
    {
        $fecha1 = date('Y-m-d H:i:s', strtotime('+50 days 09:00:00'));
        $fecha2 = date('Y-m-d H:i:s', strtotime('+50 days 15:00:00'));

        $resultado1 = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fecha1,
            'consecuente'
        );
        $this->assertTrue($resultado1['exito']);

        $resultado2 = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fecha2,
            'consecuente'
        );
        $this->assertTrue($resultado2['exito']);
    }
}
