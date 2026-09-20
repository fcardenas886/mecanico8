-- Permite que una venta incluya lineas de "servicio" (mano de obra, terceros)
-- ademas de productos con stock. ProductoID pasa a ser opcional: NULL significa
-- servicio, y NombreItem (que ya existia como override opcional) se vuelve la
-- descripcion obligatoria de esa linea.
-- Requerido por: api/registrar_venta.php, pos.js

ALTER TABLE detalleventas MODIFY ProductoID INT NULL;
