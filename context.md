# Contexto del Proyecto SIPED - Fases 0, 1, 2, 3, 4 y 5 Completadas

## Documentos de Referencia Utilizados

El agente utilizo los siguientes archivos `.md` del raiz del proyecto para entender la arquitectura, reglas y especificaciones:

| Archivo | Proposito |
|---|---|
| `agents.md` | Reglas y convenciones para el agente (regla CERO GET, bajas logicas, transacciones, RBAC, nomenclatura, testing) |
| `schema_sql.md` | Esquema completo de las 13 tablas de la base de datos |
| `roadmap.md` | Planificacion de las 6 fases + Fase 0 |
| `design.md` | Design tokens, colores semanticos, tipografia, CSS |
| `spec_fase1.md` | Especificaciones de autenticacion y aterrizaje por rol |
| `spec_fase2.md` | Especificaciones de pacientes, tutores, perfil Hub y permisos |
| `spec_fase3.md` | Especificaciones de agenda, citas, FullCalendar, anti-double booking y gestion de estados |
| `spec_fase4.md` | Especificaciones de consultas, borradores, recetas PDF y archivos adjuntos |
| `spec_fase5.md` | Especificaciones de vacunas, cartilla digital, alertas y registro de dosis |

## Fases Completadas

### FASE 0: Entorno y Testing
- Configuracion de PHPUnit y pruebas de `Funciones_SQL.php`
- Seeders con usuarios, configuracion y vacunas
- BD separada `siped_test` para tests aislada de BD de desarrollo `siped`
- Constante `MODO_PRUEBA` en `phpunit.xml` para detectar modo test automaticamente

### FASE 1: Fundacion y Seguridad (Autenticacion)
- Sistema de login dual (correo o telefono) con `password_verify()`
- Middleware de autenticacion con timeout configurable (10 min)
- Proteccion de rutas por rol (`requerirRol()`)
- Dashboard del pediatra con citas del dia y busqueda sin cita
- Vista de agenda para recepcionista
- CSS completo con tokens de `design.md`

### FASE 2: Gestion Central (Tutores y Pacientes)
- CRUD completo de pacientes con transacciones PDO
- Registro dual: paciente + N tutores (nuevos o existentes)
- Validacion anti-duplicados (nombre + apellidos + fecha_nacimiento)
- Perfil "Hub" con pestanas: contacto, citas, historial, vacunas
- Permisos por rol: recepcionista NO ve tipo_sangre, alergias, historial ni vacunas
- Edicion con gestion de tutores (vincular/desvincular)
- Baja logica (`estado = 'inactivo'`) con confirmacion
- Validaciones JS: solo letras/espacios en nombres, 10 digitos en telefono, arroba en correo

### FASE 3: Gestion de Tiempos (Agenda y Citas)
- FullCalendar.js integrado con 4 vistas: `dayGridMonth`, `timeGridWeek`, `timeGridDay`, `listYear`
- Endpoint JSON (`api_citas.php`) para carga dinamica de eventos
- Formulario de agendar cita con buscador AJAX vanilla JS (debounce 300ms, cierre al click fuera)
- Anti-double booking: valida superposicion de rangos [inicio-fin] excluyendo citas canceladas
- Calculo de duracion desde configuracion: `duracion_primera_cita` (30 min), `duracion_cita_regular` (20 min)
- Modal de detalle de cita con datos de paciente, medico, tutores, telefonos y alertas de alergia
- Gestion de estados via POST (confirmar/cancelar) - cero GET
- Persistencia de vista y fecha del calendario en localStorage
- Filtrado por rol: pediatra solo ve sus citas, recepcionista ve todas
- 17 tests de integracion para modulo de citas

