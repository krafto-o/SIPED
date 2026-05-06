<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0ea5e9;
        }

        .header h1 {
            font-size: 18px;
            color: #0ea5e9;
            margin: 0 0 5px;
        }

        .header p {
            margin: 2px 0;
            color: #64748b;
            font-size: 9px;
        }

        .titulo-reporte {
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 5px;
        }

        .rango-fechas {
            text-align: center;
            color: #64748b;
            font-size: 9px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #f8fafc;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 8px 6px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 8px 6px;
            font-size: 9px;
            border-bottom: 1px solid #e2e8f0;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .monto {
            text-align: right;
            font-weight: 600;
        }

        .total-row {
            background-color: #f8fafc;
            font-weight: 700;
        }

        .total-row td {
            padding: 10px 6px;
            font-size: 11px;
            border-top: 2px solid #1e293b;
        }

        .firma-linea {
            margin-top: 50px;
            text-align: center;
        }

        .firma-linea .linea {
            width: 200px;
            margin: 0 auto 5px;
            border-top: 1px solid #1e293b;
        }

        .firma-linea span {
            font-size: 8px;
            color: #64748b;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIPED</h1>
        <p>Sistema Integral de Gestion para Consultorio Pediatrico</p>
    </div>

    <div class="titulo-reporte"><?= htmlspecialchars($titulo) ?></div>
    <div class="rango-fechas"><?= htmlspecialchars($rango_fechas) ?> | Generado: <?= htmlspecialchars($fecha_generacion) ?></div>

    <?php if (empty($pagos)): ?>
        <p style="text-align: center; color: #64748b; margin: 30px 0;">No hay pagos registrados en este periodo.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Paciente</th>
                    <th>Medico</th>
                    <th>Forma Pago</th>
                    <th class="monto">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $index => $pago): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($pago['fecha']) ?></td>
                        <td><?= htmlspecialchars($pago['paciente']) ?></td>
                        <td><?= htmlspecialchars($pago['medico']) ?></td>
                        <td><?= htmlspecialchars($pago['forma_pago']) ?></td>
                        <td class="monto"><?= htmlspecialchars($pago['monto']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5" style="text-align: right;">TOTAL:</td>
                    <td class="monto"><?= htmlspecialchars($total_general) ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="firma-linea">
        <div class="linea"></div>
        <span><?= htmlspecialchars($linea_firma) ?></span>
    </div>

    <div class="footer">
        SIPED - Documento generado automaticamente
    </div>
</body>
</html>
