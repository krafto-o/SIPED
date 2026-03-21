<?php
$dbpath = __DIR__ . '/../hospital_pediatrico.db'; 
try {
    $db = new PDO("sqlite:$dbpath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

   
    $db->exec("
        CREATE TABLE IF NOT EXISTS Medicamento (
            Id_Medicamento INTEGER PRIMARY KEY,
            Nombre TEXT,
            Descripcion VARCHAR(200),
            Cantidad INTEGER
        );

        CREATE TABLE IF NOT EXISTS Doctor (
            Id_Doctor INTEGER PRIMARY KEY,
            Nombre VARCHAR(45),
            Apellidos VARCHAR(45),
            Telefono VARCHAR(15),
            Correo VARCHAR(45)
        );

        CREATE TABLE IF NOT EXISTS Tutor (
            Id_Tutor INTEGER PRIMARY KEY,
            Nombre VARCHAR(45),
            Apellidos VARCHAR(45),
            Telefono VARCHAR(15),
            Direccion VARCHAR(45),
            Parentesco VARCHAR(45)
        );

        CREATE TABLE IF NOT EXISTS Recepcionista (
             Id_Recepcionista INT PRIMARY KEY,
             Nombre VARCHAR(45),
             Apellidos VARCHAR(45),
             Telefono_clinica VARCHAR(15),
             Correo VARCHAR(100),
             Turno VARCHAR(45),        
        );

        CREATE TABLE IF NOT EXISTS Paciente (
            Id_Paciente INTEGER PRIMARY KEY,
            Nombre VARCHAR(45),
            Apellidos VARCHAR(45),
            Fecha_Nacimiento DATE,
            Sexo VARCHAR(45),
            Tipo_Sangre VARCHAR(45),
            Id_Tutor INTEGER,
            Id_Enfermero INTEGER,
            FOREIGN KEY (Id_Tutor) REFERENCES Tutor(Id_Tutor),
            FOREIGN KEY (Id_Enfermero) REFERENCES Enfermero(Id_Enfermero)
        );

        CREATE TABLE IF NOT EXISTS Citas (
            Id_Citas INTEGER PRIMARY KEY,
            Fecha DATE,
            Hora TIME,
            Motivo VARCHAR(45),
            Estado VARCHAR(45),
            Id_Paciente INTEGER,
            Id_Doctor INTEGER,
            FOREIGN KEY (Id_Paciente) REFERENCES Paciente(Id_Paciente),
            FOREIGN KEY (Id_Doctor) REFERENCES Doctor(Id_Doctor)
        );

        CREATE TABLE IF NOT EXISTS Historial_Clinico (
            Id_Historial INTEGER PRIMARY KEY,
            Fecha DATE,
            Observaciones VARCHAR(45),
            Id_Paciente INTEGER,
            FOREIGN KEY (Id_Paciente) REFERENCES Paciente(Id_Paciente)
        );

        CREATE TABLE IF NOT EXISTS Diagnostico (
            Id_Diagnostico INTEGER PRIMARY KEY,
            Descripcion VARCHAR(200),
            Tratamiento VARCHAR(200),
            Fecha DATE,
            Id_Historial INTEGER,
            FOREIGN KEY (Id_Historial) REFERENCES Historial_Clinico(Id_Historial)
        );

        CREATE TABLE IF NOT EXISTS Diagnostico_Medicamento (
            Id_Diagnostico INTEGER,
            Id_Medicamento INTEGER,
            FOREIGN KEY (Id_Diagnostico) REFERENCES Diagnostico(Id_Diagnostico),
            FOREIGN KEY (Id_Medicamento) REFERENCES Medicamento(Id_Medicamento)
        );
    ");

    echo "Todas las tablas fueron creadas correctamente";

} catch (PDOException $e) {
    die("Error en BD: " . $e->getMessage());
}
