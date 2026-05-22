<?php
    require_once __DIR__ . '/../modelo/m_conecta.php';

    // ─────────────────────────────────────────────
    // Whitelist de columnas ordenables (anti SQL-injection)
    // ─────────────────────────────────────────────
    function _columnasOrdenablesOrdenes() {
        return [
            'fecha'    => 'ot.fecha_creacion',
            'id'       => 'ot.id',
            'estado'   => 'ot.estado',
            'mecanico' => 'mecanico.nombre_completo',
            'cliente'  => 'cliente.nombre_completo',
            'precio'   => 'ot.precio_estimado_ia',
        ];
    }

    // ─────────────────────────────────────────────
    // Obtener órdenes con filtros y ordenación dinámica.
    // $filtros: estado, fecha_desde, fecha_hasta, asignacion
    //           ('todos'|'sin_asignar'|'asignadas'|user_id),
    //           buscar (matrícula o cliente)
    // $orden_col: clave de _columnasOrdenablesOrdenes()
    // $orden_dir: 'asc' | 'desc'
    // ─────────────────────────────────────────────
    function obtenerOrdenesConFiltros($taller_id, $filtros = [], $orden_col = 'fecha', $orden_dir = 'desc') {
        $conn = conectaBD();

        $where  = ["ot.taller_id = ?"];
        $types  = "i";
        $params = [$taller_id];

        $estado = $filtros['estado'] ?? 'todos';
        if ($estado !== 'todos' && $estado !== '') {
            $where[]  = "ot.estado = ?";
            $types   .= "s";
            $params[] = $estado;
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[]  = "ot.fecha_creacion >= ?";
            $types   .= "s";
            $params[] = $filtros['fecha_desde'] . ' 00:00:00';
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = "ot.fecha_creacion <= ?";
            $types   .= "s";
            $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        $asignacion = $filtros['asignacion'] ?? 'todos';
        if ($asignacion === 'sin_asignar') {
            $where[] = "ot.asignado_a_id IS NULL";
        } elseif ($asignacion === 'asignadas') {
            $where[] = "ot.asignado_a_id IS NOT NULL";
        } elseif (is_numeric($asignacion) && (int)$asignacion > 0) {
            $where[]  = "ot.asignado_a_id = ?";
            $types   .= "i";
            $params[] = (int)$asignacion;
        }

        if (!empty($filtros['buscar'])) {
            $like = '%' . $filtros['buscar'] . '%';
            $where[]  = "(v.matricula LIKE ? OR cliente.nombre_completo LIKE ?)";
            $types   .= "ss";
            $params[] = $like;
            $params[] = $like;
        }

        // Ordenación segura por whitelist
        $columnas = _columnasOrdenablesOrdenes();
        $col      = $columnas[$orden_col] ?? $columnas['fecha'];
        $dir      = strtolower($orden_dir) === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT
                    ot.id,
                    ot.estado,
                    ot.sintomas_cliente,
                    ot.fecha_creacion,
                    ot.precio_estimado_ia,
                    ot.tiempo_estimado_ia,
                    v.matricula,
                    v.marca,
                    v.modelo,
                    CONCAT(cliente.nombre_completo)  AS nombre_cliente,
                    CONCAT(mecanico.nombre_completo) AS nombre_mecanico,
                    mecanico.rol                     AS rol_mecanico,
                    ot.asignado_a_id
                FROM ordenes_trabajo ot
                INNER JOIN vehiculos v        ON ot.vehiculo_id   = v.id
                INNER JOIN usuarios cliente   ON v.cliente_id     = cliente.id
                LEFT  JOIN usuarios mecanico  ON ot.asignado_a_id = mecanico.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$col} {$dir}, ot.id DESC";

        $stmt = $conn->prepare($sql);

        // bind_param dinámico por referencia
        $refs = [];
        $refs[] = & $types;
        foreach ($params as $k => $v) {
            $refs[] = & $params[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

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
    // Compatibilidad: listado sin filtros
    // ─────────────────────────────────────────────
    function obtenerTodasLasOrdenes($taller_id) {
        return obtenerOrdenesConFiltros($taller_id, [], 'fecha', 'desc');
    }

    // ─────────────────────────────────────────────
    // Obtener una orden por ID (con datos completos)
    // ─────────────────────────────────────────────
    function obtenerOrdenPorId($orden_id, $taller_id) {
        $conn = conectaBD();

        $sql = "SELECT
                    ot.*,
                    v.matricula, v.marca, v.modelo, v.anio,
                    CONCAT(cliente.nombre_completo)  AS nombre_cliente,
                    cliente.telefono                 AS telefono_cliente,
                    CONCAT(mecanico.nombre_completo) AS nombre_mecanico,
                    mecanico.rol                     AS rol_mecanico
                FROM ordenes_trabajo ot
                INNER JOIN vehiculos v       ON ot.vehiculo_id = v.id
                INNER JOIN usuarios cliente  ON v.cliente_id   = cliente.id
                LEFT  JOIN usuarios mecanico ON ot.asignado_a_id = mecanico.id
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
    // Usuarios a los que se puede asignar una orden:
    // mecánicos + jefes del taller (un jefe puede
    // asignarse trabajo a sí mismo).
    // ─────────────────────────────────────────────
    function obtenerUsuariosAsignables($taller_id) {
        $conn = conectaBD();

        $sql = "SELECT id, nombre_completo, telefono, rol
                FROM usuarios
                WHERE taller_id = ?
                  AND rol IN ('mecanico', 'jefe')
                ORDER BY FIELD(rol, 'mecanico', 'jefe'), nombre_completo ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taller_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $usuarios = [];
        while ($fila = $resultado->fetch_assoc()) {
            $usuarios[] = $fila;
        }
        $stmt->close();
        $conn->close();
        return $usuarios;
    }

    // Alias retrocompatible: usado en c_asignarOrden viejo y otros sitios
    function obtenerMecanicos($taller_id) {
        return obtenerUsuariosAsignables($taller_id);
    }

    // ─────────────────────────────────────────────
    // Asignar orden a un usuario (mecánico o jefe) + asignar sus tareas
    // ─────────────────────────────────────────────
    function asignarOrdenAMecanico($orden_id, $mecanico_id, $supervisor_id, $taller_id) {
        $conn = conectaBD();
        $conn->begin_transaction();

        try {
            // Validar que el destinatario pertenece al taller y tiene rol asignable
            $stmt = $conn->prepare("SELECT id FROM usuarios
                                    WHERE id = ? AND taller_id = ?
                                      AND rol IN ('mecanico','jefe')");
            $stmt->bind_param("ii", $mecanico_id, $taller_id);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                throw new Exception("Usuario no válido para asignación.");
            }
            $stmt->close();

            // 1. Actualizar la orden
            $sql_orden = "UPDATE ordenes_trabajo
                          SET asignado_a_id = ?,
                              supervisor_id = ?,
                              estado = 'en_reparacion'
                          WHERE id = ? AND taller_id = ?";
            $stmt = $conn->prepare($sql_orden);
            $stmt->bind_param("iiii", $mecanico_id, $supervisor_id, $orden_id, $taller_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                // Comprobar si la orden existe (puede que ya estuviera asignada al mismo user)
                $check = $conn->prepare("SELECT id FROM ordenes_trabajo WHERE id = ? AND taller_id = ?");
                $check->bind_param("ii", $orden_id, $taller_id);
                $check->execute();
                $existe = (bool)$check->get_result()->fetch_assoc();
                $check->close();
                if (!$existe) {
                    throw new Exception("No se encontró la orden o no pertenece a este taller.");
                }
            }
            $stmt->close();

            // 2. Asignar el destinatario a TODAS las tareas pendientes de esta orden
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
