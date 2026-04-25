<?php
    // ─────────────────────────────────────────────
    // Endpoint AJAX: predicción de coste y tiempo
    // Entrada POST: vehiculo_id, subgrupo_id
    // Salida JSON: { ok, horas, minutos, coste, modelo_usado } | { ok:false, error }
    //
    // Flujo: enriquece con datos de BD (marca/modelo/año/km/tarifa/minutos
    // estimados del subgrupo) y delega la inferencia a modelos_ml/predecir.py.
    // ─────────────────────────────────────────────

    header('Content-Type: application/json');

    // ── Configurable: binario de Python ───────────────
    // Si 'python' no está en el PATH del servidor web, pon aquí la ruta
    // absoluta, p.ej. 'C:\\Users\\tu_usuario\\AppData\\Local\\Programs\\Python\\Python311\\python.exe'
    define('PYTHON_BIN', 'python');

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    require_once __DIR__ . '/../modelo/m_ia.php';

    $taller_id   = (int)$_SESSION['taller_id'];
    $vehiculo_id = isset($_POST['vehiculo_id']) ? (int)$_POST['vehiculo_id'] : 0;
    $subgrupo_id = isset($_POST['subgrupo_id']) ? (int)$_POST['subgrupo_id'] : 0;

    if ($vehiculo_id <= 0 || $subgrupo_id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Parámetros vehiculo_id y subgrupo_id son obligatorios']);
        exit;
    }

    // ── 1. Enriquecer desde BD ────────────────────────
    $vehiculo = obtenerVehiculoParaPrediccion($vehiculo_id, $taller_id);
    if (!$vehiculo) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Vehículo no encontrado en este taller']);
        exit;
    }

    $subgrupo = obtenerSubgrupoParaPrediccion($subgrupo_id);
    if (!$subgrupo) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Subgrupo de reparación no encontrado']);
        exit;
    }

    $tarifa = obtenerTarifaHoraTaller($taller_id);

    $payload = [
        'taller_id'              => $taller_id,
        'subgrupo_nombre'        => $subgrupo['nombre'],
        'marca'                  => $vehiculo['marca'],
        'modelo'                 => $vehiculo['modelo'],
        'anio'                   => $vehiculo['anio'],
        'kilometraje'            => $vehiculo['ultimo_kilometraje'],
        'minutos_estimados_base' => $subgrupo['minutos_estimados_base'],
        'tarifa_hora_base'       => $tarifa,
    ];

    // ── 2. Llamar a Python (stdin JSON -> stdout JSON) ─
    $script = __DIR__ . '/../modelos_ml/predecir.py';
    if (!is_file($script)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Script de predicción no encontrado']);
        exit;
    }

    $cmd = escapeshellarg(PYTHON_BIN) . ' ' . escapeshellarg($script);

    $descriptores = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],  // stderr
    ];

    $proc = proc_open($cmd, $descriptores, $pipes);
    if (!is_resource($proc)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'No se pudo ejecutar Python']);
        exit;
    }

    fwrite($pipes[0], json_encode($payload));
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code   = proc_close($proc);

    if ($code !== 0 || $stdout === '' || $stdout === false) {
        http_response_code(500);
        echo json_encode([
            'ok'     => false,
            'error'  => 'Fallo al ejecutar el predictor',
            'detalle'=> trim($stderr),
            'code'   => $code,
        ]);
        exit;
    }

    // Devolver tal cual el JSON que imprime Python.
    // Validamos que sea JSON y, si no, lo envolvemos.
    $decoded = json_decode($stdout, true);
    if ($decoded === null) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Salida no válida del predictor', 'raw' => $stdout]);
        exit;
    }

    echo json_encode($decoded);
?>
