<?php
// ─────────────────────────────────────────────
// Vista: Módulo IA — gestión de CSVs y modelos
// ─────────────────────────────────────────────
?>
<div class="ia-container">

    <div class="ia-header">
        <h2>IA — Datos de entrenamiento</h2>
        <p class="ia-subtitulo">
            Regenera los CSVs con todas las tareas finalizadas de órdenes facturadas.
            Los archivos se guardan en <code>csv/</code> y son la base para entrenar los modelos de predicción de coste y tiempo.
        </p>
    </div>

    <!-- Acciones -->
    <div class="ia-acciones">
        <button type="button" id="btn-regenerar-csvs" class="btn-primario">
            Regenerar CSVs
        </button>
        <button type="button" id="btn-entrenar-modelos" class="btn-primario">
            Entrenar modelos
        </button>
        <span id="ia-estado-accion" class="ia-estado-accion"></span>
    </div>

    <!-- Resumen: talleres con datos -->
    <h3>Talleres con tareas facturadas</h3>
    <?php if (empty($talleres_disponibles)): ?>
        <p><em>Todavía no hay talleres con tareas finalizadas y facturadas.</em></p>
    <?php else: ?>
        <table class="ia-tabla">
            <thead>
                <tr>
                    <th>ID Taller</th>
                    <th>Nombre</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($talleres_disponibles as $t): ?>
                    <tr>
                        <td><?= (int)$t['id'] ?></td>
                        <td><?= htmlspecialchars($t['nombre']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Estado actual de los modelos -->
    <h3>Modelos entrenados en <code>modelos_ml/</code></h3>
    <?php if (empty($estado_modelos)): ?>
        <p><em>No hay modelos todavía. Pulsa "Entrenar modelos" después de regenerar los CSVs.</em></p>
    <?php else: ?>
        <table class="ia-tabla">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Tamaño</th>
                    <th>Última modificación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estado_modelos as $m): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($m['archivo']) ?></code></td>
                        <td><?= number_format($m['tamanyo_bytes'] / 1024, 1, ',', '.') ?> KB</td>
                        <td><?= htmlspecialchars($m['ultima_modificado']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Estado actual de los CSVs -->
    <h3>Archivos CSV actuales en <code>csv/</code></h3>
    <?php if (empty($estado_csvs)): ?>
        <p><em>No hay archivos CSV todavía. Pulsa "Regenerar CSVs" para crearlos.</em></p>
    <?php else: ?>
        <table class="ia-tabla" id="tabla-estado-csvs">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Filas</th>
                    <th>Tamaño</th>
                    <th>Última modificación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estado_csvs as $a): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($a['archivo']) ?></code></td>
                        <td><?= number_format($a['filas'], 0, ',', '.') ?></td>
                        <td><?= number_format($a['tamanyo_bytes'] / 1024, 1, ',', '.') ?> KB</td>
                        <td><?= htmlspecialchars($a['ultima_modificado']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Log de salida del entrenamiento -->
    <pre id="ia-log" class="ia-log" hidden></pre>

</div>

<script>
document.getElementById('btn-regenerar-csvs').addEventListener('click', () => {
    const btn    = document.getElementById('btn-regenerar-csvs');
    const estado = document.getElementById('ia-estado-accion');

    btn.disabled    = true;
    estado.textContent = 'Regenerando…';
    estado.className   = 'ia-estado-accion ia-estado-trabajando';

    const fd = new FormData();
    fd.append('accion', 'regenerar_csvs');

    fetch('index.php?action=ia', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                estado.textContent = 'Error: ' + (data.error || 'desconocido');
                estado.className   = 'ia-estado-accion ia-estado-error';
                return;
            }
            const totalTalleres = data.talleres.length;
            const filasGeneral  = data.general.filas;
            estado.textContent = `OK — general.csv: ${filasGeneral} filas · ${totalTalleres} taller(es) regenerado(s). Recargando…`;
            estado.className   = 'ia-estado-accion ia-estado-ok';
            setTimeout(() => location.reload(), 1200);
        })
        .catch(err => {
            estado.textContent = 'Error de red: ' + err.message;
            estado.className   = 'ia-estado-accion ia-estado-error';
        })
        .finally(() => { btn.disabled = false; });
});

document.getElementById('btn-entrenar-modelos').addEventListener('click', () => {
    const btn    = document.getElementById('btn-entrenar-modelos');
    const estado = document.getElementById('ia-estado-accion');
    const log    = document.getElementById('ia-log');

    btn.disabled = true;
    estado.textContent = 'Entrenando modelos (puede tardar)…';
    estado.className   = 'ia-estado-accion ia-estado-trabajando';
    log.hidden = true;
    log.textContent = '';

    const fd = new FormData();
    fd.append('accion', 'entrenar_modelos');

    fetch('index.php?action=ia', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            const salida = (data.stdout || '') + (data.stderr ? '\n--- stderr ---\n' + data.stderr : '');
            if (salida) {
                log.textContent = salida;
                log.hidden = false;
            }
            if (!data.ok) {
                estado.textContent = 'Error entrenando (code ' + data.code + ')' + (data.error ? ': ' + data.error : '');
                estado.className   = 'ia-estado-accion ia-estado-error';
                return;
            }
            estado.textContent = 'Modelos entrenados. Recargando…';
            estado.className   = 'ia-estado-accion ia-estado-ok';
            setTimeout(() => location.reload(), 1500);
        })
        .catch(err => {
            estado.textContent = 'Error de red: ' + err.message;
            estado.className   = 'ia-estado-accion ia-estado-error';
        })
        .finally(() => { btn.disabled = false; });
});
</script>
