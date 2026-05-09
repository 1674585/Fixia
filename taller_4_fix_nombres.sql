-- ================================================================
-- Fix: corrige nombres de catalogo_tareas del taller 4 para que
-- coincidan EXACTAMENTE con subgrupos_reparacion.nombre (con acentos
-- y nombres completos), y así el JOIN del CSV los enlace.
-- Ejecutar UNA sola vez después del taller_4_data.sql.
-- ================================================================
START TRANSACTION;

UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de líquido de frenos'                  WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de liquido de frenos';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación de pinzas'                              WHERE taller_id = 4 AND nombre_tarea = 'Reparacion de pinzas';
UPDATE catalogo_tareas SET nombre_tarea = 'Cambio de zapatas / tambores'                      WHERE taller_id = 4 AND nombre_tarea = 'Cambio de zapatas';
UPDATE catalogo_tareas SET nombre_tarea = 'Comprobación y ajuste de frenos de mano'           WHERE taller_id = 4 AND nombre_tarea = 'Ajuste de freno de mano';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de correas (distribución, accesorios)' WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de correa de distribucion';
UPDATE catalogo_tareas SET nombre_tarea = 'Cambio de bujías'                                  WHERE taller_id = 4 AND nombre_tarea = 'Cambio de bujias';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de filtro de aire'                     WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de filtro de aire';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de filtro de combustible'              WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de filtro de combustible';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación de culata / juntas'                     WHERE taller_id = 4 AND nombre_tarea = 'Reparacion de culata';
UPDATE catalogo_tareas SET nombre_tarea = 'Diagnóstico electrónico del motor'                 WHERE taller_id = 4 AND nombre_tarea = 'Diagnostico electronico del motor';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de volante bimasa'                     WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de volante bimasa';
UPDATE catalogo_tareas SET nombre_tarea = 'Cambio de aceite de caja de cambios manual'        WHERE taller_id = 4 AND nombre_tarea = 'Cambio de aceite caja manual';
UPDATE catalogo_tareas SET nombre_tarea = 'Cambio de aceite de caja automática'               WHERE taller_id = 4 AND nombre_tarea = 'Cambio de aceite caja automatica';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación de diferencial'                         WHERE taller_id = 4 AND nombre_tarea = 'Reparacion de diferencial';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación de palieres y homocinéticas'            WHERE taller_id = 4 AND nombre_tarea = 'Reparacion de palieres';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de amortiguadores'                     WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de amortiguadores';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de muelles / ballestas'                WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de muelles';
UPDATE catalogo_tareas SET nombre_tarea = 'Cambio de brazos / bieletas'                       WHERE taller_id = 4 AND nombre_tarea = 'Cambio de bieletas';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de rótulas'                            WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de rotulas';
UPDATE catalogo_tareas SET nombre_tarea = 'Alineación de ruedas / geometría'                  WHERE taller_id = 4 AND nombre_tarea = 'Alineacion de ruedas';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de batería'                            WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de bateria';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación alternador / motor de arranque'         WHERE taller_id = 4 AND nombre_tarea = 'Reparacion alternador';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de luces'                              WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de luces';
UPDATE catalogo_tareas SET nombre_tarea = 'Diagnóstico electrónico de averías'                WHERE taller_id = 4 AND nombre_tarea = 'Diagnostico electrico';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de fusibles y relés'                   WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de fusibles';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación de centralitas y módulos'               WHERE taller_id = 4 AND nombre_tarea = 'Reparacion de centralita';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de compresor'                          WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de compresor A/C';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de filtro de habitáculo'               WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de filtro de habitaculo';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación de evaporador / condensador'            WHERE taller_id = 4 AND nombre_tarea = 'Reparacion de evaporador A/C';
UPDATE catalogo_tareas SET nombre_tarea = 'Comprobación de fugas y presión'                   WHERE taller_id = 4 AND nombre_tarea = 'Comprobacion de fugas A/C';
UPDATE catalogo_tareas SET nombre_tarea = 'Cambio de silencioso / tramo de escape'            WHERE taller_id = 4 AND nombre_tarea = 'Cambio de tramo de escape';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de catalizador'                        WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de catalizador';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de sondas lambda'                      WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de sonda lambda';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación de tubos y soportes'                    WHERE taller_id = 4 AND nombre_tarea = 'Reparacion de tubos escape';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de puertas, paragolpes, capó'          WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de paragolpes';
UPDATE catalogo_tareas SET nombre_tarea = 'Pintura parcial / completa'                        WHERE taller_id = 4 AND nombre_tarea = 'Pintura parcial';
UPDATE catalogo_tareas SET nombre_tarea = 'Pulido y retoque de pintura'                       WHERE taller_id = 4 AND nombre_tarea = 'Pulido de pintura';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de lunas'                              WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de luna';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de neumáticos'                         WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de neumaticos';
UPDATE catalogo_tareas SET nombre_tarea = 'Montaje / equilibrado'                             WHERE taller_id = 4 AND nombre_tarea = 'Equilibrado de ruedas';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación de pinchazos'                           WHERE taller_id = 4 AND nombre_tarea = 'Reparacion de pinchazos';
UPDATE catalogo_tareas SET nombre_tarea = 'Alineación y paralelismo'                          WHERE taller_id = 4 AND nombre_tarea = 'Paralelismo';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de bomba de combustible'               WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de bomba de combustible';
UPDATE catalogo_tareas SET nombre_tarea = 'Limpieza o cambio de inyectores'                   WHERE taller_id = 4 AND nombre_tarea = 'Cambio de inyectores';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de filtro de combustible'              WHERE taller_id = 4 AND nombre_tarea = 'Cambio filtro combustible';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación depósito y conductos'                   WHERE taller_id = 4 AND nombre_tarea = 'Reparacion deposito';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de radiador'                           WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de radiador';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de bomba de agua'                      WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de bomba de agua';
UPDATE catalogo_tareas SET nombre_tarea = 'Sustitución de termostato'                         WHERE taller_id = 4 AND nombre_tarea = 'Sustitucion de termostato';
UPDATE catalogo_tareas SET nombre_tarea = 'Cambio de líquido refrigerante'                    WHERE taller_id = 4 AND nombre_tarea = 'Cambio de liquido refrigerante';
UPDATE catalogo_tareas SET nombre_tarea = 'Reparación manguitos y abrazaderas'                WHERE taller_id = 4 AND nombre_tarea = 'Reparacion manguitos';
UPDATE catalogo_tareas SET nombre_tarea = 'Inspección pre-ITV'                                WHERE taller_id = 4 AND nombre_tarea = 'Inspeccion pre-ITV';
UPDATE catalogo_tareas SET nombre_tarea = 'Revisión 10.000 / 20.000 km'                       WHERE taller_id = 4 AND nombre_tarea = 'Revision 20.000 km';
UPDATE catalogo_tareas SET nombre_tarea = 'Comprobación de niveles y líquidos'                WHERE taller_id = 4 AND nombre_tarea = 'Comprobacion de niveles';
UPDATE catalogo_tareas SET nombre_tarea = 'Lavado interior / exterior'                        WHERE taller_id = 4 AND nombre_tarea = 'Lavado integral';
UPDATE catalogo_tareas SET nombre_tarea = 'Diagnóstico'                                       WHERE taller_id = 4 AND nombre_tarea = 'Diagnostico general';

COMMIT;

-- Verificación: ninguna fila debería seguir sin match contra subgrupos_reparacion
SELECT COUNT(*) AS sin_match
FROM catalogo_tareas ct
LEFT JOIN subgrupos_reparacion sg ON sg.nombre = ct.nombre_tarea
WHERE ct.taller_id = 4 AND sg.id IS NULL;

-- ================================================================
-- Bonus: actualizar password del jefe del taller 4 a 'fixia123'
-- ================================================================
UPDATE usuarios SET password_hash = '$2y$10$jf3A/krRNtfeYiZ5xFOpauBqA4jQ47DMzqzvT3OPxp17zVjTZC44K'
WHERE id = 7001;