### FASE 4: Operacion Medica (Consultas e Historial)
- Formulario de consulta segmentado: datos clinicos, historial previo (acordeon), consulta actual, laboratorio, diagnostico, tratamientos
- Autoguardado de borradores cada 30s + debounce (1s) via POST a `api_borrador.php`
- Restauracion de borrador con modal de confirmacion al entrar
- Lista dinamica de tratamientos con JS vanilla (inyeccion de bloques HTML)
- Transaccion PDO al finalizar: inserta `consultas`, itera `tratamientos`, elimina borrador, cita → `realizada`
- **Generacion de receta PDF modular**: Plantilla HTML/CSS separada (`src/plantillas/receta/default.php`), funcion `renderizarPlantilla()` con `extract()` para inyeccion de variables. Variables estructuradas como arrays (`$signos_vitales`, `$tratamientos`) para flexibilidad.
- Subida segura de archivos (PDF/JPG/PNG, max 5MB): flujo de dos pasos (temporal antes de finalizar, registro en BD durante transaccion, movimiento a directorio final solo tras commit exitoso)
- Servicio de archivos con validacion de sesion (`ver_archivo.php`)
- Limpieza automatica de recetas > 7 dias y archivos temporales > 24 horas
- Historial clinico en perfil del paciente: pestana con consultas pasadas, boton "Descargar Receta" (usa `obtenerOGenerarReceta()` → `generarRecetaPDF()`)
- Endpoint `api_receta.php` para descarga de recetas existentes o generacion on-demand
- 15 tests de integracion para modulo de consultas

### FASE 5: Especializacion Pediatrica (Vacunas)
- Funciones de vacunas: `obtenerVacunasCatalogo()`, `obtenerVacunasPaciente()`, `parsearEsquemaEdadMeses()`, `calcularVacunasPendientes()`, `verificarVacunaDuplicada()`, `registrarVacuna()`
- Algoritmo de deteccion de vacunas atrasadas: calcula edad en meses, parsea `esquema_edad` del catalogo, compara con vacunas aplicadas
- Banner de alerta minimalista en perfil del paciente: "Aviso: el paciente tiene X vacunas pendientes"
- Detalle de vacunas pendientes (nombre + esquema) dentro de la pestana Vacunas
- Cartilla digital con tabla cronologica: columnas Vacuna, Esquema, Fecha, Lote, Origen
- Badges semanticos: `.badge-interna` (verde) y `.badge-externa` (gris)
- Formulario independiente de registro (`/Vacunas/aplicar`) sin requerir `id_cita` ni `id_consulta`
- Checkbox `aplicada_externamente` deshabilita input de lote via JS vanilla
- Validacion de duplicados (misma vacuna, misma fecha)
- Transacciones PDO con deteccion de transaccion anidada (`$db->inTransaction()`)
- 13 tests de integracion para modulo de vacunas (catalogo, registro, alertas, parseo de edad, duplicados, orden cronologico)

### FASE 6: Administracion (Pagos y Reportes)
- Funciones de pagos: `obtenerConsultasPendientes()`, `obtenerDetalleConsultaParaPago()`, `registrarPago()`
- Caja de cobro (`/Pagos/index`) con tabla de consultas pendientes y modal de cobro
- Modal minimalista: solo datos basicos (paciente, medico, fecha/hora, tipo cita) sin datos clinicos
- Transaccion PDO estricta: inserta en `pagos` + actualiza `consultas.estado_pago = 'pagado'`
- Validaciones: monto > 0, forma_pago valida, consulta no pagada ni cancelada
- Dashboard de reportes (`/Reportes/index`) con 3 metricas: ingresos del dia, semana, mes
- Exportacion a PDF con DomPDF: corte del dia y reporte mensual
- Plantilla PDF modular (`src/plantillas/reporte_corte/default.php`) con tabla de pagos, total y linea de firma
- Filtrado por rol: pediatra solo ve sus consultas pendientes, recepcionista ve todas
- 13 tests de integracion para modulo de pagos (pendientes, filtro rol, excluye canceladas, registro exitoso, validaciones, detalle consulta)

---

## Archivos Creados

