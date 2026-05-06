<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/consultas.php';
require_once __DIR__ . '/../funciones/pacientes.php';

verificarSesion();
requerirRol(['pediatra']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_cita'])) {
    header('Location: /Dashboard/pediatra');
    exit;
}

$db = conectar();
$usuario = usuarioAutenticado();
$idCita = (int) $_POST['id_cita'];
$idUsuario = (int) $_SESSION['id_usuario'];

$datosConsulta = obtenerDatosConsulta($db, $idCita, $idUsuario);

if (!$datosConsulta) {
    header('Location: /Dashboard/pediatra');
    exit;
}

$edad = calcularEdad($datosConsulta['fecha_nacimiento']);
$historial = obtenerHistorialPaciente($db, $datosConsulta['id_paciente']);
$borrador = obtenerBorrador($db, $idCita, $idUsuario);

$errores = [];
$mensajeExito = '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Consulta: <?= htmlspecialchars($datosConsulta['paciente_nombre'] . ' ' . $datosConsulta['paciente_apellidos']) ?></title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="/Dashboard/pediatra" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <span class="text-sm text-muted">Consulta activa</span>
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?></span>
            <form action="/Dashboard/pediatra" method="POST" style="display:inline;">
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container consulta-container">
        <div class="consulta-header">
            <div class="consulta-header-info">
                <h1><?= htmlspecialchars($datosConsulta['paciente_nombre'] . ' ' . $datosConsulta['paciente_apellidos']) ?></h1>
                <p class="text-muted"><?= htmlspecialchars($edad) ?> | <?= htmlspecialchars($datosConsulta['sexo']) ?></p>
            </div>
            <div class="consulta-header-meta">
                <?php if (!empty($datosConsulta['tipo_sangre'])): ?>
                    <span class="badge badge-sangre"><?= htmlspecialchars($datosConsulta['tipo_sangre']) ?></span>
                <?php endif; ?>
                <?php if (!empty($datosConsulta['alergias'])): ?>
                    <span class="badge badge-cancelada">ALERGIAS: <?= htmlspecialchars($datosConsulta['alergias']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <form id="formConsulta" class="consulta-form">
            <input type="hidden" name="id_cita" value="<?= $idCita ?>">

            <div class="card mb-md">
                <div class="card-header">
                    <h2>Datos Clinicos</h2>
                </div>
                <div class="card-body">
                    <div class="datos-clinicos-grid">
                        <div class="form-group">
                            <label class="form-label" for="peso">Peso (kg)</label>
                            <input type="number" step="0.01" id="peso" name="peso" class="form-input" placeholder="0.00" value="<?= htmlspecialchars($borrador['peso'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="talla">Talla (cm)</label>
                            <input type="number" step="0.01" id="talla" name="talla" class="form-input" placeholder="0.00" value="<?= htmlspecialchars($borrador['talla'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="perimetro_cefalico">Perimetro Cefalico (cm)</label>
                            <input type="number" step="0.01" id="perimetro_cefalico" name="perimetro_cefalico" class="form-input" placeholder="0.00" value="<?= htmlspecialchars($borrador['perimetro_cefalico'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="temperatura">Temperatura (°C)</label>
                            <input type="number" step="0.1" id="temperatura" name="temperatura" class="form-input" placeholder="36.5" value="<?= htmlspecialchars($borrador['temperatura'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="frec_cardiaca">Frec. Cardiaca (lpm)</label>
                            <input type="number" id="frec_cardiaca" name="frec_cardiaca" class="form-input" placeholder="0" value="<?= htmlspecialchars($borrador['frec_cardiaca'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="frec_respiratoria">Frec. Respiratoria (rpm)</label>
                            <input type="number" id="frec_respiratoria" name="frec_respiratoria" class="form-input" placeholder="0" value="<?= htmlspecialchars($borrador['frec_respiratoria'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-md">
                <div class="card-header">
                    <h2>Historial Previo</h2>
                    <button type="button" class="btn btn-outline btn-sm" id="toggleHistorial">+</button>
                </div>
                <div class="card-body" id="historialContent" style="display: none;">
                    <?php if (empty($historial)): ?>
                        <div class="empty-state">
                            <p>No hay consultas previas registradas.</p>
                        </div>
                    <?php else: ?>
                        <div class="historial-list">
                            <?php foreach ($historial as $consulta): ?>
                                <div class="historial-item">
                                    <div class="historial-item-header">
                                        <span class="font-semibold"><?= date('d/m/Y', strtotime($consulta['fecha_hora'])) ?></span>
                                        <span class="badge badge-confirmada"><?= htmlspecialchars($consulta['motivo_consulta'] ?? 'Sin motivo') ?></span>
                                    </div>
                                    <?php if (!empty($consulta['anamnesis'])): ?>
                                        <div class="historial-anamnesis">
                                            <p class="text-sm"><strong>Enfermedades previas / Anamnesis:</strong></p>
                                            <p class="text-sm text-muted"><?= htmlspecialchars($consulta['anamnesis']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <p class="text-sm mt-sm"><strong>Diagnostico:</strong> <?= htmlspecialchars($consulta['diagnostico']) ?></p>
                                    <div class="text-sm text-muted">
                                        <?php if ($consulta['peso']): ?>Peso: <?= $consulta['peso'] ?>kg | <?php endif; ?>
                                        <?php if ($consulta['talla']): ?>Talla: <?= $consulta['talla'] ?>cm | <?php endif; ?>
                                        <?php if ($consulta['temperatura']): ?>Temp: <?= $consulta['temperatura'] ?>°C<?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-md">
                <div class="card-header">
                    <h2>Consulta Actual</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="motivo_consulta">Motivo de Consulta</label>
                        <input type="text" id="motivo_consulta" name="motivo_consulta" class="form-input" placeholder="Motivo de la consulta..." value="<?= htmlspecialchars($borrador['motivo_consulta'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="anamnesis">Anamnesis</label>
                        <textarea id="anamnesis" name="anamnesis" class="form-input form-textarea" rows="6" placeholder="Narrativa de la consulta..."><?= htmlspecialchars($borrador['anamnesis'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-md">
                <div class="card-header">
                    <h2>Estudios de Laboratorio</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="notas_laboratorio">Notas de Laboratorio</label>
                        <textarea id="notas_laboratorio" name="notas_laboratorio" class="form-input form-textarea" rows="3" placeholder="Notas sobre estudios de laboratorio..."><?= htmlspecialchars($borrador['notas_laboratorio'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Archivos Adjuntos</label>
                        <div class="zona-subida" id="zonaSubida">
                            <p>Arrastra archivos aqui o <button type="button" class="btn btn-link" id="btnSeleccionar">selecciona archivos</button></p>
                            <input type="file" id="inputArchivos" accept=".pdf,.jpg,.jpeg,.png" multiple style="display: none;">
                            <p class="text-sm text-muted">PDF, JPG, PNG (max 5MB)</p>
                        </div>
                        <div id="listaArchivos" class="archivos-lista"></div>
                    </div>
                </div>
            </div>

            <div class="card mb-md">
                <div class="card-header">
                    <h2>Diagnostico</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="diagnostico">Diagnostico *</label>
                        <textarea id="diagnostico" name="diagnostico" class="form-input form-textarea" rows="4" placeholder="Diagnostico y pasos a seguir..." required><?= htmlspecialchars($borrador['diagnostico'] ?? '') ?></textarea>
                        <span class="field-error" id="errorDiagnostico"></span>
                    </div>
                </div>
            </div>

            <div class="card mb-md">
                <div class="card-header">
                    <h2>Tratamiento</h2>
                </div>
                <div class="card-body">
                    <div id="listaTratamientos" class="tratamientos-lista">
                        <?php if (!empty($borrador['tratamientos'])): ?>
                            <?php foreach ($borrador['tratamientos'] as $index => $trat): ?>
                                <div class="tratamiento-item">
                                    <div class="tratamiento-header">
                                        <span class="tratamiento-num">Medicamento #<?= $index + 1 ?></span>
                                        <button type="button" class="btn btn-danger btn-sm btn-eliminar-tratamiento">Eliminar</button>
                                    </div>
                                    <div class="tratamiento-grid">
                                        <div class="form-group">
                                            <label class="form-label">Medicamento</label>
                                            <input type="text" name="tratamientos[<?= $index ?>][medicamento]" class="form-input" value="<?= htmlspecialchars($trat['medicamento'] ?? '') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Presentacion</label>
                                            <input type="text" name="tratamientos[<?= $index ?>][presentacion]" class="form-input" value="<?= htmlspecialchars($trat['presentacion'] ?? '') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Dosis/Frecuencia</label>
                                            <input type="text" name="tratamientos[<?= $index ?>][dosis_frecuencia]" class="form-input" value="<?= htmlspecialchars($trat['dosis_frecuencia'] ?? '') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Funcion</label>
                                            <input type="text" name="tratamientos[<?= $index ?>][funcion]" class="form-input" value="<?= htmlspecialchars($trat['funcion'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" id="btnAgregarTratamiento">+ Agregar Medicamento</button>
                </div>
            </div>

            <div class="consulta-actions">
                <span class="text-sm text-muted" id="estadoAutoguardado"></span>
                <button type="button" class="btn btn-primary btn-lg" id="btnFinalizar">Finalizar Consulta y Generar Receta</button>
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="modalReceta" style="display: none;">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h2>Consulta Finalizada</h2>
                <button type="button" class="btn btn-outline btn-sm modal-cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success" id="mensajeExitoModal">Consulta finalizada exitosamente.</div>
                <div id="recetaInfo"></div>
            </div>
            <div class="modal-footer">
                <a href="/Dashboard/pediatra" class="btn btn-primary">Volver al Dashboard</a>
                <a href="#" class="btn btn-secondary" id="btnDescargarReceta" style="display: none;" target="_blank" rel="noopener noreferrer">Descargar Receta</a>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalBorrador" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h2>Borrador Encontrado</h2>
            </div>
            <div class="modal-body">
                <p>Se encontro un borrador de esta consulta. ¿Desea restaurarlo?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnRestaurarBorrador">Si, restaurar</button>
                <button type="button" class="btn btn-outline" id="btnIgnorarBorrador">No, empezar de nuevo</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const idCita = <?= $idCita ?>;
        const idPaciente = <?= $datosConsulta['id_paciente'] ?>;
        let tratamientoCount = <?= !empty($borrador['tratamientos']) ? count($borrador['tratamientos']) : 0 ?>;
        let archivosSubidos = [];

        const formConsulta = document.getElementById('formConsulta');
        const listaTratamientos = document.getElementById('listaTratamientos');
        const btnAgregarTratamiento = document.getElementById('btnAgregarTratamiento');
        const btnFinalizar = document.getElementById('btnFinalizar');
        const estadoAutoguardado = document.getElementById('estadoAutoguardado');
        const toggleHistorial = document.getElementById('toggleHistorial');
        const historialContent = document.getElementById('historialContent');
        const zonaSubida = document.getElementById('zonaSubida');
        const inputArchivos = document.getElementById('inputArchivos');
        const btnSeleccionar = document.getElementById('btnSeleccionar');
        const listaArchivos = document.getElementById('listaArchivos');
        const modalReceta = document.getElementById('modalReceta');
        const modalBorrador = document.getElementById('modalBorrador');
        const btnRestaurarBorrador = document.getElementById('btnRestaurarBorrador');
        const btnIgnorarBorrador = document.getElementById('btnIgnorarBorrador');
        const btnDescargarReceta = document.getElementById('btnDescargarReceta');
        const modalCerrar = document.querySelector('.modal-cerrar');

        <?php if ($borrador): ?>
        modalBorrador.style.display = 'flex';
        <?php endif; ?>

        btnRestaurarBorrador.addEventListener('click', function() {
            modalBorrador.style.display = 'none';
        });

        btnIgnorarBorrador.addEventListener('click', function() {
            modalBorrador.style.display = 'none';
            document.getElementById('peso').value = '';
            document.getElementById('talla').value = '';
            document.getElementById('perimetro_cefalico').value = '';
            document.getElementById('temperatura').value = '';
            document.getElementById('frec_cardiaca').value = '';
            document.getElementById('frec_respiratoria').value = '';
            document.getElementById('motivo_consulta').value = '';
            document.getElementById('anamnesis').value = '';
            document.getElementById('notas_laboratorio').value = '';
            document.getElementById('diagnostico').value = '';
            listaTratamientos.innerHTML = '';
            tratamientoCount = 0;
        });

        toggleHistorial.addEventListener('click', function() {
            if (historialContent.style.display === 'none') {
                historialContent.style.display = 'block';
                toggleHistorial.textContent = '-';
            } else {
                historialContent.style.display = 'none';
                toggleHistorial.textContent = '+';
            }
        });

        btnAgregarTratamiento.addEventListener('click', function() {
            tratamientoCount++;
            const bloque = document.createElement('div');
            bloque.className = 'tratamiento-item';
            bloque.innerHTML = `
                <div class="tratamiento-header">
                    <span class="tratamiento-num">Medicamento #${tratamientoCount}</span>
                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-tratamiento">Eliminar</button>
                </div>
                <div class="tratamiento-grid">
                    <div class="form-group">
                        <label class="form-label">Medicamento</label>
                        <input type="text" name="tratamientos[${tratamientoCount - 1}][medicamento]" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Presentacion</label>
                        <input type="text" name="tratamientos[${tratamientoCount - 1}][presentacion]" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dosis/Frecuencia</label>
                        <input type="text" name="tratamientos[${tratamientoCount - 1}][dosis_frecuencia]" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Funcion</label>
                        <input type="text" name="tratamientos[${tratamientoCount - 1}][funcion]" class="form-input">
                    </div>
                </div>
            `;
            listaTratamientos.appendChild(bloque);
        });

        listaTratamientos.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-eliminar-tratamiento')) {
                e.target.closest('.tratamiento-item').remove();
            }
        });

        function recolectarDatos() {
            const datos = {
                peso: document.getElementById('peso').value,
                talla: document.getElementById('talla').value,
                perimetro_cefalico: document.getElementById('perimetro_cefalico').value,
                temperatura: document.getElementById('temperatura').value,
                frec_cardiaca: document.getElementById('frec_cardiaca').value,
                frec_respiratoria: document.getElementById('frec_respiratoria').value,
                motivo_consulta: document.getElementById('motivo_consulta').value,
                anamnesis: document.getElementById('anamnesis').value,
                notas_laboratorio: document.getElementById('notas_laboratorio').value,
                diagnostico: document.getElementById('diagnostico').value,
                tratamientos: []
            };

            const items = listaTratamientos.querySelectorAll('.tratamiento-item');
            items.forEach(function(item) {
                const inputs = item.querySelectorAll('input');
                if (inputs.length >= 4) {
                    datos.tratamientos.push({
                        medicamento: inputs[0].value,
                        presentacion: inputs[1].value,
                        dosis_frecuencia: inputs[2].value,
                        funcion: inputs[3].value
                    });
                }
            });

            return datos;
        }

        let autoguardadoTimeout = null;

        function guardarBorrador() {
            const datos = recolectarDatos();
            estadoAutoguardado.textContent = 'Guardando...';

            fetch('/Consultas/api_borrador', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cita: idCita, datos: datos })
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.exito) {
                    estadoAutoguardado.textContent = 'Autoguardado: ' + new Date().toLocaleTimeString();
                } else {
                    estadoAutoguardado.textContent = 'Error al guardar';
                }
            })
            .catch(function() {
                estadoAutoguardado.textContent = 'Error de conexion';
            });
        }

        function programarAutoguardado() {
            if (autoguardadoTimeout) {
                clearTimeout(autoguardadoTimeout);
            }
            autoguardadoTimeout = setTimeout(guardarBorrador, 1000);
        }

        const camposAutoguardado = ['peso', 'talla', 'perimetro_cefalico', 'temperatura', 'frec_cardiaca', 'frec_respiratoria', 'motivo_consulta', 'anamnesis', 'notas_laboratorio', 'diagnostico'];
        camposAutoguardado.forEach(function(id) {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', programarAutoguardado);
                el.addEventListener('keyup', programarAutoguardado);
            }
        });

        setInterval(guardarBorrador, 30000);

        btnSeleccionar.addEventListener('click', function() {
            inputArchivos.click();
        });

        zonaSubida.addEventListener('dragover', function(e) {
            e.preventDefault();
            zonaSubida.classList.add('zona-subida-active');
        });

        zonaSubida.addEventListener('dragleave', function() {
            zonaSubida.classList.remove('zona-subida-active');
        });

        zonaSubida.addEventListener('drop', function(e) {
            e.preventDefault();
            zonaSubida.classList.remove('zona-subida-active');
            manejarArchivos(e.dataTransfer.files);
        });

        inputArchivos.addEventListener('change', function() {
            manejarArchivos(this.files);
        });

        function manejarArchivos(files) {
            Array.from(files).forEach(function(file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('El archivo ' + file.name + ' excede 5MB');
                    return;
                }

                subirArchivo(file);
            });
        }

        function subirArchivo(file) {
            const formData = new FormData();
            formData.append('archivo', file);
            formData.append('id_cita', idCita);
            formData.append('id_paciente', idPaciente);

            const itemArchivo = document.createElement('div');
            itemArchivo.className = 'archivo-item';
            itemArchivo.innerHTML = '<span>' + file.name + '</span><span class="text-muted">Subiendo...</span>';
            listaArchivos.appendChild(itemArchivo);

            fetch('/Consultas/api_subir_archivo', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.exito) {
                    itemArchivo.innerHTML = '<span>' + file.name + '</span><span class="badge badge-activo">Subido</span>';
                    archivosSubidos.push(resp);
                } else {
                    itemArchivo.innerHTML = '<span>' + file.name + '</span><span class="badge badge-cancelada">Error: ' + resp.error + '</span>';
                }
            })
            .catch(function() {
                itemArchivo.innerHTML = '<span>' + file.name + '</span><span class="badge badge-cancelada">Error</span>';
            });
        }

        btnFinalizar.addEventListener('click', function() {
            const diagnostico = document.getElementById('diagnostico').value.trim();
            if (!diagnostico) {
                document.getElementById('errorDiagnostico').textContent = 'El diagnostico es obligatorio';
                document.getElementById('diagnostico').focus();
                return;
            }
            document.getElementById('errorDiagnostico').textContent = '';

            btnFinalizar.disabled = true;
            btnFinalizar.textContent = 'Procesando...';

            const datos = recolectarDatos();

            fetch('/Consultas/api_finalizar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_cita: idCita,
                    consulta: datos,
                    tratamientos: datos.tratamientos,
                    archivos: archivosSubidos
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                btnFinalizar.disabled = false;
                btnFinalizar.textContent = 'Finalizar Consulta y Generar Receta';

                if (resp.exito) {
                    modalReceta.style.display = 'flex';
                    if (resp.receta_url) {
                        btnDescargarReceta.href = resp.receta_url;
                        btnDescargarReceta.style.display = 'inline-block';
                        btnDescargarReceta.onclick = function() {
                            window.open(resp.receta_url, '_blank');
                            return false;
                        };
                        document.getElementById('recetaInfo').innerHTML = '<p class="text-sm">La receta ha sido generada y esta lista para descargar.</p>';
                    }
                } else {
                    alert('Error: ' + resp.error);
                }
            })
            .catch(function() {
                btnFinalizar.disabled = false;
                btnFinalizar.textContent = 'Finalizar Consulta y Generar Receta';
                alert('Error de conexion al finalizar la consulta');
            });
        });

        if (modalCerrar) {
            modalCerrar.addEventListener('click', function() {
                modalReceta.style.display = 'none';
            });
        }

        modalReceta.addEventListener('click', function(e) {
            if (e.target === modalReceta) {
                modalReceta.style.display = 'none';
            }
        });
    })();
    </script>
</body>
</html>
