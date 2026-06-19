<?php
// Asumiendo que $tipos viene del controlador
// $vehiculos también
?>
<h2>Crear Orden de Trabajo</h2>
<form method="POST" action="index.php?action=crearOrden">
    <div class="grupo-form">
        <label for="buscar_vehiculo">Buscar Vehículo (matrícula, marca, modelo o cliente) *</label>
        <input type="text" id="buscar_vehiculo"
               placeholder="Escribe al menos 2 caracteres..."
               autocomplete="off">
        <input type="hidden" id="vehiculo_id" name="vehiculo_id" value="" required>
        <div id="sugerencias-vehiculos" class="sugerencias-lista" style="display:none"></div>
        <div id="vehiculo-seleccionado" class="vehiculo-seleccionado" style="display:none"></div>
        <small>La selección debe hacerse desde la lista de sugerencias.</small>
    </div>
    <br>

    <label for="sintomas_cliente">Síntomas del Cliente:</label>
    <textarea name="sintomas_cliente" required></textarea><br>

    <label for="estado">Estado de la Orden:</label>
    <select name="estado" required>
        <option value="recibido">Recibido</option>
        <option value="diagnosticando">Diagnosticando</option>
        <option value="presupuestado">Presupuestado</option>
        <option value="en_reparacion">En reparación</option>
        <option value="listo">Listo</option>
        <option value="facturado">Facturado</option>
    </select><br>

    <div id="tareas-container">
        <div class="tarea">
            <div class="campo-tarea">
                <label>Tipo de Reparación:</label>
                <select name="tareas[0][tipo]" class="tipo">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?php echo $tipo['id']; ?>"><?php echo $tipo['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo-tarea">
                <label>Subgrupo:</label>
                <select name="tareas[0][subgrupo]" class="subgrupo" disabled>
                    <option value="">-- Seleccionar Tipo Primero --</option>
                </select>
            </div>

            <button type="button" class="btn-predecir">Predecir</button>
            <span class="prediccion-resultado"></span>
            <input type="hidden" name="tareas[0][precio_estimado]" class="precio-estimado" value="">
            <input type="hidden" name="tareas[0][tiempo_estimado]" class="tiempo-estimado" value="">
        </div>
        <button type="button" id="add-tarea">Añadir tarea</button>
    </div>

    

    <button type="submit">Crear Orden</button>
</form>

<script>
let index = 1;

document.getElementById('add-tarea').addEventListener('click', () => {
    const container = document.getElementById('tareas-container');

    const div = document.createElement('div');
    div.classList.add('tarea');

    div.innerHTML = `
        <hr>
        <div class="campo-tarea">
            <label>Tipo de Reparación:</label>
            <select name="tareas[${index}][tipo]" class="tipo">
                <option value="">-- Seleccionar --</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?php echo $tipo['id']; ?>"><?php echo $tipo['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo-tarea">
            <label>Subgrupo:</label>
            <select name="tareas[${index}][subgrupo]" class="subgrupo" disabled>
                <option value="">-- Seleccionar Tipo Primero --</option>
            </select>
        </div>

        <button type="button" class="btn-predecir">Predecir</button>
        <span class="prediccion-resultado"></span>
        <input type="hidden" name="tareas[${index}][precio_estimado]" class="precio-estimado" value="">
        <input type="hidden" name="tareas[${index}][tiempo_estimado]" class="tiempo-estimado" value="">
    `;

    container.appendChild(div);
    index++;
});

// Delegación de eventos (clave)
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('tipo')) {
        const tipoId = e.target.value;
        const subSelect = e.target.closest('.tarea').querySelector('.subgrupo');

        if (!tipoId) {
            subSelect.innerHTML = '<option>-- Seleccionar Tipo Primero --</option>';
            subSelect.disabled = true;
            return;
        }

        fetch(`index.php?action=obtenerSubgrupos&tipo_id=${tipoId}`)
            .then(res => res.json())
            .then(data => {
                subSelect.innerHTML = '<option value="">-- Seleccionar Subgrupo --</option>';
                data.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.nombre;
                    subSelect.appendChild(option);
                });
                subSelect.disabled = false;
            });
    }
});