| Archivo | Fase | Descripcion |
|---|---|---|
| `src/funciones/auth.php` | 1 | Middleware: `iniciarSesion()`, `verificarSesion()`, `requerirRol()`, `cerrarSesion()`, `usuarioAutenticado()` |
| `src/login.php` | 1 | Login dual, modal de errores, redirect por rol |
| `src/Dashboard/pediatra.php` | 1 | Panel pediatra: citas del dia, botones rapidos, busqueda sin cita |
| `src/Agenda/index.php` | 1 | Vista recepcionista: tabla de citas del dia (placeholder Fase 3) |
| `src/css/estilos.css` | 1 | Sistema de diseño completo (tokens de design.md) |
| `src/funciones/pacientes.php` | 2 | 12 funciones: `calcularEdad()`, `validarDuplicadoPaciente()`, `registrarPacienteConTutores()`, `darBajaPaciente()`, `desvincularTutor()`, `buscarTutores()`, `listarPacientes()`, etc. |
| `src/Pacientes/index.php` | 2 | Listado + busqueda de pacientes activos |
| `src/Pacientes/nuevo.php` | 2 | Registro con validaciones JS, gestion dinamica de tutores |
| `src/Pacientes/perfil.php` | 2 | Vista Hub con banner, pestanas, permisos por rol |
| `src/Pacientes/editar.php` | 2 | Edicion, vincular/desvincular tutores, baja logica |
| `src/tests/Integration/AuthTest.php` | 1 | 11 pruebas de autenticacion |
| `src/tests/Integration/PacientesTest.php` | 2 | 18 pruebas de pacientes |
| `src/funciones/citas.php` | 3 | 8 funciones: `calcularDuracionCita()`, `verificarDisponibilidad()`, `agendarCita()`, `obtenerCitasCalendario()`, `obtenerDetalleCita()`, `cambiarEstadoCita()`, `listarPediatras()`, `buscarPacientesParaCita()` |
| `src/tests/Integration/CitasTest.php` | 3 | 17 pruebas de citas (agendar, anti-double booking, estados, calendario, buscador) |
| `src/Agenda/api_citas.php` | 3 | Endpoint JSON para FullCalendar (eventos con color por estado) |
| `src/Agenda/buscar_pacientes.php` | 3 | Endpoint AJAX para buscador de pacientes (POST JSON) |
| `src/Agenda/api_detalle_cita.php` | 3 | Endpoint POST para detalle de cita (paciente + tutores) |
| `src/Agenda/gestionar_cita.php` | 3 | Handler POST para confirmar/cancelar citas con redirect |
| `src/Agenda/crear.php` | 3 | Formulario agendar cita con buscador AJAX vanilla JS |
| `src/funciones/consultas.php` | 4 | 11 funciones: `obtenerDatosConsulta()`, `obtenerHistorialPaciente()`, `guardarBorrador()`, `obtenerBorrador()`, `eliminarBorrador()`, `finalizarConsulta()`, `guardarArchivoTemporal()`, `registrarArchivosAdjuntos()`, `limpiarRecetasViejas()`, `limpiarArchivosTemporales()`, `generarRecetaPDF()`, `renderizarPlantilla()` |
| `src/Consultas/iniciar.php` | 4 | Vista principal de consulta con formulario segmentado, historial acordeon, tratamientos dinamicos, autoguardado JS |
| `src/Consultas/api_borrador.php` | 4 | Endpoint POST para autoguardado de borradores (JSON) con validacion de propiedad de cita |
| `src/Consultas/api_finalizar.php` | 4 | Endpoint POST para finalizar consulta + generar receta PDF |
| `src/Consultas/api_subir_archivo.php` | 4 | Endpoint POST para subir archivos de laboratorio |
| `src/Consultas/api_receta.php` | 4 | Endpoint POST para obtener receta existente o generarla on-demand |
| `src/Consultas/ver_archivo.php` | 4 | Servir archivos con validacion de sesion y path traversal protection |
| `src/plantillas/receta/default.php` | 4 | Plantilla HTML/CSS pura para PDF de receta (variables via `extract()`) |
| `src/tests/Integration/ConsultasTest.php` | 4 | 15 pruebas de consultas (borradores, finalizar, historial, edad, limpieza) |
| `storage/recetas_temporales/.htaccess` | 4 | Proteccion de acceso directo a recetas |
| `storage/pacientes/.htaccess` | 4 | Proteccion de acceso directo a archivos de pacientes |
| `storage/pacientes/temp/.htaccess` | 4 | Proteccion de acceso directo a archivos temporales |
| `src/funciones/instalar.php` | 0 | Script maestro de instalacion: crea BD, 13 tablas, seeders base y datos de prueba funcionales |
| `src/funciones/setup_test_db.php` | 0 | Script para crear BD `siped_test` limpia para PHPUnit |
| `src/funciones/vacunas.php` | 5 | 6 funciones: `obtenerVacunasCatalogo()`, `obtenerVacunasPaciente()`, `parsearEsquemaEdadMeses()`, `calcularVacunasPendientes()`, `verificarVacunaDuplicada()`, `registrarVacuna()` |
| `src/tests/Integration/VacunasTest.php` | 5 | 13 pruebas de vacunas (catalogo, registro interna/externa, alertas, parseo edad, duplicados, orden cronologico) |
| `src/Vacunas/aplicar.php` | 5 | Formulario de registro de vacuna con validacion, checkbox externa, JS toggle de lote |
| `src/funciones/pagos.php` | 6 | 3 funciones: `obtenerConsultasPendientes()`, `obtenerDetalleConsultaParaPago()`, `registrarPago()` |
| `src/Pagos/index.php` | 6 | Caja de cobro: tabla de pendientes + modal de cobro con datos basicos |
| `src/Pagos/procesar.php` | 6 | Handler POST para registrar pago con transaccion PDO |
| `src/funciones/reportes.php` | 6 | 6 funciones: `obtenerIngresosDia()`, `obtenerIngresosSemana()`, `obtenerIngresosMes()`, `obtenerPagosRecientes()`, `obtenerPagosPorRango()`, `generarReporteCortePDF()` |
| `src/Reportes/index.php` | 6 | Dashboard con metricas de ingresos y tabla de pagos recientes |
| `src/Reportes/exportar.php` | 6 | Handler POST para exportar corte a PDF con descarga forzada |
| `src/plantillas/reporte_corte/default.php` | 6 | Plantilla HTML/CSS para PDF de reporte de corte con tabla y firma |
| `src/tests/Integration/PagosTest.php` | 6 | 13 pruebas de pagos (pendientes, filtro rol, registro, validaciones, detalle) |

