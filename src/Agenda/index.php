<?php

require_once __DIR__ . '/../funciones/auth.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_sesion'])) {
    cerrarSesion();
    header('Location: /login');
    exit;
}

$usuario = usuarioAutenticado();
$rol = $_SESSION['rol'];

$mensajeExito = $_SESSION['success_cita'] ?? null;
$mensajeError = $_SESSION['error_cita'] ?? null;
unset($_SESSION['success_cita'], $_SESSION['error_cita']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Agenda</title>
    <link rel="stylesheet" href="/css/estilos.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
</head>
<body>
    <nav class="navbar">
        <a href="<?= $rol === 'pediatra' ? '/Dashboard/pediatra' : '/Agenda/index' ?>" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <a href="/Pacientes/index" class="btn btn-outline btn-sm">Pacientes</a>
            <a href="/Agenda/crear" class="btn btn-primary btn-sm">Nueva Cita</a>
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Agenda/index" method="POST" style="display:inline;">
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <?php if ($mensajeExito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensajeExito) ?></div>
        <?php endif; ?>

        <?php if ($mensajeError): ?>
            <div class="alert alert-error"><?= htmlspecialchars($mensajeError) ?></div>
        <?php endif; ?>

        <div class="card">
            <div id="calendar"></div>
        </div>
    </div>

    <div class="modal-overlay" id="modalDetalle">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>Detalle de Cita</h3>
                <button class="modal-close" id="cerrarModal">&times;</button>
            </div>
            <div class="modal-body" id="modalContenido">
                <p class="text-muted">Cargando...</p>
            </div>
            <div class="modal-footer" id="modalAcciones" style="display: none;">
                <form action="/Agenda/gestionar_cita" method="POST" style="display: inline;">
                    <input type="hidden" name="id_cita" id="modalIdCita">
                    <input type="hidden" name="accion" id="modalAccion">
                    <button type="submit" class="btn btn-secondary btn-sm" id="btnConfirmar">Confirmar Cita</button>
                </form>
                <form action="/Agenda/gestionar_cita" method="POST" style="display: inline;">
                    <input type="hidden" name="id_cita" id="modalIdCitaCancelar">
                    <input type="hidden" name="accion" value="cancelar">
                    <button type="submit" class="btn btn-danger btn-sm">Cancelar Cita</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var modal = document.getElementById('modalDetalle');
        var modalContenido = document.getElementById('modalContenido');
        var modalAcciones = document.getElementById('modalAcciones');
        var cerrarModal = document.getElementById('cerrarModal');

        var savedView = localStorage.getItem('siped_calendar_view') || 'timeGridWeek';
        var savedDate = localStorage.getItem('siped_calendar_date') || undefined;

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: savedView,
            initialDate: savedDate,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listYear'
            },
            locale: 'es',
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            allDaySlot: false,
            editable: false,
            events: {
                url: '/Agenda/api_citas',
                failure: function() {
                    alert('Error al cargar las citas');
                }
            },
            datesSet: function(dateInfo) {
                localStorage.setItem('siped_calendar_view', dateInfo.view.type);
                localStorage.setItem('siped_calendar_date', dateInfo.view.currentStart.toISOString().split('T')[0]);
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault();

                var idCita = info.event.id;

                fetch('/Agenda/api_detalle_cita', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id_cita=' + encodeURIComponent(idCita)
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.error) {
                        modalContenido.innerHTML = '<p class="alert-error">' + data.error + '</p>';
                        modalAcciones.style.display = 'none';
                        return;
                    }

                    var fecha = new Date(data.fecha_hora);
                    var fechaFormateada = fecha.toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    var horaFormateada = fecha.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });

                    var tipoCitaTexto = data.tipo_cita === 'primera_vez' ? 'Primera vez' : 'Consecuente';
                    var estadoClase = 'badge-' + data.estado;

                    var html = '<div class="cita-detalle">';
                    html += '<div class="cita-detalle-header">';
                    html += '<h4>' + data.paciente_nombre + ' ' + data.paciente_apellidos + '</h4>';
                    html += '<span class="badge ' + estadoClase + '">' + data.estado + '</span>';
                    html += '</div>';

                    html += '<div class="info-grid mt-md">';
                    html += '<div class="info-item"><label>Fecha</label><span>' + fechaFormateada + '</span></div>';
                    html += '<div class="info-item"><label>Hora</label><span>' + horaFormateada + '</span></div>';
                    html += '<div class="info-item"><label>Tipo</label><span>' + tipoCitaTexto + '</span></div>';
                    html += '<div class="info-item"><label>Medico</label><span>' + data.medico_nombre + ' ' + data.medico_apellidos + '</span></div>';
                    if (data.motivo) {
                        html += '<div class="info-item"><label>Motivo</label><span>' + data.motivo + '</span></div>';
                    }
                    html += '</div>';

                    if (data.alergias) {
                        html += '<div class="alert-alergias mt-md">ALERGIA: ' + data.alergias + '</div>';
                    }

                    if (data.tutores && data.tutores.length > 0) {
                        html += '<h4 class="mt-lg mb-md">Tutores</h4>';
                        html += '<div class="tutor-list">';
                        data.tutores.forEach(function(tutor) {
                            html += '<div class="tutor-item">';
                            html += '<div class="tutor-item-info">';
                            html += '<strong>' + tutor.nombre + ' ' + tutor.apellidos + ' (' + tutor.parentesco + ')</strong>';
                            html += '<span>' + tutor.telefono + (tutor.correo ? ' | ' + tutor.correo : '') + '</span>';
                            html += '</div></div>';
                        });
                        html += '</div>';
                    }

                    html += '</div>';

                    modalContenido.innerHTML = html;

                    <?php if ($rol === 'recepcionista'): ?>
                    if (data.estado === 'pendiente' || data.estado === 'confirmada') {
                        modalAcciones.style.display = 'flex';
                        document.getElementById('modalIdCita').value = idCita;
                        document.getElementById('modalIdCitaCancelar').value = idCita;
                        document.getElementById('modalAccion').value = 'confirmar';
                        if (data.estado === 'confirmada') {
                            document.getElementById('btnConfirmar').style.display = 'none';
                        } else {
                            document.getElementById('btnConfirmar').style.display = 'inline-flex';
                        }
                    } else {
                        modalAcciones.style.display = 'none';
                    }
                    <?php else: ?>
                    modalAcciones.style.display = 'none';
                    <?php endif; ?>

                    modal.classList.add('active');
                })
                .catch(function() {
                    modalContenido.innerHTML = '<p class="alert-error">Error al cargar el detalle</p>';
                    modalAcciones.style.display = 'none';
                    modal.classList.add('active');
                });
            }
        });

        calendar.render();

        cerrarModal.addEventListener('click', function() {
            modal.classList.remove('active');
        });

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>
