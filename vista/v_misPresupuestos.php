<?php
// Vista: Mis Presupuestos (cliente)
// Espera $modo='listado' con $presupuestos
// o     $modo='detalle'  con $detalle (orden + tareas)
?>

<div class="facturacion-container">

    <div class="fac-header">
        <div>
            <h2>Mis Presupuestos</h2>
            <p class="fac-subtitulo">
                <?php if ($modo === 'listado'): ?>
                    Presupuestos pendientes de tu aprobación
                <?php else: ?>
                    Selecciona qué tareas autorizas para tu vehículo
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if ($mensaje_ok): ?>
        <div class="alerta alerta-exito"><?= htmlspecialchars($mensaje_ok) ?></div>
    <?php endif; ?>
    <?php if ($mensaje_err): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($mensaje_err) ?></div>
    <?php endif; ?>

    <?php if ($modo === 'listado'): ?>

        <?php if (empty($presupuestos)): ?>
            <div class="fac-vacio">
                <p>No tienes presupuestos pendientes de aprobación.</p>
            </div>
        <?php else: ?>
            <div class="tabla-wrapper">
                <table class="tabla-fac">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vehículo</th>
                            <th>Fecha</th>
                            <th>Tareas</th>
                            <th class="col-num">Tiempo est.</th>
                            <th class="col-num">Coste est.</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presupuestos as $p): ?>
                            <tr>
                                <td>#<?= (int)$p['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($p['matricula']) ?></strong><br>
                                    <small><?= htmlspecialchars($p['marca'] . ' ' . $p['modelo'] . ' ' . $p['anio']) ?></small>
                                </td>
                                <td><small><?= date('d/m/Y', strtotime($p['fecha_creacion'])) ?></small></td>
                                <td><?= (int)$p['total_tareas'] ?></td>
                                <td class="col-num">
                                    <?php if ($p['tiempo_estimado_ia'] !== null): ?>
                                        <?= number_format((int)$p['tiempo_estimado_ia'] / 60, 2, ',', '.') ?> h
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="col-num">
                                    <?php if ($p['precio_estimado_ia'] !== null): ?>
                                        <?= number_format((float)$p['precio_estimado_ia'], 2, ',', '.') ?> €
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="col-acciones acciones">
                                    <a href="index.php?action=misPresupuestos&id=<?= (int)$p['id'] ?>"
                                       class="btn btn-sm btn-ver">Revisar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: /* === modo detalle === */ ?>

        <?php $o = $detalle['orden']; $tareas = $detalle['tareas']; ?>

        <div class="fac-cabecera-orden">
            <p>
                <strong>Orden #<?= (int)$o['id'] ?></strong> ·
                Vehículo: <strong><?= htmlspecialchars($o['matricula']) ?></strong>
                (<?= htmlspecialchars($o['marca'] . ' ' . $o['modelo'] . ' ' . $o['anio']) ?>)
            </p>
            <?php if (!empty($o['sintomas_cliente'])): ?>
                <p><em>Síntomas reportados:</em> <?= htmlspecialchars($o['sintomas_cliente']) ?></p>
            <?php endif; ?>
        </div>

        <form method="POST" action="index.php?action=misPresupuestos" id="form-presupuesto">
            <input type="hidden" name="orden_id" value="<?= (int)$o['id'] ?>">

            <div class="tabla-wrapper">
                <table class="tabla-fac">
                    <thead>
                        <tr>
                            <th>Acepto</th>
                            <th>Tarea</th>
                            <th class="col-num">Tiempo est.</th>
                            <th class="col-num">Coste est.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tareas as $t): ?>
                            <?php
                                $mins  = $t['tiempo_estimado_minutos'] !== null ? (int)$t['tiempo_estimado_minutos'] : null;
                                $precio = $t['precio_estimado'] !== null ? (float)$t['precio_estimado'] : null;
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox"
                                           name="aceptadas[]"
                                           value="<?= (int)$t['id'] ?>"
                                           class="check-tarea"
                                           data-precio="<?= $precio !== null ? $precio : 0 ?>"
                                           data-tiempo="<?= $mins !== null ? $mins : 0 ?>"
                                           checked>
                                </td>
                                <td><?= htmlspecialchars($t['nombre_tarea']) ?></td>
                                <td class="col-num">
                                    <?php if ($mins !== null): ?>
                                        <?= number_format($mins / 60, 2, ',', '.') ?> h
                                        <small>(<?= $mins ?> min)</small>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="col-num">
                                    <?php if ($precio !== null): ?>
                                        <?= number_format($precio, 2, ',', '.') ?> €
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" style="text-align:right">Totales aceptados:</th>
                            <th class="col-num"><span id="total-tiempo">0,00</span> h</th>
                            <th class="col-num"><strong><span id="total-precio">0,00</span> €</strong></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="modal-acciones" style="margin-top:1rem">
                <a href="index.php?action=misPresupuestos" class="btn btn-cancelar">Volver</a>
                <button type="submit" class="btn btn-confirmar-pago">
                    Confirmar presupuesto
                </button>
            </div>
        </form>

        <script>
            function recalcularTotales() {
                let totalPrecio = 0;
                let totalMin    = 0;
                document.querySelectorAll('.check-tarea').forEach(c => {
                    if (c.checked) {
                        totalPrecio += parseFloat(c.dataset.precio) || 0;
                        totalMin    += parseInt(c.dataset.tiempo, 10) || 0;
                    }
                });
                document.getElementById('total-precio').textContent =
                    totalPrecio.toFixed(2).replace('.', ',');
                document.getElementById('total-tiempo').textContent =
                    (totalMin / 60).toFixed(2).replace('.', ',');
            }
            document.querySelectorAll('.check-tarea').forEach(c => {
                c.addEventListener('change', recalcularTotales);
            });
            recalcularTotales();

            document.getElementById('form-presupuesto').addEventListener('submit', function(e) {
                const algunaAceptada = Array.from(document.querySelectorAll('.check-tarea'))
                    .some(c => c.checked);
                if (!algunaAceptada) {
                    e.preventDefault();
                    alert('Debes aceptar al menos una tarea para confirmar el presupuesto.');
                }
            });
        </script>

    <?php endif; ?>
</div>
