<?php

require_once __DIR__ . '/../funciones/auth.php';
require_once __DIR__ . '/../funciones/vacunas.php';

requerirRol(['pediatra']);

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
$idPaciente = (int) $_POST['id_paciente'];

$paciente = obtenerPacienteCompleto($db, $idPaciente);

if (!$paciente) {
    header('Location: /Pacientes/index');
    exit;
}

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_vacuna'])) {
    $idVacuna = isset($_POST['id_vacuna']) ? (int) $_POST['id_vacuna'] : 0;
    $fechaAplicacion = $_POST['fecha_aplicacion'] ?? '';
    $aplicadaExterna = isset($_POST['aplicada_externamente']);
    $lote = trim($_POST['lote'] ?? '');

    if ($idVacuna <= 0) {
        $errores[] = 'Debes seleccionar un tipo de vacuna.';
    }

    if (empty($fechaAplicacion)) {
        $errores[] = 'La fecha de aplicacion es requerida.';
    }

    if (!$aplicadaExterna && empty($lote)) {
        $errores[] = 'El numero de lote es requerido para vacunas internas.';
    }

    if (empty($errores)) {
        if (verificarVacunaDuplicada($db, $idPaciente, $idVacuna, $fechaAplicacion)) {
            $errores[] = 'Ya existe un registro de esta vacuna en la fecha seleccionada.';
        } else {
            $resultado = registrarVacuna($db, $idPaciente, [
                'id_vacuna' => $idVacuna,
                'fecha_aplicacion' => $fechaAplicacion,
                'aplicada_externamente' => $aplicadaExterna,
                'lote' => $lote,
            ]);

            if ($resultado) {
                $exito = true;
            } else {
                $errores[] = 'Ocurrio un error al registrar la vacuna.';
            }
        }
    }
}

$vacunasCatalogo = obtenerVacunasCatalogo($db);
$edad = calcularEdad($paciente['fecha_nacimiento']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Registrar Vacuna</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <nav class="navbar">
        <a href="/Pacientes/index" class="navbar-brand">SIPED</a>
        <div class="navbar-user">
            <span><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?> - <?= ucfirst($usuario['rol']) ?></span>
            <form action="/Vacunas/aplicar" method="POST" style="display:inline;">
                <input type="hidden" name="cerrar_sesion" value="1">
                <button type="submit" class="btn btn-outline btn-sm">Cerrar sesion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Registrar Vacuna</h1>
            <a href="/Pacientes/index" class="btn btn-outline btn-sm">Volver al listado</a>
        </div>

        <div class="patient-banner">
            <div>
                <h2><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></h2>
                <div class="patient-info">
                    <span><?= $edad ?></span>
                    <span><?= htmlspecialchars(ucfirst($paciente['sexo'])) ?></span>
                </div>
            </div>
        </div>

        <?php if ($exito): ?>
            <div class="alert alert-success">
                Vacuna registrada correctamente.
                <form action="/Pacientes/perfil" method="POST" style="display:inline; margin-left: var(--spacing-md);">
                    <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
                    <button type="submit" class="btn btn-outline btn-sm">Volver al Perfil</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: var(--spacing-md);">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Nuevo Registro de Vacuna</h2>
            </div>
            <div class="card-body">
                <form action="/Vacunas/aplicar" method="POST" id="formVacuna">
                    <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
                    <input type="hidden" name="registrar_vacuna" value="1">

                    <div class="vacuna-form-grid">
                        <div class="form-group">
                            <label for="id_vacuna">Tipo de Vacuna *</label>
                            <select name="id_vacuna" id="id_vacuna" required>
                                <option value="">Selecciona una vacuna</option>
                                <?php foreach ($vacunasCatalogo as $vacuna): ?>
                                    <option value="<?= $vacuna['id_vacuna'] ?>">
                                        <?= htmlspecialchars($vacuna['nombre']) ?> - <?= htmlspecialchars($vacuna['esquema_edad']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fecha_aplicacion">Fecha de Aplicacion *</label>
                            <input type="date" name="fecha_aplicacion" id="fecha_aplicacion"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: var(--spacing-md);">
                        <label class="checkbox-label">
                            <input type="checkbox" name="aplicada_externamente" id="aplicada_externamente">
                            Aplicada en otra institucion o medico
                        </label>
                    </div>

                    <div class="form-group" style="margin-top: var(--spacing-md);">
                        <label for="lote">Numero de Lote <span id="lote-requerido">*</span></label>
                        <input type="text" name="lote" id="lote" placeholder="Ej: LOTE-2024-001" required>
                    </div>

                    <div class="consulta-actions">
                        <button type="submit" class="btn btn-primary btn-lg">Registrar Vacuna</button>
                    </div>
                </form>
                <form action="/Pacientes/perfil" method="POST" style="margin-top: var(--spacing-md);">
                    <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
                    <button type="submit" class="btn btn-outline">Cancelar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var checkbox = document.getElementById('aplicada_externamente');
            var loteInput = document.getElementById('lote');
            var loteRequerido = document.getElementById('lote-requerido');

            checkbox.addEventListener('change', function () {
                if (this.checked) {
                    loteInput.removeAttribute('required');
                    loteInput.disabled = true;
                    loteInput.value = '';
                    loteRequerido.style.display = 'none';
                } else {
                    loteInput.setAttribute('required', 'required');
                    loteInput.disabled = false;
                    loteRequerido.style.display = 'inline';
                }
            });
        });
    </script>
</body>
</html>
