<?php
// Asumiendo que $tipos viene del controlador
// $vehiculos también
?>
<h2>Crear Orden de Trabajo</h2>
<form method="POST" action="index.php?action=crearOrden">
    <label for="vehiculo_id">Seleccionar Vehículo:</label>
    <select name="vehiculo_id" required>
        <option value="">-- Seleccionar --</option>
        <?php foreach ($vehiculos as $veh): ?>
            <option value="<?php echo $veh['id']; ?>"><?php echo $veh['matricula'] . ' - ' . $veh['marca'] . ' ' . $veh['modelo']; ?></option>
        <?php endforeach; ?>
    </select><br>

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
            <label>Tipo de Reparación:</label>
            <select name="tareas[0][tipo]" class="tipo">
                <option value="">-- Seleccionar --</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?php echo $tipo['id']; ?>"><?php echo $tipo['nombre']; ?></option>
                <?php endforeach; ?>
            </select>

            <label>Subgrupo:</label>
            <select name="tareas[0][subgrupo]" class="subgrupo" disabled>
                <option value="">-- Seleccionar Tipo Primero --</option>
            </select>
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
        <label>Tipo de Reparación:</label>
        <select name="tareas[${index}][tipo]" class="tipo">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?php echo $tipo['id']; ?>"><?php echo $tipo['nombre']; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Subgrupo:</label>
        <select name="tareas[${index}][subgrupo]" class="subgrupo" disabled>
            <option value="">-- Seleccionar Tipo Primero --</option>
        </select>
    `;

    container.appendChild(div);
    index++;
});

// Delegación de eventos (clave)
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('tipo')) {
        const tipoId = e.target.value;
        const subSelect = e.target.parentElement.querySelector('.subgrupo');

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