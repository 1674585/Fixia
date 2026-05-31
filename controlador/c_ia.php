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

    // El jefe solo puede operar sobre su propio taller; el ceo opera sobre todos.
    $es_jefe          = $_SESSION['rol'] === 'jefe';
    $taller_propio_id = (int)$_SESSION['taller_id'];

    // ── Petición AJAX: regenerar CSVs ─────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['accion'] ?? '') === 'regenerar_csvs') {

        header('Content-Type: application/json');

        try {
            if (!is_dir($carpeta_csv)) {
                throw new Exception("La carpeta csv/ no existe.");
            }

            if ($es_jefe) {
                // El jefe solo regenera el CSV de su taller. No toca general.csv.
                $talleres = array_values(array_filter(
                    obtenerTalleresConTareasFacturadas(),
                    fn($t) => (int)$t['id'] === $taller_propio_id
                ));
            } else {
                $talleres = obtenerTalleresConTareasFacturadas();
            }

            $resumen = [];
            foreach ($talleres as $taller) {
                $n = regenerarCsvTaller((int)$taller['id'], $carpeta_csv);
                $resumen[] = [
                    'taller_id'     => (int)$taller['id'],
                    'taller_nombre' => $taller['nombre'],
                    'filas'         => $n,
                    'archivo'       => "taller_{$taller['id']}.csv",
                ];
            }

            $general = null;
            if (!$es_jefe) {
                $n_general = regenerarCsvGeneral($carpeta_csv);
                $general   = ['archivo' => 'general.csv', 'filas' => $n_general];
            }

            echo json_encode([
                'ok'       => true,
                'general'  => $general,
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

        // El jefe solo entrena el modelo de su taller; el ceo entrena todos.
        $payload = $es_jefe ? ['taller_id' => $taller_propio_id] : [];
        $resp    = mlClientPost('/train', $payload, ML_API_TRAIN_TIMEOUT);

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

    if ($es_jefe) {
        // El jefe solo ve datos relativos a su taller.
        $archivo_csv_propio    = "taller_{$taller_propio_id}.csv";
        $archivo_modelo_propio = "taller_{$taller_propio_id}.pkl";

        $talleres_disponibles = array_values(array_filter(
            $talleres_disponibles,
            fn($t) => (int)$t['id'] === $taller_propio_id
        ));
        $estado_csvs = array_values(array_filter(
            $estado_csvs,
            fn($a) => $a['archivo'] === $archivo_csv_propio
        ));
        $estado_modelos = array_values(array_filter(
            $estado_modelos,
            fn($m) => $m['archivo'] === $archivo_modelo_propio
        ));
    }

    require_once __DIR__ . '/../vista/v_ia.php';
?>
