# Roadmap de Desarrollo - SIPED (To-Do List)

Este documento define la secuencia lógica de desarrollo para el Sistema Integral de Gestión para Consultorio Pediátrico.
**Regla para el Agente:** Al iniciar una tarea, marca la casilla `[x]` cuando esté completamente testeada y funcional. NO saltes de fase sin terminar la anterior.

## FASE 0: Entorno y Testing

- [X] **0.1. Configuración de PHPUnit**
  - **Descripción:** Instalar PHPUnit en el contenedor de desarrollo y configurar `phpunit.xml`.
  - **Implementación:** Crear `composer.json` para requerir `phpunit/phpunit` como dependencia de desarrollo. Configurar para que apunte a `/tests`.
- [X] **0.2. Pruebas Base del Core (Funciones_SQL)**
  - **Descripción:** Crear `FuncionesSQLTest.php` en `/tests/Integration`.
  - **Implementación:** Escribir pruebas para `obtenerDatos()`, `insertarDatos()` y `actualizarDatos()` validando la conexión a MariaDB y probando el CRUD bajo transacciones con `rollBack()`.

## FASE 1: Fundación y Seguridad (Autenticación)

- [X] **1.1. Seeders (Datos de prueba iniciales)**
  - **Descripción:** Script para poblar las tablas base.
  - **Implementación:** Insertar un Pediatra y Recepcionista en `usuarios` (usando `password_hash()`), datos en `configuracion` (tiempos de consulta), y catálogo en `vacunas_catalogo`.
- [X] **1.2. Sistema de Login (`/public/login.php`)**
  - **Descripción:** Interfaz de inicio de sesión dual (correo o teléfono) y modal de errores.
  - **Implementación:** Formulario POST. Validar con `obtenerDatos()`. Usar `password_verify()`. Iniciar `$_SESSION` con `id_usuario`, `rol` y `ultimo_acceso`.
- [X] **1.3. Middleware de Autenticación (`/funciones/auth.php`)**
  - **Descripción:** Protección de rutas y timeout.
  - **Implementación:** Verificar que exista sesión y que la inactividad no exceda 10 minutos. Crear función `requerirRol()`.
- [X] **1.4. Dashboard Pediatra (`/Dashboard/pediatra.php`)**
  - **Descripción:** Panel principal del pediatra con citas del día y búsqueda de pacientes.
  - **Implementación:** Botones rápidos, lista de citas hoy con botón "Atender" por POST, barra de búsqueda para atención sin cita.
- [X] **1.5. Vista Agenda Recepcionista (`/Agenda/index.php`)**
  - **Descripción:** Vista preliminar de citas del día para recepcionista.
  - **Implementación:** Tabla con citas del día (placeholder hasta Fase 3 con FullCalendar). Protegido por `requerirRol()`.

## FASE 2: Gestión Central (Tutores y Pacientes)

- [X] **2.1. Registro Dual de Pacientes y Tutores (`/Pacientes/nuevo.php`)**
  - **Descripción:** Registrar paciente y vincular `N` tutores.
  - **Implementación:** Transacción PDO. Validar duplicados. Insertar paciente, tutor (si es nuevo), y la relación en la tabla pivote `paciente_tutor`.
- [X] **2.2. Listado y Búsqueda (`/Pacientes/index.php`)**
  - **Descripción:** Listado y buscador de pacientes.
  - **Implementación:** Búsqueda `LIKE` en BD asegurando filtrar solo `WHERE estado = 'activo'`.
- [X] **2.3. Perfil del Paciente (`/Pacientes/perfil.php`)**
  - **Descripción:** Vista "Hub". Acceso mediante POST (Cero GET).
  - **Implementación:** Calcular edad en años/meses. Restringir pestañas de Historial y Alergias solo al rol Pediatra.
- [X] **2.4. Edición y Baja Lógica (`/Pacientes/editar.php`)**
  - **Descripción:** Actualizar datos o dar de baja.
  - **Implementación:** Actualizar `estado = 'inactivo'` en lugar de sentencias DELETE. Edición de pivote `paciente_tutor`.

## FASE 3: Gestión de Tiempos (Agenda y Citas)

- [x] **3.1. Calendario Interactivo (`/Agenda/index.php`)**
  - **Descripción:** Agenda principal.
  - **Implementación:** Integrar `FullCalendar.js`. Crear endpoint `/Agenda/api_citas.php` que retorne JSON.
- [x] **3.2. Agendar Nueva Cita (`/Agenda/crear.php`)**
  - **Descripción:** Modal/Formulario de agendamiento.
  - **Implementación:** Leer tabla `configuracion` para sumar minutos según `tipo_cita`. Ejecutar validación Anti-Double Booking antes de insertar.
- [x] **3.3. Gestión de Estados (`/Agenda/gestionar.php`)**
  - **Descripción:** Modal para confirmar o cancelar citas.
  - **Implementación:** Petición AJAX/POST (Cero GET) para actualizar el `estado` de la cita.

## FASE 4: Operación Médica (Consultas e Historial)

- [x] **4.1. Iniciar Consulta y Borradores (`/Consultas/iniciar.php`)**
  - **Descripción:** Formulario médico segmentado y autoguardado.
  - **Implementación:** Script JS enviando datos cada 30s a `borradores_consultas`. Lista dinámica UI para agregar tratamientos.
- [x] **4.2. Finalizar Consulta y Receta (`/Consultas/finalizar.php`)**
  - **Descripción:** Guardado definitivo y PDF.
  - **Implementación:** Transacción PDO (insertar en `consultas`, iterar `tratamientos`, eliminar borrador y cambiar cita a `realizada`). Generar receta con `DomPDF` en ruta temporal.
- [x] **4.3. Archivos Adjuntos (`/Consultas/subir_archivo.php`)**
  - **Descripción:** Subida segura de estudios.
  - **Implementación:** Guardar archivo fuera del webroot, insertar en `archivos_adjuntos` y crear script de descarga validada.

## FASE 5: Especialización Pediátrica (Vacunas)

- [x] **5.1. Cartilla Digital y Alertas (`/Vacunas/cartilla.php`)**
  - **Descripción:** Visualización de vacunas en el Perfil del Paciente.
  - **Implementación:** Algoritmo que compara edad del niño con `vacunas_catalogo`. Si hay atrasos, mostrar banner amarillo.
- [x] **5.2. Aplicación de Vacunas (`/Vacunas/aplicar.php`)**
  - **Descripción:** Registro independiente de dosis.
  - **Implementación:** Formulario con checkbox `aplicada_externamente` que anule la obligatoriedad del campo lote.

## FASE 6: Administración (Pagos y Reportes)

- [ ] **6.1. Caja de Cobro (`/Pagos/index.php`)**
  - **Descripción:** Listado de cuentas por cobrar.
  - **Implementación:** Mostrar consultas con `estado_pago = 'pendiente'`.
- [ ] **6.2. Registro de Cobro (`/Pagos/nuevo.php`)**
  - **Descripción:** Modal para cobrar.
  - **Implementación:** Transacción PDO para insertar en `pagos` y actualizar `consultas.estado_pago = 'pagado'`.
- [ ] **6.3. Dashboard y Exportación (`/Reportes/index.php`)**
  - **Descripción:** Panel financiero.
  - **Implementación:** Consultas de agregación SQL (`SUM()`) por día/semana/mes. Botón para exportar corte de caja a PDF con `DomPDF`.
