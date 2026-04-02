<?php
    // ─────────────────────────────────────────────
    // Controlador: Formulario crear / editar producto
    // GET  ?action=stockFormulario          → crear
    // GET  ?action=stockFormulario&id=X     → editar
    // POST                                  → guardar
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    // Solo roles con permisos de escritura
    $roles_permitidos = ['ceo', 'jefe', 'recepcionista'];
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header("Location: index.php?action=stock");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_stock.php';

    $taller_id  = (int)$_SESSION['taller_id'];
    $producto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $es_edicion  = $producto_id > 0;
    $error       = null;

    // ── POST: guardar ──────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
        $es_edicion  = $producto_id > 0;

        // Recoger y sanear datos
        $datos = [
            'referencia_sku'     => trim($_POST['referencia_sku'] ?? ''),
            'nombre'             => trim($_POST['nombre']         ?? ''),
            'cantidad_stock'     => max(0, (int)($_POST['cantidad_stock']     ?? 0)),
            'alerta_stock_minimo'=> max(0, (int)($_POST['alerta_stock_minimo']?? 5)),
            'precio_compra'      => max(0, (float)str_replace(',', '.', $_POST['precio_compra'] ?? 0)),
            'precio_venta'       => max(0, (float)str_replace(',', '.', $_POST['precio_venta']  ?? 0)),
        ];

        // Validaciones básicas
        if (empty($datos['nombre'])) {
            $error = "El nombre del producto es obligatorio.";
        } elseif ($datos['precio_venta'] <= 0) {
            $error = "El precio de venta debe ser mayor que 0.";
        } elseif ($datos['precio_compra'] < 0) {
            $error = "El precio de compra no puede ser negativo.";
        } else {
            $resultado = $es_edicion
                ? actualizarProducto($producto_id, $taller_id, $datos)
                : crearProducto($taller_id, $datos);

            if ($resultado['exito']) {
                $param = $es_edicion ? 'editado' : 'creado';
                header("Location: index.php?action=stock&{$param}=1");
                exit;
            } else {
                $error = $resultado['mensaje'];
            }
        }

        // Si hay error, repoblar el formulario con lo que envió el usuario
        $producto = $datos;
        $producto['id'] = $producto_id;

    } else {
        // ── GET: cargar producto si es edición ─────
        if ($es_edicion) {
            $producto = obtenerProductoPorId($producto_id, $taller_id);
            if (!$producto) {
                header("Location: index.php?action=stock");
                exit;
            }
        } else {
            // Valores por defecto para nuevo producto
            $producto = [
                'id'                  => 0,
                'referencia_sku'      => '',
                'nombre'              => '',
                'cantidad_stock'      => 0,
                'alerta_stock_minimo' => 5,
                'precio_compra'       => '',
                'precio_venta'        => '',
            ];
        }
    }

    require_once __DIR__ . '/../vista/v_stockFormulario.php';
?>
