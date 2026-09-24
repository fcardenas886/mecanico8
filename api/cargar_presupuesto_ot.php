<?php
// Entrega las líneas aprobadas del presupuesto de una OT (repuestos + mano de
// obra/terceros como servicio), en un formato que pos.js pueda cargar directo
// al carrito con cargarPresupuestoOT(), igual que ya hace cargarCotizacion().
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$otId = (int)($_GET['ot_id'] ?? 0);

$pdo = getDB();

$stmtOT = $pdo->prepare("SELECT OrdenTrabajoID, ClienteID, VentaID FROM ordenestrabajo WHERE OrdenTrabajoID = :id");
$stmtOT->execute([':id' => $otId]);
$ot = $stmtOT->fetch();

if (!$ot) {
    echo json_encode(['success' => false, 'error' => 'Orden de Trabajo no encontrada.']);
    exit;
}
if ($ot['VentaID']) {
    echo json_encode(['success' => false, 'error' => 'Esta OT ya fue cobrada (Venta #' . $ot['VentaID'] . ').']);
    exit;
}

$stmtP = $pdo->prepare("SELECT PresupuestoID, AplicaCombos FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
$stmtP->execute([':id' => $otId]);
$presupuesto = $stmtP->fetch();

if (!$presupuesto) {
    echo json_encode(['success' => false, 'error' => 'Esta OT no tiene presupuesto.']);
    exit;
}

// Repuestos: llevan ProductoID y Stock real, igual que cualquier venta de mostrador.
$stmtRep = $pdo->prepare("
    SELECT pd.ProductoID, p.Nombre, pd.PrecioUnitario, p.Stock, pd.Cantidad, p.TipoRepuesto, p.PrecioVenta AS PrecioLista
    FROM presupuestodetalle pd
    JOIN productos p ON pd.ProductoID = p.ProductoID
    WHERE pd.PresupuestoID = :pid AND pd.TipoLinea = 'Repuesto' AND pd.Aprobado = 1
");
$stmtRep->execute([':pid' => $presupuesto['PresupuestoID']]);
$repuestos = $stmtRep->fetchAll();

// Mano de obra / terceros: sin producto ni stock, van como línea de servicio.
$stmtServ = $pdo->prepare("
    SELECT Descripcion, PrecioUnitario, Cantidad
    FROM presupuestodetalle
    WHERE PresupuestoID = :pid AND TipoLinea IN ('ManoObra', 'Terceros') AND Aprobado = 1
");
$stmtServ->execute([':pid' => $presupuesto['PresupuestoID']]);
$servicios = $stmtServ->fetchAll();

if (empty($repuestos) && empty($servicios)) {
    echo json_encode(['success' => false, 'error' => 'No hay líneas aprobadas en el presupuesto de esta OT.']);
    exit;
}

echo json_encode([
    'success' => true,
    'ot' => ['OrdenTrabajoID' => $ot['OrdenTrabajoID'], 'ClienteID' => $ot['ClienteID']],
    'repuestos' => $repuestos,
    'servicios' => $servicios,
    'aplica_combos' => (int)($presupuesto['AplicaCombos'] ?? 1) === 1,
]);
