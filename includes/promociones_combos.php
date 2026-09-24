<?php
// Motor de combos (ej. "1 Aceite + 1 Filtro de Aceite") razonado por TipoRepuesto,
// para no tener que crear una promoción por cada combinación de marcas posible.
//
// Reglas (acordadas con el usuario):
// - El backend es la autoridad: esta función se corre siempre en el servidor, nunca se
//   confía en lo que calculó el carrito en el navegador.
// - El descuento se reparte proporcional al valor de lista de cada línea reclamada, para
//   que las devoluciones (api/registrar_devolucion.php) reembolsen lo realmente pagado.
// - Un combo no se acumula con la promoción individual del producto: si una línea queda
//   reclamada por un combo, se descarta su descuento individual y se recalcula desde el
//   precio de lista.
// - Si hay varios candidatos para un mismo cupo (ej. 2 aceites distintos), gana el de
//   mayor valor de lista, para que el ahorro mostrado sea el más atractivo posible.
// - Solo se evalúan líneas de producto simple (factor de conversión = 1, sin código de
//   pack). Los packs ya tienen su propio precio especial y no se cruzan con combos.
// - Un combo reclama la línea completa del carrito, no una fracción de su cantidad. Si el
//   cupo pide "≥1" y la línea trae 4 unidades, las 4 entran al combo (nunca cobra de más;
//   en el peor caso es un poco más generoso de lo estrictamente necesario).
// - EXCEPCIÓN: con PRECIO_FIJO (precio cerrado del pack) esa regla se invierte, porque un
//   precio cerrado no escala con la cantidad. Si el cupo pide "≥1 litro" y el cliente lleva
//   4, reclamar la línea completa dejaría los 4 litros al precio cerrado de 1 (fuga de
//   dinero). Por eso PRECIO_FIJO exige cantidad EXACTA por cupo; si no calza, ese combo no
//   se arma para esa línea (los % y montos fijos sí escalan bien y no necesitan esto).

/**
 * Aplica los combos activos sobre las líneas ya armadas de una venta.
 *
 * @param PDO $pdo
 * @param array $itemsProcesados Líneas construidas por api/registrar_venta.php. Cada línea de
 *   producto (esServicio = false) se modifica en el sitio: se recalculan 'descuento' y
 *   'subtotal' si queda reclamada por un combo, y se agrega 'combo_nombre'.
 * @return void
 */
