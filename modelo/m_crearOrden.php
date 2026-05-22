<?php
require_once __DIR__ . '/m_conecta.php';

// Obtener tipos de reparación
function obtenerTiposReparacion() {
    $conn = conectaBD();
    $stmt = $conn->prepare("SELECT id, nombre FROM tipos_reparacion");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Obtener subgrupos por tipo
function obtenerSubgruposReparacion($tipo_id) {
    $conn = conectaBD();
    $stmt = $conn->prepare("SELECT id, nombre FROM subgrupos_reparacion WHERE tipo_reparacion_id = ?");
    $stmt->bind_param("i", $tipo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ─────────────────────────────────────────────────────────────────
// Recalcula precio_estimado_ia y tiempo_estimado_ia de una orden
// sumando las tareas NO rechazadas. Se invoca tras insertar tareas
// o tras aceptar/rechazar tareas desde "Mis Presupuestos".
// ─────────────────────────────────────────────────────────────────
function recalcularEstimacionesOrden($orden_id, $taller_id, $conn = null) {
    $cerrar = false;
    if ($conn === null) {
        $conn = conectaBD();
        $cerrar = true;
    }

    $sql = "SELECT
                COALESCE(SUM(precio_estimado), 0)         AS total_precio,
                COALESCE(SUM(tiempo_estimado_minutos), 0) AS total_tiempo
            FROM tareas_asignadas
            WHERE orden_trabajo_id = ?
              AND estado <> 'rechazada'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $precio = $row['total_precio'] > 0 ? (float)$row['total_precio'] : null;
    $tiempo = $row['total_tiempo'] > 0 ? (int)$row['total_tiempo']   : null;

    $sql_upd = "UPDATE ordenes_trabajo
                SET precio_estimado_ia = ?,
                    tiempo_estimado_ia = ?
                WHERE id = ? AND taller_id = ?";
    $stmt = $conn->prepare($sql_upd);
    $stmt->bind_param("diii", $precio, $tiempo, $orden_id, $taller_id);
    $stmt->execute();
    $stmt->close();

    if ($cerrar) $conn->close();

    return ['precio' => $precio, 'tiempo' => $tiempo];
}

// Crear orden con múltiples tareas
function crearOrdenTrabajo($taller_id, $vehiculo_id, $creado_por_id, $sintomas_cliente, $tareas = [], $estado = 'recibido') {
    $conn = conectaBD();
    $conn->begin_transaction();

    try {
        $estados_validos = ['recibido', 'diagnosticando', 'presupuestado', 'en_reparacion', 'listo', 'facturado'];
        if (!in_array($estado, $estados_validos)) {
            $estado = 'recibido';
        }

        $stmt = $conn->prepare("INSERT INTO ordenes_trabajo (taller_id, vehiculo_id, creado_por_id, estado, sintomas_cliente) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $taller_id, $vehiculo_id, $creado_por_id, $estado, $sintomas_cliente);
        $stmt->execute();
        $orden_id = $conn->insert_id;
        $stmt->close();

        // Tarifa hora del taller para estimar manualmente cuando no hay predicción IA
        $stmt = $conn->prepare("SELECT tarifa_hora_base FROM talleres WHERE id = ?");
        $stmt->bind_param("i", $taller_id);
        $stmt->execute();
        $tarifa_hora = (float)($stmt->get_result()->fetch_assoc()['tarifa_hora_base'] ?? 45.00);
        $stmt->close();

        // Procesar tareas
        if (!empty($tareas)) {
            foreach ($tareas as $tarea) {

                $subgrupo_id = $tarea['subgrupo'] ?? null;
                if (!$subgrupo_id) continue;

                $precio_estimado_in = isset($tarea['precio_estimado']) && $tarea['precio_estimado'] !== ''
                    ? (float)$tarea['precio_estimado'] : null;
                $tiempo_estimado_in = isset($tarea['tiempo_estimado']) && $tarea['tiempo_estimado'] !== ''
                    ? (int)$tarea['tiempo_estimado'] : null;

                // Obtener datos del subgrupo
                $stmt = $conn->prepare("SELECT nombre, minutos_estimados_base FROM subgrupos_reparacion WHERE id = ?");
                $stmt->bind_param("i", $subgrupo_id);
                $stmt->execute();
                $subgrupo = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$subgrupo) continue;

                // Buscar en catálogo
                $stmt = $conn->prepare("SELECT id FROM catalogo_tareas WHERE taller_id = ? AND nombre_tarea = ?");
                $stmt->bind_param("is", $taller_id, $subgrupo['nombre']);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($existing) {
                    $catalogo_id = $existing['id'];
                } else {
                    $stmt = $conn->prepare("INSERT INTO catalogo_tareas (taller_id, nombre_tarea, minutos_estimados_base) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $taller_id, $subgrupo['nombre'], $subgrupo['minutos_estimados_base']);
                    $stmt->execute();
                    $catalogo_id = $conn->insert_id;
                    $stmt->close();
                }

                // Fallback si no llegó predicción IA: usar minutos_estimados_base * tarifa
                if ($tiempo_estimado_in === null) {
                    $tiempo_estimado_in = $subgrupo['minutos_estimados_base'] !== null
                        ? (int)$subgrupo['minutos_estimados_base'] : null;
                }
                if ($precio_estimado_in === null && $tiempo_estimado_in !== null) {
                    $precio_estimado_in = round(($tiempo_estimado_in / 60.0) * $tarifa_hora, 2);
                }

                // Insertar tarea asignada con la estimación
                $stmt = $conn->prepare("INSERT INTO tareas_asignadas
                    (orden_trabajo_id, tarea_catalogo_id, mecanico_id, estado, precio_estimado, tiempo_estimado_minutos)
                    VALUES (?, ?, NULL, 'pendiente', ?, ?)");
                $stmt->bind_param("iidi", $orden_id, $catalogo_id, $precio_estimado_in, $tiempo_estimado_in);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Recalcular agregados en la orden
        recalcularEstimacionesOrden($orden_id, $taller_id, $conn);

        $conn->commit();
        return $orden_id;

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    } finally {
        $conn->close();
    }
}

// Obtener vehículos del taller
function obtenerVehiculosPorTaller($taller_id) {
    $conn = conectaBD();
    $stmt = $conn->prepare("SELECT id, matricula, marca, modelo FROM vehiculos WHERE taller_id = ?");
    $stmt->bind_param("i", $taller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
