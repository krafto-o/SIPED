<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    private static $db = null;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../funciones/Funciones_SQL.php';
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

    public function testLoginExitosoConCorreo(): void
    {
        $hash = password_hash('siped123', PASSWORD_DEFAULT);

        insertarDatos(self::$db, 'usuarios', [
            'nombre' => 'Test',
            'apellidos' => 'Usuario',
            'correo' => 'test_correo@siped.com',
            'telefono' => '5550001111',
            'password' => $hash,
            'rol' => 'pediatra',
            'estado' => 'activo'
        ]);

        $usuarios = obtenerDatos(
            self::$db,
            'usuarios',
            '(correo = ? OR telefono = ?) AND estado = ?',
            ['test_correo@siped.com', 'test_correo@siped.com', 'activo']
        );

        $this->assertCount(1, $usuarios);
        $this->assertEquals('test_correo@siped.com', $usuarios[0]['correo']);
        $this->assertTrue(password_verify('siped123', $usuarios[0]['password']));
    }

    public function testLoginExitosoConTelefono(): void
    {
        $hash = password_hash('siped123', PASSWORD_DEFAULT);

        insertarDatos(self::$db, 'usuarios', [
            'nombre' => 'Test',
            'apellidos' => 'Telefono',
            'correo' => 'test_tel@siped.com',
            'telefono' => '5550002222',
            'password' => $hash,
            'rol' => 'recepcionista',
            'estado' => 'activo'
        ]);

        $usuarios = obtenerDatos(
            self::$db,
            'usuarios',
            '(correo = ? OR telefono = ?) AND estado = ?',
            ['5550002222', '5550002222', 'activo']
        );

        $this->assertCount(1, $usuarios);
        $this->assertEquals('5550002222', $usuarios[0]['telefono']);
    }

    public function testLoginFallidoPasswordIncorrecta(): void
    {
        $hash = password_hash('siped123', PASSWORD_DEFAULT);

        insertarDatos(self::$db, 'usuarios', [
            'nombre' => 'Test',
            'apellidos' => 'Password',
            'correo' => 'test_pass@siped.com',
            'telefono' => '5550003333',
            'password' => $hash,
            'rol' => 'pediatra',
            'estado' => 'activo'
        ]);

        $usuarios = obtenerDatos(
            self::$db,
            'usuarios',
            '(correo = ? OR telefono = ?) AND estado = ?',
            ['test_pass@siped.com', 'test_pass@siped.com', 'activo']
        );

        $this->assertCount(1, $usuarios);
        $this->assertFalse(password_verify('password_incorrecta', $usuarios[0]['password']));
    }

    public function testLoginFallidoCuentaInactiva(): void
    {
        $hash = password_hash('siped123', PASSWORD_DEFAULT);

        insertarDatos(self::$db, 'usuarios', [
            'nombre' => 'Test',
            'apellidos' => 'Inactivo',
            'correo' => 'test_inactivo@siped.com',
            'telefono' => '5550004444',
            'password' => $hash,
            'rol' => 'pediatra',
            'estado' => 'inactivo'
        ]);

        $usuarios = obtenerDatos(
            self::$db,
            'usuarios',
            '(correo = ? OR telefono = ?) AND estado = ?',
            ['test_inactivo@siped.com', 'test_inactivo@siped.com', 'activo']
        );

        $this->assertEmpty($usuarios);
    }

    public function testLoginFallidoUsuarioNoExiste(): void
    {
        $usuarios = obtenerDatos(
            self::$db,
            'usuarios',
            '(correo = ? OR telefono = ?) AND estado = ?',
            ['no_existe@siped.com', 'no_existe@siped.com', 'activo']
        );

        $this->assertEmpty($usuarios);
    }

    public function testPasswordHashVerificacion(): void
    {
        $password = 'siped123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('otra_password', $hash));
    }

    public function testInicioSesionSeteaVariablesSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        require_once __DIR__ . '/../../funciones/auth.php';

        $usuario = [
            'id_usuario' => 999,
            'nombre' => 'Test',
            'apellidos' => 'Sesion',
            'rol' => 'pediatra'
        ];

        iniciarSesion($usuario);

        $this->assertEquals(999, $_SESSION['id_usuario']);
        $this->assertEquals('pediatra', $_SESSION['rol']);
        $this->assertEquals('Test', $_SESSION['nombre']);
        $this->assertIsInt($_SESSION['ultimo_acceso']);

        session_destroy();
    }

    public function testVerificarSesionRetornaFalseSinSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        require_once __DIR__ . '/../../funciones/auth.php';

        $resultado = verificarSesion();

        $this->assertFalse($resultado);

        session_destroy();
    }

    public function testVerificarSesionRetornaTrueConSessionActiva(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [
            'id_usuario' => 1,
            'rol' => 'pediatra',
            'ultimo_acceso' => time()
        ];

        require_once __DIR__ . '/../../funciones/auth.php';

        $resultado = verificarSesion();

        $this->assertTrue($resultado);

        session_destroy();
    }

    public function testCerrarSesionLimpiaVariables(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [
            'id_usuario' => 1,
            'rol' => 'pediatra',
            'ultimo_acceso' => time()
        ];

        require_once __DIR__ . '/../../funciones/auth.php';

        cerrarSesion();

        $this->assertEmpty($_SESSION);
    }

    public function testUsuarioAutenticadoRetornaDatos(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [
            'id_usuario' => 42,
            'rol' => 'recepcionista',
            'nombre' => 'Maria',
            'apellidos' => 'Fernandez',
            'ultimo_acceso' => time()
        ];

        require_once __DIR__ . '/../../funciones/auth.php';

        $datos = usuarioAutenticado();

        $this->assertEquals(42, $datos['id_usuario']);
        $this->assertEquals('recepcionista', $datos['rol']);
        $this->assertEquals('Maria', $datos['nombre']);
        $this->assertEquals('Fernandez', $datos['apellidos']);

        session_destroy();
    }
}
