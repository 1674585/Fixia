<?php
require_once __DIR__ . '/m_conecta.php';

// Función para obtener tipos de reparación por taller
function obtenerTiposReparacion() {
    $conn = conectaBD(); // CAMBIA: llama a la función para obtener la conexión mysqli
    $stmt = $conn->prepare("SELECT id, nombre FROM tipos_reparacion");
    $stmt->execute();
    $result = $stmt->get_result(); // CAMBIA: obtén el resultado
    return $result->fetch_all(MYSQLI_ASSOC); // CAMBIA: fetch_all en lugar de fetchAll
}

// Función para obtener subgrupos por tipo de reparación
function obtenerSubgruposReparacion($tipo_id) {
    $conn = conectaBD();
    $stmt = $conn->prepare("SELECT id, nombre FROM subgrupos_reparacion WHERE tipo_reparacion_id = ?");
    $stmt->bind_param("i", $tipo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para crear la orden de trabajo
function crearOrdenTrabajo($taller_id, $vehiculo_id, $creado_por_id, $sintomas_cliente, $subgrupo_id = null) {
    $conn = conectaBD();
    $conn->begin_transaction(); // CAMBIA: begin_transaction en lugar de beginTransaction
    try {
        // Insertar orden
        $stmt = $conn->prepare("INSERT INTO ordenes_trabajo (taller_id, vehiculo_id, creado_por_id, estado, sintomas_cliente) VALUES (?, ?, ?, 'recibido', ?)");
        $stmt->bind_param("iiis", $taller_id, $vehiculo_id, $creado_por_id, $sintomas_cliente); // CAMBIA: bind_param con tipos
        $stmt->execute();
        $orden_id = $conn->insert_id; // CAMBIA: insert_id en lugar de lastInsertId

        // Si hay subgrupo seleccionado, crear tarea en catálogo y asignar
        if ($subgrupo_id) {
            // Obtener nombre del subgrupo
            $stmt = $conn->prepare("SELECT nombre, minutos_estimados_base FROM subgrupos_reparacion WHERE id = ?");
            $stmt->bind_param("i", $subgrupo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $subgrupo = $result->fetch_assoc();
            if ($subgrupo) {
                // Insertar o encontrar en catálogo_tareas (mysqli no soporta ON DUPLICATE KEY directamente, así que verifica manualmente)
                $stmt = $conn->prepare("SELECT id FROM catalogo_tareas WHERE taller_id = ? AND nombre_tarea = ?");
                $stmt->bind_param("is", $taller_id, $subgrupo['nombre']);
                $stmt->execute();
                $result = $stmt->get_result();
                $existing = $result->fetch_assoc();
                if ($existing) {
                    $catalogo_id = $existing['id'];
                } else {
                    $stmt = $conn->prepare("INSERT INTO catalogo_tareas (taller_id, nombre_tarea, minutos_estimados_base) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $taller_id, $subgrupo['nombre'], $subgrupo['minutos_estimados_base']);
                    $stmt->execute();
                    $catalogo_id = $conn->insert_id;
                }

                // Asignar tarea
                $stmt = $conn->prepare("INSERT INTO tareas_asignadas (orden_trabajo_id, tarea_catalogo_id, mecanico_id, estado) VALUES (?, ?, ?, 'pendiente')");
                $stmt->bind_param("iii", $orden_id, $catalogo_id, $creado_por_id);
                $stmt->execute();
            }
        }

        $conn->commit(); // CAMBIA: commit en lugar de commit()
        return $orden_id;
    } catch (Exception $e) {
        $conn->rollback(); // CAMBIA: rollback en lugar de rollBack()
        throw $e;
    }
}

// Función para obtener vehículos del taller
function obtenerVehiculosPorTaller($taller_id) {
    $conn = conectaBD();
    $stmt = $conn->prepare("SELECT id, matricula, marca, modelo FROM vehiculos WHERE taller_id = ?");
    $stmt->bind_param("i", $taller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>