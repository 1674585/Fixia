<?php
// ─────────────────────────────────────────────
// Vista: Stock — listado de productos
// Espera: $productos, $resumen, $filtros,
//         $mensaje_ok, $mensaje_err
// ─────────────────────────────────────────────

$puede_editar = in_array($_SESSION['rol'], ['ceo', 'jefe', 'recepcionista']);
?>

<div class="stock-container">

    <!-- ── Cabecera ── -->
    <div class="stock-header">
        <div>
            <h2>Stock e Inventario</h2>
            <p class="stock-subtitulo">Gestión de productos y repuestos del taller</p>
        </div>
        <?php if ($puede_editar): ?>
            <a href="index.php?action=stockFormulario" class="btn btn-primary">
                + Añadir producto
            </a>
        <?php endif; ?>
    </div>

    <!-- ── Alertas flash ── -->
    <?php if ($mensaje_ok): ?>
        <div class="alerta alerta-exito">✓ <?= htmlspecialchars($mensaje_ok) ?></div>
    <?php endif; ?>
    <?php if ($mensaje_err): ?>
        <div class="alerta alerta-error">✗ <?= htmlspecialchars($mensaje_err) ?></div>
    <?php endif; ?>

    <!-- ── Tarjetas resumen ── -->
    <div class="stock-resumen-grid">
        <div class="resumen-card">
            <span class="resumen-valor"><?= (int)$resumen['total_productos'] ?></span>
            <span class="resumen-label">Productos</span>
        </div>
        <div class="resumen-card resumen-ok">
            <span class="resumen-valor"><?= (int)$resumen['stock_ok'] ?></span>
            <span class="resumen-label">Stock correcto</span>
        </div>
        <div class="resumen-card resumen-bajo">
            <span class="resumen-valor"><?= (int)$resumen['stock_bajo'] ?></span>
            <span class="resumen-label">Stock bajo</span>
        </div>
        <div class="resumen-card resumen-agotado">
            <span class="resumen-valor"><?= (int)$resumen['sin_stock'] ?></span>
            <span class="resumen-label">Agotados</span>
        </div>
        <div class="resumen-card resumen-valor-inv">
            <span class="resumen-valor">
                <?= number_format((float)$resumen['valor_inventario'], 2, ',', '.') ?> €
            </span>
            <span class="resumen-label">Valor inventario</span>
        </div>
    </div>

    <!-- ── Barra de filtros ── -->
    <form method="GET" action="index.php" class="filtros-form">
        <input type="hidden" name="action" value="stock">

        <div class="filtros-fila">
            <div class="filtro-busqueda">
                <input type="text"
                       name="busqueda"
                       placeholder="Buscar por nombre o referencia SKU…"
                       value="<?= htmlspecialchars($filtros['busqueda']) ?>"
                       class="input-busqueda">
            </div>

            <label class="filtro-check">
                <input type="checkbox"
                       name="alerta_stock"
                       <?= $filtros['alerta_stock'] ? 'checked' : '' ?>
                       onchange="this.form.submit()">
                Solo stock bajo / agotado
            </label>

            <button type="submit" class="btn btn-filtrar">Filtrar</button>

            <?php if ($filtros['busqueda'] || $filtros['alerta_stock']): ?>
                <a href="index.php?action=stock" class="btn btn-limpiar">✕ Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- ── Tabla de productos ── -->
    <?php if (empty($productos)): ?>
        <div class="stock-vacio">
            <div class="stock-vacio-icono">📦</div>
            <p>No se encontraron productos<?= $filtros['busqueda'] ? ' para "' . htmlspecialchars($filtros['busqueda']) . '"' : '' ?>.</p>
            <?php if ($puede_editar): ?>
                <a href="index.php?action=stockFormulario" class="btn btn-primary">Añadir el primer producto</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="tabla-wrapper">
            <table class="tabla-stock" id="tablaStock">
                <thead>
                    <tr>
                        <th>SKU / Ref.</th>
                        <th>Nombre</th>
                        <th class="col-num">Stock</th>
                        <th class="col-num">Alerta</th>
                        <th class="col-num">P. Compra</th>
                        <th class="col-num">P. Venta</th>
                        <th class="col-num">Margen</th>
                        <th>Estado</th>
                        <?php if ($puede_editar): ?>
                            <th class="col-acciones">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr class="fila-<?= $p['estado_stock'] ?>">
                            <td class="td-sku">
                                <?= $p['referencia_sku']
                                    ? htmlspecialchars($p['referencia_sku'])
                                    : '<span class="sin-sku">—</span>' ?>
                            </td>

                            <td class="td-nombre">
                                <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                            </td>

                            <td class="col-num td-stock">
                                <span class="stock-num stock-num-<?= $p['estado_stock'] ?>">
                                    <?= (int)$p['cantidad_stock'] ?>
                                </span>
                            </td>

                            <td class="col-num text-muted">
                                <?= (int)$p['alerta_stock_minimo'] ?>
                            </td>

                            <td class="col-num">
                                <?= number_format((float)$p['precio_compra'], 2, ',', '.') ?> €
                            </td>

                            <td class="col-num">
                                <?= number_format((float)$p['precio_venta'], 2, ',', '.') ?> €
                            </td>

                            <td class="col-num">
                                <?php if ($p['margen_pct'] !== null): ?>
                                    <span class="margen <?= $p['margen_pct'] >= 0 ? 'margen-pos' : 'margen-neg' ?>">
                                        <?= $p['margen_pct'] >= 0 ? '+' : '' ?><?= $p['margen_pct'] ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                    $badge_cfg = [
                                        'ok'         => ['clase' => 'badge-ok',      'label' => 'OK'],
                                        'stock_bajo' => ['clase' => 'badge-bajo',    'label' => 'Stock bajo'],
                                        'sin_stock'  => ['clase' => 'badge-agotado', 'label' => 'Agotado'],
                                    ];
                                    $b = $badge_cfg[$p['estado_stock']] ?? ['clase' => '', 'label' => $p['estado_stock']];
                                ?>
                                <span class="badge-stock <?= $b['clase'] ?>"><?= $b['label'] ?></span>
                            </td>

                            <?php if ($puede_editar): ?>
                                <td class="col-acciones acciones">
                                    <a href="index.php?action=stockFormulario&id=<?= $p['id'] ?>"
                                       class="btn btn-sm btn-editar" title="Editar">
                                        ✏ Editar
                                    </a>

                                    <!-- Botón eliminar con confirmación inline -->
                                    <button type="button"
                                            class="btn btn-sm btn-eliminar"
                                            onclick="confirmarEliminar(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>')"
                                            title="Eliminar">
                                        🗑 Eliminar
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="total-resultados">
            <?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?> encontrado<?= count($productos) !== 1 ? 's' : '' ?>
        </p>
    <?php endif; ?>

</div>

<!-- ── Modal de confirmación de eliminación ── -->
<div id="modalEliminar" class="modal-overlay" style="display:none">
    <div class="modal-caja">
        <div class="modal-icono">🗑</div>
        <h3 class="modal-titulo">Eliminar producto</h3>
        <p class="modal-texto">
            ¿Estás seguro de que quieres eliminar <strong id="modalNombreProducto"></strong>?
            <br><small>Esta acción no se puede deshacer.</small>
        </p>
        <form method="POST" action="index.php?action=stock" id="formEliminar">
            <input type="hidden" name="eliminar_id" id="eliminarId">
            <div class="modal-acciones">
                <button type="button" onclick="cerrarModal()" class="btn btn-cancelar">Cancelar</button>
                <button type="submit" class="btn btn-eliminar-confirm">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmarEliminar(id, nombre) {
        document.getElementById('eliminarId').value = id;
        document.getElementById('modalNombreProducto').textContent = nombre;
        document.getElementById('modalEliminar').style.display = 'flex';
    }
    function cerrarModal() {
        document.getElementById('modalEliminar').style.display = 'none';
    }
    // Cerrar modal al hacer clic fuera
    document.getElementById('modalEliminar').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });
</script>
