<?php
// ─────────────────────────────────────────────
// Vista: Listado de órdenes de trabajo
// Espera: $ordenes, $filtros, $orden_col, $orden_dir,
//         $usuarios_asign, $mensaje_ok, $mensaje_err
// ─────────────────────────────────────────────

$estados = [
    'todos'          => 'Todos',
    'recibido'       => 'Recibido',
    'diagnosticando' => 'Diagnosticando',
    'presupuestado'  => 'Presupuestado',
    'en_reparacion'  => 'En reparación',
    'listo'          => 'Listo',
    'facturado'      => 'Facturado',
];

$badge = [
    'recibido'       => 'badge-recibido',
    'diagnosticando' => 'badge-diagnosticando',
    'presupuestado'  => 'badge-presupuestado',
    'en_reparacion'  => 'badge-en-reparacion',
    'listo'          => 'badge-listo',
    'facturado'      => 'badge-facturado',
];

$columnas_ord = [
    'fecha'    => 'Fecha de creación',
    'id'       => 'Nº de orden',
    'estado'   => 'Estado',
    'mecanico' => 'Mecánico asignado',
    'cliente'  => 'Cliente',
    'precio'   => 'Precio estimado',
];

$rol_session = $_SESSION['rol'] ?? '';
$user_id     = (int)($_SESSION['user_id'] ?? 0);
$puede_asignar      = in_array($rol_session, ['ceo', 'jefe']);
$puede_autoasignar  = in_array($rol_session, ['ceo', 'jefe']);
?>

