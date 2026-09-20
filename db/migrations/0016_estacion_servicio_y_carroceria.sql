-- ==============================================================================
-- Migración 0016: Estación de Servicio, Operaciones Solicitadas y Diagrama de Daños
-- ==============================================================================

-- 1. Catálogo maestro de Operaciones Solicitadas Rápidas (Basado en Imagen 1)
CREATE TABLE IF NOT EXISTS operacionessolicitadas (
  OperacionID INT AUTO_INCREMENT PRIMARY KEY,
  Nombre VARCHAR(100) NOT NULL,
  Categoria VARCHAR(50) NOT NULL DEFAULT 'Mantenimiento',
  Orden INT NOT NULL DEFAULT 0,
  Activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO operacionessolicitadas (Nombre, Categoria, Orden) VALUES
  ('Limpieza de Inyectores', 'Inyección', 1),
  ('Limpieza Cuerpo de Aceleración', 'Inyección', 2),
  ('Servicio de frenos completo', 'Frenos', 3),
  ('Escaneo computarizado', 'Diagnóstico', 4),
  ('Cambio de Aceite y Filtro', 'Mantenimiento', 5),
  ('Limpieza Válvula IAC', 'Inyección', 6),
  ('Cambio de bomba de gasolina', 'Combustible', 7),
  ('Medición de compresión', 'Diagnóstico', 8),
  ('Cambio de Aceite de Caja', 'Mantenimiento', 9),
  ('Cambio de Kit de Distribución', 'Motor', 10),
  ('Cambio de filtro y pre filtro de gas.', 'Combustible', 11),
  ('Cambio de Bujías', 'Encendido', 12);

-- 2. Operaciones solicitadas elegidas para una Orden de Trabajo concreta
CREATE TABLE IF NOT EXISTS orden_operaciones_solicitadas (
  ID INT AUTO_INCREMENT PRIMARY KEY,
  OrdenTrabajoID INT NOT NULL,
  OperacionID INT DEFAULT NULL,
  NombreOperacion VARCHAR(150) NOT NULL,
  CONSTRAINT FK_OOS_OT FOREIGN KEY (OrdenTrabajoID) REFERENCES ordenestrabajo (OrdenTrabajoID) ON DELETE CASCADE,
  CONSTRAINT FK_OOS_Operacion FOREIGN KEY (OperacionID) REFERENCES operacionessolicitadas (OperacionID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Chequeo de la Estación de Servicio y Fluidos por OT (Basado en Imagen 2)
CREATE TABLE IF NOT EXISTS estacionservicio_ot (
  EstacionID INT AUTO_INCREMENT PRIMARY KEY,
  OrdenTrabajoID INT NOT NULL,
  MotorNivel ENUM('Normal', 'Bajo', 'No revisado') NOT NULL DEFAULT 'Normal',
  MotorCambio TINYINT(1) NOT NULL DEFAULT 0,
  FiltroCambio TINYINT(1) NOT NULL DEFAULT 0,
  DHNivel ENUM('Normal', 'Bajo', 'No aplica') NOT NULL DEFAULT 'Normal',
  CajaNivel ENUM('Normal', 'Bajo', 'No revisado') NOT NULL DEFAULT 'Normal',
  CajaCambio TINYINT(1) NOT NULL DEFAULT 0,
  FrenosNivel ENUM('Normal', 'Bajo', 'Contaminado') NOT NULL DEFAULT 'Normal',
  FrenosCambio TINYINT(1) NOT NULL DEFAULT 0,
  RadiadorNivel ENUM('Normal', 'Bajo', 'No revisado') NOT NULL DEFAULT 'Normal',
  RadiadorAnticongelante TINYINT(1) NOT NULL DEFAULT 0,
  LavVidrioCarga TINYINT(1) NOT NULL DEFAULT 0,
  LavadoCarroceria ENUM('No', 'Basico', 'Completo') NOT NULL DEFAULT 'No',
  Observaciones VARCHAR(500) DEFAULT NULL,
  CONSTRAINT FK_Estacion_OT FOREIGN KEY (OrdenTrabajoID) REFERENCES ordenestrabajo (OrdenTrabajoID) ON DELETE CASCADE,
  UNIQUE KEY UQ_Estacion_OT (OrdenTrabajoID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Ampliar ordenestrabajo para almacenar los puntos del diagrama de daños (coordenadas relativas x, y, tipo) y texto libre adicional de operaciones
ALTER TABLE ordenestrabajo
  ADD COLUMN DaniosCarroceriaJson LONGTEXT DEFAULT NULL AFTER ObjetosValor,
  ADD COLUMN OperacionesTextoLibre TEXT DEFAULT NULL AFTER DaniosCarroceriaJson;
