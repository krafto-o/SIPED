<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/pacientes.php';
require_once __DIR__ . '/../funciones/consultas.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_sesion'])) {
    cerrarSesion();
    header('Location: /login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_paciente'])) {
    header('Location: /Pacientes/index');
    exit;
}

$db = conectar();
$usuario = usuarioAutenticado();
$esPediatra = $_SESSION['rol'] === 'pediatra';

$idPaciente = (int) $_POST['id_paciente'];
$_SESSION['id_paciente_actual'] = $idPaciente;

$paciente = $esPediatra ? obtenerPacienteCompleto($db, $idPaciente) : obtenerPacienteConTutores($db, $idPaciente);

if (!$paciente) {
    header('Location: /Pacientes/index');
    exit;
}

$edad = calcularEdad($paciente['fecha_nacimiento']);
$citas = obtenerCitasPaciente($db, $idPaciente);
$historialConsultas = $esPediatra ? obtenerHistorialPaciente($db, $idPaciente) : [];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Perfil del Paciente</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="/Pacientes/index" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Pacientes/perfil" method="POST" style="display:inline;">
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Perfil del Paciente</h1>
            <div class="flex gap-sm">
                <form action="/Pacientes/editar" method="POST" style="display:inline;">
                    <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Editar</button>
                </form>
                <a href="/Pacientes/index" class="btn btn-outline btn-sm">Volver al listado</a>
            </div>
        </div>

        <div class="patient-banner">
            <div>
                <h2><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></h2>
                <div class="patient-info">
                    <span><?= $edad ?></span>
                    <span><?= htmlspecialchars(ucfirst($paciente['sexo'])) ?></span>
                    <?php if ($esPediatra && !empty($paciente['tipo_sangre'])): ?>
                        <span class="badge-sangre"><?= htmlspecialchars($paciente['tipo_sangre']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($esPediatra && !empty($paciente['alergias'])): ?>
                    <div class="alert-alergias">ALERGIAS: <?= htmlspecialchars($paciente['alergias']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tabs" id="tabsPaciente">
            <button class="tab-btn active" data-tab="contacto">Datos de Contacto</button>
            <button class="tab-btn" data-tab="citas">Citas</button>
            <?php if ($esPediatra): ?>
                <button class="tab-btn" data-tab="historial">Historial Clinico</button>
                <button class="tab-btn" data-tab="vacunas">Vacunas</button>
            <?php endif; ?>
        </div>

        <div class="tab-content active" id="tab-contacto">
            <div class="card">
                <div class="card-header">
                    <h2>Datos del Paciente</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Nombre</label>
                            <span><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Fecha de Nacimiento</label>
                            <span><?= date('d/m/Y', strtotime($paciente['fecha_nacimiento'])) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Sexo</label>
                            <span><?= htmlspecialchars(ucfirst($paciente['sexo'])) ?></span>
                        </div>
                        <?php if ($esPediatra): ?>
                            <div class="info-item">
                                <label>Tipo de Sangre</label>
                                <span><?= $paciente['tipo_sangre'] ? htmlspecialchars($paciente['tipo_sangre']) : 'No registrado' ?></span>
                            </div>
                            <div class="info-item">
                                <label>Alergias</label>
                                <span><?= $paciente['alergias'] ? htmlspecialchars($paciente['alergias']) : 'Ninguna registrada' ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mt-lg">
                <div class="card-header">
                    <h2>Tutores</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($paciente['tutores'])): ?>
                        <div class="empty-state">
                            <p>No hay tutores registrados para este paciente.</p>
                        </div>
                    <?php else: ?>
                        <div class="tutor-list">
                            <?php foreach ($paciente['tutores'] as $tutor): ?>
                                <div class="tutor-item">
                                    <div class="tutor-item-info">
                                        <strong><?= htmlspecialchars($tutor['nombre'] . ' ' . $tutor['apellidos']) ?></strong>
                                        <span><?= htmlspecialchars($tutor['parentesco']) ?> - <?= htmlspecialchars($tutor['telefono']) ?></span>
                                        <?php if (!empty($tutor['correo'])): ?>
                                            <span> - <?= htmlspecialchars($tutor['correo']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="tab-citas">
            <div class="card">
                <div class="card-header">
                    <h2>Historial de Citas</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($citas)): ?>
                        <div class="empty-state">
                            <p>No hay citas registradas para este paciente.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Tipo</th>
                                        <th>Motivo</th>
                                        <th>Medico</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($citas as $cita): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($cita['fecha_hora'])) ?></td>
                                            <td><?= date('H:i', strtotime($cita['fecha_hora'])) ?></td>
                                            <td><?= htmlspecialchars($cita['tipo_cita'] === 'primera_vez' ? 'Primera vez' : 'Consecuente') ?></td>
                                            <td><?= htmlspecialchars($cita['motivo'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($cita['medico_nombre'] . ' ' . $cita['medico_apellidos']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= htmlspecialchars($cita['estado']) ?>">
                                                    <?= htmlspecialchars($cita['estado']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($esPediatra): ?>
            <div class="tab-content" id="tab-historial">
                <div class="card">
                    <div class="card-header">
                        <h2>Historial Clinico</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($historialConsultas)): ?>
                            <div class="empty-state">
                                <p>No hay consultas registradas para este paciente.</p>
                            </div>
                        <?php else: ?>
                            <div class="historial-list">
                                <?php foreach ($historialConsultas as $consulta): ?>
                                    <div class="historial-item" data-consulta="<?= $consulta['id_consulta'] ?>" data-cita="<?= $consulta['id_cita'] ?>">
                                        <div class="historial-item-header">
                                            <span class="font-semibold"><?= date('d/m/Y H:i', strtotime($consulta['fecha_hora'])) ?></span>
                                            <div class="historial-actions">
                                                <span class="badge badge-realizada">Realizada</span>
                                                <button type="button" class="btn btn-outline btn-sm btn-descargar-receta" data-consulta="<?= $consulta['id_consulta'] ?>" data-cita="<?= $consulta['id_cita'] ?>">Descargar Receta</button>
                                            </div>
                                        </div>
                                        <?php if (!empty($consulta['motivo_consulta'])): ?>
                                            <p class="text-sm"><strong>Motivo:</strong> <?= htmlspecialchars($consulta['motivo_consulta']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($consulta['anamnesis'])): ?>
                                            <div class="historial-anamnesis">
                                                <p class="text-sm"><strong>Anamnesis:</strong></p>
                                                <p class="text-sm text-muted"><?= htmlspecialchars($consulta['anamnesis']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <p class="text-sm mt-sm"><strong>Diagnostico:</strong> <?= htmlspecialchars($consulta['diagnostico']) ?></p>
                                        <div class="text-sm text-muted mt-sm">
                                            <?php if ($consulta['peso']): ?>Peso: <?= $consulta['peso'] ?>kg | <?php endif; ?>
                                            <?php if ($consulta['talla']): ?>Talla: <?= $consulta['talla'] ?>cm | <?php endif; ?>
                                            <?php if ($consulta['perimetro_cefalico']): ?>P.C.: <?= $consulta['perimetro_cefalico'] ?>cm | <?php endif; ?>
                                            <?php if ($consulta['temperatura']): ?>Temp: <?= $consulta['temperatura'] ?>°C | <?php endif; ?>
                                            <?php if ($consulta['frec_cardiaca']): ?>F.C.: <?= $consulta['frec_cardiaca'] ?>lpm | <?php endif; ?>
                                            <?php if ($consulta['frec_respiratoria']): ?>F.R.: <?= $consulta['frec_respiratoria'] ?>rpm<?php endif; ?>
                                        </div>
                                        <?php if (!empty($consulta['notas_laboratorio'])): ?>
                                            <p class="text-sm mt-sm"><strong>Notas Laboratorio:</strong> <?= htmlspecialchars($consulta['notas_laboratorio']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="tab-vacunas">
                <div class="card">
                    <div class="card-header">
                        <h2>Cartilla de Vacunas</h2>
                    </div>
                    <div class="card-body">
                        <div class="empty-state">
                            <p>La cartilla digital de vacunas se implementara en la Fase 5.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.tab-btn');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));

                    this.classList.add('active');
                    document.getElementById('tab-' + this.dataset.tab).classList.add('active');
                });
            });

            document.querySelectorAll('.btn-descargar-receta').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var idConsulta = this.getAttribute('data-consulta');
                    var idCita = this.getAttribute('data-cita');
                    var btnOriginal = this;

                    btnOriginal.disabled = true;
                    btnOriginal.textContent = 'Generando...';

                    fetch('/Consultas/api_receta', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_consulta: idConsulta, id_cita: idCita })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(resp) {
                        if (resp.exito) {
                            var link = document.createElement('a');
                            link.href = resp.ruta;
                            link.download = 'receta_' + idCita + '.pdf';
                            link.target = '_blank';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            btnOriginal.textContent = 'Descargar Receta';
                        } else {
                            alert('Error: ' + resp.error);
                            btnOriginal.textContent = 'Reintentar';
                        }
                        btnOriginal.disabled = false;
                    })
                    .catch(function() {
                        alert('Error de conexion');
                        btnOriginal.textContent = 'Reintentar';
                        btnOriginal.disabled = false;
                    });
                });
            });
        });
    </script>
</body>
</html>
