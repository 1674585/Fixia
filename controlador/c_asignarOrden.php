<?php
    // ─────────────────────────────────────────────
    // Controlador: Asignar orden de trabajo
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    // Solo jefes y CEO pueden asignar órdenes
    $roles_permitidos = ['ceo', 'jefe'];
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header("Location: index.php?action=ordenesTrabajo");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_ordenesTrabajo.php';

    $taller_id    = $_SESSION['taller_id'];
    $supervisor_id = $_SESSION['user_id'];
    $orden_id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $error        = null;
    $exito        = null;

    if ($orden_id === 0) {
        header("Location: index.php?action=ordenesTrabajo");
        exit;
    }

    // ── POST: procesar asignación ──────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mecanico_id = isset($_POST['mecanico_id']) ? (int)$_POST['mecanico_id'] : 0;

        if ($mecanico_id === 0) {
            $error = "Debes seleccionar un mecánico.";
        } else {
            $resultado = asignarOrdenAMecanico($orden_id, $mecanico_id, $supervisor_id, $taller_id);

            if ($resultado['exito']) {
                // Redirigir con mensaje de éxito
                header("Location: index.php?action=ordenesTrabajo&asignada=1");
                exit;
            } else {
                $error = $resultado['mensaje'];
            }
        }
    }

    // ── GET: cargar datos para el formulario ───────
    $orden    = obtenerOrdenPorId($orden_id, $taller_id);
    $mecanicos = obtenerMecanicos($taller_id);

    if (!$orden) {
        header("Location: index.php?action=ordenesTrabajo");
        exit;
    }

    require_once __DIR__ . '/../vista/v_asignarOrden.php';