## Archivos Modificados

| Archivo | Cambio |
|---|---|
| `Dockerfile` | Habilitado `mod_headers` para `.htaccess` |
| `src/phpunit.xml` | Agregado testsuite `Integration` + constante `MODO_PRUEBA` para BD aislada |
| `src/Agenda/index.php` | Reemplazada tabla placeholder por FullCalendar.js + modal detalle + persistencia de vista en localStorage |
| `src/css/estilos.css` | +270 lineas: estilos AJAX search, modal-lg, cita-detalle, overrides FullCalendar, modulo consultas completo |
| `src/funciones/Funciones_SQL.php` | Funciones envueltas en `if (!function_exists())`; agregada `eliminarRegistro()`; agregada `conectarSinBD()` para crear BDs sin especificar database; `conectar()` detecta `MODO_PRUEBA` y usa `siped_test` |
| `src/funciones/seeders.php` | Refactorizada como funcion reutilizable `seeders_base()` importada por `instalar.php` y `setup_test_db.php` |
| `src/funciones/citas.php` | Agregado `'realizada'` a estados validos en `cambiarEstadoCita()` |
| `src/funciones/pacientes.php` | `desvincularTutor()` usa `eliminarRegistro()` en vez de PDO crudo; `obtenerPacienteCompleto()` filtra por `estado = 'activo'` |
| `src/funciones/consultas.php` | Flujo de archivos en dos pasos (temporal → final dentro de transaccion); `eliminarBorrador()` y `generarRecetaPDF()` dentro de transaccion; `registrarArchivosAdjuntos()` recibe `idCita` para obtener `id_paciente`; `limpiarArchivosTemporales()` para limpieza de huerfanos |
| `src/Consultas/api_borrador.php` | Agregada validacion de propiedad de cita con `obtenerDatosConsulta()` |
| `src/Consultas/api_finalizar.php` | Borrador y receta generados dentro de transaccion via parametros de `finalizarConsulta()` |
| `src/.htaccess` | Agregados headers `X-XSS-Protection` y `Referrer-Policy` |
| `src/login.php` | Corregido bug: `match` usaba `=>` (array key) en vez de `=` (asignacion) para mensajes de error |
| `src/Pacientes/perfil.php` | Agregada pestana "Historial Clinico" con listado de consultas pasadas y boton "Descargar Receta"; Fase 5: import `vacunas.php`, alerta minimalista de vacunas pendientes en banner, contenido real de pestana Vacunas con tabla cronologica y badge de origen |
| `src/css/estilos.css` | Fase 5: +50 lineas: `.badge-interna`, `.badge-externa`, `.vacuna-atrasada`, `.vacuna-tabla`, `.vacuna-form-grid`; padding de tabla aumentado a `var(--spacing-md)` para legibilidad |
| `roadmap.md` | Marcadas tareas Fase 1 (1.1-1.5), Fase 2 (2.1-2.4), Fase 3 (3.1-3.3), Fase 4 (4.1-4.3), Fase 5 (5.1-5.2) y Fase 6 (6.1-6.3) como completadas |
| `src/css/estilos.css` | Fase 6: +60 lineas: `.metric-card`, `.badge-pagado`, `.badge-pendiente-pago`, `.export-actions`, `.firma-linea` |
| `src/Dashboard/pediatra.php` | Fase 6: Agregado boton "Caja de Cobro" en quick-actions |
| `src/Agenda/index.php` | Fase 6: Agregado enlace "Caja de Cobro" en navbar |