<div class="ordenes-container">
    <div class="ordenes-header">
        <h2>Órdenes de Trabajo</h2>

        <?php if (in_array($rol_session, ['ceo', 'jefe', 'recepcionista'])): ?>
            <a href="index.php?action=crearOrden" class="btn btn-primary">Nueva Orden</a>
        <?php endif; ?>
    </div>

    <?php if ($mensaje_ok): ?>
        <div class="alerta alerta-exito"><?= htmlspecialchars($mensaje_ok) ?></div>
    <?php endif; ?>
    <?php if ($mensaje_err): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($mensaje_err) ?></div>
    <?php endif; ?>

    <!-- Filtros rápidos por estado (mantengo el patrón existente) -->
    <div class="filtros-estado">
        <?php foreach ($estados as $valor => $etiqueta): ?>
            <?php
                $qs = $_GET; $qs['estado'] = $valor; unset($qs['asignada']);
                $qs['action'] = 'ordenesTrabajo';
                $href = 'index.php?' . http_build_query($qs);
            ?>
            <a href="<?= $href ?>"
               class="filtro-btn <?= $filtros['estado'] === $valor ? 'activo' : '' ?>">
                <?= $etiqueta ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Formulario de filtros avanzados + ordenación -->
    <form method="GET" action="index.php" class="ordenes-filtros">
        <input type="hidden" name="action" value="ordenesTrabajo">
        <input type="hidden" name="estado" value="<?= htmlspecialchars($filtros['estado']) ?>">

        <div class="filtro-grupo">
            <label>Buscar</label>
            <input type="text" name="buscar"
                   value="<?= htmlspecialchars($filtros['buscar']) ?>"
                   placeholder="Matrícula o cliente">
        </div>

        <div class="filtro-grupo">
            <label>Desde</label>
            <input type="date" name="fecha_desde"
                   value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
        </div>

        <div class="filtro-grupo">
            <label>Hasta</label>
            <input type="date" name="fecha_hasta"
                   value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
        </div>

        <div class="filtro-grupo">
            <label>Asignación</label>
            <select name="asignacion">
                <option value="todos"       <?= $filtros['asignacion'] === 'todos'       ? 'selected' : '' ?>>Todas</option>
                <option value="sin_asignar" <?= $filtros['asignacion'] === 'sin_asignar' ? 'selected' : '' ?>>Sin asignar</option>
                <option value="asignadas"   <?= $filtros['asignacion'] === 'asignadas'   ? 'selected' : '' ?>>Asignadas</option>
                <?php foreach ($usuarios_asign as $u): ?>
                    <option value="<?= (int)$u['id'] ?>"
                            <?= (string)$filtros['asignacion'] === (string)$u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nombre_completo']) ?>
                        <?= $u['rol'] === 'jefe' ? ' (jefe)' : '' ?>
                    </option>
                <?php endforeach; ?>
                <?php if ($puede_autoasignar): ?>
                    <option value="<?= $user_id ?>"
                            <?= (int)$filtros['asignacion'] === $user_id ? 'selected' : '' ?>>
                        — Mis órdenes —
                    </option>
                <?php endif; ?>
            </select>
        </div>

        <div class="filtro-grupo">
            <label>Ordenar por</label>
            <select name="orden">
                <?php foreach ($columnas_ord as $clave => $etiqueta): ?>
                    <option value="<?= $clave ?>" <?= $orden_col === $clave ? 'selected' : '' ?>>
                        <?= $etiqueta ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro-grupo">
            <label>Dirección</label>
            <select name="dir">
                <option value="desc" <?= $orden_dir === 'desc' ? 'selected' : '' ?>>Descendente</option>
                <option value="asc"  <?= $orden_dir === 'asc'  ? 'selected' : '' ?>>Ascendente</option>
            </select>
        </div>

        <div class="filtro-grupo filtro-acciones">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="index.php?action=ordenesTrabajo" class="btn btn-cancelar">Limpiar</a>
        </div>
    </form>

    <!-- Tabla de órdenes -->
    <?php if (empty($ordenes)): ?>
        <div class="sin-resultados">
            <p>No hay órdenes que coincidan con los filtros aplicados.</p>
        </div>
    <?php else: ?>
        <div class="tabla-wrapper">
            <table class="tabla-ordenes">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Vehículo</th>
                        <th>Cliente</th>
                        <th>Síntomas</th>
                        <th>Mecánico</th>
                        <th>Estado</th>
                        <th class="col-num">Estimado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordenes as $orden): ?>
                        <tr>
                            <td><?= (int)$orden['id'] ?></td>

                            <td>
                                <strong><?= htmlspecialchars($orden['matricula']) ?></strong><br>
                                <small><?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) ?></small>
                            </td>

                            <td><?= htmlspecialchars($orden['nombre_cliente']) ?></td>

                            <td class="sintomas-cell">
                                <?= htmlspecialchars(mb_strimwidth($orden['sintomas_cliente'] ?? '—', 0, 60, '...')) ?>
                            </td>

                            <td>
                                <?php if ($orden['nombre_mecanico']): ?>
                                    <?= htmlspecialchars($orden['nombre_mecanico']) ?>
                                    <?php if (($orden['rol_mecanico'] ?? '') === 'jefe'): ?>
                                        <small>(jefe)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="sin-asignar">Sin asignar</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge <?= $badge[$orden['estado']] ?? '' ?>">
                                    <?= htmlspecialchars($estados[$orden['estado']] ?? $orden['estado']) ?>
                                </span>
                            </td>

                            <td class="col-num">
                                <?php if ($orden['precio_estimado_ia'] !== null): ?>
                                    <?= number_format((float)$orden['precio_estimado_ia'], 2, ',', '.') ?> €
                                <?php else: ?>
                                    <small>—</small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <small><?= date('d/m/Y H:i', strtotime($orden['fecha_creacion'])) ?></small>
                            </td>

                            <td class="acciones">
                                

                                <?php if ($puede_asignar): ?>
                                    <a href="index.php?action=tareasOrden&orden_id=<?= (int)$orden['id'] ?>"
                                       class="btn btn-sm btn-secundario" title="Ver tareas (supervisor)">
                                        Tareas
                                    </a>
                                <?php endif; ?>

                                <?php if ($puede_asignar && !$orden['asignado_a_id']): ?>
                                    <a href="index.php?action=asignarOrden&id=<?= (int)$orden['id'] ?>"
                                       class="btn btn-sm btn-asignar" title="Asignar a un mecánico o a mí mismo">
                                        Asignar
                                    </a>
                                <?php elseif ($puede_asignar && $orden['asignado_a_id']): ?>
                                    <a href="index.php?action=asignarOrden&id=<?= (int)$orden['id'] ?>"
                                       class="btn btn-sm btn-reasignar" title="Reasignar">
                                        Reasignar
                                    </a>
                                <?php endif; ?>

                                <?php if ($puede_autoasignar && (int)$orden['asignado_a_id'] !== $user_id): ?>
                                    <form method="POST"
                                          action="index.php?action=ordenesTrabajo&<?= http_build_query(array_diff_key($_GET, ['action'=>1,'asignada'=>1])) ?>"
                                          style="display:inline"
                                          onsubmit="return confirm('¿Asignarte esta orden #<?= (int)$orden['id'] ?>?');">
                                        <input type="hidden" name="accion"   value="autoasignar">
                                        <input type="hidden" name="orden_id" value="<?= (int)$orden['id'] ?>">
                                        
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
