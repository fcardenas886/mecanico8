-- Paso 5 del flujo de taller: Orden de Trabajo (ejecucion). Mecanico asignado
-- y el vinculo con lo que efectivamente se cobro: la venta real de repuestos
-- (hecha por el POS existente, sin modificarlo) y el cobro de mano de obra /
-- terceros (via movimientoscaja, mismo mecanismo que ya usan los abonos).
-- Requerido por: ejecucion.php

ALTER TABLE ordenestrabajo
  ADD COLUMN MecanicoID INT DEFAULT NULL,
  ADD COLUMN VentaID INT DEFAULT NULL,
  ADD COLUMN ManoObraCobrada TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN MontoManoObraCobrado INT DEFAULT NULL,
  ADD CONSTRAINT FK_OT_Mecanico FOREIGN KEY (MecanicoID) REFERENCES usuarios (UsuarioID),
  ADD CONSTRAINT FK_OT_Venta FOREIGN KEY (VentaID) REFERENCES ventas (VentaID);
