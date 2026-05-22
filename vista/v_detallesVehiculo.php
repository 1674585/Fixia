<?php
function dv_fmtEur($n) {
    if ($n === null || $n === '') return '—';
    return number_format((float)$n, 2, ',', '.') . ' €';
}
function dv_fmtMin($min) {
    if ($min === null || $min === '' || (int)$min === 0) return '—';
    $h = intdiv((int)$min, 60); $m = (int)$min % 60;
    return $h > 0 ? "{$h}h {$m}min" : "{$m}min";
}
function dv_fmtFecha($d) {
    return $d ? date('d/m/Y H:i', strtotime($d)) : '—';
}
function dv_etiquetaEstado($estado) {
    $map = [
        'recibido'       => 'Recibido en taller',
        'diagnosticando' => 'En diagnóstico',
        'presupuestado'  => 'Presupuesto aprobado',
        'en_reparacion'  => 'En reparación',
        'listo'          => 'Reparación finalizada',
        'facturado'      => 'Facturado',
    ];
    return $map[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
}
?>

<div class="contenedor-detalles-vehiculo">

    <!-- INFORMACIÓN DEL VEHÍCULO -->
    <div class="card-vehiculo">
        <h1>Detalles del Vehículo</h1>

        <div class="info-vehiculo">
            <div class="fila-info">
                <label>Matrícula:</label>
                <span><?= htmlspecialchars($vehiculo['matricula']) ?></span>
            </div>
            <div class="fila-info">
                <label>Marca:</label>
                <span><?= htmlspecialchars($vehiculo['marca']) ?></span>
            </div>
            <div class="fila-info">
                <label>Modelo:</label>
                <span><?= htmlspecialchars($vehiculo['modelo']) ?></span>
            </div>
            <div class="fila-info">
                <label>Año:</label>
                <span><?= $vehiculo['anio'] ?: 'N/A' ?></span>
            </div>
            <div class="fila-info">
                <label>Último Kilometraje:</label>
                <span><?= number_format($vehiculo['ultimo_kilometraje'], 0, ',', '.') ?> km</span>
            </div>
        </div>

        <div class="botones-accion">
            <a href="index.php?action=misVehiculos" class="boton boton-secundario">
                Volver a Mis Vehículos
            </a>
        </div>
    </div>

    <!-- ÓRDENES DE TRABAJO -->
    <div class="card-ordenes">
        <h2>Histórico de Órdenes de Trabajo</h2>

        <?php if (empty($ordenes)): ?>
            <div class="alerta-info">
                <p>No hay órdenes de trabajo registradas para este vehículo.</p>
            </div>
        <?php else: ?>
            <div class="lista-ordenes">
                <?php foreach ($ordenes as $orden): ?>
                    <?php
                        $tareas       = $tareasOrdenes[$orden['id']] ?? [];
                        $facturada    = $orden['estado'] === 'facturado';
                        $tiene_real   = (int)$orden['tiempo_real_minutos'] > 0 || (float)$orden['coste_total_real'] > 0;
                        $dif          = $orden['diferencia'];
                        $dif_clase    = $dif === null ? '' : ($dif > 0 ? 'dv-dif-alza' : ($dif < 0 ? 'dv-dif-baja' : ''));
                        $dif_signo    = $dif !== null && $dif > 0 ? '+' : '';
                    ?>
                    <div class="card-orden">
                        <div class="encabezado-orden">
                            <div class="info-orden-principal">
                                <h3>Orden #<?= (int)$orden['id'] ?></h3>
                                <span class="estado estado-<?= strtolower($orden['estado']) ?>">
                                    <?= dv_etiquetaEstado($orden['estado']) ?>
                                </span>
                            </div>
                            <div class="fecha-orden">
                                Apertura: <?= dv_fmtFecha($orden['fecha_creacion']) ?>
                                <?php if ($facturada && $orden['facturado_en']): ?>
                                    <br><small>Facturada: <?= dv_fmtFecha($orden['facturado_en']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="contenido-orden">
                            <div class="fila-orden">
                                <label>Atendida por:</label>
                                <span><?= htmlspecialchars($orden['creado_por']) ?></span>
                            </div>

                            <?php if ($orden['sintomas_cliente']): ?>
                                <div class="fila-orden">
                                    <label>Síntomas reportados:</label>
                                    <span><?= nl2br(htmlspecialchars($orden['sintomas_cliente'])) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($orden['diagnostico_tecnico']): ?>
                                <div class="fila-orden">
                                    <label>Diagnóstico técnico:</label>
                                    <span><?= nl2br(htmlspecialchars($orden['diagnostico_tecnico'])) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- RESUMEN ECONÓMICO -->
                        <div class="dv-resumen">
                            <div class="dv-resumen-bloque">
                                <span class="dv-resumen-label">Presupuesto IA</span>
                                <strong><?= dv_fmtEur($orden['precio_estimado_ia']) ?></strong>
                                <?php if ($orden['tiempo_estimado_ia']): ?>
                                    <small><?= dv_fmtMin($orden['tiempo_estimado_ia']) ?> estimados</small>
                                <?php endif; ?>
                            </div>

                            <div class="dv-resumen-bloque">
                                <span class="dv-resumen-label">
                                    <?= $facturada ? 'Total facturado' : 'Coste real (provisional)' ?>
                                </span>
                                <strong><?= $tiene_real ? dv_fmtEur($orden['coste_total_real']) : '—' ?></strong>
                                <?php if ($tiene_real): ?>
                                    <small><?= dv_fmtMin($orden['tiempo_real_minutos']) ?> trabajados</small>
                                <?php endif; ?>
                            </div>

                            <?php if ($tiene_real && $dif !== null): ?>
                                <div class="dv-resumen-bloque <?= $dif_clase ?>">
                                    <span class="dv-resumen-label">Diferencia</span>
                                    <strong><?= $dif_signo . dv_fmtEur($dif) ?></strong>
                                    <?php if ((float)$orden['precio_estimado_ia'] > 0): ?>
                                        <small>(<?= $dif_signo . number_format(($dif / (float)$orden['precio_estimado_ia']) * 100, 1, ',', '.') ?>% vs estimado)</small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($tiene_real): ?>
                            <div class="dv-desglose">
                                <span>Mano de obra: <strong><?= dv_fmtEur($orden['coste_mano_obra']) ?></strong></span>
                                <span>Materiales: <strong><?= dv_fmtEur($orden['coste_materiales']) ?></strong></span>
                                <span>Tarifa/hora: <?= dv_fmtEur($orden['tarifa_hora_base']) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- TAREAS DE LA ORDEN -->
                        <?php if (!empty($tareas)): ?>
                            <div class="subtareas">
                                <h4>Detalle de tareas:</h4>
                                <table class="tabla-tareas">
                                    <thead>
                                        <tr>
                                            <th>Tarea</th>
                                            <th>Mecánico</th>
                                            <th>Estado</th>
                                            <th class="col-num">T. estimado</th>
                                            <th class="col-num">T. real</th>
                                            <th class="col-num">€ estimado</th>
                                            <th class="col-num">€ real</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tareas as $tarea): ?>
                                            <?php $rechazada = $tarea['estado'] === 'rechazada'; ?>
                                            <tr class="<?= $rechazada ? 'tarea-rechazada' : '' ?>">
                                                <td><?= htmlspecialchars($tarea['nombre_tarea']) ?></td>
                                                <td><?= $tarea['mecanico'] ? htmlspecialchars($tarea['mecanico']) : '<small>Sin asignar</small>' ?></td>
                                                <td>
                                                    <span class="estado-tarea estado-<?= strtolower($tarea['estado']) ?>">
                                                        <?= ucfirst($tarea['estado']) ?>
                                                    </span>
                                                </td>
                                                <td class="col-num"><?= dv_fmtMin($tarea['tiempo_estimado_minutos']) ?></td>
                                                <td class="col-num"><?= dv_fmtMin($tarea['duracion_real_minutos']) ?></td>
                                                <td class="col-num"><?= dv_fmtEur($tarea['precio_estimado']) ?></td>
                                                <td class="col-num">
                                                    <?php if ($rechazada): ?>
                                                        <small>—</small>
                                                    <?php else: ?>
                                                        <?= (float)$tarea['coste_total_real'] > 0 ? dv_fmtEur($tarea['coste_total_real']) : '—' ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
