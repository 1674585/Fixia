<?php
    // ─────────────────────────────────────────────
    // Controlador: Módulo IA
    // - Regenera los CSVs de la carpeta csv/
    // - Muestra el estado de los archivos
    // Solo accesible por ceo y jefe
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    if (!in_array($_SESSION['rol'], ['ceo', 'jefe'])) {
        header("Location: index.php?action=home");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_ia.php';
    require_once __DIR__ . '/../modelo/m_mlClient.php';

    $carpeta_csv      = __DIR__ . '/../csv';
    $carpeta_modelos  = __DIR__ . '/../modelos_ml';

    // ── Petición AJAX: regenerar CSVs ─────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['accion'] ?? '') === 'regenerar_csvs') {

        header('Content-Type: application/json');

        try {
            if (!is_dir($carpeta_csv)) {
                throw new Exception("La carpeta csv/ no existe.");
            }

            $talleres = obtenerTalleresConTareasFacturadas();
            $resumen  = [];

            foreach ($talleres as $taller) {
                $n = regenerarCsvTaller((int)$taller['id'], $carpeta_csv);
                $resumen[] = [
                    'taller_id'     => (int)$taller['id'],
                    'taller_nombre' => $taller['nombre'],
                    'filas'         => $n,
                    'archivo'       => "taller_{$taller['id']}.csv",
                ];
            }

            $n_general = regenerarCsvGeneral($carpeta_csv);

            echo json_encode([
                'ok'       => true,
                'general'  => ['archivo' => 'general.csv', 'filas' => $n_general],
                'talleres' => $resumen,
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    // ── Petición AJAX: entrenar modelos ───────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['accion'] ?? '') === 'entrenar_modelos') {

        header('Content-Type: application/json');

        $resp = mlClientPost('/train', [], ML_API_TRAIN_TIMEOUT);

        if ($resp['error'] !== null) {
            http_response_code(503);
            echo json_encode(['ok' => false, 'error' => $resp['error']]);
            exit;
        }

        if ($resp['status'] >= 400) {
            $mensaje = $resp['data']['error'] ?? 'Error en el microservicio ML';
            http_response_code($resp['status']);
            echo json_encode(['ok' => false, 'error' => $mensaje]);
            exit;
        }

        if ($resp['data'] === null) {
            http_response_code(502);
            echo json_encode(['ok' => false, 'error' => 'Respuesta no válida del microservicio ML', 'raw' => $resp['raw']]);
            exit;
        }

        echo json_encode($resp['data']);
        exit;
    }

    // ── GET: cargar estado actual y pintar la vista ───
    $talleres_disponibles = obtenerTalleresConTareasFacturadas();
    $estado_csvs          = estadoCsvs($carpeta_csv);
    $estado_modelos       = estadoModelos($carpeta_modelos);

    require_once __DIR__ . '/../vista/v_ia.php';
?>
