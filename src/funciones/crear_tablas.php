<?php

require_once 'Funciones_SQL.php';

function crear_tablas($db) {
        // Definición de tablas
        $queries = [
            "CREATE TABLE Medicamento (
                  Id_Medicamento INTEGER PRIMARY KEY,
                  Nombre TEXT,
                  Descripcion VARCHAR(200),
                  Cantidad INTEGER
            );",
            "CREATE TABLE Doctor (
                Id_Doctor INTEGER PRIMARY KEY,
                Nombre VARCHAR(45),
                Apellidos VARCHAR(45),
                Telefono VARCHAR(15),
                Correo VARCHAR(45)
            );",
            "CREATE TABLE Tutor (
                Id_Tutor INTEGER PRIMARY KEY,
                Nombre VARCHAR(45),
                Apellidos VARCHAR(45),
                Telefono VARCHAR(15),
                Direccion VARCHAR(45),
                Parentesco VARCHAR(45)
            );",
            "CREATE TABLE Recepcionista (
                Id_Recepcionista INT PRIMARY KEY,
                 Nombre VARCHAR(45),
                 Apellidos VARCHAR(45),
                 Telefono_clinica VARCHAR(15),
                 Correo VARCHAR(100),
                 Turno VARCHAR(45),        
            );",
            "CREATE TABLE Paciente (
                Id_Paciente INTEGER PRIMARY KEY,
                Nombre VARCHAR(45),
                Apellidos VARCHAR(45),
                Fecha_Nacimiento DATE,
                Sexo VARCHAR(45),
                Tipo_Sangre VARCHAR(45),
                Id_Tutor INTEGER,
                Id_Enfermero INTEGER,
                FOREIGN KEY (Id_Tutor) REFERENCES Tutor(Id_Tutor),
            );",
            "CREATE TABLE Citas (
                Id_Citas INTEGER PRIMARY KEY,
                Fecha DATE,
                Hora TIME,
                Motivo VARCHAR(45),
                Estado VARCHAR(45),
                Id_Paciente INTEGER,
                Id_Doctor INTEGER,
                FOREIGN KEY (Id_Paciente) REFERENCES Paciente(Id_Paciente),
                FOREIGN KEY (Id_Doctor) REFERENCES Doctor(Id_Doctor)
            );",
            "CREATE TABLE Historial_Clinico (
                Id_Historial INTEGER PRIMARY KEY,
                Fecha DATE,
                Observaciones VARCHAR(45),
                Id_Paciente INTEGER,
                FOREIGN KEY (Id_Paciente) REFERENCES Paciente(Id_Paciente)
            );",
            "CREATE TABLE Diagnostico (
              Id_Diagnostico INTEGER PRIMARY KEY,
                Descripcion VARCHAR(200),
                Tratamiento VARCHAR(200),
                Fecha DATE,
                Id_Historial INTEGER,
                FOREIGN KEY (Id_Historial) REFERENCES Historial_Clinico(Id_Historial)
            );",
            "CREATE TABLE Diagnostico_Medicamento (
              Id_Diagnostico INTEGER,
                Id_Medicamento INTEGER,
                FOREIGN KEY (Id_Diagnostico) REFERENCES Diagnostico(Id_Diagnostico),
                FOREIGN KEY (Id_Medicamento) REFERENCES Medicamento(Id_Medicamento)
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

    } catch (PDOException $e) {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;"); // No olvidar reactivarlo si falla
        registrarError("Error reseteando DB: " . $e->getMessage());
        echo "Error fatal. Revisa el log.";
    }
}


// Conectar a la db
//$db = conectar();
// Crear las tablas
// crear_tablas($db)
