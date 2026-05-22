<?php
    require_once __DIR__ . '/m_conecta.php';

    // ─────────────────────────────────────────────────────────────────
    // Helper: WHERE de fechas. $col debe ser un nombre de columna ya
    // saneado (literal en código, no parámetro del usuario).
    // ─────────────────────────────────────────────────────────────────
    function _r_rango($col, $desde, $hasta) {
        $sql = '';
        $par = [];
        $tip = '';

        if ($desde) {
            $sql  .= " AND {$col} >= ?";
            $par[] = $desde . ' 00:00:00';
            $tip  .= 's';
        }
        if ($hasta) {
            $sql  .= " AND {$col} <= ?";
            $par[] = $hasta . ' 23:59:59';
            $tip  .= 's';
        }
        return ['sql' => $sql, 'par' => $par, 'tip' => $tip];
    }

    // ─────────────────────────────────────────────────────────────────
    // Formato MySQL para agrupar por día / semana / mes
    // ─────────────────────────────────────────────────────────────────
    function _r_formatoFecha($agrupacion) {
        switch ($agrupacion) {
            case 'semana': return '%x-S%v';   // ej. 2026-S21 (ISO semana)
            case 'mes':    return '%Y-%m';
            default:       return '%Y-%m-%d'; // día
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // KPIs FINANCIEROS — solo órdenes en estado 'facturado'
    // Filtra por fecha de facturación (con fallback a fecha_creacion
    // para órdenes legacy facturadas antes del ALTER).
    // ─────────────────────────────────────────────────────────────────
    function r_kpis_financieros($taller_id, $desde, $hasta) {
        $conn = conectaBD();
        $r = _r_rango('COALESCE(ot.facturado_en, ot.fecha_creacion)', $desde, $hasta);

        $sql = "SELECT
                    COUNT(DISTINCT ot.id) AS total_ordenes,
                    ROUND(COALESCE(SUM(
                        COALESCE(ta.duracion_real_minutos, 0)/60.0 * t.tarifa_hora_base
                    ), 0), 2)                                                       AS ingresos_mano_obra,
                    ROUND(COALESCE(SUM(rt.cantidad * rt.precio_unidad_momento), 0), 2) AS ingresos_materiales,
                    ROUND(COALESCE(SUM(rt.cantidad * p.precio_compra), 0), 2)          AS coste_materiales
                FROM ordenes_trabajo ot
                INNER JOIN talleres t          ON ot.taller_id = t.id
                LEFT  JOIN tareas_asignadas ta ON ta.orden_trabajo_id = ot.id
                                              AND ta.estado <> 'rechazada'
                LEFT  JOIN repuestos_tarea rt  ON rt.tarea_asignada_id = ta.id
                LEFT  JOIN productos p         ON rt.producto_id = p.id
                WHERE ot.taller_id = ?
                  AND ot.estado    = 'facturado'
                  {$r['sql']}";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i' . $r['tip'], $taller_id, ...$r['par']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        $conn->close();

        $row['total_ordenes']       = (int)($row['total_ordenes']       ?? 0);
        $row['ingresos_mano_obra']  = (float)($row['ingresos_mano_obra']  ?? 0);
        $row['ingresos_materiales'] = (float)($row['ingresos_materiales'] ?? 0);
        $row['coste_materiales']    = (float)($row['coste_materiales']    ?? 0);

        $row['ingresos_totales'] = round($row['ingresos_mano_obra'] + $row['ingresos_materiales'], 2);
        $row['margen_bruto']     = round($row['ingresos_totales'] - $row['coste_materiales'], 2);
        $row['margen_pct']       = $row['ingresos_totales'] > 0
            ? round(($row['margen_bruto'] / $row['ingresos_totales']) * 100, 1) : 0;
        $row['ticket_medio']     = $row['total_ordenes'] > 0
            ? round($row['ingresos_totales'] / $row['total_ordenes'], 2) : 0;

        return $row;
    }

    // ─────────────────────────────────────────────────────────────────
    // EVOLUCIÓN — Ingresos y coste de materiales por día/semana/mes
    // ─────────────────────────────────────────────────────────────────
    function r_ingresos_por_periodo($taller_id, $desde, $hasta, $agrupacion = 'dia') {
        $conn = conectaBD();
        $r = _r_rango('COALESCE(ot.facturado_en, ot.fecha_creacion)', $desde, $hasta);
        $fmt = _r_formatoFecha($agrupacion);

        $sql = "SELECT
                    DATE_FORMAT(COALESCE(ot.facturado_en, ot.fecha_creacion), '{$fmt}') AS periodo,
                    ROUND(COALESCE(SUM(
                        COALESCE(ta.duracion_real_minutos,0)/60.0 * t.tarifa_hora_base
                        + IFNULL(rt.cantidad * rt.precio_unidad_momento, 0)
                    ), 0), 2)                                                       AS ingresos,
                    ROUND(COALESCE(SUM(rt.cantidad * p.precio_compra), 0), 2)          AS coste_materiales
                FROM ordenes_trabajo ot
                INNER JOIN talleres t          ON ot.taller_id = t.id
                LEFT  JOIN tareas_asignadas ta ON ta.orden_trabajo_id = ot.id
                                              AND ta.estado <> 'rechazada'
                LEFT  JOIN repuestos_tarea rt  ON rt.tarea_asignada_id = ta.id
                LEFT  JOIN productos p         ON rt.producto_id = p.id
                WHERE ot.taller_id = ?
                  AND ot.estado    = 'facturado'
                  {$r['sql']}
                GROUP BY periodo
                ORDER BY periodo ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i' . $r['tip'], $taller_id, ...$r['par']);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // FLUJO — Distribución actual de órdenes por estado
    // (todas las creadas en el período, sin filtrar por estado)
    // ─────────────────────────────────────────────────────────────────
    function r_ordenes_por_estado($taller_id, $desde, $hasta) {
        $conn = conectaBD();
        $r = _r_rango('ot.fecha_creacion', $desde, $hasta);

        $sql = "SELECT estado, COUNT(*) AS total
                FROM ordenes_trabajo ot
                WHERE ot.taller_id = ?
                  {$r['sql']}
                GROUP BY estado
                ORDER BY FIELD(estado,'recibido','diagnosticando','presupuestado','en_reparacion','listo','facturado')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i' . $r['tip'], $taller_id, ...$r['par']);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // TIEMPO — Media de minutos por orden facturada (suma de tareas)
    // ─────────────────────────────────────────────────────────────────
    function r_tiempo_medio($taller_id, $desde, $hasta) {
        $conn = conectaBD();
        $r = _r_rango('COALESCE(ot.facturado_en, ot.fecha_creacion)', $desde, $hasta);

        $sql = "SELECT
                    COUNT(*)                                       AS total_ordenes,
                    ROUND(AVG(min_orden), 1)                       AS media_min_por_orden
                FROM (
                    SELECT ot.id, SUM(ta.duracion_real_minutos) AS min_orden
                    FROM ordenes_trabajo ot
                    INNER JOIN tareas_asignadas ta ON ta.orden_trabajo_id = ot.id
                    WHERE ot.taller_id = ?
                      AND ot.estado    = 'facturado'
                      AND ta.estado    = 'finalizada'
                      AND ta.duracion_real_minutos IS NOT NULL
                      {$r['sql']}
                    GROUP BY ot.id
                ) sub";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i' . $r['tip'], $taller_id, ...$r['par']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: ['total_ordenes' => 0, 'media_min_por_orden' => null];
        $stmt->close();
        $conn->close();
        return $row;
    }

    // ─────────────────────────────────────────────────────────────────
    // PRECISIÓN IA — Compara precio_estimado_ia vs total real cobrado
    // Solo órdenes facturadas en el rango con estimación IA registrada.
    // ─────────────────────────────────────────────────────────────────
    function r_precision_ia($taller_id, $desde, $hasta) {
        $conn = conectaBD();
        $r = _r_rango('COALESCE(ot.facturado_en, ot.fecha_creacion)', $desde, $hasta);

        $sql = "SELECT
                    COUNT(*)                                                  AS ordenes_evaluadas,
                    ROUND(AVG(estimado), 2)                                   AS media_estimado,
                    ROUND(AVG(real_total), 2)                                 AS media_real,
                    ROUND(AVG(real_total - estimado), 2)                      AS desviacion_media,
                    ROUND(AVG(ABS(real_total - estimado) / NULLIF(estimado,0)) * 100, 1) AS error_pct_medio
                FROM (
                    SELECT
                        ot.id,
                        ot.precio_estimado_ia AS estimado,
                        COALESCE(SUM(
                            COALESCE(ta.duracion_real_minutos,0)/60.0 * t.tarifa_hora_base
                            + IFNULL(rt.cantidad * rt.precio_unidad_momento, 0)
                        ), 0) AS real_total
                    FROM ordenes_trabajo ot
                    INNER JOIN talleres t          ON ot.taller_id = t.id
                    LEFT  JOIN tareas_asignadas ta ON ta.orden_trabajo_id = ot.id
                                                  AND ta.estado <> 'rechazada'
                    LEFT  JOIN repuestos_tarea rt  ON rt.tarea_asignada_id = ta.id
                    WHERE ot.taller_id        = ?
                      AND ot.estado           = 'facturado'
                      AND ot.precio_estimado_ia IS NOT NULL
                      AND ot.precio_estimado_ia > 0
                      {$r['sql']}
                    GROUP BY ot.id, ot.precio_estimado_ia
                ) sub";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i' . $r['tip'], $taller_id, ...$r['par']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        $conn->close();

        return [
            'ordenes_evaluadas' => (int)($row['ordenes_evaluadas'] ?? 0),
            'media_estimado'    => (float)($row['media_estimado'] ?? 0),
            'media_real'        => (float)($row['media_real'] ?? 0),
            'desviacion_media'  => (float)($row['desviacion_media'] ?? 0),
            'error_pct_medio'   => $row['error_pct_medio'] !== null ? (float)$row['error_pct_medio'] : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // PRESUPUESTOS — Tasa de aceptación de tareas presupuestadas
    // Cuenta tareas: aceptadas (no rechazadas en órdenes presupuestadas
    // o posteriores) vs rechazadas, en el rango.
    // ─────────────────────────────────────────────────────────────────
    function r_aprobacion_presupuestos($taller_id, $desde, $hasta) {
        $conn = conectaBD();
        $r = _r_rango('ot.fecha_creacion', $desde, $hasta);

        $sql = "SELECT
                    SUM(CASE WHEN ta.estado = 'rechazada' THEN 1 ELSE 0 END) AS rechazadas,
                    SUM(CASE WHEN ta.estado <> 'rechazada' THEN 1 ELSE 0 END) AS aceptadas,
                    COUNT(*) AS total
                FROM tareas_asignadas ta
                INNER JOIN ordenes_trabajo ot ON ta.orden_trabajo_id = ot.id
                WHERE ot.taller_id = ?
                  AND ot.estado IN ('presupuestado','en_reparacion','listo','facturado')
                  {$r['sql']}";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i' . $r['tip'], $taller_id, ...$r['par']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        $conn->close();

        $total      = (int)($row['total'] ?? 0);
        $aceptadas  = (int)($row['aceptadas'] ?? 0);
        $rechazadas = (int)($row['rechazadas'] ?? 0);

        return [
            'aceptadas'   => $aceptadas,
            'rechazadas'  => $rechazadas,
            'total'       => $total,
            'tasa_pct'    => $total > 0 ? round(($aceptadas / $total) * 100, 1) : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // EQUIPO — Productividad por mecánico (incluye jefes asignados a tareas)
    // ─────────────────────────────────────────────────────────────────
    function r_productividad_mecanicos($taller_id, $desde, $hasta) {
        $conn = conectaBD();
        $r = _r_rango('COALESCE(ot.facturado_en, ot.fecha_creacion)', $desde, $hasta);

        $sql = "SELECT
                    u.id                                                AS mecanico_id,
                    u.nombre_completo                                   AS mecanico,
                    u.rol                                               AS rol,
                    COUNT(DISTINCT ta.orden_trabajo_id)                 AS ordenes_trabajadas,
                    COUNT(ta.id)                                        AS tareas_completadas,
                    ROUND(SUM(COALESCE(ta.duracion_real_minutos,0))/60, 2) AS horas_trabajadas,
                    ROUND(AVG(ta.duracion_real_minutos), 1)             AS media_min_por_tarea,
                    ROUND(
                        SUM(COALESCE(ta.tiempo_estimado_minutos, ct.minutos_estimados_base, 0)) /
                        NULLIF(SUM(ta.duracion_real_minutos), 0) * 100
                    , 1)                                                AS eficiencia_pct,
                    ROUND(
                        SUM(COALESCE(ta.duracion_real_minutos,0)) / 60.0 * t.tarifa_hora_base
                    , 2)                                                AS ingresos_mano_obra
                FROM usuarios u
                INNER JOIN tareas_asignadas ta  ON ta.mecanico_id        = u.id
                INNER JOIN catalogo_tareas ct   ON ta.tarea_catalogo_id  = ct.id
                INNER JOIN ordenes_trabajo ot   ON ta.orden_trabajo_id   = ot.id
                INNER JOIN talleres t           ON ot.taller_id          = t.id
                WHERE u.taller_id = ?
                  AND u.rol       IN ('mecanico','jefe')
                  AND ta.estado   = 'finalizada'
                  AND ot.estado   = 'facturado'
                  {$r['sql']}
                GROUP BY u.id, u.nombre_completo, u.rol, t.tarifa_hora_base
                ORDER BY horas_trabajadas DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i' . $r['tip'], $taller_id, ...$r['par']);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // INVENTARIO — Stock crítico (no depende del rango)
    // ─────────────────────────────────────────────────────────────────
    function r_stock_critico($taller_id) {
        $conn = conectaBD();
        $sql = "SELECT
                    id, referencia_sku, nombre,
                    cantidad_stock, alerta_stock_minimo,
                    precio_venta, precio_compra,
                    CASE
                        WHEN cantidad_stock = 0                    THEN 'agotado'
                        WHEN cantidad_stock <= alerta_stock_minimo THEN 'critico'
                        ELSE 'ok'
                    END AS estado_stock
                FROM productos
                WHERE taller_id = ?
                  AND cantidad_stock <= alerta_stock_minimo
                ORDER BY cantidad_stock ASC, nombre ASC
                LIMIT 20";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $taller_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // INVENTARIO — Valor agregado del stock
    // ─────────────────────────────────────────────────────────────────
    function r_valor_stock($taller_id) {
        $conn = conectaBD();
        $sql = "SELECT
                    COUNT(*)                                                       AS total_referencias,
                    COALESCE(SUM(cantidad_stock), 0)                               AS total_unidades,
                    ROUND(COALESCE(SUM(cantidad_stock * precio_compra), 0), 2)    AS valor_coste,
                    ROUND(COALESCE(SUM(cantidad_stock * precio_venta),  0), 2)    AS valor_venta,
                    ROUND(COALESCE(SUM(cantidad_stock * (precio_venta - precio_compra)), 0), 2) AS margen_potencial,
                    SUM(CASE WHEN cantidad_stock = 0                          THEN 1 ELSE 0 END) AS agotados,
                    SUM(CASE WHEN cantidad_stock > 0
                              AND cantidad_stock <= alerta_stock_minimo       THEN 1 ELSE 0 END) AS criticos,
                    SUM(CASE WHEN cantidad_stock >  alerta_stock_minimo       THEN 1 ELSE 0 END) AS ok
                FROM productos
                WHERE taller_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $taller_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        $conn->close();
        return $row;
    }

    // ─────────────────────────────────────────────────────────────────
    // INVENTARIO — Top materiales más consumidos en el rango (facturados)
    // ─────────────────────────────────────────────────────────────────
    function r_top_materiales($taller_id, $desde, $hasta) {
        $conn = conectaBD();
        $r = _r_rango('COALESCE(ot.facturado_en, ot.fecha_creacion)', $desde, $hasta);

        $sql = "SELECT
                    p.id,
                    p.nombre,
                    p.referencia_sku,
                    SUM(rt.cantidad)                                       AS unidades_consumidas,
                    ROUND(SUM(rt.cantidad * rt.precio_unidad_momento), 2)  AS ingresos_generados,
                    ROUND(SUM(rt.cantidad * (rt.precio_unidad_momento - p.precio_compra)), 2) AS margen
                FROM repuestos_tarea rt
                INNER JOIN productos p         ON rt.producto_id        = p.id
                INNER JOIN tareas_asignadas ta ON rt.tarea_asignada_id  = ta.id
                INNER JOIN ordenes_trabajo ot  ON ta.orden_trabajo_id   = ot.id
                WHERE p.taller_id = ?
                  AND ot.estado   = 'facturado'
                  AND ta.estado   <> 'rechazada'
                  {$r['sql']}
                GROUP BY p.id, p.nombre, p.referencia_sku
                ORDER BY unidades_consumidas DESC
                LIMIT 10";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i' . $r['tip'], $taller_id, ...$r['par']);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // CARTERA — Órdenes activas pendientes (snapshot, no rangos)
    // Útil para que el jefe vea el "backlog" actual.
    // ─────────────────────────────────────────────────────────────────
    function r_backlog($taller_id) {
        $conn = conectaBD();
        $sql = "SELECT
                    ot.estado,
                    COUNT(*) AS total,
                    ROUND(SUM(IFNULL(ot.precio_estimado_ia, 0)), 2) AS valor_estimado,
                    SUM(IFNULL(ot.tiempo_estimado_ia, 0))           AS minutos_estimados
                FROM ordenes_trabajo ot
                WHERE ot.taller_id = ?
                  AND ot.estado   IN ('recibido','diagnosticando','presupuestado','en_reparacion','listo')
                GROUP BY ot.estado
                ORDER BY FIELD(ot.estado,'recibido','diagnosticando','presupuestado','en_reparacion','listo')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $taller_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        return $rows;
    }
?>
