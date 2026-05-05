# Especificación de Módulo: Fase 3 (Agenda y Citas)

## 1. Reglas de Base de Datos para este Módulo

El agente debe asumir que el esquema ya existe.

- La tabla `configuracion` contiene las variables `duracion_primera_cita` y `duracion_cita_regular`.
- La tabla `citas` incluye el campo `tipo_cita` (`'primera_vez'`, `'consecuente'`) y los estados correspondientes (`'pendiente'`, `'confirmada'`, `'cancelada'`, `'realizada'`).

## 2. Vista de Calendario (`/Agenda/index.php`)

- **Librería Frontend:** Integrar **FullCalendar.js** vía CDN para renderizar el calendario.
- **Vistas Disponibles:** Habilitar las vistas `dayGridMonth`, `timeGridWeek`, `timeGridDay` y `listYear` (estilo Google Calendar).
- **Carga de Datos:** Crear un endpoint interno en PHP (ej. `/Agenda/api_citas.php`) que consulte las citas de la base de datos y devuelva el arreglo en formato JSON para que FullCalendar las consuma.

## 3. Lógica de Agendamiento (Anti-Double Booking)

- **Formulario de Nueva Cita (`/Agenda/crear.php`):** - Usar un `select` o buscador AJAX para asignar el `id_paciente`.
  - Seleccionar `fecha_hora` y el `tipo_cita`.
- **Cálculo de Tiempo:**
  - Al procesar el POST, usar `obtenerDatos()` sobre la tabla `configuracion` para saber cuántos minutos dura la cita según el `tipo_cita` seleccionado.
  - Sumar esos minutos a la `fecha_hora` de inicio para calcular la `fecha_hora_fin`.
- **Validación Estricta:** - Ejecutar una consulta SQL para verificar si el rango de tiempo [inicio - fin] se superpone con alguna cita existente en ese día (excluyendo aquellas cuyo estado sea `'cancelada'`).
  - Si hay superposición, abortar transacción y mostrar un modal de error: "Horario no disponible".

## 4. Gestión de Estados y Modales (Cero GET)

- **Visualización (Recepcionista/Pediatra):**
  - Al hacer click en un evento del calendario en el frontend, NO redirigir mediante GET. Abrir un Modal HTML/CSS en la misma página.
  - El modal hará una petición (AJAX/Fetch) enviando el `id_cita` por POST para traer: Datos de la cita, Nombre del Paciente, Tutores y Teléfonos.
- **Acciones (Recepcionista):**
  - En el modal de detalle, mostrar botones: "Confirmar Cita" (cambia estado a `confirmada`), "Cancelar Cita" (cambia estado a `cancelada`) y una "X" para cerrar.
  - Las acciones de estos botones deben enviarse mediante POST.
- **Finalización (Pediatra):**
  - El estado pasará a `realizada` de forma automatizada únicamente cuando el pediatra finalice el flujo en el Módulo de Consultas (Fase 4). La recepcionista no maneja este estado.
