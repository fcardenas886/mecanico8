<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$q = trim($_GET['q'] ?? '');

$stmt = $pdo->prepare("
    SELECT ot.OrdenTrabajoID, ot.VehiculoID, ot.FechaIngreso, ot.Estado, ot.KilometrajeIngreso,
           v.Patente, v.Marca, v.Modelo,
           c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono,
           u.Nombre AS UsuarioNombre,
           p.PresupuestoID, p.DecisionCliente,
           (SELECT COALESCE(SUM(Subtotal), 0) FROM presupuestodetalle pd WHERE pd.PresupuestoID = p.PresupuestoID) AS TotalPresupuesto,
           (SELECT COUNT(*) FROM presupuestodetalle pd WHERE pd.PresupuestoID = p.PresupuestoID) AS CantidadLineasPresupuesto
    FROM ordenestrabajo ot
    JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
    JOIN clientes c ON ot.ClienteID = c.ClienteID
    JOIN usuarios u ON ot.UsuarioID = u.UsuarioID
    LEFT JOIN (
        SELECT p1.*
        FROM presupuestos p1
        INNER JOIN (
            SELECT OrdenTrabajoID, MAX(PresupuestoID) AS MaxPresupuestoID
            FROM presupuestos
            GROUP BY OrdenTrabajoID
        ) p2 ON p1.PresupuestoID = p2.MaxPresupuestoID
    ) p ON ot.OrdenTrabajoID = p.OrdenTrabajoID
    WHERE (:q = '' OR v.Patente LIKE :like OR c.Nombre LIKE :like2 OR ot.OrdenTrabajoID = :idnum)
    ORDER BY ot.OrdenTrabajoID DESC
    LIMIT 200
");
$idNum = ctype_digit($q) ? (int)$q : 0;
$stmt->execute([':q' => $q, ':like' => "%$q%", ':like2' => "%$q%", ':idnum' => $idNum]);
$ordenes = $stmt->fetchAll();

include __DIR__ . '/views/ordenestrabajo.view.php';
require_once __DIR__ . '/includes/footer.php';
