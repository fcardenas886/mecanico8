-- Migración 0014: Intervalos configurables de mantenimiento en Estación de Servicio / Chequeo de Fluidos
ALTER TABLE estacionservicio_ot 
    ADD COLUMN AceiteIntervaloKm INT NULL DEFAULT 10000,
    ADD COLUMN AceiteIntervaloMeses INT NULL DEFAULT 6,
    ADD COLUMN FrenosIntervaloKm INT NULL DEFAULT 25000,
    ADD COLUMN FrenosIntervaloMeses INT NULL DEFAULT 12;
