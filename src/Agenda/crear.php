<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/citas.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_sesion'])) {
    cerrarSesion();
    header('Location: /login');
    exit;
}

$db = conectar();
$usuario = usuarioAutenticado();
$errores = [];
$exito = false;

$pediatras = listarPediatras($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agendar_cita'])) {
    $idPaciente = $_POST['id_paciente'] ?? null;
    $idUsuario = $_POST['id_usuario'] ?? null;
    $fechaHora = $_POST['fecha_hora'] ?? null;
    $tipoCita = $_POST['tipo_cita'] ?? null;
    $motivo = trim($_POST['motivo'] ?? '');

    if (!$idPaciente || !$idUsuario || !$fechaHora || !$tipoCita) {
        $errores[] = 'Todos los campos son obligatorios';
    }

    if (!in_array($tipoCita, ['primera_vez', 'consecuente'], true)) {
        $errores[] = 'Tipo de cita no valido';
    }

    if (empty($errores)) {
        $resultado = agendarCita($db, (int) $idPaciente, (int) $idUsuario, $fechaHora, $tipoCita, $motivo ?: null);

        if ($resultado['exito']) {
            $exito = true;
        } else {
            $errores[] = $resultado['error'];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Agendar Cita</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="/Agenda/index" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <a href="/Agenda/index" class="btn btn-outline btn-sm">Volver al calendario</a>
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Agenda/crear" method="POST" style="display:inline;">
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="text-lg font-semibold">Agendar Nueva Cita</h1>
        </div>

        <?php if ($exito): ?>
            <div class="alert alert-success">
                <strong>Cita agendada exitosamente.</strong> <a href="/Agenda/index">Volver al calendario</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <form action="/Agenda/crear" method="POST" id="formCita">
                <input type="hidden" name="agendar_cita" value="1">
                <input type="hidden" name="id_paciente" id="id_paciente" value="">

                <div class="form-group">
                    <label class="form-label" for="busqueda_paciente">Paciente</label>
                    <input type="text" class="form-input" id="busqueda_paciente" placeholder="Escribe el nombre del paciente..." autocomplete="off">
                    <div class="ajax-resultados" id="resultados_paciente" style="display: none;"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="id_usuario">Medico</label>
                        <select class="form-input" id="id_usuario" name="id_usuario" required>
                            <option value="">Seleccionar medico</option>
                            <?php foreach ($pediatras as $pediatra): ?>
                                <option value="<?= $pediatra['id_usuario'] ?>">
                                    <?= htmlspecialchars($pediatra['nombre'] . ' ' . $pediatra['apellidos']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tipo_cita">Tipo de Cita</label>
                        <select class="form-input" id="tipo_cita" name="tipo_cita" required>
                            <option value="">Seleccionar tipo</option>
                            <option value="primera_vez">Primera vez</option>
                            <option value="consecuente">Consecuente</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="fecha_hora">Fecha y Hora</label>
                    <input type="datetime-local" class="form-input" id="fecha_hora" name="fecha_hora" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="motivo">Motivo (opcional)</label>
                    <input type="text" class="form-input" id="motivo" name="motivo" maxlength="100" placeholder="Motivo de la consulta">
                </div>

                <div class="flex gap-md mt-lg">
                    <button type="submit" class="btn btn-primary">Agendar Cita</button>
                    <a href="/Agenda/index" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function() {
        const inputBusqueda = document.getElementById('busqueda_paciente');
        const inputIdPaciente = document.getElementById('id_paciente');
        const contenedorResultados = document.getElementById('resultados_paciente');
        let debounceTimer = null;

        inputBusqueda.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const termino = this.value.trim();

            if (termino.length < 2) {
                contenedorResultados.style.display = 'none';
                contenedorResultados.innerHTML = '';
                inputIdPaciente.value = '';
                return;
            }

            debounceTimer = setTimeout(function() {
                fetch('/Agenda/buscar_pacientes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ termino: termino })
                })
                .then(function(response) { return response.json(); })
                .then(function(pacientes) {
                    if (pacientes.length === 0) {
                        contenedorResultados.style.display = 'none';
                        contenedorResultados.innerHTML = '';
                        return;
                    }

                    let html = '';
                    pacientes.forEach(function(p) {
                        const nombreCompleto = p.nombre + ' ' + p.apellidos;
                        const fechaNac = new Date(p.fecha_nacimiento).toLocaleDateString('es-MX');
                        html += '<div class="ajax-resultado-item" data-id="' + p.id_paciente + '" data-nombre="' + nombreCompleto + '">' +
                                '<strong>' + nombreCompleto + '</strong>' +
                                '<span class="text-muted text-sm">Nac. ' + fechaNac + '</span>' +
                                '</div>';
                    });

                    contenedorResultados.innerHTML = html;
                    contenedorResultados.style.display = 'block';
                })
                .catch(function() {
                    contenedorResultados.style.display = 'none';
                });
            }, 300);
        });

        contenedorResultados.addEventListener('click', function(e) {
            const item = e.target.closest('.ajax-resultado-item');
            if (!item) return;

            const id = item.getAttribute('data-id');
            const nombre = item.getAttribute('data-nombre');

            inputIdPaciente.value = id;
            inputBusqueda.value = nombre;
            contenedorResultados.style.display = 'none';
            contenedorResultados.innerHTML = '';
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#busqueda_paciente') && !e.target.closest('#resultados_paciente')) {
                contenedorResultados.style.display = 'none';
                contenedorResultados.innerHTML = '';
            }
        });

        const formCita = document.getElementById('formCita');
        formCita.addEventListener('submit', function(e) {
            if (!inputIdPaciente.value) {
                e.preventDefault();
                alert('Debes seleccionar un paciente de la lista');
                inputBusqueda.focus();
            }
        });
    })();
    </script>
</body>
</html>
