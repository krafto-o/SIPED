<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FuncionesSQLTest extends TestCase
{
    private static $db = null;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../funciones/Funciones_SQL.php';
        self::$db = conectar();
    }

    public function setUp(): void
    {
        self::$db->exec("DROP TABLE IF EXISTS prueba_test");
        self::$db->exec("
            CREATE TABLE prueba_test (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                nombre VARCHAR(50) NOT NULL,
                email VARCHAR(100),
                edad INTEGER,
                estado ENUM('activo', 'inactivo') DEFAULT 'activo'
            )
        ");
    }

    public function tearDown(): void
    {
        self::$db->exec("DROP TABLE IF EXISTS prueba_test");
    }

    public function testConectarRetornaPDO(): void
    {
        $this->assertInstanceOf(PDO::class, conectar());
    }

    public function testInsertarDatosExitoso(): void
    {
        $resultado = insertarDatos(self::$db, 'prueba_test', [
            'nombre' => 'Juan Perez',
            'email' => 'juan@test.com',
            'edad' => 25
        ]);

        $this->assertTrue($resultado);

        $datos = obtenerDatos(self::$db, 'prueba_test', 'nombre = ?', ['Juan Perez']);
        $this->assertCount(1, $datos);
        $this->assertEquals('Juan Perez', $datos[0]['nombre']);
        $this->assertEquals('juan@test.com', $datos[0]['email']);
    }

    public function testInsertarDatosFallaConTablaInvalida(): void
    {
        $resultado = insertarDatos(self::$db, 'tabla inexistente', ['nombre' => 'test']);
        $this->assertFalse($resultado);
    }

    public function testObtenerDatosSinCondicion(): void
    {
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Ana', 'email' => 'ana@test.com', 'edad' => 30]);
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Luis', 'email' => 'luis@test.com', 'edad' => 22]);

        $datos = obtenerDatos(self::$db, 'prueba_test');

        $this->assertCount(2, $datos);
    }

    public function testObtenerDatosConCondicion(): void
    {
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Maria', 'email' => 'maria@test.com', 'edad' => 28, 'estado' => 'activo']);
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Pedro', 'email' => 'pedro@test.com', 'edad' => 35, 'estado' => 'inactivo']);

        $datos = obtenerDatos(self::$db, 'prueba_test', "estado = ?", ['activo']);

        $this->assertCount(1, $datos);
        $this->assertEquals('Maria', $datos[0]['nombre']);
    }

    public function testObtenerDatosTablaInexistente(): void
    {
        $datos = obtenerDatos(self::$db, 'tabla_que_no_existe');
        $this->assertIsArray($datos);
        $this->assertEmpty($datos);
    }

    public function testObtenerDatosNombreTablaInvalido(): void
    {
        $this->expectException(Exception::class);
        obtenerDatos(self::$db, 'usuarios; DROP TABLE usuarios');
    }

    public function testObtenerDatoEspecifico(): void
    {
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Carlos', 'email' => 'carlos@test.com', 'edad' => 40]);

        $datos = obtenerDatoEspecifico(self::$db, 'nombre', 'prueba_test', 'edad = ?', [40]);

        $this->assertCount(1, $datos);
        $this->assertEquals('Carlos', $datos[0]['nombre']);
    }

    public function testObtenerDatoEspecificoMultiplesColumnas(): void
    {
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Carlos', 'email' => 'carlos@test.com', 'edad' => 40]);

        $datos = obtenerDatos(self::$db, 'prueba_test', 'edad = ?', [40]);

        $this->assertCount(1, $datos);
        $this->assertEquals('Carlos', $datos[0]['nombre']);
        $this->assertEquals('carlos@test.com', $datos[0]['email']);
    }

    public function testObtenerDatoEspecificoColumnaInvalida(): void
    {
        $this->expectException(Exception::class);
        obtenerDatoEspecifico(self::$db, 'nombre; DROP TABLE', 'prueba_test');
    }

    public function testActualizarDatosExitoso(): void
    {
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Rosa', 'email' => 'rosa@test.com', 'edad' => 27]);

        $resultado = actualizarDatos(
            self::$db,
            'prueba_test',
            ['nombre' => 'Rosa Maria', 'edad' => 28],
            'email = :email',
            ['email' => 'rosa@test.com']
        );

        $this->assertTrue($resultado);

        $datos = obtenerDatos(self::$db, 'prueba_test', 'email = ?', ['rosa@test.com']);
        $this->assertEquals('Rosa Maria', $datos[0]['nombre']);
        $this->assertEquals(28, $datos[0]['edad']);
    }

    public function testActualizarDatosConCondicionNominal(): void
    {
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Diego', 'email' => 'diego@test.com', 'edad' => 33]);

        $resultado = actualizarDatos(
            self::$db,
            'prueba_test',
            ['nombre' => 'Diego Lopez'],
            'email = :email',
            ['email' => 'diego@test.com']
        );

        $this->assertTrue($resultado);

        $datos = obtenerDatos(self::$db, 'prueba_test', 'email = ?', ['diego@test.com']);
        $this->assertEquals('Diego Lopez', $datos[0]['nombre']);
    }

    public function testEjecutarConsulta(): void
    {
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Sofia', 'email' => 'sofia@test.com', 'edad' => 29]);
        insertarDatos(self::$db, 'prueba_test', ['nombre' => 'Mateo', 'email' => 'mateo@test.com', 'edad' => 31]);

        $sql = "SELECT * FROM prueba_test WHERE edad > :edad ORDER BY nombre";
        $datos = ejecutarConsulta(self::$db, $sql, ['edad' => 29]);

        $this->assertCount(1, $datos);
        $this->assertEquals('Mateo', $datos[0]['nombre']);
    }

    public function testEjecutarConsultaSQLInvalido(): void
    {
        $datos = ejecutarConsulta(self::$db, 'SELECT * FROM tabla_inexistente');
        $this->assertIsArray($datos);
        $this->assertEmpty($datos);
    }

    public function testObtenerDatosRetornaArrayVacioEnError(): void
    {
        $resultado = obtenerDatos(self::$db, 'tabla_inexistente');
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function testInsertarDatosRetornaFalseEnError(): void
    {
        $resultado = insertarDatos(self::$db, 'tabla_inexistente', ['nombre' => 'test']);
        $this->assertFalse($resultado);
    }

    public function testActualizarDatosRetornaFalseEnError(): void
    {
        $resultado = actualizarDatos(self::$db, 'tabla_inexistente', ['nombre' => 'test'], 'id = 1');
        $this->assertFalse($resultado);
    }

    public function testRegistrarErrorCreaArchivoLog(): void
    {
        $archivoLog = __DIR__ . '/../../logs/errores_db.log';

        registrarError('Error de prueba para test');

        $this->assertFileExists($archivoLog);

        $contenido = file_get_contents($archivoLog);
        $this->assertStringContainsString('Error de prueba para test', $contenido);
    }
}
