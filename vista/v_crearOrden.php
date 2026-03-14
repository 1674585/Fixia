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

    <label for="tipo_reparacion">Tipo de Reparación (Opcional):</label>
    <select name="tipo_reparacion" id="tipo_reparacion">
        <option value="">-- Seleccionar --</option>
        <?php foreach ($tipos as $tipo): ?>
            <option value="<?php echo $tipo['id']; ?>"><?php echo $tipo['nombre']; ?></option>
        <?php endforeach; ?>
    </select><br>

    <label for="subgrupo_reparacion">Subgrupo de Reparación (Opcional):</label>
    <select name="subgrupo_reparacion" id="subgrupo_reparacion" disabled>
        <option value="">-- Seleccionar Tipo Primero --</option>
    </select><br>

    <button type="submit">Crear Orden</button>
</form>

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