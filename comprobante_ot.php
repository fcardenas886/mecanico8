<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$otId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, v.Color, v.VIN,
           c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono, c.RutCuerpo, c.RutDv,
           u.Nombre AS UsuarioNombre
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

$stmtChk = $pdo->prepare("
    SELECT ci.Nombre, ci.Categoria, cr.Valor, cr.Detalle
    FROM checklistrecepcion cr
    JOIN checklistitems ci ON cr.ChecklistItemID = ci.ChecklistItemID
    WHERE cr.OrdenTrabajoID = :id
    ORDER BY ci.Orden ASC
");
$stmtChk->execute([':id' => $otId]);
$checklist = $stmtChk->fetchAll();
$checklistPorCategoria = [];
foreach ($checklist as $row) {
    $checklistPorCategoria[$row['Categoria']][] = $row;
}

// Estación de Servicio y Fluidos
$stmtEst = $pdo->prepare("SELECT * FROM estacionservicio_ot WHERE OrdenTrabajoID = :id");
$stmtEst->execute([':id' => $otId]);
$estacion = $stmtEst->fetch() ?: null;

// Operaciones Solicitadas
$stmtOps = $pdo->prepare("SELECT NombreOperacion FROM orden_operaciones_solicitadas WHERE OrdenTrabajoID = :id");
$stmtOps->execute([':id' => $otId]);
$operacionesSolicitadas = $stmtOps->fetchAll(PDO::FETCH_COLUMN);

// Daños de carrocería decodificados
$daniosCarroceria = json_decode($ot['DaniosCarroceriaJson'] ?? '[]', true) ?: [];

include __DIR__ . '/views/comprobante_ot.view.php';
require_once __DIR__ . '/includes/footer.php';
