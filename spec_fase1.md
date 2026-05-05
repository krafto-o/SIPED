# Especificación de Módulo: Fase 1 (Autenticación y Aterrizaje)

## 1. Seeders (Datos de Prueba Iniciales)

Antes de programar el login, crea un script `/funciones/seeders.php` que use `insertarDatos()` para poblar la base de datos vacía y permitir las pruebas:

- Insertar al menos un usuario con rol `'pediatra'` y uno con rol `'recepcionista'` en la tabla `usuarios`. Las contraseñas deben generarse con `password_hash('12345', PASSWORD_BCRYPT)`.
- Insertar las variables de tiempo base en la tabla `configuracion`.
- Poblar la tabla `vacunas_catalogo` con al menos 3 vacunas de prueba.

## 2. Interfaz de Login (`/public/login.php`)

- **Diseño Visual:** Caja de login centrada vertical y horizontalmente. Diseño minimalista.
- **Campos del Formulario:**
  - Input de Identificación: Debe aceptar tanto el **Correo Electrónico** como el **Teléfono** del usuario.
  - Input de Contraseña: De tipo password.
  - Botón principal: "Acceder".
  - Enlace inferior: "Olvidé mi contraseña" (Muestra un simple `alert` o modal indicando "Contacte al administrador del sistema").
- **Manejo de Errores Visuales:** Si las credenciales son incorrectas, NO recargar la página abruptamente. Mostrar una **Ventana Modal** conteniendo la descripción del error y un icono 'X' para cerrarla.

## 3. Lógica de Autenticación (`/funciones/auth.php` y backend de login)

- **Login Dual:** La consulta SQL usando `obtenerDatos()` debe buscar en la tabla `usuarios` verificando `WHERE (correo = ? OR telefono = ?) AND estado = 'activo'`.
- **Verificación:** Usar estrictamente `password_verify($password_ingresada, $usuario['password'])`.
- **Timeout de Sesión:** El tiempo máximo de inactividad es de **10 minutos**.
  - Al iniciar sesión exitosamente, guardar `$_SESSION['id_usuario']`, `$_SESSION['rol']` y `$_SESSION['ultimo_acceso'] = time()`.
  - En el middleware de protección de rutas (ej. `requerirRol()`), verificar si `(time() - $_SESSION['ultimo_acceso']) > 600`. Si es mayor, destruir la sesión y redirigir al login con un parámetro (ej. `?error=timeout`) para mostrar el modal de sesión expirada. Actualizar el `ultimo_acceso` en cada petición válida.

## 4. Redirección Post-Login (Aterrizaje por Rol)

- **Si el rol es 'recepcionista':**
  - Redirigir a `/Agenda/index.php`.
  - Debe mostrar la vista de calendario con las **Citas del día**.
- **Si el rol es 'pediatra':**
  - Redirigir a `/Dashboard/pediatra.php`.
  - **Estructura del Dashboard:**
    - Panel de botones rápidos: "Ver Agenda Completa", "Estadísticas del Sistema".
    - Lista central: Mostrar los pacientes que tienen cita el día de HOY, con un botón directo a "Atender" (que enviará el `id_cita` por POST, respetando la regla CERO GET).
    - Edge Case (Atención sin cita): Barra de búsqueda para buscar un paciente directamente por nombre e iniciar una consulta inmediata saltándose la agenda.
