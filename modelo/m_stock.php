<?php
    require_once __DIR__ . '/../modelo/m_conecta.php';

    // ─────────────────────────────────────────────────────────────────
    // Obtener todos los productos del taller con filtros opcionales
    // $filtros: ['busqueda' => string, 'alerta_stock' => bool]
    // ─────────────────────────────────────────────────────────────────
    function obtenerProductos($taller_id, $filtros = []) {
        $conn = conectaBD();

        $sql = "SELECT 
                    id,
                    referencia_sku,
                    nombre,
                    cantidad_stock,
                    alerta_stock_minimo,
                    precio_venta,
                    precio_compra,
                    -- Margen de beneficio en %
                    ROUND(((precio_venta - precio_compra) / precio_compra) * 100, 1) AS margen_pct,
                    -- Estado de stock
                    CASE 
                        WHEN cantidad_stock = 0                          THEN 'sin_stock'
                        WHEN cantidad_stock <= alerta_stock_minimo       THEN 'stock_bajo'
                        ELSE                                                  'ok'
                    END AS estado_stock
                FROM productos
                WHERE taller_id = ?";

        $params  = [$taller_id];
        $types   = "i";

        // Filtro de búsqueda por nombre o SKU
        if (!empty($filtros['busqueda'])) {
            $sql    .= " AND (nombre LIKE ? OR referencia_sku LIKE ?)";
            $busq    = '%' . $filtros['busqueda'] . '%';
            $params[] = $busq;
            $params[] = $busq;
            $types   .= "ss";
        }

        // Filtro: solo productos con stock bajo o agotado
        if (!empty($filtros['alerta_stock'])) {
            $sql .= " AND cantidad_stock <= alerta_stock_minimo";
        }

        $sql .= " ORDER BY nombre ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }

        $stmt->close();
        $conn->close();
        return $productos;
    }

    // ─────────────────────────────────────────────────────────────────
    // Obtener un producto por ID
    // ─────────────────────────────────────────────────────────────────
    function obtenerProductoPorId($producto_id, $taller_id) {
        $conn = conectaBD();

        $sql = "SELECT * FROM productos WHERE id = ? AND taller_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $producto_id, $taller_id);
        $stmt->execute();
        $producto = $stmt->get_result()->fetch_assoc();

        $stmt->close();
        $conn->close();
        return $producto;
    }

    // ─────────────────────────────────────────────────────────────────
    // Crear nuevo producto
    // ─────────────────────────────────────────────────────────────────
    function crearProducto($taller_id, $datos) {
        $conn = conectaBD();

        $sql = "INSERT INTO productos 
                    (taller_id, referencia_sku, nombre, cantidad_stock, alerta_stock_minimo, precio_venta, precio_compra)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issiidd",
            $taller_id,
            $datos['referencia_sku'],
            $datos['nombre'],
            $datos['cantidad_stock'],
            $datos['alerta_stock_minimo'],
            $datos['precio_venta'],
            $datos['precio_compra']
        );
        $stmt->execute();
        $ok = ($stmt->errno === 0);
        $nuevo_id = $conn->insert_id;

        $stmt->close();
        $conn->close();

        return $ok
            ? ['exito' => true,  'mensaje' => 'Producto creado correctamente.', 'id' => $nuevo_id]
            : ['exito' => false, 'mensaje' => 'Error al crear el producto.'];
    }

    // ─────────────────────────────────────────────────────────────────
    // Actualizar producto existente
    // ─────────────────────────────────────────────────────────────────
    function actualizarProducto($producto_id, $taller_id, $datos) {
        $conn = conectaBD();

        $sql = "UPDATE productos
                SET referencia_sku      = ?,
                    nombre              = ?,
                    cantidad_stock      = ?,
                    alerta_stock_minimo = ?,
                    precio_venta        = ?,
                    precio_compra       = ?
                WHERE id = ? AND taller_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssiiddii",
            $datos['referencia_sku'],
            $datos['nombre'],
            $datos['cantidad_stock'],
            $datos['alerta_stock_minimo'],
            $datos['precio_venta'],
            $datos['precio_compra'],
            $producto_id,
            $taller_id
        );
        $stmt->execute();
        $ok = ($stmt->errno === 0);

        $stmt->close();
        $conn->close();

        return $ok
            ? ['exito' => true,  'mensaje' => 'Producto actualizado correctamente.']
            : ['exito' => false, 'mensaje' => 'Error al actualizar el producto.'];
    }

    // ─────────────────────────────────────────────────────────────────
    // Eliminar producto (verifica que no esté en uso en repuestos_tarea)
    // ─────────────────────────────────────────────────────────────────
    function eliminarProducto($producto_id, $taller_id) {
        $conn = conectaBD();

        // Comprobar si el producto está referenciado en alguna tarea
        $sql_check = "SELECT COUNT(*) AS usos FROM repuestos_tarea WHERE producto_id = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $usos = (int)$stmt->get_result()->fetch_assoc()['usos'];
        $stmt->close();

        if ($usos > 0) {
            $conn->close();
            return [
                'exito'   => false,
                'mensaje' => "No se puede eliminar: este producto está usado en {$usos} tarea(s). Puedes poner su stock a 0 en su lugar.",
            ];
        }

        $sql = "DELETE FROM productos WHERE id = ? AND taller_id = ?";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("ii", $producto_id, $taller_id);
        $stmt2->execute();
        $ok = ($stmt2->affected_rows > 0);
        $stmt2->close();
        $conn->close();

        return $ok
            ? ['exito' => true,  'mensaje' => 'Producto eliminado correctamente.']
            : ['exito' => false, 'mensaje' => 'No se encontró el producto o no pertenece a este taller.'];
    }

    // ─────────────────────────────────────────────────────────────────
    // Resumen de stock para la cabecera del listado
    // ─────────────────────────────────────────────────────────────────
    function obtenerResumenStock($taller_id) {
        $conn = conectaBD();

        $sql = "SELECT
                    COUNT(*) AS total_productos,
                    SUM(CASE WHEN cantidad_stock = 0                    THEN 1 ELSE 0 END) AS sin_stock,
                    SUM(CASE WHEN cantidad_stock > 0 
                              AND cantidad_stock <= alerta_stock_minimo THEN 1 ELSE 0 END) AS stock_bajo,
                    SUM(CASE WHEN cantidad_stock > alerta_stock_minimo  THEN 1 ELSE 0 END) AS stock_ok,
                    SUM(cantidad_stock * precio_compra)                                    AS valor_inventario
                FROM productos
                WHERE taller_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taller_id);
        $stmt->execute();
        $resumen = $stmt->get_result()->fetch_assoc();

        $stmt->close();
        $conn->close();
        return $resumen;
    }
?>