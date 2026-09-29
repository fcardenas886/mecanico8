-- Migración 0025: Tabla de tokens cortos para acceso público seguro a documentos compartidos por WhatsApp
-- Permite que los clientes vean su Presupuesto, Comprobante de Custodia o Ficha de Entrega desde su teléfono
-- sin requerir usuario/contraseña y sin exponer datos de otros clientes por IDs secuenciales.

CREATE TABLE IF NOT EXISTS `tokens_documentos` (
  `TokenID` int NOT NULL AUTO_INCREMENT,
  `Token` varchar(16) NOT NULL,
  `TipoDocumento` varchar(30) NOT NULL COMMENT 'presupuesto, ingreso, entrega, venta',
  `ReferenciaID` int NOT NULL COMMENT 'OrdenTrabajoID o VentaID',
  `FechaCreacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UltimoAcceso` datetime DEFAULT NULL,
  PRIMARY KEY (`TokenID`),
  UNIQUE KEY `UQ_TokensDoc_Token` (`Token`),
  KEY `IX_TokensDoc_Ref` (`TipoDocumento`, `ReferenciaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
