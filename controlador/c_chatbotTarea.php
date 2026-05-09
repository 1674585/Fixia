<?php
    // ─────────────────────────────────────────────────────────────────
    // Controlador AJAX: Chatbot de ayuda al mecánico
    // - Recibe POST JSON: { tarea_id, mensaje, historial }
    // - Carga contexto de la tarea (marca, modelo, año, síntomas...)
    // - Llama a Groq y devuelve la respuesta
    // ─────────────────────────────────────────────────────────────────

    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Sesión expirada.']);
        exit;
    }

    $roles_permitidos = ['ceo', 'jefe', 'recepcionista', 'mecanico'];
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Sin permisos.']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
        exit;
    }

    require_once __DIR__ . '/../modelo/m_misTareas.php';
    require_once __DIR__ . '/../modelo/m_chatbotTarea.php';

    // ── Leer cuerpo JSON ──────────────────────────────────────────
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $tarea_id  = isset($body['tarea_id']) ? (int)$body['tarea_id'] : 0;
    $mensaje   = trim((string)($body['mensaje'] ?? ''));
    $historial = is_array($body['historial'] ?? null) ? $body['historial'] : [];

    if ($tarea_id === 0 || $mensaje === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Faltan datos (tarea_id o mensaje).']);
        exit;
    }

    if (mb_strlen($mensaje) > 2000) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Mensaje demasiado largo (máx 2000 caracteres).']);
        exit;
    }

    // ── Cargar tarea (verifica que pertenece al mecánico/taller) ──
    $mecanico_id = (int)$_SESSION['user_id'];
    $taller_id   = (int)$_SESSION['taller_id'];

    $tarea = obtenerTareaPorId($tarea_id, $mecanico_id, $taller_id);
    if (!$tarea) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Tarea no encontrada o sin acceso.']);
        exit;
    }

    // ── Construir mensajes para Groq ──────────────────────────────
    $mensajes = [];
    $mensajes[] = [
        'role'    => 'system',
        'content' => construirSystemPromptChatbot($tarea),
    ];

    // Historial recortado: últimos 10 turnos como máximo (5 user + 5 assistant)
    $historial_corto = array_slice($historial, -10);
    foreach ($historial_corto as $turno) {
        $rol = $turno['role']    ?? '';
        $txt = trim((string)($turno['content'] ?? ''));
        if (in_array($rol, ['user', 'assistant'], true) && $txt !== '') {
            $mensajes[] = ['role' => $rol, 'content' => $txt];
        }
    }

    $mensajes[] = ['role' => 'user', 'content' => $mensaje];

    // ── Llamar a Groq ─────────────────────────────────────────────
    $resp = llamarGroq($mensajes);

    if (!$resp['ok']) {
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => $resp['error']]);
        exit;
    }

    echo json_encode([
        'ok'        => true,
        'respuesta' => $resp['respuesta'],
    ]);
    exit;
?>
