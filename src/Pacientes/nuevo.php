<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/pacientes.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_sesion'])) {
    cerrarSesion();
    header('Location: /login');
    exit;
}

$db = conectar();
$usuario = usuarioAutenticado();
$esPediatra = $_SESSION['rol'] === 'pediatra';

$errorMensaje = '';
$exitoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_paciente'])) {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $errorMensaje = 'Solicitud no valida.';
    } else {
        regenerarTokenCSRF();

    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');
    $tipoSangre = $esPediatra ? trim($_POST['tipo_sangre'] ?? '') : null;
    $alergias = $esPediatra ? trim($_POST['alergias'] ?? '') : null;

    $tutores = [];
    $tutoresJson = $_POST['tutores_json'] ?? '[]';
    $tutoresData = json_decode($tutoresJson, true) ?? [];

    foreach ($tutoresData as $t) {
        if (!empty($t['nombre']) || !empty($t['id_tutor_existente'])) {
            $tutores[] = [
                'id_tutor_existente' => $t['id_tutor_existente'] ?? null,
                'nombre' => $t['nombre'] ?? '',
                'apellidos' => $t['apellidos'] ?? '',
                'telefono' => $t['telefono'] ?? '',
                'correo' => $t['correo'] ?? null,
                'direccion' => $t['direccion'] ?? null,
                'parentesco' => $t['parentesco'] ?? ''
            ];
        }
    }

    if (empty($nombre) || empty($apellidos) || empty($fechaNacimiento) || empty($sexo)) {
        $errorMensaje = 'Los campos nombre, apellidos, fecha de nacimiento y sexo son obligatorios.';
    } elseif (empty($tutores)) {
        $errorMensaje = 'Debe agregar al menos un tutor al paciente.';
    } elseif (validarDuplicadoPaciente($db, $nombre, $apellidos, $fechaNacimiento)) {
        $errorMensaje = 'Ya existe un paciente activo con ese nombre, apellidos y fecha de nacimiento.';
    } else {
        $datosPaciente = [
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'sexo' => $sexo,
            'estado' => 'activo'
        ];

        if ($esPediatra) {
            $datosPaciente['tipo_sangre'] = !empty($tipoSangre) ? $tipoSangre : null;
            $datosPaciente['alergias'] = !empty($alergias) ? $alergias : null;
        }

        $exito = registrarPacienteConTutores($db, $datosPaciente, $tutores);

        if ($exito) {
            $exitoMensaje = 'Paciente registrado exitosamente.';
        } else {
            $errorMensaje = 'Error al registrar el paciente. Intente nuevamente.';
        }
    }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= generarTokenCSRF() ?>">
    <title>SIPED - Nuevo Paciente</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="/Pacientes/index" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Pacientes/nuevo" method="POST" style="display:inline;">
                <?= campoCSRF() ?>
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Nuevo Paciente</h1>
            <a href="/Pacientes/index" class="btn btn-outline">Volver al listado</a>
        </div>

        <?php if (!empty($exitoMensaje)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($exitoMensaje) ?>
                <a href="/Pacientes/index" class="btn-link">Ver listado</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="/Pacientes/nuevo" id="formPaciente" novalidate>
            <?= campoCSRF() ?>
            <div class="card mb-lg">
                <div class="card-header">
                    <h2>Datos del Paciente</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" class="form-input"
                                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+"
                                   title="Solo se permiten letras y espacios"
                                   required>
                            <span class="field-error" id="error-nombre"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="apellidos">Apellidos *</label>
                            <input type="text" id="apellidos" name="apellidos" class="form-input"
                                   value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+"
                                   title="Solo se permiten letras y espacios"
                                   required>
                            <span class="field-error" id="error-apellidos"></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="fecha_nacimiento">Fecha de Nacimiento *</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-input"
                                   value="<?= htmlspecialchars($_POST['fecha_nacimiento'] ?? '') ?>" required>
                            <span class="field-error" id="error-fecha_nacimiento"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sexo">Sexo *</label>
                            <select id="sexo" name="sexo" class="form-input" required>
                                <option value="">Seleccionar...</option>
                                <option value="masculino" <?= ($_POST['sexo'] ?? '') === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                                <option value="femenino" <?= ($_POST['sexo'] ?? '') === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                            </select>
                            <span class="field-error" id="error-sexo"></span>
                        </div>
                    </div>

                    <?php if ($esPediatra): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="tipo_sangre">Tipo de Sangre</label>
                                <select id="tipo_sangre" name="tipo_sangre" class="form-input">
                                    <option value="">Seleccionar...</option>
                                    <option value="A+" <?= ($_POST['tipo_sangre'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
                                    <option value="A-" <?= ($_POST['tipo_sangre'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
                                    <option value="B+" <?= ($_POST['tipo_sangre'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
                                    <option value="B-" <?= ($_POST['tipo_sangre'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
                                    <option value="AB+" <?= ($_POST['tipo_sangre'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
                                    <option value="AB-" <?= ($_POST['tipo_sangre'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
                                    <option value="O+" <?= ($_POST['tipo_sangre'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
                                    <option value="O-" <?= ($_POST['tipo_sangre'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="alergias">Alergias</label>
                                <input type="text" id="alergias" name="alergias" class="form-input"
                                       value="<?= htmlspecialchars($_POST['alergias'] ?? '') ?>"
                                       placeholder="Ej: Penicilina, Latex...">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-lg">
                <div class="card-header">
                    <h2>Tutores</h2>
                </div>
                <div class="card-body">
                    <div class="flex gap-sm mb-md">
                        <button type="button" class="btn btn-secondary" id="btnNuevoTutor">Agregar Tutor Nuevo</button>
                        <button type="button" class="btn btn-outline" id="btnBuscarTutor">Buscar Tutor Existente</button>
                    </div>

                    <div id="tutoresAgregados" class="tutores-agregados"></div>

                    <div id="formTutorNuevo" class="card mt-md" style="display:none;">
                        <div class="card-header">
                            <h3>Nuevo Tutor</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="tutor_nombre">Nombre *</label>
                                    <input type="text" id="tutor_nombre" class="form-input"
                                           pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+"
                                           title="Solo se permiten letras y espacios">
                                    <span class="field-error" id="error-tutor_nombre"></span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="tutor_apellidos">Apellidos *</label>
                                    <input type="text" id="tutor_apellidos" class="form-input"
                                           pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+"
                                           title="Solo se permiten letras y espacios">
                                    <span class="field-error" id="error-tutor_apellidos"></span>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="tutor_telefono">Telefono *</label>
                                    <input type="tel" id="tutor_telefono" class="form-input"
                                           maxlength="10" inputmode="numeric"
                                           pattern="[0-9]{10}"
                                           title="Debe contener exactamente 10 digitos">
                                    <span class="field-error" id="error-tutor_telefono"></span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="tutor_correo">Correo</label>
                                    <input type="text" id="tutor_correo" class="form-input"
                                           placeholder="ejemplo@correo.com">
                                    <span class="field-error" id="error-tutor_correo"></span>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="tutor_parentesco">Parentesco *</label>
                                    <input type="text" id="tutor_parentesco" class="form-input"
                                           pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+"
                                           title="Solo se permiten letras y espacios"
                                           placeholder="Ej: Madre, Padre...">
                                    <span class="field-error" id="error-tutor_parentesco"></span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="tutor_direccion">Direccion</label>
                                    <input type="text" id="tutor_direccion" class="form-input">
                                </div>
                            </div>
                            <div class="flex gap-sm">
                                <button type="button" class="btn btn-primary" id="btnGuardarTutor">Guardar Tutor</button>
                                <button type="button" class="btn btn-outline" id="btnCancelarTutor">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="tutores_json" id="tutoresJson" value="[]">
            <button type="submit" name="registrar_paciente" class="btn btn-primary btn-block">Registrar Paciente</button>
        </form>
    </div>

    <div class="modal-overlay" id="modalError">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitulo">Error</h3>
                <button type="button" class="modal-close" id="modalCerrar">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalMensaje"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="modalAceptar">Aceptar</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalBuscarTutor">
        <div class="modal">
            <div class="modal-header">
                <h3>Buscar Tutor Existente</h3>
                <button type="button" class="modal-close" id="cerrarModalTutor">&times;</button>
            </div>
            <div class="modal-body">
                <div class="search-bar">
                    <input type="text" id="inputBusquedaTutor" class="form-input" placeholder="Buscar por nombre o telefono..." autocomplete="off">
                </div>
                <div id="resultadosBusquedaTutores" class="tutor-list mt-md" style="display: none;"></div>
                <p class="text-muted text-sm mt-sm" id="mensajeBusquedaTutor">Escribe al menos 2 caracteres para buscar.</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let tutores = [];

            function mostrarModal(titulo, mensaje) {
                document.getElementById('modalTitulo').textContent = titulo;
                document.getElementById('modalMensaje').textContent = mensaje;
                document.getElementById('modalError').classList.add('active');
            }

            function cerrarModal() {
                document.getElementById('modalError').classList.remove('active');
            }

            document.getElementById('modalCerrar').addEventListener('click', cerrarModal);
            document.getElementById('modalAceptar').addEventListener('click', cerrarModal);

            document.getElementById('modalError').addEventListener('click', function (e) {
                if (e.target === this) cerrarModal();
            });

            <?php if (!empty($errorMensaje)): ?>
                mostrarModal('Error', <?= json_encode($errorMensaje) ?>);
            <?php endif; ?>

            function soloLetras(valor) {
                return /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(valor);
            }

            function telefonoValido(valor) {
                return /^\d{10}$/.test(valor);
            }

            function correoValido(valor) {
                if (valor === '') return true;
                return valor.includes('@');
            }

            function mostrarError(campo, mensaje) {
                const el = document.getElementById('error-' + campo);
                if (el) {
                    el.textContent = mensaje;
                    el.style.display = 'block';
                }
                const input = document.getElementById(campo);
                if (input) {
                    input.style.borderColor = 'var(--color-danger)';
                }
            }

            function limpiarError(campo) {
                const el = document.getElementById('error-' + campo);
                if (el) {
                    el.textContent = '';
                    el.style.display = 'none';
                }
                const input = document.getElementById(campo);
                if (input) {
                    input.style.borderColor = '';
                }
            }

            function limpiarTodosLosErrores() {
                ['nombre', 'apellidos', 'fecha_nacimiento', 'sexo',
                 'tutor_nombre', 'tutor_apellidos', 'tutor_telefono', 'tutor_correo', 'tutor_parentesco'
                ].forEach(limpiarError);
            }

            function validarFormularioTutor() {
                limpiarTodosLosErrores();
                let valido = true;
                const nombre = document.getElementById('tutor_nombre').value.trim();
                const apellidos = document.getElementById('tutor_apellidos').value.trim();
                const telefono = document.getElementById('tutor_telefono').value.trim();
                const correo = document.getElementById('tutor_correo').value.trim();
                const parentesco = document.getElementById('tutor_parentesco').value.trim();

                if (!nombre) {
                    mostrarError('tutor_nombre', 'El nombre es obligatorio.');
                    valido = false;
                } else if (!soloLetras(nombre)) {
                    mostrarError('tutor_nombre', 'Solo se permiten letras y espacios.');
                    valido = false;
                }

                if (!apellidos) {
                    mostrarError('tutor_apellidos', 'Los apellidos son obligatorios.');
                    valido = false;
                } else if (!soloLetras(apellidos)) {
                    mostrarError('tutor_apellidos', 'Solo se permiten letras y espacios.');
                    valido = false;
                }

                if (!telefono) {
                    mostrarError('tutor_telefono', 'El telefono es obligatorio.');
                    valido = false;
                } else if (!telefonoValido(telefono)) {
                    mostrarError('tutor_telefono', 'Debe contener exactamente 10 digitos.');
                    valido = false;
                }

                if (correo && !correoValido(correo)) {
                    mostrarError('tutor_correo', 'El correo debe contener un arroba (@).');
                    valido = false;
                }

                if (!parentesco) {
                    mostrarError('tutor_parentesco', 'El parentesco es obligatorio.');
                    valido = false;
                } else if (!soloLetras(parentesco)) {
                    mostrarError('tutor_parentesco', 'Solo se permiten letras y espacios.');
                    valido = false;
                }

                return valido;
            }

            function renderTutores() {
                const contenedor = document.getElementById('tutoresAgregados');
                contenedor.innerHTML = '';
                tutores.forEach((t, i) => {
                    const tag = document.createElement('div');
                    tag.className = 'tutor-tag';
                    tag.innerHTML = t.nombre + ' (' + t.parentesco + ')' +
                        '<button type="button" class="remove-tutor" data-index="' + i + '">&times;</button>';
                    contenedor.appendChild(tag);
                });
                document.getElementById('tutoresJson').value = JSON.stringify(tutores);
            }

            document.getElementById('tutoresAgregados').addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-tutor')) {
                    tutores.splice(parseInt(e.target.dataset.index), 1);
                    renderTutores();
                }
            });

            document.getElementById('btnNuevoTutor').addEventListener('click', function () {
                limpiarTodosLosErrores();
                document.getElementById('formTutorNuevo').style.display = 'block';
                ['tutor_nombre', 'tutor_apellidos', 'tutor_telefono', 'tutor_correo', 'tutor_parentesco', 'tutor_direccion'].forEach(id => {
                    document.getElementById(id).value = '';
                });
            });

            document.getElementById('btnCancelarTutor').addEventListener('click', function () {
                document.getElementById('formTutorNuevo').style.display = 'none';
                limpiarTodosLosErrores();
                ['tutor_nombre', 'tutor_apellidos', 'tutor_telefono', 'tutor_correo', 'tutor_parentesco', 'tutor_direccion'].forEach(id => {
                    document.getElementById(id).value = '';
                });
            });

            document.getElementById('btnGuardarTutor').addEventListener('click', function () {
                if (!validarFormularioTutor()) return;

                tutores.push({
                    id_tutor_existente: null,
                    nombre: document.getElementById('tutor_nombre').value.trim(),
                    apellidos: document.getElementById('tutor_apellidos').value.trim(),
                    telefono: document.getElementById('tutor_telefono').value.trim(),
                    correo: document.getElementById('tutor_correo').value.trim() || null,
                    direccion: document.getElementById('tutor_direccion').value.trim() || null,
                    parentesco: document.getElementById('tutor_parentesco').value.trim()
                });

                renderTutores();
                document.getElementById('btnCancelarTutor').click();
            });

            document.getElementById('btnBuscarTutor').addEventListener('click', function () {
                document.getElementById('modalBuscarTutor').classList.add('active');
                document.getElementById('inputBusquedaTutor').value = '';
                document.getElementById('resultadosBusquedaTutores').style.display = 'none';
                document.getElementById('resultadosBusquedaTutores').innerHTML = '';
                document.getElementById('mensajeBusquedaTutor').style.display = 'block';
                document.getElementById('inputBusquedaTutor').focus();
            });

            document.getElementById('cerrarModalTutor').addEventListener('click', function () {
                document.getElementById('modalBuscarTutor').classList.remove('active');
            });

            document.getElementById('modalBuscarTutor').addEventListener('click', function (e) {
                if (e.target === this) this.classList.remove('active');
            });

            let debounceTutor = null;
            document.getElementById('inputBusquedaTutor').addEventListener('input', function () {
                clearTimeout(debounceTutor);
                const termino = this.value.trim();
                const contenedor = document.getElementById('resultadosBusquedaTutores');
                const mensaje = document.getElementById('mensajeBusquedaTutor');

                if (termino.length < 2) {
                    contenedor.style.display = 'none';
                    contenedor.innerHTML = '';
                    mensaje.style.display = 'block';
                    mensaje.textContent = 'Escribe al menos 2 caracteres para buscar.';
                    return;
                }

                mensaje.style.display = 'block';
                mensaje.textContent = 'Buscando...';

                debounceTutor = setTimeout(function () {
                    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch('/Pacientes/buscar_tutores', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ termino: termino, csrf_token: csrfToken })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(tutores) {
                        if (tutores.length === 0) {
                            contenedor.style.display = 'none';
                            contenedor.innerHTML = '';
                            mensaje.style.display = 'block';
                            mensaje.textContent = 'No se encontraron tutores con ese termino.';
                            return;
                        }

                        mensaje.style.display = 'none';
                        let html = '';
                        tutores.forEach(function(t) {
                            html += '<div class="tutor-item">' +
                                '<div class="tutor-item-info">' +
                                    '<strong>' + t.nombre + ' ' + t.apellidos + '</strong>' +
                                    '<span>' + t.telefono + (t.correo ? ' - ' + t.correo : '') + '</span>' +
                                '</div>' +
                                '<button type="button" class="btn btn-primary btn-sm btnSeleccionarTutor" ' +
                                    'data-id="' + t.id_tutor + '" ' +
                                    'data-nombre="' + t.nombre + ' ' + t.apellidos + '">' +
                                    'Seleccionar' +
                                '</button>' +
                            '</div>';
                        });
                        contenedor.innerHTML = html;
                        contenedor.style.display = 'block';
                    })
                    .catch(function() {
                        contenedor.style.display = 'none';
                        mensaje.style.display = 'block';
                        mensaje.textContent = 'Error de conexion.';
                    });
                }, 300);
            });

            document.getElementById('resultadosBusquedaTutores').addEventListener('click', function (e) {
                const btn = e.target.closest('.btnSeleccionarTutor');
                if (!btn) return;

                const id = btn.getAttribute('data-id');
                const nombre = btn.getAttribute('data-nombre');
                const parentesco = prompt('Ingrese el parentesco del tutor:');
                if (parentesco && parentesco.trim()) {
                    if (!soloLetras(parentesco.trim())) {
                        mostrarModal('Parentesco invalido', 'El parentesco solo debe contener letras y espacios.');
                        return;
                    }
                    tutores.push({
                        id_tutor_existente: id,
                        nombre: nombre,
                        parentesco: parentesco.trim()
                    });
                    renderTutores();
                    document.getElementById('modalBuscarTutor').classList.remove('active');
                }
            });

            document.getElementById('formPaciente').addEventListener('submit', function (e) {
                limpiarTodosLosErrores();
                let valido = true;

                const nombre = document.getElementById('nombre').value.trim();
                const apellidos = document.getElementById('apellidos').value.trim();
                const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
                const sexo = document.getElementById('sexo').value;

                if (!nombre) {
                    mostrarError('nombre', 'El nombre es obligatorio.');
                    valido = false;
                } else if (!soloLetras(nombre)) {
                    mostrarError('nombre', 'Solo se permiten letras y espacios.');
                    valido = false;
                }

                if (!apellidos) {
                    mostrarError('apellidos', 'Los apellidos son obligatorios.');
                    valido = false;
                } else if (!soloLetras(apellidos)) {
                    mostrarError('apellidos', 'Solo se permiten letras y espacios.');
                    valido = false;
                }

                if (!fechaNacimiento) {
                    mostrarError('fecha_nacimiento', 'La fecha de nacimiento es obligatoria.');
                    valido = false;
                }

                if (!sexo) {
                    mostrarError('sexo', 'Seleccione el sexo del paciente.');
                    valido = false;
                }

                if (!valido) {
                    e.preventDefault();
                    mostrarModal('Campos invalidos', 'Revise los campos marcados en rojo.');
                    return;
                }

                if (tutores.length === 0) {
                    e.preventDefault();
                    mostrarModal('Tutores requeridos', 'Debe agregar al menos un tutor al paciente antes de registrar.');
                    return;
                }
            });

            ['nombre', 'apellidos'].forEach(function (campo) {
                const input = document.getElementById(campo);
                input.addEventListener('input', function () {
                    this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
                    limpiarError(campo);
                });
            });

            const telInput = document.getElementById('tutor_telefono');
            telInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 10);
                limpiarError('tutor_telefono');
            });

            ['tutor_nombre', 'tutor_apellidos', 'tutor_parentesco'].forEach(function (campo) {
                const input = document.getElementById(campo);
                input.addEventListener('input', function () {
                    this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
                    limpiarError(campo);
                });
            });

            document.getElementById('tutor_correo').addEventListener('input', function () {
                limpiarError('tutor_correo');
            });
        });
    </script>
</body>
</html>
