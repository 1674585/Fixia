<?php
// ─────────────────────────────────────────────
// Vista: Detalle y edición de una tarea
// Espera: $tarea, $tarea_id, $orden_id, $error,
//         $exito, $repuestos, $coste_repuestos,
//         $coste_horas, $coste_total, $tarifa_hora
// ─────────────────────────────────────────────

// Valores por defecto por si el controlador antiguo no los define
$repuestos       = $repuestos       ?? [];
$coste_repuestos = $coste_repuestos ?? 0.0;
$tarifa_hora     = $tarifa_hora     ?? (float)($_SESSION['tarifa_hora_base'] ?? 45.00);
$exito           = $exito           ?? null;

// Calcular coste horas aquí también como fallback
if (!isset($coste_horas)) {
    $coste_horas = $tarea['duracion_real_minutos']
        ? round(($tarea['duracion_real_minutos'] / 60) * $tarifa_hora, 2)
        : null;
}
if (!isset($coste_total)) {
    $coste_total = $coste_horas !== null ? round($coste_horas + $coste_repuestos, 2) : null;
}

function formatearParaInput($datetime_str) {
    if (!$datetime_str) return '';
    return (new DateTime($datetime_str))->format('Y-m-d\TH:i');
}

$duracion_fmt = null;
if ($tarea['duracion_real_minutos']) {
    $h = intdiv($tarea['duracion_real_minutos'], 60);
    $m = $tarea['duracion_real_minutos'] % 60;
    $duracion_fmt = $h > 0 ? "{$h}h {$m}min" : "{$m}min";
}
?>

