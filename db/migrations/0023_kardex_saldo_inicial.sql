-- El Kardex de varios productos no explicaba su stock: se cargaron con stock pero sin un movimiento
-- de saldo inicial, y en un caso el stock se cambio sin movimiento. Esta migracion agrega:
--  1) un ajuste de regularizacion donde la cadena de saldos se corta (solo productos del taller);
--  2) el movimiento INICIAL que falta, fechado justo antes del primer movimiento del producto.
-- Es idempotente: no repite nada que ya exista. Requerido por: kardex.php

-- 1) Cortes en la cadena de saldos (productos del taller, ProductoID >= 8)
INSERT INTO kardex (ProductoID, FechaMovimiento, TipoTransaccion, CantidadEntrada, CantidadSalida, StockSaldo, ValorUnitario)
SELECT x.ProductoID, DATE_SUB(x.FechaMovimiento, INTERVAL 1 SECOND),
       IF((x.StockSaldo + x.CantidadSalida - x.CantidadEntrada) - x.prev > 0, 'AJUSTE_ENTRADA', 'AJUSTE_SALIDA'),
       GREATEST((x.StockSaldo + x.CantidadSalida - x.CantidadEntrada) - x.prev, 0),
       GREATEST(x.prev - (x.StockSaldo + x.CantidadSalida - x.CantidadEntrada), 0),
       x.StockSaldo + x.CantidadSalida - x.CantidadEntrada,
       p.CostoCompra
FROM (SELECT k.*, LAG(k.StockSaldo) OVER (PARTITION BY k.ProductoID ORDER BY k.FechaMovimiento, k.KardexID) AS prev FROM kardex k) x
JOIN productos p ON p.ProductoID = x.ProductoID
WHERE x.ProductoID >= 8 AND x.prev IS NOT NULL
  AND ABS(x.prev + x.CantidadEntrada - x.CantidadSalida - x.StockSaldo) > 0.0005;

-- 2a) Saldo inicial de los productos que ya tienen movimientos (lo que habia antes del primero)
INSERT INTO kardex (ProductoID, FechaMovimiento, TipoTransaccion, CantidadEntrada, CantidadSalida, StockSaldo, ValorUnitario)
SELECT p.ProductoID, DATE_SUB(f.FechaMovimiento, INTERVAL 1 SECOND), 'INICIAL',
       f.StockSaldo - f.CantidadEntrada + f.CantidadSalida, 0,
       f.StockSaldo - f.CantidadEntrada + f.CantidadSalida, p.CostoCompra
FROM productos p
JOIN kardex f ON f.KardexID = (SELECT k.KardexID FROM kardex k WHERE k.ProductoID = p.ProductoID ORDER BY k.FechaMovimiento, k.KardexID LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM kardex i WHERE i.ProductoID = p.ProductoID AND i.TipoTransaccion = 'INICIAL')
  AND f.StockSaldo - f.CantidadEntrada + f.CantidadSalida > 0;

-- 2b) Saldo inicial de los productos con stock que nunca tuvieron movimientos
INSERT INTO kardex (ProductoID, FechaMovimiento, TipoTransaccion, CantidadEntrada, CantidadSalida, StockSaldo, ValorUnitario)
SELECT p.ProductoID, NOW(), 'INICIAL', p.Stock, 0, p.Stock, p.CostoCompra
FROM productos p
WHERE p.Stock > 0 AND NOT EXISTS (SELECT 1 FROM kardex k WHERE k.ProductoID = p.ProductoID);
