<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/pacientes.php';

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
$paciente = obtenerPacienteCompleto($db, $idPaciente);

if (!$paciente) {
    header('Location: /Pacientes/index');
    exit;
}

$errorMensaje = '';
$exitoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_paciente'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');

    if (empty($nombre) || empty($apellidos) || empty($fechaNacimiento) || empty($sexo)) {
        $errorMensaje = 'Los campos nombre, apellidos, fecha de nacimiento y sexo son obligatorios.';
    } else {
        $datos = [
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'sexo' => $sexo
        ];

        if ($esPediatra) {
            $datos['tipo_sangre'] = !empty(trim($_POST['tipo_sangre'] ?? '')) ? trim($_POST['tipo_sangre']) : null;
            $datos['alergias'] = !empty(trim($_POST['alergias'] ?? '')) ? trim($_POST['alergias']) : null;
        }

        $exito = actualizarPaciente($db, $idPaciente, $datos);

        if ($exito) {
            $exitoMensaje = 'Datos del paciente actualizados exitosamente.';
            $paciente = obtenerPacienteCompleto($db, $idPaciente);
        } else {
            $errorMensaje = 'Error al actualizar los datos.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desvincular_tutor'])) {
    $idTutor = (int) ($_POST['id_tutor'] ?? 0);
    if ($idTutor > 0) {
        $exito = desvincularTutor($db, $idPaciente, $idTutor);
        if ($exito) {
            $exitoMensaje = 'Tutor desvinculado exitosamente.';
            $paciente = obtenerPacienteCompleto($db, $idPaciente);
        } else {
            $errorMensaje = 'Error al desvincular el tutor.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_tutor_existente'])) {
    $idTutor = (int) ($_POST['id_tutor'] ?? 0);
    $parentesco = trim($_POST['parentesco_nuevo'] ?? '');

    if ($idTutor > 0 && !empty($parentesco)) {
        $exito = vincularTutorAPaciente($db, $idPaciente, $idTutor, $parentesco);
        if ($exito) {
            $exitoMensaje = 'Tutor vinculado exitosamente.';
            $paciente = obtenerPacienteCompleto($db, $idPaciente);
        } else {
            $errorMensaje = 'Error al vincular el tutor. Puede que ya este asociado.';
        }
    } else {
        $errorMensaje = 'Seleccione un tutor e ingrese el parentesco.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dar_baja'])) {
    $confirmacion = $_POST['confirmacion_baja'] ?? '';
    if ($confirmacion === 'BAJA') {
        $exito = darBajaPaciente($db, $idPaciente);
        if ($exito) {
            header('Location: /Pacientes/index?mensaje=baja_exitosa');
            exit;
        } else {
            $errorMensaje = 'Error al dar de baja al paciente.';
        }
    } else {
        $errorMensaje = 'Debe escribir BAJA para confirmar.';
    }
}

