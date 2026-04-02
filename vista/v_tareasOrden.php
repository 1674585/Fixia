<?php
// ─────────────────────────────────────────────
// Vista: Tareas de una orden asignadas al mecánico
// Espera: $tareas (array), $info_orden (array), $orden_id (int)
// ─────────────────────────────────────────────
 
$estados_tarea = [
    'pendiente'  => ['label' => 'Pendiente',   'clase' => 'tarea-pendiente'],
    'en_proceso' => ['label' => 'En proceso',  'clase' => 'tarea-en-proceso'],
    'finalizada' => ['label' => 'Finalizada',  'clase' => 'tarea-finalizada'],
];
?>
 
<div class="tareas-orden-container">
 
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php?action=misTareas">Mis Tareas</a>
        <span class="bc-sep">›</span>
        <span>Orden #<?= $orden_id ?></span>
    </nav>
 
    <!-- Cabecera con info del vehículo -->
    <div class="to-header">
        <div class="to-vehiculo-info">
            <h2 class="to-matricula"><?= htmlspecialchars($info_orden['matricula']) ?></h2>
            <span class="to-modelo"><?= htmlspecialchars($info_orden['marca'] . ' ' . $info_orden['modelo']) ?></span>
        </div>
        <div class="to-meta">
            <span class="to-cliente">👤 <?= htmlspecialchars($info_orden['nombre_cliente']) ?></span>
            <?php if ($info_orden['sintomas_cliente']): ?>
                <p class="to-sintomas">"<?= htmlspecialchars(mb_strimwidth($info_orden['sintomas_cliente'], 0, 120, '...')) ?>"</p>
            <?php endif; ?>
        </div>
    </div>
 
    <?php if (isset($_GET['guardada'])): ?>
        <div class="alerta alerta-exito">✓ Tarea actualizada correctamente.</div>
    <?php endif; ?>
 
    <?php if (isset($_GET['orden_lista'])): ?>
        <div class="alerta alerta-orden-lista">
            🎉 <strong>¡Orden completada!</strong> Todas las tareas están finalizadas. La orden ha pasado automáticamente a estado <em>Listo</em>.
        </div>
    <?php endif; ?>
 
    <?php if (!empty($error_orden)): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error_orden) ?></div>
    <?php endif; ?>
 
    <!-- Listado de tareas -->
    <div class="tareas-lista">
        <?php foreach ($tareas as $tarea):
            $est = $estados_tarea[$tarea['estado']] ?? ['label' => $tarea['estado'], 'clase' => ''];
            $duracion_fmt = null;
            if ($tarea['duracion_real_minutos']) {
                $h = intdiv($tarea['duracion_real_minutos'], 60);
                $m = $tarea['duracion_real_minutos'] % 60;
                $duracion_fmt = $h > 0 ? "{$h}h {$m}min" : "{$m}min";
            }
        ?>
            <a href="index.php?action=detallesTarea&tarea_id=<?= $tarea['id'] ?>&orden_id=<?= $orden_id ?>"
               class="tarea-card estado-card-<?= $tarea['estado'] ?>">
 
                <div class="tarea-card-left">
                    <div class="tarea-icono-estado <?= $est['clase'] ?>">
                        <?php if ($tarea['estado'] === 'finalizada'): ?>✓
                        <?php elseif ($tarea['estado'] === 'en_proceso'): ?>▶
                        <?php else: ?>○
                        <?php endif; ?>
                    </div>
                </div>
 
                <div class="tarea-card-body">
                    <div class="tarea-nombre"><?= htmlspecialchars($tarea['nombre_tarea']) ?></div>
 
                    <div class="tarea-detalles">
                        <?php if ($tarea['minutos_estimados_base']): ?>
                            <span class="tarea-detalle-item">
                                🕐 Estimado: <?= $tarea['minutos_estimados_base'] ?>min
                            </span>
                        <?php endif; ?>
 
                        <?php if ($tarea['hora_inicio']): ?>
                            <span class="tarea-detalle-item">
                                ▷ Inicio: <?= date('d/m H:i', strtotime($tarea['hora_inicio'])) ?>
                            </span>
                        <?php endif; ?>
 
                        <?php if ($tarea['hora_fin']): ?>
                            <span class="tarea-detalle-item">
                                ■ Fin: <?= date('d/m H:i', strtotime($tarea['hora_fin'])) ?>
                            </span>
                        <?php endif; ?>
 
                        <?php if ($duracion_fmt): ?>
                            <span class="tarea-detalle-item tarea-duracion">
                                ⏱ Real: <?= $duracion_fmt ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
 
                <div class="tarea-card-right">
                    <span class="badge-tarea-estado <?= $est['clase'] ?>">
                        <?= $est['label'] ?>
                    </span>
                    <span class="tarea-flecha">›</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
 
    <?php
        // Calcular si todas las tareas ya están finalizadas para mostrar/ocultar el botón
        $total_tareas      = count($tareas);
        $tareas_finalizadas = count(array_filter($tareas, fn($t) => $t['estado'] === 'finalizada'));
        $orden_ya_lista    = ($info_orden['estado_orden'] === 'listo');
        $todas_finalizadas = ($total_tareas > 0 && $total_tareas === $tareas_finalizadas);
    ?>
 
    <!-- Botón manual de marcar orden como lista -->
    <?php if (!$orden_ya_lista): ?>
        <div class="orden-acciones">
            <?php if ($todas_finalizadas): ?>
                <!-- Todas finalizadas pero la orden no está en listo aún (caso edge) -->
                <form method="POST" action="index.php?action=tareasOrden&orden_id=<?= $orden_id ?>">
                    <input type="hidden" name="marcar_lista" value="1">
                    <button type="submit" class="btn-marcar-lista">
                        ✓ Marcar orden como lista
                    </button>
                </form>
            <?php else: ?>
                <!-- Aún hay tareas sin finalizar: botón deshabilitado con contador -->
                <div class="btn-marcar-lista-disabled">
                    ✓ Marcar orden como lista
                    <span class="contador-pendientes">
                        (<?= $total_tareas - $tareas_finalizadas ?> tarea<?= ($total_tareas - $tareas_finalizadas) > 1 ? 's' : '' ?> sin finalizar)
                    </span>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="orden-completada-banner">
            ✓ Esta orden ya está marcada como <strong>Lista</strong>
        </div>
    <?php endif; ?>
 
</div>