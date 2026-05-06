<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/pagos.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_sesion'])) {
    cerrarSesion();
    header('Location: /login');
    exit;
}

$db = conectar();
$usuario = usuarioAutenticado();
$consultasPendientes = obtenerConsultasPendientes($db, $_SESSION['id_usuario'] ?? null, $_SESSION['rol']);

$mensajeExito = $_SESSION['success_pago'] ?? '';
$mensajeError = $_SESSION['error_pago'] ?? '';
unset($_SESSION['success_pago'], $_SESSION['error_pago']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Caja de Cobro</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="<?= $_SESSION['rol'] === 'pediatra' ? '/Dashboard/pediatra' : '/Agenda/index' ?>" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <a href="/Reportes/index" class="btn btn-outline btn-sm">Reportes</a>
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Pagos/index" method="POST" style="display:inline;">
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Caja de Cobro</h1>
        </div>

        <?php if ($mensajeExito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensajeExito) ?></div>
        <?php endif; ?>

        <?php if ($mensajeError): ?>
            <div class="alert alert-error"><?= htmlspecialchars($mensajeError) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Consultas Pendientes de Pago</h2>
            </div>
            <div class="card-body">
                <?php if (empty($consultasPendientes)): ?>
                    <div class="empty-state">
                        <p>No hay consultas pendientes de cobro.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Paciente</th>
                                    <th>Medico</th>
                                    <th>Motivo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($consultasPendientes as $consulta): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($consulta['fecha_hora'])) ?></td>
                                        <td><?= htmlspecialchars($consulta['paciente_nombre'] . ' ' . $consulta['paciente_apellidos']) ?></td>
                                        <td><?= htmlspecialchars($consulta['medico_nombre'] . ' ' . $consulta['medico_apellidos']) ?></td>
                                        <td><?= htmlspecialchars($consulta['motivo_consulta'] ?? 'Sin motivo') ?></td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-secondary btn-sm btn-cobrar"
                                                data-id="<?= (int) $consulta['id_consulta'] ?>"
                                                data-paciente="<?= htmlspecialchars($consulta['paciente_nombre'] . ' ' . $consulta['paciente_apellidos']) ?>"
                                                data-medico="<?= htmlspecialchars($consulta['medico_nombre'] . ' ' . $consulta['medico_apellidos']) ?>"
                                                data-fecha="<?= date('d/m/Y H:i', strtotime($consulta['fecha_hora'])) ?>"
                                                data-tipo="<?= $consulta['tipo_cita'] === 'primera_vez' ? 'Primera vez' : 'Consecuente' ?>"
                                            >
                                                Registrar Pago
                                            </button>
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

    <div class="modal-overlay" id="modalPago">
        <div class="modal">
            <div class="modal-header">
                <h3>Registrar Pago</h3>
                <button type="button" class="modal-close" id="btnCerrarModal">&times;</button>
            </div>
            <form action="/Pagos/procesar" method="POST">
                <div class="modal-body">
                    <div class="info-grid mb-md">
                        <div class="info-item">
                            <label>Paciente</label>
                            <span id="modalPaciente"></span>
                        </div>
                        <div class="info-item">
                            <label>Medico</label>
                            <span id="modalMedico"></span>
                        </div>
                        <div class="info-item">
                            <label>Fecha / Hora</label>
                            <span id="modalFecha"></span>
                        </div>
                        <div class="info-item">
                            <label>Tipo de Cita</label>
                            <span id="modalTipo"></span>
                        </div>
                    </div>

                    <input type="hidden" name="id_consulta" id="modalIdConsulta">

                    <div class="form-group">
                        <label for="monto" class="form-label">Monto a cobrar</label>
                        <input
                            type="number"
                            name="monto"
                            id="monto"
                            class="form-input"
                            step="0.01"
                            min="0.01"
                            placeholder="0.00"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="forma_pago" class="form-label">Forma de pago</label>
                        <select name="forma_pago" id="forma_pago" class="form-input" required>
                            <option value="">Seleccione...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="btnCancelarModal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary">Confirmar Pago</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('modalPago');
            var btnCerrar = document.getElementById('btnCerrarModal');
            var btnCancelar = document.getElementById('btnCancelarModal');

            document.querySelectorAll('.btn-cobrar').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('modalIdConsulta').value = this.dataset.id;
                    document.getElementById('modalPaciente').textContent = this.dataset.paciente;
                    document.getElementById('modalMedico').textContent = this.dataset.medico;
                    document.getElementById('modalFecha').textContent = this.dataset.fecha;
                    document.getElementById('modalTipo').textContent = this.dataset.tipo;
                    document.getElementById('monto').value = '';
                    document.getElementById('forma_pago').value = '';
                    modal.classList.add('active');
                });
            });

            function cerrarModal() {
                modal.classList.remove('active');
            }

            btnCerrar.addEventListener('click', cerrarModal);
            btnCancelar.addEventListener('click', cerrarModal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    cerrarModal();
                }
            });
        });
    </script>
</body>
</html>
