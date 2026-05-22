-- =====================================================================
-- ALTERs para 3 funcionalidades nuevas
--   1) Auto-cálculo de estimaciones IA en ordenes_trabajo
--   2) Pantalla "Mis Presupuestos" (cliente acepta/rechaza por tarea)
--   3) Pantalla "Facturación" con auditoría (quién y cuándo facturó)
-- Ejecutar una sola vez sobre la BBDD fixia.
-- =====================================================================

-- 1. Estimaciones IA persistentes por tarea (para agregarlas en la orden)
ALTER TABLE `tareas_asignadas`
  ADD COLUMN `precio_estimado` DECIMAL(10,2) DEFAULT NULL AFTER `duracion_real_minutos`,
  ADD COLUMN `tiempo_estimado_minutos` INT(11) DEFAULT NULL AFTER `precio_estimado`;

-- 2. Nuevo estado 'rechazada' para tareas que el cliente rechaza en su presupuesto
ALTER TABLE `tareas_asignadas`
  MODIFY COLUMN `estado` ENUM('pendiente','en_proceso','finalizada','rechazada') DEFAULT 'pendiente';

-- 3. Auditoría de facturación: quién marcó "facturado" y cuándo
ALTER TABLE `ordenes_trabajo`
  ADD COLUMN `facturado_por_id` INT(11) DEFAULT NULL,
  ADD COLUMN `facturado_en` DATETIME DEFAULT NULL,
  ADD KEY `fk_orden_facturado_por` (`facturado_por_id`),
  ADD CONSTRAINT `fk_orden_facturado_por`
      FOREIGN KEY (`facturado_por_id`) REFERENCES `usuarios` (`id`);
