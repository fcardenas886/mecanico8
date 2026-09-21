-- Catalogo de servicios (mano de obra) con precio definido y politica de cobro.
-- Se reutiliza operacionessolicitadas (los servicios que ya se marcan en la recepcion).
-- PoliticaCobro: 'Siempre' (se cobra si el cliente aprueba) o 'SoloSiNoAprueba'
-- (ej. el diagnostico: no se cobra si el cliente aprueba la reparacion, se cobra si la rechaza).
-- Los precios sembrados son de EJEMPLO y se ajustan desde la pantalla Servicios y precios.
-- Requerido por: servicios.php, presupuesto.php

ALTER TABLE operacionessolicitadas
  ADD COLUMN PrecioBase INT DEFAULT NULL,
  ADD COLUMN PoliticaCobro VARCHAR(20) NOT NULL DEFAULT 'Siempre',
  ADD COLUMN EsDiagnosticoBase TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE presupuestodetalle
  ADD COLUMN ServicioID INT DEFAULT NULL,
  ADD COLUMN PoliticaCobro VARCHAR(20) NOT NULL DEFAULT 'Siempre';

UPDATE operacionessolicitadas SET PrecioBase = 35000 WHERE OperacionID = 1;
UPDATE operacionessolicitadas SET PrecioBase = 25000 WHERE OperacionID = 2;
UPDATE operacionessolicitadas SET PrecioBase = 45000 WHERE OperacionID = 3;
UPDATE operacionessolicitadas SET PrecioBase = 15000 WHERE OperacionID = 4;
UPDATE operacionessolicitadas SET PrecioBase = 10000 WHERE OperacionID = 5;
UPDATE operacionessolicitadas SET PrecioBase = 20000 WHERE OperacionID = 6;
UPDATE operacionessolicitadas SET PrecioBase = 40000 WHERE OperacionID = 7;
UPDATE operacionessolicitadas SET PrecioBase = 20000 WHERE OperacionID = 8;
UPDATE operacionessolicitadas SET PrecioBase = 18000 WHERE OperacionID = 9;
UPDATE operacionessolicitadas SET PrecioBase = 120000 WHERE OperacionID = 10;
UPDATE operacionessolicitadas SET PrecioBase = 15000 WHERE OperacionID = 11;
UPDATE operacionessolicitadas SET PrecioBase = 18000 WHERE OperacionID = 12;

INSERT INTO operacionessolicitadas (Nombre, Categoria, Orden, Activo, PrecioBase, PoliticaCobro, EsDiagnosticoBase)
VALUES ('Diagnóstico general', 'Diagnóstico', 0, 1, 15000, 'SoloSiNoAprueba', 1);
