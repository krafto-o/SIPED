<?php

require_once __DIR__ . '/../funciones/auth.php';

requerirRol(['pediatra']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_sesion'])) {
    cerrarSesion();
    header('Location: /login');
    exit;
}

$db = conectar();
$usuario = usuarioAutenticado();

$hoy = date('Y-m-d');

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

$busquedaError = '';
$resultadosBusqueda = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_paciente'])) {
    $termino = trim($_POST['termino_busqueda'] ?? '');

    if (!empty($termino)) {
        $resultadosBusqueda = ejecutarConsulta(
            $db,
            "SELECT id_paciente, nombre, apellidos, fecha_nacimiento
             FROM paciente
             WHERE estado = 'activo'
             AND (nombre LIKE ? OR apellidos LIKE ?)
             ORDER BY nombre ASC
             LIMIT 10",
            ["%{$termino}%", "%{$termino}%"]
        );

        if (empty($resultadosBusqueda)) {
            $busquedaError = 'No se encontraron pacientes con ese nombre.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Dashboard</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="/Dashboard/pediatra" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <a href="/Pacientes/index" class="btn btn-outline btn-sm">Pacientes</a>
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?></span>
            <form action="/Dashboard/pediatra" method="POST" style="display:inline;">
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesión</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-lg font-semibold mb-lg">Panel del Pediatra</h1>

        <div class="quick-actions">
            <a href="/Agenda/index" class="btn btn-primary">Ver Agenda Completa</a>
            <a href="/Pagos/index" class="btn btn-secondary">Caja de Cobro</a>
            <a href="/Reportes/index" class="btn btn-outline">Estadisticas del Sistema</a>
        </div>

        <div class="card mb-lg">
            <div class="card-header">
                <h2>Citas de Hoy - <?= date('d/m/Y') ?></h2>
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
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citasHoy as $cita): ?>
                                    <tr>
                                        <td><?= date('H:i', strtotime($cita['fecha_hora'])) ?></td>
                                        <td><?= htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellidos']) ?></td>
                                        <td><?= htmlspecialchars($cita['tipo_cita'] === 'primera_vez' ? 'Primera vez' : 'Consecuente') ?></td>
                                        <td>
                                            <span class="badge badge-<?= htmlspecialchars($cita['estado']) ?>">
                                                <?= htmlspecialchars($cita['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($cita['estado'] !== 'cancelada' && $cita['estado'] !== 'realizada'): ?>
                                                <form action="/Consultas/iniciar" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">Atender</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted text-sm">No disponible</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Atención sin Cita</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="/Dashboard/pediatra">
                    <div class="search-bar">
                        <input
                            type="text"
                            name="termino_busqueda"
                            class="form-input"
                            placeholder="Buscar paciente por nombre..."
                            value="<?= htmlspecialchars($_POST['termino_busqueda'] ?? '') ?>"
                        >
                        <button type="submit" name="buscar_paciente" class="btn btn-primary">Buscar</button>
                    </div>
                </form>

                <?php if (!empty($busquedaError)): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($busquedaError) ?></div>
                <?php endif; ?>

                <?php if (!empty($resultadosBusqueda)): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Fecha de Nacimiento</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultadosBusqueda as $paciente): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($paciente['fecha_nacimiento'])) ?></td>
                                        <td>
                                            <form action="/Pacientes/perfil" method="POST" style="display:inline;">
                                                <input type="hidden" name="id_paciente" value="<?= (int) $paciente['id_paciente'] ?>">
                                                <button type="submit" class="btn btn-outline btn-sm">Ver Perfil</button>
                                            </form>
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
</body>
</html>
