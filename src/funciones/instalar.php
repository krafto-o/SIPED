<?php

require_once __DIR__ . '/Funciones_SQL.php';

function crear_tablas($db)
{
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

    foreach ($queries as $sql) {
        $db->exec($sql);
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
}

function seeders_base($db)
{
    $hash = password_hash('siped123', PASSWORD_DEFAULT);

    insertarDatos($db, 'usuarios', [
        'nombre' => 'Carlos',
        'apellidos' => 'Ramirez Lopez',
        'correo' => 'pediatra@siped.com',
        'telefono' => '5551234567',
        'password' => $hash,
        'rol' => 'pediatra',
        'estado' => 'activo'
    ]);

    insertarDatos($db, 'usuarios', [
        'nombre' => 'Maria',
        'apellidos' => 'Fernandez Torres',
        'correo' => 'recepcion@siped.com',
        'telefono' => '5559876543',
        'password' => $hash,
        'rol' => 'recepcionista',
        'estado' => 'activo'
    ]);

    $config = [
        ['clave' => 'consultorio_nombre', 'valor' => 'Consultorio Pediatrico SIPED', 'descripcion' => 'Nombre del consultorio'],
        ['clave' => 'costo_consulta', 'valor' => '500.00', 'descripcion' => 'Costo estandar de consulta general'],
        ['clave' => 'costo_primera_vez', 'valor' => '700.00', 'descripcion' => 'Costo de primera consulta'],
        ['clave' => 'horario_inicio', 'valor' => '09:00', 'descripcion' => 'Hora de apertura del consultorio'],
        ['clave' => 'horario_fin', 'valor' => '18:00', 'descripcion' => 'Hora de cierre del consultorio'],
        ['clave' => 'duracion_cita', 'valor' => '30', 'descripcion' => 'Duracion estandar de cita en minutos (fallback)'],
        ['clave' => 'duracion_primera_cita', 'valor' => '30', 'descripcion' => 'Duracion de cita de primera vez en minutos'],
        ['clave' => 'duracion_cita_regular', 'valor' => '20', 'descripcion' => 'Duracion de cita consecuente en minutos'],
        ['clave' => 'timeout_sesion', 'valor' => '10', 'descripcion' => 'Minutos de inactividad antes de cerrar sesion']
    ];

    foreach ($config as $item) {
        insertarDatos($db, 'configuracion', $item);
    }

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
}

function datos_prueba($db)
{
    $hoy = date('Y-m-d');
    $manana = date('Y-m-d', strtotime('+1 day'));
    $tresDias = date('Y-m-d', strtotime('+3 days'));
    $cincoDiasAtras = date('Y-m-d', strtotime('-5 days'));
    $idPediatra = 1;

    // Pacientes
    insertarDatos($db, 'paciente', [
        'nombre' => 'Sofia',
        'apellidos' => 'Martinez Lopez',
        'fecha_nacimiento' => '2023-01-15',
        'sexo' => 'femenino',
        'tipo_sangre' => 'O+',
        'alergias' => 'Penicilina',
        'estado' => 'activo'
    ]);
    $idSofia = (int) $db->lastInsertId();

    insertarDatos($db, 'paciente', [
        'nombre' => 'Mateo',
        'apellidos' => 'Garcia Ruiz',
        'fecha_nacimiento' => '2020-06-20',
        'sexo' => 'masculino',
        'tipo_sangre' => 'A+',
        'alergias' => null,
        'estado' => 'activo'
    ]);
    $idMateo = (int) $db->lastInsertId();

    insertarDatos($db, 'paciente', [
        'nombre' => 'Luna',
        'apellidos' => 'Hernandez Diaz',
        'fecha_nacimiento' => '2024-03-10',
        'sexo' => 'femenino',
        'tipo_sangre' => 'B+',
        'alergias' => 'Latex, Ibuprofeno',
        'estado' => 'activo'
    ]);
    $idLuna = (int) $db->lastInsertId();

    // Tutores
    insertarDatos($db, 'tutor', [
        'nombre' => 'Ana',
        'apellidos' => 'Martinez Lopez',
        'telefono' => '5551112222',
        'correo' => 'ana@test.com',
        'direccion' => 'Calle Reforma 123',
        'estado' => 'activo'
    ]);
    $idAna = (int) $db->lastInsertId();

    insertarDatos($db, 'tutor', [
        'nombre' => 'Carlos',
        'apellidos' => 'Garcia Ruiz',
        'telefono' => '5553334444',
        'correo' => 'carlos@test.com',
        'direccion' => 'Av. Juarez 456',
        'estado' => 'activo'
    ]);
    $idCarlos = (int) $db->lastInsertId();

    insertarDatos($db, 'tutor', [
        'nombre' => 'Rosa',
        'apellidos' => 'Hernandez Diaz',
        'telefono' => '5556667777',
        'correo' => 'rosa@test.com',
        'direccion' => 'Blvd. Central 789',
        'estado' => 'activo'
    ]);
    $idRosa = (int) $db->lastInsertId();

    insertarDatos($db, 'tutor', [
        'nombre' => 'Pedro',
        'apellidos' => 'Hernandez Diaz',
        'telefono' => '5558889999',
        'correo' => 'pedro@test.com',
        'direccion' => 'Blvd. Central 789',
        'estado' => 'activo'
    ]);
    $idPedro = (int) $db->lastInsertId();

    // Pivote paciente_tutor
    insertarDatos($db, 'paciente_tutor', ['id_paciente' => $idSofia, 'id_tutor' => $idAna, 'parentesco' => 'Madre']);
    insertarDatos($db, 'paciente_tutor', ['id_paciente' => $idMateo, 'id_tutor' => $idCarlos, 'parentesco' => 'Padre']);
    insertarDatos($db, 'paciente_tutor', ['id_paciente' => $idLuna, 'id_tutor' => $idRosa, 'parentesco' => 'Madre']);
    insertarDatos($db, 'paciente_tutor', ['id_paciente' => $idLuna, 'id_tutor' => $idPedro, 'parentesco' => 'Padre']);

    // Citas
    // Hoy 10:00 - Sofia primera_vez pendiente
    insertarDatos($db, 'citas', [
        'id_paciente' => $idSofia,
        'id_usuario' => $idPediatra,
        'fecha_hora' => $hoy . ' 10:00:00',
        'tipo_cita' => 'primera_vez',
        'motivo' => 'Fiebre persistente',
        'estado' => 'pendiente'
    ]);
    $idCitaHoy1 = (int) $db->lastInsertId();

    // Hoy 11:30 - Mateo consecuente pendiente
    insertarDatos($db, 'citas', [
        'id_paciente' => $idMateo,
        'id_usuario' => $idPediatra,
        'fecha_hora' => $hoy . ' 11:30:00',
        'tipo_cita' => 'consecuente',
        'motivo' => 'Control mensual',
        'estado' => 'pendiente'
    ]);
    $idCitaHoy2 = (int) $db->lastInsertId();

    // Hoy 16:00 - Luna primera_vez pendiente
    insertarDatos($db, 'citas', [
        'id_paciente' => $idLuna,
        'id_usuario' => $idPediatra,
        'fecha_hora' => $hoy . ' 16:00:00',
        'tipo_cita' => 'primera_vez',
        'motivo' => 'Vacunacion',
        'estado' => 'pendiente'
    ]);
    $idCitaHoy3 = (int) $db->lastInsertId();

    // Manana 09:00 - Sofia consecuente confirmada
    insertarDatos($db, 'citas', [
        'id_paciente' => $idSofia,
        'id_usuario' => $idPediatra,
        'fecha_hora' => $manana . ' 09:00:00',
        'tipo_cita' => 'consecuente',
        'motivo' => 'Revision',
        'estado' => 'confirmada'
    ]);

    // +3 dias 14:00 - Mateo primera_vez pendiente
    insertarDatos($db, 'citas', [
        'id_paciente' => $idMateo,
        'id_usuario' => $idPediatra,
        'fecha_hora' => $tresDias . ' 14:00:00',
        'tipo_cita' => 'primera_vez',
        'motivo' => 'Dolor abdominal',
        'estado' => 'pendiente'
    ]);

    // Consulta realizada hace 5 dias (para probar historial y recetas)
    insertarDatos($db, 'citas', [
        'id_paciente' => $idSofia,
        'id_usuario' => $idPediatra,
        'fecha_hora' => $cincoDiasAtras . ' 10:00:00',
        'tipo_cita' => 'primera_vez',
        'motivo' => 'Infeccion de garganta',
        'estado' => 'realizada'
    ]);
    $idCitaRealizada = (int) $db->lastInsertId();

    insertarDatos($db, 'consultas', [
        'id_cita' => $idCitaRealizada,
        'motivo_consulta' => 'Infeccion de garganta',
        'anamnesis' => 'Paciente con fiebre de 38.5C desde hace 2 dias. Dolor al tragar. Sin tos ni secrecion nasal.',
        'peso' => 12.50,
        'talla' => 85.00,
        'perimetro_cefalico' => 48.00,
        'temperatura' => 38.50,
        'frec_cardiaca' => 110,
        'frec_respiratoria' => 28,
        'notas_laboratorio' => null,
        'diagnostico' => 'Faringoamigdalitis aguda bacteriana. Se indica antibiotico y antipiretico.',
        'estado_pago' => 'pendiente'
    ]);
    $idConsulta = (int) $db->lastInsertId();

    insertarDatos($db, 'tratamientos', [
        'id_consulta' => $idConsulta,
        'medicamento' => 'Amoxicilina',
        'presentacion' => 'Suspension 250mg/5ml',
        'dosis_frecuencia' => '5ml cada 8 horas por 10 dias',
        'funcion' => 'Antibiotico'
    ]);

    insertarDatos($db, 'tratamientos', [
        'id_consulta' => $idConsulta,
        'medicamento' => 'Paracetamol',
        'presentacion' => 'Gotas 100mg/ml',
        'dosis_frecuencia' => '0.8ml cada 6 horas si hay fiebre',
        'funcion' => 'Antipiretico'
    ]);
}

function instalar_sistema($db)
{
    $esCLI = (php_sapi_name() === 'cli');

    $salida = function($msg) use ($esCLI) {
        if ($esCLI) {
            echo strip_tags($msg) . "\n";
        } else {
            echo $msg . "<br>";
        }
    };

    $ok = function($msg) use ($esCLI) {
        if ($esCLI) {
            echo "  OK: $msg\n";
        } else {
            echo "<p style='color:green;'>✓ $msg</p>";
        }
    };

    $fail = function($msg) use ($esCLI) {
        if ($esCLI) {
            echo "  ERROR: $msg\n";
        } else {
            echo "<p style='color:red;'>✗ $msg</p>";
        }
    };

    $salida("<h2>Instalacion del sistema SIPED</h2>");

    // PASO 1: Crear tablas
    $salida("<h3>Paso 1: Creando tablas...</h3>");
    try {
        crear_tablas($db);
        $ok('13 tablas creadas correctamente');
    } catch (\Exception $e) {
        $fail('Error creando tablas: ' . $e->getMessage());
        return;
    }

    // PASO 2: Seeders base
    $salida("<h3>Paso 2: Insertando datos base...</h3>");
    try {
        seeders_base($db);
        $ok('2 usuarios (pediatra, recepcionista)');
        $ok('9 variables de configuracion');
        $ok('14 vacunas en catalogo');
    } catch (\Exception $e) {
        $fail('Error en seeders: ' . $e->getMessage());
        return;
    }

    // PASO 3: Datos de prueba funcionales
    $salida("<h3>Paso 3: Insertando datos de prueba...</h3>");
    try {
        datos_prueba($db);
        $ok('3 pacientes (Sofia, Mateo, Luna)');
        $ok('4 tutores vinculados');
        $ok('5 citas (3 hoy, 1 manana, 1 en 3 dias)');
        $ok('1 consulta realizada con 2 tratamientos (historial clinico)');
    } catch (\Exception $e) {
        $fail('Error en datos de prueba: ' . $e->getMessage());
        return;
    }

    // RESUMEN
    $salida("<hr>");
    $salida("<h3 style='color:green;'>¡Instalacion completada exitosamente!</h3>");
    $salida("<h4>Credenciales de acceso:</h4>");

    if ($esCLI) {
        $salida("  Pediatra:       pediatra@siped.com / 5551234567 → siped123");
        $salida("  Recepcionista:  recepcion@siped.com / 5559876543 → siped123");
    } else {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;'>";
        echo "<tr><th>Rol</th><th>Correo</th><th>Telefono</th><th>Contraseña</th></tr>";
        echo "<tr><td>Pediatra</td><td>pediatra@siped.com</td><td>5551234567</td><td>siped123</td></tr>";
        echo "<tr><td>Recepcionista</td><td>recepcion@siped.com</td><td>5559876543</td><td>siped123</td></tr>";
        echo "</table>";
    }

    $salida("<p><strong>⚠ IMPORTANTE:</strong> Cambia las contraseñas despues del primer inicio de sesion.</p>");
}

// Crear BD si no existe
$dbSinBD = conectarSinBD();
$dbSinBD->exec("CREATE DATABASE IF NOT EXISTS siped CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");

// Ejecutar instalacion
$db = conectar();
instalar_sistema($db);
