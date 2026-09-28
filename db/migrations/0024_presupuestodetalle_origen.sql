-- Agrega la columna Origen a presupuestodetalle para registrar de dónde proviene la línea
-- ('Directo', 'Diagnostico', 'Historial', 'Compatibilidad', 'Cliente').
-- Requerido por: presupuesto.php

ALTER TABLE presupuestodetalle
  ADD COLUMN Origen VARCHAR(50) DEFAULT 'Directo';