<div class="dt-container">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php?action=misTareas">Mis Tareas</a>
        <span class="bc-sep">›</span>
        <a href="index.php?action=tareasOrden&orden_id=<?= $orden_id ?>">Orden #<?= $orden_id ?></a>
        <span class="bc-sep">›</span>
        <span><?= htmlspecialchars(mb_strimwidth($tarea['nombre_tarea'], 0, 30, '...')) ?></span>
    </nav>

    <?php if ($error): ?>
        <div class="alerta alerta-error">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($exito): ?>
        <div class="alerta alerta-exito">✓ <?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════
         FILA 1: Info vehículo + Formulario tarea
    ════════════════════════════════════════════ -->
    <div class="dt-grid">

        <!-- Columna izquierda: contexto -->
        <div class="dt-columna-info">

            <div class="card">
                <h3 class="card-titulo">Vehículo</h3>
                <div class="dato-fila">
                    <span class="dato-label">Matrícula</span>
                    <strong><?= htmlspecialchars($tarea['matricula']) ?></strong>
                </div>
                <div class="dato-fila">
                    <span class="dato-label">Modelo</span>
                    <span><?= htmlspecialchars($tarea['marca'] . ' ' . $tarea['modelo'] . ' (' . $tarea['anio'] . ')') ?></span>
                </div>
                <div class="dato-fila">
                    <span class="dato-label">Cliente</span>
                    <span><?= htmlspecialchars($tarea['nombre_cliente']) ?></span>
                </div>
                <?php if ($tarea['telefono_cliente']): ?>
                    <div class="dato-fila">
                        <span class="dato-label">Teléfono</span>
                        <a href="tel:<?= htmlspecialchars($tarea['telefono_cliente']) ?>" class="link-tel">
                            <?= htmlspecialchars($tarea['telefono_cliente']) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($tarea['sintomas_cliente']): ?>
                <div class="card">
                    <h3 class="card-titulo">Síntomas reportados</h3>
                    <p class="sintomas-texto"><?= nl2br(htmlspecialchars($tarea['sintomas_cliente'])) ?></p>
                </div>
            <?php endif; ?>

            <!-- Resumen de costes -->
            <div class="card">
                <h3 class="card-titulo">Resumen de costes</h3>
                <div class="dato-fila">
                    <span class="dato-label">Duración real</span>
                    <span><?= $duracion_fmt ?? '—' ?></span>
                </div>
                <div class="dato-fila">
                    <span class="dato-label">Tarifa/hora</span>
                    <span><?= number_format($tarifa_hora, 2, ',', '.') ?> €</span>
                </div>
                <div class="dato-fila">
                    <span class="dato-label">Coste mano obra</span>
                    <span><?= $coste_horas !== null ? number_format($coste_horas, 2, ',', '.') . ' €' : '—' ?></span>
                </div>
                <div class="dato-fila">
                    <span class="dato-label">Coste materiales</span>
                    <span><?= number_format($coste_repuestos, 2, ',', '.') ?> €</span>
                </div>
                <?php if ($tarea['minutos_estimados_base']): ?>
                    <div class="dato-fila">
                        <span class="dato-label">Tiempo estimado</span>
                        <span><?= $tarea['minutos_estimados_base'] ?> min</span>
                    </div>
                    <?php if ($duracion_fmt): ?>
                        <?php
                            $diff_min   = $tarea['duracion_real_minutos'] - $tarea['minutos_estimados_base'];
                            $clase_diff = $diff_min > 0 ? 'diff-positivo' : 'diff-negativo';
                            $signo      = $diff_min > 0 ? '+' : '';
                        ?>
                        <div class="dato-fila">
                            <span class="dato-label">Desviación</span>
                            <span class="<?= $clase_diff ?>"><?= $signo . $diff_min ?> min</span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="dato-fila dato-fila-total">
                    <span class="dato-label"><strong>TOTAL TAREA</strong></span>
                    <strong class="coste-total">
                        <?= $coste_total !== null ? number_format($coste_total, 2, ',', '.') . ' €' : '—' ?>
                    </strong>
                </div>
            </div>
        </div>

        <!-- Columna derecha: formulario tarea -->
        <div class="dt-columna-form">
            <div class="card">
                <h3 class="card-titulo"><?= htmlspecialchars($tarea['nombre_tarea']) ?></h3>

                <form method="POST"
                      action="index.php?action=detallesTarea&tarea_id=<?= $tarea_id ?>&orden_id=<?= $orden_id ?>">
                    <input type="hidden" name="accion" value="guardar_tarea">

                    <!-- Estado -->
                    <div class="campo">
                        <label class="campo-label">Estado de la tarea</label>
                        <div class="estado-opciones">
                            <label class="estado-opcion <?= $tarea['estado'] === 'pendiente'  ? 'seleccionada' : '' ?>">
                                <input type="radio" name="estado" value="pendiente"
                                       <?= $tarea['estado'] === 'pendiente'  ? 'checked' : '' ?>>
                                <span class="estado-dot dot-pendiente"></span>
                                Pendiente
                            </label>
                            <label class="estado-opcion <?= $tarea['estado'] === 'en_proceso' ? 'seleccionada' : '' ?>">
                                <input type="radio" name="estado" value="en_proceso"
                                       <?= $tarea['estado'] === 'en_proceso' ? 'checked' : '' ?>>
                                <span class="estado-dot dot-en-proceso"></span>
                                En proceso
                            </label>
                            <label class="estado-opcion <?= $tarea['estado'] === 'finalizada' ? 'seleccionada' : '' ?>">
                                <input type="radio" name="estado" value="finalizada"
                                       <?= $tarea['estado'] === 'finalizada' ? 'checked' : '' ?>>
                                <span class="estado-dot dot-finalizada"></span>
                                Finalizada
                            </label>
                        </div>
                    </div>

                    <!-- Hora inicio -->
                    <div class="campo">
                        <label class="campo-label" for="hora_inicio">Hora de inicio</label>
                        <input type="datetime-local" id="hora_inicio" name="hora_inicio"
                               class="campo-input"
                               value="<?= htmlspecialchars(formatearParaInput($tarea['hora_inicio'])) ?>">
                        <span class="campo-ayuda">Cuándo empezaste a trabajar en esta tarea</span>
                    </div>

                    <!-- Hora fin -->
                    <div class="campo">
                        <label class="campo-label" for="hora_fin">Hora de finalización</label>
                        <input type="datetime-local" id="hora_fin" name="hora_fin"
                               class="campo-input"
                               value="<?= htmlspecialchars(formatearParaInput($tarea['hora_fin'])) ?>">
                        <span class="campo-ayuda">La duración real se calculará automáticamente</span>
                    </div>

                    <div class="acciones-rapidas">
                        <button type="button" class="btn-rapido" onclick="rellenarAhora('hora_inicio')">▷ Inicio = ahora</button>
                        <button type="button" class="btn-rapido" onclick="rellenarAhora('hora_fin')">■ Fin = ahora</button>
                    </div>

                    <div class="form-acciones">
                        <a href="index.php?action=tareasOrden&orden_id=<?= $orden_id ?>" class="btn btn-cancelar">Cancelar</a>
                        <button type="submit" class="btn btn-guardar">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div><!-- /.dt-grid -->


    <!-- ════════════════════════════════════════════
         FILA 2: Repuestos consumidos
    ════════════════════════════════════════════ -->
    <div class="repuestos-section">

        <h3 class="section-titulo">🔩 Repuestos y materiales consumidos</h3>

        <!-- Tabla de repuestos ya añadidos -->
        <?php if (empty($repuestos)): ?>
            <p class="repuestos-vacio">No se han registrado materiales para esta tarea todavía.</p>
        <?php else: ?>
            <table class="tabla-repuestos">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Precio/ud (momento)</th>
                        <th>Subtotal</th>
                        <th>Stock actual</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repuestos as $r): ?>
                        <tr>
                            <td class="td-sku"><?= $r['referencia_sku'] ? htmlspecialchars($r['referencia_sku']) : '—' ?></td>
                            <td><?= htmlspecialchars($r['nombre_producto']) ?></td>
                            <td><?= (int)$r['cantidad'] ?> ud.</td>
                            <td><?= number_format((float)$r['precio_unidad_momento'], 2, ',', '.') ?> €</td>
                            <td><strong><?= number_format((float)$r['subtotal'], 2, ',', '.') ?> €</strong></td>
                            <td>
                                <?php if ((int)$r['stock_actual'] <= 0): ?>
                                    <span class="stock-agotado"><?= (int)$r['stock_actual'] ?> (agotado)</span>
                                <?php else: ?>
                                    <?= (int)$r['stock_actual'] ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST"
                                      action="index.php?action=detallesTarea&tarea_id=<?= $tarea_id ?>&orden_id=<?= $orden_id ?>"
                                      onsubmit="return confirm('¿Quitar este repuesto? Se devolverán <?= (int)$r['cantidad'] ?> unidades al stock.')">
                                    <input type="hidden" name="accion"      value="eliminar_repuesto">
                                    <input type="hidden" name="repuesto_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn-quitar">✕ Quitar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="tfoot-label">Total materiales</td>
                        <td colspan="3"><strong><?= number_format($coste_repuestos, 2, ',', '.') ?> €</strong></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>

        <!-- ── Formulario para añadir repuesto ── -->
        <div class="añadir-repuesto-card">
            <h4 class="añadir-titulo">Añadir material</h4>

            <form method="POST"
                  action="index.php?action=detallesTarea&tarea_id=<?= $tarea_id ?>&orden_id=<?= $orden_id ?>"
                  id="formAñadirRepuesto">
                <input type="hidden" name="accion"      value="añadir_repuesto">
                <input type="hidden" name="producto_id" id="productoIdInput" value="">

                <!-- Buscador live -->
                <div class="buscador-wrapper">
                    <label class="campo-label" for="buscadorProducto">Buscar producto</label>
                    <input type="text"
                           id="buscadorProducto"
                           class="campo-input"
                           placeholder="Escribe nombre o referencia SKU…"
                           autocomplete="off">

                    <!-- Dropdown de resultados -->
                    <div id="dropdownResultados" class="dropdown-resultados" style="display:none"></div>

                    <!-- Producto seleccionado -->
                    <div id="productoSeleccionado" class="producto-seleccionado" style="display:none">
                        <span id="productoSeleccionadoNombre"></span>
                        <span id="productoSeleccionadoPrecio" class="producto-precio"></span>
                        <span id="productoSeleccionadoStock"  class="producto-stock"></span>
                        <button type="button" onclick="limpiarSeleccion()" class="btn-limpiar-sel">✕</button>
                    </div>
                </div>

                <!-- Cantidad -->
                <div class="campo cantidad-campo">
                    <label class="campo-label" for="cantidadInput">Cantidad</label>
                    <input type="number"
                           id="cantidadInput"
                           name="cantidad"
                           class="campo-input"
                           min="1"
                           value="1"
                           required>
                </div>

                <button type="submit"
                        id="btnAñadir"
                        class="btn btn-añadir"
                        disabled>
                    + Añadir al registro
                </button>
            </form>
        </div>

    </div><!-- /.repuestos-section -->

