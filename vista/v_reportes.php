<?php
// ─────────────────────────────────────────────
// Vista: Reportes y métricas de negocio
// ─────────────────────────────────────────────

// Helpers de formato
function r_eur($n)  { return number_format((float)$n, 2, ',', '.') . ' €'; }
function r_pct($n)  { return number_format((float)$n, 1, ',', '.') . '%'; }
function r_num($n)  { return number_format((float)$n, 0, ',', '.'); }
function r_min($m)  {
    if (!$m) return '—';
    $h = intdiv((int)$m, 60); $min = (int)$m % 60;
    return $h > 0 ? "{$h}h {$min}min" : "{$min}min";
}

$etiquetas_estado = [
    'recibido'       => 'Recibido',
    'diagnosticando' => 'Diagnosticando',
    'presupuestado'  => 'Presupuestado',
    'en_reparacion'  => 'En reparación',
    'listo'          => 'Listo',
    'facturado'      => 'Facturado',
];

$periodos_opciones = [
    '7dias'       => 'Últimos 7 días',
    '30dias'      => 'Últimos 30 días',
    'mes_actual'  => 'Este mes',
    'mes_anterior'=> 'Mes anterior',
    'anio_actual' => 'Este año',
    'personalizado'=> 'Personalizado',
];
?>
<!– Importar Chart.js desde CDN –>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="rep-container">

    <!-- ══ CABECERA + FILTROS ══════════════════════════════════════ -->
    <div class="rep-header">
        <div>
            <h2>Reportes</h2>
            <p class="rep-subtitulo">Métricas de negocio del taller</p>
        </div>
    </div>

    <!-- Barra de filtros -->
    <form method="GET" action="index.php" class="rep-filtros">
        <input type="hidden" name="action" value="reportes">

        <div class="rep-filtros-fila">

            <!-- Período predefinido -->
            <div class="rep-filtro-grupo">
                <label class="rep-filtro-label">Período</label>
                <div class="rep-periodo-btns">
                    <?php foreach ($periodos_opciones as $val => $lbl): ?>
                        <button type="submit" name="periodo" value="<?= $val ?>"
                                class="rep-periodo-btn <?= $periodo === $val ? 'activo' : '' ?>">
                            <?= $lbl ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fechas personalizadas -->
            <?php if ($periodo === 'personalizado'): ?>
                <div class="rep-filtro-grupo rep-fechas-custom">
                    <label class="rep-filtro-label">Desde</label>
                    <input type="date" name="desde" value="<?= htmlspecialchars($fecha_desde) ?>" class="rep-input-fecha">
                    <label class="rep-filtro-label">Hasta</label>
                    <input type="date" name="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>" class="rep-input-fecha">
                    <button type="submit" class="btn btn-aplicar">Aplicar</button>
                </div>
            <?php endif; ?>

            <!-- Agrupación temporal -->
            <div class="rep-filtro-grupo">
                <label class="rep-filtro-label">Agrupar gráficos por</label>
                <div class="rep-periodo-btns">
                    <?php foreach (['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes'] as $v => $l): ?>
                        <button type="submit" name="agrupacion" value="<?= $v ?>"
                                class="rep-periodo-btn <?= $agrupacion === $v ? 'activo' : '' ?>">
                            <?= $l ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Info del rango activo -->
        <?php if ($fecha_desde || $fecha_hasta): ?>
            <p class="rep-rango-activo">
                📅 Mostrando datos del
                <strong><?= $fecha_desde ? date('d/m/Y', strtotime($fecha_desde)) : '—' ?></strong>
                al
                <strong><?= $fecha_hasta ? date('d/m/Y', strtotime($fecha_hasta)) : 'hoy' ?></strong>
            </p>
        <?php endif; ?>

        <!-- Mantener agrupacion y periodo al cambiar el otro -->
        <input type="hidden" name="periodo"    value="<?= htmlspecialchars($periodo) ?>">
        <input type="hidden" name="agrupacion" value="<?= htmlspecialchars($agrupacion) ?>">
        <?php if ($periodo === 'personalizado'): ?>
            <input type="hidden" name="desde" value="<?= htmlspecialchars($fecha_desde) ?>">
            <input type="hidden" name="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
        <?php endif; ?>
    </form>


    <!-- ══ KPIs FINANCIEROS (tarjetas grandes) ════════════════════ -->
    <div class="rep-kpi-grid">
        <div class="kpi-card kpi-ingresos">
            <span class="kpi-icono">💰</span>
            <span class="kpi-valor"><?= r_eur($financiero['ingresos_totales'] ?? 0) ?></span>
            <span class="kpi-label">Ingresos totales</span>
        </div>
        <div class="kpi-card kpi-margen">
            <span class="kpi-icono">📈</span>
            <span class="kpi-valor"><?= r_eur($financiero['margen_bruto'] ?? 0) ?></span>
            <span class="kpi-label">Margen bruto <small>(<?= r_pct($financiero['margen_pct'] ?? 0) ?>)</small></span>
        </div>
        <div class="kpi-card kpi-ticket">
            <span class="kpi-icono">🧾</span>
            <span class="kpi-valor"><?= r_eur($financiero['ticket_medio'] ?? 0) ?></span>
            <span class="kpi-label">Ticket medio</span>
        </div>
        <div class="kpi-card kpi-ordenes">
            <span class="kpi-icono">🔧</span>
            <span class="kpi-valor"><?= r_num($financiero['total_ordenes'] ?? 0) ?></span>
            <span class="kpi-label">Órdenes facturadas</span>
        </div>
        <div class="kpi-card kpi-tiempo">
            <span class="kpi-icono">⏱</span>
            <span class="kpi-valor"><?= r_min($tiempo_medio['media_min_por_orden'] ?? null) ?></span>
            <span class="kpi-label">Tiempo medio/orden</span>
        </div>
    </div>


    <!-- ══ FILA 1: Ingresos + Órdenes por estado ══════════════════ -->
    <div class="rep-grid-2">

        <!-- Gráfico: Ingresos vs costes por período -->
        <div class="rep-card">
            <h3 class="rep-card-titulo">💰 Ingresos y costes por <?= $agrupacion ?></h3>
            <?php if (empty($ingresos_periodo)): ?>
                <p class="rep-vacio">Sin datos en el período seleccionado.</p>
            <?php else: ?>
                <canvas id="graficoIngresos" height="220"></canvas>
            <?php endif; ?>
        </div>

        <!-- Gráfico: Órdenes por estado (donut) -->
        <div class="rep-card">
            <h3 class="rep-card-titulo">🔥 Órdenes por estado</h3>
            <?php if (empty($ordenes_por_estado)): ?>
                <p class="rep-vacio">Sin órdenes en el período seleccionado.</p>
            <?php else: ?>
                <canvas id="graficoEstados" height="220"></canvas>
                <div class="rep-leyenda-estados">
                    <?php foreach ($ordenes_por_estado as $e): ?>
                        <div class="rep-leyenda-item">
                            <span class="badge-estado badge-<?= str_replace('_','-',$e['estado']) ?>">
                                <?= $etiquetas_estado[$e['estado']] ?? $e['estado'] ?>
                            </span>
                            <strong><?= r_num($e['total']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <!-- ══ FILA 2: Throughput + Tiempo medio ══════════════════════ -->
    <div class="rep-grid-2">

        <!-- Throughput -->
        <div class="rep-card">
            <h3 class="rep-card-titulo">🔥 Throughput — órdenes facturadas por <?= $agrupacion ?></h3>
            <?php if (empty($throughput)): ?>
                <p class="rep-vacio">Sin datos en el período seleccionado.</p>
            <?php else: ?>
                <canvas id="graficoThroughput" height="220"></canvas>
            <?php endif; ?>
        </div>

        <!-- Desglose financiero -->
        <div class="rep-card">
            <h3 class="rep-card-titulo">💰 Desglose de ingresos</h3>
            <div class="rep-desglose">
                <div class="rep-desglose-fila">
                    <span>Mano de obra</span>
                    <div class="rep-desglose-barra-wrap">
                        <?php
                            $tot = (float)($financiero['ingresos_totales'] ?? 1) ?: 1;
                            $pct_mano = round(($financiero['ingresos_mano_obra'] ?? 0) / $tot * 100);
                            $pct_mat  = round(($financiero['ingresos_materiales'] ?? 0) / $tot * 100);
                        ?>
                        <div class="rep-desglose-barra" style="width:<?= $pct_mano ?>%"></div>
                    </div>
                    <span class="rep-desglose-valor"><?= r_eur($financiero['ingresos_mano_obra'] ?? 0) ?> (<?= $pct_mano ?>%)</span>
                </div>
                <div class="rep-desglose-fila">
                    <span>Materiales</span>
                    <div class="rep-desglose-barra-wrap">
                        <div class="rep-desglose-barra rep-barra-mat" style="width:<?= $pct_mat ?>%"></div>
                    </div>
                    <span class="rep-desglose-valor"><?= r_eur($financiero['ingresos_materiales'] ?? 0) ?> (<?= $pct_mat ?>%)</span>
                </div>
                <div class="rep-desglose-fila rep-desglose-sep">
                    <span>Coste materiales</span>
                    <span class="rep-desglose-valor rep-coste"><?= r_eur($financiero['coste_materiales'] ?? 0) ?></span>
                </div>
                <div class="rep-desglose-fila rep-desglose-total">
                    <span><strong>Margen bruto</strong></span>
                    <span class="rep-desglose-valor">
                        <strong><?= r_eur($financiero['margen_bruto'] ?? 0) ?></strong>
                        <small>(<?= r_pct($financiero['margen_pct'] ?? 0) ?>)</small>
                    </span>
                </div>
            </div>
        </div>

    </div>


    <!-- ══ FILA 3: Productividad mecánicos ════════════════════════ -->
    <div class="rep-card">
        <h3 class="rep-card-titulo">👨‍🔧 Productividad por mecánico</h3>
        <?php if (empty($productividad)): ?>
            <p class="rep-vacio">Sin datos de mecánicos en el período.</p>
        <?php else: ?>
            <div class="tabla-wrapper">
                <table class="rep-tabla">
                    <thead>
                        <tr>
                            <th>Mecánico</th>
                            <th class="col-num">Órdenes</th>
                            <th class="col-num">Tareas</th>
                            <th class="col-num">Horas trabajadas</th>
                            <th class="col-num">Media/tarea</th>
                            <th class="col-num">Eficiencia</th>
                            <th class="col-num">Ingresos generados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productividad as $mec):
                            $ef = (float)($mec['eficiencia_pct'] ?? 0);
                            $ef_clase = $ef >= 100 ? 'ef-alta' : ($ef >= 80 ? 'ef-media' : 'ef-baja');
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($mec['mecanico']) ?></strong></td>
                                <td class="col-num"><?= r_num($mec['ordenes_trabajadas']) ?></td>
                                <td class="col-num"><?= r_num($mec['tareas_completadas']) ?></td>
                                <td class="col-num"><?= number_format((float)$mec['horas_trabajadas'], 1, ',', '.') ?>h</td>
                                <td class="col-num"><?= r_min($mec['media_min_por_tarea']) ?></td>
                                <td class="col-num">
                                    <span class="ef-badge <?= $ef_clase ?>">
                                        <?= $ef > 0 ? r_pct($ef) : '—' ?>
                                    </span>
                                </td>
                                <td class="col-num"><?= r_eur($mec['ingresos_mano_obra']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Gráfico de barras: horas por mecánico -->
            <canvas id="graficoMecanicos" height="120" style="margin-top:1.5rem"></canvas>
        <?php endif; ?>
    </div>


    <!-- ══ FILA 4: Stock crítico + Valor stock ════════════════════ -->
    <div class="rep-grid-2">

        <!-- Stock crítico -->
        <div class="rep-card">
            <h3 class="rep-card-titulo">📦 Stock crítico</h3>

            <!-- Mini KPIs stock -->
            <div class="stock-mini-kpis">
                <div class="stock-mini-kpi agotado">
                    <span class="smk-num"><?= r_num($valor_stock['agotados'] ?? 0) ?></span>
                    <span class="smk-lbl">Agotados</span>
                </div>
                <div class="stock-mini-kpi critico">
                    <span class="smk-num"><?= r_num($valor_stock['criticos'] ?? 0) ?></span>
                    <span class="smk-lbl">Stock bajo</span>
                </div>
                <div class="stock-mini-kpi ok">
                    <span class="smk-num"><?= r_num($valor_stock['ok'] ?? 0) ?></span>
                    <span class="smk-lbl">Correctos</span>
                </div>
            </div>

            <?php if (empty($stock_critico)): ?>
                <p class="rep-vacio rep-vacio-ok">✓ Todo el stock está en niveles correctos.</p>
            <?php else: ?>
                <table class="rep-tabla rep-tabla-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="col-num">Stock</th>
                            <th class="col-num">Mínimo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock_critico as $s): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($s['nombre']) ?>
                                    <?php if ($s['referencia_sku']): ?>
                                        <small class="td-sku"><?= htmlspecialchars($s['referencia_sku']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="col-num <?= $s['cantidad_stock'] == 0 ? 'stock-cero' : 'stock-bajo' ?>">
                                    <strong><?= (int)$s['cantidad_stock'] ?></strong>
                                </td>
                                <td class="col-num text-muted"><?= (int)$s['alerta_stock_minimo'] ?></td>
                                <td>
                                    <?php if ($s['estado_stock'] === 'agotado'): ?>
                                        <span class="badge-stock badge-agotado">Agotado</span>
                                    <?php else: ?>
                                        <span class="badge-stock badge-bajo">Stock bajo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="index.php?action=stock&alerta_stock=1" class="rep-link-stock">
                    Ver todos en Stock →
                </a>
            <?php endif; ?>
        </div>

        <!-- Valor stock + Top productos -->
        <div class="rep-card">
            <h3 class="rep-card-titulo">📦 Valor del inventario</h3>

            <div class="rep-valor-stock-kpis">
                <div class="vsk-item">
                    <span class="vsk-val"><?= r_eur($valor_stock['valor_coste'] ?? 0) ?></span>
                    <span class="vsk-lbl">Valor a precio coste</span>
                </div>
                <div class="vsk-item">
                    <span class="vsk-val"><?= r_eur($valor_stock['valor_venta'] ?? 0) ?></span>
                    <span class="vsk-lbl">Valor a precio venta</span>
                </div>
                <div class="vsk-item vsk-margen">
                    <span class="vsk-val"><?= r_eur($valor_stock['margen_potencial'] ?? 0) ?></span>
                    <span class="vsk-lbl">Margen potencial</span>
                </div>
                <div class="vsk-item">
                    <span class="vsk-val"><?= r_num($valor_stock['total_referencias'] ?? 0) ?></span>
                    <span class="vsk-lbl">Referencias</span>
                </div>
                <div class="vsk-item">
                    <span class="vsk-val"><?= r_num($valor_stock['total_unidades'] ?? 0) ?></span>
                    <span class="vsk-lbl">Unidades totales</span>
                </div>
            </div>

            <?php if (!empty($productos_mas_usados)): ?>
                <h4 class="rep-subtitulo-seccion">Top 10 materiales consumidos</h4>
                <table class="rep-tabla rep-tabla-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="col-num">Unidades</th>
                            <th class="col-num">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos_mas_usados as $idx => $pr): ?>
                            <tr>
                                <td>
                                    <span class="top-rank"><?= $idx + 1 ?></span>
                                    <?= htmlspecialchars($pr['nombre']) ?>
                                </td>
                                <td class="col-num"><?= r_num($pr['unidades_consumidas']) ?></td>
                                <td class="col-num"><?= r_eur($pr['ingresos_generados']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

</div><!-- /.rep-container -->


<!-- ══ CHARTS ════════════════════════════════════════════════════ -->
<script>
// Datos PHP → JS
const dataIngresos   = <?= json_encode($ingresos_periodo) ?>;
const dataEstados    = <?= json_encode($ordenes_por_estado) ?>;
const dataThroughput = <?= json_encode($throughput) ?>;
const dataMecanicos  = <?= json_encode($productividad) ?>;

const colores = {
    azul:    '#2563eb',
    azulClaro:'#93c5fd',
    verde:   '#16a34a',
    verdeClaro:'#86efac',
    rojo:    '#dc2626',
    amarillo:'#f59e0b',
    morado:  '#7c3aed',
    gris:    '#6b7280',
};

const coloresEstado = {
    'recibido':       '#93c5fd',
    'diagnosticando': '#fde68a',
    'presupuestado':  '#c4b5fd',
    'en_reparacion':  '#fdba74',
    'listo':          '#86efac',
    'facturado':      '#d1d5db',
};

const optsBase = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales:  { y: { beginAtZero: true, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } }
};

