<?php
// ─────────────────────────────────────────────
// Vista: Facturación — órdenes listas para cobrar
// Espera: $ordenes, $mensaje_ok, $mensaje_err
// ─────────────────────────────────────────────
?>

<div class="facturacion-container">

    <div class="fac-header">
        <div>
            <h2>Facturación</h2>
            <p class="fac-subtitulo">Órdenes completadas pendientes de cobro</p>
        </div>
        <div class="fac-resumen-badge">
            <span class="fac-count"><?= count($ordenes) ?></span>
            <span class="fac-count-label">pendiente<?= count($ordenes) !== 1 ? 's' : '' ?> de cobro</span>
        </div>
    </div>

    <?php if ($mensaje_ok): ?>
        <div class="alerta alerta-exito">✓ <?= $mensaje_ok ?></div>
    <?php endif; ?>
    <?php if ($mensaje_err): ?>
        <div class="alerta alerta-error">✗ <?= $mensaje_err ?></div>
    <?php endif; ?>

    <?php if (empty($ordenes)): ?>
        <div class="fac-vacio">
            <div class="fac-vacio-icono">✅</div>
            <p>No hay órdenes pendientes de cobro en este momento.</p>
        </div>
    <?php else: ?>
        <div class="tabla-wrapper">
            <table class="tabla-fac">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Vehículo</th>
                        <th>Cliente</th>
                        <th>Fecha finalización</th>
                        <th class="col-num">Mano de obra</th>
                        <th class="col-num">Materiales</th>
                        <th class="col-num">Total</th>
                        <th class="col-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordenes as $o): ?>
                        <tr>
                            <td>#<?= (int)$o['id'] ?></td>

                            <td>
                                <strong><?= htmlspecialchars($o['matricula']) ?></strong><br>
                                <small><?= htmlspecialchars($o['marca'] . ' ' . $o['modelo'] . ' ' . $o['anio']) ?></small>
                            </td>

                            <td>
                                <?= htmlspecialchars($o['nombre_cliente']) ?>
                                <?php if ($o['telefono_cliente']): ?>
                                    <br><small><?= htmlspecialchars($o['telefono_cliente']) ?></small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <small><?= date('d/m/Y H:i', strtotime($o['fecha_creacion'])) ?></small>
                            </td>

                            <td class="col-num">
                                <?= number_format((float)$o['coste_mano_obra'], 2, ',', '.') ?> €
                            </td>

                            <td class="col-num">
                                <?= number_format((float)$o['coste_materiales'], 2, ',', '.') ?> €
                            </td>

                            <td class="col-num total-celda">
                                <strong><?= number_format((float)$o['total_orden'], 2, ',', '.') ?> €</strong>
                            </td>

                            <td class="col-acciones acciones">
                                <a href="index.php?action=facturaDetalle&id=<?= $o['id'] ?>"
                                   class="btn btn-sm btn-ver">
                                    🧾 Ver factura
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-cobrar"
                                        onclick="abrirModalCobro(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['nombre_cliente'])) ?>', '<?= htmlspecialchars(addslashes($o['matricula'])) ?>', '<?= number_format((float)$o['total_orden'], 2, ',', '.') ?>')">
                                    ✓ Confirmar pago
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ── Modal de confirmación de pago ── -->
<div id="modalCobro" class="modal-overlay" style="display:none">
    <div class="modal-caja">
        <div class="modal-icono">💳</div>
        <h3 class="modal-titulo">Confirmar pago</h3>
        <p class="modal-texto">
            Cliente: <strong id="modalCliente"></strong><br>
            Vehículo: <strong id="modalMatricula"></strong><br>
            <span class="modal-importe-label">Total a cobrar:</span>
            <span class="modal-importe" id="modalImporte"></span>
        </p>
        <p class="modal-aviso">
            ¿El cliente ha realizado el pago? Esta acción marcará la orden como <strong>Facturada</strong> y no se podrá deshacer.
        </p>
        <form method="POST" action="index.php?action=confirmarPago">
            <input type="hidden" name="orden_id" id="modalOrdenId">
            <div class="modal-acciones">
                <button type="button" onclick="cerrarModal()" class="btn btn-cancelar">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-confirmar-pago">
                    ✓ Sí, confirmar pago
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalCobro(ordenId, cliente, matricula, importe) {
        document.getElementById('modalOrdenId').value    = ordenId;
        document.getElementById('modalCliente').textContent   = cliente;
        document.getElementById('modalMatricula').textContent = matricula;
        document.getElementById('modalImporte').textContent   = importe + ' €';
        document.getElementById('modalCobro').style.display   = 'flex';
    }

    function cerrarModal() {
        document.getElementById('modalCobro').style.display = 'none';
    }

    document.getElementById('modalCobro').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });
</script>