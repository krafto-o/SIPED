<?php
require_once '../funciones/Funciones_SQL.php';

$mensaje = "";

// 1. Se verifica si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = conectar();

    // 2. RECIBIR Y LIMPIAR DATOS DEL TUTOR
    // Se limpian las variables que vienen del formulario
    $nombre_tutor = htmlspecialchars($_POST['nombre_tutor'] ?? '');
    $apellidos_tutor = htmlspecialchars($_POST['apellidos_tutor'] ?? '');
    $telefono_tutor = htmlspecialchars($_POST['telefono_tutor'] ?? '');
    $direccion_tutor = htmlspecialchars($_POST['direccion_tutor'] ?? '');
    $parentesco = htmlspecialchars($_POST['parentesco'] ?? '');

    // 3. RECIBIR Y LIMPIAR DATOS DEL PACIENTE
    $nombre_pac = htmlspecialchars($_POST['nombre_paciente'] ?? '');
    $apellidos_pac = htmlspecialchars($_POST['apellidos_paciente'] ?? '');
    $fecha_nac = htmlspecialchars($_POST['fecha_nacimiento'] ?? '');
    $sexo = htmlspecialchars($_POST['sexo'] ?? '');
    $tipo_sangre = htmlspecialchars($_POST['tipo_sangre'] ?? '');
    
    // 4. PREPARAR ARRAY DEL TUTOR (Las llaves deben coincidir con las columnas en SQL)
    $datosTutor = [
        'Nombre' => $nombre_tutor,
        'Apellidos' => $apellidos_tutor,
        'Telefono' => $telefono_tutor,
        'Direccion' => $direccion_tutor,
        'Parentesco' => $parentesco
    ];
    // 6. PREPARAR ARRAY DEL PACIENTE
    $datosPaciente = [
        'Nombre' => $nombre_pac,
        'Apellidos' => $apellidos_pac,
        'Fecha_Nacimiento' => $fecha_nac,
        'Sexo' => $sexo,
        'Tipo_Sangre' => $tipo_sangre,
        'Id_Tutor' => $id_tutor_generado, // Vinculamos al paciente con su tutor
    ];
 

    // 1. INICIA LA TRANSACCIÓN
    $db->beginTransaction();

    try {
        // 2. Insertamos al Tutor
        $insertoTutor = insertarDatos($db, 'Tutor', $datosTutor);

        if (!$insertoTutor) {
            throw new Exception("Falló la inserción del Tutor.");
        }

        // Obtenemos el ID
        $id_tutor_generado = $db->lastInsertId();

        // 3. Preparamos y agregamos al Paciente
        $datosPaciente['Id_Tutor'] = $id_tutor_generado; // Le pasamos el ID

        $insertoPaciente = insertarDatos($db, 'Paciente', $datosPaciente);

        if (!$insertoPaciente) {
            throw new Exception("Falló la inserción del Paciente.");
        }

        // 4. Si no hubo ningun error se guarda la Transacción
        $db->commit();
        $mensaje = "<p style='color: green;'>¡Paciente y Tutor guardados con éxito!</p>";

    } catch (Exception $e) {
        // 5. Si algo fallo (Tutor o Paciente), Se cancela la Transacción
        $db->rollBack();
        
        // Registramos en el log el error
        registrarError("Error en Transacción Registro: " . $e->getMessage());
        
        $mensaje = "<p style='color: red;'>Ocurrió un error al guardar. Ningún dato fue registrado.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Paciente</title>
  <link rel="stylesheet" href="estilosP.css">
  <style>
    fieldset { margin-bottom: 20px; border: 1px solid #ccc; padding: 15px; }
    legend { font-weight: bold; color: #333; }
  </style>
</head>
<body>

  <div class="form-container">
    <h2>Registro de Paciente</h2>
    
    <?= $mensaje ?>

    <form method="POST" action="">
      
      <fieldset>
          <legend>Datos del Paciente</legend>
          
          <label for="nombre_paciente">Nombre:</label>
          <input type="text" id="nombre_paciente" name="nombre_paciente" required>

          <label for="apellidos_paciente">Apellidos:</label>
          <input type="text" id="apellidos_paciente" name="apellidos_paciente" required>

          <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
          <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>

          <label for="sexo">Sexo:</label>
          <select id="sexo" name="sexo" required>
            <option value="Masculino">Masculino</option>
            <option value="Femenino">Femenino</option>
          </select>

          <label for="tipo_sangre">Tipo de sangre:</label>
          <input type="text" id="tipo_sangre" name="tipo_sangre" required>
      </fieldset>

      <fieldset>
          <legend>Datos del Tutor Responsable</legend>
          
          <label for="nombre_tutor">Nombre del Tutor:</label>
          <input type="text" id="nombre_tutor" name="nombre_tutor" required>

          <label for="apellidos_tutor">Apellidos del Tutor:</label>
          <input type="text" id="apellidos_tutor" name="apellidos_tutor" required>

          <label for="telefono_tutor">Teléfono:</label>
          <input type="tel" id="telefono_tutor" name="telefono_tutor" required>

          <label for="direccion_tutor">Dirección:</label>
          <input type="text" id="direccion_tutor" name="direccion_tutor" required>

          <label for="parentesco">Parentesco (Ej. Madre, Padre, Abuelo):</label>
          <input type="text" id="parentesco" name="parentesco" required>
      </fieldset>

      <button type="submit">Guardar Registro Completo</button>
    </form>
  </div>

</body>
</html>
