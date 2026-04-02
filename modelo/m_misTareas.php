<?php
    require_once __DIR__ . '/../modelo/m_conecta.php';

    // ─────────────────────────────────────────────────────────────────
    // Órdenes de trabajo que tienen al menos una tarea asignada
    // al mecánico en sesión
    // ─────────────────────────────────────────────────────────────────
    function obtenerOrdenesPorMecanico($mecanico_id, $taller_id) {
        $conn = conectaBD();

        $sql = "SELECT DISTINCT
                    ot.id,
                    ot.estado,
                    ot.sintomas_cliente,
                    ot.fecha_creacion,
                    v.matricula,
                    v.marca,
                    v.modelo,
                    CONCAT(cliente.nombre_completo) AS nombre_cliente,
                    (SELECT COUNT(*) 
                     FROM tareas_asignadas ta2 
                     WHERE ta2.orden_trabajo_id = ot.id 
                       AND ta2.mecanico_id = ?) AS total_tareas,
                    (SELECT COUNT(*) 
                     FROM tareas_asignadas ta3 
                     WHERE ta3.orden_trabajo_id = ot.id 
                       AND ta3.mecanico_id = ? 
                       AND ta3.estado = 'finalizada') AS tareas_finalizadas
                FROM ordenes_trabajo ot
                INNER JOIN tareas_asignadas ta ON ta.orden_trabajo_id = ot.id
                INNER JOIN vehiculos v          ON ot.vehiculo_id = v.id
                INNER JOIN usuarios cliente     ON v.cliente_id = cliente.id
                WHERE ta.mecanico_id = ?
                  AND ot.taller_id   = ?
                  AND ot.estado != 'listo'
                  AND ot.estado != 'facturado'
                ORDER BY ot.fecha_creacion DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $mecanico_id, $mecanico_id, $mecanico_id, $taller_id);
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
    // Tareas de una orden asignadas a un mecánico concreto
    // ─────────────────────────────────────────────────────────────────
    function obtenerTareasDeOrden($orden_id, $mecanico_id, $taller_id) {
        $conn = conectaBD();

        $sql = "SELECT 
                    ta.id,
                    ta.estado,
                    ta.hora_inicio,
                    ta.hora_fin,
                    ta.duracion_real_minutos,
                    ct.nombre_tarea,
                    ct.minutos_estimados_base,
                    ot.id                          AS orden_id,
                    ot.estado                      AS estado_orden,
                    ot.sintomas_cliente,
                    v.matricula,
                    v.marca,
                    v.modelo,
                    CONCAT(cliente.nombre_completo) AS nombre_cliente
                FROM tareas_asignadas ta
                INNER JOIN catalogo_tareas ct   ON ta.tarea_catalogo_id = ct.id
                INNER JOIN ordenes_trabajo ot   ON ta.orden_trabajo_id  = ot.id
                INNER JOIN vehiculos v          ON ot.vehiculo_id       = v.id
                INNER JOIN usuarios cliente     ON v.cliente_id         = cliente.id
                WHERE ta.orden_trabajo_id = ?
                  AND ta.mecanico_id      = ?
                  AND ot.taller_id        = ?
                ORDER BY FIELD(ta.estado, 'en_proceso', 'pendiente', 'finalizada'), ta.id ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $orden_id, $mecanico_id, $taller_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $tareas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $tareas[] = $fila;
        }

        $stmt->close();
        $conn->close();
        return $tareas;
    }

    // ─────────────────────────────────────────────────────────────────
    // Una sola tarea (para la pantalla de edición)
    // ─────────────────────────────────────────────────────────────────
    function obtenerTareaPorId($tarea_id, $mecanico_id, $taller_id) {
        $conn = conectaBD();

        $sql = "SELECT 
                    ta.id,
                    ta.estado,
                    ta.hora_inicio,
                    ta.hora_fin,
                    ta.duracion_real_minutos,
                    ct.nombre_tarea,
                    ct.minutos_estimados_base,
                    ot.id                          AS orden_id,
                    ot.estado                      AS estado_orden,
                    ot.sintomas_cliente,
                    v.matricula,
                    v.marca,
                    v.modelo,
                    v.anio,
                    CONCAT(cliente.nombre_completo) AS nombre_cliente,
                    cliente.telefono               AS telefono_cliente
                FROM tareas_asignadas ta
                INNER JOIN catalogo_tareas ct   ON ta.tarea_catalogo_id = ct.id
                INNER JOIN ordenes_trabajo ot   ON ta.orden_trabajo_id  = ot.id
                INNER JOIN vehiculos v          ON ot.vehiculo_id       = v.id
                INNER JOIN usuarios cliente     ON v.cliente_id         = cliente.id
                WHERE ta.id          = ?
                  AND ta.mecanico_id = ?
                  AND ot.taller_id   = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $tarea_id, $mecanico_id, $taller_id);
        $stmt->execute();
        $tarea = $stmt->get_result()->fetch_assoc();

        $stmt->close();
        $conn->close();
        return $tarea;
    }

    // ─────────────────────────────────────────────────────────────────
    // Guardar cambios en una tarea (estado, hora inicio, hora fin)
    // Calcula automáticamente duracion_real_minutos.
    // Si todas las tareas de la orden quedan 'finalizada', pone
    // la orden en estado 'listo' automáticamente.
    // ─────────────────────────────────────────────────────────────────
    function actualizarTarea($tarea_id, $mecanico_id, $taller_id, $estado, $hora_inicio, $hora_fin) {
        $conn = conectaBD();
        $conn->begin_transaction();

        try {
            // Normalizar strings vacíos a NULL
            $hora_inicio = ($hora_inicio !== '' && $hora_inicio !== null) ? $hora_inicio : null;
            $hora_fin    = ($hora_fin    !== '' && $hora_fin    !== null) ? $hora_fin    : null;

            // Calcular duración real en minutos si hay ambas horas y fin > inicio
            $duracion_sql = "NULL";
            if ($hora_inicio && $hora_fin) {
                $inicio = new DateTime($hora_inicio);
                $fin    = new DateTime($hora_fin);
                if ($fin > $inicio) {
                    $diff         = $inicio->diff($fin);
                    $duracion_sql = (string)(($diff->days * 1440) + ($diff->h * 60) + $diff->i);
                }
            }

            // 1. Actualizar la tarea (duración interpolada como entero PHP — seguro)
            $sql = "UPDATE tareas_asignadas ta
                    INNER JOIN ordenes_trabajo ot ON ta.orden_trabajo_id = ot.id
                    SET ta.estado                = ?,
                        ta.hora_inicio           = ?,
                        ta.hora_fin              = ?,
                        ta.duracion_real_minutos = {$duracion_sql}
                    WHERE ta.id          = ?
                      AND ta.mecanico_id = ?
                      AND ot.taller_id   = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiii", $estado, $hora_inicio, $hora_fin, $tarea_id, $mecanico_id, $taller_id);
            $stmt->execute();

            if ($stmt->errno !== 0) {
                throw new Exception("Error al actualizar la tarea.");
            }
            $stmt->close();

            // 2. Obtener la orden_trabajo_id de esta tarea
            $sql_orden_id = "SELECT orden_trabajo_id FROM tareas_asignadas WHERE id = ?";
            $stmt2 = $conn->prepare($sql_orden_id);
            $stmt2->bind_param("i", $tarea_id);
            $stmt2->execute();
            $fila = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            if (!$fila) {
                throw new Exception("No se encontró la tarea.");
            }
            $orden_id = (int)$fila['orden_trabajo_id'];

            // 3. Comprobar si TODAS las tareas de esa orden están finalizadas
            $sql_check = "SELECT 
                              COUNT(*) AS total,
                              SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END) AS finalizadas
                          FROM tareas_asignadas
                          WHERE orden_trabajo_id = ?";
            $stmt3 = $conn->prepare($sql_check);
            $stmt3->bind_param("i", $orden_id);
            $stmt3->execute();
            $conteo = $stmt3->get_result()->fetch_assoc();
            $stmt3->close();

            $orden_lista = (
                (int)$conteo['total'] > 0 &&
                (int)$conteo['total'] === (int)$conteo['finalizadas']
            );

            // 4. Si todas finalizadas → poner la orden en 'listo' automáticamente
            if ($orden_lista) {
                $sql_listo = "UPDATE ordenes_trabajo 
                              SET estado = 'listo'
                              WHERE id = ? AND taller_id = ?";
                $stmt4 = $conn->prepare($sql_listo);
                $stmt4->bind_param("ii", $orden_id, $taller_id);
                $stmt4->execute();
                $stmt4->close();
            }

            $conn->commit();
            $conn->close();

            return [
                'exito'        => true,
                'mensaje'      => 'Tarea actualizada correctamente.',
                'orden_lista'  => $orden_lista,
                'orden_id'     => $orden_id,
            ];

        } catch (Exception $e) {
            $conn->rollback();
            $conn->close();
            return ['exito' => false, 'mensaje' => $e->getMessage(), 'orden_lista' => false];
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Marcar manualmente una orden como 'listo'
    // Solo si todas sus tareas están finalizadas
    // ─────────────────────────────────────────────────────────────────
    function marcarOrdenComoLista($orden_id, $taller_id) {
        $conn = conectaBD();

        // Verificar que todas las tareas estén finalizadas
        $sql_check = "SELECT 
                          COUNT(*) AS total,
                          SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END) AS finalizadas
                      FROM tareas_asignadas
                      WHERE orden_trabajo_id = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("i", $orden_id);
        $stmt->execute();
        $conteo = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ((int)$conteo['total'] === 0 || (int)$conteo['total'] !== (int)$conteo['finalizadas']) {
            $conn->close();
            return [
                'exito'   => false,
                'mensaje' => 'No se puede marcar como lista: aún hay tareas sin finalizar (' .
                             ((int)$conteo['total'] - (int)$conteo['finalizadas']) . ' pendiente/s).',
            ];
        }

        $sql_update = "UPDATE ordenes_trabajo 
                       SET estado = 'listo'
                       WHERE id = ? AND taller_id = ?";
        $stmt2 = $conn->prepare($sql_update);
        $stmt2->bind_param("ii", $orden_id, $taller_id);
        $stmt2->execute();
        $ok = ($stmt2->errno === 0);
        $stmt2->close();
        $conn->close();

        return $ok
            ? ['exito' => true,  'mensaje' => 'Orden marcada como lista.']
            : ['exito' => false, 'mensaje' => 'Error al actualizar la orden.'];
    }
?>