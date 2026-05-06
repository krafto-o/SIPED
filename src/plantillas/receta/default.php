<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #1e293b; font-size: 12px; }
        .membrete { text-align: center; border-bottom: 2px solid #0ea5e9; padding-bottom: 15px; margin-bottom: 20px; }
        .membrete h1 { color: #0ea5e9; margin: 0; font-size: 20px; }
        .membrete p { margin: 3px 0; color: #64748b; }
        .info-section { margin-bottom: 15px; }
        .info-section h3 { color: #0ea5e9; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 8px; font-size: 14px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
        .info-item { padding: 3px 0; }
        .info-label { font-weight: bold; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #0ea5e9; color: white; padding: 8px; text-align: left; font-size: 11px; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        .diagnostico { background: #f8fafc; padding: 10px; border-left: 3px solid #0ea5e9; margin: 10px 0; }
        .firma { margin-top: 60px; text-align: center; }
        .firma-line { border-top: 1px solid #1e293b; width: 250px; margin: 0 auto 5px; }
        .signos-vitales { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 10px 0; }
        .signo-item { background: #f8fafc; padding: 8px; text-align: center; border-radius: 4px; }
        .signo-valor { font-size: 16px; font-weight: bold; color: #0ea5e9; }
        .signo-label { font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    <div class="membrete">
        <h1>SIPED - Sistema Pediatrico</h1>
        <p>Receta Medica</p>
    </div>

    <div class="info-section">
        <h3>Datos del Medico</h3>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Doctor:</span> <?= $medico_nombre_completo ?></div>
            <div class="info-item"><span class="info-label">Fecha:</span> <?= $fecha_consulta ?></div>
            <div class="info-item"><span class="info-label">Correo:</span> <?= $medico_correo ?></div>
            <div class="info-item"><span class="info-label">Telefono:</span> <?= $medico_telefono ?></div>
        </div>
    </div>

    <div class="info-section">
        <h3>Datos del Paciente</h3>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Nombre:</span> <?= $paciente_nombre_completo ?></div>
            <div class="info-item"><span class="info-label">Edad:</span> <?= $paciente_edad ?></div>
            <div class="info-item"><span class="info-label">Sexo:</span> <?= $paciente_sexo ?></div>
            <div class="info-item"><span class="info-label">Fecha de Nacimiento:</span> <?= $paciente_fecha_nacimiento ?></div>
        </div>
    </div>

    <div class="info-section">
        <h3>Signos Vitales</h3>
        <div class="signos-vitales">
            <?php foreach ($signos_vitales as $sv): ?>
            <div class="signo-item">
                <div class="signo-valor"><?= $sv['valor'] ?></div>
                <div class="signo-label"><?= $sv['etiqueta'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="info-section">
        <h3>Motivo de Consulta</h3>
        <p><?= $motivo_consulta ?></p>
    </div>

    <div class="info-section">
        <h3>Diagnostico</h3>
        <div class="diagnostico"><?= $diagnostico_html ?></div>
    </div>

    <?php if (!empty($tratamientos)): ?>
    <div class="info-section">
        <h3>Tratamiento</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicamento</th>
                    <th>Presentacion</th>
                    <th>Dosis/Frecuencia</th>
                    <th>Funcion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tratamientos as $t): ?>
                <tr>
                    <td><?= $t['numero'] ?></td>
                    <td><?= $t['medicamento'] ?></td>
                    <td><?= $t['presentacion'] ?></td>
                    <td><?= $t['dosis_frecuencia'] ?></td>
                    <td><?= $t['funcion'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($notas_laboratorio_html): ?>
    <div class="info-section">
        <h3>Notas de Laboratorio</h3>
        <p><?= $notas_laboratorio_html ?></p>
    </div>
    <?php endif; ?>

    <div class="firma">
        <div class="firma-line"></div>
        <p><?= $medico_nombre_completo ?></p>
        <p style="color: #64748b;">Cedula Profesional</p>
    </div>
</body>
</html>