## Archivos Eliminados

| Archivo | Motivo |
|---|---|
| `src/funciones/crear_tablas.php` | Fusionado en `instalar.php` |
| `src/funciones/reset_db.php` | Fusionado en `instalar.php` |

## Bugs Corregidos

1. **Cerrar sesion no funcionaba**: El `header('Location: /login')` se ejecutaba despues de enviar HTML. Solucion: mover la logica de cerrar sesion al inicio de cada archivo, antes de cualquier output.
2. **Boton registrar sin efecto**: El formulario enviaba datos vacios sin validacion JS. Solucion: validacion completa antes de submit + modal de errores.
3. **Transacciones anidadas en tests**: `registrarPacienteConTutores()` iniciaba `beginTransaction()` dentro de un test que ya tenia transaccion activa. Solucion: verificar `$db->inTransaction()` antes de iniciar.
4. **`actualizarDatos()` con params posicionales**: La funcion usa named params internamente pero se le pasaban `?`. Solucion: cambiar llamadas a usar `:nombre_param`.
5. **Modal detalle cita - URL incorrecta**: El fetch apuntaba a `/Agenda/api_detalle` pero el archivo es `api_detalle_cita.php`. Solucion: corregir URL en index.php.
6. **Boton confirmar cita - "datos incompletos"**: El input hidden `accion` nunca recibia su valor. Solucion: agregar `document.getElementById('modalAccion').value = 'confirmar'` al abrir modal.
7. **Calendario perdia vista al recargar**: Tras confirmar/cancelar cita, el redirect recargaba siempre en `timeGridWeek`. Solucion: persistir vista y fecha en localStorage con callback `datesSet` de FullCalendar.
8. **Columna `fecha_creacion` inexistente en `consultas`**: Las queries usaban `c.fecha_creacion` pero la columna es `c.fecha_hora` (heredada de `citas`). Solucion: cambiar todas las referencias a `c.fecha_hora`.
9. **`obtenerHistorialPaciente()` columnas faltantes**: No incluia `perimetro_cefalico`, `temperatura`, `frec_cardiaca`, `frec_respiratoria`. El perfil del paciente generaba warnings al acceder a indices inexistentes. Solucion: agregar columnas al SELECT.
10. **`cambiarEstadoCita()` rechazaba 'realizada'**: El estado 'realizada' no estaba en el array de estados validos. Solucion: agregarlo junto a 'pendiente', 'confirmada', 'cancelada'.
11. **Campos decimales con string vacio causaban error**: Si un signo vital no se llenaba, se enviaba `""` que MariaDB rechazaba para columnas `DECIMAL`. Solucion: convertir `""` a `null` antes de insertar.
12. **`Cannot redeclare registrarError()` y otras funciones**: Composer autoload carga `Funciones_SQL.php` automaticamente, y cuando DomPDF incluye `vendor/autoload.php` dentro de `generarRecetaPDF()`, las funciones se redeclaraban. Solucion: envolver todas las funciones en `if (!function_exists('...'))`.
13. **`calcularEdad()` no importada en `consultas.php`**: La funcion `generarRecetaPDF()` llamaba `calcularEdad()` sin haber hecho `require_once '../funciones/pacientes.php'`. Solucion: agregar import al inicio del archivo.
14. **Archivos adjuntos se perdian si la consulta fallaba**: Los archivos se subian directamente al directorio final antes de la transaccion. Solucion: flujo de dos pasos - subir a temporal antes de finalizar, registrar en BD durante la transaccion, mover a directorio final solo si el commit es exitoso.
15. **`desvincularTutor()` usaba PDO crudo**: Violaba regla de `agents.md`. Solucion: reemplazar por `eliminarRegistro()`.
16. **`obtenerPacienteCompleto()` no filtraba por estado**: Inconsistencia con `obtenerPacienteConTutores()`. Solucion: agregar `AND estado = 'activo'`.
17. **`api_borrador.php` sin validacion de propiedad**: Cualquier pediatra podia sobrescribir borrador de otro. Solucion: agregar `obtenerDatosConsulta()` para validar `id_usuario`.
18. **`login.php` mensajes de error nunca se mostraban**: `match` usaba `=>` (sintaxis de array key) en vez de `=` (asignacion). Solucion: corregir a asignacion.
19. **Tests contaminaban BD de desarrollo**: Las pruebas compartian BD con datos manuales. Solucion: BD separada `siped_test` con constante `MODO_PRUEBA`.
20. **`testListadoSoloPacientesActivos` fallaba por datos residuales**: Esperaba 1 paciente pero habia 44. Solucion: usar sufijo `uniqid()` para nombres unicos.
21. **Tests UTF-8 fallaban por encoding**: `assertStringContainsString('anio')` no encontraba `'año'`. Solucion: usar caracteres UTF-8 reales en assertions.
22. **Boton Cancelar en aplicar.php guardaba vacuna**: El `<form>` de cancelar estaba anidado dentro del `<form>` principal (HTML invalido). El navegador ignoraba el form interno y enviaba los datos. Solucion: mover el form de cancelar fuera del form de registro como elemento hermano.
23. **Columnas de tabla de vacunas muy pegadas**: Padding vertical de 8px insuficiente. Solucion: aumentar a `var(--spacing-md)` (16px), agregar `text-transform: uppercase` y `letter-spacing` a headers, hover en filas.
24. **`obtenerVacunasPaciente()` no retornaba `id_vacuna`**: El JOIN no incluia la columna `va.id_vacuna`, causando `Undefined array key` en `calcularVacunasPendientes()`. Solucion: agregar `va.id_vacuna` al SELECT.
25. **`parsearEsquemaEdadMeses()` regex incorrecto**: El patron de anos capturaba antes que el de rangos. Solucion: mover el regex de rango `(\d+)-(\d+)\s*mes` antes del de anos.
26. **`ConsultasTest` flaky por citas residuales**: Las citas de `CitasTest` no se limpiaban entre clases de prueba, causando conflictos de double-booking en `setUpBeforeClass` de `ConsultasTest`. Solucion: agregar `DELETE FROM citas WHERE id_usuario = ?` antes de crear la cita de prueba.

