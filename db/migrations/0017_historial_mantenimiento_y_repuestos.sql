-- ==============================================================================
-- Migración 0017: Historial de Mantenimiento, Catálogo de Repuestos y Matriz de Compatibilidad
-- ==============================================================================

-- 1. Tabla de Historial Clínico de Mantenimiento y Alertas Preventivas por Vehículo
CREATE TABLE IF NOT EXISTS historialmantenimiento (
  MantenimientoID INT AUTO_INCREMENT PRIMARY KEY,
  VehiculoID INT NOT NULL,
  OrdenTrabajoID INT DEFAULT NULL,
  TipoMantenimiento VARCHAR(100) NOT NULL,
  KilometrajeRealizado INT NOT NULL,
  FechaRealizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KilometrajeProximo INT DEFAULT NULL,
  FechaProxima DATE DEFAULT NULL,
  Estado ENUM('Vigente', 'Proximo', 'Vencido') NOT NULL DEFAULT 'Vigente',
  Notas VARCHAR(500) DEFAULT NULL,
  UsuarioID INT DEFAULT NULL,
  CONSTRAINT FK_HM_Vehiculo FOREIGN KEY (VehiculoID) REFERENCES vehiculos (VehiculoID) ON DELETE CASCADE,
  CONSTRAINT FK_HM_OT FOREIGN KEY (OrdenTrabajoID) REFERENCES ordenestrabajo (OrdenTrabajoID) ON DELETE SET NULL,
  INDEX idx_hm_vehiculo (VehiculoID),
  INDEX idx_hm_fecha (FechaRealizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categorías Automotrices para el Taller Mecánico
INSERT INTO categorias (Nombre, Descripcion) VALUES
  ('Aceites y Lubricantes', 'Aceites de motor sintéticos, semisintéticos, minerales, ATF de transmisión y fluidos hidráulicos'),
  ('Filtros Automotrices', 'Filtros de aceite, aire de motor, cabina/polen y combustible'),
  ('Frenos y Embrague', 'Pastillas, discos, balatas, tambores y líquido de frenos'),
  ('Encendido e Inyección', 'Bujías de encendido, bobinas, cables y limpiadores de inyectores'),
  ('Motor y Distribución', 'Kits de distribución, correas auxiliares, termostatos y bombas de agua')
ON DUPLICATE KEY UPDATE Nombre = VALUES(Nombre);

-- 3. Ampliar tabla de productos para números de parte automotriz (OEM / Alternativo / Marca / Viscosidad)
ALTER TABLE productos
  ADD COLUMN NumeroParteOEM VARCHAR(100) DEFAULT NULL AFTER Descripcion,
  ADD COLUMN NumeroParteAlternativo VARCHAR(100) DEFAULT NULL AFTER NumeroParteOEM,
  ADD COLUMN MarcaRepuesto VARCHAR(80) DEFAULT NULL AFTER NumeroParteAlternativo,
  ADD COLUMN ViscosidadAceite VARCHAR(30) DEFAULT NULL AFTER MarcaRepuesto,
  ADD COLUMN TipoRepuesto ENUM('Aceite', 'FiltroAceite', 'FiltroAire', 'FiltroCombustible', 'FiltroCabina', 'Frenos', 'Bujias', 'Distribucion', 'General') NOT NULL DEFAULT 'General' AFTER ViscosidadAceite;

-- 4. Matriz de Compatibilidad Vehículo - Repuesto ("¿Qué necesita este auto?")
CREATE TABLE IF NOT EXISTS compatibilidadrepuestos (
  CompatibilidadID INT AUTO_INCREMENT PRIMARY KEY,
  ProductoID INT NOT NULL,
  MarcaVehiculo VARCHAR(60) NOT NULL,
  ModeloVehiculo VARCHAR(60) NOT NULL,
  AnioDesde INT DEFAULT NULL,
  AnioHasta INT DEFAULT NULL,
  Motor VARCHAR(60) DEFAULT NULL,
  Notas VARCHAR(255) DEFAULT NULL,
  CONSTRAINT FK_CR_Producto FOREIGN KEY (ProductoID) REFERENCES productos (ProductoID) ON DELETE CASCADE,
  INDEX idx_vehiculo_marca_modelo (MarcaVehiculo, ModeloVehiculo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
