-- Paso 4 del flujo de taller: Presupuesto / Cotizacion. Tablas propias del
-- taller (NO reutiliza cotizaciones/cotizacionesdetalle del minimarket, que
-- el POS ya usa para pausar ventas -- mezclar ambos dominios habria sido
-- arriesgado). Desglose en 3 tipos de linea y aprobacion por linea, para
-- soportar Aprobado Total / Aprobado Parcial / Rechazado.
-- Requerido por: presupuesto.php

CREATE TABLE presupuestos (
  PresupuestoID INT AUTO_INCREMENT PRIMARY KEY,
  OrdenTrabajoID INT NOT NULL,
  UsuarioID INT NOT NULL,
  FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  TiempoEntrega VARCHAR(100) DEFAULT NULL,
  DecisionCliente VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
  FechaDecision DATETIME DEFAULT NULL,
  CONSTRAINT FK_Presupuesto_OT FOREIGN KEY (OrdenTrabajoID) REFERENCES ordenestrabajo (OrdenTrabajoID),
  CONSTRAINT FK_Presupuesto_Usuario FOREIGN KEY (UsuarioID) REFERENCES usuarios (UsuarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE presupuestodetalle (
  PresupuestoDetalleID INT AUTO_INCREMENT PRIMARY KEY,
  PresupuestoID INT NOT NULL,
  TipoLinea VARCHAR(20) NOT NULL,
  ProductoID INT DEFAULT NULL,
  Descripcion VARCHAR(255) NOT NULL,
  Cantidad DECIMAL(10,3) NOT NULL DEFAULT 1,
  PrecioUnitario INT NOT NULL,
  Subtotal INT NOT NULL,
  Aprobado TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT FK_PresupuestoDetalle_Presupuesto FOREIGN KEY (PresupuestoID) REFERENCES presupuestos (PresupuestoID) ON DELETE CASCADE,
  CONSTRAINT FK_PresupuestoDetalle_Producto FOREIGN KEY (ProductoID) REFERENCES productos (ProductoID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
