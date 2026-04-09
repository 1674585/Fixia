<?php
    // ─────────────────────────────────────────────
    // Controlador: Cambiar contraseña de un usuario
    // Solo acepta POST. Redirige siempre.
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    if (!in_array($_SESSION['rol'], ['ceo', 'jefe'])) {
        header("Location: index.php?action=home");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: index.php?action=usuarios");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_usuarios.php';

    $taller_id   = (int)$_SESSION['taller_id'];
    $usuario_id  = isset($_POST['usuario_id'])      ? (int)$_POST['usuario_id']      : 0;
    $nueva_pass  = $_POST['nueva_password']         ?? '';
    $confirmar   = $_POST['confirmar_password']     ?? '';

    if ($usuario_id === 0 || empty($nueva_pass)) {
        header("Location: index.php?action=usuarios&error=datos_invalidos");
        exit;
    }

    if ($nueva_pass !== $confirmar) {
        header("Location: index.php?action=usuarios&error=passwords_no_coinciden");
        exit;
    }

    if (strlen($nueva_pass) < 8) {
        header("Location: index.php?action=usuarios&error=password_corta");
        exit;
    }

    $resultado = cambiarPasswordUsuario($usuario_id, $taller_id, $nueva_pass);

    if ($resultado['exito']) {
        header("Location: index.php?action=usuarios&password=1");
    } else {
        $msg = urlencode($resultado['mensaje']);
        header("Location: index.php?action=usuarios&error={$msg}");
    }
    exit;
?>