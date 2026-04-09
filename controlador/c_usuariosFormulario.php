<?php
    // ─────────────────────────────────────────────
    // Controlador: Formulario crear / editar usuario
    // GET  ?action=usuariosFormulario          → crear
    // GET  ?action=usuariosFormulario&id=X     → editar
    // POST                                     → guardar
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

    $taller_id  = (int)$_SESSION['taller_id'];
    $usuario_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $es_edicion = $usuario_id > 0;
    $error      = null;

    $roles_disponibles = ['jefe', 'recepcionista', 'mecanico', 'cliente'];

    // ── POST: guardar ──────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
        $es_edicion = $usuario_id > 0;

        $datos = [
            'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
            'email'           => trim(strtolower($_POST['email'] ?? '')),
            'telefono'        => trim($_POST['telefono'] ?? ''),
            'rol'             => $_POST['rol'] ?? '',
            'password'        => $_POST['password'] ?? '',
        ];

        // Validaciones
        if (empty($datos['nombre_completo'])) {
            $error = "El nombre es obligatorio.";
        } elseif (empty($datos['email']) || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $error = "El email no es válido.";
        } elseif (!in_array($datos['rol'], $roles_disponibles)) {
            $error = "El rol seleccionado no es válido.";
        } elseif (!$es_edicion && strlen($datos['password']) < 8) {
            $error = "La contraseña debe tener al menos 8 caracteres.";
        } else {
            if ($es_edicion) {
                $resultado = actualizarUsuario($usuario_id, $taller_id, $datos);
            } else {
                $resultado = crearUsuario($taller_id, $datos);
            }

            if ($resultado['exito']) {
                $param = $es_edicion ? 'editado' : 'creado';
                header("Location: index.php?action=usuarios&{$param}=1");
                exit;
            } else {
                $error = $resultado['mensaje'];
            }
        }

        // Repoblar formulario con los datos enviados si hay error
        $usuario = $datos;
        $usuario['id'] = $usuario_id;

    } else {
        // ── GET: cargar usuario si es edición ─────
        if ($es_edicion) {
            $usuario = obtenerUsuarioPorId($usuario_id, $taller_id);
            if (!$usuario) {
                header("Location: index.php?action=usuarios");
                exit;
            }
        } else {
            $usuario = [
                'id'             => 0,
                'nombre_completo'=> '',
                'email'          => '',
                'telefono'       => '',
                'rol'            => 'mecanico',
            ];
        }
    }

    require_once __DIR__ . '/../vista/v_usuariosFormulario.php';
?>
