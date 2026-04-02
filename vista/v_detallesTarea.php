<?php
// ─────────────────────────────────────────────
// Vista: Detalle y edición de una tarea
// Espera: $tarea (array), $tarea_id (int),
//         $orden_id (int), $error (string|null)
// ─────────────────────────────────────────────
 
// Formatear horas para input datetime-local (necesita "Y-m-dTH:i")
function formatearParaInput($datetime_str) {
    if (!$datetime_str) return '';
    return (new DateTime($datetime_str))->format('Y-m-d\TH:i');
}
 
$duracion_fmt = null;
if ($tarea['duracion_real_minutos']) {
    $h = intdiv($tarea['duracion_real_minutos'], 60);
    $m = $tarea['duracion_real_minutos'] % 60;
    $duracion_fmt = $h > 0 ? "{$h}h {$m}min" : "{$m}min";
}
?>
 
<div class="dt-container">
 
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php?action=misTareas">Mis Tareas</a>
        <span class="bc-sep">›</span>
        <a href="index.php?action=tareasOrden&orden_id=<?= $orden_id ?>">Orden #<?= $orden_id ?></a>
        <span class="bc-sep">›</span>
        <span><?= htmlspecialchars(mb_strimwidth($tarea['nombre_tarea'], 0, 30, '...')) ?></span>
    </nav>
 
    <?php if ($error): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
 
    <div class="dt-grid">
 
        <!-- ── Columna izquierda: info de contexto ── -->
        <div class="dt-columna-info">
 
            <div class="card">
                <h3 class="card-titulo">Vehículo</h3>
                <div class="dato-fila">
                    <span class="dato-label">Matrícula</span>
                    <strong><?= htmlspecialchars($tarea['matricula']) ?></strong>
                </div>
                <div class="dato-fila">
                    <span class="dato-label">Modelo</span>
                    <span><?= htmlspecialchars($tarea['marca'] . ' ' . $tarea['modelo'] . ' (' . $tarea['anio'] . ')') ?></span>
                </div>
                <div class="dato-fila">
                    <span class="dato-label">Cliente</span>
                    <span><?= htmlspecialchars($tarea['nombre_cliente']) ?></span>
                </div>
                <?php if ($tarea['telefono_cliente']): ?>
                    <div class="dato-fila">
                        <span class="dato-label">Teléfono</span>
                        <a href="tel:<?= htmlspecialchars($tarea['telefono_cliente']) ?>" class="link-tel">
                            <?= htmlspecialchars($tarea['telefono_cliente']) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
 
            <?php if ($tarea['sintomas_cliente']): ?>
                <div class="card card-sintomas">
                    <h3 class="card-titulo">Síntomas reportados</h3>
                    <p class="sintomas-texto"><?= nl2br(htmlspecialchars($tarea['sintomas_cliente'])) ?></p>
                </div>
            <?php endif; ?>
 
            <!-- Resumen de tiempos -->
            <div class="card">
                <h3 class="card-titulo">Tiempos</h3>
                <div class="dato-fila">
                    <span class="dato-label">Estimado</span>
                    <span>
                        <?= $tarea['minutos_estimados_base']
                            ? $tarea['minutos_estimados_base'] . ' min'
                            : '—' ?>
                    </span>
                </div>
                <div class="dato-fila">
                    <span class="dato-label">Real</span>
                    <span class="<?= $duracion_fmt ? 'duracion-real' : '' ?>">
                        <?= $duracion_fmt ?? '—' ?>
                    </span>
                </div>
                <?php if ($duracion_fmt && $tarea['minutos_estimados_base']): ?>
                    <?php
                        $diff_min = $tarea['duracion_real_minutos'] - $tarea['minutos_estimados_base'];
                        $clase_diff = $diff_min > 0 ? 'diff-positivo' : 'diff-negativo';
                        $signo = $diff_min > 0 ? '+' : '';
                    ?>
                    <div class="dato-fila">
                        <span class="dato-label">Desviación</span>
                        <span class="<?= $clase_diff ?>"><?= $signo . $diff_min ?> min</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
 
        <!-- ── Columna derecha: formulario de edición ── -->
        <div class="dt-columna-form">
 
            <div class="card">
                <h3 class="card-titulo">
                    <?= htmlspecialchars($tarea['nombre_tarea']) ?>
                </h3>
 
                <form method="POST"
                      action="index.php?action=detallesTarea&tarea_id=<?= $tarea_id ?>&orden_id=<?= $orden_id ?>">
 
                    <!-- Estado -->
                    <div class="campo">
                        <label class="campo-label" for="estado">Estado de la tarea</label>
                        <div class="estado-opciones">
 
                            <label class="estado-opcion <?= $tarea['estado'] === 'pendiente'  ? 'seleccionada' : '' ?>">
                                <input type="radio" name="estado" value="pendiente"
                                       <?= $tarea['estado'] === 'pendiente'  ? 'checked' : '' ?>>
                                <span class="estado-dot dot-pendiente"></span>
                                Pendiente
                            </label>
 
                            <label class="estado-opcion <?= $tarea['estado'] === 'en_proceso' ? 'seleccionada' : '' ?>">
                                <input type="radio" name="estado" value="en_proceso"
                                       <?= $tarea['estado'] === 'en_proceso' ? 'checked' : '' ?>>
                                <span class="estado-dot dot-en-proceso"></span>
                                En proceso
                            </label>
 
                            <label class="estado-opcion <?= $tarea['estado'] === 'finalizada' ? 'seleccionada' : '' ?>">
                                <input type="radio" name="estado" value="finalizada"
                                       <?= $tarea['estado'] === 'finalizada' ? 'checked' : '' ?>>
                                <span class="estado-dot dot-finalizada"></span>
                                Finalizada
                            </label>
 
                        </div>
                    </div>
 
                    <!-- Hora inicio -->
                    <div class="campo">
                        <label class="campo-label" for="hora_inicio">Hora de inicio</label>
                        <input type="datetime-local"
                               id="hora_inicio"
                               name="hora_inicio"
                               class="campo-input"
                               value="<?= htmlspecialchars(formatearParaInput($tarea['hora_inicio'])) ?>">
                        <span class="campo-ayuda">Cuándo empezaste a trabajar en esta tarea</span>
                    </div>
 
                    <!-- Hora fin -->
                    <div class="campo">
                        <label class="campo-label" for="hora_fin">Hora de finalización</label>
                        <input type="datetime-local"
                               id="hora_fin"
                               name="hora_fin"
                               class="campo-input"
                               value="<?= htmlspecialchars(formatearParaInput($tarea['hora_fin'])) ?>">
                        <span class="campo-ayuda">Se calculará la duración real automáticamente</span>
                    </div>
 
                    <!-- Botón rellenar ahora -->
                    <div class="acciones-rapidas">
                        <button type="button" class="btn-rapido" onclick="rellenarAhora('hora_inicio')">
                            ▷ Inicio = ahora
                        </button>
                        <button type="button" class="btn-rapido" onclick="rellenarAhora('hora_fin')">
                            ■ Fin = ahora
                        </button>
                    </div>
 
                    <div class="form-acciones">
                        <a href="index.php?action=tareasOrden&orden_id=<?= $orden_id ?>"
                           class="btn btn-cancelar">Cancelar</a>
                        <button type="submit" class="btn btn-guardar">Guardar cambios</button>
                    </div>
 
                </form>
            </div>
        </div>
 
    </div>
</div>