<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class VacunasTest extends TestCase
{
    private static $db = null;
    private static $idPaciente = null;
    private static $idPacienteBebe = null;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../funciones/Funciones_SQL.php';
        require_once __DIR__ . '/../../funciones/vacunas.php';
        self::$db = conectar();

        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'PacienteVacunas',
            'apellidos' => 'Test Mayor',
            'fecha_nacimiento' => '2024-01-15',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ]);
        self::$idPaciente = (int) self::$db->lastInsertId();

        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'PacienteBebe',
            'apellidos' => 'Test Bebe',
            'fecha_nacimiento' => date('Y-m-d', strtotime('-1 month')),
            'sexo' => 'femenino',
            'estado' => 'activo'
        ]);
        self::$idPacienteBebe = (int) self::$db->lastInsertId();
    }

    protected function setUp(): void
    {
        self::$db->beginTransaction();
    }

    protected function tearDown(): void
    {
        self::$db->rollBack();
    }

    public function testObtenerVacunasCatalogo(): void
    {
        $vacunas = obtenerVacunasCatalogo(self::$db);
        $this->assertIsArray($vacunas);
        $this->assertNotEmpty($vacunas);
        $this->assertGreaterThanOrEqual(14, count($vacunas));

        foreach ($vacunas as $vacuna) {
            $this->assertArrayHasKey('id_vacuna', $vacuna);
            $this->assertArrayHasKey('nombre', $vacuna);
            $this->assertArrayHasKey('esquema_edad', $vacuna);
        }
    }

    public function testObtenerVacunasPacienteVacio(): void
    {
        $vacunas = obtenerVacunasPaciente(self::$db, self::$idPaciente);
        $this->assertIsArray($vacunas);
        $this->assertEmpty($vacunas);
    }

    public function testRegistrarVacunaInterna(): void
    {
        $catalogo = obtenerVacunasCatalogo(self::$db);
        $idVacuna = (int) $catalogo[0]['id_vacuna'];

        $resultado = registrarVacuna(self::$db, self::$idPaciente, [
            'id_vacuna' => $idVacuna,
            'fecha_aplicacion' => date('Y-m-d'),
            'aplicada_externamente' => false,
            'lote' => 'LOTE-TEST-001',
        ]);

        $this->assertTrue($resultado);

        $vacunas = obtenerVacunasPaciente(self::$db, self::$idPaciente);
        $this->assertCount(1, $vacunas);
        $this->assertEquals('LOTE-TEST-001', $vacunas[0]['lote']);
        $this->assertEquals(0, (int) $vacunas[0]['aplicada_externamente']);
    }

    public function testRegistrarVacunaExternaSinLote(): void
    {
        $catalogo = obtenerVacunasCatalogo(self::$db);
        $idVacuna = (int) $catalogo[1]['id_vacuna'];

        $resultado = registrarVacuna(self::$db, self::$idPaciente, [
            'id_vacuna' => $idVacuna,
            'fecha_aplicacion' => date('Y-m-d'),
            'aplicada_externamente' => true,
            'lote' => '',
        ]);

        $this->assertTrue($resultado);

        $vacunas = obtenerVacunasPaciente(self::$db, self::$idPaciente);
        $this->assertCount(1, $vacunas);
        $this->assertEquals(1, (int) $vacunas[0]['aplicada_externamente']);
        $this->assertNull($vacunas[0]['lote']);
    }

    public function testVacunaAtrasadaDetectada(): void
    {
        $pendientes = calcularVacunasPendientes(self::$db, self::$idPaciente);
        $this->assertIsArray($pendientes);
        $this->assertNotEmpty($pendientes);

        $nombres = array_map(fn($v) => $v['nombre'], $pendientes);
        $this->assertContains('BCG (Tuberculosis)', $nombres);
        $this->assertContains('Hepatitis B', $nombres);
    }

    public function testVacunaNoAtrasadaAun(): void
    {
        $pendientes = calcularVacunasPendientes(self::$db, self::$idPacienteBebe);
        $this->assertIsArray($pendientes);

        $nombres = array_map(fn($v) => $v['nombre'], $pendientes);
        $this->assertNotContains('Pentavalente (DPT-Hib-HepB)', $nombres);
    }

    public function testVacunaDuplicada(): void
    {
        $catalogo = obtenerVacunasCatalogo(self::$db);
        $idVacuna = (int) $catalogo[0]['id_vacuna'];
        $fechaHoy = date('Y-m-d');

        $resultado1 = registrarVacuna(self::$db, self::$idPaciente, [
            'id_vacuna' => $idVacuna,
            'fecha_aplicacion' => $fechaHoy,
            'aplicada_externamente' => false,
            'lote' => 'LOTE-001',
        ]);
        $this->assertTrue($resultado1);

        $esDuplicada = verificarVacunaDuplicada(self::$db, self::$idPaciente, $idVacuna, $fechaHoy);
        $this->assertTrue($esDuplicada);

        $resultado2 = registrarVacuna(self::$db, self::$idPaciente, [
            'id_vacuna' => $idVacuna,
            'fecha_aplicacion' => $fechaHoy,
            'aplicada_externamente' => false,
            'lote' => 'LOTE-002',
        ]);
        $this->assertTrue($resultado2);

        $vacunas = obtenerVacunasPaciente(self::$db, self::$idPaciente);
        $this->assertCount(2, $vacunas);
    }

    public function testCartillaCronologica(): void
    {
        $catalogo = obtenerVacunasCatalogo(self::$db);

        registrarVacuna(self::$db, self::$idPaciente, [
            'id_vacuna' => (int) $catalogo[0]['id_vacuna'],
            'fecha_aplicacion' => '2024-02-01',
            'aplicada_externamente' => false,
            'lote' => 'A',
        ]);

        registrarVacuna(self::$db, self::$idPaciente, [
            'id_vacuna' => (int) $catalogo[1]['id_vacuna'],
            'fecha_aplicacion' => '2024-01-15',
            'aplicada_externamente' => false,
            'lote' => 'B',
        ]);

        $vacunas = obtenerVacunasPaciente(self::$db, self::$idPaciente);
        $this->assertCount(2, $vacunas);
        $this->assertEquals('2024-02-01', $vacunas[0]['fecha_aplicacion']);
        $this->assertEquals('2024-01-15', $vacunas[1]['fecha_aplicacion']);
    }

    public function testParsearEsquemaEdadRecienNacido(): void
    {
        $this->assertEquals(0, parsearEsquemaEdadMeses('Recien nacido'));
    }

    public function testParsearEsquemaEdadMeses(): void
    {
        $this->assertEquals(2, parsearEsquemaEdadMeses('2 meses'));
        $this->assertEquals(6, parsearEsquemaEdadMeses('6 meses'));
    }

    public function testParsearEsquemaEdadAnios(): void
    {
        $this->assertEquals(12, parsearEsquemaEdadMeses('12 meses'));
        $this->assertEquals(72, parsearEsquemaEdadMeses('6 anos'));
    }

    public function testParsearEsquemaEdadRango(): void
    {
        $this->assertEquals(6, parsearEsquemaEdadMeses('6-23 meses'));
    }

    public function testVacunaAplicadaNoAparecePendiente(): void
    {
        $catalogo = obtenerVacunasCatalogo(self::$db);
        $idVacuna = (int) $catalogo[0]['id_vacuna'];

        registrarVacuna(self::$db, self::$idPaciente, [
            'id_vacuna' => $idVacuna,
            'fecha_aplicacion' => date('Y-m-d'),
            'aplicada_externamente' => false,
            'lote' => 'LOTE-TEST',
        ]);

        $pendientes = calcularVacunasPendientes(self::$db, self::$idPaciente);
        foreach ($pendientes as $pendiente) {
            $this->assertNotEquals($idVacuna, $pendiente['id_vacuna']);
        }
    }
}
