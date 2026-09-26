<?php
// No incluir includes/header.php todavía: si el POST termina en aprobación,
// redirige con header('Location: ejecucion.php...') y eso falla si ya se envió HTML antes.
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = getDB();
$user = currentUser();
$error = '';
$message = '';

$otId = (int)($_GET['id'] ?? $_POST['ot_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, v.Color, v.KilometrajeUltimo,
           c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono
    FROM ordenestrabajo ot
    JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
    JOIN clientes c ON ot.ClienteID = c.ClienteID
    WHERE ot.OrdenTrabajoID = :id
");
$stmt->execute([':id' => $otId]);
$ot = $stmt->fetch();

if (!$ot) {
    http_response_code(404);
    require_once __DIR__ . '/includes/header.php';
    echo "<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Orden de Trabajo no encontrada</h2><a href='ordenestrabajo.php'>Volver al listado</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

function cargarPresupuesto(PDO $pdo, int $otId) {
    $stmt = $pdo->prepare("SELECT * FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
    $stmt->execute([':id' => $otId]);
    return $stmt->fetch() ?: null;
}

function cargarLineasPresupuesto(PDO $pdo, int $presupuestoId): array {
    $stmt = $pdo->prepare("SELECT * FROM presupuestodetalle WHERE PresupuestoID = :pid ORDER BY PresupuestoDetalleID ASC");
    $stmt->execute([':pid' => $presupuestoId]);
    return $stmt->fetchAll();
}

// Agrega una línea al presupuesto. Si el mismo repuesto o servicio ya está al mismo precio,
// suma la cantidad en vez de duplicar la línea. Devuelve 'agregada' o 'sumada'.
function agregarLineaPresupuesto(PDO $pdo, int $pid, array $d): string {
    $productoId = !empty($d['producto_id']) ? (int)$d['producto_id'] : null;
    $servicioId = !empty($d['servicio_id']) ? (int)$d['servicio_id'] : null;
    $cantidad = (float)$d['cantidad'];
    $precio = (int)$d['precio'];

    if ($productoId || $servicioId) {
        $col = $productoId ? 'ProductoID' : 'ServicioID';
        $stmt = $pdo->prepare("SELECT PresupuestoDetalleID FROM presupuestodetalle WHERE PresupuestoID = :pid AND {$col} = :ref AND PrecioUnitario = :precio LIMIT 1");
        $stmt->execute([':pid' => $pid, ':ref' => $productoId ?: $servicioId, ':precio' => $precio]);
        $existente = $stmt->fetchColumn();
        if ($existente) {
            $pdo->prepare("UPDATE presupuestodetalle SET Cantidad = Cantidad + :cant, Subtotal = ROUND(PrecioUnitario * (Cantidad)) WHERE PresupuestoDetalleID = :id")
                ->execute([':cant' => $cantidad, ':id' => $existente]);
            return 'sumada';
        }
    }

    $pdo->prepare("
        INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, ProductoID, Descripcion, Cantidad, PrecioUnitario, Subtotal, ServicioID, PoliticaCobro, Origen)
        VALUES (:pid, :tipo, :prodid, :desc, :cant, :precio, :subtotal, :sid, :pol, :origen)
    ")->execute([
        ':pid' => $pid, ':tipo' => $d['tipo'], ':prodid' => $productoId, ':desc' => $d['descripcion'],
        ':cant' => $cantidad, ':precio' => $precio, ':subtotal' => (int)round($precio * $cantidad),
        ':sid' => $servicioId, ':pol' => $d['politica'] ?? 'Siempre', ':origen' => $d['origen'] ?? 'Directo',
    ]);
    return 'agregada';
}

// Totales del presupuesto con el mismo motor de combos/ofertas de la Caja. Única fuente para
// la pantalla y las respuestas AJAX, así el total nunca se muestra sin descuentos.
function totalesPresupuesto(PDO $pdo, array $presupuesto, array $lineas): array {
    $subtotal = 0;
    $aprobado = 0;
    $grupos = ['ManoObra' => 0, 'Repuesto' => 0, 'Terceros' => 0];
    foreach ($lineas as $l) {
        if ($l['PoliticaCobro'] !== 'SoloSiNoAprueba') {
            $subtotal += (int)$l['Subtotal'];
            $grupos[$l['TipoLinea']] = ($grupos[$l['TipoLinea']] ?? 0) + (int)$l['Subtotal'];
        }
        if ($l['Aprobado']) $aprobado += (int)$l['Subtotal'];
    }

    $aplicaCombos = (int)($presupuesto['AplicaCombos'] ?? 1) === 1;
    if ((int)($presupuesto['DescuentosCongelados'] ?? 0) === 1) {
        // Presupuesto ya aprobado: valen los descuentos congelados al aprobar, no un recálculo con las ofertas de hoy.
        $combos = $combosAprobado = descuentosCongeladosPresupuesto($lineas);
    } else {
        // Las promociones individuales de cada producto se aplican siempre (como en la Caja);
        // el interruptor solo controla los combos.
        $combos = calcularCombosPresupuesto($pdo, array_filter($lineas, fn($l) => $l['PoliticaCobro'] !== 'SoloSiNoAprueba'), $aplicaCombos);
        $combosAprobado = calcularCombosPresupuesto($pdo, array_filter($lineas, fn($l) => $l['Aprobado']), $aplicaCombos);
    }

    return [
        'subtotal' => $subtotal,
        'grupos' => $grupos,
        'combos' => $combos,
        'total' => $subtotal - $combos['descuento'],
        'totalAprobado' => $aprobado - $combosAprobado['descuento'],
        'aplicaCombos' => $aplicaCombos,
    ];
}

$presupuesto = cargarPresupuesto($pdo, $otId);
if (!$presupuesto) {
    $stmt = $pdo->prepare("INSERT INTO presupuestos (OrdenTrabajoID, UsuarioID) VALUES (:ot, :uid)");
    $stmt->execute([':ot' => $otId, ':uid' => $user['id']]);
    $presupuesto = cargarPresupuesto($pdo, $otId);
    if ($presupuesto) {
        agregarDiagnosticoAlPresupuesto($pdo, (int)$presupuesto['PresupuestoID'], (string)($ot['Estado'] ?? ''));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $esAjax = !empty($_POST['ajax']);

    if ($action === 'actualizar_telefono_cliente') {
        $nuevoTel = trim($_POST['telefono'] ?? '');
        $pdo->prepare("UPDATE clientes SET Telefono = :tel WHERE ClienteID = :cid")
            ->execute([':tel' => $nuevoTel, ':cid' => $ot['ClienteID']]);
        $ot['ClienteTelefono'] = $nuevoTel;
        $message = 'Teléfono del cliente guardado.';
    }

    if ($presupuesto && $presupuesto['DecisionCliente'] === 'Pendiente') {
        $pid = (int)$presupuesto['PresupuestoID'];

        $origen = trim($_POST['origen'] ?? 'Directo');
        if (!in_array($origen, ['Cliente', 'Diagnostico', 'Historial', 'Compatibilidad', 'Directo'], true)) {
            $origen = 'Directo';
        }
        $cantidad = (float)($_POST['cantidad'] ?? 1);

        if ($action === 'agregar_repuesto') {
            $stmtP = $pdo->prepare("SELECT Nombre, PrecioVenta FROM productos WHERE ProductoID = :id AND Activo = TRUE");
            $stmtP->execute([':id' => (int)($_POST['producto_id'] ?? 0)]);
            $prod = $stmtP->fetch();
            if ($prod && $cantidad > 0) {
                $r = agregarLineaPresupuesto($pdo, $pid, [
                    'tipo' => 'Repuesto', 'producto_id' => (int)$_POST['producto_id'], 'descripcion' => $prod['Nombre'],
                    'cantidad' => $cantidad, 'precio' => (int)$prod['PrecioVenta'], 'origen' => $origen,
                ]);
                $message = $r === 'sumada' ? "Ya estaba en el presupuesto: se sumó la cantidad de \"{$prod['Nombre']}\"." : "\"{$prod['Nombre']}\" agregado.";
            } else {
                $error = 'Selecciona un repuesto válido.';
            }
        } elseif ($action === 'agregar_servicio') {
            $stmtS = $pdo->prepare("SELECT OperacionID, Nombre, PrecioBase, PoliticaCobro FROM operacionessolicitadas WHERE OperacionID = :id AND Activo = TRUE AND PrecioBase > 0");
            $stmtS->execute([':id' => (int)($_POST['servicio_id'] ?? 0)]);
            $serv = $stmtS->fetch();
            if ($serv && $cantidad > 0) {
                $r = agregarLineaPresupuesto($pdo, $pid, [
                    'tipo' => 'ManoObra', 'servicio_id' => (int)$serv['OperacionID'], 'descripcion' => $serv['Nombre'],
                    'cantidad' => $cantidad, 'precio' => (int)$serv['PrecioBase'], 'politica' => $serv['PoliticaCobro'], 'origen' => $origen,
                ]);
                $message = $r === 'sumada' ? "Ya estaba en el presupuesto: se sumó la cantidad de \"{$serv['Nombre']}\"." : "\"{$serv['Nombre']}\" agregado.";
            } else {
                $error = 'Selecciona un servicio válido.';
            }
        } elseif ($action === 'agregar_linea') {
            $tipo = $_POST['tipo'] ?? '';
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = (int)($_POST['precio'] ?? 0);
            if (in_array($tipo, ['ManoObra', 'Repuesto', 'Terceros'], true) && $descripcion !== '' && $precio > 0 && $cantidad > 0) {
                agregarLineaPresupuesto($pdo, $pid, [
                    'tipo' => $tipo, 'descripcion' => $descripcion, 'cantidad' => $cantidad, 'precio' => $precio, 'origen' => $origen,
                ]);
                $message = "\"{$descripcion}\" agregado.";
            } else {
                $error = 'Completa descripción, precio y cantidad.';
            }
        } elseif ($action === 'cotizar_operacion_rapida') {
            $stmtOp = $pdo->prepare("
                SELECT oos.NombreOperacion, oos.OperacionID, op.PrecioBase, op.PoliticaCobro
                FROM orden_operaciones_solicitadas oos
                LEFT JOIN operacionessolicitadas op ON oos.OperacionID = op.OperacionID
                WHERE oos.ID = :id AND oos.OrdenTrabajoID = :ot
            ");
            $stmtOp->execute([':id' => (int)($_POST['operacion_solicitada_id'] ?? 0), ':ot' => $otId]);
            $opRow = $stmtOp->fetch();
            if ($opRow) {
                $conPrecio = (int)($opRow['PrecioBase'] ?? 0) > 0;
                $precio = (int)($_POST['precio'] ?? 0) ?: ($conPrecio ? (int)$opRow['PrecioBase'] : 0);
                if ($precio > 0) {
                    agregarLineaPresupuesto($pdo, $pid, [
                        'tipo' => 'ManoObra', 'servicio_id' => $conPrecio ? (int)$opRow['OperacionID'] : null,
                        'descripcion' => $opRow['NombreOperacion'], 'cantidad' => 1, 'precio' => $precio,
                        'politica' => $conPrecio ? $opRow['PoliticaCobro'] : 'Siempre', 'origen' => 'Cliente',
                    ]);
                    $message = "\"{$opRow['NombreOperacion']}\" agregado al presupuesto.";
                } else {
                    $error = 'Indica el precio de la operación.';
                }
            }
        } elseif ($action === 'reutilizar_repuesto_historial') {
            $prodId = (int)($_POST['producto_id'] ?? 0);
            $desc = trim($_POST['descripcion'] ?? '');
            $precio = (int)($_POST['precio'] ?? 0);
            $tipo = in_array($_POST['tipo'] ?? '', ['Repuesto', 'ManoObra', 'Terceros'], true) ? $_POST['tipo'] : 'Repuesto';

            if ($prodId > 0) {
                $stmtP = $pdo->prepare("SELECT Nombre, PrecioVenta FROM productos WHERE ProductoID = :id AND Activo = TRUE");
                $stmtP->execute([':id' => $prodId]);
                if ($prodRow = $stmtP->fetch()) {
                    $desc = $prodRow['Nombre'];
                    $precio = (int)$prodRow['PrecioVenta'];
                } else {
                    $prodId = 0;
                }
            }

            if ($desc !== '' && $precio > 0) {
                $r = agregarLineaPresupuesto($pdo, $pid, [
                    'tipo' => $tipo, 'producto_id' => $prodId ?: null, 'descripcion' => $desc,
                    'cantidad' => 1, 'precio' => $precio, 'origen' => $origen,
                ]);
                $message = $r === 'sumada' ? "Ya estaba en el presupuesto: se sumó la cantidad de \"{$desc}\"." : "\"{$desc}\" agregado.";
            } else {
                $error = 'No se pudo incorporar el ítem.';
            }
        } elseif ($action === 'editar_linea') {
            // Precio y cantidad se ajustan en la misma tabla. Un repuesto con precio distinto al de
            // lista queda fuera de los combos (precio final acordado), en el presupuesto y en la Caja.
            $precio = (int)($_POST['precio'] ?? 0);
            if ($precio > 0 && $cantidad > 0) {
                $pdo->prepare("
                    UPDATE presupuestodetalle
                    SET PrecioUnitario = :precio, Cantidad = :cant, Subtotal = ROUND(:precio2 * :cant2)
                    WHERE PresupuestoDetalleID = :id AND PresupuestoID = :pid
                ")->execute([':precio' => $precio, ':cant' => $cantidad, ':precio2' => $precio, ':cant2' => $cantidad,
                             ':id' => (int)($_POST['linea_id'] ?? 0), ':pid' => $pid]);
                $message = 'Línea actualizada.';
            } else {
                $error = 'Precio y cantidad deben ser mayores a 0.';
            }
        } elseif ($action === 'eliminar_linea') {
            $pdo->prepare("DELETE FROM presupuestodetalle WHERE PresupuestoDetalleID = :id AND PresupuestoID = :pid")
                ->execute([':id' => (int)($_POST['linea_id'] ?? 0), ':pid' => $pid]);
            $message = 'Línea eliminada.';
        } elseif ($action === 'cambiar_politica') {
            $nueva = ($_POST['politica'] ?? '') === 'SoloSiNoAprueba' ? 'SoloSiNoAprueba' : 'Siempre';
            $pdo->prepare("UPDATE presupuestodetalle SET PoliticaCobro = :p WHERE PresupuestoDetalleID = :id AND PresupuestoID = :pid AND TipoLinea = 'ManoObra'")
                ->execute([':p' => $nueva, ':id' => (int)($_POST['linea_id'] ?? 0), ':pid' => $pid]);
            $message = $nueva === 'Siempre' ? 'La línea ahora se cobra siempre.' : 'La línea se cobra solo si el cliente no aprueba.';
        } elseif ($action === 'toggle_combos') {
            $aplica = !empty($_POST['aplica_combos']) ? 1 : 0;
            $pdo->prepare("UPDATE presupuestos SET AplicaCombos = :a WHERE PresupuestoID = :id")
                ->execute([':a' => $aplica, ':id' => $pid]);
            $message = $aplica ? 'Se aplican los combos de Promociones.' : 'Presupuesto sin descuentos de combos.';
        } elseif ($action === 'guardar_tiempo_entrega') {
            $tiempo = trim($_POST['tiempo_entrega'] ?? '');
            $pdo->prepare("UPDATE presupuestos SET TiempoEntrega = :t WHERE PresupuestoID = :id")
                ->execute([':t' => $tiempo ?: null, ':id' => $pid]);
            $message = 'Tiempo de entrega guardado.';
        } elseif ($action === 'decidir') {
            $decision = $_POST['decision'] ?? '';
            if (in_array($decision, ['AprobadoTotal', 'AprobadoParcial', 'Rechazado'], true)) {
                $pdo->beginTransaction();
                try {
                    if ($decision === 'AprobadoParcial') {
                        $aprobadas = array_map('intval', $_POST['lineas_aprobadas'] ?? []);
                        if (empty($aprobadas)) {
                            throw new RuntimeException('Marca al menos un ítem aprobado por el cliente.');
                        }
                        $stmtLineas = $pdo->prepare("SELECT PresupuestoDetalleID FROM presupuestodetalle WHERE PresupuestoID = :pid");
                        $stmtLineas->execute([':pid' => $pid]);
                        $upd = $pdo->prepare("UPDATE presupuestodetalle SET Aprobado = :ap WHERE PresupuestoDetalleID = :id");
                        foreach ($stmtLineas->fetchAll(PDO::FETCH_COLUMN) as $lineaId) {
                            $upd->execute([':ap' => in_array((int)$lineaId, $aprobadas, true) ? 1 : 0, ':id' => $lineaId]);
                        }
                    } elseif ($decision === 'Rechazado') {
                        $pdo->prepare("UPDATE presupuestodetalle SET Aprobado = 0 WHERE PresupuestoID = :pid")
                            ->execute([':pid' => $pid]);
                    } else {
                        $pdo->prepare("UPDATE presupuestodetalle SET Aprobado = 1 WHERE PresupuestoID = :pid")
                            ->execute([':pid' => $pid]);
                    }

                    // Líneas condicionales (ej. diagnóstico): solo se cobran si el cliente no aprueba trabajo.
                    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM presupuestodetalle WHERE PresupuestoID = :pid AND PoliticaCobro <> 'SoloSiNoAprueba' AND Aprobado = 1");
                    $stmtC->execute([':pid' => $pid]);
                    $cobraCondicional = ((int)$stmtC->fetchColumn() === 0) ? 1 : 0;
                    $pdo->prepare("UPDATE presupuestodetalle SET Aprobado = :ap WHERE PresupuestoID = :pid AND PoliticaCobro = 'SoloSiNoAprueba'")
                        ->execute([':ap' => $cobraCondicional, ':pid' => $pid]);

                    $pdo->prepare("UPDATE presupuestos SET DecisionCliente = :d, FechaDecision = NOW() WHERE PresupuestoID = :id")
                        ->execute([':d' => $decision, ':id' => $pid]);

                    // Al aprobar, los descuentos (combos y ofertas) quedan congelados: desde acá el presupuesto
                    // es el que vale y la Caja cobra exactamente estos montos.
                    if ($decision !== 'Rechazado') {
                        congelarDescuentosPresupuesto($pdo, $pid, (int)($presupuesto['AplicaCombos'] ?? 1) === 1);
                    }

                    $estadoOT = $decision === 'Rechazado' ? 'Presupuesto rechazado' : 'Presupuesto aprobado';
                    $pdo->prepare("UPDATE ordenestrabajo SET Estado = :e WHERE OrdenTrabajoID = :id")
                        ->execute([':e' => $estadoOT, ':id' => $otId]);
                    $pdo->commit();

                    if ($decision !== 'Rechazado') {
                        header("Location: ejecucion.php?id={$otId}&aprobado=1");
                        exit;
                    }
                    $message = 'Presupuesto registrado como rechazado.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = $e instanceof RuntimeException ? $e->getMessage() : 'Error al registrar la decisión: ' . $e->getMessage();
                }
            }
        }

        $presupuesto = cargarPresupuesto($pdo, $otId);

        // Edición y borrado desde la tabla: responde con los totales ya descontados y el HTML
        // del bloque de totales, para refrescar la pantalla sin recargar.
        if ($esAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if ($error) {
                echo json_encode(['ok' => false, 'error' => $error]);
                exit;
            }
            $lineas = cargarLineasPresupuesto($pdo, $pid);
            $tot = totalesPresupuesto($pdo, $presupuesto, $lineas);
            $lineaId = (int)($_POST['linea_id'] ?? 0);
            $linea = current(array_filter($lineas, fn($l) => (int)$l['PresupuestoDetalleID'] === $lineaId)) ?: null;
            $pendiente = true;
            ob_start();
            include __DIR__ . '/views/partials/presupuesto_totales.php';
            $totalesHtml = ob_get_clean();

            require_once __DIR__ . '/includes/whatsapp_helper.php';
            $waMsg = mensajePresupuestoWhatsApp($ot, $presupuesto, (float)$tot['total'], obtenerNombreTaller($pdo));
            $waUrl = generarUrlWhatsapp($ot['ClienteTelefono'] ?? '', $waMsg);

            echo json_encode([
                'ok' => true,
                'count' => count($lineas),
                'subtotal_fmt' => $linea ? formatCLP($linea['Subtotal']) : null,
                'grupos_fmt' => array_map('formatCLP', $tot['grupos']),
                'total' => $tot['total'],
                'total_fmt' => formatCLP($tot['total']),
                'totales_html' => $totalesHtml,
                'wa_url' => $waUrl,
            ]);
            exit;
        }
    }
}

$lineas = $presupuesto ? cargarLineasPresupuesto($pdo, (int)$presupuesto['PresupuestoID']) : [];
$tot = totalesPresupuesto($pdo, $presupuesto ?: [], $lineas);

// Precio de lista actual de cada repuesto, para marcar las líneas cuyo precio se editó a mano.
$preciosLista = [];
$idsProd = array_values(array_unique(array_filter(array_map(fn($l) => (int)($l['ProductoID'] ?? 0), $lineas))));
if (!empty($idsProd)) {
    $stmtPL = $pdo->prepare('SELECT ProductoID, PrecioVenta FROM productos WHERE ProductoID IN (' . implode(',', array_fill(0, count($idsProd), '?')) . ')');
    $stmtPL->execute($idsProd);
    foreach ($stmtPL->fetchAll() as $r) $preciosLista[(int)$r['ProductoID']] = (int)$r['PrecioVenta'];
}

// 1. Operaciones solicitadas por el cliente en Recepción
$stmtOp = $pdo->prepare("
    SELECT oos.*, op.PrecioBase, op.PoliticaCobro, op.Categoria
    FROM orden_operaciones_solicitadas oos
    LEFT JOIN operacionessolicitadas op ON oos.OperacionID = op.OperacionID
    WHERE oos.OrdenTrabajoID = :ot
    ORDER BY oos.ID ASC
");
$stmtOp->execute([':ot' => $otId]);
$operacionesSolicitadas = $stmtOp->fetchAll();

// 2. Hallazgos del diagnóstico técnico
$stmtDiag = $pdo->prepare("
    SELECT d.*, u.Nombre AS MecanicoNombre
    FROM diagnosticoot d
    LEFT JOIN usuarios u ON d.UsuarioID = u.UsuarioID
    WHERE d.OrdenTrabajoID = :id
    ORDER BY d.Fecha ASC
");
$stmtDiag->execute([':id' => $otId]);
$hallazgos = $stmtDiag->fetchAll();

// 3. Observaciones de la Estación de Servicio
$stmtEst = $pdo->prepare("SELECT Observaciones FROM estacionservicio_ot WHERE OrdenTrabajoID = :ot");
$stmtEst->execute([':ot' => $otId]);
$observaciones = trim((string)($stmtEst->fetchColumn() ?: ''));

// Catálogo para el buscador único (repuestos + servicios)
$servicios = $pdo->query("SELECT OperacionID, Nombre, Categoria, PrecioBase FROM operacionessolicitadas WHERE Activo = TRUE AND PrecioBase > 0 ORDER BY EsDiagnosticoBase DESC, Categoria, Orden")->fetchAll();
$productos = $pdo->query("SELECT ProductoID, Nombre, PrecioVenta, Stock, CodigoBarras, MarcaRepuesto, NumeroParteOEM, NumeroParteAlternativo FROM productos WHERE Activo = TRUE ORDER BY Nombre ASC")->fetchAll();

// 4. Repuestos y servicios usados antes en ESTE vehículo
$stmtHist = $pdo->prepare("
    SELECT pd.ProductoID, pd.TipoLinea, pd.Descripcion, pd.PrecioUnitario,
           ot.OrdenTrabajoID, ot.FechaIngreso, p.Stock, p.PrecioVenta AS PrecioActual
    FROM presupuestodetalle pd
    JOIN presupuestos pr ON pd.PresupuestoID = pr.PresupuestoID
    JOIN ordenestrabajo ot ON pr.OrdenTrabajoID = ot.OrdenTrabajoID
    LEFT JOIN productos p ON pd.ProductoID = p.ProductoID
    WHERE ot.VehiculoID = :vid AND pd.Aprobado = 1 AND ot.OrdenTrabajoID != :ot
    ORDER BY ot.OrdenTrabajoID DESC, pd.PresupuestoDetalleID ASC
");
$stmtHist->execute([':vid' => $ot['VehiculoID'], ':ot' => $otId]);

// Sugerencias para este auto: historial propio primero, luego lo comprobado en el mismo modelo.
$sugerencias = [];
foreach ($stmtHist->fetchAll() as $rh) {
    $clave = $rh['ProductoID'] ? ('p_' . $rh['ProductoID']) : ('d_' . mb_strtolower(trim($rh['Descripcion'])));
    if (isset($sugerencias[$clave])) continue;
    $sugerencias[$clave] = [
        'producto_id' => (int)($rh['ProductoID'] ?? 0),
        'descripcion' => $rh['Descripcion'],
        'tipo' => $rh['TipoLinea'],
        'precio' => (int)($rh['PrecioActual'] ?: $rh['PrecioUnitario']),
        'stock' => $rh['Stock'],
        'origen' => 'Historial',
        'detalle' => 'Usado en ' . formatFolioOT($rh['OrdenTrabajoID']) . ' · ' . date('d/m/Y', strtotime($rh['FechaIngreso'])),
    ];
}

$stmtCompat = $pdo->prepare("
    SELECT cr.VecesUsado, p.ProductoID, p.Nombre, p.PrecioVenta, p.Stock
    FROM compatibilidadrepuestos cr
    JOIN productos p ON cr.ProductoID = p.ProductoID
    WHERE LOWER(cr.MarcaVehiculo) = LOWER(:marca) AND LOWER(cr.ModeloVehiculo) = LOWER(:modelo) AND p.Activo = TRUE
    ORDER BY cr.VecesUsado DESC, p.TipoRepuesto ASC
");
$stmtCompat->execute([':marca' => $ot['Marca'], ':modelo' => $ot['Modelo']]);
foreach ($stmtCompat->fetchAll() as $rc) {
    $clave = 'p_' . $rc['ProductoID'];
    if (isset($sugerencias[$clave])) continue;
    $sugerencias[$clave] = [
        'producto_id' => (int)$rc['ProductoID'],
        'descripcion' => $rc['Nombre'],
        'tipo' => 'Repuesto',
        'precio' => (int)$rc['PrecioVenta'],
        'stock' => $rc['Stock'],
        'origen' => 'Compatibilidad',
        'detalle' => 'Usado ' . (int)$rc['VecesUsado'] . ' veces en ' . $ot['Marca'] . ' ' . $ot['Modelo'],
    ];
}

require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/views/presupuesto.view.php';
require_once __DIR__ . '/includes/footer.php';