## Estado del Entorno

| Componente | Estado |
|---|---|
| Contenedores Docker | 3 corriendo: `siped_web` (80), `siped_db` (3307), `siped_adminer` (8081) |
| PHP | 8.3.30 en contenedor |
| MariaDB | 10.11 |
| Composer | Instalado, dependencias: DomPDF v3.1.5, PHPUnit v11.5.55 |
| PHPUnit | **87 tests pasando, 269 assertions, 0 fallos** |
| BD desarrollo (`siped`) | 13 tablas + seeders base + datos de prueba funcionales (3 pacientes, 4 tutores, 5 citas, 1 consulta realizada) |
| BD tests (`siped_test`) | 13 tablas + seeders base + datos minimos para tests (1 paciente, 1 tutor) |

## Instalacion desde Cero

Para una instalacion limpia del contenedor:

```bash
docker exec siped_web php /var/www/html/funciones/instalar.php
docker exec siped_web php /var/www/html/funciones/setup_test_db.php
```

Esto crea ambas BDs (`siped` y `siped_test`) con tablas, seeders y datos de prueba funcionales.

## Usuarios de Prueba (Seeders)

| Rol | Correo | Telefono | Password |
|---|---|---|---|
| Pediatra | `pediatra@siped.com` | `5551234567` | `siped123` |
| Recepcionista | `recepcion@siped.com` | `5559876543` | `siped123` |

## Paginas Accesibles

