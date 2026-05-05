# Especificación de Módulo: Fase 6 (Administración, Pagos y Reportes)

## 1. Reglas de Base de Datos para este Módulo

El agente debe asumir que el esquema ya existe y está completo.

- Interactuar con la tabla `consultas`, específicamente con la columna `estado_pago` (`'pendiente'`, `'pagado'`).
- Interactuar con la tabla `pagos` para registrar los ingresos monetarios vinculados a un `id_consulta`.

## 2. Módulo de Cobros (`/Pagos/index.php`)

- **Acceso:** Permitido para Recepcionista y Pediatra.
- **Vista Principal (Caja de Cobro):**
  - Mostrar una tabla con todas las consultas cuyo `estado_pago` sea estrictamente igual a `'pendiente'`.
  - **Columnas de la tabla:** Fecha de la consulta, Nombre del Paciente (requiere JOIN entre `consultas`, `citas` y `paciente`), Motivo de la consulta y un botón de "Registrar Pago".

## 3. Lógica de Registro de Pago (Cero GET)

- **Interacción UI:** Al hacer click en "Registrar Pago", NO usar enlaces GET. Abrir un Modal o cargar un formulario pasando el `id_consulta` por un input oculto (POST).
- **Formulario (`/Pagos/nuevo.php`):**
  - `Input Number`: Monto a cobrar.
  - `Select`: Forma de Pago (Efectivo, Tarjeta, Transferencia).
- **Transacción PDO Estricta:** Al procesar el guardado, se DEBE abrir una transacción:
  1. Insertar el registro en la tabla `pagos`.
  2. Actualizar el registro en la tabla `consultas` (`UPDATE consultas SET estado_pago = 'pagado' WHERE id_consulta = ?`).
  3. Hacer `commit()`. Si algo falla, hacer `rollBack()` y mostrar error.

## 4. Dashboard de Negocio y Reportes (`/Reportes/index.php`)

- **Acceso:** Permitido para Recepcionista y Pediatra (la recepcionista lo necesita para el corte de caja diario).
- **Vista Principal:**
  - Diseño limpio con 3 tarjetas (cards) principales de métricas:
    1. **Ingresos del Día:** Suma total de pagos registrados `WHERE DATE(fecha_pago) = CURDATE()`.
    2. **Ingresos de la Semana:** Suma total usando `YEARWEEK(fecha_pago, 1) = YEARWEEK(CURDATE(), 1)`.
    3. **Ingresos del Mes:** Suma total usando el mes actual.
  - Mostrar debajo una tabla o listado de los pagos individuales recientes (historial de caja).
- **Exportación a PDF:**
  - Agregar botones "Exportar Corte del Día" y "Exportar Reporte Mensual".
  - Estos botones envían un formulario POST a `/Reportes/exportar.php` con el rango de fechas a exportar.
  - **Librería PDF:** Utilizar `DomPDF` para generar un documento estructurado en formato de tabla con el logo de la clínica, la lista de pagos, el total generado y una línea para la firma de la persona que realiza el corte de caja.
