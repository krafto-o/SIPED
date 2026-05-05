# Especificación de Módulo: Fase 2 (Pacientes y Tutores)

## 1. Reglas de Base de Datos para este Módulo

El agente debe asumir que el esquema ya existe. Las operaciones de este módulo interactúan con las tablas `paciente`, `tutor` y la tabla pivote `paciente_tutor`.

- **Bajas Lógicas:** NUNCA eliminar registros. Para borrar un paciente o tutor, realizar un UPDATE cambiando el campo `estado` a `'inactivo'`.
- Todas las consultas SELECT (listados, búsquedas, validaciones) deben incluir la cláusula `WHERE estado = 'activo'`.

## 2. Permisos y Vistas por Rol

- **Recepcionista:** Tiene acceso al CRUD de datos de contacto (Tablas `paciente` y `tutor`). PUEDE ver: Nombre, Edad, Sexo. NO PUEDE ver ni editar: Tipo de Sangre, Alergias, ni el Historial de Consultas.
- **Pediatra:** Acceso total. Es el único que puede asignar/editar el "Tipo de Sangre", "Alergias" y ver la pestaña de "Historial Médico" y "Vacunas".

## 3. Módulo de Registro y Edición (`/Pacientes/nuevo.php` y `editar.php`)

- **Validación Anti-Duplicados:** Al hacer POST, usar `obtenerDatos()` para verificar si ya existe un paciente con el mismo `nombre`, `apellidos` y `fecha_nacimiento` (que esté activo). Si existe, detener y mostrar error.
- **Flujo de Tutores (UI y Transacción):**
  - Al registrar, debe haber un botón "Buscar Tutor Existente" que abra un modal para buscar por nombre/teléfono. Si es nuevo, se llena el formulario del tutor.
  - Se pueden agregar múltiples tutores al mismo paciente.
  - La lógica PHP DEBE usar una **Transacción PDO**: Insertar paciente -> Insertar tutor(es) si son nuevos -> Insertar registros en la tabla `paciente_tutor` uniendo ambos IDs con su `parentesco`.
- **Modificación y Baja (`editar.php`):** Accesible mediante POST (Regla CERO GET). Permite actualizar datos, desvincular tutores (borrando de la tabla pivote) o dar de baja al paciente (cambiando `estado` a inactivo).

## 4. Perfil del Paciente (El Hub) - `/Pacientes/perfil.php`

- Acceso estricto mediante POST enviando el `id_paciente`.
- **Banner Superior (Atención Rápida):**
  - Mostrar: Nombre completo, Sexo.
  - **Lógica de Edad Especial (PHP):** Crear función `calcularEdad($fecha_nacimiento)`.
    - Si la diferencia con hoy es `< 3 años`, imprimir formato: "2 años y 4 meses".
    - Si es `>= 3 años`, imprimir solo años: "4 años".
  - Solo si el rol en `$_SESSION` es 'pediatra', mostrar en el banner el "Tipo de Sangre" y un tag rojo de "ALERGIAS: [descripción]".
- **Pestañas de Navegación del Perfil:**
  - "Datos de Contacto" (Visible para todos).
  - "Próximas Citas" (Visible para todos).
  - "Historial Clínico" (Visible SOLO para Pediatra).
  - "Vacunas" (Visible SOLO para Pediatra).
