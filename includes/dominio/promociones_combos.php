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

        // El mismo combo puede armarse varias veces en una venta (ej. repuestos para 2 autos: 2 aceites +
        // 2 filtros = 2 packs). Cada vuelta arma un pack con líneas todavía no reclamadas; las líneas con más
        // cantidad se parten (ver más abajo), así el sobrante queda disponible para la vuelta siguiente.
        // Se corta apenas no se puede armar otro pack (o por seguridad a las 50 vueltas).
        for ($vuelta = 0; $vuelta < 50; $vuelta++) {
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

            if (!$exito || empty($asignacionCombo)) break;

            // 4. Calcular cuánto descuenta el combo sobre la cantidad exacta de cada cupo (no sobre
            //    toda la línea). Todavía no se toca nada: si el combo no rinde descuento (ej. un
            //    precio cerrado mayor al de lista), se descarta sin haber partido ninguna línea.
            $valorBase = 0;
            foreach ($asignacionCombo as $idx => $requerida) {
                $pid = (int)$itemsProcesados[$idx]['prod']['ProductoID'];
                $valorBase += $infoProducto[$pid]['precioLista'] * $requerida;
            }
            if ($valorBase <= 0) break;

            $tipo = $combo['TipoDescuento'];
            $valor = (float)$combo['ValorDescuento'];
            if ($tipo === 'PORCENTAJE') {
                $descuentoCombo = (int)round($valorBase * min(100, max(0, $valor)) / 100);
            } elseif ($tipo === 'MONTO_FIJO') {
                $descuentoCombo = (int)min($valorBase, round($valor));
            } else { // PRECIO_FIJO: el valor es el precio final que debe quedar el combo completo
                $descuentoCombo = (int)max(0, $valorBase - round($valor));
            }
            if ($descuentoCombo <= 0) break;

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
}

/**
 * Descuento de la promoción individual de un producto (DESCUENTO_UNIT o MULTIBUY) sobre una
 * línea de producto simple. Es la fórmula única: la usan tanto la Caja (api/registrar_venta.php)
 * como el presupuesto, para que ambos den exactamente el mismo número.
 */
function descuentoPromoIndividual(?array $promo, int $precioUnitario, float $cant): int
{
    if (!$promo) return 0;
    if ($promo['Tipo'] === 'DESCUENTO_UNIT') {
        $descUnit = (int)round($precioUnitario * ((float)$promo['DescuentoPorcentaje'] / 100));
        return (int)round($cant * $descUnit);
    }
    if ($promo['Tipo'] === 'MULTIBUY') {
        $cantMin = (int)$promo['CantidadMinima'];
        $precioOf = (int)$promo['PrecioOferta'];
        if ($cantMin > 0 && $cant >= $cantMin) {
            $packs = (int)floor($cant / $cantMin);
            $resto = $cant % $cantMin;
            $subtotalConPromo = ($packs * $precioOf) + ($resto * $precioUnitario);
            $subtotalNormal = $cant * $precioUnitario;
            return (int)max(0, $subtotalNormal - $subtotalConPromo);
        }
    }
    return 0;
}

/**
 * Descuentos que le corresponden a un presupuesto, con los mismos motores que cobra la Caja
 * (promoción individual de cada producto + combos), para que el presupuesto entregado al cliente
 * diga lo mismo que se va a cobrar.
 *
 * @param array $lineas Filas de presupuestodetalle (ya filtradas a las aprobadas si el presupuesto
 *   está decidido). Solo los repuestos con ProductoID participan.
 * @param bool $conCombos false = el presupuesto desactivó los combos (las promociones individuales
 *   de cada producto igual se aplican, como en la Caja).
 * @return array ['descuento' => total (promos + combos), 'combos' => [['nombre','monto']],
 *   'promos' => [['nombre','monto']]]
 */
