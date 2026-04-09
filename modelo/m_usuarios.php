<?php
    require_once __DIR__ . '/m_conecta.php';

    // ─────────────────────────────────────────────────────────────────
    // Obtener todos los usuarios del taller con filtros opcionales
    // ─────────────────────────────────────────────────────────────────
    function obtenerUsuariosTaller($taller_id, $filtros = []) {
        $conn   = conectaBD();
        $params = [$taller_id];
        $types  = 'i';

        $sql = "SELECT
                    u.id,
                    u.rol,
                    u.nombre_completo,
                    u.email,
                    u.telefono,
                    -- Tareas asignadas activas
                    (SELECT COUNT(*) FROM tareas_asignadas ta
                     INNER JOIN ordenes_trabajo ot ON ta.orden_trabajo_id = ot.id
                     WHERE ta.mecanico_id = u.id
                       AND ta.estado != 'finalizada') AS tareas_activas,
                    -- Órdenes creadas
                    (SELECT COUNT(*) FROM ordenes_trabajo
                     WHERE creado_por_id = u.id)       AS ordenes_creadas
                FROM usuarios u
                WHERE u.taller_id = ?";

        // Filtro por rol
        if (!empty($filtros['rol'])) {
            $sql    .= " AND u.rol = ?";
            $params[] = $filtros['rol'];
            $types   .= 's';
        }

        // Filtro por búsqueda de nombre o email
        if (!empty($filtros['busqueda'])) {
            $sql    .= " AND (u.nombre_completo LIKE ? OR u.email LIKE ?)";
            $like    = '%' . $filtros['busqueda'] . '%';
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }

        $sql .= " ORDER BY FIELD(u.rol,'jefe','recepcionista','mecanico','cliente','ceo'), u.nombre_completo ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        return $usuarios;
    }

    // ─────────────────────────────────────────────────────────────────
    // Obtener un usuario por ID verificando que pertenece al taller
    // ─────────────────────────────────────────────────────────────────
    function obtenerUsuarioPorId($usuario_id, $taller_id) {
        $conn = conectaBD();
        $sql  = "SELECT id, rol, nombre_completo, email, telefono
                 FROM usuarios
                 WHERE id = ? AND taller_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $usuario_id, $taller_id);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $usuario;
    }

    // ─────────────────────────────────────────────────────────────────
    // Crear usuario nuevo en el taller
    // ─────────────────────────────────────────────────────────────────
    function crearUsuario($taller_id, $datos) {
        $conn = conectaBD();

        // Comprobar email único dentro del taller
        $sql_check = "SELECT id FROM usuarios WHERE email = ? AND taller_id = ?";
        $stmt_chk  = $conn->prepare($sql_check);
        $stmt_chk->bind_param("si", $datos['email'], $taller_id);
        $stmt_chk->execute();
        if ($stmt_chk->get_result()->fetch_assoc()) {
            $stmt_chk->close(); $conn->close();
            return ['exito' => false, 'mensaje' => 'Ya existe un usuario con ese email en este taller.'];
        }
        $stmt_chk->close();

        $hash = password_hash($datos['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (taller_id, rol, nombre_completo, email, password_hash, telefono)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss",
            $taller_id,
            $datos['rol'],
            $datos['nombre_completo'],
            $datos['email'],
            $hash,
            $datos['telefono']
        );
        $stmt->execute();
        $ok = ($stmt->errno === 0);
        $stmt->close();
        $conn->close();

        return $ok
            ? ['exito' => true,  'mensaje' => 'Usuario creado correctamente.']
            : ['exito' => false, 'mensaje' => 'Error al crear el usuario.'];
    }

    // ─────────────────────────────────────────────────────────────────
    // Actualizar datos de un usuario (sin tocar la contraseña)
    // ─────────────────────────────────────────────────────────────────
    function actualizarUsuario($usuario_id, $taller_id, $datos) {
        $conn = conectaBD();

        // Comprobar que el email no esté en uso por otro usuario del mismo taller
        $sql_check = "SELECT id FROM usuarios WHERE email = ? AND taller_id = ? AND id != ?";
        $stmt_chk  = $conn->prepare($sql_check);
        $stmt_chk->bind_param("sii", $datos['email'], $taller_id, $usuario_id);
        $stmt_chk->execute();
        if ($stmt_chk->get_result()->fetch_assoc()) {
            $stmt_chk->close(); $conn->close();
            return ['exito' => false, 'mensaje' => 'Ese email ya está en uso por otro usuario del taller.'];
        }
        $stmt_chk->close();

        $sql = "UPDATE usuarios
                SET rol            = ?,
                    nombre_completo = ?,
                    email          = ?,
                    telefono       = ?
                WHERE id = ? AND taller_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii",
            $datos['rol'],
            $datos['nombre_completo'],
            $datos['email'],
            $datos['telefono'],
            $usuario_id,
            $taller_id
        );
        $stmt->execute();
        $ok = ($stmt->errno === 0);
        $stmt->close();
        $conn->close();

        return $ok
            ? ['exito' => true,  'mensaje' => 'Usuario actualizado correctamente.']
            : ['exito' => false, 'mensaje' => 'Error al actualizar el usuario.'];
    }

    // ─────────────────────────────────────────────────────────────────
    // Cambiar contraseña de un usuario
    // ─────────────────────────────────────────────────────────────────
    function cambiarPasswordUsuario($usuario_id, $taller_id, $nueva_password) {
        $conn = conectaBD();
        $hash = password_hash($nueva_password, PASSWORD_BCRYPT);

        $sql  = "UPDATE usuarios SET password_hash = ? WHERE id = ? AND taller_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $hash, $usuario_id, $taller_id);
        $stmt->execute();
        $ok = ($stmt->affected_rows === 1);
        $stmt->close();
        $conn->close();

        return $ok
            ? ['exito' => true,  'mensaje' => 'Contraseña actualizada correctamente.']
            : ['exito' => false, 'mensaje' => 'No se pudo actualizar la contraseña.'];
    }

    // ─────────────────────────────────────────────────────────────────
    // Eliminar usuario — con verificación de dependencias
    // No se puede eliminar si tiene tareas activas o es el propio jefe
    // ─────────────────────────────────────────────────────────────────
    function eliminarUsuario($usuario_id, $taller_id, $jefe_id) {
        if ($usuario_id === $jefe_id) {
            return ['exito' => false, 'mensaje' => 'No puedes eliminarte a ti mismo.'];
        }

        $conn = conectaBD();

        // Verificar tareas activas (en_proceso o pendiente)
        $sql_tareas = "SELECT COUNT(*) AS activas
                       FROM tareas_asignadas ta
                       INNER JOIN ordenes_trabajo ot ON ta.orden_trabajo_id = ot.id
                       WHERE ta.mecanico_id = ?
                         AND ta.estado != 'finalizada'
                         AND ot.taller_id = ?";
        $stmt = $conn->prepare($sql_tareas);
        $stmt->bind_param("ii", $usuario_id, $taller_id);
        $stmt->execute();
        $activas = (int)$stmt->get_result()->fetch_assoc()['activas'];
        $stmt->close();

        if ($activas > 0) {
            $conn->close();
            return [
                'exito'   => false,
                'mensaje' => "No se puede eliminar: el usuario tiene {$activas} tarea(s) activa(s) asignada(s). Reasígnelas primero.",
            ];
        }

        $sql = "DELETE FROM usuarios WHERE id = ? AND taller_id = ?";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("ii", $usuario_id, $taller_id);
        $stmt2->execute();
        $ok = ($stmt2->affected_rows === 1);
        $stmt2->close();
        $conn->close();

        return $ok
            ? ['exito' => true,  'mensaje' => 'Usuario eliminado correctamente.']
            : ['exito' => false, 'mensaje' => 'No se encontró el usuario o no pertenece a este taller.'];
    }

    // ─────────────────────────────────────────────────────────────────
    // Resumen rápido de usuarios por rol para la cabecera
    // ─────────────────────────────────────────────────────────────────
    function obtenerResumenUsuarios($taller_id) {
        $conn = conectaBD();
        $sql  = "SELECT rol, COUNT(*) AS total
                 FROM usuarios
                 WHERE taller_id = ?
                 GROUP BY rol";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taller_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();

        // Convertir a array asociativo rol → total
        $resumen = [];
        foreach ($rows as $r) {
            $resumen[$r['rol']] = (int)$r['total'];
        }
        return $resumen;
    }
?>