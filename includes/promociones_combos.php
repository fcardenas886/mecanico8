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
// - Un combo solo descuenta la cantidad exacta que pide cada cupo. Si el cupo pide "1" y la
//   línea trae 4 unidades, esa línea se PARTE en dos: 1 unidad entra al combo (con su
//   descuento), y las 3 restantes se venden aparte a precio normal, como una línea extra
//   real en la boleta (no una fracción visual: son dos filas de detalleventas, cada una con
//   su propio stock/kardex).

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

        $asignacionCombo = []; // idx de $itemsProcesados => cantidad que pide ese cupo (no siempre toda la línea)
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

                // Sin combo: línea marcada así (ej. presupuesto con combos desactivados) o con un precio
                // distinto al de lista (precio editado a mano = precio final acordado, no se combina).
                if (!empty($it['sin_combo']) || (int)$it['precio'] !== $info['precioLista']) continue;

                $coincide = $cupo['ModoSeleccion'] === 'PRODUCTO_ESPECIFICO'
                    ? ((int)$cupo['ProductoID'] === $pid)
                    : ($info['tipo'] === $cupo['TipoRepuesto']);
                if (!$coincide) continue;
                if ((float)$it['cant'] < (float)$cupo['CantidadRequerida']) continue;

                $valorLinea = $info['precioLista'] * (float)$it['cant'];
                if ($valorLinea > $candidatoValor) {
                    $candidatoValor = $valorLinea;
                    $candidatoIdx = $idx;
                }
            }

            if ($candidatoIdx === null) { $exito = false; break; }
            $asignacionCombo[$candidatoIdx] = (float)$cupo['CantidadRequerida'];
        }

        if (!$exito || empty($asignacionCombo)) continue;

        // 4. Calcular cuánto descuenta el combo sobre la cantidad exacta de cada cupo (no sobre
        //    toda la línea). Todavía no se toca nada: si el combo no rinde descuento (ej. un
        //    precio cerrado mayor al de lista), se descarta sin haber partido ninguna línea.
        $valorBase = 0;
        foreach ($asignacionCombo as $idx => $requerida) {
            $pid = (int)$itemsProcesados[$idx]['prod']['ProductoID'];
            $valorBase += $infoProducto[$pid]['precioLista'] * $requerida;
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

        // 5. Ahora sí: partir en dos las líneas que traen más cantidad de la que pide su cupo.
        //    La cantidad exacta se va al combo; el resto queda como línea aparte a precio normal
        //    (con la misma tasa por unidad que ya tenía esa línea, por si venía con una
        //    promoción individual, para no perderla en el sobrante).
        foreach ($asignacionCombo as $idx => $requerida) {
            $cantLinea = (float)$itemsProcesados[$idx]['cant'];
            $sobrante = $cantLinea - $requerida;
            if ($sobrante <= 0.0001) continue;

            $original = $itemsProcesados[$idx];
            $tasaSubtotal = $original['cant'] > 0 ? $original['subtotal'] / $original['cant'] : $original['precio'];
            $tasaDescuento = $original['cant'] > 0 ? $original['descuento'] / $original['cant'] : 0;

            $lineaSobrante = $original;
            $lineaSobrante['cant'] = $sobrante;
            $lineaSobrante['descuento'] = (int)round($tasaDescuento * $sobrante);
            $lineaSobrante['subtotal'] = max(0, (int)round($tasaSubtotal * $sobrante));
            unset($lineaSobrante['combo_nombre']);
            $itemsProcesados[] = $lineaSobrante;

            // La línea original se reduce a la cantidad exacta que pide el cupo; su precio
            // y subtotal se recalculan justo abajo desde el precio de lista.
            $itemsProcesados[$idx]['cant'] = $requerida;
        }

        // 6. Recalcular las líneas reclamadas desde el precio de lista (sin descuento
        //    individual) y repartir el descuento del combo proporcional al valor de cada una.
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

/**
 * Descuento de combos que le corresponde a un presupuesto, con el mismo motor que cobra la
 * Caja, para que el presupuesto entregado al cliente diga lo mismo que se va a cobrar.
 *
 * @param array $lineas Filas de presupuestodetalle (idealmente ya filtradas a las aprobadas si
 *   el presupuesto está decidido). Solo los repuestos con ProductoID participan.
 * @return array ['descuento' => int total, 'combos' => [['nombre' => ..., 'monto' => int], ...]]
 */
function calcularCombosPresupuesto(PDO $pdo, array $lineas): array
{
    $items = [];
    foreach ($lineas as $l) {
        if (($l['TipoLinea'] ?? '') !== 'Repuesto' || empty($l['ProductoID'])) continue;
        $items[] = [
            'esServicio' => false,
            'prod' => ['ProductoID' => (int)$l['ProductoID']],
            'cant' => (float)$l['Cantidad'],
            'factor' => 1,
            'precio' => (int)$l['PrecioUnitario'],
            'descuento' => 0,
            'subtotal' => (int)$l['Subtotal'],
        ];
    }
    if (empty($items)) return ['descuento' => 0, 'combos' => []];

    aplicarCombosCarrito($pdo, $items);

    $porCombo = [];
    $total = 0;
    foreach ($items as $it) {
        if (empty($it['combo_nombre'])) continue;
        $porCombo[$it['combo_nombre']] = ($porCombo[$it['combo_nombre']] ?? 0) + (int)$it['descuento'];
        $total += (int)$it['descuento'];
    }
    $combos = [];
    foreach ($porCombo as $nombre => $monto) $combos[] = ['nombre' => $nombre, 'monto' => $monto];
    return ['descuento' => $total, 'combos' => $combos];
}