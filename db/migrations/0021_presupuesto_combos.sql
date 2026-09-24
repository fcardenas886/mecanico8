-- El presupuesto puede aplicar (o no) los combos de Promociones, con el mismo motor de la Caja,
-- para que lo que se le entrega al cliente coincida con lo que se cobra.
-- Requerido por: presupuesto.php, api/cargar_presupuesto_ot.php

ALTER TABLE presupuestos
  ADD COLUMN AplicaCombos TINYINT(1) NOT NULL DEFAULT 1;
