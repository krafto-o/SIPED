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

$termino = $_POST['termino_busqueda'] ?? '';
$pacientes = listarPacientes($db, $termino);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= generarTokenCSRF() ?>">
    <title>SIPED - Pacientes</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="<?= $_SESSION['rol'] === 'pediatra' ? '/Dashboard/pediatra' : '/Agenda/index' ?>" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Pacientes/index" method="POST" style="display:inline;">
                <?= campoCSRF() ?>
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Pacientes</h1>
            <a href="/Pacientes/nuevo" class="btn btn-primary">Nuevo Paciente</a>
        </div>

        <form method="POST" action="/Pacientes/index">
            <?= campoCSRF() ?>
            <div class="search-bar">
                <input
                    type="text"
                    name="termino_busqueda"
                    class="form-input"
                    placeholder="Buscar por nombre o apellidos..."
                    value="<?= htmlspecialchars($termino) ?>"
                >
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if (!empty($termino)): ?>
                    <a href="/Pacientes/index" class="btn btn-outline">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="card">
            <div class="card-body">
                <?php if (empty($pacientes)): ?>
                    <div class="empty-state">
                        <p>No se encontraron pacientes<?= !empty($termino) ? ' con ese termino de busqueda' : '' ?>.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Edad</th>
                                    <th>Sexo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></td>
                                        <td><?= calcularEdad($paciente['fecha_nacimiento']) ?></td>
                                        <td><?= htmlspecialchars($paciente['sexo']) ?></td>
                                        <td>
                                            <div class="flex gap-sm">
                                                <form action="/Pacientes/perfil" method="POST" style="display:inline;">
                                                    <?= campoCSRF() ?>
                                                    <input type="hidden" name="id_paciente" value="<?= (int) $paciente['id_paciente'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm">Ver Perfil</button>
                                                </form>
                                                <form action="/Pacientes/editar" method="POST" style="display:inline;">
                                                    <?= campoCSRF() ?>
                                                    <input type="hidden" name="id_paciente" value="<?= (int) $paciente['id_paciente'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">Editar</button>
                                                </form>
                                            </div>
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