function aplicarCombosCarrito(PDO $pdo, array &$itemsProcesados): void
{
    // 1. Reunir las líneas elegibles (producto simple, factor 1, sin línea de servicio).
    $productoIds = [];
    foreach ($itemsProcesados as $it) {
        if (empty($it['esServicio']) && (float)($it['factor'] ?? 1) <= 1.0) {
            $productoIds[] = (int)$it['prod']['ProductoID'];
        }
    }
    $productoIds = array_values(array_unique($productoIds));
    if (empty($productoIds)) return;

    // 2. Precio de lista y tipo de repuesto real de cada producto, directo de la tabla
    //    (nunca del precio que mandó el carrito, para que no se pueda manipular).
    $in = implode(',', array_fill(0, count($productoIds), '?'));
    $stmt = $pdo->prepare("SELECT ProductoID, TipoRepuesto, PrecioVenta FROM productos WHERE ProductoID IN ($in)");
    $stmt->execute($productoIds);
    $infoProducto = [];
    foreach ($stmt->fetchAll() as $r) {
        $infoProducto[(int)$r['ProductoID']] = ['tipo' => $r['TipoRepuesto'], 'precioLista' => (int)$r['PrecioVenta']];
    }

    // 3. Combos activos y vigentes, con sus cupos.
    $stmtCombos = $pdo->query("
        SELECT * FROM promociones_combos
        WHERE Activa = 1 AND FechaInicio <= NOW() AND FechaFin >= NOW()
        ORDER BY ComboID ASC
    ");
    $combos = $stmtCombos->fetchAll();
    if (empty($combos)) return;

    $stmtItemsCombo = $pdo->prepare("SELECT * FROM promociones_combo_items WHERE ComboID = :id");

    // Índices de líneas ya reclamadas por otro combo, para no repartirlas dos veces.
    $reclamadas = [];

    foreach ($combos as $combo) {
        $stmtItemsCombo->execute([':id' => $combo['ComboID']]);
        $cupos = $stmtItemsCombo->fetchAll();
        if (empty($cupos)) continue;

        $asignacionCombo = []; // idx de $itemsProcesados => true, solo si el combo completo se logra armar
        $exito = true;

        foreach ($cupos as $cupo) {
            $candidatoIdx = null;
            $candidatoValor = -1;

            foreach ($itemsProcesados as $idx => $it) {
                if (!empty($it['esServicio']) || (float)($it['factor'] ?? 1) > 1.0) continue;
                if (isset($reclamadas[$idx]) || isset($asignacionCombo[$idx])) continue;

                $pid = (int)$it['prod']['ProductoID'];
                if (!isset($infoProducto[$pid])) continue;
                $info = $infoProducto[$pid];

                $coincide = $cupo['ModoSeleccion'] === 'PRODUCTO_ESPECIFICO'
                    ? ((int)$cupo['ProductoID'] === $pid)
                    : ($info['tipo'] === $cupo['TipoRepuesto']);
                if (!$coincide) continue;

                if ($combo['TipoDescuento'] === 'PRECIO_FIJO') {
                    // Cantidad exacta: un precio cerrado no puede "regalar" el excedente.
                    if (abs((float)$it['cant'] - (float)$cupo['CantidadRequerida']) > 0.0001) continue;
                } else {
                    if ((float)$it['cant'] < (float)$cupo['CantidadRequerida']) continue;
                }

                $valorLinea = $info['precioLista'] * (float)$it['cant'];
                if ($valorLinea > $candidatoValor) {
                    $candidatoValor = $valorLinea;
                    $candidatoIdx = $idx;
                }
            }

            if ($candidatoIdx === null) { $exito = false; break; }
            $asignacionCombo[$candidatoIdx] = true;
        }

        if (!$exito || empty($asignacionCombo)) continue;

        // 4. Recalcular esas líneas desde el precio de lista (sin descuento individual)
        //    y repartir el descuento del combo proporcional al valor de cada una.
        $valorBase = 0;
        foreach (array_keys($asignacionCombo) as $idx) {
            $pid = (int)$itemsProcesados[$idx]['prod']['ProductoID'];
            $valorBase += $infoProducto[$pid]['precioLista'] * (float)$itemsProcesados[$idx]['cant'];
        }
        if ($valorBase <= 0) continue;

        $tipo = $combo['TipoDescuento'];
        $valor = (float)$combo['ValorDescuento'];
        if ($tipo === 'PORCENTAJE') {
            $descuentoCombo = (int)round($valorBase * min(100, max(0, $valor)) / 100);
        } elseif ($tipo === 'MONTO_FIJO') {
            $descuentoCombo = (int)min($valorBase, round($valor));
        } else { // PRECIO_FIJO: el valor es el precio final que debe quedar el combo completo
            $descuentoCombo = (int)max(0, $valorBase - round($valor));
        }
        if ($descuentoCombo <= 0) continue;

        $idxs = array_keys($asignacionCombo);
        $repartido = 0;
        foreach ($idxs as $n => $idx) {
            $pid = (int)$itemsProcesados[$idx]['prod']['ProductoID'];
            $precioLista = $infoProducto[$pid]['precioLista'];
            $subtotalLista = (int)round($precioLista * (float)$itemsProcesados[$idx]['cant']);

            $esUltimo = ($n === count($idxs) - 1);
            $shareDescuento = $esUltimo
                ? ($descuentoCombo - $repartido)
                : (int)round($descuentoCombo * ($subtotalLista / $valorBase));
            $repartido += $shareDescuento;

            $itemsProcesados[$idx]['precio'] = $precioLista;
            $itemsProcesados[$idx]['descuento'] = $shareDescuento;
            $itemsProcesados[$idx]['subtotal'] = max(0, $subtotalLista - $shareDescuento);
            $itemsProcesados[$idx]['combo_nombre'] = $combo['Nombre'];

            $reclamadas[$idx] = true;
        }
    }
}
