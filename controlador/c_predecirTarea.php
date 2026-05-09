<?php
    // ─────────────────────────────────────────────
    // Endpoint AJAX: predicción de coste y tiempo.
    // Entrada POST: vehiculo_id, subgrupo_id
    // Salida JSON: { ok, horas, minutos, coste, modelo_usado } | { ok:false, error }
    //
    // Flujo: enriquece con datos de BD (marca/modelo/año/km/tarifa/minutos
    // estimados del subgrupo) y delega la inferencia al microservicio
    // FastAPI vía HTTP (POST /predict).
    // ─────────────────────────────────────────────

    header('Content-Type: application/json');

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
    require_once __DIR__ . '/../modelo/m_mlClient.php';

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
        'anio'                   => $vehiculo['anio'] !== null ? (int)$vehiculo['anio'] : null,
        'kilometraje'            => $vehiculo['ultimo_kilometraje'] !== null ? (int)$vehiculo['ultimo_kilometraje'] : null,
        'minutos_estimados_base' => $subgrupo['minutos_estimados_base'] !== null ? (int)$subgrupo['minutos_estimados_base'] : null,
        'tarifa_hora_base'       => $tarifa,
    ];

    // ── 2. Llamar al microservicio FastAPI ────────────
    $resp = mlClientPost('/predict', $payload, ML_API_TIMEOUT);

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
?>
