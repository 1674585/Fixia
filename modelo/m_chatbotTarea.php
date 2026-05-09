<?php
    // ─────────────────────────────────────────────────────────────────
    // Cliente para el chatbot del mecánico (Groq Cloud).
    //
    // - Lee la API key desde .env de la raíz del proyecto.
    // - Centraliza la llamada a la Chat Completions API de Groq.
    //
    // .env esperado:
    //   GROQ_API_KEY=gsk_xxxxxxxxxxxx
    //   GROQ_MODEL=llama-3.3-70b-versatile
    // ─────────────────────────────────────────────────────────────────

    /**
     * Carga el .env de la raíz una única vez en la request.
     * Parser minimalista: KEY=VALUE, ignora comentarios (#) y líneas vacías.
     * Soporta valores entre comillas dobles o simples.
     */
    function cargarEnvFixia() {
        static $cargado = false;
        if ($cargado) return;
        $cargado = true;

        $ruta = __DIR__ . '/../.env';
        if (!is_file($ruta) || !is_readable($ruta)) return;

        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#') continue;

            $pos = strpos($linea, '=');
            if ($pos === false) continue;

            $clave = trim(substr($linea, 0, $pos));
            $valor = trim(substr($linea, $pos + 1));

            // Quitar comillas envolventes si las hay
            if (strlen($valor) >= 2) {
                $primero = $valor[0];
                $ultimo  = $valor[strlen($valor) - 1];
                if (($primero === '"' && $ultimo === '"') ||
                    ($primero === "'" && $ultimo === "'")) {
                    $valor = substr($valor, 1, -1);
                }
            }

            if ($clave !== '' && getenv($clave) === false) {
                putenv("$clave=$valor");
                $_ENV[$clave]    = $valor;
                $_SERVER[$clave] = $valor;
            }
        }
    }

    /**
     * Llama a Groq Chat Completions con el array de mensajes dado.
     *
     * @param array $mensajes  [{role:'system'|'user'|'assistant', content:'...'}, ...]
     * @return array           ['ok'=>bool, 'respuesta'=>string|null, 'error'=>string|null]
     */
    function llamarGroq(array $mensajes) {
        cargarEnvFixia();

        $api_key = getenv('GROQ_API_KEY') ?: '';
        $modelo  = getenv('GROQ_MODEL')   ?: 'llama-3.3-70b-versatile';

        if ($api_key === '') {
            return ['ok' => false, 'respuesta' => null, 'error' => 'No se ha configurado GROQ_API_KEY en .env'];
        }

        $payload = [
            'model'       => $modelo,
            'messages'    => $mensajes,
            'temperature' => 0.4,
            'max_tokens'  => 1024,
        ];

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $api_key,
            ],
        ]);

        $raw       = curl_exec($ch);
        $errno     = curl_errno($ch);
        $errmsg    = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return ['ok' => false, 'respuesta' => null, 'error' => "Error de conexión con Groq: $errmsg"];
        }

        $data = json_decode($raw, true);

        if ($http_code >= 400) {
            $msg = $data['error']['message'] ?? "Groq devolvió HTTP $http_code";
            return ['ok' => false, 'respuesta' => null, 'error' => $msg];
        }

        $contenido = $data['choices'][0]['message']['content'] ?? null;
        if (!$contenido) {
            return ['ok' => false, 'respuesta' => null, 'error' => 'Respuesta vacía de Groq.'];
        }

        return ['ok' => true, 'respuesta' => trim($contenido), 'error' => null];
    }

    /**
     * Construye el prompt de sistema con el contexto del vehículo y la tarea.
     */
    function construirSystemPromptChatbot(array $tarea) {
        $marca   = $tarea['marca']            ?? '';
        $modelo  = $tarea['modelo']           ?? '';
        $anio    = $tarea['anio']             ?? '';
        $matric  = $tarea['matricula']        ?? '';
        $nombre  = $tarea['nombre_tarea']     ?? '';
        $sint    = trim((string)($tarea['sintomas_cliente'] ?? ''));

        $sint_txt = $sint !== '' ? $sint : '(no se han registrado síntomas)';

        return
            "Eres un asistente experto en mecánica del automóvil. Ayudas a un mecánico " .
            "profesional resolviendo dudas técnicas sobre la tarea que está realizando.\n\n" .
            "CONTEXTO DEL VEHÍCULO Y LA TAREA (tenlo SIEMPRE en cuenta en cada respuesta):\n" .
            "- Marca: {$marca}\n" .
            "- Modelo: {$modelo}\n" .
            "- Año: {$anio}\n" .
            "- Matrícula: {$matric}\n" .
            "- Tarea actual: {$nombre}\n" .
            "- Síntomas reportados por el cliente: {$sint_txt}\n\n" .
            "INSTRUCCIONES:\n" .
            "- Responde en español, de forma directa y técnica, como hablarías con un compañero de taller.\n" .
            "- Da pares de apriete, capacidades, referencias o procedimientos concretos cuando sea posible, " .
            "indicando si son específicos del modelo o aproximaciones generales.\n" .
            "- Si una respuesta puede variar según motorización o versión, pregunta brevemente por el motor antes de responder.\n" .
            "- Si la pregunta no tiene relación con mecánica/automoción, recuérdale amablemente que estás para ayudarle con la tarea.\n" .
            "- No inventes datos: si no estás seguro de un valor exacto, dilo claramente y sugiere consultar el manual de taller.";
    }
?>
