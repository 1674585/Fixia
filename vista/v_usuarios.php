<?php
// ─────────────────────────────────────────────
// Vista: Gestión de usuarios del taller
// Espera: $usuarios, $resumen, $filtros,
//         $mensaje_ok, $mensaje_err, $jefe_id
// ─────────────────────────────────────────────

$roles_config = [
    'jefe'         => ['label' => 'Jefe',          'clase' => 'rol-jefe'],
    'recepcionista'=> ['label' => 'Recepcionista',  'clase' => 'rol-recepcionista'],
    'mecanico'     => ['label' => 'Mecánico',       'clase' => 'rol-mecanico'],
    'cliente'      => ['label' => 'Cliente',        'clase' => 'rol-cliente'],
    'ceo'          => ['label' => 'CEO',            'clase' => 'rol-ceo'],
];
?>

<div class="usr-container">

    <!-- Cabecera -->
    <div class="usr-header">
        <div>
            <h2>Gestión de Usuarios</h2>
            <p class="usr-subtitulo">Usuarios del taller · <?= array_sum($resumen) ?> en total</p>
        </div>
        <a href="index.php?action=usuariosFormulario" class="btn btn-primary">
            Nuevo usuario
        </a>
    </div>

    <!-- Alertas -->
    <?php if ($mensaje_ok): ?>
        <div class="alerta alerta-exito"><?= htmlspecialchars($mensaje_ok) ?></div>
    <?php endif; ?>
    <?php if ($mensaje_err): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($mensaje_err) ?></div>
    <?php endif; ?>

    <!-- Resumen por rol -->
    <div class="usr-resumen-grid">
        <?php foreach ($roles_config as $rol => $cfg): ?>
            <?php if (isset($resumen[$rol]) && $resumen[$rol] > 0): ?>
                <a href="index.php?action=usuarios&rol=<?= $rol ?>"
                   class="usr-resumen-card <?= $filtros['rol'] === $rol ? 'activo' : '' ?>">
                    <span class="usr-resumen-num"><?= $resumen[$rol] ?></span>
                    <span class="usr-resumen-label"><?= $cfg['label'] ?><?= $resumen[$rol] !== 1 ? 's' : '' ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!empty($filtros['rol'])): ?>
            <a href="index.php?action=usuarios" class="usr-resumen-card usr-resumen-todos">
                Ver todos
            </a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <form method="GET" action="index.php" class="usr-filtros">
        <input type="hidden" name="action" value="usuarios">
        <input type="hidden" name="rol"    value="<?= htmlspecialchars($filtros['rol']) ?>">
        <div class="usr-filtros-fila">
            <input type="text"
                   name="busqueda"
                   placeholder="Buscar por nombre o email…"
                   value="<?= htmlspecialchars($filtros['busqueda']) ?>"
                   class="usr-input-busqueda">
            <button type="submit" class="btn btn-filtrar">Buscar</button>
            <?php if ($filtros['busqueda']): ?>
                <a href="index.php?action=usuarios&rol=<?= htmlspecialchars($filtros['rol']) ?>"
                   class="btn btn-limpiar">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabla -->
    <?php if (empty($usuarios)): ?>
        <div class="usr-vacio">
            <p>No se encontraron usuarios<?= $filtros['busqueda'] ? ' para "' . htmlspecialchars($filtros['busqueda']) . '"' : '' ?>.</p>
        </div>
    <?php else: ?>
        <div class="tabla-wrapper">
            <table class="tabla-usr">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th class="col-num">Tareas activas</th>
                        <th class="col-num">Órdenes creadas</th>
                        <th class="col-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u):
                        $rc = $roles_config[$u['rol']] ?? ['label' => $u['rol'], 'clase' => ''];
                        $es_yo = ((int)$u['id'] === $jefe_id);
                    ?>
                        <tr class="<?= $es_yo ? 'fila-yo' : '' ?>">
                            <td>
                                <strong><?= htmlspecialchars($u['nombre_completo']) ?></strong>
                                <?php if ($es_yo): ?>
                                    <span class="badge-yo">Tú</span>
                                <?php endif; ?>
                            </td>
                            <td class="td-email"><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= $u['telefono'] ? htmlspecialchars($u['telefono']) : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <span class="badge-rol <?= $rc['clase'] ?>"><?= $rc['label'] ?></span>
                            </td>
                            <td class="col-num">
                                <?php if ((int)$u['tareas_activas'] > 0): ?>
                                    <span class="tareas-activas-badge"><?= (int)$u['tareas_activas'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-num"><?= (int)$u['ordenes_creadas'] ?></td>
                            <td class="col-acciones acciones">
                                <!-- Editar -->
                                <a href="index.php?action=usuariosFormulario&id=<?= $u['id'] ?>"
                                   class="btn btn-sm btn-editar">Editar</a>

                                <!-- Cambiar contraseña -->
                                <button type="button"
                                        class="btn btn-sm btn-password"
                                        onclick="abrirModalPassword(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre_completo'])) ?>')">
                                    Contraseña
                                </button>

                                <!-- Eliminar (no se puede eliminar a uno mismo) -->
                                <?php if (!$es_yo): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-eliminar"
                                            onclick="abrirModalEliminar(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre_completo'])) ?>', <?= (int)$u['tareas_activas'] ?>)">
                                        Eliminar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="total-resultados"><?= count($usuarios) ?> usuario<?= count($usuarios) !== 1 ? 's' : '' ?></p>
    <?php endif; ?>
</div>


<!-- ══ Modal: Eliminar usuario ══════════════════════════════════ -->
<div id="modalEliminar" class="modal-overlay" style="display:none">
    <div class="modal-caja">
        <h3 class="modal-titulo">Eliminar usuario</h3>
        <p class="modal-texto">
            ¿Eliminar a <strong id="modalElimNombre"></strong>?
            <br><small>Esta acción no se puede deshacer.</small>
        </p>
        <div id="modalElimAlerta" class="alerta alerta-warning" style="display:none"></div>
        <form method="POST" action="index.php?action=usuarios" id="formEliminar">
            <input type="hidden" name="eliminar_id" id="eliminarId">
            <div class="modal-acciones">
                <button type="button" onclick="cerrarModales()" class="btn btn-cancelar">Cancelar</button>
                <button type="submit" id="btnEliminarConfirmar" class="btn btn-eliminar-confirm">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ Modal: Cambiar contraseña ════════════════════════════════ -->
<div id="modalPassword" class="modal-overlay" style="display:none">
    <div class="modal-caja">
        <h3 class="modal-titulo">Cambiar contraseña</h3>
        <p class="modal-texto">
            Usuario: <strong id="modalPassNombre"></strong>
        </p>
        <form method="POST" action="index.php?action=cambiarPassword" id="formPassword">
            <input type="hidden" name="usuario_id" id="passUsuarioId">

            <div class="campo">
                <label class="campo-label" for="nueva_password">Nueva contraseña</label>
                <input type="password" id="nueva_password" name="nueva_password"
                       class="campo-input" minlength="8" required
                       placeholder="Mínimo 8 caracteres">
            </div>
            <div class="campo" style="margin-top:.7rem">
                <label class="campo-label" for="confirmar_password">Confirmar contraseña</label>
                <input type="password" id="confirmar_password" name="confirmar_password"
                       class="campo-input" minlength="8" required
                       placeholder="Repite la contraseña">
            </div>
            <div id="passError" class="alerta alerta-error" style="display:none;margin-top:.7rem"></div>

            <div class="modal-acciones" style="margin-top:1.2rem">
                <button type="button" onclick="cerrarModales()" class="btn btn-cancelar">Cancelar</button>
                <button type="submit" class="btn btn-confirmar-pass" onclick="return validarPassword()">
                    Guardar contraseña
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalEliminar(id, nombre, tareasActivas) {
        document.getElementById('eliminarId').value        = id;
        document.getElementById('modalElimNombre').textContent = nombre;
        const alerta = document.getElementById('modalElimAlerta');
        const btn    = document.getElementById('btnEliminarConfirmar');

        if (tareasActivas > 0) {
            alerta.textContent  = 'Este usuario tiene ' + tareasActivas + ' tarea(s) activa(s). No se puede eliminar hasta reasignarlas.';
            alerta.style.display = 'block';
            btn.disabled = true;
        } else {
            alerta.style.display = 'none';
            btn.disabled = false;
        }
        document.getElementById('modalEliminar').style.display = 'flex';
    }

    function abrirModalPassword(id, nombre) {
        document.getElementById('passUsuarioId').value       = id;
        document.getElementById('modalPassNombre').textContent = nombre;
        document.getElementById('nueva_password').value      = '';
        document.getElementById('confirmar_password').value  = '';
        document.getElementById('passError').style.display   = 'none';
        document.getElementById('modalPassword').style.display = 'flex';
    }

    function cerrarModales() {
        document.getElementById('modalEliminar').style.display  = 'none';
        document.getElementById('modalPassword').style.display  = 'none';
    }

    function validarPassword() {
        const p1  = document.getElementById('nueva_password').value;
        const p2  = document.getElementById('confirmar_password').value;
        const err = document.getElementById('passError');
        if (p1 !== p2) {
            err.textContent    = 'Las contraseñas no coinciden.';
            err.style.display  = 'block';
            return false;
        }
        if (p1.length < 8) {
            err.textContent    = 'La contraseña debe tener al menos 8 caracteres.';
            err.style.display  = 'block';
            return false;
        }
        return true;
    }

    // Cerrar modales al hacer clic fuera
    ['modalEliminar','modalPassword'].forEach(id => {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) cerrarModales();
        });
    });
</script>
