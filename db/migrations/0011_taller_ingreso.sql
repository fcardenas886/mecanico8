-- Ficha de Vehiculo, Checklist de Recepcion (configurable) y Orden de Ingreso
-- (Comprobante de Custodia). Cubre los pasos 1 y 2 del flujo de taller acordado
-- con el cliente: Checklist de Recepcion -> Orden de Ingreso.
-- Diagnostico, Presupuesto y ejecucion de la OT se agregan en migraciones posteriores.
-- Requerido por: vehiculos.php, ordeningreso.php, comprobante_ot.php, ordenestrabajo.php

CREATE TABLE vehiculos (
  VehiculoID INT AUTO_INCREMENT PRIMARY KEY,
  ClienteID INT NOT NULL,
  Patente VARCHAR(15) NOT NULL,
  Marca VARCHAR(50) NOT NULL,
  Modelo VARCHAR(50) NOT NULL,
  Anio SMALLINT DEFAULT NULL,
  Color VARCHAR(30) DEFAULT NULL,
  VIN VARCHAR(30) DEFAULT NULL,
  KilometrajeUltimo INT DEFAULT NULL,
  Activo TINYINT(1) NOT NULL DEFAULT 1,
  FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT FK_Vehiculos_Clientes FOREIGN KEY (ClienteID) REFERENCES clientes (ClienteID),
  UNIQUE KEY UQ_Vehiculos_Patente (Patente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catalogo configurable de items del checklist: el administrador puede agregar,
-- renombrar o desactivar items segun lo requiera el taller, no es una lista fija.
CREATE TABLE checklistitems (
  ChecklistItemID INT AUTO_INCREMENT PRIMARY KEY,
  Nombre VARCHAR(80) NOT NULL,
  Categoria VARCHAR(30) NOT NULL DEFAULT 'Accesorio',
  Orden INT NOT NULL DEFAULT 0,
  Activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO checklistitems (Nombre, Categoria, Orden) VALUES
  ('Matrícula', 'Accesorio', 1),
  ('Espejo izquierdo', 'Accesorio', 2),
  ('Espejo derecho', 'Accesorio', 3),
  ('Vidrios', 'Accesorio', 4),
  ('Plumas limpiaparabrisas', 'Accesorio', 5),
  ('Radio', 'Accesorio', 6),
  ('Encendedor', 'Accesorio', 7),
  ('Control de alarma', 'Accesorio', 8),
  ('Tapacubos', 'Accesorio', 9),
  ('Tapa de gasolina', 'Accesorio', 10),
  ('Antena', 'Accesorio', 11),
  ('Gata', 'Seguridad', 12),
  ('Llanta de emergencia', 'Seguridad', 13),
  ('Extintor', 'Seguridad', 14),
  ('Triángulos', 'Seguridad', 15),
  ('Herramientas', 'Seguridad', 16),
  ('Botiquín', 'Seguridad', 17),
  ('Golpes', 'Daño', 18),
  ('Rayaduras', 'Daño', 19),
  ('Emblemas', 'Daño', 20),
  ('Faros / Focos', 'Daño', 21);

-- Orden de Trabajo: el expediente central del vehiculo. Esta migracion solo cubre
-- los campos del Ingreso (recepcion + custodia); Diagnostico, Presupuesto y avance
-- de reparacion se agregan mas adelante sobre esta misma tabla.
CREATE TABLE ordenestrabajo (
  OrdenTrabajoID INT AUTO_INCREMENT PRIMARY KEY,
  VehiculoID INT NOT NULL,
  ClienteID INT NOT NULL,
  UsuarioID INT NOT NULL COMMENT 'Quien recibio el vehiculo',
  FechaIngreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KilometrajeIngreso INT DEFAULT NULL,
  NivelCombustible VARCHAR(10) NOT NULL DEFAULT '1/2',
  ObjetosValor VARCHAR(255) DEFAULT NULL,
  AutorizaPresupuestoPrevio TINYINT(1) NOT NULL DEFAULT 1,
  AutorizaPruebaManejo TINYINT(1) NOT NULL DEFAULT 0,
  FirmaClienteBase64 LONGTEXT DEFAULT NULL,
  Estado VARCHAR(30) NOT NULL DEFAULT 'Ingresado',
  FechaEntrega DATETIME DEFAULT NULL,
  CONSTRAINT FK_OT_Vehiculos FOREIGN KEY (VehiculoID) REFERENCES vehiculos (VehiculoID),
  CONSTRAINT FK_OT_Clientes FOREIGN KEY (ClienteID) REFERENCES clientes (ClienteID),
  CONSTRAINT FK_OT_Usuarios FOREIGN KEY (UsuarioID) REFERENCES usuarios (UsuarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Respuesta Si/No de cada item del checklist para una OT concreta, con un detalle
-- breve opcional (ej. "Rayadura puerta izquierda"). Referencia el catalogo, no
-- repite texto libre por cada orden.
CREATE TABLE checklistrecepcion (
  ChecklistRecepcionID INT AUTO_INCREMENT PRIMARY KEY,
  OrdenTrabajoID INT NOT NULL,
  ChecklistItemID INT NOT NULL,
  Valor ENUM('Si','No') NOT NULL,
  Detalle VARCHAR(255) DEFAULT NULL,
  CONSTRAINT FK_Checklist_OT FOREIGN KEY (OrdenTrabajoID) REFERENCES ordenestrabajo (OrdenTrabajoID) ON DELETE CASCADE,
  CONSTRAINT FK_Checklist_Item FOREIGN KEY (ChecklistItemID) REFERENCES checklistitems (ChecklistItemID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