</div><!-- /.dt-container -->


<script>
// ── Rellenar hora con "ahora" ──────────────────
function rellenarAhora(campo) {
    const ahora = new Date();
    const pad   = n => String(n).padStart(2, '0');
    document.getElementById(campo).value =
        ahora.getFullYear() + '-' + pad(ahora.getMonth()+1) + '-' + pad(ahora.getDate()) +
        'T' + pad(ahora.getHours()) + ':' + pad(ahora.getMinutes());
}

// ── Resaltar opción de estado seleccionada ─────
document.querySelectorAll('.estado-opcion input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.estado-opcion').forEach(el => el.classList.remove('seleccionada'));
        radio.closest('.estado-opcion').classList.add('seleccionada');
    });
});

// ── Buscador live de productos ─────────────────
const inputBuscador   = document.getElementById('buscadorProducto');
const dropdown        = document.getElementById('dropdownResultados');
const productoIdInput = document.getElementById('productoIdInput');
const btnAñadir       = document.getElementById('btnAñadir');
const bloqueSeleccion = document.getElementById('productoSeleccionado');
const spanNombre      = document.getElementById('productoSeleccionadoNombre');
const spanPrecio      = document.getElementById('productoSeleccionadoPrecio');
const spanStock       = document.getElementById('productoSeleccionadoStock');

