<?php
    // ─────────────────────────────────────────────────────────────────
    // Cliente HTTP para el microservicio ML (FastAPI).
    //
    // Centraliza la configuración (URL base, API Key, timeouts) y la
    // mecánica de cURL para que los controladores no la repitan.
    //
    // Configuración por variables de entorno (con defaults):
    //   ML_API_URL     -> http://127.0.0.1:8001
    //   ML_API_KEY     -> fixia-ml-dev-key-change-me
    //   ML_API_TIMEOUT -> 30   (segundos para predict)
    //   ML_API_TRAIN_TIMEOUT -> 600 (segundos para train)
    // ─────────────────────────────────────────────────────────────────

    if (!defined('ML_API_URL')) {
        define('ML_API_URL', getenv('ML_API_URL') ?: 'http://127.0.0.1:8001');
    }
    if (!defined('ML_API_KEY')) {
        define('ML_API_KEY', getenv('ML_API_KEY') ?: 'fixia-ml-dev-key-change-me2');
    }
    if (!defined('ML_API_TIMEOUT')) {
        define('ML_API_TIMEOUT', (int)(getenv('ML_API_TIMEOUT') ?: 30));
    }
    if (!defined('ML_API_TRAIN_TIMEOUT')) {
        define('ML_API_TRAIN_TIMEOUT', (int)(getenv('ML_API_TRAIN_TIMEOUT') ?: 600));
    }

    /**
     * Realiza una llamada POST al microservicio ML.
     *
     * @param string $ruta      Ruta relativa (ej: '/predict', '/train').
     * @param array  $payload   Cuerpo JSON. Para endpoints sin body, pasar [].
     * @param int    $timeout   Timeout en segundos.
     * @return array            ['status' => int, 'data' => array|null, 'raw' => string, 'error' => string|null]
     */
    function mlClientPost($ruta, array $payload = [], $timeout = null) {
        $timeout = $timeout ?? ML_API_TIMEOUT;
        $url     = rtrim(ML_API_URL, '/') . '/' . ltrim($ruta, '/');
        $body    = empty($payload) ? '{}' : json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-Key: ' . ML_API_KEY,
            ],
        ]);

        $raw       = curl_exec($ch);
        $errno     = curl_errno($ch);
        $errmsg    = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return [
                'status' => 0,
                'data'   => null,
                'raw'    => '',
                'error'  => "Error de conexión con el microservicio ML: $errmsg",
            ];
        }

        $data = json_decode($raw, true);
        return [
            'status' => (int)$http_code,
            'data'   => is_array($data) ? $data : null,
            'raw'    => (string)$raw,
            'error'  => null,
        ];
    }
?>
