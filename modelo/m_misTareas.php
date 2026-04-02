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
    // REPUESTOS: Buscar productos del taller (para el buscador live)
    // Devuelve array JSON-friendly, máx 15 resultados
    // ─────────────────────────────────────────────────────────────────
    function buscarProductosParaTarea($taller_id, $q) {
        $conn = conectaBD();

        $like = '%' . $q . '%';
        $sql  = "SELECT id, nombre, referencia_sku, precio_venta, cantidad_stock
                 FROM productos
                 WHERE taller_id = ?
                   AND (nombre LIKE ? OR referencia_sku LIKE ?)
                   AND cantidad_stock > 0
                 ORDER BY nombre ASC
                 LIMIT 15";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $taller_id, $like, $like);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }

        $stmt->close();
        $conn->close();
        return $productos;
    }

    // ─────────────────────────────────────────────────────────────────
    // REPUESTOS: Obtener repuestos ya consumidos en una tarea
    // ─────────────────────────────────────────────────────────────────
    function obtenerRepuestosDeTarea($tarea_id) {
        $conn = conectaBD();

        $sql = "SELECT 
                    rt.id,
                    rt.cantidad,
                    rt.precio_unidad_momento,
                    rt.cantidad * rt.precio_unidad_momento AS subtotal,
                    p.id              AS producto_id,
                    p.nombre          AS nombre_producto,
                    p.referencia_sku,
                    p.cantidad_stock  AS stock_actual
                FROM repuestos_tarea rt
                INNER JOIN productos p ON rt.producto_id = p.id
                WHERE rt.tarea_asignada_id = ?
                ORDER BY p.nombre ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $tarea_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $repuestos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $repuestos[] = $fila;
        }

        $stmt->close();
        $conn->close();
        return $repuestos;
    }

    // ─────────────────────────────────────────────────────────────────
    // REPUESTOS: Añadir un repuesto a la tarea y descontar del stock
    // — En una sola transacción para garantizar consistencia
    // ─────────────────────────────────────────────────────────────────
    function añadirRepuestoATarea($tarea_id, $mecanico_id, $taller_id, $producto_id, $cantidad) {
        if ($cantidad <= 0) {
            return ['exito' => false, 'mensaje' => 'La cantidad debe ser mayor que 0.'];
        }

        $conn = conectaBD();
        $conn->begin_transaction();

        try {
            // 1. Verificar que la tarea pertenece al mecánico y al taller
            $sql_check = "SELECT ta.id 
                          FROM tareas_asignadas ta
                          INNER JOIN ordenes_trabajo ot ON ta.orden_trabajo_id = ot.id
                          WHERE ta.id = ? AND ta.mecanico_id = ? AND ot.taller_id = ?";
            $stmt = $conn->prepare($sql_check);
            $stmt->bind_param("iii", $tarea_id, $mecanico_id, $taller_id);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                throw new Exception("No tienes permiso para modificar esta tarea.");
            }
            $stmt->close();

            // 2. Verificar stock disponible y obtener precio de venta actual
            $sql_prod = "SELECT nombre, cantidad_stock, precio_venta 
                         FROM productos 
                         WHERE id = ? AND taller_id = ?
                         FOR UPDATE";   // bloqueo de fila durante la transacción
            $stmt2 = $conn->prepare($sql_prod);
            $stmt2->bind_param("ii", $producto_id, $taller_id);
            $stmt2->execute();
            $producto = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            if (!$producto) {
                throw new Exception("Producto no encontrado.");
            }
            if ($producto['cantidad_stock'] < $cantidad) {
                throw new Exception(
                    "Stock insuficiente. Disponible: {$producto['cantidad_stock']} ud. de \"{$producto['nombre']}\"."
                );
            }

            // 3. Comprobar si ya existe ese producto en esta tarea → acumular cantidad
            $sql_existe = "SELECT id, cantidad FROM repuestos_tarea 
                           WHERE tarea_asignada_id = ? AND producto_id = ?";
            $stmt3 = $conn->prepare($sql_existe);
            $stmt3->bind_param("ii", $tarea_id, $producto_id);
            $stmt3->execute();
            $existente = $stmt3->get_result()->fetch_assoc();
            $stmt3->close();

            $precio = (float)$producto['precio_venta'];

            if ($existente) {
                // Actualizar cantidad en el registro existente
                $nueva_cantidad = $existente['cantidad'] + $cantidad;
                $sql_upd = "UPDATE repuestos_tarea 
                            SET cantidad = ?, precio_unidad_momento = ?
                            WHERE id = ?";
                $stmt4 = $conn->prepare($sql_upd);
                $stmt4->bind_param("idi", $nueva_cantidad, $precio, $existente['id']);
                $stmt4->execute();
                $stmt4->close();
            } else {
                // Insertar nuevo registro
                $sql_ins = "INSERT INTO repuestos_tarea 
                                (tarea_asignada_id, producto_id, cantidad, precio_unidad_momento)
                            VALUES (?, ?, ?, ?)";
                $stmt4 = $conn->prepare($sql_ins);
                $stmt4->bind_param("iiid", $tarea_id, $producto_id, $cantidad, $precio);
                $stmt4->execute();
                $stmt4->close();
            }

            // 4. Descontar del stock
            $sql_stock = "UPDATE productos 
                          SET cantidad_stock = cantidad_stock - ?
                          WHERE id = ? AND taller_id = ?";
            $stmt5 = $conn->prepare($sql_stock);
            $stmt5->bind_param("iii", $cantidad, $producto_id, $taller_id);
            $stmt5->execute();
            $stmt5->close();

            $conn->commit();
            $conn->close();

            return ['exito' => true, 'mensaje' => 'Repuesto añadido y stock actualizado.'];

        } catch (Exception $e) {
            $conn->rollback();
            $conn->close();
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // REPUESTOS: Eliminar un repuesto de la tarea y devolver al stock
    // ─────────────────────────────────────────────────────────────────
    function eliminarRepuestoDeTarea($repuesto_id, $mecanico_id, $taller_id) {
        $conn = conectaBD();
        $conn->begin_transaction();

        try {
            // 1. Obtener el repuesto y verificar que la tarea es del mecánico/taller
            $sql_get = "SELECT rt.id, rt.cantidad, rt.producto_id, rt.tarea_asignada_id
                        FROM repuestos_tarea rt
                        INNER JOIN tareas_asignadas ta  ON rt.tarea_asignada_id = ta.id
                        INNER JOIN ordenes_trabajo ot   ON ta.orden_trabajo_id  = ot.id
                        WHERE rt.id = ? AND ta.mecanico_id = ? AND ot.taller_id = ?";
            $stmt = $conn->prepare($sql_get);
            $stmt->bind_param("iii", $repuesto_id, $mecanico_id, $taller_id);
            $stmt->execute();
            $repuesto = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$repuesto) {
                throw new Exception("Repuesto no encontrado o sin permisos.");
            }

            // 2. Devolver las unidades al stock
            $sql_devolver = "UPDATE productos 
                             SET cantidad_stock = cantidad_stock + ?
                             WHERE id = ?";
            $stmt2 = $conn->prepare($sql_devolver);
            $stmt2->bind_param("ii", $repuesto['cantidad'], $repuesto['producto_id']);
            $stmt2->execute();
            $stmt2->close();

            // 3. Eliminar el registro de repuesto
            $sql_del = "DELETE FROM repuestos_tarea WHERE id = ?";
            $stmt3 = $conn->prepare($sql_del);
            $stmt3->bind_param("i", $repuesto_id);
            $stmt3->execute();
            $stmt3->close();

            $conn->commit();
            $conn->close();

            return ['exito' => true, 'mensaje' => 'Repuesto eliminado y stock recuperado.'];

        } catch (Exception $e) {
            $conn->rollback();
            $conn->close();
            return ['exito' => false, 'mensaje' => $e->getMessage()];
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