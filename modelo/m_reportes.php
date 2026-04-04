<?php
    require_once __DIR__ . '/m_conecta.php';

    // ─────────────────────────────────────────────────────────────────
    // Helper: construir cláusula WHERE de fecha según filtros
    // Devuelve ['sql' => string, 'params' => array, 'types' => string]
    // ─────────────────────────────────────────────────────────────────
    function _filtroFecha($alias_col, $fecha_desde, $fecha_hasta) {
        $sql    = '';
        $params = [];
        $types  = '';

        if ($fecha_desde) {
            $sql    .= " AND {$alias_col} >= ?";
            $params[] = $fecha_desde . ' 00:00:00';
            $types   .= 's';
        }
        if ($fecha_hasta) {
            $sql    .= " AND {$alias_col} <= ?";
            $params[] = $fecha_hasta . ' 23:59:59';
            $types   .= 's';
        }
        return ['sql' => $sql, 'params' => $params, 'types' => $types];
    }

    // ─────────────────────────────────────────────────────────────────
    // 🔥 CORE — Órdenes por estado
    // ─────────────────────────────────────────────────────────────────
    function reporteOrdenesPorEstado($taller_id, $fecha_desde, $fecha_hasta) {
        $conn = conectaBD();
        $f    = _filtroFecha('ot.fecha_creacion', $fecha_desde, $fecha_hasta);

        $sql = "SELECT
                    estado,
                    COUNT(*) AS total
                FROM ordenes_trabajo ot
                WHERE taller_id = ?{$f['sql']}
                GROUP BY estado
                ORDER BY FIELD(estado,'recibido','diagnosticando','presupuestado','en_reparacion','listo','facturado')";

        $stmt = $conn->prepare($sql);
        $params = array_merge([$taller_id], $f['params']);
        $types  = 'i' . $f['types'];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close(); $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // 🔥 CORE — Tiempo medio de reparación (en_reparacion → listo)
    //    Usa duracion_real_minutos de las tareas
    // ─────────────────────────────────────────────────────────────────
    function reporteTiempoMedioReparacion($taller_id, $fecha_desde, $fecha_hasta) {
        $conn = conectaBD();
        $f    = _filtroFecha('ot.fecha_creacion', $fecha_desde, $fecha_hasta);

        $sql = "SELECT
                    COUNT(DISTINCT ot.id)                                   AS total_ordenes,
                    ROUND(AVG(ta.duracion_real_minutos), 1)                 AS media_min_por_tarea,
                    ROUND(SUM(ta.duracion_real_minutos) /
                          NULLIF(COUNT(DISTINCT ot.id), 0), 1)              AS media_min_por_orden
                FROM ordenes_trabajo ot
                INNER JOIN tareas_asignadas ta ON ta.orden_trabajo_id = ot.id
                WHERE ot.taller_id = ?
                  AND ta.duracion_real_minutos IS NOT NULL
                  {$f['sql']}";

        $stmt = $conn->prepare($sql);
        $params = array_merge([$taller_id], $f['params']);
        $stmt->bind_param('i' . $f['types'], ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $conn->close();
        return $row;
    }

    // ─────────────────────────────────────────────────────────────────
    // 🔥 CORE — Throughput: órdenes facturadas por día/semana/mes
    // ─────────────────────────────────────────────────────────────────
    function reporteThroughput($taller_id, $fecha_desde, $fecha_hasta, $agrupacion = 'dia') {
        $conn = conectaBD();
        $f    = _filtroFecha('ot.fecha_creacion', $fecha_desde, $fecha_hasta);

        $formato = match($agrupacion) {
            'semana' => '%Y-%u',
            'mes'    => '%Y-%m',
            default  => '%Y-%m-%d',
        };

        $sql = "SELECT
                    DATE_FORMAT(ot.fecha_creacion, '{$formato}') AS periodo,
                    COUNT(*) AS ordenes_facturadas
                FROM ordenes_trabajo ot
                WHERE ot.taller_id = ?
                  AND ot.estado    = 'facturado'
                  {$f['sql']}
                GROUP BY periodo
                ORDER BY periodo ASC";

        $stmt = $conn->prepare($sql);
        $params = array_merge([$taller_id], $f['params']);
        $stmt->bind_param('i' . $f['types'], ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close(); $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // 💰 DINERO — Ingresos, costes, margen, ticket medio
    // ─────────────────────────────────────────────────────────────────
    function reporteFinanciero($taller_id, $fecha_desde, $fecha_hasta) {
        $conn = conectaBD();
        $f    = _filtroFecha('ot.fecha_creacion', $fecha_desde, $fecha_hasta);

        $sql = "SELECT
                    COUNT(DISTINCT ot.id)                                        AS total_ordenes,

                    -- Ingresos: mano de obra (precio venta = tarifa taller) + materiales (precio venta)
                    ROUND(
                        SUM(COALESCE(ta.duracion_real_minutos,0)/60.0 * t.tarifa_hora_base)
                        + COALESCE(SUM(rt.cantidad * rt.precio_unidad_momento), 0)
                    , 2)                                                         AS ingresos_totales,

                    -- Coste materiales (precio compra registrado en productos)
                    ROUND(
                        COALESCE(SUM(rt.cantidad * p.precio_compra), 0)
                    , 2)                                                         AS coste_materiales,

                    -- Margen bruto = ingresos - coste materiales
                    ROUND(
                        SUM(COALESCE(ta.duracion_real_minutos,0)/60.0 * t.tarifa_hora_base)
                        + COALESCE(SUM(rt.cantidad * rt.precio_unidad_momento), 0)
                        - COALESCE(SUM(rt.cantidad * p.precio_compra), 0)
                    , 2)                                                         AS margen_bruto,

                    -- Ingresos mano de obra
                    ROUND(
                        SUM(COALESCE(ta.duracion_real_minutos,0)/60.0 * t.tarifa_hora_base)
                    , 2)                                                         AS ingresos_mano_obra,

                    -- Ingresos materiales (precio venta)
                    ROUND(
                        COALESCE(SUM(rt.cantidad * rt.precio_unidad_momento), 0)
                    , 2)                                                         AS ingresos_materiales

                FROM ordenes_trabajo ot
                INNER JOIN talleres t           ON ot.taller_id        = t.id
                LEFT  JOIN tareas_asignadas ta  ON ta.orden_trabajo_id = ot.id
                LEFT  JOIN repuestos_tarea  rt  ON rt.tarea_asignada_id = ta.id
                LEFT  JOIN productos p          ON rt.producto_id       = p.id
                WHERE ot.taller_id = ?
                  AND ot.estado    = 'facturado'
                  {$f['sql']}";

        $stmt = $conn->prepare($sql);
        $params = array_merge([$taller_id], $f['params']);
        $stmt->bind_param('i' . $f['types'], ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Ticket medio
        $row['ticket_medio'] = $row['total_ordenes'] > 0
            ? round($row['ingresos_totales'] / $row['total_ordenes'], 2)
            : 0;

        // Margen %
        $row['margen_pct'] = $row['ingresos_totales'] > 0
            ? round(($row['margen_bruto'] / $row['ingresos_totales']) * 100, 1)
            : 0;

        $conn->close();
        return $row;
    }

    // ─────────────────────────────────────────────────────────────────
    // 💰 DINERO — Ingresos agrupados por período (para gráfico)
    // ─────────────────────────────────────────────────────────────────
    function reporteIngresosPorPeriodo($taller_id, $fecha_desde, $fecha_hasta, $agrupacion = 'dia') {
        $conn = conectaBD();
        $f    = _filtroFecha('ot.fecha_creacion', $fecha_desde, $fecha_hasta);

        $formato = match($agrupacion) {
            'semana' => '%Y-%u',
            'mes'    => '%Y-%m',
            default  => '%Y-%m-%d',
        };

        $sql = "SELECT
                    DATE_FORMAT(ot.fecha_creacion, '{$formato}') AS periodo,
                    ROUND(
                        SUM(COALESCE(ta.duracion_real_minutos,0)/60.0 * t.tarifa_hora_base)
                        + COALESCE(SUM(rt.cantidad * rt.precio_unidad_momento), 0)
                    , 2) AS ingresos,
                    ROUND(
                        COALESCE(SUM(rt.cantidad * p.precio_compra), 0)
                    , 2) AS costes
                FROM ordenes_trabajo ot
                INNER JOIN talleres t           ON ot.taller_id        = t.id
                LEFT  JOIN tareas_asignadas ta  ON ta.orden_trabajo_id = ot.id
                LEFT  JOIN repuestos_tarea rt   ON rt.tarea_asignada_id = ta.id
                LEFT  JOIN productos p          ON rt.producto_id       = p.id
                WHERE ot.taller_id = ?
                  AND ot.estado    = 'facturado'
                  {$f['sql']}
                GROUP BY periodo
                ORDER BY periodo ASC";

        $stmt = $conn->prepare($sql);
        $params = array_merge([$taller_id], $f['params']);
        $stmt->bind_param('i' . $f['types'], ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close(); $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // 👨‍🔧 EQUIPO — Productividad por mecánico
    // ─────────────────────────────────────────────────────────────────
    function reporteProductividadMecanicos($taller_id, $fecha_desde, $fecha_hasta) {
        $conn = conectaBD();
        $f    = _filtroFecha('ot.fecha_creacion', $fecha_desde, $fecha_hasta);

        $sql = "SELECT
                    u.id                                                AS mecanico_id,
                    u.nombre_completo                                   AS mecanico,
                    COUNT(DISTINCT ta.orden_trabajo_id)                 AS ordenes_trabajadas,
                    COUNT(ta.id)                                        AS tareas_completadas,
                    ROUND(SUM(COALESCE(ta.duracion_real_minutos,0))/60, 2) AS horas_trabajadas,
                    ROUND(AVG(ta.duracion_real_minutos), 1)             AS media_min_por_tarea,

                    -- Eficiencia: (tiempo estimado / tiempo real) * 100
                    ROUND(
                        SUM(COALESCE(ct.minutos_estimados_base,0)) /
                        NULLIF(SUM(ta.duracion_real_minutos), 0) * 100
                    , 1)                                                AS eficiencia_pct,

                    -- Ingresos generados por las horas de este mecánico
                    ROUND(
                        SUM(COALESCE(ta.duracion_real_minutos,0)) / 60.0 * t.tarifa_hora_base
                    , 2)                                                AS ingresos_mano_obra
                FROM usuarios u
                INNER JOIN tareas_asignadas ta  ON ta.mecanico_id        = u.id
                INNER JOIN catalogo_tareas ct   ON ta.tarea_catalogo_id  = ct.id
                INNER JOIN ordenes_trabajo ot   ON ta.orden_trabajo_id   = ot.id
                INNER JOIN talleres t           ON ot.taller_id          = t.id
                WHERE u.taller_id = ?
                  AND u.rol       = 'mecanico'
                  AND ta.estado   = 'finalizada'
                  {$f['sql']}
                GROUP BY u.id, u.nombre_completo, t.tarifa_hora_base
                ORDER BY horas_trabajadas DESC";

        $stmt = $conn->prepare($sql);
        $params = array_merge([$taller_id], $f['params']);
        $stmt->bind_param('i' . $f['types'], ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close(); $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // 📦 INVENTARIO — Productos con stock crítico
    // ─────────────────────────────────────────────────────────────────
    function reporteStockCritico($taller_id) {
        $conn = conectaBD();

        $sql = "SELECT
                    id,
                    referencia_sku,
                    nombre,
                    cantidad_stock,
                    alerta_stock_minimo,
                    precio_venta,
                    precio_compra,
                    CASE
                        WHEN cantidad_stock = 0                    THEN 'agotado'
                        WHEN cantidad_stock <= alerta_stock_minimo THEN 'critico'
                        ELSE 'ok'
                    END AS estado_stock
                FROM productos
                WHERE taller_id = ?
                  AND cantidad_stock <= alerta_stock_minimo
                ORDER BY cantidad_stock ASC, nombre ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $taller_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close(); $conn->close();
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // 📦 INVENTARIO — Valor total del stock y resumen
    // ─────────────────────────────────────────────────────────────────
    function reporteValorStock($taller_id) {
        $conn = conectaBD();

        $sql = "SELECT
                    COUNT(*)                                          AS total_referencias,
                    SUM(cantidad_stock)                               AS total_unidades,
                    ROUND(SUM(cantidad_stock * precio_compra), 2)    AS valor_coste,
                    ROUND(SUM(cantidad_stock * precio_venta),  2)    AS valor_venta,
                    ROUND(SUM(cantidad_stock * (precio_venta - precio_compra)), 2) AS margen_potencial,
                    SUM(CASE WHEN cantidad_stock = 0                    THEN 1 ELSE 0 END) AS agotados,
                    SUM(CASE WHEN cantidad_stock > 0
                              AND cantidad_stock <= alerta_stock_minimo THEN 1 ELSE 0 END) AS criticos,
                    SUM(CASE WHEN cantidad_stock > alerta_stock_minimo  THEN 1 ELSE 0 END) AS ok
                FROM productos
                WHERE taller_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $taller_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $conn->close();
        return $row;
    }

    // ─────────────────────────────────────────────────────────────────
    // 📦 INVENTARIO — Productos más consumidos en el período
    // ─────────────────────────────────────────────────────────────────
    function reporteProductosMasUsados($taller_id, $fecha_desde, $fecha_hasta) {
        $conn = conectaBD();
        $f    = _filtroFecha('ot.fecha_creacion', $fecha_desde, $fecha_hasta);

        $sql = "SELECT
                    p.nombre,
                    p.referencia_sku,
                    SUM(rt.cantidad)                               AS unidades_consumidas,
                    ROUND(SUM(rt.cantidad * rt.precio_unidad_momento), 2) AS ingresos_generados
                FROM repuestos_tarea rt
                INNER JOIN productos p           ON rt.producto_id        = p.id
                INNER JOIN tareas_asignadas ta   ON rt.tarea_asignada_id  = ta.id
                INNER JOIN ordenes_trabajo ot    ON ta.orden_trabajo_id   = ot.id
                WHERE p.taller_id = ?
                  {$f['sql']}
                GROUP BY p.id, p.nombre, p.referencia_sku
                ORDER BY unidades_consumidas DESC
                LIMIT 10";

        $stmt = $conn->prepare($sql);
        $params = array_merge([$taller_id], $f['params']);
        $stmt->bind_param('i' . $f['types'], ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close(); $conn->close();
        return $rows;
    }
?>