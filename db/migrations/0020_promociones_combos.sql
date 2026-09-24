-- Combos por tipo de repuesto o por producto especifico (ej. "1 Aceite + 1 Filtro de Aceite").
-- Se guardan aparte de `promociones` (que sigue manejando ofertas de un solo producto) para no tocarla.
-- Requerido por: promociones.php, api/registrar_venta.php

CREATE TABLE promociones_combos (
  ComboID INT AUTO_INCREMENT PRIMARY KEY,
  Nombre VARCHAR(150) NOT NULL,
  TipoDescuento ENUM('PORCENTAJE', 'MONTO_FIJO', 'PRECIO_FIJO') NOT NULL,
  ValorDescuento DECIMAL(10,2) NOT NULL,
  FechaInicio DATETIME NOT NULL,
  FechaFin DATETIME NOT NULL,
  Activa TINYINT(1) NOT NULL DEFAULT 1,
  CreadoEn TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Los "cupos" que debe cumplir el carrito para activar el combo.
CREATE TABLE promociones_combo_items (
  ComboItemID INT AUTO_INCREMENT PRIMARY KEY,
  ComboID INT NOT NULL,
  ModoSeleccion ENUM('TIPO_REPUESTO', 'PRODUCTO_ESPECIFICO') NOT NULL DEFAULT 'TIPO_REPUESTO',
  TipoRepuesto ENUM('Aceite','FiltroAceite','FiltroAire','FiltroCombustible','FiltroCabina','Frenos','Bujias','Distribucion','General') NULL,
  ProductoID INT NULL,
  CantidadRequerida DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  FOREIGN KEY (ComboID) REFERENCES promociones_combos(ComboID) ON DELETE CASCADE,
  FOREIGN KEY (ProductoID) REFERENCES productos(ProductoID) ON DELETE CASCADE
);

-- Snapshot de texto (no FK) para que la boleta y las devoluciones sigan mostrando el
-- nombre del combo aunque el combo se edite o elimine despues de la venta.
ALTER TABLE detalleventas
  ADD COLUMN ComboAplicado VARCHAR(150) DEFAULT NULL;
