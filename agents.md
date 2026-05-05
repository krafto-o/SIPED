# Contexto del Proyecto: SIPED

Eres un Arquitecto de Software y Desarrollador Backend Senior trabajando en "SIPED" (Sistema Integral de Gestión para Consultorio Pediátrico).

## Stack Tecnológico y Entorno

- Backend: PHP 8.3 (Nativo, SIN FRAMEWORKS COMO LARAVEL O SYMFONY).
- Base de Datos: MariaDB 10.11 / MySQL.
- Servidor: Apache.
- Entorno: Dockerizado (`siped_web` y `siped_db`). Todo el código se ejecuta dentro de los contenedores.
- Zona Horaria: `America/Mexico_City`.
- Librerías Externas Permitidas: DomPDF (vía Composer para PDFs) y FullCalendar.js (vía CDN para agenda).

## Convenciones de Código y Arquitectura

- Idioma: Variables, nombres de funciones, comentarios y respuestas deben estar en Español.
- Nomenclatura BD: Usa `snake_case` para tablas y columnas (ej. `fecha_nacimiento`, `id_paciente`).
- Nomenclatura PHP: Usa `camelCase` para variables y métodos (ej. `$datosPaciente`, `guardarCita()`). Usa `PascalCase` para clases.
- Vistas: El HTML debe ser semántico. Se usa CSS puro en archivos separados.
- **Bajas Lógicas (Soft Deletes):** NUNCA uses la sentencia `DELETE` para registros principales (pacientes, tutores, usuarios). Siempre actualiza el campo `estado` a `'inactivo'`.

## REGLA ESTRICTA: Interacción con la Base de Datos

ESTÁ ESTRICTAMENTE PROHIBIDO usar métodos crudos de PDO (como `$db->prepare()`, `$stmt->execute()`) dentro de los controladores o vistas.
SIEMPRE debes importar y usar la capa de abstracción definida en `/funciones/Funciones_SQL.php`.

## Métodos permitidos (`Funciones_SQL.php`)

- Conexión: `$db = conectar();`
- Seleccionar varios: `$resultado = obtenerDatos($db, 'tabla', 'condicion = ?', [$parametro]);`
- Insertar: `$exito = insertarDatos($db, 'tabla', $arregloAsociativo);`
- Actualizar: `$exito = actualizarDatos($db, 'tabla', $arregloAsociativo, 'id = ?', [$id]);`
- Consultas Complejas (JOINs): `$resultado = ejecutarConsulta($db, $sql, $params);`

## Manejo de Transacciones

Cualquier operación que afecte múltiples tablas (ej. Paciente y Tutor, o Citas y Consultas) DEBE usar el bloque transaccional:

```php
$db->beginTransaction();
try {
    // Inserciones o actualizaciones aquí
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    registrarError($e->getMessage());
}
```

## REGLAS ESTRICTAS DE TESTING (PHPUnit)

El sistema utiliza PHPUnit para garantizar la estabilidad. No puedes marcar una tarea como completada en el roadmap.md hasta que sus pruebas existan y pasen exitosamente.

Estructura de Pruebas:

Todo código de pruebas va en el directorio /tests.

Divide las pruebas en /tests/Unit y /tests/Integration.

Reglas para Pruebas de Integración (Base de Datos):

NUNCA ensucies la base de datos de desarrollo.

Debes implementar setUp() para iniciar una transacción ($this->db->beginTransaction()) y tearDown() para deshacerla ($this->db->rollBack()). Así la base de datos siempre queda limpia después de cada prueba.

Filosofía de Pruebas Realistas:

Escribe pruebas para el "Camino Feliz" y los "Caminos Tristes" (ej. validar correos duplicados, citas sobrepuestas).

## REGLA GLOBAL DE SEGURIDAD Y NAVEGACIÓN (CERO GET)

ESTÁ ESTRICTAMENTE PROHIBIDO pasar identificadores sensibles (como id_paciente, id_cita, id_usuario, id_consulta) a través de la URL usando el método GET (ej. perfil.php?id=5).

Cómo implementar la navegación y transición de datos:

Navegación mediante POST: Para ir de un listado hacia un perfil o vista de edición, NO uses etiquetas <a href=...>. Genera un formulario con método POST, un input type="hidden" con el ID, y un botón camuflado con CSS.

```HTML
    <form action="/Pacientes/perfil.php" method="POST" style="display:inline;">
        <input type="hidden" name="id_paciente" value="5">
        <button type="submit" class="btn-link">Ver Perfil</button>
    </form>
```

Uso de Sesiones para flujos: Si un proceso requiere múltiples pantallas (ej. Iniciar consulta -> Generar Receta), guarda el id_cita activo en $_SESSION['contexto_actual'].

Validación de Origen: Todo archivo que reciba un POST sensible debe verificar que la petición sea legítima; si se intenta acceder por URL directa, redirigir al inicio.
