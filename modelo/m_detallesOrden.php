<?php
require_once __DIR__ . '/m_conecta.php';

// Función para obtener detalles de la orden de trabajo
function obtenerOrdenPorId($orden_id, $taller_id) {
    $conn = conectaBD();
    $stmt = $conn->prepare("SELECT * FROM ordenes_trabajo WHERE id = ? AND taller_id = ?");
    $stmt->bind_param("ii", $orden_id, $taller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Función para obtener detalles del vehículo
function obtenerVehiculoPorId($vehiculo_id, $taller_id) {
    $conn = conectaBD();
    $stmt = $conn->prepare("SELECT * FROM vehiculos WHERE id = ? AND taller_id = ?");
    $stmt->bind_param("ii", $vehiculo_id, $taller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Función para obtener tareas asignadas a la orden
function obtenerTareasAsignadas($orden_id) {
    $conn = conectaBD();
    $stmt = $conn->prepare("
        SELECT ta.*, ct.nombre_tarea, u.nombre_completo AS mecanico_nombre
        FROM tareas_asignadas ta
        JOIN catalogo_tareas ct ON ta.tarea_catalogo_id = ct.id
        LEFT JOIN usuarios u ON ta.mecanico_id = u.id
        WHERE ta.orden_trabajo_id = ?
    ");
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>