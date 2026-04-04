<?php
    require_once __DIR__ . '/m_conecta.php';

    // ─────────────────────────────────────────────────────────────────
    // Órdenes en estado 'listo' pendientes de cobro
    // ─────────────────────────────────────────────────────────────────
    function obtenerOrdenesPendientesCobro($taller_id) {
        $conn = conectaBD();

        $sql = "SELECT
                    ot.id,
                    ot.estado,
                    ot.fecha_creacion,
                    ot.sintomas_cliente,
                    v.matricula,
                    v.marca,
                    v.modelo,
                    v.anio,
                    CONCAT(cliente.nombre_completo) AS nombre_cliente,
                    cliente.telefono               AS telefono_cliente,
                    -- Coste mano de obra: suma de (minutos reales / 60) * tarifa_hora_base
                    ROUND(
                        SUM(COALESCE(ta.duracion_real_minutos, 0) / 60.0 * t.tarifa_hora_base)
                    , 2)                           AS coste_mano_obra,
                    -- Coste materiales: suma de (cantidad * precio_unidad_momento)
                    ROUND(
                        COALESCE(SUM(rt.cantidad * rt.precio_unidad_momento), 0)
                    , 2)                           AS coste_materiales,
                    -- Total
                    ROUND(
                        SUM(COALESCE(ta.duracion_real_minutos, 0) / 60.0 * t.tarifa_hora_base)
                        + COALESCE(SUM(rt.cantidad * rt.precio_unidad_momento), 0)
                    , 2)                           AS total_orden
                FROM ordenes_trabajo ot
                INNER JOIN talleres t      ON ot.taller_id  = t.id
                INNER JOIN vehiculos v     ON ot.vehiculo_id = v.id
                INNER JOIN usuarios cliente ON v.cliente_id  = cliente.id
                LEFT  JOIN tareas_asignadas ta  ON ta.orden_trabajo_id = ot.id
                LEFT  JOIN repuestos_tarea  rt  ON rt.tarea_asignada_id = ta.id
                WHERE ot.taller_id = ?
                  AND ot.estado    = 'listo'
                GROUP BY ot.id, ot.estado, ot.fecha_creacion, ot.sintomas_cliente,
                         v.matricula, v.marca, v.modelo, v.anio,
                         cliente.nombre_completo, cliente.telefono, t.tarifa_hora_base
                ORDER BY ot.fecha_creacion ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taller_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $ordenes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $ordenes[] = $fila;
        }

        $stmt->close();
        $conn->close();
        return $ordenes;
    }

    // ─────────────────────────────────────────────────────────────────
    // Detalle completo de una orden para la factura desglosada
    // ─────────────────────────────────────────────────────────────────
    function obtenerDetalleFactura($orden_id, $taller_id) {
        $conn = conectaBD();

        // ── Cabecera de la orden ──
        $sql_orden = "SELECT
                          ot.id,
                          ot.estado,
                          ot.fecha_creacion,
                          ot.sintomas_cliente,
                          ot.diagnostico_tecnico,
                          v.matricula, v.marca, v.modelo, v.anio, v.ultimo_kilometraje,
                          CONCAT(cliente.nombre_completo) AS nombre_cliente,
                          cliente.telefono               AS telefono_cliente,
                          cliente.email                  AS email_cliente,
                          CONCAT(creador.nombre_completo) AS nombre_creador,
                          t.nombre                       AS nombre_taller,
                          t.identificacion_fiscal,
                          t.direccion                    AS direccion_taller,
                          t.tarifa_hora_base
                      FROM ordenes_trabajo ot
                      INNER JOIN talleres t       ON ot.taller_id    = t.id
                      INNER JOIN vehiculos v      ON ot.vehiculo_id  = v.id
                      INNER JOIN usuarios cliente ON v.cliente_id    = cliente.id
                      INNER JOIN usuarios creador ON ot.creado_por_id = creador.id
                      WHERE ot.id = ? AND ot.taller_id = ?";

        $stmt = $conn->prepare($sql_orden);
        $stmt->bind_param("ii", $orden_id, $taller_id);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$orden) {
            $conn->close();
            return null;
        }

        // ── Tareas con su coste de mano de obra y repuestos ──
        $sql_tareas = "SELECT
                           ta.id                           AS tarea_id,
                           ta.estado,
                           ta.hora_inicio,
                           ta.hora_fin,
                           ta.duracion_real_minutos,
                           ROUND(ta.duracion_real_minutos / 60.0 * ?, 2) AS coste_mano_obra,
                           ct.nombre_tarea,
                           ct.minutos_estimados_base,
                           CONCAT(mec.nombre_completo)     AS nombre_mecanico
                       FROM tareas_asignadas ta
                       INNER JOIN catalogo_tareas ct ON ta.tarea_catalogo_id = ct.id
                       INNER JOIN usuarios mec        ON ta.mecanico_id       = mec.id
                       WHERE ta.orden_trabajo_id = ?
                       ORDER BY ta.id ASC";

        $stmt2 = $conn->prepare($sql_tareas);
        $tarifa = (float)$orden['tarifa_hora_base'];
        $stmt2->bind_param("di", $tarifa, $orden_id);
        $stmt2->execute();
        $resultado_tareas = $stmt2->get_result();

        $tareas = [];
        while ($t = $resultado_tareas->fetch_assoc()) {
            $tareas[] = $t;
        }
        $stmt2->close();

        // ── Repuestos de cada tarea ──
        $sql_repuestos = "SELECT
                              rt.id,
                              rt.tarea_asignada_id,
                              rt.cantidad,
                              rt.precio_unidad_momento,
                              rt.cantidad * rt.precio_unidad_momento AS subtotal,
                              p.nombre          AS nombre_producto,
                              p.referencia_sku
                          FROM repuestos_tarea rt
                          INNER JOIN productos p ON rt.producto_id = p.id
                          INNER JOIN tareas_asignadas ta ON rt.tarea_asignada_id = ta.id
                          WHERE ta.orden_trabajo_id = ?
                          ORDER BY ta.id ASC, p.nombre ASC";

        $stmt3 = $conn->prepare($sql_repuestos);
        $stmt3->bind_param("i", $orden_id);
        $stmt3->execute();
        $resultado_rep = $stmt3->get_result();

        // Indexar repuestos por tarea_id
        $repuestos_por_tarea = [];
        while ($r = $resultado_rep->fetch_assoc()) {
            $repuestos_por_tarea[$r['tarea_asignada_id']][] = $r;
        }
        $stmt3->close();
        $conn->close();

        // Añadir repuestos y coste_materiales a cada tarea
        $coste_total_mano_obra  = 0;
        $coste_total_materiales = 0;

        foreach ($tareas as &$tarea) {
            $tarea['repuestos']        = $repuestos_por_tarea[$tarea['tarea_id']] ?? [];
            $tarea['coste_materiales'] = array_reduce(
                $tarea['repuestos'],
                fn($c, $r) => $c + (float)$r['subtotal'],
                0.0
            );
            $tarea['coste_total_tarea'] = (float)$tarea['coste_mano_obra'] + $tarea['coste_materiales'];
            $coste_total_mano_obra     += (float)$tarea['coste_mano_obra'];
            $coste_total_materiales    += $tarea['coste_materiales'];
        }
        unset($tarea);

        return [
            'orden'                  => $orden,
            'tareas'                 => $tareas,
            'coste_total_mano_obra'  => round($coste_total_mano_obra,  2),
            'coste_total_materiales' => round($coste_total_materiales, 2),
            'total_factura'          => round($coste_total_mano_obra + $coste_total_materiales, 2),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Confirmar pago: pasar orden de 'listo' a 'facturado'
    // ─────────────────────────────────────────────────────────────────
    function confirmarPagoOrden($orden_id, $taller_id) {
        $conn = conectaBD();

        // Solo se puede facturar si está en 'listo'
        $sql = "UPDATE ordenes_trabajo
                SET estado = 'facturado'
                WHERE id = ? AND taller_id = ? AND estado = 'listo'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $orden_id, $taller_id);
        $stmt->execute();
        $ok = ($stmt->affected_rows === 1);
        $stmt->close();
        $conn->close();

        return $ok
            ? ['exito' => true,  'mensaje' => 'Pago confirmado. Orden marcada como facturada.']
            : ['exito' => false, 'mensaje' => 'No se pudo confirmar el pago. La orden puede haber cambiado de estado.'];
    }
?>