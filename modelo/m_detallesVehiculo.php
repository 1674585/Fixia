<?php
require_once __DIR__ . '/m_conecta.php';

/**
 * Obtiene un vehículo específico por su ID.
 * Valida que pertenezca al cliente (multi-tenant).
 */
function obtenerVehiculo($vehiculo_id, $cliente_id, $conectar) {
    $query = "SELECT id, matricula, marca, modelo, anio, ultimo_kilometraje
              FROM vehiculos
              WHERE id = ? AND cliente_id = ?";

    $stmt = $conectar->prepare($query);
    $stmt->bind_param("ii", $vehiculo_id, $cliente_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $vehiculo = $resultado->fetch_assoc();
    $stmt->close();

    return $vehiculo;
}

/**
 * Obtiene todas las órdenes de trabajo de un vehículo del cliente,
 * con totales calculados (mano de obra, materiales, tiempo real) por orden.
 * Solo cuenta tareas NO rechazadas.
 */
function obtenerOrdenesVehiculo($vehiculo_id, $cliente_id, $conectar) {
    $query = "SELECT
                    ot.id,
                    ot.estado,
                    ot.sintomas_cliente,
                    ot.diagnostico_tecnico,
                    ot.precio_estimado_ia,
                    ot.tiempo_estimado_ia,
                    ot.fecha_creacion,
                    ot.facturado_en,
                    u.nombre_completo AS creado_por,
                    t.tarifa_hora_base,
                    ROUND(COALESCE(SUM(CASE WHEN ta.estado <> 'rechazada'
                                            THEN ta.duracion_real_minutos / 60.0 * t.tarifa_hora_base
                                            ELSE 0 END), 0), 2)        AS coste_mano_obra,
                    ROUND(COALESCE(SUM(CASE WHEN ta.estado <> 'rechazada'
                                            THEN rt.cantidad * rt.precio_unidad_momento
                                            ELSE 0 END), 0), 2)        AS coste_materiales,
                    COALESCE(SUM(CASE WHEN ta.estado <> 'rechazada'
                                      THEN ta.duracion_real_minutos
                                      ELSE 0 END), 0)                  AS tiempo_real_minutos
              FROM ordenes_trabajo ot
              INNER JOIN usuarios  u  ON ot.creado_por_id = u.id
              INNER JOIN vehiculos v  ON ot.vehiculo_id   = v.id
              INNER JOIN talleres  t  ON ot.taller_id     = t.id
              LEFT  JOIN tareas_asignadas ta ON ta.orden_trabajo_id = ot.id
              LEFT  JOIN repuestos_tarea  rt ON rt.tarea_asignada_id = ta.id
              WHERE ot.vehiculo_id = ? AND v.cliente_id = ?
              GROUP BY ot.id, ot.estado, ot.sintomas_cliente, ot.diagnostico_tecnico,
                       ot.precio_estimado_ia, ot.tiempo_estimado_ia, ot.fecha_creacion,
                       ot.facturado_en, u.nombre_completo, t.tarifa_hora_base
              ORDER BY ot.fecha_creacion DESC";

    $stmt = $conectar->prepare($query);
    $stmt->bind_param("ii", $vehiculo_id, $cliente_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $ordenes = [];
    while ($fila = $resultado->fetch_assoc()) {
        $fila['coste_total_real'] = round((float)$fila['coste_mano_obra'] + (float)$fila['coste_materiales'], 2);
        $fila['diferencia']       = $fila['precio_estimado_ia'] !== null
            ? round($fila['coste_total_real'] - (float)$fila['precio_estimado_ia'], 2)
            : null;
        $ordenes[] = $fila;
    }

    $stmt->close();
    return $ordenes;
}

/**
 * Obtiene las tareas de una orden con estimado vs real y coste de materiales.
 * LEFT JOIN al mecánico: las tareas pendientes (sin asignar) también se listan.
 */
function obtenerTareasOrden($orden_id, $conectar) {
    $query = "SELECT
                    ta.id,
                    ta.estado,
                    ta.hora_inicio,
                    ta.hora_fin,
                    ta.duracion_real_minutos,
                    ta.precio_estimado,
                    ta.tiempo_estimado_minutos,
                    ct.nombre_tarea,
                    u.nombre_completo AS mecanico,
                    t.tarifa_hora_base,
                    ROUND(COALESCE(ta.duracion_real_minutos, 0) / 60.0 * t.tarifa_hora_base, 2) AS coste_mano_obra,
                    ROUND(COALESCE((SELECT SUM(rt.cantidad * rt.precio_unidad_momento)
                                    FROM repuestos_tarea rt
                                    WHERE rt.tarea_asignada_id = ta.id), 0), 2)                AS coste_materiales
              FROM tareas_asignadas ta
              INNER JOIN catalogo_tareas  ct ON ta.tarea_catalogo_id = ct.id
              INNER JOIN ordenes_trabajo  ot ON ta.orden_trabajo_id  = ot.id
              INNER JOIN talleres         t  ON ot.taller_id         = t.id
              LEFT  JOIN usuarios         u  ON ta.mecanico_id       = u.id
              WHERE ta.orden_trabajo_id = ?
              ORDER BY FIELD(ta.estado, 'en_proceso', 'pendiente', 'finalizada', 'rechazada'),
                       ta.hora_inicio, ta.id";

    $stmt = $conectar->prepare($query);
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $tareas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $fila['coste_total_real'] = round((float)$fila['coste_mano_obra'] + (float)$fila['coste_materiales'], 2);
        $tareas[] = $fila;
    }

    $stmt->close();
    return $tareas;
}
?>
