<?php

require_once 'Funciones_SQL.php';

function crear_tablas($db)
{
    // Desactivar llaves foráneas para poder recrear tablas
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Opcional pero recomendado: Limpiar las tablas existentes para evitar errores al recrearlas
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
        echo "<strong style='color:green;'>¡Base de datos construida con la nueva arquitectura SIPED!</strong>";
    } catch (PDOException $e) {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        // Asegúrate de que registrarError() esté disponible o cambiar por error_log()
        error_log("Error creando DB: " . $e->getMessage());
        echo "<strong style='color:red;'>Error fatal. Revisa el archivo de logs. " . $e->getMessage() . "</strong>";
    }
}

// Conectar a la db
$db = conectar();
// Crear las tablas
crear_tablas($db);
