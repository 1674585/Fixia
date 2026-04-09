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
            + Nuevo usuario
        </a>
    </div>

    <!-- Alertas -->
    <?php if ($mensaje_ok): ?>
        <div class="alerta alerta-exito">✓ <?= htmlspecialchars($mensaje_ok) ?></div>
    <?php endif; ?>
    <?php if ($mensaje_err): ?>
        <div class="alerta alerta-error">✗ <?= htmlspecialchars($mensaje_err) ?></div>
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
                   class="btn btn-limpiar">✕ Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabla -->
    <?php if (empty($usuarios)): ?>
        <div class="usr-vacio">
            <div class="usr-vacio-icono">👥</div>
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
                                   class="btn btn-sm btn-editar">✏ Editar</a>

                                <!-- Cambiar contraseña -->
                                <button type="button"
                                        class="btn btn-sm btn-password"
                                        onclick="abrirModalPassword(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre_completo'])) ?>')">
                                    🔑 Contraseña
                                </button>

                                <!-- Eliminar (no se puede eliminar a uno mismo) -->
                                <?php if (!$es_yo): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-eliminar"
                                            onclick="abrirModalEliminar(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre_completo'])) ?>', <?= (int)$u['tareas_activas'] ?>)">
                                        🗑 Eliminar
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
        <div class="modal-icono">🗑</div>
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
        <div class="modal-icono">🔑</div>
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
            alerta.textContent  = '⚠ Este usuario tiene ' + tareasActivas + ' tarea(s) activa(s). No se puede eliminar hasta reasignarlas.';
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

<style>
    .usr-container  { padding: 1.5rem; max-width: 1100px; margin: 0 auto; }

    /* Cabecera */
    .usr-header     { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.2rem; flex-wrap: wrap; gap: .8rem; }
    .usr-header h2  { margin: 0 0 .2rem; font-size: 1.5rem; color: #111827; }
    .usr-subtitulo  { margin: 0; font-size: .88rem; color: #9ca3af; }

    /* Resumen por rol */
    .usr-resumen-grid { display: flex; flex-wrap: wrap; gap: .7rem; margin-bottom: 1.3rem; }
    .usr-resumen-card {
        display: flex; flex-direction: column; align-items: center;
        background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px;
        padding: .6rem 1.1rem; text-decoration: none; color: inherit;
        transition: border-color .15s, background .15s; cursor: pointer;
    }
    .usr-resumen-card:hover, .usr-resumen-card.activo { border-color: #2563eb; background: #eff6ff; }
    .usr-resumen-num   { font-size: 1.3rem; font-weight: 700; color: #111827; }
    .usr-resumen-label { font-size: .75rem; color: #9ca3af; }
    .usr-resumen-todos { color: #2563eb; font-size: .85rem; padding: .6rem .9rem; border-style: dashed; }

    /* Filtros */
    .usr-filtros      { margin-bottom: 1.2rem; }
    .usr-filtros-fila { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; }
    .usr-input-busqueda {
        flex: 1; min-width: 200px;
        padding: .55rem .9rem; border: 1.5px solid #d1d5db;
        border-radius: 7px; font-size: .9rem;
    }
    .usr-input-busqueda:focus { outline: none; border-color: #2563eb; }

    /* Tabla */
    .tabla-wrapper { overflow-x: auto; }
    .tabla-usr { width: 100%; border-collapse: collapse; font-size: .9rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
    .tabla-usr th { background: #f8fafc; padding: .7rem 1rem; text-align: left; font-size: .78rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid #e5e7eb; }
    .tabla-usr td { padding: .7rem 1rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
    .tabla-usr tbody tr:last-child td { border-bottom: none; }
    .tabla-usr tbody tr:hover { background: #f9fafb; }
    .fila-yo { background: #f0f9ff !important; }

    .col-num      { text-align: right; }
    .col-acciones { text-align: center; white-space: nowrap; }
    .acciones     { display: flex; gap: .4rem; justify-content: center; flex-wrap: wrap; }

    .td-email     { color: #6b7280; font-size: .85rem; }
    .text-muted   { color: #d1d5db; }

    .badge-yo { background: #eff6ff; color: #2563eb; font-size: .7rem; padding: .15rem .45rem; border-radius: 8px; margin-left: .4rem; font-weight: 600; }

    /* Badges de rol */
    .badge-rol         { display: inline-block; padding: .22rem .65rem; border-radius: 10px; font-size: .75rem; font-weight: 600; }
    .rol-jefe          { background: #fef3c7; color: #92400e; }
    .rol-recepcionista { background: #ede9fe; color: #5b21b6; }
    .rol-mecanico      { background: #dbeafe; color: #1d4ed8; }
    .rol-cliente       { background: #dcfce7; color: #166534; }
    .rol-ceo           { background: #fee2e2; color: #991b1b; }

    .tareas-activas-badge { background: #fef3c7; color: #92400e; font-size: .78rem; font-weight: 600; padding: .15rem .5rem; border-radius: 8px; }

    /* Botones */
    .btn          { display: inline-block; padding: .45rem 1rem; border-radius: 6px; text-decoration: none; font-size: .88rem; cursor: pointer; border: none; font-weight: 500; }
    .btn-primary  { background: #2563eb; color: #fff; }
    .btn-primary:hover  { background: #1d4ed8; }
    .btn-filtrar  { background: #2563eb; color: #fff; }
    .btn-limpiar  { background: #f3f4f6; color: #6b7280; text-decoration: none; }
    .btn-editar   { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .btn-password { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
    .btn-eliminar { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }
    .btn-sm       { padding: .3rem .65rem; font-size: .8rem; }
    .btn-cancelar        { background: #e5e7eb; color: #374151; }
    .btn-eliminar-confirm { background: #dc2626; color: #fff; padding: .5rem 1.2rem; border-radius: 7px; border: none; font-size: .9rem; font-weight: 600; cursor: pointer; }
    .btn-eliminar-confirm:disabled { background: #fca5a5; cursor: not-allowed; }
    .btn-confirmar-pass  { background: #7c3aed; color: #fff; padding: .5rem 1.2rem; border-radius: 7px; border: none; font-size: .9rem; font-weight: 600; cursor: pointer; }

    /* Vacío */
    .usr-vacio       { text-align: center; padding: 4rem 2rem; color: #9ca3af; }
    .usr-vacio-icono { font-size: 3rem; margin-bottom: 1rem; }
    .total-resultados { font-size: .82rem; color: #9ca3af; margin-top: .6rem; text-align: right; }

    /* Alertas */
    .alerta         { padding: .8rem 1rem; border-radius: 7px; margin-bottom: 1rem; font-size: .9rem; }
    .alerta-exito   { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alerta-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .alerta-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .modal-caja    { background: #fff; border-radius: 14px; padding: 2rem 2.2rem; max-width: 420px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
    .modal-icono   { font-size: 2.5rem; text-align: center; margin-bottom: .6rem; }
    .modal-titulo  { font-size: 1.1rem; font-weight: 700; margin: 0 0 .6rem; color: #111827; text-align: center; }
    .modal-texto   { font-size: .9rem; color: #6b7280; text-align: center; line-height: 1.5; margin: 0 0 1rem; }
    .modal-acciones { display: flex; gap: .7rem; justify-content: center; }

    /* Campo formulario en modal */
    .campo-label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
    .campo-input { width: 100%; padding: .55rem .8rem; border: 1.5px solid #d1d5db; border-radius: 7px; font-size: .9rem; box-sizing: border-box; }
    .campo-input:focus { outline: none; border-color: #7c3aed; }
</style>