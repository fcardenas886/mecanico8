-- El sistema aprende que repuestos usa cada modelo (y motor) a partir de las
-- ordenes entregadas. Origen distingue lo cargado a mano de lo aprendido;
-- VecesUsado y UltimoUso permiten ordenar por lo que mas se ha comprobado.
-- Requerido por: includes/repuestos_aprendizaje.php, buscador_repuestos.php

ALTER TABLE compatibilidadrepuestos
  ADD COLUMN Origen VARCHAR(20) NOT NULL DEFAULT 'Manual',
  ADD COLUMN VecesUsado INT NOT NULL DEFAULT 0,
  ADD COLUMN UltimoUso DATETIME DEFAULT NULL;
