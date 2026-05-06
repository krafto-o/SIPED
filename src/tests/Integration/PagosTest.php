<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PagosTest extends TestCase
{
    private static $db = null;
    private static $idPaciente = null;
    private static $idPediatra = null;
    private static $idConsultaPendiente = null;
    private static $idConsultaPagada = null;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../funciones/Funciones_SQL.php';
        require_once __DIR__ . '/../../funciones/pacientes.php';
        require_once __DIR__ . '/../../funciones/citas.php';
        require_once __DIR__ . '/../../funciones/consultas.php';
        require_once __DIR__ . '/../../funciones/pagos.php';

        self::$db = conectar();

        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'PacientePrueba',
            'apellidos' => 'Pagos Test',
            'fecha_nacimiento' => '2020-03-10',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ]);
        self::$idPaciente = (int) self::$db->lastInsertId();

        $usuarios = obtenerDatos(self::$db, 'usuarios', "rol = 'pediatra' AND estado = 'activo'");
        self::$idPediatra = (int) $usuarios[0]['id_usuario'];

        ejecutarConsulta(self::$db, "DELETE FROM citas WHERE id_usuario = ?", [self::$idPediatra]);
        ejecutarConsulta(self::$db, "DELETE FROM borradores_consultas WHERE id_usuario = ?", [self::$idPediatra]);

        $fechaHora1 = '2028-07-01 10:00:00';
        $resultado1 = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora1,
            'primera_vez',
            'Consulta de prueba pago'
        );
        if (!$resultado1['exito']) {
            throw new \RuntimeException('Failed to create test cita 1: ' . ($resultado1['error'] ?? 'unknown'));
        }

        $datosConsulta1 = [
            'motivo_consulta' => 'Control general',
            'anamnesis' => 'Paciente acude a control',
            'peso' => '15.0',
            'talla' => '90.0',
            'diagnostico' => 'Paciente sano'
        ];

        $resultadoFinalizar = finalizarConsulta(self::$db, $resultado1['id_cita'], $datosConsulta1, []);
        if (!$resultadoFinalizar['exito']) {
            throw new \RuntimeException('Failed to finalize consulta 1: ' . ($resultadoFinalizar['error'] ?? 'unknown'));
        }
        self::$idConsultaPendiente = (int) $resultadoFinalizar['id_consulta'];

        $fechaHora2 = '2028-07-02 11:00:00';
        $resultado2 = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora2,
            'consecuente',
            'Seguimiento'
        );
        if (!$resultado2['exito']) {
            throw new \RuntimeException('Failed to create test cita 2: ' . ($resultado2['error'] ?? 'unknown'));
        }

        $datosConsulta2 = [
            'motivo_consulta' => 'Seguimiento',
            'diagnostico' => 'Seguimiento de tratamiento'
        ];

        $resultadoFinalizar2 = finalizarConsulta(self::$db, $resultado2['id_cita'], $datosConsulta2, []);
        if (!$resultadoFinalizar2['exito']) {
            throw new \RuntimeException('Failed to finalize consulta 2: ' . ($resultadoFinalizar2['error'] ?? 'unknown'));
        }
        self::$idConsultaPagada = (int) $resultadoFinalizar2['id_consulta'];

        insertarDatos(self::$db, 'pagos', [
            'id_consulta' => self::$idConsultaPagada,
            'monto' => 500.00,
            'forma_pago' => 'efectivo',
            'fecha_pago' => date('Y-m-d H:i:s')
        ]);
        ejecutarConsulta(
            self::$db,
            "UPDATE consultas SET estado_pago = 'pagado' WHERE id_consulta = ?",
            [self::$idConsultaPagada]
        );
    }

    protected function setUp(): void
    {
        self::$db->beginTransaction();
    }

    protected function tearDown(): void
    {
        self::$db->rollBack();
    }

    public function testObtenerConsultasPendientes(): void
    {
        $pendientes = obtenerConsultasPendientes(self::$db, null, 'recepcionista');
        $this->assertIsArray($pendientes);

        foreach ($pendientes as $consulta) {
            $this->assertEquals('pendiente', $this->obtenerEstadoPago((int) $consulta['id_consulta']));
        }
    }

    private function obtenerEstadoPago(int $idConsulta): string
    {
        $resultado = obtenerDatos(self::$db, 'consultas', 'id_consulta = ?', [$idConsulta]);
        return $resultado[0]['estado_pago'] ?? '';
    }

    public function testFiltraPorPediatra(): void
    {
        $pendientesPediatra = obtenerConsultasPendientes(self::$db, self::$idPediatra, 'pediatra');
        $this->assertIsArray($pendientesPediatra);

        foreach ($pendientesPediatra as $consulta) {
            $this->assertEquals(self::$idPediatra, (int) $this->obtenerMedicoId((int) $consulta['id_consulta']));
        }
    }

    private function obtenerMedicoId(int $idConsulta): int
    {
        $resultado = ejecutarConsulta(
            self::$db,
            "SELECT c.id_usuario FROM consultas q INNER JOIN citas c ON q.id_cita = c.id_cita WHERE q.id_consulta = ?",
            [$idConsulta]
        );
        return (int) ($resultado[0]['id_usuario'] ?? 0);
    }

    public function testExcluyeCitasCanceladas(): void
    {
        $fechaHora = '2028-07-03 09:00:00';
        $resultado = agendarCita(
            self::$db,
            self::$idPaciente,
            self::$idPediatra,
            $fechaHora,
            'consecuente'
        );
        $this->assertTrue($resultado['exito']);

        $datosConsulta = [
            'motivo_consulta' => 'Test',
            'diagnostico' => 'Diagnostico test'
        ];

        $finalizar = finalizarConsulta(self::$db, $resultado['id_cita'], $datosConsulta, []);
        $this->assertTrue($finalizar['exito']);

        cambiarEstadoCita(self::$db, $resultado['id_cita'], 'cancelada');

        $pendientes = obtenerConsultasPendientes(self::$db, null, 'recepcionista');
        foreach ($pendientes as $consulta) {
            $this->assertNotEquals((int) $finalizar['id_consulta'], (int) $consulta['id_consulta']);
        }
    }

    public function testRegistrarPagoExitoso(): void
    {
        $resultado = registrarPago(self::$db, self::$idConsultaPendiente, 350.00, 'efectivo');

        $this->assertTrue($resultado['exito']);

        $pagos = obtenerDatos(self::$db, 'pagos', 'id_consulta = ?', [self::$idConsultaPendiente]);
        $this->assertCount(1, $pagos);
        $this->assertEquals('350.00', $pagos[0]['monto']);
        $this->assertEquals('efectivo', $pagos[0]['forma_pago']);

        $consulta = obtenerDatos(self::$db, 'consultas', 'id_consulta = ?', [self::$idConsultaPendiente]);
        $this->assertEquals('pagado', $consulta[0]['estado_pago']);
    }

    public function testRegistrarPagoTarjeta(): void
    {
        $fechaHora = '2028-07-04 10:00:00';
        $cita = agendarCita(self::$db, self::$idPaciente, self::$idPediatra, $fechaHora, 'consecuente');
        $this->assertTrue($cita['exito']);

        $datosConsulta = [
            'motivo_consulta' => 'Test',
            'diagnostico' => 'Diagnostico'
        ];
        $finalizar = finalizarConsulta(self::$db, $cita['id_cita'], $datosConsulta, []);
        $this->assertTrue($finalizar['exito']);

        $resultado = registrarPago(self::$db, (int) $finalizar['id_consulta'], 500.00, 'tarjeta');
        $this->assertTrue($resultado['exito']);

        $pagos = obtenerDatos(self::$db, 'pagos', 'id_consulta = ?', [$finalizar['id_consulta']]);
        $this->assertEquals('tarjeta', $pagos[0]['forma_pago']);
    }

    public function testRegistrarPagoTransferencia(): void
    {
        $fechaHora = '2028-07-05 10:00:00';
        $cita = agendarCita(self::$db, self::$idPaciente, self::$idPediatra, $fechaHora, 'consecuente');
        $this->assertTrue($cita['exito']);

        $datosConsulta = [
            'motivo_consulta' => 'Test',
            'diagnostico' => 'Diagnostico'
        ];
        $finalizar = finalizarConsulta(self::$db, $cita['id_cita'], $datosConsulta, []);
        $this->assertTrue($finalizar['exito']);

        $resultado = registrarPago(self::$db, (int) $finalizar['id_consulta'], 750.50, 'transferencia');
        $this->assertTrue($resultado['exito']);

        $pagos = obtenerDatos(self::$db, 'pagos', 'id_consulta = ?', [$finalizar['id_consulta']]);
        $this->assertEquals('transferencia', $pagos[0]['forma_pago']);
    }

    public function testRechazaMontoInvalido(): void
    {
        $resultado = registrarPago(self::$db, self::$idConsultaPendiente, 0, 'efectivo');
        $this->assertFalse($resultado['exito']);
        $this->assertStringContainsString('monto', strtolower($resultado['error']));

        $resultadoNegativo = registrarPago(self::$db, self::$idConsultaPendiente, -100, 'efectivo');
        $this->assertFalse($resultadoNegativo['exito']);
    }

    public function testRechazaFormaPagoInvalida(): void
    {
        $resultado = registrarPago(self::$db, self::$idConsultaPendiente, 300, 'bitcoin');
        $this->assertFalse($resultado['exito']);
        $this->assertStringContainsString('forma de pago', strtolower($resultado['error']));
    }

    public function testRechazaConsultaYaPagada(): void
    {
        $resultado = registrarPago(self::$db, self::$idConsultaPagada, 500, 'efectivo');
        $this->assertFalse($resultado['exito']);
        $this->assertStringContainsString('pagada', strtolower($resultado['error']));
    }

    public function testRechazaConsultaInexistente(): void
    {
        $resultado = registrarPago(self::$db, 99999, 500, 'efectivo');
        $this->assertFalse($resultado['exito']);
    }

    public function testObtenerDetalleConsultaParaPago(): void
    {
        $detalle = obtenerDetalleConsultaParaPago(self::$db, self::$idConsultaPendiente);
        $this->assertNotNull($detalle);
        $this->assertEquals('PacientePrueba', $detalle['paciente_nombre']);
        $this->assertArrayHasKey('medico_nombre', $detalle);
        $this->assertArrayHasKey('fecha_hora', $detalle);
        $this->assertArrayHasKey('tipo_cita', $detalle);
        $this->assertArrayNotHasKey('motivo_consulta', $detalle);
        $this->assertArrayNotHasKey('diagnostico', $detalle);
    }

    public function testObtenerDetalleConsultaParaPagoNullSiPagada(): void
    {
        $detalle = obtenerDetalleConsultaParaPago(self::$db, self::$idConsultaPagada);
        $this->assertNull($detalle);
    }

    public function testObtenerDetalleConsultaParaPagoNullSiInexistente(): void
    {
        $detalle = obtenerDetalleConsultaParaPago(self::$db, 99999);
        $this->assertNull($detalle);
    }
}
