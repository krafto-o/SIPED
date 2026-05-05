# Especificación de Módulo: Fase 4 (Operación Médica y Consultas)

## 1. Reglas de Base de Datos para este Módulo

El agente debe asumir que el esquema ya existe.

- Utilizar la tabla `consultas` para guardar la anamnesis y signos vitales.
- Utilizar la tabla `tratamientos` para el listado dinámico de medicamentos (relación 1 a N con la consulta).
- Utilizar la tabla `borradores_consultas` para el autoguardado temporal.
- Utilizar la tabla `archivos_adjuntos` para el registro de estudios de laboratorio.

## 2. Vista de Consulta Activa (`/Consultas/iniciar.php`)

- Acceso estricto mediante POST (enviando `id_cita`). Validar que el rol sea `'pediatra'`.
- **Estructura Visual (Vertical y Segmentada):**
  1. **Cabecera Estática:** Nombre completo del paciente y Edad calculada (Si es < 3 años, mostrar en años y meses).
  2. **Datos Clínicos (Inputs):** Peso, Talla, Perímetro Cefálico, Temperatura, F. Cardíaca, F. Respiratoria. Mostrar Tipo de sangre y Alergias.
  3. **Historial Previo (Acordeón):** Por defecto CERRADO. Al darle "+" muestra enfermedades previas y consultas pasadas.
  4. **Consulta Actual:** Input `motivo_consulta` y un `textarea` grande para la `anamnesis` (narrativa).
  5. **Estudios de Laboratorio:** `textarea` para notas manuales y una zona para subir archivos (PDFs/Imágenes).
  6. **Diagnóstico:** `textarea` para explicación y pasos a seguir.
  7. **Tratamiento (Lista Dinámica UI):** - Diseño: Botón "+ Agregar Medicamento".
     - JS inyecta un bloque HTML con 4 inputs: Medicamento, Presentación, Dosis/Frecuencia, Función.
     - Botón final: "Finalizar Consulta y Generar Receta".

## 3. Lógica de Autoguardado (Borradores)

- Implementar un script JS que recolecte los valores del formulario cada 30 segundos (o al detectar pausas en la escritura).
- Enviar datos vía POST (AJAX/Fetch) a `/Consultas/api_borrador.php`.
- El endpoint guarda los datos serializados (JSON) en `borradores_consultas`.
- Al entrar a iniciar una consulta, verificar si existe un borrador previo para ese `id_cita`. Si existe, preguntar al pediatra si desea restaurarlo.
- Al finalizar la consulta oficialmente, eliminar el registro del borrador.

## 4. Finalización, Recetas (PDF) y Seguridad

- **Transacción PDO Final:** Insertar en `consultas`, obtener el ID generado, iterar e insertar en `tratamientos`, eliminar borrador, y actualizar la `citas.estado = 'realizada'`.
- **Librería PDF:** Usar `DomPDF`.
- **Diseño del PDF:** Membrete simulado, datos del doctor, fecha, datos del niño, diagnóstico y el listado de `tratamientos`. Espacio para firma.
- **Gestión de Archivos de Recetas:** Guardar en `/storage/recetas_temporales/`. Las recetas con más de 7 días se eliminarán (crear función de limpieza). Si se pide una receta antigua, generarla al vuelo.
- **Seguridad Subida de Archivos:** Guardar los estudios de lab en `/storage/pacientes/{id}/` (fuera del acceso público web). Crear script `/Consultas/ver_archivo.php` que valide sesión antes de servir el archivo.
