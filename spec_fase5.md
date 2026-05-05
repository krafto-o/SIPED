# Especificación de Módulo: Fase 5 (Especialización Pediátrica - Vacunas)

## 1. Reglas de Base de Datos para este Módulo

El agente debe asumir que el esquema ya existe.

- Interactuar con la tabla `vacunas_catalogo` para obtener los tipos de vacunas y sus edades de aplicación recomendadas.
- Interactuar con la tabla `vacunas_aplicadas` para registrar las dosis. Esta tabla ya contiene el campo `aplicada_externamente` (BOOLEAN) para diferenciar las vacunas puestas en la clínica de las externas.

## 2. Alertas Proactivas (Lógica Backend)

- En el controlador que carga el Perfil del Paciente (`/Pacientes/perfil.php`), crear una función que evalúe el esquema de vacunación.
- **Algoritmo:**
  1. Calcular la edad actual del paciente en meses.
  2. Consultar `vacunas_catalogo` para obtener las vacunas recomendadas hasta esa edad.
  3. Comparar con los registros del paciente en `vacunas_aplicadas`.
  4. Si hay vacunas pendientes cuyo "esquema de edad" ya se cumplió, almacenar en un arreglo `$vacunasAtrasadas`.
- **UI de Alerta:** En el "Banner Superior" del Perfil del Paciente (definido en Fase 2), si el arreglo `$vacunasAtrasadas` no está vacío, inyectar un banner de advertencia (amarillo/naranja) que diga: *"Atención: El paciente tiene [X] vacunas atrasadas (ej. Sarampión, Rotavirus)."*

## 3. Vista de Cartilla Digital (Pestaña en Perfil)

- **Diseño de Lista:** Dentro de la pestaña "Vacunas" del perfil del paciente, mostrar un listado cronológico de las vacunas aplicadas.
- **Columnas/Datos mostrados:** Nombre de la Vacuna, Fecha de Aplicación, Lote, y Origen ("Interna" o "Externa").
- Si `aplicada_externamente` es TRUE (1), en la columna de Lote imprimir "N/A" o "Dato Externo".
- **Botón de Acción:** Un botón principal "Registrar Nueva Vacuna" que funcione bajo la **REGLA GLOBAL CERO GET**, enviando el `id_paciente` por POST hacia un modal o hacia la vista `/Vacunas/aplicar.php`.

## 4. Flujo de Registro de Vacuna (`/Vacunas/aplicar.php`)

- **Independencia:** Este módulo no requiere un `id_consulta` ni un `id_cita`. Solo requiere el `id_paciente`.
- **Formulario:**
  - `Select`: Tipo de vacuna (cargado dinámicamente desde `vacunas_catalogo`).
  - `Input Date`: Fecha de aplicación (por defecto hoy, pero editable por si se registra historial pasado).
  - `Checkbox`: "¿Aplicada en otra institución/médico?" (`aplicada_externamente`).
  - `Input Text`: Número de Lote.
- **Interacción JavaScript (Front-end):**
  - Si el checkbox `aplicada_externamente` es marcado, el input de "Número de Lote" debe deshabilitarse o quitarle el atributo `required`.
- **Backend:** Al procesar el POST, usar `insertarDatos()` y retornar al perfil del paciente.
