<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/reportes.php';

requerirRol(['recepcionista', 'pediatra']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_sesion'])) {
    cerrarSesion();
    header('Location: /login');
    exit;
}

$db = conectar();
$usuario = usuarioAutenticado();

$ingresosDia = obtenerIngresosDia($db);
$ingresosSemana = obtenerIngresosSemana($db);
$ingresosMes = obtenerIngresosMes($db);
$pagosRecientes = obtenerPagosRecientes($db);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= generarTokenCSRF() ?>">
    <title>SIPED - Reportes</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="<?= $_SESSION['rol'] === 'pediatra' ? '/Dashboard/pediatra' : '/Agenda/index' ?>" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <a href="/Pagos/index" class="btn btn-outline btn-sm">Caja de Cobro</a>
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Reportes/index" method="POST" style="display:inline;">
                <?= campoCSRF() ?>
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Reportes y Estadisticas</h1>
        </div>

        <div class="grid grid-3 mb-lg">
            <div class="metric-card">
                <span class="metric-label">Ingresos del Dia</span>
                <span class="metric-value">$<?= number_format($ingresosDia, 2) ?></span>
                <span class="metric-sub"><?= date('d/m/Y') ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Ingresos de la Semana</span>
                <span class="metric-value">$<?= number_format($ingresosSemana, 2) ?></span>
                <span class="metric-sub">Semana actual</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Ingresos del Mes</span>
                <span class="metric-value">$<?= number_format($ingresosMes, 2) ?></span>
                <span class="metric-sub"><?= date('F Y') ?></span>
            </div>
        </div>

        <div class="export-actions">
            <form action="/Reportes/exportar" method="POST" style="display:inline;">
                <?= campoCSRF() ?>
                <input type="hidden" name="rango" value="dia">
                <button type="submit" class="btn btn-primary btn-sm">Exportar Corte del Dia</button>
            </form>
            <form action="/Reportes/exportar" method="POST" style="display:inline;">
                <?= campoCSRF() ?>
                <input type="hidden" name="rango" value="mes">
                <button type="submit" class="btn btn-primary btn-sm">Exportar Reporte Mensual</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Historial de Pagos Recientes</h2>
            </div>
            <div class="card-body">
                <?php if (empty($pagosRecientes)): ?>
                    <div class="empty-state">
                        <p>No hay pagos registrados aun.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Paciente</th>
                                    <th>Medico</th>
                                    <th>Forma de Pago</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagosRecientes as $pago): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></td>
                                        <td><?= htmlspecialchars($pago['paciente_nombre'] . ' ' . $pago['paciente_apellidos']) ?></td>
                                        <td><?= htmlspecialchars($pago['medico_nombre'] . ' ' . $pago['medico_apellidos']) ?></td>
                                        <td><?= ucfirst(htmlspecialchars($pago['forma_pago'])) ?></td>
                                        <td class="font-semibold">$<?= number_format((float) $pago['monto'], 2) ?></td>
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
