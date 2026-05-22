<?php
    require_once __DIR__ . '/m_conecta.php';
    require_once __DIR__ . '/m_crearOrden.php'; // recalcularEstimacionesOrden()

    // ─────────────────────────────────────────────────────────────────
    // Órdenes del cliente que están pendientes de su aprobación.
    // Se consideran "pendientes de presupuesto" las que están en estado
    // 'recibido' o 'diagnosticando' y tienen al menos una tarea propuesta.
    // ─────────────────────────────────────────────────────────────────
    function obtenerPresupuestosPendientesCliente($cliente_id, $taller_id) {
        $conn = conectaBD();

        $sql = "SELECT
                    ot.id,
                    ot.estado,
                    ot.fecha_creacion,
                    ot.sintomas_cliente,
                    ot.precio_estimado_ia,
                    ot.tiempo_estimado_ia,
                    v.id        AS vehiculo_id,
                    v.matricula,
                    v.marca,
                    v.modelo,
                    v.anio,
                    (SELECT COUNT(*) FROM tareas_asignadas ta
                     WHERE ta.orden_trabajo_id = ot.id
                       AND ta.estado <> 'rechazada') AS total_tareas
                FROM ordenes_trabajo ot
                INNER JOIN vehiculos v ON ot.vehiculo_id = v.id
                WHERE v.cliente_id = ?
                  AND ot.taller_id = ?
                  AND ot.estado IN ('recibido','diagnosticando')
                HAVING total_tareas > 0
                ORDER BY ot.fecha_creacion DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cliente_id, $taller_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $ordenes = [];
        while ($f = $res->fetch_assoc()) {
            $ordenes[] = $f;
        }
        $stmt->close();
        $conn->close();
        return $ordenes;
    }

    // ─────────────────────────────────────────────────────────────────
    // Detalle de un presupuesto: la orden + sus tareas propuestas
    // (excluye rechazadas previas porque el cliente las descartó ya).
    // ─────────────────────────────────────────────────────────────────
    function obtenerPresupuestoDetalle($orden_id, $cliente_id, $taller_id) {
        $conn = conectaBD();

        // Cabecera: validar pertenencia al cliente
        $sql_o = "SELECT
                      ot.id,
                      ot.estado,
                      ot.fecha_creacion,
                      ot.sintomas_cliente,
                      ot.precio_estimado_ia,
                      ot.tiempo_estimado_ia,
                      v.matricula, v.marca, v.modelo, v.anio
                  FROM ordenes_trabajo ot
                  INNER JOIN vehiculos v ON ot.vehiculo_id = v.id
                  WHERE ot.id = ?
                    AND ot.taller_id = ?
                    AND v.cliente_id = ?
                    AND ot.estado IN ('recibido','diagnosticando')";
        $stmt = $conn->prepare($sql_o);
        $stmt->bind_param("iii", $orden_id, $taller_id, $cliente_id);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$orden) {
            $conn->close();
            return null;
        }

        // Tareas no rechazadas
        $sql_t = "SELECT
                      ta.id,
                      ta.estado,
                      ta.precio_estimado,
                      ta.tiempo_estimado_minutos,
                      ct.nombre_tarea,
                      ct.minutos_estimados_base
                  FROM tareas_asignadas ta
                  INNER JOIN catalogo_tareas ct ON ta.tarea_catalogo_id = ct.id
                  WHERE ta.orden_trabajo_id = ?
                    AND ta.estado <> 'rechazada'
                  ORDER BY ta.id ASC";
        $stmt = $conn->prepare($sql_t);
        $stmt->bind_param("i", $orden_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $tareas = [];
        while ($f = $res->fetch_assoc()) {
            $tareas[] = $f;
        }
        $stmt->close();
        $conn->close();

        return ['orden' => $orden, 'tareas' => $tareas];
    }

    // ─────────────────────────────────────────────────────────────────
    // Confirma el presupuesto del cliente:
    //   $tareas_aceptadas → array de IDs aceptados
    // Marca las NO aceptadas como 'rechazada', recalcula los agregados
    // de la orden y pone la orden en estado 'presupuestado'.
    // ─────────────────────────────────────────────────────────────────
    function confirmarPresupuestoCliente($orden_id, $cliente_id, $taller_id, $tareas_aceptadas) {
        $conn = conectaBD();
        $conn->begin_transaction();

        try {
            // Validar que la orden pertenece a un vehículo del cliente
            $sql_v = "SELECT ot.id
                      FROM ordenes_trabajo ot
                      INNER JOIN vehiculos v ON ot.vehiculo_id = v.id
                      WHERE ot.id = ?
                        AND ot.taller_id = ?
                        AND v.cliente_id = ?
                        AND ot.estado IN ('recibido','diagnosticando')";
            $stmt = $conn->prepare($sql_v);
            $stmt->bind_param("iii", $orden_id, $taller_id, $cliente_id);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                throw new Exception("Presupuesto no encontrado o no editable.");
            }
            $stmt->close();

            // Obtener todas las tareas no rechazadas de la orden
            $stmt = $conn->prepare("SELECT id FROM tareas_asignadas
                                    WHERE orden_trabajo_id = ?
                                      AND estado <> 'rechazada'");
            $stmt->bind_param("i", $orden_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $todas = [];
            while ($f = $res->fetch_assoc()) {
                $todas[] = (int)$f['id'];
            }
            $stmt->close();

            // Normalizar IDs aceptados a enteros y filtrar los que existen en la orden
            $aceptadas = array_intersect(
                array_map('intval', $tareas_aceptadas),
                $todas
            );

            if (empty($aceptadas)) {
                throw new Exception("Debes aceptar al menos una tarea para confirmar el presupuesto.");
            }

            // Marcar como rechazada cualquier tarea no aceptada
            foreach ($todas as $tid) {
                if (!in_array($tid, $aceptadas, true)) {
                    $stmt = $conn->prepare("UPDATE tareas_asignadas
                                            SET estado = 'rechazada'
                                            WHERE id = ? AND orden_trabajo_id = ?");
                    $stmt->bind_param("ii", $tid, $orden_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // Recalcular agregados de la orden (solo cuenta no-rechazadas)
            recalcularEstimacionesOrden($orden_id, $taller_id, $conn);

            // Estado de la orden → presupuestado
            $stmt = $conn->prepare("UPDATE ordenes_trabajo
                                    SET estado = 'presupuestado'
                                    WHERE id = ? AND taller_id = ?");
            $stmt->bind_param("ii", $orden_id, $taller_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            return ['exito' => true, 'mensaje' => 'Presupuesto confirmado correctamente.'];

        } catch (Exception $e) {
            $conn->rollback();
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        } finally {
            $conn->close();
        }
    }
?>
