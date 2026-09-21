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
    SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono
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
        if ($presupuesto) agregarDiagnosticoAlPresupuesto($pdo, (int)$presupuesto['PresupuestoID'], $ot['Estado']);
    }

    if ($presupuesto && $presupuesto['DecisionCliente'] === 'Pendiente') {

        if ($action === 'agregar_repuesto') {
            $productoId = (int)($_POST['producto_id'] ?? 0);
            $cantidad = (float)($_POST['cantidad'] ?? 1);
            $stmtP = $pdo->prepare("SELECT Nombre, PrecioVenta FROM productos WHERE ProductoID = :id AND Activo = TRUE");
            $stmtP->execute([':id' => $productoId]);
            $prod = $stmtP->fetch();
            if ($prod && $cantidad > 0) {
                $subtotal = (int)round($prod['PrecioVenta'] * $cantidad);
                $pdo->prepare("
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, ProductoID, Descripcion, Cantidad, PrecioUnitario, Subtotal)
                    VALUES (:pid, 'Repuesto', :prodid, :desc, :cant, :precio, :subtotal)
                ")->execute([
                    ':pid' => $presupuesto['PresupuestoID'], ':prodid' => $productoId, ':desc' => $prod['Nombre'],
                    ':cant' => $cantidad, ':precio' => $prod['PrecioVenta'], ':subtotal' => $subtotal
                ]);
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
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, ServicioID, PoliticaCobro)
                    VALUES (:pid, 'ManoObra', :desc, :cant, :precio, :subtotal, :sid, :pol)
                ")->execute([
                    ':pid' => $presupuesto['PresupuestoID'], ':desc' => $serv['Nombre'], ':cant' => $cantidad,
                    ':precio' => $serv['PrecioBase'], ':subtotal' => $subtotal, ':sid' => $serv['OperacionID'], ':pol' => $serv['PoliticaCobro'],
                ]);
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
            if (in_array($tipo, ['ManoObra', 'Terceros'], true) && $descripcion !== '' && $precio > 0 && $cantidad > 0) {
                $subtotal = (int)round($precio * $cantidad);
                $pdo->prepare("
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal)
                    VALUES (:pid, :tipo, :desc, :cant, :precio, :subtotal)
                ")->execute([
                    ':pid' => $presupuesto['PresupuestoID'], ':tipo' => $tipo, ':desc' => $descripcion,
                    ':cant' => $cantidad, ':precio' => $precio, ':subtotal' => $subtotal
                ]);
            } else {
                $error = 'Completa descripción, precio y cantidad.';
            }
        } elseif ($action === 'eliminar_linea') {
            $lineaId = (int)($_POST['linea_id'] ?? 0);
            $pdo->prepare("DELETE FROM presupuestodetalle WHERE PresupuestoDetalleID = :id AND PresupuestoID = :pid")
                ->execute([':id' => $lineaId, ':pid' => $presupuesto['PresupuestoID']]);
        } elseif ($action === 'guardar_tiempo_entrega') {
            $tiempo = trim($_POST['tiempo_entrega'] ?? '');
            $pdo->prepare("UPDATE presupuestos SET TiempoEntrega = :t WHERE PresupuestoID = :id")
                ->execute([':t' => $tiempo ?: null, ':id' => $presupuesto['PresupuestoID']]);
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
                    // AprobadoTotal: todas las líneas quedan Aprobado = 1 (valor por defecto al crearlas).

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

// Hallazgos del diagnóstico, para que el presupuestador los tenga a la vista.
$stmtDiag = $pdo->prepare("
    SELECT d.Area, d.Hallazgo FROM diagnosticoot d WHERE d.OrdenTrabajoID = :id ORDER BY d.Fecha ASC
");
$stmtDiag->execute([':id' => $otId]);
$hallazgos = $stmtDiag->fetchAll();

$servicios = $pdo->query("SELECT OperacionID, Nombre, Categoria, PrecioBase FROM operacionessolicitadas WHERE Activo = TRUE AND PrecioBase > 0 ORDER BY EsDiagnosticoBase DESC, Categoria, Orden")->fetchAll();
$productos = $pdo->query("SELECT ProductoID, Nombre, PrecioVenta, Stock, CodigoBarras, MarcaRepuesto, NumeroParteOEM, NumeroParteAlternativo FROM productos WHERE Activo = TRUE ORDER BY Nombre ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/views/presupuesto.view.php';
require_once __DIR__ . '/includes/footer.php';