// ── Gráfico ingresos vs costes ─────────────────
if (dataIngresos.length > 0) {
    new Chart(document.getElementById('graficoIngresos'), {
        type: 'bar',
        data: {
            labels:   dataIngresos.map(d => d.periodo),
            datasets: [
                {
                    label: 'Ingresos',
                    data:  dataIngresos.map(d => d.ingresos),
                    backgroundColor: colores.azul,
                    borderRadius: 4,
                },
                {
                    label: 'Costes materiales',
                    data:  dataIngresos.map(d => d.costes),
                    backgroundColor: colores.rojo + '99',
                    borderRadius: 4,
                },
            ],
        },
        options: {
            ...optsBase,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' €'
                    }
                }
            }
        }
    });
}

// ── Gráfico estados (donut) ────────────────────
if (dataEstados.length > 0) {
    new Chart(document.getElementById('graficoEstados'), {
        type: 'doughnut',
        data: {
            labels:   dataEstados.map(d => d.estado),
            datasets: [{
                data:            dataEstados.map(d => d.total),
                backgroundColor: dataEstados.map(d => coloresEstado[d.estado] ?? colores.gris),
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { display: true, position: 'right' },
                tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' órdenes' } }
            }
        }
    });
}

// ── Gráfico throughput ─────────────────────────
if (dataThroughput.length > 0) {
    new Chart(document.getElementById('graficoThroughput'), {
        type: 'line',
        data: {
            labels: dataThroughput.map(d => d.periodo),
            datasets: [{
                label: 'Órdenes facturadas',
                data:  dataThroughput.map(d => d.ordenes_facturadas),
                borderColor:     colores.verde,
                backgroundColor: colores.verde + '22',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: colores.verde,
            }],
        },
        options: {
            ...optsBase,
            plugins: { legend: { display: false } }
        }
    });
}

