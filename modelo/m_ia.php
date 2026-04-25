<?php
    require_once __DIR__ . '/m_conecta.php';

    // ─────────────────────────────────────────────────────────────────
    // Módulo IA — consulta de tareas finalizadas de órdenes facturadas
    // y exportación a CSV para entrenamiento de modelos ML.
    //
    // Una fila = una tarea (tareas_asignadas) con contexto completo
    // (taller, vehículo, catálogo, subgrupo, repuestos, duración).
    // ─────────────────────────────────────────────────────────────────

    // SQL común: trae todas las columnas necesarias para el CSV.
    // Se filtra opcionalmente por taller_id; si $taller_id es null, trae todos.
    function _queryTareasParaCsv($taller_id = null) {
        $conn = conectaBD();

        $where_taller = $taller_id !== null ? " AND ot.taller_id = ? " : "";

        $sql = "
            SELECT
                ta.id                              AS tarea_id,
                ot.id                              AS orden_id,
                ot.taller_id                       AS taller_id,
                t.tarifa_hora_base                 AS tarifa_hora_base,
                ct.id                              AS catalogo_tarea_id,
                ct.nombre_tarea                    AS nombre_tarea,
                ct.minutos_estimados_base          AS minutos_estimados_base,
                sg.id                              AS subgrupo_id,
                sg.nombre                          AS subgrupo_nombre,
                tr.id                              AS tipo_reparacion_id,
                tr.nombre                          AS tipo_reparacion_nombre,
                v.marca                            AS marca,
                v.modelo                           AS modelo,
                v.anio                             AS anio,
                v.ultimo_kilometraje               AS kilometraje,
                ta.duracion_real_minutos           AS duracion_real_minutos,
                COALESCE(SUM(rt.cantidad * rt.precio_unidad_momento), 0) AS coste_repuestos
            FROM tareas_asignadas ta
            INNER JOIN ordenes_trabajo  ot ON ot.id = ta.orden_trabajo_id
            INNER JOIN talleres         t  ON t.id  = ot.taller_id
            INNER JOIN vehiculos        v  ON v.id  = ot.vehiculo_id
            INNER JOIN catalogo_tareas  ct ON ct.id = ta.tarea_catalogo_id
            LEFT  JOIN subgrupos_reparacion sg ON sg.nombre = ct.nombre_tarea
            LEFT  JOIN tipos_reparacion  tr ON tr.id  = sg.tipo_reparacion_id
            LEFT  JOIN repuestos_tarea   rt ON rt.tarea_asignada_id = ta.id
            WHERE ta.estado = 'finalizada'
              AND ot.estado = 'facturado'
              AND ta.duracion_real_minutos IS NOT NULL
              AND ta.duracion_real_minutos > 0
              {$where_taller}
            GROUP BY ta.id
            ORDER BY ot.taller_id, ta.id
        ";

        $stmt = $conn->prepare($sql);
        if ($taller_id !== null) {
            $stmt->bind_param("i", $taller_id);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Talleres que tienen al menos una tarea facturada+finalizada
    function obtenerTalleresConTareasFacturadas() {
        $conn = conectaBD();
        $sql = "
            SELECT DISTINCT t.id, t.nombre
            FROM talleres t
            INNER JOIN ordenes_trabajo  ot ON ot.taller_id = t.id
            INNER JOIN tareas_asignadas ta ON ta.orden_trabajo_id = ot.id
            WHERE ta.estado = 'finalizada'
              AND ot.estado = 'facturado'
              AND ta.duracion_real_minutos IS NOT NULL
              AND ta.duracion_real_minutos > 0
            ORDER BY t.id
        ";
        $res = $conn->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    // Convierte una fila cruda a una fila CSV con los campos derivados
    function _filaCsv($row) {
        $horas_reales    = round($row['duracion_real_minutos'] / 60, 4);
        $coste_mano_obra = round($horas_reales * (float)$row['tarifa_hora_base'], 2);
        $coste_repuestos = round((float)$row['coste_repuestos'], 2);
        $coste_total     = round($coste_mano_obra + $coste_repuestos, 2);

        return [
            'tarea_id'               => $row['tarea_id'],
            'orden_id'               => $row['orden_id'],
            'taller_id'              => $row['taller_id'],
            'tarifa_hora_base'       => $row['tarifa_hora_base'],
            'catalogo_tarea_id'      => $row['catalogo_tarea_id'],
            'nombre_tarea'           => $row['nombre_tarea'],
            'minutos_estimados_base' => $row['minutos_estimados_base'],
            'subgrupo_id'            => $row['subgrupo_id'],
            'subgrupo_nombre'        => $row['subgrupo_nombre'],
            'tipo_reparacion_id'     => $row['tipo_reparacion_id'],
            'tipo_reparacion_nombre' => $row['tipo_reparacion_nombre'],
            'marca'                  => $row['marca'],
            'modelo'                 => $row['modelo'],
            'anio'                   => $row['anio'],
            'kilometraje'            => $row['kilometraje'],
            'duracion_real_minutos'  => $row['duracion_real_minutos'],
            'horas_reales'           => $horas_reales,
            'coste_mano_obra'        => $coste_mano_obra,
            'coste_repuestos'        => $coste_repuestos,
            'coste_total'            => $coste_total,
        ];
    }

    // Escribe un array de filas a un CSV. Sobrescribe si existe.
    // Devuelve [nº filas escritas, ruta]
    function _escribirCsv($ruta, $filas) {
        $fh = fopen($ruta, 'w');
        if ($fh === false) {
            throw new Exception("No se pudo abrir el archivo: $ruta");
        }

        $cabeceras = [
            'tarea_id', 'orden_id', 'taller_id', 'tarifa_hora_base',
            'catalogo_tarea_id', 'nombre_tarea', 'minutos_estimados_base',
            'subgrupo_id', 'subgrupo_nombre',
            'tipo_reparacion_id', 'tipo_reparacion_nombre',
            'marca', 'modelo', 'anio', 'kilometraje',
            'duracion_real_minutos', 'horas_reales',
            'coste_mano_obra', 'coste_repuestos', 'coste_total'
        ];
        fputcsv($fh, $cabeceras);

        foreach ($filas as $fila) {
            fputcsv($fh, $fila);
        }
        fclose($fh);

        return [count($filas), $ruta];
    }

    // Regenera el CSV de un taller específico. Devuelve nº de filas.
    function regenerarCsvTaller($taller_id, $carpeta_csv) {
        $datos = _queryTareasParaCsv($taller_id);
        $filas = array_map('_filaCsv', $datos);
        $ruta  = rtrim($carpeta_csv, '/\\') . DIRECTORY_SEPARATOR . "taller_{$taller_id}.csv";
        [$n, ] = _escribirCsv($ruta, $filas);
        return $n;
    }

    // Regenera el CSV general (todos los talleres).
    function regenerarCsvGeneral($carpeta_csv) {
        $datos = _queryTareasParaCsv(null);
        $filas = array_map('_filaCsv', $datos);
        $ruta  = rtrim($carpeta_csv, '/\\') . DIRECTORY_SEPARATOR . "general.csv";
        [$n, ] = _escribirCsv($ruta, $filas);
        return $n;
    }

    // ─── Lookups para el endpoint de predicción ──────────────────────

    // Datos del vehículo para contextualizar la predicción.
    // Valida además que pertenece al taller (multi-tenant).
    function obtenerVehiculoParaPrediccion($vehiculo_id, $taller_id) {
        $conn = conectaBD();
        $stmt = $conn->prepare("
            SELECT id, marca, modelo, anio, ultimo_kilometraje
            FROM vehiculos
            WHERE id = ? AND taller_id = ?
        ");
        $stmt->bind_param("ii", $vehiculo_id, $taller_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Datos del subgrupo (nombre + minutos estimados base).
    function obtenerSubgrupoParaPrediccion($subgrupo_id) {
        $conn = conectaBD();
        $stmt = $conn->prepare("
            SELECT id, nombre, minutos_estimados_base
            FROM subgrupos_reparacion
            WHERE id = ?
        ");
        $stmt->bind_param("i", $subgrupo_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Tarifa hora base del taller.
    function obtenerTarifaHoraTaller($taller_id) {
        $conn = conectaBD();
        $stmt = $conn->prepare("SELECT tarifa_hora_base FROM talleres WHERE id = ?");
        $stmt->bind_param("i", $taller_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (float)$row['tarifa_hora_base'] : null;
    }

    // Estado actual de los CSVs de la carpeta: por cada archivo,
    // nº de filas (sin cabecera) y fecha de última modificación.
    function estadoCsvs($carpeta_csv) {
        $estado = [];
        if (!is_dir($carpeta_csv)) return $estado;

        foreach (glob(rtrim($carpeta_csv, '/\\') . DIRECTORY_SEPARATOR . '*.csv') as $archivo) {
            $filas = 0;
            $fh = fopen($archivo, 'r');
            if ($fh) {
                while (fgets($fh) !== false) $filas++;
                fclose($fh);
                $filas = max(0, $filas - 1); // descontar cabecera
            }
            $estado[] = [
                'archivo'           => basename($archivo),
                'filas'             => $filas,
                'ultima_modificado' => date('Y-m-d H:i:s', filemtime($archivo)),
                'tamanyo_bytes'     => filesize($archivo),
            ];
        }
        return $estado;
    }

    // Estado actual de los modelos .pkl en la carpeta modelos_ml/.
    function estadoModelos($carpeta_modelos) {
        $estado = [];
        if (!is_dir($carpeta_modelos)) return $estado;

        foreach (glob(rtrim($carpeta_modelos, '/\\') . DIRECTORY_SEPARATOR . '*.pkl') as $archivo) {
            $estado[] = [
                'archivo'           => basename($archivo),
                'tamanyo_bytes'     => filesize($archivo),
                'ultima_modificado' => date('Y-m-d H:i:s', filemtime($archivo)),
            ];
        }
        return $estado;
    }
?>
