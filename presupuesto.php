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

$presupuesto = cargarPresupuesto($pdo, $otId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'crear_presupuesto' && !$presupuesto) {
        $stmt = $pdo->prepare("INSERT INTO presupuestos (OrdenTrabajoID, UsuarioID) VALUES (:ot, :uid)");
        $stmt->execute([':ot' => $otId, ':uid' => $user['id']]);
        $presupuesto = cargarPresupuesto($pdo, $otId);
    }

    if ($action === 'actualizar_telefono_cliente') {
        $nuevoTel = trim($_POST['telefono'] ?? '');
        $pdo->prepare("UPDATE clientes SET Telefono = :tel WHERE ClienteID = :cid")
            ->execute([':tel' => $nuevoTel, ':cid' => $ot['ClienteID']]);
        $ot['ClienteTelefono'] = $nuevoTel;
        $message = 'Teléfono del cliente guardado exitosamente.';
    }

    if ($presupuesto && $presupuesto['DecisionCliente'] === 'Pendiente') {

        $origen = trim($_POST['origen'] ?? 'Directo');
        if (!in_array($origen, ['Cliente', 'Diagnostico', 'Fluidos', 'Directo'], true)) {
            $origen = 'Directo';
        }

        if ($action === 'agregar_repuesto') {
            $productoId = (int)($_POST['producto_id'] ?? 0);
            $cantidad = (float)($_POST['cantidad'] ?? 1);
            $stmtP = $pdo->prepare("SELECT Nombre, PrecioVenta FROM productos WHERE ProductoID = :id AND Activo = TRUE");
            $stmtP->execute([':id' => $productoId]);
            $prod = $stmtP->fetch();
            if ($prod && $cantidad > 0) {
                $subtotal = (int)round($prod['PrecioVenta'] * $cantidad);
                $pdo->prepare("
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, ProductoID, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                    VALUES (:pid, 'Repuesto', :prodid, :desc, :cant, :precio, :subtotal, :origen)
                ")->execute([
                    ':pid' => $presupuesto['PresupuestoID'], ':prodid' => $productoId, ':desc' => $prod['Nombre'],
                    ':cant' => $cantidad, ':precio' => $prod['PrecioVenta'], ':subtotal' => $subtotal, ':origen' => $origen
                ]);
                $message = 'Repuesto agregado correctamente.';
            } else {
                $error = 'Selecciona un repuesto válido.';
            }
        } elseif ($action === 'agregar_servicio') {
            $stmtS = $pdo->prepare("SELECT OperacionID, Nombre, PrecioBase, PoliticaCobro FROM operacionessolicitadas WHERE OperacionID = :id AND Activo = TRUE AND PrecioBase > 0");
            $stmtS->execute([':id' => (int)($_POST['servicio_id'] ?? 0)]);
            $serv = $stmtS->fetch();
            $cantidad = (float)($_POST['cantidad'] ?? 1);
            if ($serv && $cantidad > 0) {
                $subtotal = (int)round($serv['PrecioBase'] * $cantidad);
                $pdo->prepare("
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, ServicioID, PoliticaCobro, Origen)
                    VALUES (:pid, 'ManoObra', :desc, :cant, :precio, :subtotal, :sid, :pol, :origen)
                ")->execute([
                    ':pid' => $presupuesto['PresupuestoID'], ':desc' => $serv['Nombre'], ':cant' => $cantidad,
                    ':precio' => $serv['PrecioBase'], ':subtotal' => $subtotal, ':sid' => $serv['OperacionID'], ':pol' => $serv['PoliticaCobro'],
                    ':origen' => $origen
                ]);
                $message = 'Servicio agregado correctamente.';
            } else {
                $error = 'Selecciona un servicio válido.';
            }
        } elseif ($action === 'cambiar_politica') {
            $lineaId = (int)($_POST['linea_id'] ?? 0);
            $nueva = ($_POST['politica'] ?? '') === 'SoloSiNoAprueba' ? 'SoloSiNoAprueba' : 'Siempre';
            $pdo->prepare("UPDATE presupuestodetalle SET PoliticaCobro = :p WHERE PresupuestoDetalleID = :id AND PresupuestoID = :pid AND TipoLinea = 'ManoObra'")
                ->execute([':p' => $nueva, ':id' => $lineaId, ':pid' => $presupuesto['PresupuestoID']]);
        } elseif ($action === 'agregar_linea') {
            $tipo = $_POST['tipo'] ?? '';
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = (int)($_POST['precio'] ?? 0);
            $cantidad = (float)($_POST['cantidad'] ?? 1);
            if (in_array($tipo, ['ManoObra', 'Repuesto', 'Terceros'], true) && $descripcion !== '' && $precio > 0 && $cantidad > 0) {
                $subtotal = (int)round($precio * $cantidad);
                $pdo->prepare("
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                    VALUES (:pid, :tipo, :desc, :cant, :precio, :subtotal, :origen)
                ")->execute([
                    ':pid' => $presupuesto['PresupuestoID'], ':tipo' => $tipo, ':desc' => $descripcion,
                    ':cant' => $cantidad, ':precio' => $precio, ':subtotal' => $subtotal, ':origen' => $origen
                ]);
                $message = 'Línea agregada correctamente.';
            } else {
                $error = 'Completa descripción, precio y cantidad.';
            }
        } elseif ($action === 'cotizar_operacion_rapida') {
            $opId = (int)($_POST['operacion_solicitada_id'] ?? 0);
            $stmtOp = $pdo->prepare("SELECT * FROM orden_operaciones_solicitadas WHERE ID = :id AND OrdenTrabajoID = :ot");
            $stmtOp->execute([':id' => $opId, ':ot' => $otId]);
            $opRow = $stmtOp->fetch();
            if ($opRow) {
                $precioBase = 25000;
                $servicioId = null;
                $pol = 'Siempre';
                if (!empty($opRow['OperacionID'])) {
                    $stmtS = $pdo->prepare("SELECT PrecioBase, PoliticaCobro FROM operacionessolicitadas WHERE OperacionID = :id");
                    $stmtS->execute([':id' => $opRow['OperacionID']]);
                    $sRow = $stmtS->fetch();
                    if ($sRow && (int)$sRow['PrecioBase'] > 0) {
                        $precioBase = (int)$sRow['PrecioBase'];
                        $pol = $sRow['PoliticaCobro'];
                        $servicioId = $opRow['OperacionID'];
                    }
                }
                $pdo->prepare("
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, ServicioID, PoliticaCobro, Origen)
                    VALUES (:pid, 'ManoObra', :desc, 1, :precio, :subtotal, :sid, :pol, 'Cliente')
                ")->execute([
                    ':pid' => $presupuesto['PresupuestoID'],
                    ':desc' => $opRow['NombreOperacion'],
                    ':precio' => $precioBase,
                    ':subtotal' => $precioBase,
                    ':sid' => $servicioId,
                    ':pol' => $pol
                ]);
                $message = "Operación '{$opRow['NombreOperacion']}' incorporada al presupuesto.";
            }
        } elseif ($action === 'cotizar_hallazgo_rapido') {
            $diagId = (int)($_POST['diagnostico_id'] ?? 0);
            $stmtD = $pdo->prepare("SELECT * FROM diagnosticoot WHERE DiagnosticoID = :id AND OrdenTrabajoID = :ot");
            $stmtD->execute([':id' => $diagId, ':ot' => $otId]);
            $diagRow = $stmtD->fetch();
            if ($diagRow) {
                $desc = "Solución " . $diagRow['Area'] . ": " . $diagRow['Hallazgo'];
                $precio = (int)($_POST['precio'] ?? 25000) ?: 25000;
                $tipo = in_array($_POST['tipo'] ?? '', ['ManoObra', 'Repuesto', 'Terceros'], true) ? $_POST['tipo'] : 'ManoObra';
                $pdo->prepare("
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                    VALUES (:pid, :tipo, :desc, 1, :precio, :subtotal, 'Diagnostico')
                ")->execute([
                    ':pid' => $presupuesto['PresupuestoID'],
                    ':tipo' => $tipo,
                    ':desc' => $desc,
                    ':precio' => $precio,
                    ':subtotal' => $precio
                ]);
                $message = "Hallazgo técnico incorporado al presupuesto.";
            }
        } elseif ($action === 'cargar_paquete_fluidos') {
            $stmtEstCheck = $pdo->prepare("SELECT * FROM estacionservicio_ot WHERE OrdenTrabajoID = :ot");
            $stmtEstCheck->execute([':ot' => $otId]);
            $estRow = $stmtEstCheck->fetch();

            if ($estRow) {
                $agregados = 0;

                // Buscar si el auto ya usó un filtro o aceite anteriormente en este vehículo o modelo
                $filtroCompat = null;
                $aceiteCompat = null;

                $stmtPrev = $pdo->prepare("
                    SELECT pd.ProductoID, pd.Descripcion, pd.PrecioUnitario, p.Nombre AS ProdNombre, p.PrecioVenta, p.TipoRepuesto
                    FROM presupuestodetalle pd
                    JOIN presupuestos pr ON pd.PresupuestoID = pr.PresupuestoID
                    JOIN ordenestrabajo ot ON pr.OrdenTrabajoID = ot.OrdenTrabajoID
                    LEFT JOIN productos p ON pd.ProductoID = p.ProductoID
                    WHERE ot.VehiculoID = :vid AND pd.Aprobado = 1 AND ot.OrdenTrabajoID != :ot
                    ORDER BY ot.OrdenTrabajoID DESC
                ");
                $stmtPrev->execute([':vid' => $ot['VehiculoID'], ':ot' => $otId]);
                foreach ($stmtPrev->fetchAll() as $rowPrev) {
                    $descLow = mb_strtolower($rowPrev['Descripcion']);
                    if (!$filtroCompat && (str_contains($descLow, 'filtro') && str_contains($descLow, 'aceite'))) {
                        $filtroCompat = $rowPrev;
                    }
                    if (!$aceiteCompat && (str_contains($descLow, 'aceite') && (str_contains($descLow, '5w') || str_contains($descLow, '10w') || str_contains($descLow, 'sintetico') || str_contains($descLow, 'sintético')))) {
                        $aceiteCompat = $rowPrev;
                    }
                }

                if (!$filtroCompat || !$aceiteCompat) {
                    $stmtMCompat = $pdo->prepare("
                        SELECT cr.*, p.ProductoID, p.Nombre AS ProdNombre, p.PrecioVenta, p.TipoRepuesto, p.Descripcion
                        FROM compatibilidadrepuestos cr
                        JOIN productos p ON cr.ProductoID = p.ProductoID
                        WHERE LOWER(cr.MarcaVehiculo) = LOWER(:m) AND LOWER(cr.ModeloVehiculo) = LOWER(:mo) AND p.Activo = TRUE
                        ORDER BY cr.VecesUsado DESC
                    ");
                    $stmtMCompat->execute([':m' => $ot['Marca'], ':mo' => $ot['Modelo']]);
                    foreach ($stmtMCompat->fetchAll() as $rComp) {
                        if (!$filtroCompat && $rComp['TipoRepuesto'] === 'FiltroAceite') $filtroCompat = $rComp;
                        if (!$aceiteCompat && $rComp['TipoRepuesto'] === 'Aceite') $aceiteCompat = $rComp;
                    }
                }

                if (!empty($estRow['MotorCambio'])) {
                    // Mano de obra de cambio de aceite y filtro
                    $pdo->prepare("
                        INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                        VALUES (:pid, 'ManoObra', 'Servicio de Cambio de Aceite y Filtro', 1, 15000, 15000, 'Fluidos')
                    ")->execute([':pid' => $presupuesto['PresupuestoID']]);
                    $agregados++;

                    // Aceite de motor exacto o genérico
                    $descAceite = $aceiteCompat ? ($aceiteCompat['ProdNombre'] ?? $aceiteCompat['Descripcion']) : 'Aceite de Motor Sintético 5W-30 (4 Litros)';
                    $precioAceite = $aceiteCompat ? (int)($aceiteCompat['PrecioVenta'] ?? $aceiteCompat['PrecioUnitario'] ?? 35000) : 35000;
                    $prodIdAceite = !empty($aceiteCompat['ProductoID']) ? (int)$aceiteCompat['ProductoID'] : null;

                    $pdo->prepare("
                        INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, ProductoID, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                        VALUES (:pid, 'Repuesto', :prodid, :desc, 1, :precio, :subtotal, 'Fluidos')
                    ")->execute([
                        ':pid' => $presupuesto['PresupuestoID'],
                        ':prodid' => $prodIdAceite,
                        ':desc' => $descAceite,
                        ':precio' => $precioAceite,
                        ':subtotal' => $precioAceite
                    ]);
                    $agregados++;

                    // Filtro de aceite exacto o estándar (siempre acompaña al cambio de aceite)
                    $descFiltro = $filtroCompat ? ($filtroCompat['ProdNombre'] ?? $filtroCompat['Descripcion']) : 'Filtro de Aceite de Motor';
                    $precioFiltro = $filtroCompat ? (int)($filtroCompat['PrecioVenta'] ?? $filtroCompat['PrecioUnitario'] ?? 6900) : 6900;
                    $prodIdFiltro = !empty($filtroCompat['ProductoID']) ? (int)$filtroCompat['ProductoID'] : null;

                    $pdo->prepare("
                        INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, ProductoID, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                        VALUES (:pid, 'Repuesto', :prodid, :desc, 1, :precio, :subtotal, 'Fluidos')
                    ")->execute([
                        ':pid' => $presupuesto['PresupuestoID'],
                        ':prodid' => $prodIdFiltro,
                        ':desc' => $descFiltro,
                        ':precio' => $precioFiltro,
                        ':subtotal' => $precioFiltro
                    ]);
                    $agregados++;
                }

                if (!empty($estRow['FrenosCambio'])) {
                    $pdo->prepare("
                        INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                        VALUES (:pid, 'ManoObra', 'Purga y Reemplazo de Líquido de Frenos DOT4', 1, 20000, 20000, 'Fluidos')
                    ")->execute([':pid' => $presupuesto['PresupuestoID']]);
                    $agregados++;
                }
                if (!empty($estRow['RadiadorAnticongelante'])) {
                    $pdo->prepare("
                        INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                        VALUES (:pid, 'Repuesto', 'Refrigerante / Anticongelante 50/50 Concentrado', 1, 12000, 12000, 'Fluidos')
                    ")->execute([':pid' => $presupuesto['PresupuestoID']]);
                    $agregados++;
                }
                if (!empty($estRow['CajaCambio'])) {
                    $pdo->prepare("
                        INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                        VALUES (:pid, 'ManoObra', 'Servicio de Cambio de Aceite de Caja/Transmisión', 1, 25000, 25000, 'Fluidos')
                    ")->execute([':pid' => $presupuesto['PresupuestoID']]);
                    $agregados++;
                }
                $message = "Se cargaron {$agregados} ítems de lubricación y fluidos al presupuesto.";
            } else {
                $error = 'No se encontró chequeo de fluidos registrado para esta orden.';
            }
        } elseif ($action === 'reutilizar_repuesto_historial') {
            $prodId = (int)($_POST['producto_id'] ?? 0);
            $desc = trim($_POST['descripcion'] ?? '');
            $precio = (int)($_POST['precio'] ?? 0);
            $tipo = in_array($_POST['tipo'] ?? '', ['Repuesto', 'ManoObra', 'Terceros'], true) ? $_POST['tipo'] : 'Repuesto';
            $origen = trim($_POST['origen'] ?? 'Directo');

            if ($prodId > 0) {
                $stmtP = $pdo->prepare("SELECT Nombre, PrecioVenta FROM productos WHERE ProductoID = :id AND Activo = TRUE");
                $stmtP->execute([':id' => $prodId]);
                $prodRow = $stmtP->fetch();
                if ($prodRow) {
                    $desc = $prodRow['Nombre'];
                    $precio = (int)$prodRow['PrecioVenta'];
                }
            }

            if ($desc !== '' && $precio > 0) {
                $pdo->prepare("
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, ProductoID, Descripcion, Cantidad, PrecioUnitario, Subtotal, Origen)
                    VALUES (:pid, :tipo, :prodid, :desc, 1, :precio, :subtotal, :origen)
                ")->execute([
                    ':pid' => $presupuesto['PresupuestoID'],
                    ':tipo' => $tipo,
                    ':prodid' => $prodId > 0 ? $prodId : null,
                    ':desc' => $desc,
                    ':precio' => $precio,
                    ':subtotal' => $precio,
                    ':origen' => $origen
                ]);
                $message = "Ítem '{$desc}' agregado exitosamente desde el historial.";
            } else {
                $error = 'No se pudo incorporar el ítem del historial.';
            }
        } elseif ($action === 'editar_precio') {
            // El precio de una línea se puede ajustar a mano. Un repuesto con precio distinto al de
            // lista queda fuera de los combos (precio final acordado), en el presupuesto y en la Caja.
            $lineaId = (int)($_POST['linea_id'] ?? 0);
            $precio = (int)($_POST['precio'] ?? 0);
            if ($precio > 0) {
                $pdo->prepare("
                    UPDATE presupuestodetalle
                    SET PrecioUnitario = :precio, Subtotal = ROUND(:precio2 * Cantidad)
                    WHERE PresupuestoDetalleID = :id AND PresupuestoID = :pid
                ")->execute([':precio' => $precio, ':precio2' => $precio, ':id' => $lineaId, ':pid' => $presupuesto['PresupuestoID']]);
                $message = 'Precio actualizado.';
            } else {
                $error = 'El precio debe ser mayor a 0.';
            }
        } elseif ($action === 'toggle_combos') {
            $aplica = !empty($_POST['aplica_combos']) ? 1 : 0;
            $pdo->prepare("UPDATE presupuestos SET AplicaCombos = :a WHERE PresupuestoID = :id")
                ->execute([':a' => $aplica, ':id' => $presupuesto['PresupuestoID']]);
            $presupuesto['AplicaCombos'] = $aplica;
            $message = $aplica ? 'Los combos de Promociones se aplican a este presupuesto.' : 'Este presupuesto va sin descuentos de combos.';
        } elseif ($action === 'eliminar_linea') {
            $lineaId = (int)($_POST['linea_id'] ?? 0);
            $pdo->prepare("DELETE FROM presupuestodetalle WHERE PresupuestoDetalleID = :id AND PresupuestoID = :pid")
                ->execute([':id' => $lineaId, ':pid' => $presupuesto['PresupuestoID']]);
            $message = 'Línea eliminada.';
        } elseif ($action === 'guardar_tiempo_entrega') {
            $tiempo = trim($_POST['tiempo_entrega'] ?? '');
            $pdo->prepare("UPDATE presupuestos SET TiempoEntrega = :t WHERE PresupuestoID = :id")
                ->execute([':t' => $tiempo ?: null, ':id' => $presupuesto['PresupuestoID']]);
            $message = 'Tiempo de entrega guardado.';
        } elseif ($action === 'decidir') {
            $decision = $_POST['decision'] ?? '';
            if (in_array($decision, ['AprobadoTotal', 'AprobadoParcial', 'Rechazado'], true)) {
                $pdo->beginTransaction();
                try {
                    if ($decision === 'AprobadoParcial') {
                        $aprobadas = array_map('intval', $_POST['lineas_aprobadas'] ?? []);
                        $stmtLineas = $pdo->prepare("SELECT PresupuestoDetalleID FROM presupuestodetalle WHERE PresupuestoID = :pid");
                        $stmtLineas->execute([':pid' => $presupuesto['PresupuestoID']]);
                        foreach ($stmtLineas->fetchAll(PDO::FETCH_COLUMN) as $lineaId) {
                            $aprobado = in_array((int)$lineaId, $aprobadas, true) ? 1 : 0;
                            $pdo->prepare("UPDATE presupuestodetalle SET Aprobado = :ap WHERE PresupuestoDetalleID = :id")
                                ->execute([':ap' => $aprobado, ':id' => $lineaId]);
                        }
                    } elseif ($decision === 'Rechazado') {
                        $pdo->prepare("UPDATE presupuestodetalle SET Aprobado = 0 WHERE PresupuestoID = :pid")
                            ->execute([':pid' => $presupuesto['PresupuestoID']]);
                    }

                    // Líneas condicionales (ej. diagnóstico): solo se cobran si el cliente no aprueba trabajo.
                    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM presupuestodetalle WHERE PresupuestoID = :pid AND PoliticaCobro <> 'SoloSiNoAprueba' AND Aprobado = 1");
                    $stmtC->execute([':pid' => $presupuesto['PresupuestoID']]);
                    $cobraCondicional = ((int)$stmtC->fetchColumn() === 0) ? 1 : 0;
                    $pdo->prepare("UPDATE presupuestodetalle SET Aprobado = :ap WHERE PresupuestoID = :pid AND PoliticaCobro = 'SoloSiNoAprueba'")
                        ->execute([':ap' => $cobraCondicional, ':pid' => $presupuesto['PresupuestoID']]);

                    $pdo->prepare("UPDATE presupuestos SET DecisionCliente = :d, FechaDecision = NOW() WHERE PresupuestoID = :id")
                        ->execute([':d' => $decision, ':id' => $presupuesto['PresupuestoID']]);

                    $estadoOT = $decision === 'Rechazado' ? 'Presupuesto rechazado' : 'Presupuesto aprobado';
                    $pdo->prepare("UPDATE ordenestrabajo SET Estado = :e WHERE OrdenTrabajoID = :id")
                        ->execute([':e' => $estadoOT, ':id' => $otId]);
                    $pdo->commit();

                    if ($decision !== 'Rechazado') {
                        if (!headers_sent()) {
                            header("Location: ejecucion.php?id={$otId}&aprobado=1");
                            exit;
                        } else {
                            echo "<script>window.location.href='ejecucion.php?id={$otId}&aprobado=1';</script>";
                            exit;
                        }
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Error al registrar la decisión: ' . $e->getMessage();
                }
            }
        }

        $presupuesto = cargarPresupuesto($pdo, $otId);
    }
}

$lineas = [];
$total = 0;
if ($presupuesto) {
    $stmtL = $pdo->prepare("SELECT * FROM presupuestodetalle WHERE PresupuestoID = :pid ORDER BY PresupuestoDetalleID ASC");
    $stmtL->execute([':pid' => $presupuesto['PresupuestoID']]);
    $lineas = $stmtL->fetchAll();
    foreach ($lineas as $l) { if ($l['PoliticaCobro'] !== 'SoloSiNoAprueba') $total += $l['Subtotal']; }
}

$totalAprobado = 0;
foreach ($lineas as $l) { if ($l['Aprobado']) $totalAprobado += $l['Subtotal']; }

// Combos de Promociones (ej. 1 Aceite + 1 Filtro): mismo motor que la Caja, para que el presupuesto
// diga lo mismo que se va a cobrar. Pendiente: sobre todas las líneas; decidido: solo las aprobadas.
$combosPresupuesto = ['descuento' => 0, 'combos' => []];
$combosAprobado = ['descuento' => 0, 'combos' => []];
$aplicaCombos = $presupuesto ? (int)($presupuesto['AplicaCombos'] ?? 1) === 1 : true;
if ($presupuesto) {
    // Las promociones individuales de cada producto se aplican siempre (como en la Caja);
    // el interruptor solo controla los combos.
    $lineasBase = array_filter($lineas, fn($l) => $l['PoliticaCobro'] !== 'SoloSiNoAprueba');
    $combosPresupuesto = calcularCombosPresupuesto($pdo, $lineasBase, $aplicaCombos);
    $combosAprobado = calcularCombosPresupuesto($pdo, array_filter($lineas, fn($l) => $l['Aprobado']), $aplicaCombos);
}
// Precio de lista actual de cada repuesto, para marcar las líneas cuyo precio se editó a mano.
$preciosLista = [];
$idsProd = array_values(array_unique(array_filter(array_map(fn($l) => (int)($l['ProductoID'] ?? 0), $lineas))));
if (!empty($idsProd)) {
    $stmtPL = $pdo->prepare('SELECT ProductoID, PrecioVenta FROM productos WHERE ProductoID IN (' . implode(',', array_fill(0, count($idsProd), '?')) . ')');
    $stmtPL->execute($idsProd);
    foreach ($stmtPL->fetchAll() as $r) $preciosLista[(int)$r['ProductoID']] = (int)$r['PrecioVenta'];
}
$subtotalSinCombos = $total;
$total -= $combosPresupuesto['descuento'];
$totalAprobado -= $combosAprobado['descuento'];

// 1. Cargar Operaciones solicitadas por el cliente en Recepción
$stmtOp = $pdo->prepare("
    SELECT oos.*, op.PrecioBase, op.PoliticaCobro, op.Categoria
    FROM orden_operaciones_solicitadas oos
    LEFT JOIN operacionessolicitadas op ON oos.OperacionID = op.OperacionID
    WHERE oos.OrdenTrabajoID = :ot
    ORDER BY oos.ID ASC
");
$stmtOp->execute([':ot' => $otId]);
$operacionesSolicitadas = $stmtOp->fetchAll();

// 2. Cargar Hallazgos del diagnóstico técnico
$stmtDiag = $pdo->prepare("
    SELECT d.*, u.Nombre AS MecanicoNombre
    FROM diagnosticoot d
    LEFT JOIN usuarios u ON d.UsuarioID = u.UsuarioID
    WHERE d.OrdenTrabajoID = :id
    ORDER BY d.Fecha ASC
");
$stmtDiag->execute([':id' => $otId]);
$hallazgos = $stmtDiag->fetchAll();

// 3. Cargar Chequeo de fluidos (Estación de Servicio)
$stmtEst = $pdo->prepare("SELECT * FROM estacionservicio_ot WHERE OrdenTrabajoID = :ot");
$stmtEst->execute([':ot' => $otId]);
$estacion = $stmtEst->fetch() ?: null;

$servicios = $pdo->query("SELECT OperacionID, Nombre, Categoria, PrecioBase FROM operacionessolicitadas WHERE Activo = TRUE AND PrecioBase > 0 ORDER BY EsDiagnosticoBase DESC, Categoria, Orden")->fetchAll();
$productos = $pdo->query("SELECT ProductoID, Nombre, PrecioVenta, Stock, CodigoBarras, MarcaRepuesto, NumeroParteOEM, NumeroParteAlternativo FROM productos WHERE Activo = TRUE ORDER BY Nombre ASC")->fetchAll();

// 4. Historial de repuestos e insumos utilizados anteriormente en ESTE vehículo
$stmtHist = $pdo->prepare("
    SELECT pd.ProductoID, pd.TipoLinea, pd.Descripcion, pd.PrecioUnitario, pd.Cantidad,
           ot.OrdenTrabajoID, ot.FechaIngreso,
           p.Stock, p.PrecioVenta AS PrecioActual, p.TipoRepuesto, p.MarcaRepuesto
    FROM presupuestodetalle pd
    JOIN presupuestos pr ON pd.PresupuestoID = pr.PresupuestoID
    JOIN ordenestrabajo ot ON pr.OrdenTrabajoID = ot.OrdenTrabajoID
    LEFT JOIN productos p ON pd.ProductoID = p.ProductoID
    WHERE ot.VehiculoID = :vid AND pd.Aprobado = 1 AND ot.OrdenTrabajoID != :ot
    ORDER BY ot.OrdenTrabajoID DESC, pd.PresupuestoDetalleID ASC
");
$stmtHist->execute([':vid' => $ot['VehiculoID'], ':ot' => $otId]);
$repuestosHistorialVehiculo = $stmtHist->fetchAll();

$repuestosAnterioresUnicos = [];
$serviciosAnterioresUnicos = [];
foreach ($repuestosHistorialVehiculo as $rh) {
    $clave = $rh['ProductoID'] ? ('p_' . $rh['ProductoID']) : ('d_' . mb_strtolower(trim($rh['Descripcion'])));
    if ($rh['TipoLinea'] === 'Repuesto') {
        if (!isset($repuestosAnterioresUnicos[$clave])) {
            $repuestosAnterioresUnicos[$clave] = $rh;
        }
    } else {
        if (!isset($serviciosAnterioresUnicos[$clave])) {
            $serviciosAnterioresUnicos[$clave] = $rh;
        }
    }
}

// 5. Repuestos aprendidos / compatibles con el modelo general
$stmtCompat = $pdo->prepare("
    SELECT cr.*, p.ProductoID, p.Nombre AS ProductoNombre, p.MarcaRepuesto, p.NumeroParteOEM,
           p.NumeroParteAlternativo, p.TipoRepuesto, p.PrecioVenta, p.Stock
    FROM compatibilidadrepuestos cr
    JOIN productos p ON cr.ProductoID = p.ProductoID
    WHERE LOWER(cr.MarcaVehiculo) = LOWER(:marca) AND LOWER(cr.ModeloVehiculo) = LOWER(:modelo) AND p.Activo = TRUE
    ORDER BY cr.VecesUsado DESC, p.TipoRepuesto ASC
");
$stmtCompat->execute([':marca' => $ot['Marca'], ':modelo' => $ot['Modelo']]);
$repuestosCompatiblesBruto = $stmtCompat->fetchAll();

// Filtrar para que en compatibles SOLO aparezcan los que NO están ya en el historial propio del vehículo
$repuestosCompatiblesModelo = [];
foreach ($repuestosCompatiblesBruto as $rcm) {
    $yaEnAuto = false;
    foreach ($repuestosAnterioresUnicos as $rAnt) {
        if ((int)$rAnt['ProductoID'] === (int)$rcm['ProductoID']) {
            $yaEnAuto = true;
            break;
        }
    }
    if (!$yaEnAuto) {
        $repuestosCompatiblesModelo[] = $rcm;
    }
}

// 6. Detectar filtro y aceite sugeridos para guiar al usuario
$filtroSugerido = null;
$aceiteSugerido = null;

foreach ($repuestosAnterioresUnicos as $ru) {
    $descLow = mb_strtolower($ru['Descripcion']);
    if (!$filtroSugerido && (str_contains($descLow, 'filtro') && str_contains($descLow, 'aceite'))) {
        $filtroSugerido = [
            'Nombre' => $ru['Descripcion'],
            'ProductoID' => $ru['ProductoID'],
            'Precio' => (int)($ru['PrecioActual'] ?: $ru['PrecioUnitario']),
            'Origen' => 'HistorialVehiculo',
            'OT' => $ru['OrdenTrabajoID'],
            'Stock' => $ru['Stock']
        ];
    }
    if (!$aceiteSugerido && (str_contains($descLow, 'aceite') && (str_contains($descLow, '5w') || str_contains($descLow, '10w') || str_contains($descLow, 'sintetico') || str_contains($descLow, 'sintético')))) {
        $aceiteSugerido = [
            'Nombre' => $ru['Descripcion'],
            'ProductoID' => $ru['ProductoID'],
            'Precio' => (int)($ru['PrecioActual'] ?: $ru['PrecioUnitario']),
            'Origen' => 'HistorialVehiculo',
            'OT' => $ru['OrdenTrabajoID'],
            'Stock' => $ru['Stock']
        ];
    }
}

if (!$filtroSugerido || !$aceiteSugerido) {
    foreach ($repuestosCompatiblesBruto as $rcm) {
        if (!$filtroSugerido && $rcm['TipoRepuesto'] === 'FiltroAceite') {
            $filtroSugerido = [
                'Nombre' => $rcm['ProductoNombre'],
                'ProductoID' => $rcm['ProductoID'],
                'Precio' => (int)$rcm['PrecioVenta'],
                'Origen' => 'CompatibilidadModelo',
                'OT' => null,
                'Stock' => $rcm['Stock']
            ];
        }
        if (!$aceiteSugerido && $rcm['TipoRepuesto'] === 'Aceite') {
            $aceiteSugerido = [
                'Nombre' => $rcm['ProductoNombre'],
                'ProductoID' => $rcm['ProductoID'],
                'Precio' => (int)$rcm['PrecioVenta'],
                'Origen' => 'CompatibilidadModelo',
                'OT' => null,
                'Stock' => $rcm['Stock']
            ];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/views/presupuesto.view.php';
require_once __DIR__ . '/includes/footer.php';