| URL | Sin Auth | Pediatra | Recepcionista |
|---|---|---|---|
| `/login` | 200 | 302 → Dashboard | 302 → Agenda |
| `/Dashboard/pediatra` | 302 → login | 200 | 302 → login |
| `/Agenda/index` | 302 → login | 200 (FullCalendar) | 200 (FullCalendar) |
| `/Agenda/crear` | 302 → login | 200 | 200 |
| `/Agenda/api_citas` | 302 → login | 200 (JSON) | 200 (JSON) |
| `/Agenda/api_detalle_cita` | 302 → login | 200 (POST) | 200 (POST) |
| `/Agenda/buscar_pacientes` | 302 → login | 200 (POST) | 200 (POST) |
| `/Agenda/gestionar_cita` | 302 → login | 200 (POST) | 200 (POST) |
| `/Pacientes/index` | 302 → login | 200 | 200 |
| `/Pacientes/nuevo` | 302 → login | 200 | 200 |
| `/Pacientes/perfil` | 302 → login | 200 (acceso POST) | 200 (acceso POST) |
| `/Pacientes/editar` | 302 → login | 200 (acceso POST) | 200 (acceso POST) |
| `/Consultas/iniciar` | 302 → login | 200 (acceso POST) | 302 → login |
| `/Consultas/api_borrador` | 302 → login | 200 (POST) | 302 → login |
| `/Consultas/api_finalizar` | 302 → login | 200 (POST) | 302 → login |
| `/Consultas/api_subir_archivo` | 302 → login | 200 (POST) | 302 → login |
| `/Consultas/api_receta` | 302 → login | 200 (POST) | 302 → login |
| `/Consultas/ver_archivo` | 302 → login | 200 (GET con ruta) | 302 → login |
| `/Vacunas/aplicar` | 302 → login | 200 (acceso POST) | 302 → login |
| `/Pagos/index` | 302 → login | 200 | 200 |
| `/Pagos/procesar` | 302 → login | 200 (POST) | 200 (POST) |
| `/Reportes/index` | 302 → login | 200 | 200 |
| `/Reportes/exportar` | 302 → login | 200 (POST, descarga PDF) | 200 (POST, descarga PDF) |

## Reglas Implementadas

- **CERO GET**: Todos los IDs sensibles se pasan por POST con `input type="hidden"`
- **Bajas logicas**: Nunca se usa `DELETE` para registros principales, siempre `estado = 'inactivo'`
- **WHERE estado = 'activo'**: Todas las consultas SELECT filtran por estado activo
- **Transacciones PDO**: Operaciones multi-tabla usan `beginTransaction()` / `commit()` / `rollBack()`
- **Sin PDO crudo**: Solo se usan las funciones de `Funciones_SQL.php` en vistas/controladores (incluye `eliminarRegistro()`)
- **Nomenclatura**: PHP `camelCase`, BD `snake_case`, todo en espanol
- **Vanilla JS**: Cero dependencias externas en frontend (sin jQuery), debouncing en inputs de busqueda
- **Plantillas modulares**: PDFs usan `src/plantillas/{tipo}/default.php` con `renderizarPlantilla()` + `extract()`. La logica PHP prepara datos, la plantilla solo presenta.
- **Funciones protegidas contra redeclaracion**: Todas las funciones en `Funciones_SQL.php` envueltas en `if (!function_exists())` para evitar conflictos con Composer autoload.
- **Campos decimales null-safe**: Strings vacios se convierten a `null` antes de insertar en columnas `DECIMAL`.
- **Archivos adjuntos en dos pasos**: Subida a `temp/{id_cita}/` antes de finalizar, registro en BD durante transaccion, movimiento a `pacientes/{id_paciente}/` solo tras commit exitoso.
- **BD aislada para tests**: Constante `MODO_PRUEBA` en `phpunit.xml` hace que `conectar()` use `siped_test` en lugar de `siped`.
- **Headers de seguridad**: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`.
- **Funciones protegidas contra redeclaracion en vacunas.php**: Todas envueltas en `if (!function_exists())`.
- **Parseo inteligente de esquema de edad**: Funcion `parsearEsquemaEdadMeses()` maneja "Recien nacido", "2 meses", "1 año", "6 anos", "6-23 meses" con regex en orden de prioridad.
- **Transacciones anidadas detectadas**: `registrarVacuna()` verifica `$db->inTransaction()` antes de iniciar para evitar conflictos con tests.
- **Checkbox toggle en JS vanilla**: Al marcar `aplicada_externamente`, se deshabilita el input de lote y se remueve `required`.
- **Alerta minimalista en banner**: Solo muestra conteo de vacunas pendientes; el detalle completo (nombre + esquema) se muestra dentro de la pestana Vacunas.

## Estado del Proyecto

Todas las fases (0-6) estan completadas. El sistema cuenta con:
- Autenticacion y autorizacion por roles
- CRUD completo de pacientes y tutores
- Agenda con FullCalendar.js y anti-double booking
- Consultas medicas con borradores, recetas PDF y archivos adjuntos
- Cartilla digital de vacunas con alertas
- Caja de cobro y dashboard de reportes con exportacion PDF