// Predicción de coste y tiempo (delegación, vale también para tareas añadidas dinámicamente)
document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('btn-predecir')) return;

    const btn       = e.target;
    const tareaDiv  = btn.closest('.tarea');
    const resultado = tareaDiv.querySelector('.prediccion-resultado');
    const subgrupo  = tareaDiv.querySelector('.subgrupo');
    const vehiculo  = document.getElementById('vehiculo_id');

    const vehiculoId = vehiculo ? vehiculo.value : '';
    const subgrupoId = subgrupo ? subgrupo.value : '';

    if (!vehiculoId) {
        resultado.textContent = 'Selecciona un vehículo primero';
        resultado.className = 'prediccion-resultado prediccion-error';
        return;
    }
    if (!subgrupoId) {
        resultado.textContent = 'Selecciona un subgrupo';
        resultado.className = 'prediccion-resultado prediccion-error';
        return;
    }

    btn.disabled = true;
    resultado.textContent = 'Calculando…';
    resultado.className = 'prediccion-resultado prediccion-cargando';

    const fd = new FormData();
    fd.append('vehiculo_id', vehiculoId);
    fd.append('subgrupo_id', subgrupoId);

    fetch('index.php?action=predecirTarea', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                resultado.textContent = 'Error: ' + (data.error || 'Error en la predicción');
                resultado.className = 'prediccion-resultado prediccion-error';
                return;
            }
            const coste = data.coste.toFixed(2).replace('.', ',');
            const horas = data.horas.toFixed(2).replace('.', ',');
            resultado.textContent = `Estimado: ${coste} € — ${horas} h (${data.modelo_usado})`;
            resultado.className = 'prediccion-resultado prediccion-ok';

            // Guardar la predicción en hiddens para que el backend agregue precio_estimado_ia / tiempo_estimado_ia
            const precioInput = tareaDiv.querySelector('.precio-estimado');
            const tiempoInput = tareaDiv.querySelector('.tiempo-estimado');
            if (precioInput) precioInput.value = Number(data.coste).toFixed(2);
            if (tiempoInput) tiempoInput.value = Math.round(Number(data.minutos ?? (data.horas * 60)));
        })
        .catch(err => {
            resultado.textContent = 'Error de red: ' + err.message;
            resultado.className = 'prediccion-resultado prediccion-error';
        })
        .finally(() => { btn.disabled = false; });
});
</script>

<script>
    document.getElementById('tipo_reparacion').addEventListener('change', function() {
        const tipoId = this.value;
        const subSelect = document.getElementById('subgrupo_reparacion');
        
        if (!tipoId) {
            subSelect.innerHTML = '<option value="">-- Seleccionar Tipo Primero --</option>';
            subSelect.disabled = true;
            return;
        }

        // Hacer petición AJAX
        fetch(`index.php?action=obtenerSubgrupos&tipo_id=${tipoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }
                
                // Limpiar y poblar el select
                subSelect.innerHTML = '<option value="">-- Seleccionar Subgrupo --</option>';
                data.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.nombre;
                    subSelect.appendChild(option);
                });
                subSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error en AJAX:', error);
                alert('Error al cargar subgrupos');
            });
    });
</script>

<script>
    // ── Autocomplete de matrícula (estilo buscarClientes) ──
    (function() {
        const inputBuscar    = document.getElementById('buscar_vehiculo');
        const inputHiddenId  = document.getElementById('vehiculo_id');
        const lista          = document.getElementById('sugerencias-vehiculos');
        const seleccionado   = document.getElementById('vehiculo-seleccionado');
        let timeoutBusqueda;

        function ocultarSugerencias() {
            lista.style.display = 'none';
            lista.innerHTML = '';
        }

        function limpiarSeleccion() {
            inputHiddenId.value = '';
            seleccionado.style.display = 'none';
            seleccionado.textContent = '';
        }

        function mostrarSugerencias(vehiculos) {
            lista.innerHTML = '';
            if (!vehiculos.length) {
                lista.innerHTML = '<div class="sugerencia-item sugerencia-vacia">Sin coincidencias</div>';
                lista.style.display = 'block';
                return;
            }
            vehiculos.forEach(v => {
                const item = document.createElement('div');
                item.className = 'sugerencia-item';
                item.innerHTML = `
                    <div class="sugerencia-nombre"><strong>${v.matricula}</strong> — ${v.marca} ${v.modelo}${v.anio ? ' (' + v.anio + ')' : ''}</div>
                    <div class="sugerencia-email">Cliente: ${v.nombre_cliente}</div>
                `;
                item.addEventListener('click', () => seleccionarVehiculo(v));
                lista.appendChild(item);
            });
            lista.style.display = 'block';
        }

        function seleccionarVehiculo(v) {
            inputHiddenId.value = v.id;
            inputBuscar.value   = v.matricula;
            seleccionado.style.display = 'block';
            seleccionado.textContent = `Seleccionado: ${v.matricula} — ${v.marca} ${v.modelo} (Cliente: ${v.nombre_cliente})`;
            ocultarSugerencias();
        }

        function buscar(q) {
            fetch(`index.php?action=buscarVehiculos&q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(mostrarSugerencias)
                .catch(err => console.error('Error buscando vehículos:', err));
        }

        inputBuscar.addEventListener('input', function() {
            clearTimeout(timeoutBusqueda);
            const q = this.value.trim();
            // Si el usuario edita el texto tras haber seleccionado, invalidamos la selección
            if (inputHiddenId.value) limpiarSeleccion();
            if (q.length >= 2) {
                timeoutBusqueda = setTimeout(() => buscar(q), 300);
            } else {
                ocultarSugerencias();
            }
        });

        inputBuscar.addEventListener('focus', function() {
            if (this.value.trim().length >= 2 && !inputHiddenId.value) {
                buscar(this.value.trim());
            }
        });

        document.addEventListener('click', function(e) {
            if (!inputBuscar.contains(e.target) && !lista.contains(e.target)) {
                ocultarSugerencias();
            }
        });

        // Validación al enviar: exigir selección desde la lista
        const formulario = inputBuscar.closest('form');
        if (formulario) {
            formulario.addEventListener('submit', function(e) {
                if (!inputHiddenId.value) {
                    e.preventDefault();
                    alert('Debes seleccionar un vehículo de la lista de sugerencias.');
                    inputBuscar.focus();
                }
            });
        }
    })();
</script>