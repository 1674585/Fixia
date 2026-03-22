<?php
    require_once __DIR__ . '/../modelo/m_conecta.php';

    // ─────────────────────────────────────────────
    // Obtener todas las órdenes de trabajo de un taller
    // ─────────────────────────────────────────────
    function obtenerTodasLasOrdenes($taller_id) {
        $conn = conectaBD();

        $sql = "SELECT 
                    ot.id,
                    ot.estado,
                    ot.sintomas_cliente,
                    ot.fecha_creacion,
                    v.matricula,
                    v.marca,
                    v.modelo,
                    CONCAT(cliente.nombre_completo) AS nombre_cliente,
                    CONCAT(mecanico.nombre_completo) AS nombre_mecanico,
                    ot.asignado_a_id
                FROM ordenes_trabajo ot
                INNER JOIN vehiculos v ON ot.vehiculo_id = v.id
                INNER JOIN usuarios cliente ON v.cliente_id = cliente.id
                LEFT JOIN usuarios mecanico ON ot.asignado_a_id = mecanico.id
                WHERE ot.taller_id = ?
                ORDER BY ot.fecha_creacion DESC";

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

    // ─────────────────────────────────────────────
    // Obtener una orden por ID (con datos completos)
    // ─────────────────────────────────────────────
    function obtenerOrdenPorId($orden_id, $taller_id) {
        $conn = conectaBD();

        $sql = "SELECT 
                    ot.*,
                    v.matricula, v.marca, v.modelo, v.anio,
                    CONCAT(cliente.nombre_completo) AS nombre_cliente,
                    cliente.telefono AS telefono_cliente,
                    CONCAT(mecanico.nombre_completo) AS nombre_mecanico
                FROM ordenes_trabajo ot
                INNER JOIN vehiculos v ON ot.vehiculo_id = v.id
                INNER JOIN usuarios cliente ON v.cliente_id = cliente.id
                LEFT JOIN usuarios mecanico ON ot.asignado_a_id = mecanico.id
                WHERE ot.id = ? AND ot.taller_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $orden_id, $taller_id);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();

        $stmt->close();
        $conn->close();
        return $orden;
    }

    // ─────────────────────────────────────────────
    // Obtener mecánicos disponibles del taller
    // ─────────────────────────────────────────────
    function obtenerMecanicos($taller_id) {
        $conn = conectaBD();

        $sql = "SELECT id, nombre_completo, telefono
                FROM usuarios
                WHERE taller_id = ? AND rol = 'mecanico'
                ORDER BY nombre_completo ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taller_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $mecanicos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $mecanicos[] = $fila;
        }

        $stmt->close();
        $conn->close();
        return $mecanicos;
    }

    // ─────────────────────────────────────────────
    // Asignar orden a mecánico + asignar sus tareas
    // ─────────────────────────────────────────────
    function asignarOrdenAMecanico($orden_id, $mecanico_id, $supervisor_id, $taller_id) {
        $conn = conectaBD();
        $conn->begin_transaction();

        try {
            // 1. Actualizar la orden: asignar mecánico, supervisor y cambiar estado
            $sql_orden = "UPDATE ordenes_trabajo 
                          SET asignado_a_id = ?, 
                              supervisor_id = ?,
                              estado = 'en_reparacion'
                          WHERE id = ? AND taller_id = ?";
            $stmt = $conn->prepare($sql_orden);
            $stmt->bind_param("iiii", $mecanico_id, $supervisor_id, $orden_id, $taller_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("No se encontró la orden o no pertenece a este taller.");
            }
            $stmt->close();

            // 2. Asignar el mecánico a TODAS las tareas pendientes de esta orden
            $sql_tareas = "UPDATE tareas_asignadas 
                           SET mecanico_id = ?, estado = 'pendiente'
                           WHERE orden_trabajo_id = ? AND estado = 'pendiente'";
            $stmt2 = $conn->prepare($sql_tareas);
            $stmt2->bind_param("ii", $mecanico_id, $orden_id);
            $stmt2->execute();
            $stmt2->close();

            $conn->commit();
            $conn->close();
            return ['exito' => true, 'mensaje' => 'Orden asignada correctamente.'];

        } catch (Exception $e) {
            $conn->rollback();
            $conn->close();
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }
?>