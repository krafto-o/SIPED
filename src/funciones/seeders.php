<?php

require_once 'Funciones_SQL.php';

function crear_seeders($db)
{
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Usuarios de prueba (password: siped123)
    $hash = password_hash('siped123', PASSWORD_DEFAULT);

    $usuarios = [
        [
            'nombre' => 'Carlos',
            'apellidos' => 'Ramirez Lopez',
            'correo' => 'pediatra@siped.com',
            'telefono' => '5551234567',
            'password' => $hash,
            'rol' => 'pediatra',
            'estado' => 'activo'
        ],
        [
            'nombre' => 'Maria',
            'apellidos' => 'Fernandez Torres',
            'correo' => 'recepcion@siped.com',
            'telefono' => '5559876543',
            'password' => $hash,
            'rol' => 'recepcionista',
            'estado' => 'activo'
        ]
    ];

    foreach ($usuarios as $usuario) {
        insertarDatos($db, 'usuarios', $usuario);
    }

    // Configuracion
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

    // Vacunas del catalogo (calendario basico de vacunacion en Mexico)
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

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "<strong style='color:green;'>Seeders creados exitosamente.</strong><br>";
    echo "Usuarios de prueba:<br>";
    echo "- Pediatra: pediatra@siped.com / siped123<br>";
    echo "- Recepcionista: recepcion@siped.com / siped123<br>";
}

$db = conectar();
crear_seeders($db);
