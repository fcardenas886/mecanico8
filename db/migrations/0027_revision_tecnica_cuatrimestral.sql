-- Migración 0027: Soporte para Régimen Cuatrimestral (cada 4 meses) de Revisión Técnica
-- Aplica a buses de transporte público con >= 20 años y transporte escolar antiguo según Decreto 156 MTT

ALTER TABLE vehiculos
  MODIFY COLUMN RevisionTecnicaRegimen ENUM('anual','semestral','cuatrimestral') NOT NULL DEFAULT 'anual';

ALTER TABLE vehiculos
  ADD COLUMN RevisionTecnicaMes3 TINYINT UNSIGNED NULL AFTER RevisionTecnicaMes2;
