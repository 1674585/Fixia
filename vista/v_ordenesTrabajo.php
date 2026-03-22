<?php
// ─────────────────────────────────────────────
// Vista: Listado de órdenes de trabajo
// Espera: $ordenes (array), $filtro_estado (string)
// ─────────────────────────────────────────────
 
$estados = [
    'todos'         => 'Todos',
    'recibido'      => 'Recibido',
    'diagnosticando'=> 'Diagnosticando',
    'presupuestado' => 'Presupuestado',
    'en_reparacion' => 'En reparación',
    'listo'         => 'Listo',
    'facturado'     => 'Facturado',
];
 
$badge = [
    'recibido'       => 'badge-recibido',
    'diagnosticando' => 'badge-diagnosticando',
    'presupuestado'  => 'badge-presupuestado',
    'en_reparacion'  => 'badge-en-reparacion',
    'listo'          => 'badge-listo',
    'facturado'      => 'badge-facturado',
];
?>
 
<div class="ordenes-container">
    <div class="ordenes-header">
        <h2>Órdenes de Trabajo</h2>
 
        <?php if (in_array($_SESSION['rol'], ['ceo', 'jefe', 'recepcionista'])): ?>
            <a href="index.php?action=crearOrden" class="btn btn-primary">+ Nueva Orden</a>
        <?php endif; ?>
    </div>
 
    <!-- Filtros por estado -->
    <div class="filtros-estado">
        <?php foreach ($estados as $valor => $etiqueta): ?>
            <a href="index.php?action=ordenesTrabajo&estado=<?= $valor ?>"
               class="filtro-btn <?= $filtro_estado === $valor ? 'activo' : '' ?>">
                <?= $etiqueta ?>
            </a>
        <?php endforeach; ?>
    </div>
 
    <!-- Tabla de órdenes -->
    <?php if (empty($ordenes)): ?>
        <div class="sin-resultados">
            <p>No hay órdenes de trabajo <?= $filtro_estado !== 'todos' ? 'con estado "' . htmlspecialchars($estados[$filtro_estado]) . '"' : '' ?>.</p>
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
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordenes as $orden): ?>
                        <tr>
                            <td><?= htmlspecialchars($orden['id']) ?></td>
 
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
                                <?php else: ?>
                                    <span class="sin-asignar">Sin asignar</span>
                                <?php endif; ?>
                            </td>
 
                            <td>
                                <span class="badge <?= $badge[$orden['estado']] ?? '' ?>">
                                    <?= htmlspecialchars($estados[$orden['estado']] ?? $orden['estado']) ?>
                                </span>
                            </td>
 
                            <td>
                                <small><?= date('d/m/Y H:i', strtotime($orden['fecha_creacion'])) ?></small>
                            </td>
 
                            <td class="acciones">
                                <a href="index.php?action=detallesOrden&id=<?= $orden['id'] ?>"
                                   class="btn btn-sm btn-secundario" title="Ver detalles">
                                    Ver
                                </a>
 
                                <?php if (in_array($_SESSION['rol'], ['ceo', 'jefe']) && !$orden['asignado_a_id']): ?>
                                    <a href="index.php?action=asignarOrden&id=<?= $orden['id'] ?>"
                                       class="btn btn-sm btn-asignar" title="Asignar mecánico">
                                        Asignar
                                    </a>
                                <?php elseif (in_array($_SESSION['rol'], ['ceo', 'jefe']) && $orden['asignado_a_id']): ?>
                                    <a href="index.php?action=asignarOrden&id=<?= $orden['id'] ?>"
                                       class="btn btn-sm btn-reasignar" title="Reasignar mecánico">
                                        Reasignar
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>