<?php
// ─────────────────────────────────────────────
// Vista: Reportes y métricas del taller
// Variables: $periodo, $agrupacion, $fecha_desde, $fecha_hasta,
//   $financiero, $ingresos_periodo, $ordenes_estado, $tiempo_medio,
//   $precision_ia, $presupuestos, $productividad,
//   $stock_critico, $valor_stock, $top_materiales, $backlog
// ─────────────────────────────────────────────

function r_eur($n)  { return $n === null ? '—' : number_format((float)$n, 2, ',', '.') . ' €'; }
function r_pct($n)  { return $n === null ? '—' : number_format((float)$n, 1, ',', '.') . '%'; }
function r_num($n)  { return number_format((float)$n, 0, ',', '.'); }
function r_min($m)  {
    if ($m === null || (int)$m === 0) return '—';
    $h = intdiv((int)$m, 60); $min = (int)$m % 60;
    return $h > 0 ? "{$h}h {$min}min" : "{$min}min";
}
function r_fecha($d) {
    if (!$d) return '—';
    $ts = strtotime($d);
    return $ts ? date('d/m/Y', $ts) : '—';
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
    'hoy'           => 'Hoy',
    '7dias'         => 'Últimos 7 días',
    '30dias'        => 'Últimos 30 días',
    'mes_actual'    => 'Este mes',
    'mes_anterior'  => 'Mes anterior',
    'anio_actual'   => 'Este año',
    'personalizado' => 'Personalizado',
];