let timeoutBusqueda = null;

inputBuscador.addEventListener('input', () => {
    clearTimeout(timeoutBusqueda);
    const q = inputBuscador.value.trim();

    if (q.length < 2) {
        dropdown.style.display = 'none';
        dropdown.innerHTML     = '';
        return;
    }

    timeoutBusqueda = setTimeout(() => {
        fetch('index.php?action=buscarProductosTarea&q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(productos => {
                dropdown.innerHTML = '';

                if (productos.length === 0) {
                    dropdown.innerHTML = '<div class="dropdown-item dropdown-vacio">Sin resultados</div>';
                } else {
                    productos.forEach(p => {
                        const item = document.createElement('div');
                        item.className = 'dropdown-item';
                        item.innerHTML =
                            '<span class="di-nombre">' + escHtml(p.nombre) + '</span>' +
                            (p.referencia_sku ? '<span class="di-sku">' + escHtml(p.referencia_sku) + '</span>' : '') +
                            '<span class="di-precio">' + formatEur(p.precio_venta) + ' €</span>' +
                            '<span class="di-stock">Stock: ' + p.cantidad_stock + '</span>';

                        item.addEventListener('click', () => seleccionarProducto(p));
                        dropdown.appendChild(item);
                    });
                }
                dropdown.style.display = 'block';
            })
            .catch(() => { dropdown.style.display = 'none'; });
    }, 280); // debounce 280ms
});

function seleccionarProducto(p) {
    productoIdInput.value = p.id;
    inputBuscador.value   = '';
    dropdown.style.display = 'none';

    spanNombre.textContent = p.nombre;
    spanPrecio.textContent = formatEur(p.precio_venta) + ' €/ud';
    spanStock.textContent  = 'Stock: ' + p.cantidad_stock;

    bloqueSeleccion.style.display = 'flex';
    btnAñadir.disabled = false;

    // Limitar cantidad al stock disponible
    const cantInput = document.getElementById('cantidadInput');
    cantInput.max = p.cantidad_stock;
    if (parseInt(cantInput.value) > p.cantidad_stock) {
        cantInput.value = p.cantidad_stock;
    }
}

function limpiarSeleccion() {
    productoIdInput.value         = '';
    bloqueSeleccion.style.display = 'none';
    btnAñadir.disabled            = true;
    document.getElementById('cantidadInput').removeAttribute('max');
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', e => {
    if (!inputBuscador.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

// Helpers
function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatEur(num) {
    return parseFloat(num).toFixed(2).replace('.', ',');
}
</script>