// ── Gráfico horas por mecánico ─────────────────
if (dataMecanicos.length > 0) {
    new Chart(document.getElementById('graficoMecanicos'), {
        type: 'bar',
        data: {
            labels: dataMecanicos.map(d => d.mecanico),
            datasets: [
                {
                    label: 'Horas trabajadas',
                    data:  dataMecanicos.map(d => d.horas_trabajadas),
                    backgroundColor: colores.azul,
                    borderRadius: 4,
                },
                {
                    label: 'Eficiencia %',
                    data:  dataMecanicos.map(d => d.eficiencia_pct),
                    backgroundColor: colores.verde + 'aa',
                    borderRadius: 4,
                    yAxisID: 'yEf',
                },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true, position: 'top' } },
            scales: {
                x:   { grid: { display: false } },
                y:   { beginAtZero: true, title: { display: true, text: 'Horas' } },
                yEf: { beginAtZero: true, position: 'right', title: { display: true, text: 'Eficiencia %' }, grid: { display: false } }
            }
        }
    });
}
</script>

<style>
/* ── Layout general ── */
.rep-container { padding: 1.5rem; max-width: 1280px; margin: 0 auto; }
.rep-header    { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.2rem; }
.rep-header h2 { margin: 0 0 .2rem; font-size: 1.5rem; color: #111827; }
.rep-subtitulo { margin: 0; font-size: .88rem; color: #9ca3af; }

/* ── Filtros ── */
.rep-filtros        { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1rem 1.2rem; margin-bottom: 1.4rem; }
.rep-filtros-fila   { display: flex; flex-wrap: wrap; gap: 1.2rem; align-items: flex-start; }
.rep-filtro-grupo   { display: flex; flex-direction: column; gap: .4rem; }
.rep-filtro-label   { font-size: .78rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; }
.rep-periodo-btns   { display: flex; flex-wrap: wrap; gap: .4rem; }
.rep-periodo-btn    { padding: .3rem .75rem; border: 1.5px solid #e5e7eb; border-radius: 20px; background: #f9fafb; font-size: .82rem; cursor: pointer; color: #374151; transition: all .15s; }
.rep-periodo-btn.activo, .rep-periodo-btn:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
.rep-fechas-custom  { flex-direction: row; align-items: center; flex-wrap: wrap; gap: .6rem; }
.rep-input-fecha    { padding: .4rem .7rem; border: 1.5px solid #d1d5db; border-radius: 6px; font-size: .88rem; }
.rep-rango-activo   { margin: .6rem 0 0; font-size: .83rem; color: #6b7280; }
.btn-aplicar        { padding: .4rem .9rem; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: .88rem; }

/* ── KPI cards ── */
.rep-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.4rem; }
.kpi-card     { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.1rem 1.2rem; display: flex; flex-direction: column; gap: .3rem; transition: box-shadow .15s; }
.kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
.kpi-icono    { font-size: 1.4rem; }
.kpi-valor    { font-size: 1.45rem; font-weight: 700; color: #111827; line-height: 1.1; }
.kpi-label    { font-size: .78rem; color: #9ca3af; }
.kpi-label small { font-size: .9em; }
.kpi-ingresos  { border-left: 4px solid #2563eb; }
.kpi-margen    { border-left: 4px solid #16a34a; }
.kpi-ticket    { border-left: 4px solid #7c3aed; }
.kpi-ordenes   { border-left: 4px solid #f59e0b; }
.kpi-tiempo    { border-left: 4px solid #06b6d4; }

/* ── Grids ── */
.rep-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem; }
@media (max-width: 780px) { .rep-grid-2 { grid-template-columns: 1fr; } }

/* ── Cards ── */
.rep-card        { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.2rem 1.3rem; margin-bottom: 1.2rem; }
.rep-card-titulo { font-size: 1rem; font-weight: 700; color: #111827; margin: 0 0 1rem; padding-bottom: .6rem; border-bottom: 1px solid #f3f4f6; }
.rep-vacio       { color: #9ca3af; font-size: .9rem; text-align: center; padding: 1.5rem 0; }
.rep-vacio-ok    { color: #16a34a; }

/* ── Leyenda estados ── */
.rep-leyenda-estados { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .8rem; }
.rep-leyenda-item    { display: flex; align-items: center; gap: .4rem; font-size: .82rem; }
.badge-estado        { padding: .2rem .6rem; border-radius: 10px; font-size: .75rem; font-weight: 600; }
.badge-recibido       { background: #dbeafe; color: #1d4ed8; }
.badge-diagnosticando { background: #fef9c3; color: #92400e; }
.badge-presupuestado  { background: #ede9fe; color: #5b21b6; }
.badge-en-reparacion  { background: #ffedd5; color: #c2410c; }
.badge-listo          { background: #dcfce7; color: #166534; }
.badge-facturado      { background: #f3f4f6; color: #374151; }

/* ── Desglose financiero ── */
.rep-desglose       { display: flex; flex-direction: column; gap: .7rem; }
.rep-desglose-fila  { display: flex; align-items: center; gap: .8rem; font-size: .88rem; color: #374151; }
.rep-desglose-fila > span:first-child { min-width: 100px; }
.rep-desglose-barra-wrap { flex: 1; background: #f3f4f6; border-radius: 99px; height: 8px; overflow: hidden; }
.rep-desglose-barra      { height: 100%; background: #2563eb; border-radius: 99px; transition: width .4s; }
.rep-barra-mat           { background: #7c3aed; }
.rep-desglose-valor      { min-width: 140px; text-align: right; font-size: .83rem; color: #6b7280; }
.rep-desglose-sep        { border-top: 1px solid #f3f4f6; padding-top: .5rem; }
.rep-coste               { color: #dc2626; }
.rep-desglose-total      { border-top: 2px solid #e5e7eb; padding-top: .6rem; }

/* ── Tablas ── */
.tabla-wrapper  { overflow-x: auto; }
.rep-tabla      { width: 100%; border-collapse: collapse; font-size: .88rem; }
.rep-tabla th   { background: #f8fafc; padding: .6rem .9rem; text-align: left; font-size: .78rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid #e5e7eb; }
.rep-tabla td   { padding: .6rem .9rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.rep-tabla tbody tr:last-child td { border-bottom: none; }
.rep-tabla tbody tr:hover { background: #f9fafb; }
.rep-tabla-sm td, .rep-tabla-sm th { padding: .45rem .75rem; }
.col-num        { text-align: right; }
.text-muted     { color: #9ca3af; }
.td-sku         { display: block; font-family: monospace; font-size: .75rem; color: #9ca3af; }

/* Eficiencia badges */
.ef-badge   { padding: .2rem .55rem; border-radius: 8px; font-size: .78rem; font-weight: 600; }
.ef-alta    { background: #dcfce7; color: #166534; }
.ef-media   { background: #fef9c3; color: #92400e; }
.ef-baja    { background: #fee2e2; color: #991b1b; }

/* ── Stock mini KPIs ── */
.stock-mini-kpis   { display: flex; gap: .8rem; margin-bottom: 1rem; }
.stock-mini-kpi    { flex: 1; text-align: center; padding: .6rem; border-radius: 8px; }
.stock-mini-kpi.agotado { background: #fee2e2; }
.stock-mini-kpi.critico { background: #fef3c7; }
.stock-mini-kpi.ok      { background: #dcfce7; }
.smk-num { display: block; font-size: 1.4rem; font-weight: 700; }
.smk-lbl { font-size: .75rem; color: #6b7280; }
.agotado .smk-num { color: #dc2626; }
.critico .smk-num { color: #d97706; }
.ok .smk-num      { color: #16a34a; }

.stock-cero  { color: #dc2626; }
.stock-bajo  { color: #d97706; }

.badge-stock    { display: inline-block; padding: .2rem .55rem; border-radius: 8px; font-size: .75rem; font-weight: 600; }
.badge-agotado  { background: #fee2e2; color: #991b1b; }
.badge-bajo     { background: #fef3c7; color: #92400e; }

.rep-link-stock { display: inline-block; margin-top: .8rem; font-size: .85rem; color: #2563eb; text-decoration: none; }
.rep-link-stock:hover { text-decoration: underline; }

/* ── Valor stock ── */
.rep-valor-stock-kpis { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; margin-bottom: 1.2rem; }
.vsk-item    { background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 8px; padding: .7rem .9rem; }
.vsk-val     { display: block; font-size: 1.1rem; font-weight: 700; color: #111827; }
.vsk-lbl     { font-size: .75rem; color: #9ca3af; }
.vsk-margen .vsk-val { color: #16a34a; }

/* ── Top rank ── */
.top-rank { display: inline-block; width: 20px; height: 20px; border-radius: 50%; background: #eff6ff; color: #2563eb; font-size: .72rem; font-weight: 700; text-align: center; line-height: 20px; margin-right: .3rem; }

.rep-subtitulo-seccion { font-size: .88rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin: 1rem 0 .5rem; }

/* ── Btn volver (factura) ── */
.btn  { display: inline-block; padding: .5rem 1.1rem; border-radius: 7px; font-size: .9rem; cursor: pointer; border: none; text-decoration: none; font-weight: 500; }
.btn-volver { background: #e5e7eb; color: #374151; }
</style>