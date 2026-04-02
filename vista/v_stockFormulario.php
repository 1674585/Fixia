<?php
// ─────────────────────────────────────────────
// Vista: Formulario crear / editar producto
// Espera: $producto (array), $es_edicion (bool),
//         $error (string|null), $taller_id (int)
// ─────────────────────────────────────────────
?>

<div class="sf-container">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php?action=stock">Stock</a>
        <span class="bc-sep">›</span>
        <span><?= $es_edicion ? 'Editar producto' : 'Añadir producto' ?></span>
    </nav>

    <div class="sf-header">
        <h2><?= $es_edicion ? '✏ Editar producto' : '+ Añadir producto' ?></h2>
        <?php if ($es_edicion): ?>
            <span class="sf-id-badge">ID #<?= (int)$producto['id'] ?></span>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="alerta alerta-error">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST"
          action="index.php?action=stockFormulario<?= $es_edicion ? '&id=' . (int)$producto['id'] : '' ?>"
          class="sf-form"
          novalidate>

        <input type="hidden" name="producto_id" value="<?= (int)($producto['id'] ?? 0) ?>">

        <div class="sf-grid">

            <!-- ── Sección: Identificación ── -->
            <div class="sf-seccion">
                <h3 class="sf-seccion-titulo">Identificación</h3>

                <div class="campo">
                    <label class="campo-label" for="nombre">
                        Nombre del producto <span class="obligatorio">*</span>
                    </label>
                    <input type="text"
                           id="nombre"
                           name="nombre"
                           class="campo-input"
                           placeholder="Ej: Filtro de aceite Mann W712/75"
                           value="<?= htmlspecialchars($producto['nombre'] ?? '') ?>"
                           required
                           maxlength="100">
                </div>

                <div class="campo">
                    <label class="campo-label" for="referencia_sku">
                        Referencia / SKU
                    </label>
                    <input type="text"
                           id="referencia_sku"
                           name="referencia_sku"
                           class="campo-input campo-mono"
                           placeholder="Ej: MANN-W71275"
                           value="<?= htmlspecialchars($producto['referencia_sku'] ?? '') ?>"
                           maxlength="50">
                    <span class="campo-ayuda">Código interno o referencia del fabricante (opcional)</span>
                </div>
            </div>

            <!-- ── Sección: Stock ── -->
            <div class="sf-seccion">
                <h3 class="sf-seccion-titulo">Stock</h3>

                <div class="campos-fila">
                    <div class="campo">
                        <label class="campo-label" for="cantidad_stock">
                            Cantidad en stock <span class="obligatorio">*</span>
                        </label>
                        <input type="number"
                               id="cantidad_stock"
                               name="cantidad_stock"
                               class="campo-input"
                               min="0"
                               value="<?= (int)($producto['cantidad_stock'] ?? 0) ?>"
                               required>
                    </div>

                    <div class="campo">
                        <label class="campo-label" for="alerta_stock_minimo">
                            Alerta de stock mínimo
                        </label>
                        <input type="number"
                               id="alerta_stock_minimo"
                               name="alerta_stock_minimo"
                               class="campo-input"
                               min="0"
                               value="<?= (int)($producto['alerta_stock_minimo'] ?? 5) ?>">
                        <span class="campo-ayuda">Se marcará en amarillo cuando el stock baje de este valor</span>
                    </div>
                </div>
            </div>

            <!-- ── Sección: Precios ── -->
            <div class="sf-seccion">
                <h3 class="sf-seccion-titulo">Precios</h3>

                <div class="campos-fila">
                    <div class="campo">
                        <label class="campo-label" for="precio_compra">
                            Precio de compra (€) <span class="obligatorio">*</span>
                        </label>
                        <div class="input-euro">
                            <input type="number"
                                   id="precio_compra"
                                   name="precio_compra"
                                   class="campo-input"
                                   min="0"
                                   step="0.01"
                                   placeholder="0.00"
                                   value="<?= $producto['precio_compra'] !== '' ? number_format((float)$producto['precio_compra'], 2, '.', '') : '' ?>"
                                   required
                                   oninput="calcularMargen()">
                            <span class="euro-simbolo">€</span>
                        </div>
                    </div>

                    <div class="campo">
                        <label class="campo-label" for="precio_venta">
                            Precio de venta (€) <span class="obligatorio">*</span>
                        </label>
                        <div class="input-euro">
                            <input type="number"
                                   id="precio_venta"
                                   name="precio_venta"
                                   class="campo-input"
                                   min="0.01"
                                   step="0.01"
                                   placeholder="0.00"
                                   value="<?= $producto['precio_venta'] !== '' ? number_format((float)$producto['precio_venta'], 2, '.', '') : '' ?>"
                                   required
                                   oninput="calcularMargen()">
                            <span class="euro-simbolo">€</span>
                        </div>
                    </div>
                </div>

                <!-- Preview de margen en tiempo real -->
                <div class="margen-preview" id="margenPreview" style="display:none">
                    <span class="margen-preview-label">Margen estimado:</span>
                    <span class="margen-preview-valor" id="margenValor">—</span>
                </div>
            </div>

        </div><!-- /.sf-grid -->

        <!-- ── Acciones ── -->
        <div class="sf-acciones">
            <a href="index.php?action=stock" class="btn btn-cancelar">Cancelar</a>
            <button type="submit" class="btn btn-guardar">
                <?= $es_edicion ? '💾 Guardar cambios' : '+ Añadir producto' ?>
            </button>
        </div>

    </form>
</div>

<script>
    function calcularMargen() {
        const compra = parseFloat(document.getElementById('precio_compra').value) || 0;
        const venta  = parseFloat(document.getElementById('precio_venta').value)  || 0;
        const preview = document.getElementById('margenPreview');
        const valor   = document.getElementById('margenValor');

        if (compra > 0 && venta > 0) {
            const pct = ((venta - compra) / compra * 100).toFixed(1);
            const beneficio = (venta - compra).toFixed(2);
            valor.textContent = (pct >= 0 ? '+' : '') + pct + '% (' + (pct >= 0 ? '+' : '') + beneficio + ' €/ud)';
            valor.className = 'margen-preview-valor ' + (pct >= 0 ? 'margen-pos' : 'margen-neg');
            preview.style.display = 'flex';
        } else {
            preview.style.display = 'none';
        }
    }

    // Calcular al cargar si ya hay valores (modo edición)
    document.addEventListener('DOMContentLoaded', calcularMargen);
</script>
