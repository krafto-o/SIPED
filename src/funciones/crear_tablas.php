<?php

require_once 'Funciones_SQL.php';

function crear_tablas($db)
{
        // Definición de tablas
        $queries = [
            "CREATE TABLE medico (
                id_medico INTEGER PRIMARY KEY AUTO_INCREMENT,
                nombre VARCHAR(45),
                apellidos VARCHAR(45),
                telefono VARCHAR(15),
                correo VARCHAR(45)
            );",
            "CREATE TABLE tutor (
                id_tutor INTEGER PRIMARY KEY AUTO_INCREMENT,
                nombre VARCHAR(45),
                apellidos VARCHAR(45),
                telefono VARCHAR(15),
                correo VARCHAR(45),
                direccion VARCHAR(45),
                parentesco VARCHAR(45)
            );",
            "CREATE TABLE recepcionista (
                id_recepcionista INTEGER PRIMARY KEY AUTO_INCREMENT,
                nombre VARCHAR(45),
                apellidos VARCHAR(45),
                telefono VARCHAR(15),
                correo VARCHAR(100)
            );",
            "CREATE TABLE paciente (
                id_paciente INTEGER PRIMARY KEY AUTO_INCREMENT,
                nombre VARCHAR(45),
                apellidos VARCHAR(45),
                fecha_nacimiento DATE,
                sexo VARCHAR(45),
                id_tutor INTEGER,
                FOREIGN KEY (id_tutor) REFERENCES tutor(id_tutor)
            );",
            "CREATE TABLE citas (
                id_cita INTEGER PRIMARY KEY AUTO_INCREMENT,
                fecha_hora DATETIME,
                motivo VARCHAR(45),
                estado VARCHAR(45),
                id_paciente INTEGER,
                id_medico INTEGER,
                FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente),
                FOREIGN KEY (id_medico) REFERENCES medico(id_medico)
            );",
            "CREATE TABLE historial_clinico (
                id_historial INTEGER PRIMARY KEY AUTO_INCREMENT,
                fecha_hora DATETIME,
                observaciones VARCHAR(45),
                id_paciente INTEGER,
                FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente)
            );",
            "CREATE TABLE diagnostico (
                id_diagnostico INTEGER PRIMARY KEY AUTO_INCREMENT,
                descripcion VARCHAR(200),
                tratamiento VARCHAR(200),
                fecha_hora DATETIME,
                id_historial INTEGER,
                FOREIGN KEY (id_historial) REFERENCES historial_clinico(id_historial)
            );"
        ];

        foreach ($queries as $sql) {
            $db->exec($sql);
        }

        // 4. (Opcional) Insertar datos de prueba (Seeders)
        //echo "Insertando datos de prueba...<br>";
        //$db->exec("INSERT INTO categorias (nombre) VALUES ('Electrónica'), ('Hogar');");
        //$db->exec("INSERT INTO usuarios (nombre, email, password) VALUES ('Admin', 'admin@siped.com', '12345');"
        // 5. Reactivar revisión de llaves foráneas
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

        echo "<strong>¡Base de datos reconstruida con éxito!</strong>";

        //catch (PDOException $e) {
        //    $db->exec("SET FOREIGN_KEY_CHECKS = 1;"); // No olvidar reactivarlo si falla
        //    registrarError("Error reseteando DB: " . $e->getMessage());
        //    echo "Error fatal. Revisa el log.";
        //}
}


// Conectar a la db
$db = conectar();
// Crear las tablas
crear_tablas($db);
