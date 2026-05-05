<?php

require_once __DIR__ . '/funciones/auth.php';

if (verificarSesion()) {
    if ($_SESSION['rol'] === 'pediatra') {
        header('Location: /Dashboard/pediatra');
    } else {
        header('Location: /Agenda/index');
    }
    exit;
}

$errorMensaje = '';
$errorTipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificacion = trim($_POST['identificacion'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identificacion) || empty($password)) {
        $errorMensaje = 'Todos los campos son obligatorios.';
        $errorTipo = 'error';
    } else {
        $db = conectar();
        $usuarios = obtenerDatos(
            $db,
            'usuarios',
            '(correo = ? OR telefono = ?) AND estado = ?',
            [$identificacion, $identificacion, 'activo']
        );

        if (empty($usuarios)) {
            $errorMensaje = 'Credenciales incorrectas o cuenta inactiva.';
            $errorTipo = 'error';
        } elseif (!password_verify($password, $usuarios[0]['password'])) {
            $errorMensaje = 'Credenciales incorrectas.';
            $errorTipo = 'error';
        } else {
            iniciarSesion($usuarios[0]);

            if ($usuarios[0]['rol'] === 'pediatra') {
                header('Location: /Dashboard/pediatra');
            } else {
                header('Location: /Agenda/index');
            }
            exit;
        }
    }
}

if (isset($_GET['error'])) {
    match ($_GET['error']) {
        'timeout' => [
            $errorMensaje => 'Tu sesión ha expirado por inactividad. Inicia sesión nuevamente.',
            $errorTipo => 'warning'
        ],
        'no_autenticado' => [
            $errorMensaje => 'Debes iniciar sesión para acceder.',
            $errorTipo => 'warning'
        ],
        'rol_no_autorizado' => [
            $errorMensaje => 'No tienes permisos para acceder a esta sección.',
            $errorTipo => 'error'
        ],
        default => []
    };
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPED - Iniciar Sesión</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="card">
                <div class="login-header">
                    <h1>SIPED</h1>
                    <p>Sistema Integral de Gestión Pediátrica</p>
                </div>

                <div class="card-body">
                    <?php if (!empty($errorMensaje)): ?>
                        <div class="alert alert-<?= htmlspecialchars($errorTipo) ?>" id="alertaError">
                            <?= htmlspecialchars($errorMensaje) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/login" autocomplete="off">
                        <div class="form-group">
                            <label class="form-label" for="identificacion">Correo o Teléfono</label>
                            <input
                                type="text"
                                id="identificacion"
                                name="identificacion"
                                class="form-input"
                                placeholder="ejemplo@correo.com o 5551234567"
                                value="<?= htmlspecialchars($_POST['identificacion'] ?? '') ?>"
                                required
                                autofocus
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Contraseña</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                placeholder="Tu contraseña"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Acceder
                        </button>
                    </form>
                </div>

                <div class="login-footer">
                    <a href="#" id="btnOlvidePassword">Olvidé mi contraseña</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalError">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitulo">Error</h3>
                <button class="modal-close" id="modalCerrar">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalMensaje"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="modalAceptar">Aceptar</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alerta = document.getElementById('alertaError');
            const modal = document.getElementById('modalError');
            const modalTitulo = document.getElementById('modalTitulo');
            const modalMensaje = document.getElementById('modalMensaje');
            const modalCerrar = document.getElementById('modalCerrar');
            const modalAceptar = document.getElementById('modalAceptar');
            const btnOlvide = document.getElementById('btnOlvidePassword');

            function mostrarModal(titulo, mensaje) {
                modalTitulo.textContent = titulo;
                modalMensaje.textContent = mensaje;
                modal.classList.add('active');
            }

            function cerrarModal() {
                modal.classList.remove('active');
            }

            if (alerta) {
                const titulo = alerta.classList.contains('alert-warning') ? 'Aviso' : 'Error';
                mostrarModal(titulo, alerta.textContent.trim());
            }

            modalCerrar.addEventListener('click', cerrarModal);
            modalAceptar.addEventListener('click', cerrarModal);

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    cerrarModal();
                }
            });

            btnOlvide.addEventListener('click', function (e) {
                e.preventDefault();
                mostrarModal(
                    'Recuperar contraseña',
                    'Contacte al administrador del sistema para restablecer su contraseña.'
                );
            });
        });
    </script>
</body>
</html>
