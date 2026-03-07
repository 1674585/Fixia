<?php
require_once __DIR__ . '/m_conecta.php';

/**
 * Obtiene un vehículo específico por su ID
 * @param int $vehiculo_id ID del vehículo
 * @param int $cliente_id ID del cliente (para validación)
 * @param mysqli $conectar Conexión a la BD
 * @return array|null Datos del vehículo o null
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
 * Obtiene todas las órdenes de trabajo de un vehículo
 * @param int $vehiculo_id ID del vehículo
 * @param int $cliente_id ID del cliente (para validación)
 * @param mysqli $conectar Conexión a la BD
 * @return array Array de órdenes o array vacío
 */
function obtenerOrdenesVehiculo($vehiculo_id, $cliente_id, $conectar) {
    $query = "SELECT ot.id, ot.estado, ot.sintomas_cliente, ot.diagnostico_tecnico, 
                     ot.precio_estimado_ia, ot.tiempo_estimado_ia, ot.fecha_creacion,
                     u.nombre_completo as creado_por
              FROM ordenes_trabajo ot
              INNER JOIN usuarios u ON ot.creado_por_id = u.id
              INNER JOIN vehiculos v ON ot.vehiculo_id = v.id
              WHERE ot.vehiculo_id = ? AND v.cliente_id = ?
              ORDER BY ot.fecha_creacion DESC";
    
    $stmt = $conectar->prepare($query);
    $stmt->bind_param("ii", $vehiculo_id, $cliente_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $ordenes = [];
    while ($fila = $resultado->fetch_assoc()) {
        $ordenes[] = $fila;
    }
    
    $stmt->close();
    return $ordenes;
}

/**
 * Obtiene las tareas asignadas de una orden de trabajo
 * @param int $orden_id ID de la orden de trabajo
 * @param mysqli $conectar Conexión a la BD
 * @return array Array de tareas o array vacío
 */
function obtenerTareasOrden($orden_id, $conectar) {
    $query = "SELECT ta.id, ct.nombre_tarea, u.nombre_completo as mecanico,
                     ta.hora_inicio, ta.hora_fin, ta.duracion_real_minutos, ta.estado
              FROM tareas_asignadas ta
              INNER JOIN catalogo_tareas ct ON ta.tarea_catalogo_id = ct.id
              INNER JOIN usuarios u ON ta.mecanico_id = u.id
              WHERE ta.orden_trabajo_id = ?
              ORDER BY ta.hora_inicio";
    
    $stmt = $conectar->prepare($query);
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $tareas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $tareas[] = $fila;
    }
    
    $stmt->close();
    return $tareas;
}

?>