function calcularCombosPresupuesto(PDO $pdo, array $lineas, bool $conCombos = true): array
{
    $vacio = ['descuento' => 0, 'combos' => [], 'promos' => [], 'porLinea' => []];
    $items = [];
    $nombres = [];
    foreach ($lineas as $l) {
        if (($l['TipoLinea'] ?? '') !== 'Repuesto' || empty($l['ProductoID'])) continue;
        $id = (int)$l['PresupuestoDetalleID'];
        $nombres[$id] = $l['Descripcion'];
        $items[] = [
            'esServicio' => false,
            'sin_combo' => !$conCombos,
            'origen' => $id,
            'prod' => ['ProductoID' => (int)$l['ProductoID']],
            'cant' => (float)$l['Cantidad'],
            'factor' => 1,
            'precio' => (int)$l['PrecioUnitario'],
            'descuento' => 0,
            'subtotal' => (int)$l['Subtotal'],
        ];
    }
    if (empty($items)) return $vacio;

    // Promoción individual vigente de cada producto, igual que la busca la Caja.
    $ids = array_values(array_unique(array_map(fn($i) => $i['prod']['ProductoID'], $items)));
    $stmt = $pdo->prepare("
        SELECT ProductoID, Tipo, CantidadMinima, DescuentoPorcentaje, PrecioOferta FROM promociones
        WHERE ProductoID IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
          AND Activa = TRUE AND FechaInicio <= NOW() AND FechaFin >= NOW()
    ");
    $stmt->execute($ids);
    $promos = [];
    foreach ($stmt->fetchAll() as $p) { $promos[(int)$p['ProductoID']] ??= $p; }

    foreach ($items as &$it) {
        $it['descuento'] = descuentoPromoIndividual($promos[$it['prod']['ProductoID']] ?? null, $it['precio'], $it['cant']);
        $it['subtotal'] = max(0, (int)round($it['cant'] * $it['precio']) - $it['descuento']);
    }
    unset($it);

    aplicarCombosCarrito($pdo, $items);

    $porCombo = [];
    $porPromo = [];
    $porLinea = [];
    foreach ($items as $it) {
        $o = $it['origen'];
        $porLinea[$o] ??= ['combo' => 0, 'comboNombre' => null, 'oferta' => 0];
        if (!empty($it['combo_nombre'])) {
            $porCombo[$it['combo_nombre']] = ($porCombo[$it['combo_nombre']] ?? 0) + (int)$it['descuento'];
            $porLinea[$o]['combo'] += (int)$it['descuento'];
            $porLinea[$o]['comboNombre'] = $it['combo_nombre'];
        } elseif ((int)$it['descuento'] > 0) {
            $porPromo[$o] = ($porPromo[$o] ?? 0) + (int)$it['descuento'];
            $porLinea[$o]['oferta'] += (int)$it['descuento'];
        }
    }
    $res = $vacio;
    $res['porLinea'] = $porLinea;
    foreach ($porCombo as $nombre => $monto) { $res['combos'][] = ['nombre' => $nombre, 'monto' => $monto]; $res['descuento'] += $monto; }
    foreach ($porPromo as $origen => $monto) { $res['promos'][] = ['nombre' => $nombres[$origen] ?? 'Producto', 'monto' => $monto]; $res['descuento'] += $monto; }
    return $res;
}


/**
 * Congela los descuentos de un presupuesto recién aprobado: calcula combos y ofertas sobre las
 * líneas aprobadas y los guarda línea por línea. Desde ese momento el presupuesto es el que
 * vale: la Caja cobra exactamente estos montos, aunque después venza una oferta o cambie un precio.
 * Debe llamarse dentro de la transacción de la decisión, después de marcar las líneas aprobadas.
 */
function congelarDescuentosPresupuesto(PDO $pdo, int $presupuestoId, bool $conCombos): void
{
    $stmt = $pdo->prepare("SELECT * FROM presupuestodetalle WHERE PresupuestoID = :pid AND Aprobado = 1");
    $stmt->execute([':pid' => $presupuestoId]);
    $calc = calcularCombosPresupuesto($pdo, $stmt->fetchAll(), $conCombos);

    $upd = $pdo->prepare("UPDATE presupuestodetalle SET DescuentoCombo = :c, ComboNombre = :n, DescuentoOferta = :o WHERE PresupuestoDetalleID = :id");
    foreach ($calc['porLinea'] as $lineaId => $d) {
        $upd->execute([':c' => $d['combo'], ':n' => $d['comboNombre'], ':o' => $d['oferta'], ':id' => $lineaId]);
    }
    $pdo->prepare("UPDATE presupuestos SET DescuentosCongelados = 1 WHERE PresupuestoID = :pid")->execute([':pid' => $presupuestoId]);
}

/**
 * Descuentos ya congelados de un presupuesto aprobado, en el mismo formato que
 * calcularCombosPresupuesto (para mostrarlos sin recalcular nada).
 */
function descuentosCongeladosPresupuesto(array $lineas): array
{
    $res = ['descuento' => 0, 'combos' => [], 'promos' => [], 'porLinea' => []];
    $porCombo = [];
    foreach ($lineas as $l) {
        if (empty($l['Aprobado'])) continue;
        $c = (int)($l['DescuentoCombo'] ?? 0);
        $o = (int)($l['DescuentoOferta'] ?? 0);
        if ($c > 0) $porCombo[$l['ComboNombre'] ?: 'Combo'] = ($porCombo[$l['ComboNombre'] ?: 'Combo'] ?? 0) + $c;
        if ($o > 0) $res['promos'][] = ['nombre' => $l['Descripcion'], 'monto' => $o];
        $res['descuento'] += $c + $o;
    }
    foreach ($porCombo as $nombre => $monto) $res['combos'][] = ['nombre' => $nombre, 'monto' => $monto];
    return $res;
}