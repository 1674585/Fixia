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

// Crear orden con múltiples tareas
function crearOrdenTrabajo($taller_id, $vehiculo_id, $creado_por_id, $sintomas_cliente, $tareas = [], $estado = 'recibido') {
    $conn = conectaBD();
    $conn->begin_transaction(); 

    try {
        // Insertar orden
        $estados_validos = ['recibido', 'diagnosticando', 'presupuestado', 'en_reparacion', 'listo', 'facturado'];

        if (!in_array($estado, $estados_validos)) {
            $estado = 'recibido';
        }

        $stmt = $conn->prepare("INSERT INTO ordenes_trabajo (taller_id, vehiculo_id, creado_por_id, estado, sintomas_cliente) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $taller_id, $vehiculo_id, $creado_por_id, $estado, $sintomas_cliente);
        $stmt->execute();
        $orden_id = $conn->insert_id;

        // Procesar tareas
        if (!empty($tareas)) {
            foreach ($tareas as $tarea) {

                $subgrupo_id = $tarea['subgrupo'] ?? null;
                if (!$subgrupo_id) continue;

                // Obtener datos del subgrupo
                $stmt = $conn->prepare("SELECT nombre, minutos_estimados_base FROM subgrupos_reparacion WHERE id = ?");
                $stmt->bind_param("i", $subgrupo_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $subgrupo = $result->fetch_assoc();

                if ($subgrupo) {

                    // Buscar en catálogo
                    $stmt = $conn->prepare("SELECT id FROM catalogo_tareas WHERE taller_id = ? AND nombre_tarea = ?");
                    $stmt->bind_param("is", $taller_id, $subgrupo['nombre']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $existing = $result->fetch_assoc();

                    if ($existing) {
                        $catalogo_id = $existing['id'];
                    } else {
                        // Insertar en catálogo
                        $stmt = $conn->prepare("INSERT INTO catalogo_tareas (taller_id, nombre_tarea, minutos_estimados_base) VALUES (?, ?, ?)");
                        $stmt->bind_param("isi", $taller_id, $subgrupo['nombre'], $subgrupo['minutos_estimados_base']);
                        $stmt->execute();
                        $catalogo_id = $conn->insert_id;
                    }

                    // Insertar tarea asignada
                    $stmt = $conn->prepare("INSERT INTO tareas_asignadas (orden_trabajo_id, tarea_catalogo_id, mecanico_id, estado) VALUES (?, ?, NULL, 'pendiente')");
                    $stmt->bind_param("ii", $orden_id, $catalogo_id);
                    $stmt->execute();
                }
            }
        }

        $conn->commit();
        return $orden_id;

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
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