$agrupaciones = [
    'dia'    => 'Día',
    'semana' => 'Semana',
    'mes'    => 'Mes',
];
?>
<!-- Chart.js desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="rep-container">

    <!-- Cabecera -->
    <div class="rep-header">
        <div>
            <h2>Reportes</h2>
            <p class="rep-subtitulo">Métricas del taller</p>
        </div>
        <p class="rep-rango-activo">
            Datos del <strong><?= r_fecha($fecha_desde) ?></strong>
            al <strong><?= r_fecha($fecha_hasta) ?></strong>
        </p>
    </div>

    <!-- Filtros -->
    <form method="GET" action="index.php" class="rep-filtros">
        <input type="hidden" name="action" value="reportes">

        <div class="rep-filtro-grupo">
            <label class="rep-filtro-label">Período</label>
            <select name="periodo" onchange="this.form.submit()">
                <?php foreach ($periodos_opciones as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= $periodo === $val ? 'selected' : '' ?>>
                        <?= $lbl ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($periodo === 'personalizado'): ?>
            <div class="rep-filtro-grupo">
                <label class="rep-filtro-label">Desde</label>
                <input type="date" name="desde" value="<?= htmlspecialchars($fecha_desde) ?>">
            </div>
            <div class="rep-filtro-grupo">
                <label class="rep-filtro-label">Hasta</label>
                <input type="date" name="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
            </div>
        <?php endif; ?>

        <div class="rep-filtro-grupo">
            <label class="rep-filtro-label">Agrupar por</label>
            <select name="agrupacion" onchange="this.form.submit()">
                <?php foreach ($agrupaciones as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= $agrupacion === $val ? 'selected' : '' ?>>
                        <?= $lbl ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="rep-filtro-grupo rep-filtro-acciones">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="index.php?action=reportes" class="btn btn-cancelar">Limpiar</a>
        </div>
    </form>


    <!-- KPIs financieros principales -->
    <div class="rep-kpi-grid">
        <div class="kpi-card kpi-ingresos">
            <span class="kpi-valor"><?= r_eur($financiero['ingresos_totales']) ?></span>
            <span class="kpi-label">Ingresos facturados</span>
        </div>
        <div class="kpi-card kpi-margen">
            <span class="kpi-valor"><?= r_eur($financiero['margen_bruto']) ?></span>
            <span class="kpi-label">Margen bruto <small>(<?= r_pct($financiero['margen_pct']) ?>)</small></span>
        </div>
        <div class="kpi-card kpi-ticket">
            <span class="kpi-valor"><?= r_eur($financiero['ticket_medio']) ?></span>
            <span class="kpi-label">Ticket medio</span>
        </div>
        <div class="kpi-card kpi-ordenes">
            <span class="kpi-valor"><?= r_num($financiero['total_ordenes']) ?></span>
            <span class="kpi-label">Órdenes facturadas</span>
        </div>
        <div class="kpi-card kpi-tiempo">
            <span class="kpi-valor"><?= r_min($tiempo_medio['media_min_por_orden']) ?></span>
            <span class="kpi-label">Tiempo medio por orden</span>
        </div>
    </div>


    <!-- Fila 1: Evolución ingresos + Distribución estados -->
    <div class="rep-grid-2">

        <div class="rep-card">
            <h3 class="rep-card-titulo">Evolución de ingresos (por <?= $agrupaciones[$agrupacion] ?>)</h3>
            <?php if (empty($ingresos_periodo)): ?>
                <p class="rep-vacio">Sin órdenes facturadas en el período.</p>
            <?php else: ?>
                <canvas id="graficoIngresos" height="220"></canvas>
            <?php endif; ?>
        </div>

        <div class="rep-card">
            <h3 class="rep-card-titulo">Órdenes por estado <small>(creadas en el período)</small></h3>
            <?php if (empty($ordenes_estado)): ?>
                <p class="rep-vacio">Sin órdenes creadas en el período.</p>
            <?php else: ?>
                <canvas id="graficoEstados" height="220"></canvas>
                <div class="rep-leyenda-estados">
                    <?php foreach ($ordenes_estado as $e): ?>
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


    <!-- Fila 2: Desglose financiero + Precisión IA -->
    <div class="rep-grid-2">

        <div class="rep-card">
            <h3 class="rep-card-titulo">Desglose financiero</h3>
            <table class="rep-tabla">
                <tbody>
                    <tr>
                        <td>Ingresos mano de obra</td>
                        <td class="col-num"><?= r_eur($financiero['ingresos_mano_obra']) ?></td>
                    </tr>
                    <tr>
                        <td>Ingresos materiales</td>
                        <td class="col-num"><?= r_eur($financiero['ingresos_materiales']) ?></td>
                    </tr>
                    <tr>
                        <td>Coste de materiales</td>
                        <td class="col-num rep-coste">− <?= r_eur($financiero['coste_materiales']) ?></td>
                    </tr>
                    <tr class="rep-desglose-total">
                        <td><strong>Margen bruto</strong></td>
                        <td class="col-num"><strong><?= r_eur($financiero['margen_bruto']) ?></strong>
                            <small>(<?= r_pct($financiero['margen_pct']) ?>)</small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="rep-card">
            <h3 class="rep-card-titulo">Precisión del presupuesto IA</h3>
            <?php if ($precision_ia['ordenes_evaluadas'] === 0): ?>
                <p class="rep-vacio">Sin órdenes facturadas con presupuesto IA en el período.</p>
            <?php else: ?>
                <table class="rep-tabla">
                    <tbody>
                        <tr>
                            <td>Órdenes evaluadas</td>
                            <td class="col-num"><?= r_num($precision_ia['ordenes_evaluadas']) ?></td>
                        </tr>
                        <tr>
                            <td>Estimado medio</td>
                            <td class="col-num"><?= r_eur($precision_ia['media_estimado']) ?></td>
                        </tr>
                        <tr>
                            <td>Real medio</td>
                            <td class="col-num"><?= r_eur($precision_ia['media_real']) ?></td>
                        </tr>
                        <tr>
                            <td>Desviación media</td>
                            <?php $d = $precision_ia['desviacion_media']; ?>
                            <td class="col-num <?= $d > 0 ? 'rep-dif-alza' : ($d < 0 ? 'rep-dif-baja' : '') ?>">
                                <?= ($d > 0 ? '+' : '') . r_eur($d) ?>
                            </td>
                        </tr>
                        <tr class="rep-desglose-total">
                            <td><strong>Error medio absoluto</strong></td>
                            <td class="col-num"><strong><?= r_pct($precision_ia['error_pct_medio']) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>


    <!-- Fila 3: Presupuestos aceptación + Backlog actual -->
    <div class="rep-grid-2">

        <div class="rep-card">
            <h3 class="rep-card-titulo">Aceptación de presupuestos <small>(tareas)</small></h3>
            <?php if ($presupuestos['total'] === 0): ?>
                <p class="rep-vacio">Sin tareas presupuestadas en el período.</p>
            <?php else: ?>
                <table class="rep-tabla">
                    <tbody>
                        <tr>
                            <td>Tareas aceptadas</td>
                            <td class="col-num"><?= r_num($presupuestos['aceptadas']) ?></td>
                        </tr>
                        <tr>
                            <td>Tareas rechazadas</td>
                            <td class="col-num"><?= r_num($presupuestos['rechazadas']) ?></td>
                        </tr>
                        <tr class="rep-desglose-total">
                            <td><strong>Tasa de aceptación</strong></td>
                            <td class="col-num"><strong><?= r_pct($presupuestos['tasa_pct']) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="rep-card">
            <h3 class="rep-card-titulo">Cartera actual <small>(órdenes abiertas)</small></h3>
            <?php if (empty($backlog)): ?>
                <p class="rep-vacio rep-vacio-ok">No hay órdenes abiertas.</p>
            <?php else: ?>
                <table class="rep-tabla">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th class="col-num">Órdenes</th>
                            <th class="col-num">Valor estimado</th>
                            <th class="col-num">Tiempo estimado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $tot_ord = 0; $tot_val = 0; $tot_min = 0;
                            foreach ($backlog as $b) {
                                $tot_ord += (int)$b['total'];
                                $tot_val += (float)$b['valor_estimado'];
                                $tot_min += (int)$b['minutos_estimados'];
                            }
                        ?>
                        <?php foreach ($backlog as $b): ?>
                            <tr>
                                <td>
                                    <span class="badge-estado badge-<?= str_replace('_','-',$b['estado']) ?>">
                                        <?= $etiquetas_estado[$b['estado']] ?? $b['estado'] ?>
                                    </span>
                                </td>
                                <td class="col-num"><?= r_num($b['total']) ?></td>
                                <td class="col-num"><?= r_eur($b['valor_estimado']) ?></td>
                                <td class="col-num"><?= r_min($b['minutos_estimados']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="rep-desglose-total">
                            <td><strong>Total</strong></td>
                            <td class="col-num"><strong><?= r_num($tot_ord) ?></strong></td>
                            <td class="col-num"><strong><?= r_eur($tot_val) ?></strong></td>
                            <td class="col-num"><strong><?= r_min($tot_min) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>


    <!-- Productividad equipo -->
    <div class="rep-card">
        <h3 class="rep-card-titulo">Productividad del equipo</h3>
        <?php if (empty($productividad)): ?>
            <p class="rep-vacio">Sin actividad en el período seleccionado.</p>
        <?php else: ?>
            <div class="tabla-wrapper">
                <table class="rep-tabla">
                    <thead>
                        <tr>
                            <th>Mecánico</th>
                            <th class="col-num">Órdenes</th>
                            <th class="col-num">Tareas</th>
                            <th class="col-num">Horas</th>
                            <th class="col-num">Media/tarea</th>
                            <th class="col-num">Eficiencia</th>
                            <th class="col-num">Ingresos mano de obra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productividad as $mec):
                            $ef = (float)($mec['eficiencia_pct'] ?? 0);
                            $ef_clase = $ef >= 100 ? 'ef-alta' : ($ef >= 80 ? 'ef-media' : 'ef-baja');
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($mec['mecanico']) ?></strong>
                                    <?php if (($mec['rol'] ?? '') === 'jefe'): ?>
                                        <small> — jefe</small>
                                    <?php endif; ?>
                                </td>
                                <td class="col-num"><?= r_num($mec['ordenes_trabajadas']) ?></td>
                                <td class="col-num"><?= r_num($mec['tareas_completadas']) ?></td>
                                <td class="col-num"><?= number_format((float)$mec['horas_trabajadas'], 1, ',', '.') ?> h</td>
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
            <canvas id="graficoMecanicos" height="120" style="margin-top:1.5rem"></canvas>
        <?php endif; ?>
    </div>


    <!-- Fila 4: Stock crítico + Top materiales -->
    <div class="rep-grid-2">

        <div class="rep-card">
            <h3 class="rep-card-titulo">Stock crítico</h3>

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
                <p class="rep-vacio rep-vacio-ok">Todo el stock está en niveles correctos.</p>
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
                    Ver todos en Stock
                </a>
            <?php endif; ?>
        </div>

        <div class="rep-card">
            <h3 class="rep-card-titulo">Valor del inventario</h3>

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
                    <span class="vsk-val"><?= r_num($valor_stock['total_unidades'] ?? 0) ?></span>
                    <span class="vsk-lbl">Unidades totales</span>
                </div>
            </div>

            <?php if (!empty($top_materiales)): ?>
                <h4 class="rep-subtitulo-seccion">Top 10 materiales del período</h4>
                <table class="rep-tabla rep-tabla-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="col-num">Uds.</th>
                            <th class="col-num">Ingresos</th>
                            <th class="col-num">Margen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_materiales as $idx => $pr): ?>
                            <tr>
                                <td>
                                    <span class="top-rank"><?= $idx + 1 ?></span>
                                    <?= htmlspecialchars($pr['nombre']) ?>
                                </td>
                                <td class="col-num"><?= r_num($pr['unidades_consumidas']) ?></td>
                                <td class="col-num"><?= r_eur($pr['ingresos_generados']) ?></td>
                                <td class="col-num"><?= r_eur($pr['margen']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.rep-container -->


<!-- Gráficos -->
<script>
(function() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no cargado');
        return;
    }

    const dataIngresos  = <?= json_encode($ingresos_periodo) ?>;
    const dataEstados   = <?= json_encode($ordenes_estado) ?>;
    const dataMecanicos = <?= json_encode($productividad) ?>;

    const etiquetasEstado = <?= json_encode($etiquetas_estado) ?>;

    const colorEstado = {
        recibido: '#93c5fd', diagnosticando: '#fde68a', presupuestado: '#c4b5fd',
        en_reparacion: '#fdba74', listo: '#86efac', facturado: '#d1d5db'
    };

    // Gráfico: ingresos vs costes
    const elIngresos = document.getElementById('graficoIngresos');
    if (elIngresos && dataIngresos.length > 0) {
        new Chart(elIngresos, {
            type: 'bar',
            data: {
                labels: dataIngresos.map(d => d.periodo),
                datasets: [
                    {
                        label: 'Ingresos',
                        data: dataIngresos.map(d => Number(d.ingresos)),
                        backgroundColor: '#2563eb', borderRadius: 4
                    },
                    {
                        label: 'Coste materiales',
                        data: dataIngresos.map(d => Number(d.coste_materiales)),
                        backgroundColor: 'rgba(220, 38, 38, 0.7)', borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' €' }
                    }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // Gráfico: distribución por estado (donut)
    const elEstados = document.getElementById('graficoEstados');
    if (elEstados && dataEstados.length > 0) {
        new Chart(elEstados, {
            type: 'doughnut',
            data: {
                labels: dataEstados.map(d => etiquetasEstado[d.estado] || d.estado),
                datasets: [{
                    data: dataEstados.map(d => Number(d.total)),
                    backgroundColor: dataEstados.map(d => colorEstado[d.estado] || '#9ca3af'),
                    borderWidth: 2, borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '60%',
                plugins: {
                    legend: { display: true, position: 'right' },
                    tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' órdenes' } }
                }
            }
        });
    }

    // Gráfico: productividad de mecánicos (horas + eficiencia)
    const elMec = document.getElementById('graficoMecanicos');
    if (elMec && dataMecanicos.length > 0) {
        new Chart(elMec, {
            type: 'bar',
            data: {
                labels: dataMecanicos.map(d => d.mecanico),
                datasets: [
                    {
                        label: 'Horas trabajadas',
                        data: dataMecanicos.map(d => Number(d.horas_trabajadas)),
                        backgroundColor: '#2563eb', borderRadius: 4, yAxisID: 'yHoras'
                    },
                    {
                        label: 'Eficiencia %',
                        data: dataMecanicos.map(d => Number(d.eficiencia_pct) || 0),
                        backgroundColor: 'rgba(22, 163, 74, 0.7)', borderRadius: 4, yAxisID: 'yEf'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true, position: 'top' } },
                scales: {
                    yHoras: { beginAtZero: true, position: 'left',  title: { display: true, text: 'Horas' } },
                    yEf:    { beginAtZero: true, position: 'right', title: { display: true, text: 'Eficiencia %' }, grid: { display: false } }
                }
            }
        });
    }
})();
</script>

<style>
.rep-container { padding: 1.5rem; max-width: 1280px; margin: 0 auto; }
.rep-header    { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 1rem; }
.rep-header h2 { margin: 0 0 .2rem; font-size: 1.5rem; color: #111827; }
.rep-subtitulo { margin: 0; font-size: .88rem; color: #9ca3af; }
.rep-rango-activo { margin: 0; font-size: .85rem; color: #4b5563; }

.rep-filtros        { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1rem 1.2rem; margin-bottom: 1.4rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
.rep-filtro-grupo   { display: flex; flex-direction: column; gap: .35rem; }
.rep-filtro-label   { font-size: .76rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
.rep-filtro-grupo select, .rep-filtro-grupo input[type="date"] { padding: .4rem .7rem; border: 1.5px solid #d1d5db; border-radius: 6px; font-size: .88rem; background: #fff; }
.rep-filtro-acciones { flex-direction: row; gap: .5rem; }

.btn            { display: inline-block; padding: .45rem 1rem; border-radius: 6px; font-size: .88rem; border: none; cursor: pointer; text-decoration: none; font-weight: 500; }
.btn-primary    { background: #2563eb; color: #fff; }
.btn-cancelar   { background: #e5e7eb; color: #374151; }

.rep-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.4rem; }
.kpi-card     { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.1rem 1.2rem; display: flex; flex-direction: column; gap: .3rem; }
.kpi-valor    { font-size: 1.45rem; font-weight: 700; color: #111827; line-height: 1.1; }
.kpi-label    { font-size: .78rem; color: #9ca3af; }
.kpi-label small { font-size: .9em; color: #6b7280; }
.kpi-ingresos { border-left: 4px solid #2563eb; }
.kpi-margen   { border-left: 4px solid #16a34a; }
.kpi-ticket   { border-left: 4px solid #7c3aed; }
.kpi-ordenes  { border-left: 4px solid #f59e0b; }
.kpi-tiempo   { border-left: 4px solid #06b6d4; }

.rep-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem; }
@media (max-width: 880px) { .rep-grid-2 { grid-template-columns: 1fr; } }

.rep-card        { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.1rem 1.2rem; margin-bottom: 1.2rem; }
.rep-card-titulo { font-size: 1rem; font-weight: 700; color: #111827; margin: 0 0 1rem; padding-bottom: .55rem; border-bottom: 1px solid #f3f4f6; }
.rep-card-titulo small { color: #9ca3af; font-weight: 400; font-size: .82em; }
.rep-vacio       { color: #9ca3af; font-size: .9rem; text-align: center; padding: 1.2rem 0; }
.rep-vacio-ok    { color: #16a34a; }

.rep-leyenda-estados { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .8rem; }
.rep-leyenda-item    { display: flex; align-items: center; gap: .4rem; font-size: .82rem; }
.badge-estado        { padding: .2rem .55rem; border-radius: 10px; font-size: .75rem; font-weight: 600; }
.badge-recibido       { background: #dbeafe; color: #1d4ed8; }
.badge-diagnosticando { background: #fef9c3; color: #92400e; }
.badge-presupuestado  { background: #ede9fe; color: #5b21b6; }
.badge-en-reparacion  { background: #ffedd5; color: #c2410c; }
.badge-listo          { background: #dcfce7; color: #166534; }
.badge-facturado      { background: #f3f4f6; color: #374151; }

.tabla-wrapper  { overflow-x: auto; }
.rep-tabla      { width: 100%; border-collapse: collapse; font-size: .88rem; }
.rep-tabla th   { background: #f8fafc; padding: .55rem .8rem; text-align: left; font-size: .76rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid #e5e7eb; }
.rep-tabla td   { padding: .55rem .8rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.rep-tabla tbody tr:last-child td { border-bottom: none; }
.rep-tabla-sm td, .rep-tabla-sm th { padding: .4rem .7rem; }
.col-num        { text-align: right; }
.text-muted     { color: #9ca3af; }
.td-sku         { display: block; font-family: monospace; font-size: .75rem; color: #9ca3af; }

.rep-desglose-total td { border-top: 2px solid #e5e7eb; padding-top: .7rem; }
.rep-coste      { color: #dc2626; }
.rep-dif-alza   { color: #dc2626; }
.rep-dif-baja   { color: #16a34a; }

.ef-badge   { padding: .2rem .55rem; border-radius: 8px; font-size: .78rem; font-weight: 600; }
.ef-alta    { background: #dcfce7; color: #166534; }
.ef-media   { background: #fef9c3; color: #92400e; }
.ef-baja    { background: #fee2e2; color: #991b1b; }

.stock-mini-kpis   { display: flex; gap: .8rem; margin-bottom: 1rem; }
.stock-mini-kpi    { flex: 1; text-align: center; padding: .55rem; border-radius: 8px; }
.stock-mini-kpi.agotado { background: #fee2e2; }
.stock-mini-kpi.critico { background: #fef3c7; }
.stock-mini-kpi.ok      { background: #dcfce7; }
.smk-num { display: block; font-size: 1.3rem; font-weight: 700; }
.smk-lbl { font-size: .73rem; color: #6b7280; }
.agotado .smk-num { color: #dc2626; }
.critico .smk-num { color: #d97706; }
.ok      .smk-num { color: #16a34a; }

.stock-cero { color: #dc2626; }
.stock-bajo { color: #d97706; }

.badge-stock    { display: inline-block; padding: .2rem .55rem; border-radius: 8px; font-size: .75rem; font-weight: 600; }
.badge-agotado  { background: #fee2e2; color: #991b1b; }
.badge-bajo     { background: #fef3c7; color: #92400e; }

.rep-link-stock { display: inline-block; margin-top: .8rem; font-size: .85rem; color: #2563eb; text-decoration: none; }
.rep-link-stock:hover { text-decoration: underline; }

.rep-valor-stock-kpis { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; margin-bottom: 1rem; }
.vsk-item   { background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 8px; padding: .6rem .8rem; }
.vsk-val    { display: block; font-size: 1.05rem; font-weight: 700; color: #111827; }
.vsk-lbl    { font-size: .74rem; color: #9ca3af; }
.vsk-margen .vsk-val { color: #16a34a; }

.top-rank { display: inline-block; width: 20px; height: 20px; border-radius: 50%; background: #eff6ff; color: #2563eb; font-size: .72rem; font-weight: 700; text-align: center; line-height: 20px; margin-right: .3rem; }
.rep-subtitulo-seccion { font-size: .84rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin: 1rem 0 .5rem; }
</style>
