<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PacientesTest extends TestCase
{
    private static $db = null;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../funciones/Funciones_SQL.php';
        require_once __DIR__ . '/../../funciones/pacientes.php';
        self::$db = conectar();
    }

    protected function setUp(): void
    {
        self::$db->beginTransaction();
    }

    protected function tearDown(): void
    {
        self::$db->rollBack();
    }

    public function testCalcularEdadMenor3Anios(): void
    {
        $edad = calcularEdad(date('Y-m-d', strtotime('-2 years -4 months')));
        $this->assertStringContainsString('2 anio', $edad);
        $this->assertStringContainsString('4 mes', $edad);
    }

    public function testCalcularEdadMayor3Anios(): void
    {
        $edad = calcularEdad(date('Y-m-d', strtotime('-5 years -2 months')));
        $this->assertStringContainsString('5 anios', $edad);
        $this->assertStringNotContainsString('mes', $edad);
    }

    public function testCalcularEdadRecienNacido(): void
    {
        $edad = calcularEdad(date('Y-m-d'));
        $this->assertEquals('Recien nacido', $edad);
    }

    public function testCalcularEdadSoloMeses(): void
    {
        $edad = calcularEdad(date('Y-m-d', strtotime('-8 months')));
        $this->assertStringContainsString('8 mes', $edad);
        $this->assertStringNotContainsString('anio', $edad);
    }

    public function testRegistrarPacienteExitoso(): void
    {
        $datosPaciente = [
            'nombre' => 'Sofia',
            'apellidos' => 'Martinez Lopez',
            'fecha_nacimiento' => '2020-05-15',
            'sexo' => 'femenino',
            'estado' => 'activo'
        ];

        $tutores = [
            [
                'id_tutor_existente' => null,
                'nombre' => 'Ana',
                'apellidos' => 'Martinez',
                'telefono' => '5551112222',
                'correo' => 'ana@test.com',
                'direccion' => 'Calle 123',
                'parentesco' => 'Madre'
            ]
        ];

        $resultado = registrarPacienteConTutores(self::$db, $datosPaciente, $tutores);
        $this->assertTrue($resultado);

        $pacientes = obtenerDatos(self::$db, 'paciente', "nombre = 'Sofia' AND estado = 'activo'");
        $this->assertCount(1, $pacientes);
        $this->assertEquals('Martinez Lopez', $pacientes[0]['apellidos']);
    }

    public function testRegistrarPacienteConTutorExistente(): void
    {
        insertarDatos(self::$db, 'tutor', [
            'nombre' => 'Carlos',
            'apellidos' => 'Ramirez',
            'telefono' => '5553334444',
            'correo' => 'carlos@test.com',
            'estado' => 'activo'
        ]);
        $idTutor = (int) self::$db->lastInsertId();

        $datosPaciente = [
            'nombre' => 'Mateo',
            'apellidos' => 'Ramirez Garcia',
            'fecha_nacimiento' => '2019-03-10',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ];

        $tutores = [
            [
                'id_tutor_existente' => $idTutor,
                'nombre' => '',
                'apellidos' => '',
                'telefono' => '',
                'correo' => null,
                'direccion' => null,
                'parentesco' => 'Padre'
            ]
        ];

        $resultado = registrarPacienteConTutores(self::$db, $datosPaciente, $tutores);
        $this->assertTrue($resultado);

        $pivote = obtenerDatos(self::$db, 'paciente_tutor', 'id_tutor = ?', [$idTutor]);
        $this->assertCount(1, $pivote);
        $this->assertEquals('Padre', $pivote[0]['parentesco']);
    }

    public function testRegistrarPacienteDuplicadoFalla(): void
    {
        $datosPaciente = [
            'nombre' => 'Lucia',
            'apellidos' => 'Hernandez Ruiz',
            'fecha_nacimiento' => '2021-01-20',
            'sexo' => 'femenino',
            'estado' => 'activo'
        ];

        $tutores = [
            [
                'id_tutor_existente' => null,
                'nombre' => 'Maria',
                'apellidos' => 'Hernandez',
                'telefono' => '5556667777',
                'correo' => null,
                'direccion' => null,
                'parentesco' => 'Madre'
            ]
        ];

        registrarPacienteConTutores(self::$db, $datosPaciente, $tutores);

        $esDuplicado = validarDuplicadoPaciente(self::$db, 'Lucia', 'Hernandez Ruiz', '2021-01-20');
        $this->assertTrue($esDuplicado);
    }

    public function testValidarDuplicadoNoDetectaInactivos(): void
    {
        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Pedro',
            'apellidos' => 'Sanchez',
            'fecha_nacimiento' => '2018-06-15',
            'sexo' => 'masculino',
            'estado' => 'inactivo'
        ]);

        $esDuplicado = validarDuplicadoPaciente(self::$db, 'Pedro', 'Sanchez', '2018-06-15');
        $this->assertFalse($esDuplicado);
    }

    public function testDarBajaPaciente(): void
    {
        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Valentina',
            'apellidos' => 'Torres',
            'fecha_nacimiento' => '2020-08-10',
            'sexo' => 'femenino',
            'estado' => 'activo'
        ]);
        $idPaciente = (int) self::$db->lastInsertId();

        $resultado = darBajaPaciente(self::$db, $idPaciente);
        $this->assertTrue($resultado);

        $paciente = obtenerDatos(self::$db, 'paciente', 'id_paciente = ?', [$idPaciente]);
        $this->assertEquals('inactivo', $paciente[0]['estado']);
    }

    public function testDesvincularTutor(): void
    {
        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Diego',
            'apellidos' => 'Lopez',
            'fecha_nacimiento' => '2019-11-05',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ]);
        $idPaciente = (int) self::$db->lastInsertId();

        insertarDatos(self::$db, 'tutor', [
            'nombre' => 'Rosa',
            'apellidos' => 'Lopez',
            'telefono' => '5558889999',
            'estado' => 'activo'
        ]);
        $idTutor = (int) self::$db->lastInsertId();

        insertarDatos(self::$db, 'paciente_tutor', [
            'id_paciente' => $idPaciente,
            'id_tutor' => $idTutor,
            'parentesco' => 'Madre'
        ]);

        $resultado = desvincularTutor(self::$db, $idPaciente, $idTutor);
        $this->assertTrue($resultado);

        $pivote = obtenerDatos(self::$db, 'paciente_tutor', 'id_paciente = ? AND id_tutor = ?', [$idPaciente, $idTutor]);
        $this->assertEmpty($pivote);
    }

    public function testBuscarTutoresPorNombre(): void
    {
        insertarDatos(self::$db, 'tutor', [
            'nombre' => 'Fernanda',
            'apellidos' => 'Gomez',
            'telefono' => '5550001111',
            'estado' => 'activo'
        ]);

        insertarDatos(self::$db, 'tutor', [
            'nombre' => 'Francisco',
            'apellidos' => 'Diaz',
            'telefono' => '5550002222',
            'estado' => 'activo'
        ]);

        $resultados = buscarTutores(self::$db, 'Fer');
        $this->assertCount(1, $resultados);
        $this->assertEquals('Fernanda', $resultados[0]['nombre']);
    }

    public function testBuscarTutoresPorTelefono(): void
    {
        insertarDatos(self::$db, 'tutor', [
            'nombre' => 'Gabriela',
            'apellidos' => 'Morales',
            'telefono' => '5551234567',
            'estado' => 'activo'
        ]);

        $resultados = buscarTutores(self::$db, '555123');
        $this->assertCount(1, $resultados);
        $this->assertEquals('Gabriela', $resultados[0]['nombre']);
    }

    public function testListadoSoloPacientesActivos(): void
    {
        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Activo',
            'apellidos' => 'Uno',
            'fecha_nacimiento' => '2020-01-01',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ]);

        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Inactivo',
            'apellidos' => 'Dos',
            'fecha_nacimiento' => '2020-02-02',
            'sexo' => 'femenino',
            'estado' => 'inactivo'
        ]);

        $pacientes = listarPacientes(self::$db);
        $this->assertCount(1, $pacientes);
        $this->assertEquals('Activo', $pacientes[0]['nombre']);
    }

    public function testListadoConBusqueda(): void
    {
        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Camila',
            'apellidos' => 'Rodriguez',
            'fecha_nacimiento' => '2019-05-10',
            'sexo' => 'femenino',
            'estado' => 'activo'
        ]);

        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Carlos',
            'apellidos' => 'Perez',
            'fecha_nacimiento' => '2018-08-20',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ]);

        $pacientes = listarPacientes(self::$db, 'Cami');
        $this->assertCount(1, $pacientes);
        $this->assertEquals('Camila', $pacientes[0]['nombre']);
    }

    public function testObtenerPacienteConTutores(): void
    {
        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Isabella',
            'apellidos' => 'Navarro',
            'fecha_nacimiento' => '2021-03-15',
            'sexo' => 'femenino',
            'estado' => 'activo'
        ]);
        $idPaciente = (int) self::$db->lastInsertId();

        insertarDatos(self::$db, 'tutor', [
            'nombre' => 'Laura',
            'apellidos' => 'Navarro',
            'telefono' => '5554443333',
            'estado' => 'activo'
        ]);
        $idTutor = (int) self::$db->lastInsertId();

        insertarDatos(self::$db, 'paciente_tutor', [
            'id_paciente' => $idPaciente,
            'id_tutor' => $idTutor,
            'parentesco' => 'Madre'
        ]);

        $paciente = obtenerPacienteConTutores(self::$db, $idPaciente);
        $this->assertNotNull($paciente);
        $this->assertEquals('Isabella', $paciente['nombre']);
        $this->assertCount(1, $paciente['tutores']);
        $this->assertEquals('Madre', $paciente['tutores'][0]['parentesco']);
    }

    public function testObtenerPacienteInexistenteRetornaNull(): void
    {
        $paciente = obtenerPacienteConTutores(self::$db, 99999);
        $this->assertNull($paciente);
    }

    public function testActualizarPaciente(): void
    {
        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Original',
            'apellidos' => 'Apellido',
            'fecha_nacimiento' => '2020-01-01',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ]);
        $idPaciente = (int) self::$db->lastInsertId();

        $resultado = actualizarPaciente(self::$db, $idPaciente, [
            'nombre' => 'Actualizado',
            'sexo' => 'femenino'
        ]);

        $this->assertTrue($resultado);

        $paciente = obtenerDatos(self::$db, 'paciente', 'id_paciente = ?', [$idPaciente]);
        $this->assertEquals('Actualizado', $paciente[0]['nombre']);
        $this->assertEquals('femenino', $paciente[0]['sexo']);
    }

    public function testVincularTutorAPaciente(): void
    {
        insertarDatos(self::$db, 'paciente', [
            'nombre' => 'Nuevo',
            'apellidos' => 'Paciente',
            'fecha_nacimiento' => '2020-06-06',
            'sexo' => 'masculino',
            'estado' => 'activo'
        ]);
        $idPaciente = (int) self::$db->lastInsertId();

        insertarDatos(self::$db, 'tutor', [
            'nombre' => 'Tutor',
            'apellidos' => 'Nuevo',
            'telefono' => '5557778888',
            'estado' => 'activo'
        ]);
        $idTutor = (int) self::$db->lastInsertId();

        $resultado = vincularTutorAPaciente(self::$db, $idPaciente, $idTutor, 'Padre');
        $this->assertTrue($resultado);

        $pivote = obtenerDatos(self::$db, 'paciente_tutor', 'id_paciente = ?', [$idPaciente]);
        $this->assertCount(1, $pivote);
        $this->assertEquals('Padre', $pivote[0]['parentesco']);
    }
}
