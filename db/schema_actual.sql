
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `abonoscredito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abonoscredito` (
  `AbonoID` int NOT NULL AUTO_INCREMENT,
  `ClienteID` int NOT NULL,
  `FechaAbono` datetime NOT NULL,
  `Monto` int NOT NULL,
  `MetodoPago` varchar(50) NOT NULL,
  `TurnoID` int DEFAULT NULL,
  `VentaID` int DEFAULT NULL,
  PRIMARY KEY (`AbonoID`),
  KEY `ClienteID` (`ClienteID`),
  KEY `FK_Abonos_Ventas` (`VentaID`),
  CONSTRAINT `abonoscredito_ibfk_1` FOREIGN KEY (`ClienteID`) REFERENCES `clientes` (`ClienteID`),
  CONSTRAINT `FK_Abonos_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ajustesstock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ajustesstock` (
  `AjusteStockID` int NOT NULL AUTO_INCREMENT,
  `UsuarioID` int NOT NULL,
  `FechaAjuste` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Motivo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Observaciones` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `ProveedorID` int DEFAULT NULL,
  `DocReferencia` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`AjusteStockID`),
  KEY `FK_AjustesStock_Usuarios` (`UsuarioID`),
  KEY `FK_AjustesStock_Proveedores` (`ProveedorID`),
  CONSTRAINT `FK_AjustesStock_Proveedores` FOREIGN KEY (`ProveedorID`) REFERENCES `proveedores` (`ProveedorID`),
  CONSTRAINT `FK_AjustesStock_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auditoriaeventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoriaeventos` (
  `AuditoriaID` int NOT NULL AUTO_INCREMENT,
  `Fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UsuarioID` int NOT NULL,
  `TipoEvento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `SupervisorID` int DEFAULT NULL,
  `ReferenciaID` int DEFAULT NULL,
  PRIMARY KEY (`AuditoriaID`),
  KEY `FK_AuditoriaEventos_Usuarios` (`UsuarioID`),
  KEY `FK_AuditoriaEventos_Supervisor` (`SupervisorID`),
  CONSTRAINT `FK_AuditoriaEventos_Supervisor` FOREIGN KEY (`SupervisorID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `FK_AuditoriaEventos_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cajas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cajas` (
  `CajaID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Activa` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`CajaID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `CategoriaID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`CategoriaID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `checklistitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklistitems` (
  `ChecklistItemID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Categoria` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Accesorio',
  `Orden` int NOT NULL DEFAULT '0',
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ChecklistItemID`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `checklistrecepcion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklistrecepcion` (
  `ChecklistRecepcionID` int NOT NULL AUTO_INCREMENT,
  `OrdenTrabajoID` int NOT NULL,
  `ChecklistItemID` int NOT NULL,
  `Valor` enum('Si','No') COLLATE utf8mb4_unicode_ci NOT NULL,
  `Detalle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ChecklistRecepcionID`),
  KEY `FK_Checklist_OT` (`OrdenTrabajoID`),
  KEY `FK_Checklist_Item` (`ChecklistItemID`),
  CONSTRAINT `FK_Checklist_Item` FOREIGN KEY (`ChecklistItemID`) REFERENCES `checklistitems` (`ChecklistItemID`),
  CONSTRAINT `FK_Checklist_OT` FOREIGN KEY (`OrdenTrabajoID`) REFERENCES `ordenestrabajo` (`OrdenTrabajoID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `ClienteID` int NOT NULL AUTO_INCREMENT,
  `RutCuerpo` int DEFAULT NULL,
  `RutDv` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Cliente Gen├®rico',
  `Telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `PuntosAcumulados` int NOT NULL DEFAULT '0',
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `LimiteCredito` int NOT NULL DEFAULT '0',
  `SaldoDeudor` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ClienteID`),
  UNIQUE KEY `RutCuerpo` (`RutCuerpo`),
  CONSTRAINT `CK_Clientes_Puntos` CHECK ((`PuntosAcumulados` >= 0)),
  CONSTRAINT `CK_Clientes_RutDv` CHECK ((`RutDv` in (_utf8mb4'0',_utf8mb4'1',_utf8mb4'2',_utf8mb4'3',_utf8mb4'4',_utf8mb4'5',_utf8mb4'6',_utf8mb4'7',_utf8mb4'8',_utf8mb4'9',_utf8mb4'K',_utf8mb4'k')))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compatibilidadrepuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compatibilidadrepuestos` (
  `CompatibilidadID` int NOT NULL AUTO_INCREMENT,
  `ProductoID` int NOT NULL,
  `MarcaVehiculo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ModeloVehiculo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `AnioDesde` int DEFAULT NULL,
  `AnioHasta` int DEFAULT NULL,
  `Motor` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Notas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`CompatibilidadID`),
  KEY `FK_CR_Producto` (`ProductoID`),
  KEY `idx_vehiculo_marca_modelo` (`MarcaVehiculo`,`ModeloVehiculo`),
  CONSTRAINT `FK_CR_Producto` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras` (
  `CompraID` int NOT NULL AUTO_INCREMENT,
  `ProveedorID` int NOT NULL,
  `NotaPedidoID` int DEFAULT NULL,
  `UsuarioID` int NOT NULL,
  `FechaCompra` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `NumeroDocumento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `MontoNeto` int NOT NULL DEFAULT '0',
  `MontoIva` int NOT NULL DEFAULT '0',
  `MontoExento` int NOT NULL DEFAULT '0',
  `MontoTotal` int NOT NULL DEFAULT '0',
  `Estado` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Completada',
  PRIMARY KEY (`CompraID`),
  KEY `FK_Compras_Proveedores` (`ProveedorID`),
  KEY `FK_Compras_Usuarios` (`UsuarioID`),
  KEY `FK_Compras_NotaPedido` (`NotaPedidoID`),
  CONSTRAINT `FK_Compras_NotaPedido` FOREIGN KEY (`NotaPedidoID`) REFERENCES `notaspedido` (`NotaPedidoID`),
  CONSTRAINT `FK_Compras_Proveedores` FOREIGN KEY (`ProveedorID`) REFERENCES `proveedores` (`ProveedorID`),
  CONSTRAINT `FK_Compras_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_Compras_Estado` CHECK ((`Estado` in (_utf8mb4'Completada',_utf8mb4'Anulada'))),
  CONSTRAINT `CK_Compras_Exento` CHECK ((`MontoExento` >= 0)),
  CONSTRAINT `CK_Compras_Iva` CHECK ((`MontoIva` >= 0)),
  CONSTRAINT `CK_Compras_Neto` CHECK ((`MontoNeto` >= 0)),
  CONSTRAINT `CK_Compras_Total` CHECK ((`MontoTotal` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuraciones` (
  `Clave` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Valor` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`Clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cotizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cotizaciones` (
  `CotizacionID` int NOT NULL AUTO_INCREMENT,
  `ClienteID` int DEFAULT NULL,
  `FechaCotizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Total` int NOT NULL DEFAULT '0',
  `UsuarioID` int NOT NULL,
  `Estado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`CotizacionID`),
  KEY `FK_Cotizaciones_Clientes` (`ClienteID`),
  KEY `FK_Cotizaciones_Usuarios` (`UsuarioID`),
  CONSTRAINT `FK_Cotizaciones_Clientes` FOREIGN KEY (`ClienteID`) REFERENCES `clientes` (`ClienteID`),
  CONSTRAINT `FK_Cotizaciones_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cotizacionesdetalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cotizacionesdetalle` (
  `CotizacionDetalleID` int NOT NULL AUTO_INCREMENT,
  `CotizacionID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `PrecioUnitario` int NOT NULL,
  `Descuento` int NOT NULL DEFAULT '0',
  `Subtotal` int NOT NULL,
  PRIMARY KEY (`CotizacionDetalleID`),
  KEY `FK_CotizDet_Cotizaciones` (`CotizacionID`),
  KEY `FK_CotizDet_Productos` (`ProductoID`),
  CONSTRAINT `FK_CotizDet_Cotizaciones` FOREIGN KEY (`CotizacionID`) REFERENCES `cotizaciones` (`CotizacionID`) ON DELETE CASCADE,
  CONSTRAINT `FK_CotizDet_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_CotizDet_Cant` CHECK ((`Cantidad` > 0)),
  CONSTRAINT `CK_CotizDet_Desc` CHECK ((`Descuento` >= 0)),
  CONSTRAINT `CK_CotizDet_Precio` CHECK ((`PrecioUnitario` >= 0)),
  CONSTRAINT `CK_CotizDet_Sub` CHECK ((`Subtotal` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalleajustesstock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalleajustesstock` (
  `DetalleAjusteStockID` int NOT NULL AUTO_INCREMENT,
  `AjusteStockID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `TipoMovimiento` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`DetalleAjusteStockID`),
  KEY `FK_DetalleAjustes_Ajustes` (`AjusteStockID`),
  KEY `FK_DetalleAjustes_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleAjustes_Ajustes` FOREIGN KEY (`AjusteStockID`) REFERENCES `ajustesstock` (`AjusteStockID`),
  CONSTRAINT `FK_DetalleAjustes_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_DetalleAjustes_Tipo` CHECK ((`TipoMovimiento` in (_cp850'ENTRADA',_cp850'SALIDA')))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detallecompras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detallecompras` (
  `DetalleCompraID` int NOT NULL AUTO_INCREMENT,
  `CompraID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `CostoUnitario` int NOT NULL,
  `Subtotal` int NOT NULL,
  PRIMARY KEY (`DetalleCompraID`),
  KEY `FK_DetalleCompras_Compras` (`CompraID`),
  KEY `FK_DetalleCompras_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleCompras_Compras` FOREIGN KEY (`CompraID`) REFERENCES `compras` (`CompraID`),
  CONSTRAINT `FK_DetalleCompras_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_DetalleCompras_Cant` CHECK ((`Cantidad` > 0)),
  CONSTRAINT `CK_DetalleCompras_Costo` CHECK ((`CostoUnitario` >= 0)),
  CONSTRAINT `CK_DetalleCompras_Subtotal` CHECK ((`Subtotal` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalledevoluciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalledevoluciones` (
  `DetalleDevolucionID` int NOT NULL AUTO_INCREMENT,
  `DevolucionID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `MontoDevuelto` int NOT NULL,
  PRIMARY KEY (`DetalleDevolucionID`),
  KEY `FK_DetalleDevoluciones_Devoluciones` (`DevolucionID`),
  KEY `FK_DetalleDevoluciones_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleDevoluciones_Devoluciones` FOREIGN KEY (`DevolucionID`) REFERENCES `devoluciones` (`DevolucionID`),
  CONSTRAINT `FK_DetalleDevoluciones_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_DetalleDevoluciones_Cant` CHECK ((`Cantidad` > 0)),
  CONSTRAINT `CK_DetalleDevoluciones_Monto` CHECK ((`MontoDevuelto` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detallenotaspedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detallenotaspedido` (
  `DetalleNotaPedidoID` int NOT NULL AUTO_INCREMENT,
  `NotaPedidoID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `CantidadPedida` decimal(10,3) NOT NULL,
  `CostoAcordado` int NOT NULL,
  PRIMARY KEY (`DetalleNotaPedidoID`),
  KEY `FK_DetalleNotasPedido_NotasPedido` (`NotaPedidoID`),
  KEY `FK_DetalleNotasPedido_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleNotasPedido_NotasPedido` FOREIGN KEY (`NotaPedidoID`) REFERENCES `notaspedido` (`NotaPedidoID`),
  CONSTRAINT `FK_DetalleNotasPedido_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_DetalleNotasPedido_Cant` CHECK ((`CantidadPedida` > 0)),
  CONSTRAINT `CK_DetalleNotasPedido_Costo` CHECK ((`CostoAcordado` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalleventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalleventas` (
  `DetalleVentaID` int NOT NULL AUTO_INCREMENT,
  `VentaID` int NOT NULL,
  `ProductoID` int DEFAULT NULL,
  `NombreItem` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `FactorConversion` decimal(10,3) NOT NULL DEFAULT '1.000',
  `PrecioUnitario` int NOT NULL,
  `CostoUnitario` int NOT NULL DEFAULT '0',
  `Descuento` int NOT NULL DEFAULT '0',
  `EsAfecto` tinyint(1) NOT NULL,
  `Subtotal` int NOT NULL,
  PRIMARY KEY (`DetalleVentaID`),
  KEY `FK_DetalleVentas_Ventas` (`VentaID`),
  KEY `FK_DetalleVentas_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleVentas_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleVentas_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`),
  CONSTRAINT `CK_DetalleVentas_Cant` CHECK ((`Cantidad` > 0)),
  CONSTRAINT `CK_DetalleVentas_Desc` CHECK ((`Descuento` >= 0)),
  CONSTRAINT `CK_DetalleVentas_Precio` CHECK ((`PrecioUnitario` >= 0)),
  CONSTRAINT `CK_DetalleVentas_Subtotal` CHECK ((`Subtotal` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `devoluciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devoluciones` (
  `DevolucionID` int NOT NULL AUTO_INCREMENT,
  `VentaID` int NOT NULL,
  `UsuarioID` int NOT NULL,
  `FechaDevolucion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `MontoDevuelto` int NOT NULL,
  `MetodoDevolucion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Motivo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`DevolucionID`),
  KEY `FK_Devoluciones_Usuarios` (`UsuarioID`),
  KEY `IX_Devoluciones_Venta` (`VentaID`),
  CONSTRAINT `FK_Devoluciones_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `FK_Devoluciones_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`),
  CONSTRAINT `CK_Devoluciones_Metodo` CHECK ((`MetodoDevolucion` in (_utf8mb4'Efectivo',_utf8mb4'Tarjeta',_utf8mb4'Nota de Credito',_utf8mb4'Cambio de Mercaderia'))),
  CONSTRAINT `CK_Devoluciones_Monto` CHECK ((`MontoDevuelto` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `diagnosticoot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `diagnosticoot` (
  `DiagnosticoID` int NOT NULL AUTO_INCREMENT,
  `OrdenTrabajoID` int NOT NULL,
  `Area` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Hallazgo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `UsuarioID` int NOT NULL,
  `Fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`DiagnosticoID`),
  KEY `FK_Diagnostico_OT` (`OrdenTrabajoID`),
  KEY `FK_Diagnostico_Usuario` (`UsuarioID`),
  CONSTRAINT `FK_Diagnostico_OT` FOREIGN KEY (`OrdenTrabajoID`) REFERENCES `ordenestrabajo` (`OrdenTrabajoID`) ON DELETE CASCADE,
  CONSTRAINT `FK_Diagnostico_Usuario` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dte_emitidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dte_emitidos` (
  `DteID` int NOT NULL AUTO_INCREMENT,
  `VentaID` int DEFAULT NULL,
  `DevolucionID` int DEFAULT NULL,
  `TipoDocumento` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Folio` int NOT NULL,
  `TrackID` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `EstadoSii` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT 'Pendiente',
  `Ambiente` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `RutReceptor` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `MontoTotal` int DEFAULT NULL,
  `Mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  `PdfUrl` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `XmlUrl` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `FechaEmision` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`DteID`),
  KEY `VentaID` (`VentaID`),
  KEY `DevolucionID` (`DevolucionID`),
  CONSTRAINT `dte_emitidos_ibfk_1` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`) ON DELETE SET NULL,
  CONSTRAINT `dte_emitidos_ibfk_2` FOREIGN KEY (`DevolucionID`) REFERENCES `devoluciones` (`DevolucionID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `estacionservicio_ot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estacionservicio_ot` (
  `EstacionID` int NOT NULL AUTO_INCREMENT,
  `OrdenTrabajoID` int NOT NULL,
  `MotorNivel` enum('Normal','Bajo','No revisado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `MotorCambio` tinyint(1) NOT NULL DEFAULT '0',
  `FiltroCambio` tinyint(1) NOT NULL DEFAULT '0',
  `DHNivel` enum('Normal','Bajo','No aplica') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `CajaNivel` enum('Normal','Bajo','No revisado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `CajaCambio` tinyint(1) NOT NULL DEFAULT '0',
  `FrenosNivel` enum('Normal','Bajo','Contaminado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `FrenosCambio` tinyint(1) NOT NULL DEFAULT '0',
  `RadiadorNivel` enum('Normal','Bajo','No revisado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `RadiadorAnticongelante` tinyint(1) NOT NULL DEFAULT '0',
  `LavVidrioCarga` tinyint(1) NOT NULL DEFAULT '0',
  `LavadoCarroceria` enum('No','Basico','Completo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `Observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `AceiteIntervaloKm` int DEFAULT '10000',
  `AceiteIntervaloMeses` int DEFAULT '6',
  `FrenosIntervaloKm` int DEFAULT '25000',
  `FrenosIntervaloMeses` int DEFAULT '12',
  PRIMARY KEY (`EstacionID`),
  UNIQUE KEY `UQ_Estacion_OT` (`OrdenTrabajoID`),
  CONSTRAINT `FK_Estacion_OT` FOREIGN KEY (`OrdenTrabajoID`) REFERENCES `ordenestrabajo` (`OrdenTrabajoID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historialmantenimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historialmantenimiento` (
  `MantenimientoID` int NOT NULL AUTO_INCREMENT,
  `VehiculoID` int NOT NULL,
  `OrdenTrabajoID` int DEFAULT NULL,
  `TipoMantenimiento` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `KilometrajeRealizado` int NOT NULL,
  `FechaRealizado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `KilometrajeProximo` int DEFAULT NULL,
  `FechaProxima` date DEFAULT NULL,
  `Estado` enum('Vigente','Proximo','Vencido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Vigente',
  `Notas` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `UsuarioID` int DEFAULT NULL,
  PRIMARY KEY (`MantenimientoID`),
  KEY `FK_HM_OT` (`OrdenTrabajoID`),
  KEY `idx_hm_vehiculo` (`VehiculoID`),
  KEY `idx_hm_fecha` (`FechaRealizado`),
  CONSTRAINT `FK_HM_OT` FOREIGN KEY (`OrdenTrabajoID`) REFERENCES `ordenestrabajo` (`OrdenTrabajoID`) ON DELETE SET NULL,
  CONSTRAINT `FK_HM_Vehiculo` FOREIGN KEY (`VehiculoID`) REFERENCES `vehiculos` (`VehiculoID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historialprecios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historialprecios` (
  `HistorialPrecioID` int NOT NULL AUTO_INCREMENT,
  `ProductoID` int NOT NULL,
  `FechaCambio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `PrecioVentaAnterior` int NOT NULL,
  `PrecioVentaNuevo` int NOT NULL,
  `CostoCompraAnterior` int DEFAULT NULL,
  `CostoCompraNuevo` int DEFAULT NULL,
  `UsuarioID` int NOT NULL,
  PRIMARY KEY (`HistorialPrecioID`),
  KEY `FK_HistorialPrecios_Productos` (`ProductoID`),
  KEY `FK_HistorialPrecios_Usuarios` (`UsuarioID`),
  CONSTRAINT `FK_HistorialPrecios_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `FK_HistorialPrecios_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_HistPrecios_CCant` CHECK ((`CostoCompraAnterior` >= 0)),
  CONSTRAINT `CK_HistPrecios_CCnue` CHECK ((`CostoCompraNuevo` >= 0)),
  CONSTRAINT `CK_HistPrecios_PVant` CHECK ((`PrecioVentaAnterior` >= 0)),
  CONSTRAINT `CK_HistPrecios_PVnue` CHECK ((`PrecioVentaNuevo` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventariodetalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventariodetalles` (
  `InventarioDetalleID` int NOT NULL AUTO_INCREMENT,
  `InventarioID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `CantidadFisica` decimal(10,3) NOT NULL,
  `StockSistemaAlMomento` decimal(10,3) DEFAULT NULL,
  `Diferencia` decimal(10,3) DEFAULT NULL,
  PRIMARY KEY (`InventarioDetalleID`),
  KEY `FK_InvDetalles_Inventarios` (`InventarioID`),
  KEY `FK_InvDetalles_Productos` (`ProductoID`),
  CONSTRAINT `FK_InvDetalles_Inventarios` FOREIGN KEY (`InventarioID`) REFERENCES `inventarios` (`InventarioID`) ON DELETE CASCADE,
  CONSTRAINT `FK_InvDetalles_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventarios` (
  `InventarioID` int NOT NULL AUTO_INCREMENT,
  `UsuarioID` int NOT NULL,
  `Nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `FechaCreacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FechaHoraInventario` datetime NOT NULL,
  `Estado` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Borrador',
  `FechaProcesamiento` datetime DEFAULT NULL,
  PRIMARY KEY (`InventarioID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kardex`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kardex` (
  `KardexID` int NOT NULL AUTO_INCREMENT,
  `ProductoID` int NOT NULL,
  `FechaMovimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TipoTransaccion` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `VentaID` int DEFAULT NULL,
  `CompraID` int DEFAULT NULL,
  `AjusteStockID` int DEFAULT NULL,
  `DevolucionID` int DEFAULT NULL,
  `CantidadEntrada` decimal(10,3) NOT NULL DEFAULT '0.000',
  `CantidadSalida` decimal(10,3) NOT NULL DEFAULT '0.000',
  `StockSaldo` decimal(10,3) NOT NULL,
  `ValorUnitario` int NOT NULL DEFAULT '0',
  `CostoMedioPonderado` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`KardexID`),
  KEY `FK_Kardex_Ventas` (`VentaID`),
  KEY `FK_Kardex_Compras` (`CompraID`),
  KEY `FK_Kardex_Ajustes` (`AjusteStockID`),
  KEY `FK_Kardex_Devoluciones` (`DevolucionID`),
  KEY `IX_Kardex_Producto` (`ProductoID`),
  CONSTRAINT `FK_Kardex_Ajustes` FOREIGN KEY (`AjusteStockID`) REFERENCES `ajustesstock` (`AjusteStockID`),
  CONSTRAINT `FK_Kardex_Compras` FOREIGN KEY (`CompraID`) REFERENCES `compras` (`CompraID`),
  CONSTRAINT `FK_Kardex_Devoluciones` FOREIGN KEY (`DevolucionID`) REFERENCES `devoluciones` (`DevolucionID`),
  CONSTRAINT `FK_Kardex_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `FK_Kardex_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`),
  CONSTRAINT `CK_Kardex_Entrada` CHECK ((`CantidadEntrada` >= 0)),
  CONSTRAINT `CK_Kardex_Pmp` CHECK ((`CostoMedioPonderado` >= 0)),
  CONSTRAINT `CK_Kardex_Saldo` CHECK ((`StockSaldo` >= 0)),
  CONSTRAINT `CK_Kardex_Salida` CHECK ((`CantidadSalida` >= 0)),
  CONSTRAINT `CK_Kardex_Tipo` CHECK ((`TipoTransaccion` in (_utf8mb4'INICIAL',_utf8mb4'VENTA',_utf8mb4'COMPRA',_utf8mb4'AJUSTE_ENTRADA',_utf8mb4'AJUSTE_SALIDA',_utf8mb4'ANULACION_VENTA',_utf8mb4'DEVOLUCION_VENTA'))),
  CONSTRAINT `CK_Kardex_ValUnit` CHECK ((`ValorUnitario` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=187 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientoscaja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientoscaja` (
  `MovimientoCajaID` int NOT NULL AUTO_INCREMENT,
  `TurnoID` int NOT NULL,
  `TipoMovimiento` varchar(15) NOT NULL,
  `Monto` int NOT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `FechaMovimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`MovimientoCajaID`),
  KEY `TurnoID` (`TurnoID`),
  CONSTRAINT `movimientoscaja_ibfk_1` FOREIGN KEY (`TurnoID`) REFERENCES `turnos` (`TurnoID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notaspedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notaspedido` (
  `NotaPedidoID` int NOT NULL AUTO_INCREMENT,
  `ProveedorID` int NOT NULL,
  `UsuarioID` int NOT NULL,
  `FechaPedido` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `NumeroDocumento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Estado` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Pendiente',
  `NotaPedidoOrigenID` int DEFAULT NULL,
  `Observaciones` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`NotaPedidoID`),
  KEY `FK_NotasPedido_Proveedores` (`ProveedorID`),
  KEY `FK_NotasPedido_Usuarios` (`UsuarioID`),
  KEY `FK_NotasPedido_Origen` (`NotaPedidoOrigenID`),
  CONSTRAINT `FK_NotasPedido_Origen` FOREIGN KEY (`NotaPedidoOrigenID`) REFERENCES `notaspedido` (`NotaPedidoID`),
  CONSTRAINT `FK_NotasPedido_Proveedores` FOREIGN KEY (`ProveedorID`) REFERENCES `proveedores` (`ProveedorID`),
  CONSTRAINT `FK_NotasPedido_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_NotasPedido_Estado` CHECK ((`Estado` in (_utf8mb4'Pendiente',_utf8mb4'Recibida',_utf8mb4'Cancelada')))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operacionessolicitadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operacionessolicitadas` (
  `OperacionID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Categoria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mantenimiento',
  `Orden` int NOT NULL DEFAULT '0',
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`OperacionID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orden_operaciones_solicitadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orden_operaciones_solicitadas` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OrdenTrabajoID` int NOT NULL,
  `OperacionID` int DEFAULT NULL,
  `NombreOperacion` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `FK_OOS_OT` (`OrdenTrabajoID`),
  KEY `FK_OOS_Operacion` (`OperacionID`),
  CONSTRAINT `FK_OOS_Operacion` FOREIGN KEY (`OperacionID`) REFERENCES `operacionessolicitadas` (`OperacionID`) ON DELETE SET NULL,
  CONSTRAINT `FK_OOS_OT` FOREIGN KEY (`OrdenTrabajoID`) REFERENCES `ordenestrabajo` (`OrdenTrabajoID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ordenestrabajo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordenestrabajo` (
  `OrdenTrabajoID` int NOT NULL AUTO_INCREMENT,
  `VehiculoID` int NOT NULL,
  `ClienteID` int NOT NULL,
  `UsuarioID` int NOT NULL COMMENT 'Quien recibio el vehiculo',
  `FechaIngreso` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `KilometrajeIngreso` int DEFAULT NULL,
  `NivelCombustible` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1/2',
  `ObjetosValor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DaniosCarroceriaJson` longtext COLLATE utf8mb4_unicode_ci,
  `OperacionesTextoLibre` text COLLATE utf8mb4_unicode_ci,
  `AutorizaPresupuestoPrevio` tinyint(1) NOT NULL DEFAULT '1',
  `AutorizaPruebaManejo` tinyint(1) NOT NULL DEFAULT '0',
  `FirmaClienteBase64` longtext COLLATE utf8mb4_unicode_ci,
  `Estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ingresado',
  `FechaEntrega` datetime DEFAULT NULL,
  `MecanicoID` int DEFAULT NULL,
  `VentaID` int DEFAULT NULL,
  `ManoObraCobrada` tinyint(1) NOT NULL DEFAULT '0',
  `MontoManoObraCobrado` int DEFAULT NULL,
  PRIMARY KEY (`OrdenTrabajoID`),
  KEY `FK_OT_Vehiculos` (`VehiculoID`),
  KEY `FK_OT_Clientes` (`ClienteID`),
  KEY `FK_OT_Usuarios` (`UsuarioID`),
  KEY `FK_OT_Mecanico` (`MecanicoID`),
  KEY `FK_OT_Venta` (`VentaID`),
  CONSTRAINT `FK_OT_Clientes` FOREIGN KEY (`ClienteID`) REFERENCES `clientes` (`ClienteID`),
  CONSTRAINT `FK_OT_Mecanico` FOREIGN KEY (`MecanicoID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `FK_OT_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `FK_OT_Vehiculos` FOREIGN KEY (`VehiculoID`) REFERENCES `vehiculos` (`VehiculoID`),
  CONSTRAINT `FK_OT_Venta` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pagosventa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagosventa` (
  `PagoVentaID` int NOT NULL AUTO_INCREMENT,
  `VentaID` int NOT NULL,
  `MetodoPago` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Monto` int NOT NULL,
  PRIMARY KEY (`PagoVentaID`),
  KEY `IX_PagosVenta_Venta` (`VentaID`),
  CONSTRAINT `FK_PagosVenta_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`),
  CONSTRAINT `CK_PagosVenta_Metodo` CHECK ((`MetodoPago` in (_utf8mb4'Efectivo',_utf8mb4'Tarjeta Debito',_utf8mb4'Tarjeta Credito',_utf8mb4'Transferencia',_utf8mb4'Puntos',_utf8mb4'Credito Interno',_utf8mb4'Vale Devolucion'))),
  CONSTRAINT `CK_PagosVenta_Monto` CHECK ((`Monto` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permisos` (
  `PermisoID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`PermisoID`),
  UNIQUE KEY `Nombre` (`Nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `presupuestodetalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presupuestodetalle` (
  `PresupuestoDetalleID` int NOT NULL AUTO_INCREMENT,
  `PresupuestoID` int NOT NULL,
  `TipoLinea` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ProductoID` int DEFAULT NULL,
  `Descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Cantidad` decimal(10,3) NOT NULL DEFAULT '1.000',
  `PrecioUnitario` int NOT NULL,
  `Subtotal` int NOT NULL,
  `Aprobado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`PresupuestoDetalleID`),
  KEY `FK_PresupuestoDetalle_Presupuesto` (`PresupuestoID`),
  KEY `FK_PresupuestoDetalle_Producto` (`ProductoID`),
  CONSTRAINT `FK_PresupuestoDetalle_Presupuesto` FOREIGN KEY (`PresupuestoID`) REFERENCES `presupuestos` (`PresupuestoID`) ON DELETE CASCADE,
  CONSTRAINT `FK_PresupuestoDetalle_Producto` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `presupuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presupuestos` (
  `PresupuestoID` int NOT NULL AUTO_INCREMENT,
  `OrdenTrabajoID` int NOT NULL,
  `UsuarioID` int NOT NULL,
  `FechaCreacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TiempoEntrega` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DecisionCliente` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `FechaDecision` datetime DEFAULT NULL,
  PRIMARY KEY (`PresupuestoID`),
  KEY `FK_Presupuesto_OT` (`OrdenTrabajoID`),
  KEY `FK_Presupuesto_Usuario` (`UsuarioID`),
  CONSTRAINT `FK_Presupuesto_OT` FOREIGN KEY (`OrdenTrabajoID`) REFERENCES `ordenestrabajo` (`OrdenTrabajoID`),
  CONSTRAINT `FK_Presupuesto_Usuario` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `ProductoID` int NOT NULL AUTO_INCREMENT,
  `CodigoBarras` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `NumeroParteOEM` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `NumeroParteAlternativo` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `MarcaRepuesto` varchar(80) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `ViscosidadAceite` varchar(30) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `TipoRepuesto` enum('Aceite','FiltroAceite','FiltroAire','FiltroCombustible','FiltroCabina','Frenos','Bujias','Distribucion','General') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'General',
  `CategoriaID` int NOT NULL,
  `UnidadMedida` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'UNIDAD',
  `Stock` decimal(10,3) NOT NULL DEFAULT '0.000',
  `StockMinimo` decimal(10,3) NOT NULL DEFAULT '0.000',
  `PrecioVenta` int NOT NULL,
  `CostoCompra` int NOT NULL DEFAULT '0',
  `EsAfecto` tinyint(1) NOT NULL DEFAULT '1',
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `EsPesable` tinyint(1) DEFAULT '0',
  `CodigoPLU` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`ProductoID`),
  UNIQUE KEY `CodigoBarras` (`CodigoBarras`),
  UNIQUE KEY `CodigoPLU` (`CodigoPLU`),
  KEY `FK_Productos_Categorias` (`CategoriaID`),
  CONSTRAINT `FK_Productos_Categorias` FOREIGN KEY (`CategoriaID`) REFERENCES `categorias` (`CategoriaID`),
  CONSTRAINT `CK_Productos_CostoCompra` CHECK ((`CostoCompra` >= 0)),
  CONSTRAINT `CK_Productos_PrecioVenta` CHECK ((`PrecioVenta` >= 0)),
  CONSTRAINT `CK_Productos_Stock` CHECK ((`Stock` >= 0)),
  CONSTRAINT `CK_Productos_StockMin` CHECK ((`StockMinimo` >= 0)),
  CONSTRAINT `CK_Productos_Unidad` CHECK ((`UnidadMedida` in (_utf8mb4'UNIDAD',_utf8mb4'KG',_utf8mb4'LITRO')))
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `productoscodigos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productoscodigos` (
  `CodigoID` int NOT NULL AUTO_INCREMENT,
  `ProductoID` int NOT NULL,
  `CodigoBarras` varchar(50) NOT NULL,
  `Descripcion` varchar(100) DEFAULT NULL,
  `Cantidad` decimal(10,3) NOT NULL DEFAULT '1.000',
  `PrecioVenta` int DEFAULT NULL,
  `CreadoEn` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CodigoID`),
  UNIQUE KEY `uq_codigo_barras_adicional` (`CodigoBarras`),
  KEY `fk_prodcodigos_producto` (`ProductoID`),
  CONSTRAINT `fk_prodcodigos_producto` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promociones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promociones` (
  `PromocionID` int NOT NULL AUTO_INCREMENT,
  `ProductoID` int NOT NULL,
  `Tipo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `CantidadMinima` decimal(10,3) NOT NULL DEFAULT '1.000',
  `DescuentoPorcentaje` decimal(5,2) NOT NULL DEFAULT '0.00',
  `PrecioOferta` int NOT NULL DEFAULT '0',
  `Activa` tinyint(1) NOT NULL DEFAULT '1',
  `FechaInicio` datetime NOT NULL,
  `FechaFin` datetime NOT NULL,
  PRIMARY KEY (`PromocionID`),
  KEY `FK_Promociones_Productos` (`ProductoID`),
  CONSTRAINT `FK_Promociones_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `ProveedorID` int NOT NULL AUTO_INCREMENT,
  `RutCuerpo` int NOT NULL,
  `RutDv` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `RazonSocial` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Giro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Direccion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ProveedorID`),
  UNIQUE KEY `RutCuerpo` (`RutCuerpo`),
  KEY `IX_Proveedores_Rut` (`RutCuerpo`),
  CONSTRAINT `CK_Proveedores_RutDv` CHECK ((`RutDv` in (_utf8mb4'0',_utf8mb4'1',_utf8mb4'2',_utf8mb4'3',_utf8mb4'4',_utf8mb4'5',_utf8mb4'6',_utf8mb4'7',_utf8mb4'8',_utf8mb4'9',_utf8mb4'K',_utf8mb4'k')))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reportesz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reportesz` (
  `ReporteZID` int NOT NULL AUTO_INCREMENT,
  `CajaID` int NOT NULL,
  `NumeroZ` int NOT NULL,
  `UsuarioID` int NOT NULL,
  `FechaEmision` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FechaInicio` datetime NOT NULL,
  `MontoNeto` int NOT NULL DEFAULT '0',
  `MontoIva` int NOT NULL DEFAULT '0',
  `MontoExento` int NOT NULL DEFAULT '0',
  `MontoTotal` int NOT NULL DEFAULT '0',
  `CantidadBoletas` int NOT NULL DEFAULT '0',
  `CantidadFacturas` int NOT NULL DEFAULT '0',
  `PrimerFolioBoleta` int DEFAULT NULL,
  `UltimoFolioBoleta` int DEFAULT NULL,
  `PrimerFolioFactura` int DEFAULT NULL,
  `UltimoFolioFactura` int DEFAULT NULL,
  PRIMARY KEY (`ReporteZID`),
  UNIQUE KEY `UQ_ReportesZ_Caja_Numero` (`CajaID`,`NumeroZ`),
  KEY `FK_ReportesZ_Usuarios` (`UsuarioID`),
  CONSTRAINT `FK_ReportesZ_Cajas` FOREIGN KEY (`CajaID`) REFERENCES `cajas` (`CajaID`),
  CONSTRAINT `FK_ReportesZ_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_ReportesZ_Boletas` CHECK ((`CantidadBoletas` >= 0)),
  CONSTRAINT `CK_ReportesZ_Facturas` CHECK ((`CantidadFacturas` >= 0)),
  CONSTRAINT `CK_ReportesZ_MontoExento` CHECK ((`MontoExento` >= 0)),
  CONSTRAINT `CK_ReportesZ_MontoIva` CHECK ((`MontoIva` >= 0)),
  CONSTRAINT `CK_ReportesZ_MontoNeto` CHECK ((`MontoNeto` >= 0)),
  CONSTRAINT `CK_ReportesZ_MontoTotal` CHECK ((`MontoTotal` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `RolID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`RolID`),
  UNIQUE KEY `Nombre` (`Nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rolespermisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rolespermisos` (
  `RolID` int NOT NULL,
  `PermisoID` int NOT NULL,
  PRIMARY KEY (`RolID`,`PermisoID`),
  KEY `FK_RolesPermisos_Permisos` (`PermisoID`),
  CONSTRAINT `FK_RolesPermisos_Permisos` FOREIGN KEY (`PermisoID`) REFERENCES `permisos` (`PermisoID`) ON DELETE CASCADE,
  CONSTRAINT `FK_RolesPermisos_Roles` FOREIGN KEY (`RolID`) REFERENCES `roles` (`RolID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `terminalescaja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalescaja` (
  `TerminalID` int NOT NULL AUTO_INCREMENT,
  `NombreEquipo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `CajaID` int NOT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`TerminalID`),
  UNIQUE KEY `NombreEquipo` (`NombreEquipo`),
  KEY `FK_TerminalesCaja_Cajas` (`CajaID`),
  CONSTRAINT `FK_TerminalesCaja_Cajas` FOREIGN KEY (`CajaID`) REFERENCES `cajas` (`CajaID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turnos` (
  `TurnoID` int NOT NULL AUTO_INCREMENT,
  `CajaID` int NOT NULL,
  `UsuarioID` int NOT NULL,
  `FechaApertura` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `MontoApertura` int NOT NULL,
  `FechaCierre` datetime DEFAULT NULL,
  `MontoCierreEfectivo` int DEFAULT NULL,
  `MontoCierreTarjeta` int DEFAULT NULL,
  `MontoCierreTransferencia` int DEFAULT NULL,
  `MontoCierreSistema` int DEFAULT NULL,
  `Estado` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Abierto',
  `Observaciones` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`TurnoID`),
  KEY `FK_Turnos_Cajas` (`CajaID`),
  KEY `FK_Turnos_Usuarios` (`UsuarioID`),
  CONSTRAINT `FK_Turnos_Cajas` FOREIGN KEY (`CajaID`) REFERENCES `cajas` (`CajaID`),
  CONSTRAINT `FK_Turnos_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_Turnos_Estado` CHECK ((`Estado` in (_utf8mb4'Abierto',_utf8mb4'Pendiente',_utf8mb4'Cerrado'))),
  CONSTRAINT `CK_Turnos_MontoApertura` CHECK ((`MontoApertura` >= 0)),
  CONSTRAINT `CK_Turnos_MontoCierreEfectivo` CHECK ((`MontoCierreEfectivo` >= 0)),
  CONSTRAINT `CK_Turnos_MontoCierreSistema` CHECK ((`MontoCierreSistema` >= 0)),
  CONSTRAINT `CK_Turnos_MontoCierreTarjeta` CHECK ((`MontoCierreTarjeta` >= 0)),
  CONSTRAINT `CK_Turnos_MontoCierreTransferencia` CHECK ((`MontoCierreTransferencia` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `UsuarioID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `RutCuerpo` int NOT NULL,
  `RutDv` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `NombreUsuario` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `PasswordHash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `RolID` int NOT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`UsuarioID`),
  UNIQUE KEY `NombreUsuario` (`NombreUsuario`),
  KEY `FK_Usuarios_Roles` (`RolID`),
  CONSTRAINT `FK_Usuarios_Roles` FOREIGN KEY (`RolID`) REFERENCES `roles` (`RolID`),
  CONSTRAINT `CK_Usuarios_RutDv` CHECK ((`RutDv` in (_cp850'0',_cp850'1',_cp850'2',_cp850'3',_cp850'4',_cp850'5',_cp850'6',_cp850'7',_cp850'8',_cp850'9',_cp850'K',_cp850'k')))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `valescanjes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `valescanjes` (
  `CanjeID` int NOT NULL AUTO_INCREMENT,
  `ValeID` int NOT NULL,
  `VentaID` int NOT NULL,
  `Monto` int NOT NULL,
  `FechaCanje` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CanjeID`),
  KEY `FK_Canjes_Vale` (`ValeID`),
  KEY `FK_Canjes_Venta` (`VentaID`),
  CONSTRAINT `FK_Canjes_Vale` FOREIGN KEY (`ValeID`) REFERENCES `valesdevolucion` (`ValeID`),
  CONSTRAINT `FK_Canjes_Venta` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `valesdevolucion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `valesdevolucion` (
  `ValeID` int NOT NULL AUTO_INCREMENT,
  `CodigoVale` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `MontoOriginal` int NOT NULL,
  `MontoDisponible` int NOT NULL,
  `FechaEmision` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Estado` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Activo',
  `VentaID` int DEFAULT NULL,
  PRIMARY KEY (`ValeID`),
  UNIQUE KEY `CodigoVale` (`CodigoVale`),
  KEY `FK_Vales_Ventas` (`VentaID`),
  CONSTRAINT `FK_Vales_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehiculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehiculos` (
  `VehiculoID` int NOT NULL AUTO_INCREMENT,
  `ClienteID` int NOT NULL,
  `Patente` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Marca` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Modelo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Anio` smallint DEFAULT NULL,
  `Color` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Combustible` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Motor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Transmision` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `TipoVehiculo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `VIN` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `KilometrajeUltimo` int DEFAULT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `FechaCreacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`VehiculoID`),
  UNIQUE KEY `UQ_Vehiculos_Patente` (`Patente`),
  KEY `FK_Vehiculos_Clientes` (`ClienteID`),
  CONSTRAINT `FK_Vehiculos_Clientes` FOREIGN KEY (`ClienteID`) REFERENCES `clientes` (`ClienteID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `VentaID` int NOT NULL AUTO_INCREMENT,
  `TurnoID` int NOT NULL,
  `ClienteID` int DEFAULT NULL,
  `ReporteZID` int DEFAULT NULL,
  `FechaVenta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TipoDocumento` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
  `TipoDte` int DEFAULT NULL,
  `Folio` int DEFAULT NULL,
  `MontoNeto` int NOT NULL DEFAULT '0',
  `MontoIva` int NOT NULL DEFAULT '0',
  `MontoExento` int NOT NULL DEFAULT '0',
  `DescuentoGlobal` int NOT NULL DEFAULT '0',
  `MontoTotal` int NOT NULL DEFAULT '0',
  `MontoPagado` int NOT NULL DEFAULT '0',
  `Vuelto` int NOT NULL DEFAULT '0',
  `PuntosGanados` int NOT NULL DEFAULT '0',
  `PuntosCanjeados` int NOT NULL DEFAULT '0',
  `Estado` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Completada',
  `DtePdfPath` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `DteToken` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`VentaID`),
  UNIQUE KEY `UX_Ventas_FolioDte` (`TipoDte`,`Folio`),
  KEY `FK_Ventas_Turnos` (`TurnoID`),
  KEY `FK_Ventas_Clientes` (`ClienteID`),
  KEY `FK_Ventas_ReportesZ` (`ReporteZID`),
  KEY `IX_Ventas_Fecha` (`FechaVenta`),
  CONSTRAINT `FK_Ventas_Clientes` FOREIGN KEY (`ClienteID`) REFERENCES `clientes` (`ClienteID`),
  CONSTRAINT `FK_Ventas_ReportesZ` FOREIGN KEY (`ReporteZID`) REFERENCES `reportesz` (`ReporteZID`),
  CONSTRAINT `FK_Ventas_Turnos` FOREIGN KEY (`TurnoID`) REFERENCES `turnos` (`TurnoID`),
  CONSTRAINT `CK_Ventas_DescGlobal` CHECK ((`DescuentoGlobal` >= 0)),
  CONSTRAINT `CK_Ventas_Estado` CHECK ((`Estado` in (_utf8mb4'Completada',_utf8mb4'Anulada'))),
  CONSTRAINT `CK_Ventas_MontoExento` CHECK ((`MontoExento` >= 0)),
  CONSTRAINT `CK_Ventas_MontoIva` CHECK ((`MontoIva` >= 0)),
  CONSTRAINT `CK_Ventas_MontoNeto` CHECK ((`MontoNeto` >= 0)),
  CONSTRAINT `CK_Ventas_MontoPagado` CHECK ((`MontoPagado` >= 0)),
  CONSTRAINT `CK_Ventas_MontoTotal` CHECK ((`MontoTotal` >= 0)),
  CONSTRAINT `CK_Ventas_PuntosCanjeados` CHECK ((`PuntosCanjeados` >= 0)),
  CONSTRAINT `CK_Ventas_PuntosGanados` CHECK ((`PuntosGanados` >= 0)),
  CONSTRAINT `CK_Ventas_TipoDoc` CHECK ((`TipoDocumento` in (_utf8mb4'Boleta',_utf8mb4'Factura',_utf8mb4'Sin Documento'))),
  CONSTRAINT `CK_Ventas_Vuelto` CHECK ((`Vuelto` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

