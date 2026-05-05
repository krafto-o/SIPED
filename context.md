# Contexto del Proyecto SIPED - Fases 0, 1, 2 y 3 Completadas

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

## Fases Completadas

### FASE 0: Entorno y Testing
- Configuracion de PHPUnit y pruebas de `Funciones_SQL.php`
- Seeders con usuarios, configuracion y vacunas

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

## Archivos Modificados

| Archivo | Cambio |
|---|---|
| `Dockerfile` | Habilitado `mod_headers` para `.htaccess` |
| `src/phpunit.xml` | Agregado testsuite `Integration` |
| `src/Agenda/index.php` | Reemplazada tabla placeholder por FullCalendar.js + modal detalle + persistencia de vista en localStorage |
| `src/css/estilos.css` | +115 lineas: estilos AJAX search, modal-lg, cita-detalle, overrides FullCalendar |
| `src/funciones/seeders.php` | Agregados `duracion_primera_cita` (30 min) y `duracion_cita_regular` (20 min) |
| `roadmap.md` | Marcadas tareas Fase 1 (1.1-1.5), Fase 2 (2.1-2.4) y Fase 3 (3.1-3.3) como completadas |

## Bugs Corregidos

1. **Cerrar sesion no funcionaba**: El `header('Location: /login')` se ejecutaba despues de enviar HTML. Solucion: mover la logica de cerrar sesion al inicio de cada archivo, antes de cualquier output.
2. **Boton registrar sin efecto**: El formulario enviaba datos vacios sin validacion JS. Solucion: validacion completa antes de submit + modal de errores.
3. **Transacciones anidadas en tests**: `registrarPacienteConTutores()` iniciaba `beginTransaction()` dentro de un test que ya tenia transaccion activa. Solucion: verificar `$db->inTransaction()` antes de iniciar.
4. **`actualizarDatos()` con params posicionales**: La funcion usa named params internamente pero se le pasaban `?`. Solucion: cambiar llamadas a usar `:nombre_param`.
5. **Modal detalle cita - URL incorrecta**: El fetch apuntaba a `/Agenda/api_detalle` pero el archivo es `api_detalle_cita.php`. Solucion: corregir URL en index.php.
6. **Boton confirmar cita - "datos incompletos"**: El input hidden `accion` nunca recibia su valor. Solucion: agregar `document.getElementById('modalAccion').value = 'confirmar'` al abrir modal.
7. **Calendario perdia vista al recargar**: Tras confirmar/cancelar cita, el redirect recargaba siempre en `timeGridWeek`. Solucion: persistir vista y fecha en localStorage con callback `datesSet` de FullCalendar.

## Estado del Entorno

| Componente | Estado |
|---|---|
| Contenedores Docker | 3 corriendo: `siped_web` (80), `siped_db` (3307), `siped_adminer` (8081) |
| PHP | 8.3.30 en contenedor |
| MariaDB | 10.11 |
| Composer | Instalado, dependencias: DomPDF v3.1.5, PHPUnit v11.5.55 |
| PHPUnit | 64 tests pasando, 146 assertions, 0 fallos |
| Base de datos | 13 tablas creadas, seeders aplicados (incluye duracion_primera_cita y duracion_cita_regular) |

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

## Reglas Implementadas

- **CERO GET**: Todos los IDs sensibles se pasan por POST con `input type="hidden"`
- **Bajas logicas**: Nunca se usa `DELETE` para registros principales, siempre `estado = 'inactivo'`
- **WHERE estado = 'activo'**: Todas las consultas SELECT filtran por estado activo
- **Transacciones PDO**: Operaciones multi-tabla usan `beginTransaction()` / `commit()` / `rollBack()`
- **Sin PDO crudo**: Solo se usan las funciones de `Funciones_SQL.php` en vistas/controladores
- **Nomenclatura**: PHP `camelCase`, BD `snake_case`, todo en espanol
- **Vanilla JS**: Cero dependencias externas en frontend (sin jQuery), debouncing en inputs de busqueda

## Proximo Paso: Fase 4

Segun `roadmap.md` y `spec_fase4.md` (por crear), la Fase 4 incluye:
- 4.1 Iniciar consulta con borradores autoguardados (`borradores_consultas`)
- 4.2 Finalizar consulta y generar receta PDF con DomPDF
- 4.3 Subida segura de archivos adjuntos (estudios de laboratorio)
- Cambio automatico de cita a `realizada` al finalizar consulta
