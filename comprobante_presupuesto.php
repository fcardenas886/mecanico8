<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$otId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, v.Color, v.VIN, v.KilometrajeUltimo,
           c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono, c.Email AS ClienteEmail,
           c.RutCuerpo, c.RutDv, u.Nombre AS UsuarioNombre
    FROM ordenestrabajo ot
    JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
    JOIN clientes c ON ot.ClienteID = c.ClienteID
    JOIN usuarios u ON ot.UsuarioID = u.UsuarioID
    WHERE ot.OrdenTrabajoID = :id
");
$stmt->execute([':id' => $otId]);
$ot = $stmt->fetch();

if (!$ot) {
    http_response_code(404);
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Orden de Trabajo no encontrada</h2><a href='ordenestrabajo.php'>Volver al listado</a></div>");
}

// Cargar presupuesto más reciente
$stmtP = $pdo->prepare("SELECT * FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
$stmtP->execute([':id' => $otId]);
$presupuesto = $stmtP->fetch();

if (!$presupuesto) {
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Esta Orden de Trabajo no tiene un presupuesto creado</h2><a href='presupuesto.php?id=$otId' class='btn btn-primary'>Crear Presupuesto</a></div>");
}

// Cargar líneas de detalle
$stmtL = $pdo->prepare("SELECT * FROM presupuestodetalle WHERE PresupuestoID = :pid ORDER BY TipoLinea ASC, PresupuestoDetalleID ASC");
$stmtL->execute([':pid' => $presupuesto['PresupuestoID']]);
$lineas = $stmtL->fetchAll();

$lineasRepuesto = [];
$lineasManoObra = [];
$lineasTerceros = [];
$totalPresupuesto = 0;
$totalAprobado = 0;

foreach ($lineas as $l) {
    if ($l['PoliticaCobro'] !== 'SoloSiNoAprueba') $totalPresupuesto += $l['Subtotal'];
    if ($l['Aprobado']) $totalAprobado += $l['Subtotal'];

    if ($l['TipoLinea'] === 'Repuesto') {
        $lineasRepuesto[] = $l;
    } elseif ($l['TipoLinea'] === 'ManoObra') {
        $lineasManoObra[] = $l;
    } else {
        $lineasTerceros[] = $l;
    }
}

// Cargar operaciones solicitadas y hallazgos para trazabilidad en el comprobante
$stmtOp = $pdo->prepare("SELECT * FROM orden_operaciones_solicitadas WHERE OrdenTrabajoID = :ot");
$stmtOp->execute([':ot' => $otId]);
$operacionesSolicitadas = $stmtOp->fetchAll();

$stmtDiag = $pdo->prepare("SELECT * FROM diagnosticoot WHERE OrdenTrabajoID = :ot ORDER BY Fecha ASC");
$stmtDiag->execute([':ot' => $otId]);
$hallazgos = $stmtDiag->fetchAll();

// Cargar configuraciones del taller
$configStmt = $pdo->query("SELECT Clave, Valor FROM configuraciones");
$configs = [];
while ($row = $configStmt->fetch()) {
    $configs[$row['Clave']] = $row['Valor'];
}

$nombreEmpresa = $configs['nombre_empresa'] ?? 'Taller Mecánico Especializado';
$rutEmpresa = $configs['rut_empresa'] ?? '76.123.456-7';
$direccionEmpresa = $configs['direccion_empresa'] ?? 'Av. Principal 1234';
$telefonoEmpresa = $configs['telefono_empresa'] ?? '+56 9 1234 5678';
$emailEmpresa = $configs['email_empresa'] ?? 'contacto@tallermecanico.cl';

include __DIR__ . '/views/comprobante_presupuesto.view.php';
require_once __DIR__ . '/includes/footer.php';
