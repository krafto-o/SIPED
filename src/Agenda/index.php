<?php

require_once __DIR__ . '/../funciones/auth.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_sesion'])) {
    cerrarSesion();
    header('Location: /login');
    exit;
}

$db = conectar();
$usuario = usuarioAutenticado();

$hoy = date('Y-m-d');

if ($_SESSION['rol'] === 'recepcionista') {
    $citasHoy = ejecutarConsulta(
        $db,
        "SELECT c.id_cita, c.fecha_hora, c.tipo_cita, c.estado,
                p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
                u.nombre AS medico_nombre, u.apellidos AS medico_apellidos
         FROM citas c
         INNER JOIN paciente p ON c.id_paciente = p.id_paciente
         INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
         WHERE DATE(c.fecha_hora) = ?
         ORDER BY c.fecha_hora ASC",
        [$hoy]
    );
} else {
    $citasHoy = ejecutarConsulta(
        $db,
        "SELECT c.id_cita, c.fecha_hora, c.tipo_cita, c.estado,
                p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos
         FROM citas c
         INNER JOIN paciente p ON c.id_paciente = p.id_paciente
         WHERE c.id_usuario = ? AND DATE(c.fecha_hora) = ?
         ORDER BY c.fecha_hora ASC",
        [$_SESSION['id_usuario'], $hoy]
    );
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Agenda</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="<?= $_SESSION['rol'] === 'pediatra' ? '/Dashboard/pediatra' : '/Agenda/index' ?>" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <a href="/Pacientes/index" class="btn btn-outline btn-sm">Pacientes</a>
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Agenda/index" method="POST" style="display:inline;">
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesión</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-lg font-semibold mb-lg">Agenda - <?= date('d/m/Y') ?></h1>

        <div class="card">
            <div class="card-header">
                <h2>Citas del Día</h2>
            </div>
            <div class="card-body">
                <?php if (empty($citasHoy)): ?>
                    <div class="empty-state">
                        <p>No hay citas programadas para hoy.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Paciente</th>
                                    <?php if ($_SESSION['rol'] === 'recepcionista'): ?>
                                        <th>Médico</th>
                                    <?php endif; ?>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citasHoy as $cita): ?>
                                    <tr>
                                        <td><?= date('H:i', strtotime($cita['fecha_hora'])) ?></td>
                                        <td><?= htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellidos']) ?></td>
                                        <?php if ($_SESSION['rol'] === 'recepcionista'): ?>
                                            <td><?= htmlspecialchars($cita['medico_nombre'] . ' ' . $cita['medico_apellidos']) ?></td>
                                        <?php endif; ?>
                                        <td><?= htmlspecialchars($cita['tipo_cita'] === 'primera_vez' ? 'Primera vez' : 'Consecuente') ?></td>
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

        <div class="alert alert-info mt-lg">
            <strong>Vista preliminar:</strong> El calendario interactivo con FullCalendar.js se implementará en la Fase 3.
        </div>
    </div>
</body>
</html>
