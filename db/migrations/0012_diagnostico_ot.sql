-- Paso 3 del flujo de taller: Diagnostico / Evaluacion Tecnica. Se registra
-- entre la Orden de Ingreso y el Presupuesto: hallazgos del mecanico por area,
-- sobre la misma OT. Paso saltable si el cliente ya pidio algo puntual.
-- Requerido por: diagnostico.php

CREATE TABLE diagnosticoot (
  DiagnosticoID INT AUTO_INCREMENT PRIMARY KEY,
  OrdenTrabajoID INT NOT NULL,
  Area VARCHAR(20) NOT NULL,
  Hallazgo VARCHAR(500) NOT NULL,
  UsuarioID INT NOT NULL,
  Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT FK_Diagnostico_OT FOREIGN KEY (OrdenTrabajoID) REFERENCES ordenestrabajo (OrdenTrabajoID) ON DELETE CASCADE,
  CONSTRAINT FK_Diagnostico_Usuario FOREIGN KEY (UsuarioID) REFERENCES usuarios (UsuarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
