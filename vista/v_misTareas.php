<?php
// ─────────────────────────────────────────────
// Vista: Mis Tareas — listado de órdenes
// Espera: $ordenes (array)
// ─────────────────────────────────────────────
 
$estados_orden = [
    'recibido'       => ['label' => 'Recibido',       'clase' => 'estado-recibido'],
    'diagnosticando' => ['label' => 'Diagnosticando', 'clase' => 'estado-diagnosticando'],
    'presupuestado'  => ['label' => 'Presupuestado',  'clase' => 'estado-presupuestado'],
    'en_reparacion'  => ['label' => 'En reparación',  'clase' => 'estado-en-reparacion'],
    'listo'          => ['label' => 'Listo',           'clase' => 'estado-listo'],
    'facturado'      => ['label' => 'Facturado',       'clase' => 'estado-facturado'],
];
?>
 
<div class="mis-tareas-container">
 
    <div class="mt-header">
        <div>
            <h2>Mis Tareas</h2>
            <p class="mt-subtitulo">Órdenes de trabajo que tienes asignadas</p>
        </div>
        <?php if (!empty($ordenes)): ?>
            <div class="mt-resumen-global">
                <span class="resumen-num"><?= count($ordenes) ?></span>
                <span class="resumen-label">órdenes activas</span>
            </div>
        <?php endif; ?>
    </div>
 
    <?php if (isset($_GET['guardada'])): ?>
        <div class="alerta alerta-exito">✓ Tarea actualizada correctamente.</div>
    <?php endif; ?>
 
    <?php if (empty($ordenes)): ?>
        <div class="mt-vacio">
            <div class="mt-vacio-icono">🔧</div>
            <p>No tienes órdenes de trabajo asignadas en este momento.</p>
        </div>
    <?php else: ?>
        <div class="ordenes-grid">
            <?php foreach ($ordenes as $orden):
                $progreso  = $orden['total_tareas'] > 0
                    ? round(($orden['tareas_finalizadas'] / $orden['total_tareas']) * 100)
                    : 0;
                $estado    = $estados_orden[$orden['estado']] ?? ['label' => $orden['estado'], 'clase' => ''];
                $pendientes = $orden['total_tareas'] - $orden['tareas_finalizadas'];
            ?>
                <a href="index.php?action=tareasOrden&orden_id=<?= $orden['id'] ?>"
                   class="orden-card">
 
                    <div class="orden-card-top">
                        <div class="orden-vehiculo">
                            <span class="orden-matricula"><?= htmlspecialchars($orden['matricula']) ?></span>
                            <span class="orden-modelo"><?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) ?></span>
                        </div>
                        <span class="badge-estado <?= $estado['clase'] ?>">
                            <?= $estado['label'] ?>
                        </span>
                    </div>
 
                    <div class="orden-cliente">
                        <span class="icono-cliente">👤</span>
                        <?= htmlspecialchars($orden['nombre_cliente']) ?>
                    </div>
 
                    <?php if ($orden['sintomas_cliente']): ?>
                        <p class="orden-sintomas">
                            <?= htmlspecialchars(mb_strimwidth($orden['sintomas_cliente'], 0, 80, '...')) ?>
                        </p>
                    <?php endif; ?>
 
                    <!-- Barra de progreso -->
                    <div class="progreso-wrapper">
                        <div class="progreso-barra">
                            <div class="progreso-relleno" style="width: <?= $progreso ?>%"></div>
                        </div>
                        <div class="progreso-info">
                            <span><?= $orden['tareas_finalizadas'] ?>/<?= $orden['total_tareas'] ?> tareas</span>
                            <span><?= $progreso ?>%</span>
                        </div>
                    </div>
 
                    <div class="orden-footer">
                        <span class="orden-fecha">
                            <?= date('d/m/Y', strtotime($orden['fecha_creacion'])) ?>
                        </span>
                        <?php if ($pendientes > 0): ?>
                            <span class="badge-pendientes"><?= $pendientes ?> pendiente<?= $pendientes > 1 ? 's' : '' ?></span>
                        <?php else: ?>
                            <span class="badge-completada">✓ Completada</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>