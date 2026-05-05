<?php

require_once 'Funciones_SQL.php';

function instalar_sistema($db)
{
    echo "<h2>Instalación del sistema SIPED</h2>";

    // ===== PASO 1: Crear tablas =====
    echo "<h3>Paso 1: Creando tablas...</h3>";

    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    $tablas = ['usuarios', 'configuracion', 'tutor', 'paciente', 'paciente_tutor', 'citas', 'borradores_consultas', 'consultas', 'tratamientos', 'archivos_adjuntos', 'vacunas_catalogo', 'vacunas_aplicadas', 'pagos'];
    foreach ($tablas as $tabla) {
        $db->exec("DROP TABLE IF EXISTS $tabla;");
    }

    $queries = [
        "CREATE TABLE usuarios (
            id_usuario INTEGER PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(45) NOT NULL,
            apellidos VARCHAR(45) NOT NULL,
            correo VARCHAR(100) UNIQUE NOT NULL,
            telefono VARCHAR(15) UNIQUE,
            password VARCHAR(255) NOT NULL,
            rol ENUM('pediatra', 'recepcionista') NOT NULL,
            estado ENUM('activo', 'inactivo') DEFAULT 'activo'
        );",

        "CREATE TABLE configuracion (
            clave VARCHAR(50) PRIMARY KEY,
            valor VARCHAR(100) NOT NULL,
            descripcion VARCHAR(200)
        );",

        "CREATE TABLE tutor (
            id_tutor INTEGER PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(45) NOT NULL,
            apellidos VARCHAR(45) NOT NULL,
            telefono VARCHAR(15) NOT NULL,
            correo VARCHAR(100),
            direccion VARCHAR(150),
            estado ENUM('activo', 'inactivo') DEFAULT 'activo'
        );",

        "CREATE TABLE paciente (
            id_paciente INTEGER PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(45) NOT NULL,
            apellidos VARCHAR(45) NOT NULL,
            fecha_nacimiento DATE NOT NULL,
            sexo VARCHAR(15) NOT NULL,
            tipo_sangre VARCHAR(10),
            alergias TEXT,
            estado ENUM('activo', 'inactivo') DEFAULT 'activo'
        );",

        "CREATE TABLE paciente_tutor (
            id_paciente INTEGER NOT NULL,
            id_tutor INTEGER NOT NULL,
            parentesco VARCHAR(45) NOT NULL,
            PRIMARY KEY (id_paciente, id_tutor),
            FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente) ON DELETE CASCADE,
            FOREIGN KEY (id_tutor) REFERENCES tutor(id_tutor) ON DELETE CASCADE
        );",

        "CREATE TABLE citas (
            id_cita INTEGER PRIMARY KEY AUTO_INCREMENT,
            id_paciente INTEGER NOT NULL,
            id_usuario INTEGER NOT NULL,
            fecha_hora DATETIME NOT NULL,
            tipo_cita ENUM('primera_vez', 'consecuente') DEFAULT 'consecuente',
            motivo VARCHAR(100),
            estado ENUM('pendiente', 'confirmada', 'cancelada', 'realizada') DEFAULT 'pendiente',
            FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        );",

        "CREATE TABLE borradores_consultas (
            id_cita INTEGER PRIMARY KEY,
            id_usuario INTEGER NOT NULL,
            datos_json TEXT NOT NULL,
            FOREIGN KEY (id_cita) REFERENCES citas(id_cita) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        );",

        "CREATE TABLE consultas (
            id_consulta INTEGER PRIMARY KEY AUTO_INCREMENT,
            id_cita INTEGER NOT NULL,
            motivo_consulta VARCHAR(200),
            anamnesis TEXT,
            peso DECIMAL(5,2),
            talla DECIMAL(5,2),
            perimetro_cefalico DECIMAL(5,2),
            temperatura DECIMAL(4,2),
            frec_cardiaca INTEGER,
            frec_respiratoria INTEGER,
            notas_laboratorio TEXT,
            diagnostico TEXT NOT NULL,
            estado_pago ENUM('pendiente', 'pagado') DEFAULT 'pendiente',
            FOREIGN KEY (id_cita) REFERENCES citas(id_cita) ON DELETE CASCADE
        );",

        "CREATE TABLE tratamientos (
            id_tratamiento INTEGER PRIMARY KEY AUTO_INCREMENT,
            id_consulta INTEGER NOT NULL,
            medicamento VARCHAR(100) NOT NULL,
            presentacion VARCHAR(100) NOT NULL,
            dosis_frecuencia VARCHAR(200) NOT NULL,
            funcion VARCHAR(150),
            FOREIGN KEY (id_consulta) REFERENCES consultas(id_consulta) ON DELETE CASCADE
        );",

        "CREATE TABLE archivos_adjuntos (
            id_archivo INTEGER PRIMARY KEY AUTO_INCREMENT,
            id_consulta INTEGER NOT NULL,
            nombre_original VARCHAR(150) NOT NULL,
            ruta_segura VARCHAR(255) NOT NULL,
            fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_consulta) REFERENCES consultas(id_consulta) ON DELETE CASCADE
        );",

        "CREATE TABLE vacunas_catalogo (
            id_vacuna INTEGER PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL,
            esquema_edad VARCHAR(50)
        );",

        "CREATE TABLE vacunas_aplicadas (
            id_registro INTEGER PRIMARY KEY AUTO_INCREMENT,
            id_paciente INTEGER NOT NULL,
            id_vacuna INTEGER NOT NULL,
            fecha_aplicacion DATE NOT NULL,
            aplicada_externamente BOOLEAN DEFAULT FALSE,
            lote VARCHAR(50),
            FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente) ON DELETE CASCADE,
            FOREIGN KEY (id_vacuna) REFERENCES vacunas_catalogo(id_vacuna) ON DELETE CASCADE
        );",

        "CREATE TABLE pagos (
            id_pago INTEGER PRIMARY KEY AUTO_INCREMENT,
            id_consulta INTEGER NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            forma_pago ENUM('efectivo', 'tarjeta', 'transferencia') NOT NULL,
            fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_consulta) REFERENCES consultas(id_consulta) ON DELETE CASCADE
        );"
    ];

    try {
        foreach ($queries as $sql) {
            $db->exec($sql);
        }
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "<p style='color:green;'>✓ 13 tablas creadas correctamente.</p>";
    } catch (PDOException $e) {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "<p style='color:red;'>✗ Error creando tablas: " . htmlspecialchars($e->getMessage()) . "</p>";
        return;
    }

    // ===== PASO 2: Crear usuarios principales =====
    echo "<h3>Paso 2: Creando usuarios principales...</h3>";

    $hash = password_hash('siped123', PASSWORD_DEFAULT);

    $usuarios = [
        [
            'nombre' => 'Pediatra',
            'apellidos' => 'Principal',
            'correo' => 'pediatra@siped.com',
            'telefono' => '1234567890',
            'password' => $hash,
            'rol' => 'pediatra',
            'estado' => 'activo'
        ],
        [
            'nombre' => 'Recepcionista',
            'apellidos' => 'Principal',
            'correo' => 'recepcion@siped.com',
            'telefono' => '0987654321',
            'password' => $hash,
            'rol' => 'recepcionista',
            'estado' => 'activo'
        ]
    ];

    try {
        foreach ($usuarios as $usuario) {
            insertarDatos($db, 'usuarios', $usuario);
        }
        echo "<p style='color:green;'>✓ Usuarios creados correctamente.</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>✗ Error creando usuarios: " . htmlspecialchars($e->getMessage()) . "</p>";
        return;
    }

    // ===== PASO 3: Configuración básica =====
    echo "<h3>Paso 3: Configurando sistema...</h3>";

    $config = [
        ['clave' => 'consultorio_nombre', 'valor' => 'Consultorio Pediatrico SIPED', 'descripcion' => 'Nombre del consultorio'],
        ['clave' => 'costo_consulta', 'valor' => '500.00', 'descripcion' => 'Costo estandar de consulta general'],
        ['clave' => 'costo_primera_vez', 'valor' => '700.00', 'descripcion' => 'Costo de primera consulta'],
        ['clave' => 'horario_inicio', 'valor' => '09:00', 'descripcion' => 'Hora de apertura del consultorio'],
        ['clave' => 'horario_fin', 'valor' => '18:00', 'descripcion' => 'Hora de cierre del consultorio'],
        ['clave' => 'duracion_cita', 'valor' => '30', 'descripcion' => 'Duracion estandar de cita en minutos'],
        ['clave' => 'timeout_sesion', 'valor' => '10', 'descripcion' => 'Minutos de inactividad antes de cerrar sesion']
    ];

    foreach ($config as $item) {
        insertarDatos($db, 'configuracion', $item);
    }

    echo "<p style='color:green;'>✓ Configuración básica creada.</p>";

    // ===== PASO 4: Catálogo de vacunas =====
    echo "<h3>Paso 4: Cargando catálogo de vacunas...</h3>";

    $vacunas = [
        ['nombre' => 'BCG (Tuberculosis)', 'esquema_edad' => 'Recien nacido'],
        ['nombre' => 'Hepatitis B', 'esquema_edad' => 'Recien nacido, 2 meses, 6 meses'],
        ['nombre' => 'Pentavalente (DPT-Hib-HepB)', 'esquema_edad' => '2, 4, 6 meses'],
        ['nombre' => 'Polio (Sabin/Inactivada)', 'esquema_edad' => '2, 4, 6 meses, 18 meses, 6 anos'],
        ['nombre' => 'Rotavirus', 'esquema_edad' => '2, 4 meses'],
        ['nombre' => 'Neumococo conjugada 10-valente', 'esquema_edad' => '2, 4, 12 meses'],
        ['nombre' => 'Influenza', 'esquema_edad' => '6-23 meses (anual)'],
        ['nombre' => 'SRP (Sarampion-Rubeola-Parotiditis)', 'esquema_edad' => '12 meses, 6 anos'],
        ['nombre' => 'DPT (Difteria-Tosferina-Tetanos)', 'esquema_edad' => '18 meses, 6 anos, 11-14 anos'],
        ['nombre' => 'VPH (Virus del Papiloma Humano)', 'esquema_edad' => '11 anos (2 dosis)'],
        ['nombre' => 'Hepatitis A', 'esquema_edad' => '12 meses (refuerzo 24 meses)'],
        ['nombre' => 'Meningococo', 'esquema_edad' => '12 meses, refuerzo segun indicacion'],
        ['nombre' => 'Varicela', 'esquema_edad' => '12 meses, refuerzo a los 6 anos'],
        ['nombre' => 'Triple Viral (SRP) refuerzo', 'esquema_edad' => '6 anos']
    ];

    foreach ($vacunas as $vacuna) {
        insertarDatos($db, 'vacunas_catalogo', $vacuna);
    }

    echo "<p style='color:green;'>✓ Catálogo de vacunas cargado (" . count($vacunas) . " vacunas).</p>";

    // ===== RESUMEN FINAL =====
    echo "<hr>";
    echo "<h3 style='color:green;'>¡Instalación completada exitosamente!</h3>";
    echo "<h4>Credenciales de acceso:</h4>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;'>";
    echo "<tr><th>Rol</th><th>Correo</th><th>Teléfono</th><th>Contraseña</th></tr>";
    echo "<tr><td>Pediatra</td><td>pediatra@siped.com</td><td>1234567890</td><td>siped123</td></tr>";
    echo "<tr><td>Recepcionista</td><td>recepcion@siped.com</td><td>0987654321</td><td>siped123</td></tr>";
    echo "</table>";
    echo "<p><strong>⚠ IMPORTANTE:</strong> Cambia las contraseñas después del primer inicio de sesión.</p>";
}

// Ejecutar instalación
$db = conectar();
instalar_sistema($db);
