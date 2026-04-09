<?php
    // ─────────────────────────────────────────────
    // Controlador: Gestión de usuarios del taller
    // Solo accesible por jefe y ceo
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    if (!in_array($_SESSION['rol'], ['ceo', 'jefe'])) {
        header("Location: index.php?action=home");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_usuarios.php';

    $taller_id   = (int)$_SESSION['taller_id'];
    $jefe_id     = (int)$_SESSION['user_id'];
    $mensaje_ok  = null;
    $mensaje_err = null;

    // ── POST: eliminar usuario ─────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
        $eliminar_id = (int)$_POST['eliminar_id'];
        $resultado   = eliminarUsuario($eliminar_id, $taller_id, $jefe_id);

        if ($resultado['exito']) {
            header("Location: index.php?action=usuarios&eliminado=1");
            exit;
        } else {
            $mensaje_err = $resultado['mensaje'];
        }
    }

    // ── Mensajes flash ─────────────────────────────
    if (isset($_GET['creado']))    $mensaje_ok = 'Usuario creado correctamente.';
    if (isset($_GET['editado']))   $mensaje_ok = 'Usuario actualizado correctamente.';
    if (isset($_GET['eliminado'])) $mensaje_ok = 'Usuario eliminado correctamente.';
    if (isset($_GET['password']))  $mensaje_ok = 'Contraseña cambiada correctamente.';

    // ── Filtros ────────────────────────────────────
    $filtros = [
        'rol'      => $_GET['rol']      ?? '',
        'busqueda' => trim($_GET['busqueda'] ?? ''),
    ];

    $usuarios = obtenerUsuariosTaller($taller_id, $filtros);
    $resumen  = obtenerResumenUsuarios($taller_id);

    require_once __DIR__ . '/../vista/v_usuarios.php';
?>