$resultadosBusquedaTutores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_tutor_editar'])) {
    $terminoTutor = trim($_POST['termino_tutor_editar'] ?? '');
    if (!empty($terminoTutor)) {
        $resultadosBusquedaTutores = buscarTutores($db, $terminoTutor);
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Editar Paciente</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="/Pacientes/index" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Pacientes/editar" method="POST" style="display:inline;">
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Editar Paciente</h1>
            <div class="flex gap-sm">
                <form action="/Pacientes/perfil" method="POST" style="display:inline;">
                    <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
                    <button type="submit" class="btn btn-outline btn-sm">Ver Perfil</button>
                </form>
                <a href="/Pacientes/index" class="btn btn-outline btn-sm">Volver al listado</a>
            </div>
        </div>

        <?php if (!empty($errorMensaje)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMensaje) ?></div>
        <?php endif; ?>

        <?php if (!empty($exitoMensaje)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($exitoMensaje) ?></div>
        <?php endif; ?>

        <form method="POST" action="/Pacientes/editar">
            <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">

            <div class="card mb-lg">
                <div class="card-header">
                    <h2>Datos del Paciente</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" class="form-input"
                                   value="<?= htmlspecialchars($paciente['nombre']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="apellidos">Apellidos *</label>
                            <input type="text" id="apellidos" name="apellidos" class="form-input"
                                   value="<?= htmlspecialchars($paciente['apellidos']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="fecha_nacimiento">Fecha de Nacimiento *</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-input"
                                   value="<?= htmlspecialchars($paciente['fecha_nacimiento']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sexo">Sexo *</label>
                            <select id="sexo" name="sexo" class="form-input" required>
                                <option value="masculino" <?= $paciente['sexo'] === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                                <option value="femenino" <?= $paciente['sexo'] === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                            </select>
                        </div>
                    </div>

                    <?php if ($esPediatra): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="tipo_sangre">Tipo de Sangre</label>
                                <select id="tipo_sangre" name="tipo_sangre" class="form-input">
                                    <option value="">Seleccionar...</option>
                                    <?php
                                    $tipos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                    foreach ($tipos as $tipo) {
                                        $selected = $paciente['tipo_sangre'] === $tipo ? 'selected' : '';
                                        echo "<option value=\"$tipo\" $selected>$tipo</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="alergias">Alergias</label>
                                <input type="text" id="alergias" name="alergias" class="form-input"
                                       value="<?= htmlspecialchars($paciente['alergias'] ?? '') ?>"
                                       placeholder="Ej: Penicilina, Latex...">
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="actualizar_paciente" class="btn btn-primary">Actualizar Datos</button>
                </div>
            </div>
        </form>

        <div class="card mb-lg">
            <div class="card-header">
                <h2>Tutores</h2>
            </div>
            <div class="card-body">
                <?php if (empty($paciente['tutores'])): ?>
                    <div class="empty-state">
                        <p>No hay tutores vinculados a este paciente.</p>
                    </div>
                <?php else: ?>
                    <div class="tutor-list mb-lg">
                        <?php foreach ($paciente['tutores'] as $tutor): ?>
                            <div class="tutor-item">
                                <div class="tutor-item-info">
                                    <strong><?= htmlspecialchars($tutor['nombre'] . ' ' . $tutor['apellidos']) ?></strong>
                                    <span><?= htmlspecialchars($tutor['parentesco']) ?> - <?= htmlspecialchars($tutor['telefono']) ?></span>
                                </div>
                                <div class="tutor-actions">
                                    <form method="POST" action="/Pacientes/editar" style="display:inline;">
                                        <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
                                        <input type="hidden" name="id_tutor" value="<?= (int) $tutor['id_tutor'] ?>">
                                        <button type="submit" name="desvincular_tutor" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Desvincular este tutor?')">Desvincular</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3>Vincular Tutor Existente</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/Pacientes/editar">
                            <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
                            <div class="search-bar">
                                <input type="text" name="termino_tutor_editar" class="form-input" placeholder="Buscar por nombre o telefono...">
                                <button type="submit" name="buscar_tutor_editar" class="btn btn-secondary">Buscar</button>
                            </div>
                        </form>

                        <?php if (!empty($resultadosBusquedaTutores)): ?>
                            <form method="POST" action="/Pacientes/editar" class="mt-md">
                                <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
                                <div class="form-group">
                                    <label class="form-label">Seleccionar Tutor</label>
                                    <select name="id_tutor" class="form-input" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($resultadosBusquedaTutores as $tutor): ?>
                                            <option value="<?= (int) $tutor['id_tutor'] ?>">
                                                <?= htmlspecialchars($tutor['nombre'] . ' ' . $tutor['apellidos'] . ' - ' . $tutor['telefono']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="parentesco_nuevo">Parentesco *</label>
                                    <input type="text" id="parentesco_nuevo" name="parentesco_nuevo" class="form-input" required>
                                </div>
                                <button type="submit" name="agregar_tutor_existente" class="btn btn-primary">Vincular Tutor</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card danger-zone">
            <div class="card-header">
                <h2>Zona de Peligro</h2>
            </div>
            <div class="card-body">
                <h3>Dar de Baja al Paciente</h3>
                <p class="text-muted mb-md">Esta accion desactivara al paciente del sistema. No se eliminan los datos, pero no aparecera en los listados.</p>
                <form method="POST" action="/Pacientes/editar">
                    <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
                    <div class="form-group">
                        <label class="form-label" for="confirmacion_baja">Escriba <strong>BAJA</strong> para confirmar</label>
                        <input type="text" id="confirmacion_baja" name="confirmacion_baja" class="form-input" required>
                    </div>
                    <button type="submit" name="dar_baja" class="btn btn-danger" onclick="return confirm('Esta seguro de dar de baja a este paciente?')">Dar de Baja</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
