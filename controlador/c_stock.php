<?php
    // ─────────────────────────────────────────────
    // Controlador: Stock — listado y eliminación
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    $roles_permitidos = ['ceo', 'jefe', 'recepcionista', 'mecanico'];
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header("Location: index.php?action=home");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_stock.php';

    $taller_id = (int)$_SESSION['taller_id'];
    $mensaje_ok  = null;
    $mensaje_err = null;

    // ── POST: eliminar producto ────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
        // Solo roles con permiso de escritura pueden eliminar
        if (!in_array($_SESSION['rol'], ['ceo', 'jefe', 'recepcionista'])) {
            $mensaje_err = "No tienes permisos para eliminar productos.";
        } else {
            $eliminar_id = (int)$_POST['eliminar_id'];
            $resultado   = eliminarProducto($eliminar_id, $taller_id);

            if ($resultado['exito']) {
                header("Location: index.php?action=stock&eliminado=1");
                exit;
            } else {
                $mensaje_err = $resultado['mensaje'];
            }
        }
    }

    // ── GET: filtros ───────────────────────────────
    $filtros = [
        'busqueda'    => trim($_GET['busqueda'] ?? ''),
        'alerta_stock' => isset($_GET['alerta_stock']),
    ];

    // Mensajes flash desde redirecciones
    if (isset($_GET['creado']))    $mensaje_ok = 'Producto añadido correctamente.';
    if (isset($_GET['editado']))   $mensaje_ok = 'Producto actualizado correctamente.';
    if (isset($_GET['eliminado'])) $mensaje_ok = 'Producto eliminado correctamente.';

    $productos = obtenerProductos($taller_id, $filtros);
    $resumen   = obtenerResumenStock($taller_id);

    require_once __DIR__ . '/../vista/v_stock.php';
?>