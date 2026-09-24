-- Al aprobar un presupuesto se congelan los descuentos (combos y ofertas) linea por linea.
-- Desde ahi el presupuesto es el que vale: la Caja cobra exactamente esos montos, aunque
-- despues venza una oferta o cambie un precio.
-- Requerido por: presupuesto.php, api/cargar_presupuesto_ot.php, api/registrar_venta.php

ALTER TABLE presupuestos
  ADD COLUMN DescuentosCongelados TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE presupuestodetalle
  ADD COLUMN DescuentoCombo INT NOT NULL DEFAULT 0,
  ADD COLUMN ComboNombre VARCHAR(150) DEFAULT NULL,
  ADD COLUMN DescuentoOferta INT NOT NULL DEFAULT 0;
