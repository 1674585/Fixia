<?php
// ─────────────────────────────────────────────
// Vista: Detalle de factura desglosada
// Espera: $factura (array con claves:
//   orden, tareas, coste_total_mano_obra,
//   coste_total_materiales, total_factura)
// ─────────────────────────────────────────────

$orden  = $factura['orden'];
$tareas = $factura['tareas'];

$estados_orden = [
    'listo'     => ['label' => 'Lista para cobro', 'clase' => 'badge-listo'],
    'facturado' => ['label' => 'Facturada',         'clase' => 'badge-facturado'],
];
$est = $estados_orden[$orden['estado']] ?? ['label' => $orden['estado'], 'clase' => ''];

function fmtMin($min) {
    if ($min === null || $min === '' || (int)$min === 0) return '—';
    $h = intdiv((int)$min, 60); $m = (int)$min % 60;
    return $h > 0 ? "{$h}h {$m}min" : "{$m}min";
}
function fmtEur($n) {
    return number_format((float)$n, 2, ',', '.') . ' €';
}
function fmtFecha($d) {
    return $d ? date('d/m/Y H:i', strtotime($d)) : '—';
}
function fmtEurOrDash($n) {
    return $n === null ? '—' : fmtEur($n);
}
?>

<div class="fd-container">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php?action=facturacion">Facturación</a>
        <span class="bc-sep">/</span>
        <span>Orden #<?= (int)$orden['id'] ?></span>
    </nav>

    <!-- Cabecera de la factura -->
    <div class="fd-cabecera">
        <div class="fd-taller">
            <h2 class="fd-taller-nombre"><?= htmlspecialchars($orden['nombre_taller']) ?></h2>
            <?php if ($orden['identificacion_fiscal']): ?>
                <span class="fd-cif"><?= htmlspecialchars($orden['identificacion_fiscal']) ?></span>
            <?php endif; ?>
            <?php if ($orden['direccion_taller']): ?>
                <p class="fd-direccion"><?= htmlspecialchars($orden['direccion_taller']) ?></p>
            <?php endif; ?>
        </div>
        <div class="fd-meta">
            <div class="fd-meta-row">
                <span class="fd-meta-label">Orden</span>
                <strong>#<?= (int)$orden['id'] ?></strong>
            </div>
            <div class="fd-meta-row">
                <span class="fd-meta-label">Fecha</span>
                <span><?= fmtFecha($orden['fecha_creacion']) ?></span>
            </div>
            <div class="fd-meta-row">
                <span class="fd-meta-label">Estado</span>
                <span class="badge <?= $est['clase'] ?>"><?= $est['label'] ?></span>
            </div>
            <div class="fd-meta-row">
                <span class="fd-meta-label">Tarifa/hora</span>
                <span><?= fmtEur($orden['tarifa_hora_base']) ?></span>
            </div>
        </div>
    </div>

    <!-- Datos cliente + vehículo -->
    <div class="fd-partes">
        <div class="fd-parte">
            <h4 class="fd-parte-titulo">Cliente</h4>
            <p class="fd-parte-valor"><?= htmlspecialchars($orden['nombre_cliente']) ?></p>
            <?php if ($orden['telefono_cliente']): ?>
                <p class="fd-parte-sub"><?= htmlspecialchars($orden['telefono_cliente']) ?></p>
            <?php endif; ?>
            <?php if ($orden['email_cliente']): ?>
                <p class="fd-parte-sub"><?= htmlspecialchars($orden['email_cliente']) ?></p>
            <?php endif; ?>
        </div>
        <div class="fd-parte">
            <h4 class="fd-parte-titulo">Vehículo</h4>
            <p class="fd-parte-valor"><?= htmlspecialchars($orden['matricula']) ?></p>
            <p class="fd-parte-sub"><?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo'] . ' (' . $orden['anio'] . ')') ?></p>
            <?php if ($orden['ultimo_kilometraje']): ?>
                <p class="fd-parte-sub"><?= number_format($orden['ultimo_kilometraje'], 0, ',', '.') ?> km</p>
            <?php endif; ?>
        </div>
        <?php if ($orden['sintomas_cliente'] || $orden['diagnostico_tecnico']): ?>
            <div class="fd-parte fd-parte-wide">
                <?php if ($orden['sintomas_cliente']): ?>
                    <h4 class="fd-parte-titulo">Síntomas</h4>
                    <p class="fd-parte-sub"><?= nl2br(htmlspecialchars($orden['sintomas_cliente'])) ?></p>
                <?php endif; ?>
                <?php if ($orden['diagnostico_tecnico']): ?>
                    <h4 class="fd-parte-titulo" style="margin-top:.5rem">Diagnóstico</h4>
                    <p class="fd-parte-sub"><?= nl2br(htmlspecialchars($orden['diagnostico_tecnico'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Resumen presupuesto vs real ── -->
    <?php if ($factura['total_estimado'] !== null): ?>
        <?php
            $dif = $factura['diferencia_estimado'];
            $dif_clase = $dif === null ? '' : ($dif > 0 ? 'fd-dif-alza' : ($dif < 0 ? 'fd-dif-baja' : ''));
            $dif_signo = $dif !== null && $dif > 0 ? '+' : '';
        ?>
        <div class="fd-resumen-presupuesto">
            <div class="fd-resumen-bloque">
                <span class="fd-resumen-label">Presupuesto IA</span>
                <strong><?= fmtEur($factura['total_estimado']) ?></strong>
                <?php if ($orden['tiempo_estimado_ia']): ?>
                    <small><?= fmtMin($orden['tiempo_estimado_ia']) ?> est.</small>
                <?php endif; ?>
            </div>
            <div class="fd-resumen-bloque">
                <span class="fd-resumen-label">Total real</span>
                <strong><?= fmtEur($factura['total_factura']) ?></strong>
            </div>
            <div class="fd-resumen-bloque <?= $dif_clase ?>">
                <span class="fd-resumen-label">Diferencia</span>
                <strong><?= $dif_signo . fmtEur($dif) ?></strong>
                <?php if ($factura['total_estimado'] > 0): ?>
                    <small>(<?= $dif_signo . number_format(($dif / $factura['total_estimado']) * 100, 1, ',', '.') ?>%)</small>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── Desglose de tareas ── -->
    <h3 class="fd-seccion-titulo">Desglose de trabajos</h3>

    <?php if (empty($tareas)): ?>
        <p class="fd-vacio">Esta orden no tiene tareas registradas.</p>
    <?php else: ?>

        <?php foreach ($tareas as $idx => $tarea): ?>
            <div class="fd-tarea-bloque">

                <!-- Cabecera de la tarea -->
                <div class="fd-tarea-header">
                    <div class="fd-tarea-num"><?= $idx + 1 ?></div>
                    <div class="fd-tarea-info">
                        <span class="fd-tarea-nombre"><?= htmlspecialchars($tarea['nombre_tarea']) ?></span>
                        <span class="fd-tarea-mecanico">Mecánico: <?= htmlspecialchars($tarea['nombre_mecanico']) ?></span>
                    </div>
                    <div class="fd-tarea-total">
                        <?= fmtEur($tarea['coste_total_tarea']) ?>
                    </div>
                </div>

                <!-- Línea: Mano de obra -->
                <table class="fd-lineas">
                    <tbody>
                        <tr class="fd-linea-mano-obra">
                            <td class="fd-linea-desc">
                                Mano de obra — <?= fmtMin($tarea['duracion_real_minutos']) ?>
                                <?php if ($tarea['hora_inicio'] && $tarea['hora_fin']): ?>
                                    <span class="fd-horas-detalle">
                                        (de <?= fmtFecha($tarea['hora_inicio']) ?> a <?= fmtFecha($tarea['hora_fin']) ?>)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="fd-linea-cant">1</td>
                            <td class="fd-linea-precio"><?= fmtEur($tarea['coste_mano_obra']) ?></td>
                            <td class="fd-linea-subtotal"><?= fmtEur($tarea['coste_mano_obra']) ?></td>
                        </tr>

                        <!-- Líneas: Repuestos -->
                        <?php if (!empty($tarea['repuestos'])): ?>
                            <?php foreach ($tarea['repuestos'] as $r): ?>
                                <tr class="fd-linea-repuesto">
                                    <td class="fd-linea-desc">
                                        <?= htmlspecialchars($r['nombre_producto']) ?>
                                        <?php if ($r['referencia_sku']): ?>
                                            <span class="fd-sku">[<?= htmlspecialchars($r['referencia_sku']) ?>]</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fd-linea-cant"><?= (int)$r['cantidad'] ?> ud.</td>
                                    <td class="fd-linea-precio"><?= fmtEur($r['precio_unidad_momento']) ?></td>
                                    <td class="fd-linea-subtotal"><?= fmtEur($r['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Subtotales de la tarea -->
                <div class="fd-tarea-subtotales">
                    <span>Mano de obra: <strong><?= fmtEur($tarea['coste_mano_obra']) ?></strong></span>
                    <span>Materiales: <strong><?= fmtEur($tarea['coste_materiales']) ?></strong></span>
                    <span class="fd-subtotal-tarea">Subtotal tarea: <strong><?= fmtEur($tarea['coste_total_tarea']) ?></strong></span>
                </div>

                <!-- Comparativa estimado vs real de la tarea -->
                <?php if ($tarea['precio_estimado'] !== null || $tarea['tiempo_estimado_minutos'] !== null): ?>
                    <?php
                        $real_total   = (float)$tarea['coste_total_tarea'];
                        $est_total    = $tarea['precio_estimado'] !== null ? (float)$tarea['precio_estimado'] : null;
                        $dif_t        = $est_total !== null ? round($real_total - $est_total, 2) : null;
                        $dif_t_clase  = $dif_t === null ? '' : ($dif_t > 0 ? 'fd-dif-alza' : ($dif_t < 0 ? 'fd-dif-baja' : ''));
                        $dif_t_signo  = $dif_t !== null && $dif_t > 0 ? '+' : '';

                        $real_min     = $tarea['duracion_real_minutos'] !== null ? (int)$tarea['duracion_real_minutos'] : null;
                        $est_min      = $tarea['tiempo_estimado_minutos'] !== null ? (int)$tarea['tiempo_estimado_minutos'] : null;
                        $dif_min      = ($real_min !== null && $est_min !== null) ? ($real_min - $est_min) : null;
                    ?>
                    <table class="fd-comparativa">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="col-num">Estimado IA</th>
                                <th class="col-num">Real</th>
                                <th class="col-num">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Tiempo</td>
                                <td class="col-num"><?= fmtMin($est_min) ?></td>
                                <td class="col-num"><?= fmtMin($real_min) ?></td>
                                <td class="col-num <?= $dif_min === null ? '' : ($dif_min > 0 ? 'fd-dif-alza' : ($dif_min < 0 ? 'fd-dif-baja' : '')) ?>">
                                    <?php if ($dif_min === null): ?>—
                                    <?php else: ?><?= ($dif_min > 0 ? '+' : '') . fmtMin(abs($dif_min)) ?><?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Coste</td>
                                <td class="col-num"><?= fmtEurOrDash($est_total) ?></td>
                                <td class="col-num"><?= fmtEur($real_total) ?></td>
                                <td class="col-num <?= $dif_t_clase ?>">
                                    <?php if ($dif_t === null): ?>—
                                    <?php else: ?><?= $dif_t_signo . fmtEur($dif_t) ?><?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>

    <!-- ── Totales finales ── -->
    <div class="fd-totales">
        <?php if ($factura['total_estimado'] !== null): ?>
            <div class="fd-total-fila fd-total-estimado">
                <span>Presupuesto IA inicial</span>
                <span><?= fmtEur($factura['total_estimado']) ?></span>
            </div>
        <?php endif; ?>
        <div class="fd-total-fila">
            <span>Total mano de obra</span>
            <span><?= fmtEur($factura['coste_total_mano_obra']) ?></span>
        </div>
        <div class="fd-total-fila">
            <span>Total materiales</span>
            <span><?= fmtEur($factura['coste_total_materiales']) ?></span>
        </div>
        <div class="fd-total-fila fd-total-final">
            <span>TOTAL A PAGAR</span>
            <strong><?= fmtEur($factura['total_factura']) ?></strong>
        </div>
        <?php if ($orden['estado'] === 'facturado' && !empty($orden['nombre_facturador'])): ?>
            <div class="fd-total-fila fd-total-auditoria">
                <span>Facturado por</span>
                <span>
                    <?= htmlspecialchars($orden['nombre_facturador']) ?>
                    <?php if ($orden['facturado_en']): ?>
                        · <?= fmtFecha($orden['facturado_en']) ?>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Acciones ── -->
    <div class="fd-acciones">
        <a href="index.php?action=facturacion" class="btn btn-volver">Volver</a>

        <?php if ($orden['estado'] === 'listo'): ?>
            <button type="button"
                    class="btn btn-cobrar"
                    onclick="document.getElementById('modalConfirmar').style.display='flex'">
                Confirmar pago — <?= fmtEur($factura['total_factura']) ?>
            </button>
        <?php else: ?>
            <span class="badge badge-facturado">Pagada y facturada</span>
        <?php endif; ?>
    </div>

</div>

<!-- ── Modal de confirmación de pago ── -->
<div id="modalConfirmar" class="modal-overlay" style="display:none">
    <div class="modal-caja">
        <h3 class="modal-titulo">Confirmar pago</h3>
        <p class="modal-texto">
            Vas a marcar la orden <strong>#<?= (int)$orden['id'] ?></strong> del cliente
            <strong><?= htmlspecialchars($orden['nombre_cliente']) ?></strong>
            como pagada y facturada.
        </p>
        <p class="modal-importe-grande"><?= fmtEur($factura['total_factura']) ?></p>
        <p class="modal-aviso">Esta acción no se puede deshacer.</p>
        <form method="POST" action="index.php?action=confirmarPago">
            <input type="hidden" name="orden_id" value="<?= (int)$orden['id'] ?>">
            <input type="hidden" name="origen"   value="detalle">
            <div class="modal-acciones">
                <button type="button"
                        onclick="document.getElementById('modalConfirmar').style.display='none'"
                        class="btn btn-cancelar">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-confirmar-pago">
                    Sí, confirmar pago
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('modalConfirmar').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
</script>