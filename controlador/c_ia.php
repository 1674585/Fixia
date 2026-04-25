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

    // Si 'python' no está en el PATH del servidor, cambia a ruta absoluta.
    if (!defined('PYTHON_BIN')) define('PYTHON_BIN', 'python');

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

        $script = realpath($carpeta_modelos . '/entrenar.py');
        if (!$script || !is_file($script)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se encontró entrenar.py']);
            exit;
        }

        $cmd = escapeshellarg(PYTHON_BIN) . ' ' . escapeshellarg($script);

        $descriptores = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptores, $pipes, dirname($script));
        if (!is_resource($proc)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo ejecutar Python']);
            exit;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $code   = proc_close($proc);

        echo json_encode([
            'ok'      => $code === 0,
            'code'    => $code,
            'stdout'  => trim($stdout),
            'stderr'  => trim($stderr),
        ]);
        exit;
    }

    // ── GET: cargar estado actual y pintar la vista ───
    $talleres_disponibles = obtenerTalleresConTareasFacturadas();
    $estado_csvs          = estadoCsvs($carpeta_csv);
    $estado_modelos       = estadoModelos($carpeta_modelos);

    require_once __DIR__ . '/../vista/v_ia.php';
?>
