-- Migración 0026: Módulo de Revisión Técnica (PRT) y Alertas de Vencimiento
-- Añade soporte para el calendario legal MTT (Decreto 156) para particulares (anual) y transporte/camiones (semestral)

ALTER TABLE vehiculos
  ADD COLUMN RevisionTecnicaRegimen ENUM('anual','semestral') NOT NULL DEFAULT 'anual' AFTER KilometrajeUltimo,
  ADD COLUMN RevisionTecnicaMes1 TINYINT UNSIGNED NULL AFTER RevisionTecnicaRegimen,
  ADD COLUMN RevisionTecnicaMes2 TINYINT UNSIGNED NULL AFTER RevisionTecnicaMes1,
  ADD COLUMN RevisionTecnicaVencimiento DATE NULL AFTER RevisionTecnicaMes2,
  ADD COLUMN RevisionTecnicaUltima DATE NULL AFTER RevisionTecnicaVencimiento,
  ADD COLUMN RevisionTecnicaEstado VARCHAR(20) NOT NULL DEFAULT 'vigente' AFTER RevisionTecnicaUltima,
  ADD COLUMN EsTransportePublico TINYINT(1) NOT NULL DEFAULT 0 AFTER RevisionTecnicaEstado,
  ADD COLUMN RevisionTecnicaActualizadoEn DATETIME NULL AFTER EsTransportePublico;

-- Índice para optimizar filtros de vencimiento
CREATE INDEX IDX_Vehiculos_RT_Vencimiento ON vehiculos (RevisionTecnicaVencimiento, RevisionTecnicaEstado);
